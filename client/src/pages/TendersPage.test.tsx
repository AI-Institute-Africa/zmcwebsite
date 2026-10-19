import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import TendersPage from "./TendersPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: { getTenders: vi.fn() },
  PRAZ_EGP_URL: "https://egp.praz.org.zw/",
}));

import { portalApi, PRAZ_EGP_URL } from "../lib/api";

const getTenders = portalApi.getTenders as unknown as ReturnType<typeof vi.fn>;

describe("TendersPage", () => {
  beforeEach(() => {
    getTenders.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders tender notices and the e-GP portal link", async () => {
    getTenders.mockResolvedValue({
      data: [{ id: 3, title: "Supply of Broadcasting Equipment", reference_number: "ZMC/TEN/2026/01" }],
    });

    renderWithProviders(<TendersPage onNavigate={vi.fn()} />);

    expect(screen.getByTestId("link-praz-portal")).toHaveAttribute("href", PRAZ_EGP_URL);
    expect(await screen.findByTestId("tender-card-3")).toHaveTextContent("Supply of Broadcasting Equipment");
  });
});
