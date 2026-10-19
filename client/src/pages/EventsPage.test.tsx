import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import EventsPage from "./EventsPage";
import { SubscribeProvider } from "../components/SubscribeDialog";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: { getEvents: vi.fn() },
}));

import { portalApi } from "../lib/api";

const getEvents = portalApi.getEvents as unknown as ReturnType<typeof vi.fn>;

function renderEvents(onNavigate = vi.fn()) {
  return renderWithProviders(
    <SubscribeProvider>
      <EventsPage onNavigate={onNavigate} />
    </SubscribeProvider>,
  );
}

describe("EventsPage", () => {
  beforeEach(() => {
    getEvents.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders event cards from the portal", async () => {
    getEvents.mockResolvedValue({
      data: [{ id: 5, title: "Media Symposium 2026", event_category: "Symposium", location: "Harare" }],
    });

    renderEvents();

    expect(await screen.findByTestId("event-card-5")).toHaveTextContent("Media Symposium 2026");
    expect(screen.getByText("Upcoming Events")).toBeInTheDocument();
  });

  it("navigates to the event detail when a card is clicked", async () => {
    getEvents.mockResolvedValue({ data: [{ id: 5, title: "Media Symposium 2026" }] });
    const onNavigate = vi.fn();
    const user = userEvent.setup();
    renderEvents(onNavigate);

    await user.click(await screen.findByTestId("event-card-5"));

    expect(onNavigate).toHaveBeenCalledWith("events/5");
  });

  it("shows the empty state when there are no events", async () => {
    renderEvents();
    expect(await screen.findByText("No upcoming events scheduled at this time.")).toBeInTheDocument();
  });
});
