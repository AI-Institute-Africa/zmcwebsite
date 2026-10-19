import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import HomePage from "./HomePage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: {
    getNews: vi.fn().mockResolvedValue({ data: [] }),
  },
  portalAssetUrl: (path: string | null | undefined) => (path ? `https://portal.test/${path}` : ""),
  PORTAL_LOGIN_URL: "https://portal.test/login",
  PORTAL_BASE_URL: "https://portal.test",
  TRAINING_HUB_URL: "https://traininghub.test",
  PRAZ_EGP_URL: "https://egp.test/",
  CONTACT_EMAIL: "info@zmc.org.zw",
}));

import { portalApi } from "../lib/api";

const getNews = portalApi.getNews as unknown as ReturnType<typeof vi.fn>;

describe("HomePage", () => {
  beforeEach(() => {
    getNews.mockReset().mockResolvedValue({ data: [] });
  });

  it("composes the hero, news slider and services sections", async () => {
    getNews.mockResolvedValue({
      data: [{ id: 1, title: "Homepage Feature Story", category: "Statement" }],
    });

    renderWithProviders(<HomePage onNavigate={vi.fn()} />);

    expect(screen.getByTestId("stat-box-0")).toHaveTextContent("500+");
    expect(await screen.findByText("Homepage Feature Story")).toBeInTheDocument();
    expect(screen.getByTestId("slide-click-0")).toBeInTheDocument();
    expect(screen.getByTestId("service-card-accreditation")).toBeInTheDocument();
  });

  it("loads the latest news feed on mount", async () => {
    getNews.mockReturnValue(new Promise(() => {}));
    renderWithProviders(<HomePage onNavigate={vi.fn()} />);
    expect(getNews).toHaveBeenCalledWith(1, 8);
  });
});
