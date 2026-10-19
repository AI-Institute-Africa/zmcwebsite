import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { SubscribeProvider, useSubscribe } from "./SubscribeDialog";
import { renderWithProviders } from "../test/render";

function Trigger() {
  const { open, isSubscribed } = useSubscribe();
  return (
    <div>
      <button onClick={open}>open-subscribe</button>
      <span data-testid="subscribed-flag">{String(isSubscribed)}</span>
    </div>
  );
}

function renderDialog() {
  return renderWithProviders(
    <SubscribeProvider>
      <Trigger />
    </SubscribeProvider>,
  );
}

const tick = () => new Promise((r) => setTimeout(r, 0));

describe("SubscribeDialog", () => {
  beforeEach(() => {
    localStorage.clear();
    vi.restoreAllMocks();
  });

  afterEach(() => {
    localStorage.clear();
  });

  it("is not subscribed by default", () => {
    renderDialog();
    expect(screen.getByTestId("subscribed-flag")).toHaveTextContent("false");
  });

  it("reads a persisted subscription from localStorage", async () => {
    localStorage.setItem("zmc_subscribed", "true");
    renderDialog();
    await waitFor(() => expect(screen.getByTestId("subscribed-flag")).toHaveTextContent("true"));
  });

  it("opens the dialog via the context", async () => {
    const user = userEvent.setup();
    renderDialog();
    await user.click(screen.getByText("open-subscribe"));
    expect(await screen.findByTestId("dialog-subscribe")).toBeInTheDocument();
    expect(screen.getByTestId("text-subscribe-title")).toHaveTextContent("Subscribe for Free Updates");
  });

  it("validates required fields before submitting", async () => {
    const user = userEvent.setup();
    const fetchSpy = vi.spyOn(globalThis, "fetch");
    renderDialog();
    await user.click(screen.getByText("open-subscribe"));
    await user.click(await screen.findByTestId("button-subscribe-submit"));

    expect(await screen.findByText(/Please enter your full name/i)).toBeInTheDocument();
    expect(fetchSpy).not.toHaveBeenCalled();
  });

  it("submits a valid subscription, persists it and closes the dialog", async () => {
    const user = userEvent.setup();
    const fetchSpy = vi
      .spyOn(globalThis, "fetch")
      .mockResolvedValue({
        ok: true,
        status: 201,
        json: async () => ({ alreadySubscribed: false, subscriber: {} }),
      } as Response);

    renderDialog();
    await user.click(screen.getByText("open-subscribe"));
    await user.type(await screen.findByTestId("input-subscribe-fullname"), "Jane Doe");
    await user.type(screen.getByTestId("input-subscribe-email"), "jane@example.com");
    await user.click(screen.getByTestId("button-subscribe-submit"));

    await waitFor(() => expect(localStorage.getItem("zmc_subscribed")).toBe("true"));
    await waitFor(() => expect(screen.getByTestId("subscribed-flag")).toHaveTextContent("true"));

    const [url, options] = fetchSpy.mock.calls[0];
    expect(url).toBe("/api/subscribe");
    expect(options!.method).toBe("POST");
    expect(JSON.parse(options!.body as string)).toEqual({ fullName: "Jane Doe", email: "jane@example.com" });
  });

  it("handles an already-subscribed response", async () => {
    const user = userEvent.setup();
    vi.spyOn(globalThis, "fetch").mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ alreadySubscribed: true, subscriber: {} }),
    } as Response);

    renderDialog();
    await user.click(screen.getByText("open-subscribe"));
    await user.type(await screen.findByTestId("input-subscribe-fullname"), "Jane Doe");
    await user.type(screen.getByTestId("input-subscribe-email"), "jane@example.com");
    await user.click(screen.getByTestId("button-subscribe-submit"));

    await tick();
    await waitFor(() => expect(screen.getByTestId("subscribed-flag")).toHaveTextContent("true"));
  });

  it("keeps the dialog open and reports failure when the request errors", async () => {
    const user = userEvent.setup();
    vi.spyOn(globalThis, "fetch").mockResolvedValue({
      ok: false,
      status: 500,
      statusText: "Server Error",
      text: async () => "boom",
    } as Response);

    renderDialog();
    await user.click(screen.getByText("open-subscribe"));
    await user.type(await screen.findByTestId("input-subscribe-fullname"), "Jane Doe");
    await user.type(screen.getByTestId("input-subscribe-email"), "jane@example.com");
    await user.click(screen.getByTestId("button-subscribe-submit"));

    await waitFor(() => expect(localStorage.getItem("zmc_subscribed")).toBeNull());
    expect(screen.getByTestId("dialog-subscribe")).toBeInTheDocument();
  });
});
