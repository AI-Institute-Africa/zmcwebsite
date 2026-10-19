import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { Router, Route } from "wouter";
import { memoryLocation } from "wouter/memory-location";
import EventDetailPage from "./EventDetailPage";
import { LanguageProvider } from "../i18n/LanguageContext";

vi.mock("../lib/api", () => ({
  portalApi: { getEvents: vi.fn() },
}));

import { portalApi } from "../lib/api";

const getEvents = portalApi.getEvents as unknown as ReturnType<typeof vi.fn>;

function renderDetail(path: string) {
  const location = memoryLocation({ path, record: true });
  return render(
    <LanguageProvider>
      <Router hook={location.hook}>
        <Route path="/events/:id" component={() => <EventDetailPage onNavigate={vi.fn()} />} />
      </Router>
    </LanguageProvider>,
  );
}

describe("EventDetailPage", () => {
  beforeEach(() => {
    getEvents.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders the full event details for a matching id", async () => {
    getEvents.mockResolvedValue({
      data: [
        {
          id: 5,
          title: "Media Symposium 2026",
          event_category: "Symposium",
          location: "Harare",
          description: "A gathering of media practitioners.",
        },
      ],
    });

    renderDetail("/events/5");

    expect(await screen.findByText("Media Symposium 2026")).toBeInTheDocument();
    expect(screen.getByText("Harare")).toBeInTheDocument();
    expect(screen.getByText("A gathering of media practitioners.")).toBeInTheDocument();
    expect(getEvents).toHaveBeenCalledWith(1, 100);
  });

  it("shows not-found when no event matches the id", async () => {
    getEvents.mockResolvedValue({ data: [{ id: 1, title: "Other" }] });
    renderDetail("/events/999");
    expect(await screen.findByText("Event not found")).toBeInTheDocument();
  });
});
