import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import RegisteredMediaHousesPage from "./RegisteredMediaHousesPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: {
    getAccreditedPractitioners: vi.fn(),
    getRegisteredMediaHouses: vi.fn(),
  },
}));

vi.mock("../lib/pdfExport", () => ({
  exportTableToPdf: vi.fn().mockResolvedValue(undefined),
}));

import { portalApi } from "../lib/api";

const getMediaHouses = portalApi.getRegisteredMediaHouses as unknown as ReturnType<typeof vi.fn>;

describe("RegisteredMediaHousesPage", () => {
  beforeEach(() => {
    getMediaHouses.mockReset().mockResolvedValue({
      data: [{ organisation_name: "Daily Test News", registration_no: "ZMC/REG/2026/0001", province: "Harare" }],
      meta: { last_page: 1, total: 1 },
    });
  });

  it("renders the media houses directory and legacy export button", async () => {
    renderWithProviders(<RegisteredMediaHousesPage onNavigate={vi.fn()} />);

    expect(screen.getAllByRole("heading", { name: "Registered Media Houses" }).length).toBeGreaterThan(0);
    expect(screen.getByTestId("button-download-legacy-list")).toBeInTheDocument();
    await waitFor(() => expect(screen.getByTestId("directory-row-0")).toHaveTextContent("Daily Test News"));
  });
});
