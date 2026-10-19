import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { Router, Route } from "wouter";
import { memoryLocation } from "wouter/memory-location";
import NoticeDetailPage from "./NoticeDetailPage";
import { LanguageProvider } from "../i18n/LanguageContext";

vi.mock("../lib/api", () => ({
  portalApi: { getNotices: vi.fn() },
}));

import { portalApi } from "../lib/api";

const getNotices = portalApi.getNotices as unknown as ReturnType<typeof vi.fn>;

function renderDetail(path: string) {
  const location = memoryLocation({ path, record: true });
  return render(
    <LanguageProvider>
      <Router hook={location.hook}>
        <Route
          path="/notices/:id"
          component={() => (
            <NoticeDetailPage onNavigate={vi.fn()} backTo={{ label: "Press Releases", page: "press-releases" }} />
          )}
        />
      </Router>
    </LanguageProvider>,
  );
}

describe("NoticeDetailPage", () => {
  beforeEach(() => {
    getNotices.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders the notice details for a matching id", async () => {
    getNotices.mockResolvedValue({
      data: [
        {
          id: 9,
          title: "Public Notice on Accreditation",
          notice_category: "Announcement",
          summary: "Important summary text.",
          body: "Full notice body.",
        },
      ],
    });

    renderDetail("/notices/9");

    expect(await screen.findByText("Public Notice on Accreditation")).toBeInTheDocument();
    expect(screen.getByText("Important summary text.")).toBeInTheDocument();
    expect(screen.getByTestId("button-back-notices")).toHaveTextContent("Back to Press Releases");
  });

  it("shows not-found when no notice matches the id", async () => {
    getNotices.mockResolvedValue({ data: [] });
    renderDetail("/notices/999");
    expect(await screen.findByText("Notice not found")).toBeInTheDocument();
  });
});
