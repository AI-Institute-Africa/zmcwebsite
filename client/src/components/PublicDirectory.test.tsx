import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import PublicDirectory from "./PublicDirectory";
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
import { exportTableToPdf } from "../lib/pdfExport";

const getPractitioners = portalApi.getAccreditedPractitioners as unknown as ReturnType<typeof vi.fn>;
const getMediaHouses = portalApi.getRegisteredMediaHouses as unknown as ReturnType<typeof vi.fn>;
const exportPdf = exportTableToPdf as unknown as ReturnType<typeof vi.fn>;

describe("PublicDirectory", () => {
  beforeEach(() => {
    getPractitioners.mockReset().mockResolvedValue({ data: [], meta: { last_page: 1, total: 0 } });
    getMediaHouses.mockReset().mockResolvedValue({ data: [], meta: { last_page: 1, total: 0 } });
    exportPdf.mockReset().mockResolvedValue(undefined);
  });

  it("renders the practitioners variant heading with no legacy list button", async () => {
    renderWithProviders(<PublicDirectory variant="practitioners" />);

    expect(screen.getByRole("heading", { name: "Accredited Media Practitioners" })).toBeInTheDocument();
    expect(screen.queryByTestId("button-download-legacy-list")).not.toBeInTheDocument();
    expect(screen.getByTestId("button-export-pdf-practitioners")).toBeInTheDocument();
    await screen.findByText("No records to display yet");
  });

  it("renders the media houses variant with the legacy list button", async () => {
    renderWithProviders(<PublicDirectory variant="mediahouses" />);

    expect(screen.getByRole("heading", { name: "Registered Media Houses" })).toBeInTheDocument();
    expect(screen.getByTestId("button-download-legacy-list")).toBeInTheDocument();
    expect(screen.getByTestId("button-export-pdf-mediahouses")).toBeInTheDocument();
    await screen.findByText("No records to display yet");
  });

  it("renders practitioner rows with a derived status badge", async () => {
    getPractitioners.mockResolvedValue({
      data: [
        {
          first_name: "Tendai",
          surname: "Moyo",
          category: "Journalist",
          organisation: "Daily News",
          accreditation_number: "ZMC/ACC/2026/0001",
          issued_at: "2025-01-01T00:00:00Z",
          expires_at: "2099-01-01T00:00:00Z",
        },
      ],
      meta: { last_page: 1, total: 1 },
    });

    renderWithProviders(<PublicDirectory variant="practitioners" />);

    expect(await screen.findByTestId("directory-table-practitioners")).toBeInTheDocument();
    expect(screen.getByTestId("directory-row-0")).toHaveTextContent("Tendai Moyo");
    expect(screen.getByTestId("directory-row-0")).toHaveTextContent("ZMC/ACC/2026/0001");
    expect(screen.getByText("Active")).toBeInTheDocument();
    expect(screen.getByText("1 record")).toBeInTheDocument();
  });

  it("marks expired records with an Expired status", async () => {
    getPractitioners.mockResolvedValue({
      data: [{ full_name: "Old Timer", expires_at: "2000-01-01T00:00:00Z" }],
      meta: { last_page: 1, total: 1 },
    });

    renderWithProviders(<PublicDirectory variant="practitioners" />);

    expect(await screen.findByText("Expired")).toBeInTheDocument();
    expect(screen.getByTestId("directory-row-0")).toHaveTextContent("Old Timer");
  });

  it("shows an error state when the directory fails to load", async () => {
    getPractitioners.mockRejectedValue(new Error("Service unavailable"));

    renderWithProviders(<PublicDirectory variant="practitioners" />);

    const alert = await screen.findByRole("alert");
    expect(alert).toHaveTextContent("Directory unavailable");
    expect(alert).toHaveTextContent("Service unavailable");
  });

  it("shows the empty search state when no records match", async () => {
    const user = userEvent.setup();
    renderWithProviders(<PublicDirectory variant="practitioners" />);
    await screen.findByText("No records to display yet");

    await user.type(screen.getByTestId("input-directory-search-practitioners"), "Nobody");

    await waitFor(() =>
      expect(getPractitioners).toHaveBeenLastCalledWith(
        expect.objectContaining({ search: "Nobody", page: 1 }),
      ),
    );
  });

  it("paginates to the next page of records", async () => {
    getPractitioners.mockResolvedValue({
      data: [{ full_name: "Page One Person" }],
      meta: { last_page: 2, total: 30 },
    });

    const user = userEvent.setup();
    renderWithProviders(<PublicDirectory variant="practitioners" />);
    await screen.findByTestId("directory-table-practitioners");
    await new Promise((resolve) => setTimeout(resolve, 450));

    await user.click(screen.getByRole("button", { name: "Next" }));

    expect(await screen.findByText("Page 2 of 2")).toBeInTheDocument();
    expect(getPractitioners).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }));
  });

  it("reports an export error when there are no records", async () => {
    const user = userEvent.setup();
    renderWithProviders(<PublicDirectory variant="practitioners" />);
    await screen.findByText("No records to display yet");

    await user.click(screen.getByTestId("button-export-pdf-practitioners"));

    expect(await screen.findByTestId("text-export-error")).toHaveTextContent("There are no records to export.");
    expect(exportPdf).not.toHaveBeenCalled();
  });

  it("exports the current records to PDF", async () => {
    getPractitioners.mockResolvedValue({
      data: [{ full_name: "Exportable Person", expires_at: "2099-01-01T00:00:00Z" }],
      meta: { last_page: 1, total: 1 },
    });

    const user = userEvent.setup();
    renderWithProviders(<PublicDirectory variant="practitioners" />);
    await screen.findByTestId("directory-table-practitioners");

    await user.click(screen.getByTestId("button-export-pdf-practitioners"));

    await waitFor(() => expect(exportPdf).toHaveBeenCalledTimes(1));
    expect(exportPdf).toHaveBeenCalledWith(
      expect.objectContaining({ filename: "accredited-media-practitioners.pdf" }),
    );
  });
});
