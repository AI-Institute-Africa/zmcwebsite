import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Hero from "./Hero";
import en from "../i18n/en";
import { PORTAL_LOGIN_URL, portalApi } from "../lib/api";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  PORTAL_LOGIN_URL: "https://portal.test/login",
  portalApi: { getHomeStats: vi.fn() },
}));

const getHomeStatsMock = portalApi.getHomeStats as unknown as ReturnType<typeof vi.fn>;

describe("Hero", () => {
  beforeEach(() => {
    getHomeStatsMock.mockReset();
    getHomeStatsMock.mockReturnValue(new Promise(() => {}));
  });

  it("renders the welcome heading and subtitle", () => {
    renderWithProviders(<Hero onNavigate={vi.fn()} />);
    expect(screen.getByText(en.hero.welcomeTo)).toBeInTheDocument();
    expect(screen.getByText(en.hero.title)).toBeInTheDocument();
    expect(screen.getByText(en.hero.subtitle)).toBeInTheDocument();
  });

  it("navigates to about when Learn More is clicked", async () => {
    const user = userEvent.setup();
    const onNavigate = vi.fn();
    renderWithProviders(<Hero onNavigate={onNavigate} />);
    await user.click(screen.getByTestId("button-learn-more"));
    expect(onNavigate).toHaveBeenCalledWith("about");
  });

  it("links Get Accredited to the portal login in a new tab", () => {
    renderWithProviders(<Hero onNavigate={vi.fn()} />);
    const link = screen.getByTestId("button-get-accredited");
    expect(link).toHaveAttribute("href", PORTAL_LOGIN_URL);
    expect(link).toHaveAttribute("target", "_blank");
  });

  it("renders the quick stats from the portal and the four stat boxes", async () => {
    getHomeStatsMock.mockResolvedValue({
      status: "success",
      data: {
        accredited_journalists: 1234,
        registered_media_houses: 56,
        complaints_resolved: 789,
      },
    });
    renderWithProviders(<Hero onNavigate={vi.fn()} />);

    expect(screen.getByText(en.hero.quickStats)).toBeInTheDocument();
    for (let i = 0; i < 4; i++) {
      expect(screen.getByTestId(`stat-box-${i}`)).toBeInTheDocument();
    }

    expect(await screen.findAllByText("1,234")).toHaveLength(2);
    expect(await screen.findAllByText("56")).toHaveLength(2);
    expect(screen.getByText("789")).toBeInTheDocument();
    expect(screen.getByText("50+")).toBeInTheDocument();
    expect(screen.getByText("20+")).toBeInTheDocument();
    expect(screen.getByText("100%")).toBeInTheDocument();
  });

  it("falls back to static stats when the stats request fails", async () => {
    getHomeStatsMock.mockRejectedValue(new Error("offline"));
    renderWithProviders(<Hero onNavigate={vi.fn()} />);

    expect(await screen.findAllByText("500")).toHaveLength(2);
    expect(await screen.findAllByText("150")).toHaveLength(2);
    expect(screen.getByText("1,000")).toBeInTheDocument();
    expect(screen.getByText("50+")).toBeInTheDocument();
  });
});
