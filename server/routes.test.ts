// @vitest-environment node
import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import express from "express";
import { createServer, type Server } from "http";
import type { AddressInfo } from "net";
import { registerRoutes } from "./routes";

let httpServer: Server;
let baseUrl: string;

const realFetch = globalThis.fetch;

/** Mock only outgoing SendPulse calls, delegating local test requests to real fetch. */
function mockSendPulse(response: Response) {
  const spy = vi.spyOn(globalThis, "fetch").mockImplementation((input: any, init?: any) => {
    const url = typeof input === "string" ? input : input?.url ?? String(input);
    if (url.includes("sendpulse.com")) return Promise.resolve(response);
    return realFetch(input, init);
  });
  return spy;
}

async function startServer() {
  const app = express();
  app.use(express.json());
  httpServer = createServer(app);
  await registerRoutes(httpServer, app);
  await new Promise<void>((resolve) => httpServer.listen(0, "127.0.0.1", resolve));
  const { port } = httpServer.address() as AddressInfo;
  baseUrl = `http://127.0.0.1:${port}`;
}

beforeEach(async () => {
  await startServer();
});

afterEach(async () => {
  await new Promise<void>((resolve) => httpServer.close(() => resolve()));
});

describe("POST /api/subscribe", () => {
  it("creates a new subscriber and returns 201", async () => {
    const res = await fetch(`${baseUrl}/api/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ fullName: "New Person", email: "new-person@example.com" }),
    });
    expect(res.status).toBe(201);
    const body = await res.json();
    expect(body.alreadySubscribed).toBe(false);
    expect(body.subscriber.email).toBe("new-person@example.com");
  });

  it("returns 200 and alreadySubscribed for a duplicate email", async () => {
    const payload = { fullName: "Dup", email: "duplicate@example.com" };
    await fetch(`${baseUrl}/api/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const res = await fetch(`${baseUrl}/api/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    expect(res.status).toBe(200);
    expect((await res.json()).alreadySubscribed).toBe(true);
  });

  it("rejects an invalid payload with 400", async () => {
    const res = await fetch(`${baseUrl}/api/subscribe`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ fullName: "X", email: "bad" }),
    });
    expect(res.status).toBe(400);
    expect(typeof (await res.json()).message).toBe("string");
  });
});

describe("POST /api/complaints", () => {
  const validComplaint = {
    name: "Jane Doe",
    email: "jane@example.com",
    description: "A sufficiently detailed complaint about media conduct.",
  };

  it("rejects an invalid payload with 400", async () => {
    const res = await fetch(`${baseUrl}/api/complaints`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name: "J" }),
    });
    expect(res.status).toBe(400);
    expect(typeof (await res.json()).message).toBe("string");
  });

  it("returns 500 when the mail service is not configured", async () => {
    vi.stubEnv("SENDPULSE_API_KEY", "");
    vi.stubEnv("MAIL_FROM", "");
    vi.stubEnv("COMPLAINT_RECIPIENT", "");
    const res = await fetch(`${baseUrl}/api/complaints`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(validComplaint),
    });
    expect(res.status).toBe(500);
    expect((await res.json()).message).toMatch(/not configured/i);
    vi.unstubAllEnvs();
  });

  it("sends the complaint email and returns success when configured", async () => {
    vi.stubEnv("SENDPULSE_API_KEY", "test-key");
    vi.stubEnv("MAIL_FROM", "from@example.com");
    vi.stubEnv("COMPLAINT_RECIPIENT", "to@example.com");

    const fetchSpy = mockSendPulse({
      ok: true,
      status: 200,
      text: async () => "",
    } as Response);

    const res = await fetch(`${baseUrl}/api/complaints`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        ...validComplaint,
        evidenceLink: "https://example.com/evidence",
        attachments: [{ filename: "evidence.pdf", content: "base64data" }],
      }),
    });

    expect(res.status).toBe(200);
    expect(await res.json()).toEqual({ success: true });
    const sendpulseCalls = fetchSpy.mock.calls.filter(([u]: any[]) =>
      String(u).includes("sendpulse.com"),
    );
    expect(sendpulseCalls).toHaveLength(1);
    const [url, options] = sendpulseCalls[0] as [string, any];
    expect(url).toBe("https://api.sendpulse.com/smtp/emails");
    expect(options.headers.Authorization).toBe("Bearer test-key");
    const sent = JSON.parse(options.body);
    expect(sent.email.to[0].email).toBe("to@example.com");
    expect(sent.email.from.email).toBe("from@example.com");
    expect(sent.email.attachments_binary).toEqual({ "evidence.pdf": "base64data" });
    expect(sent.email.text).toContain("jane@example.com");
    vi.unstubAllEnvs();
  });

  it("returns 500 when the email provider rejects the request", async () => {
    vi.stubEnv("SENDPULSE_API_KEY", "test-key");
    vi.stubEnv("MAIL_FROM", "from@example.com");
    vi.stubEnv("COMPLAINT_RECIPIENT", "to@example.com");

    vi.spyOn(globalThis, "fetch").mockImplementation((input: any, init?: any) => {
      const url = typeof input === "string" ? input : input?.url ?? String(input);
      if (url.includes("sendpulse.com")) {
        return Promise.resolve({
          ok: false,
          status: 502,
          statusText: "Bad Gateway",
          text: async () => "upstream error",
        } as Response);
      }
      return realFetch(input, init);
    });

    const res = await fetch(`${baseUrl}/api/complaints`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(validComplaint),
    });
    expect(res.status).toBe(500);
    expect((await res.json()).message).toMatch(/email provider rejected/i);
    vi.unstubAllEnvs();
  });
});
