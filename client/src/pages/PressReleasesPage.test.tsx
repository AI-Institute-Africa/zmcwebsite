import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { Router, Route } from "wouter";
import { memoryLocation } from "wouter/memory-location";
import PressReleasesPage from "./PressReleasesPage";
import { renderWithProviders } from "../test/render";
import { LanguageProvider } from "../i18n/LanguageContext";

vi.mock("../lib/api", () => ({
  portalApi: {
    getNews: vi.fn(),
    getNotices: vi.fn(),
    getSingleRecord: vi.fn(),
  },
  portalAssetUrl: (path: string | null | undefined) => (path ? `https://portal.test/${path}` : ""),
}));

import { portalApi } from "../lib/api";

const getNews = portalApi.getNews as unknown as ReturnType<typeof vi.fn>;
const getNotices = portalApi.getNotices as unknown as ReturnType<typeof vi.fn>;
const getSingleRecord = portalApi.getSingleRecord as unknown as ReturnType<typeof vi.fn>;

function renderDetail(path: string) {
  const location = memoryLocation({ path, record: true });
  return render(
    <LanguageProvider>
      <Router hook={location.hook}>
        <Route path="/press-releases/:id" component={() => <PressReleasesPage onNavigate={vi.fn()} />} />
      </Router>
    </LanguageProvider>,
  );
}

describe("PressReleasesPage", () => {
  beforeEach(() => {
    getNews.mockReset().mockResolvedValue({ data: [] });
    getNotices.mockReset().mockResolvedValue({ data: [] });
    getSingleRecord.mockReset();
  });

  it("renders the list of releases with category badges and no legacy copy", async () => {
    getNews.mockResolvedValue({
      data: [
        {
          id: 1,
          title: "ZMC Statement on Media Freedom",
          category: "Statement",
          excerpt: "A short summary of the statement.",
          created_at: "2024-01-02T00:00:00Z",
          image_path: "uploads/banner.jpg",
        },
      ],
    });

    renderWithProviders(<PressReleasesPage onNavigate={vi.fn()} />);

    expect(await screen.findByText("ZMC Statement on Media Freedom")).toBeInTheDocument();
    expect(screen.getByTestId("release-card-1")).toBeInTheDocument();
    expect(screen.getByText("Statement")).toBeInTheDocument();
    const img = screen.getByAltText("ZMC Statement on Media Freedom");
    expect(img).toHaveAttribute("src", "https://portal.test/uploads/banner.jpg");
    expect(screen.queryByText(/Read Full Release/i)).not.toBeInTheDocument();
  });

  it("derives an excerpt from the body when none is provided", async () => {
    getNews.mockResolvedValue({
      data: [{ id: 2, title: "Body Only", body: "Hello <b>world</b> from ZMC" }],
    });
    renderWithProviders(<PressReleasesPage onNavigate={vi.fn()} />);
    expect(await screen.findByText("Hello world from ZMC")).toBeInTheDocument();
  });

  it("renders the notices section from the notices feed, newest first", async () => {
    getNotices.mockResolvedValue({
      data: [
        { id: 10, title: "Older Notice", published_at: "2024-01-01T00:00:00Z" },
        { id: 11, title: "Newer Notice", notice_category: "Award", published_at: "2024-05-01T00:00:00Z" },
      ],
    });
    renderWithProviders(<PressReleasesPage onNavigate={vi.fn()} />);

    expect(await screen.findByTestId("notices-section")).toBeInTheDocument();
    expect(screen.getByTestId("notice-card-11")).toBeInTheDocument();
    expect(screen.getByTestId("notice-card-10")).toBeInTheDocument();
    const cards = screen.getAllByTestId(/notice-card-/);
    expect(cards[0]).toHaveTextContent("Newer Notice");
  });

  it("navigates to a release detail when a card is clicked", async () => {
    getNews.mockResolvedValue({ data: [{ id: 7, title: "Clickable" }] });
    const user = userEvent.setup();
    const { history } = renderWithProviders(<PressReleasesPage onNavigate={vi.fn()} />);
    await user.click(await screen.findByTestId("release-card-7"));
    expect(history.at(-1)).toBe("/press-releases/7");
  });

  it("shows the empty state when there are no releases", async () => {
    renderWithProviders(<PressReleasesPage onNavigate={vi.fn()} />);
    expect(await screen.findByText("No Press Releases Yet")).toBeInTheDocument();
  });

  it("falls back to the empty state when the news feed fails", async () => {
    getNews.mockRejectedValue(new Error("offline"));
    renderWithProviders(<PressReleasesPage onNavigate={vi.fn()} />);
    expect(await screen.findByText("No Press Releases Yet")).toBeInTheDocument();
  });

  it("renders a full press release detail view", async () => {
    getSingleRecord.mockResolvedValue({
      id: 5,
      title: "Full Release Title",
      category: "Press Release",
      body: "Paragraph one.\n\nParagraph two.",
      created_at: "2024-02-03T00:00:00Z",
      author_name: "ZMC",
    });

    renderDetail("/press-releases/5");

    expect(await screen.findByText("Full Release Title")).toBeInTheDocument();
    expect(getSingleRecord).toHaveBeenCalledWith("news", "5");
    expect(screen.getByText(/Paragraph one\./)).toBeInTheDocument();
    expect(screen.getByText(/Paragraph two\./)).toBeInTheDocument();
    expect(screen.getByText("Back to Releases")).toBeInTheDocument();
    expect(screen.queryByText(/Read Full Release/i)).not.toBeInTheDocument();
  });

  it("shows a not-found message when a release cannot be loaded", async () => {
    getSingleRecord.mockRejectedValue(new Error("Failed to load press release"));
    renderDetail("/press-releases/999");
    await waitFor(() => expect(screen.getByText("Failed to load press release")).toBeInTheDocument());
    expect(screen.getByText("Back to Home")).toBeInTheDocument();
  });
});
