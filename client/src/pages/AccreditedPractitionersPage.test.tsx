import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import AccreditedPractitionersPage from "./AccreditedPractitionersPage";
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

const getPractitioners = portalApi.getAccreditedPractitioners as unknown as ReturnType<typeof vi.fn>;

describe("AccreditedPractitionersPage", () => {
  beforeEach(() => {
    getPractitioners.mockReset().mockResolvedValue({
      data: [{ first_name: "Test", surname: "Practitioner", accreditation_number: "ZMC/ACC/2026/0001" }],
      meta: { last_page: 1, total: 1 },
    });
  });

  it("renders the practitioners directory from the portal", async () => {
    renderWithProviders(<AccreditedPractitionersPage onNavigate={vi.fn()} />);

    expect(screen.getByRole("heading", { name: "Accredited Media Practitioners" })).toBeInTheDocument();
    await waitFor(() => expect(screen.getByTestId("directory-row-0")).toHaveTextContent("Test Practitioner"));
  });
});
