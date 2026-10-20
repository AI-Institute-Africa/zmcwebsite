import { describe, it, expect, beforeEach, vi } from "vitest";
import {
  PORTAL_BASE_URL,
  PORTAL_LOGIN_URL,
  TRAINING_HUB_URL,
  PRAZ_EGP_URL,
  CONTACT_EMAIL,
  SOCIAL_LINKS,
  portalAssetUrl,
  portalApi,
} from "./api";

const BASE_URL = `${PORTAL_BASE_URL}/api/v1`;

function jsonResponse(body: unknown, init: { ok?: boolean; status?: number } = {}) {
  const ok = init.ok ?? true;
  const status = init.status ?? (ok ? 200 : 400);
  return {
    ok,
    status,
    statusText: ok ? "OK" : "Bad Request",
    json: vi.fn().mockResolvedValue(body),
    text: vi.fn().mockResolvedValue(JSON.stringify(body)),
  } as unknown as Response;
}

function mockFetch(response: Response) {
  const fn = vi.fn().mockResolvedValue(response);
  vi.stubGlobal("fetch", fn);
  return fn;
}

describe("api constants", () => {
  it("exposes the portal and external URLs", () => {
    expect(PORTAL_BASE_URL).toBe("https://portal.zmc.org.zw");
    expect(PORTAL_LOGIN_URL).toBe("https://portal.zmc.org.zw/login");
    expect(TRAINING_HUB_URL).toBe("https://traininghub.zmc.org.zw");
    expect(PRAZ_EGP_URL).toBe("https://egp.praz.org.zw/");
    expect(CONTACT_EMAIL).toBe("info@zmc.org.zw");
  });

  it("exposes all social links with https URLs", () => {
    const values = Object.values(SOCIAL_LINKS);
    expect(values).toHaveLength(6);
    for (const url of values) expect(url).toMatch(/^https:\/\//);
    expect(SOCIAL_LINKS.whatsapp).toContain("wa.me");
  });
});

describe("portalAssetUrl", () => {
  it("returns null for falsy paths", () => {
    expect(portalAssetUrl()).toBeNull();
    expect(portalAssetUrl(null)).toBeNull();
    expect(portalAssetUrl("")).toBeNull();
  });

  it("builds a storage URL for a given image path", () => {
    expect(portalAssetUrl("news/photo.jpg")).toBe(
      `${PORTAL_BASE_URL}/storage/news/photo.jpg`,
    );
  });
});

describe("portalApi content feeds", () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it("getStatus calls the status endpoint", async () => {
    const fetchMock = mockFetch(jsonResponse({ ok: true }));
    await expect(portalApi.getStatus()).resolves.toEqual({ ok: true });
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/status`);
  });

  it("getNews passes pagination query params", async () => {
    const fetchMock = mockFetch(jsonResponse({ data: [] }));
    await portalApi.getNews(2, 5);
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/news?page=2&per_page=5`);
  });

  it("getNews uses defaults", async () => {
    const fetchMock = mockFetch(jsonResponse({ data: [] }));
    await portalApi.getNews();
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/news?page=1&per_page=20`);
  });

  it("getNotices, getEvents, getVacancies, getTenders build correct URLs", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.getNotices();
    await portalApi.getEvents(3, 10);
    await portalApi.getVacancies();
    await portalApi.getTenders();
    expect(fetchMock).toHaveBeenNthCalledWith(1, `${BASE_URL}/notices?page=1&per_page=20`);
    expect(fetchMock).toHaveBeenNthCalledWith(2, `${BASE_URL}/events?page=3&per_page=10`);
    expect(fetchMock).toHaveBeenNthCalledWith(3, `${BASE_URL}/vacancies?page=1&per_page=20`);
    expect(fetchMock).toHaveBeenNthCalledWith(4, `${BASE_URL}/tenders?page=1&per_page=20`);
  });

  it("getDownloads hits the downloads endpoint", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.getDownloads();
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/downloads`);
  });

  it("getExternalContent hits external-content", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.getExternalContent();
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/external-content`);
  });

  it("getSingleRecord builds the correct URL", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.getSingleRecord("news", 42);
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/news/42`);
  });
});

describe("portalApi verification", () => {
  it("verifyAccreditation URL-encodes the number", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.verifyAccreditation("ACC 123/45");
    expect(fetchMock).toHaveBeenCalledWith(
      `${BASE_URL}/verify/accreditation/ACC%20123%2F45`,
    );
  });

  it("verifyRegistration URL-encodes the number", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.verifyRegistration("REG#1");
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/verify/registration/REG%231`);
  });
});

describe("portalApi directories", () => {
  it("getAccreditedPractitioners serialises params", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.getAccreditedPractitioners({ page: 2, search: "jane doe", medium: "radio" });
    const url = fetchMock.mock.calls[0][0] as string;
    expect(url.startsWith(`${BASE_URL}/accredited-practitioners?`)).toBe(true);
    expect(url).toContain("page=2");
    expect(url).toContain("search=jane+doe");
    expect(url).toContain("medium=radio");
  });

  it("getRegisteredMediaHouses serialises params", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.getRegisteredMediaHouses({ province: "Harare" });
    expect(fetchMock).toHaveBeenCalledWith(
      `${BASE_URL}/registered-mediahouses?province=Harare`,
    );
  });

  it("getHomeStats calls the public stats endpoint", async () => {
    const fetchMock = mockFetch(jsonResponse({ data: {} }));
    await portalApi.getHomeStats();
    expect(fetchMock).toHaveBeenCalledWith(`${BASE_URL}/public-stats`);
  });
});

describe("portalApi submissions", () => {
  it("submitContact sends JSON when given an object", async () => {
    const fetchMock = mockFetch(jsonResponse({ success: true }));
    await portalApi.submitContact({ name: "A" });
    const [, options] = fetchMock.mock.calls[0];
    expect(options.method).toBe("POST");
    expect(options.headers).toEqual({ "Content-Type": "application/json" });
    expect(options.body).toBe(JSON.stringify({ name: "A" }));
  });

  it("submitContact sends FormData without a JSON content type", async () => {
    const fetchMock = mockFetch(jsonResponse({ success: true }));
    const fd = new FormData();
    await portalApi.submitContact(fd);
    const [, options] = fetchMock.mock.calls[0];
    expect(options.headers).toEqual({});
    expect(options.body).toBe(fd);
  });

  it("submitComplaint posts to the complaints endpoint", async () => {
    const fetchMock = mockFetch(jsonResponse({ success: true }));
    const fd = new FormData();
    fd.append("full_name", "Joe");
    await portalApi.submitComplaint(fd);
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/complaints`);
    expect(fetchMock.mock.calls[0][1].method).toBe("POST");
  });

  it("submitAppeal with FormData sets only the Accept header", async () => {
    const fetchMock = mockFetch(jsonResponse({ data: { id: 1 } }));
    const fd = new FormData();
    await portalApi.submitAppeal(fd);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url).toBe(`${BASE_URL}/appeals`);
    expect(options.headers).toEqual({ Accept: "application/json" });
    expect(options.body).toBe(fd);
  });

  it("submitAppeal with an object sets JSON headers", async () => {
    const fetchMock = mockFetch(jsonResponse({ data: { id: 1 } }));
    await portalApi.submitAppeal({ a: 1 });
    expect(fetchMock.mock.calls[0][1].headers).toEqual({
      "Content-Type": "application/json",
      Accept: "application/json",
    });
  });

  it("initiateAppealPayment posts JSON and returns the parsed body", async () => {
    const fetchMock = mockFetch(jsonResponse({ redirect_url: "https://pay" }));
    const res = await portalApi.initiateAppealPayment({ appeal_id: 7, email: "a@b.com" });
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/appeals/payment/initiate`);
    expect(fetchMock.mock.calls[0][1].body).toBe(
      JSON.stringify({ appeal_id: 7, email: "a@b.com" }),
    );
    expect(res).toEqual({ redirect_url: "https://pay" });
  });

  it("uploadAppealPaymentProof posts multipart with Accept header", async () => {
    const fetchMock = mockFetch(jsonResponse({ status: "ok" }));
    const fd = new FormData();
    await portalApi.uploadAppealPaymentProof(fd);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url).toBe(`${BASE_URL}/appeals/payment/proof`);
    expect(options.headers).toEqual({ Accept: "application/json" });
    expect(options.body).toBe(fd);
  });

  it("getAppealPaymentStatus posts JSON", async () => {
    const fetchMock = mockFetch(jsonResponse({ status: "paid" }));
    const res = await portalApi.getAppealPaymentStatus({ appeal_id: 1, poll_url: "x" });
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/appeals/payment/status`);
    expect(res).toEqual({ status: "paid" });
  });

  it("subscribeNewsletter posts JSON to public/subscribe", async () => {
    const fetchMock = mockFetch(jsonResponse({ success: true }));
    await portalApi.subscribeNewsletter({ full_name: "A", email: "a@b.com" });
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/public/subscribe`);
    expect(fetchMock.mock.calls[0][1].body).toBe(
      JSON.stringify({ full_name: "A", email: "a@b.com" }),
    );
  });
});

describe("handleResponse error handling", () => {
  it("throws a structured error with message and errors", async () => {
    mockFetch(
      jsonResponse(
        { message: "Validation failed", errors: { email: ["required"] } },
        { ok: false, status: 422 },
      ),
    );
    await expect(portalApi.getStatus()).rejects.toMatchObject({
      status: 422,
      message: "Validation failed",
      errors: { email: ["required"] },
    });
  });

  it("falls back to a generic message when the error body is not JSON", async () => {
    mockFetch({
      ok: false,
      status: 500,
      statusText: "Server Error",
      json: vi.fn().mockRejectedValue(new Error("no json")),
    } as unknown as Response);
    await expect(portalApi.getStatus()).rejects.toMatchObject({
      status: 500,
      message: "An unexpected error occurred",
      errors: null,
    });
  });
});

describe("portalApi.pr authentication", () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it("login stores the returned token", async () => {
    mockFetch(jsonResponse({ token: "abc123" }));
    const res = await portalApi.pr.login({ email: "a@b.com", password: "pw" });
    expect(res).toEqual({ token: "abc123" });
    expect(localStorage.getItem("pr_token")).toBe("abc123");
  });

  it("login does not store a token if none is returned", async () => {
    mockFetch(jsonResponse({ user: "x" }));
    await portalApi.pr.login({ email: "a@b.com", password: "pw" });
    expect(localStorage.getItem("pr_token")).toBeNull();
  });

  it("logout removes the token", () => {
    localStorage.setItem("pr_token", "abc");
    portalApi.pr.logout();
    expect(localStorage.getItem("pr_token")).toBeNull();
  });

  it("list attaches the Bearer token and uses the /pr prefix", async () => {
    localStorage.setItem("pr_token", "tok");
    const fetchMock = mockFetch(jsonResponse([]));
    await portalApi.pr.list("news", { search: "abc", per_page: 5 });
    const [url, options] = fetchMock.mock.calls[0];
    expect(url).toBe(`${BASE_URL}/pr/news?search=abc&per_page=5`);
    expect(options.headers.Authorization).toBe("Bearer tok");
  });

  it("list omits the query string when no params are provided", async () => {
    const fetchMock = mockFetch(jsonResponse([]));
    await portalApi.pr.list("notices");
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/pr/notices`);
  });

  it("create sends JSON for objects", async () => {
    const fetchMock = mockFetch(jsonResponse({ id: 1 }));
    await portalApi.pr.create("events", { title: "x" });
    const [, options] = fetchMock.mock.calls[0];
    expect(options.method).toBe("POST");
    expect(options.headers["Content-Type"]).toBe("application/json");
  });

  it("create sends FormData as-is", async () => {
    const fetchMock = mockFetch(jsonResponse({ id: 1 }));
    const fd = new FormData();
    await portalApi.pr.create("events", fd);
    const [, options] = fetchMock.mock.calls[0];
    expect(options.body).toBe(fd);
    expect(options.headers["Content-Type"]).toBeUndefined();
  });

  it("getOne, update and delete use the expected verbs and URLs", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.pr.getOne("tenders", 9);
    await portalApi.pr.update("tenders", 9, { title: "y" });
    await portalApi.pr.delete("tenders", 9);
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/pr/tenders/9`);
    expect(fetchMock.mock.calls[1][1].method).toBe("PUT");
    expect(fetchMock.mock.calls[2][1].method).toBe("DELETE");
  });

  it("togglePublish issues a PATCH to the publish sub-resource", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.pr.togglePublish("vacancies", 3);
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/pr/vacancies/3/publish`);
    expect(fetchMock.mock.calls[0][1].method).toBe("PATCH");
  });

  it("getStats and getSubscribers hit the pr endpoints", async () => {
    const fetchMock = mockFetch(jsonResponse({}));
    await portalApi.pr.getStats();
    await portalApi.pr.getSubscribers();
    expect(fetchMock.mock.calls[0][0]).toBe(`${BASE_URL}/pr/stats`);
    expect(fetchMock.mock.calls[1][0]).toBe(`${BASE_URL}/pr/subscribers`);
  });
});
