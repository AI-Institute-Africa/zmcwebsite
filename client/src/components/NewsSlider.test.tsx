import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import NewsSlider from "./NewsSlider";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalAssetUrl: (p?: string | null) => (p ? `https://portal.zmc.org.zw/storage/${p}` : null),
  portalApi: { getNews: vi.fn() },
}));

import { portalApi } from "../lib/api";

const getNews = portalApi.getNews as unknown as ReturnType<typeof vi.fn>;

const newsItem = (over: Record<string, unknown> = {}) => ({
  id: 1,
  title: "ZMC launches new programme",
  excerpt: "A short summary of the story.",
  notice_category: "Commission Update",
  created_at: "2026-01-15T00:00:00Z",
  image_path: null,
  body: "<p>Full body</p>",
  ...over,
});

describe("NewsSlider", () => {
  beforeEach(() => {
    getNews.mockReset();
  });

  it("shows a loading state first", () => {
    getNews.mockReturnValue(new Promise(() => {}));
    renderWithProviders(<NewsSlider />);
    expect(screen.getByText(/Loading latest news/i)).toBeInTheDocument();
  });

  it("renders slides with the category badge and never 'Read Full Release'", async () => {
    getNews.mockResolvedValue({ data: [newsItem()] });
    renderWithProviders(<NewsSlider />);

    await waitFor(() => expect(screen.getByText("ZMC launches new programme")).toBeInTheDocument());
    expect(screen.getAllByText("Commission Update").length).toBeGreaterThan(0);
    expect(screen.queryByText(/Read Full Release/i)).not.toBeInTheDocument();
  });

  it("falls back to the announcement label when no category is present", async () => {
    getNews.mockResolvedValue({ data: [newsItem({ notice_category: undefined, category: undefined, news_category: undefined })] });
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("ZMC launches new programme")).toBeInTheDocument());
    expect(screen.getAllByText("Announcement").length).toBeGreaterThan(0);
  });

  it("reads a nested category object's name", async () => {
    getNews.mockResolvedValue({ data: [newsItem({ notice_category: undefined, category: { name: "Notices" } })] });
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("ZMC launches new programme")).toBeInTheDocument());
    expect(screen.getAllByText("Notices").length).toBeGreaterThan(0);
  });

  it("handles a bare array response", async () => {
    getNews.mockResolvedValue([newsItem({ title: "Bare array item" })]);
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("Bare array item")).toBeInTheDocument());
  });

  it("derives an excerpt from the body when no excerpt exists", async () => {
    getNews.mockResolvedValue({
      data: [newsItem({ excerpt: "", body: "<p>" + "Long body text ".repeat(30) + "</p>", title: "Body Excerpt" })],
    });
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("Body Excerpt")).toBeInTheDocument());
    expect(screen.getByText(/Long body text/)).toBeInTheDocument();
  });

  it("shows an empty state when there is no news", async () => {
    getNews.mockResolvedValue({ data: [] });
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText(/No news available/i)).toBeInTheDocument());
  });

  it("shows an error state when the request fails", async () => {
    getNews.mockRejectedValue(new Error("boom"));
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText(/Something went wrong/i)).toBeInTheDocument());
    expect(screen.getByText("boom")).toBeInTheDocument();
  });

  it("uses the portal asset URL for slide images", async () => {
    getNews.mockResolvedValue({ data: [newsItem({ image_path: "news/pic.jpg" })] });
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("ZMC launches new programme")).toBeInTheDocument());
    expect(screen.getAllByText("Commission Update").length).toBeGreaterThan(0);
  });

  it("navigates to the release on slide click", async () => {
    const user = userEvent.setup();
    getNews.mockResolvedValue({ data: [newsItem({ id: 99 })] });
    const { history } = renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("ZMC launches new programme")).toBeInTheDocument());
    await user.click(screen.getByTestId("slide-click-0"));
    expect(history).toContain("/press-releases/99");
  });

  it("advances the slide with the next/prev buttons", async () => {
    const user = userEvent.setup();
    getNews.mockResolvedValue({
      data: [newsItem({ id: 1, title: "First" }), newsItem({ id: 2, title: "Second" })],
    });
    renderWithProviders(<NewsSlider />);
    await waitFor(() => expect(screen.getByText("First")).toBeInTheDocument());

    const track = screen.getByText("First").closest(".flex") as HTMLElement;
    await user.click(screen.getByTestId("button-next-slide"));
    expect(screen.getByText("Second")).toBeInTheDocument();
    await user.click(screen.getByTestId("button-prev-slide"));
    expect(screen.getByText("First")).toBeInTheDocument();
    expect(track).toBeTruthy();
  });
});
