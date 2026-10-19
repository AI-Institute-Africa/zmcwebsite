import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import VacanciesPage from "./VacanciesPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: { getVacancies: vi.fn() },
}));

import { portalApi } from "../lib/api";

const getVacancies = portalApi.getVacancies as unknown as ReturnType<typeof vi.fn>;

describe("VacanciesPage", () => {
  beforeEach(() => {
    getVacancies.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders the vacancy list with search", async () => {
    getVacancies.mockResolvedValue({
      data: [{ id: 4, title: "Editor", vacancy_reference: "ZMC/VAC/2026/04", employment_type: "Full-time", duty_station: "Harare" }],
    });

    renderWithProviders(<VacanciesPage onNavigate={vi.fn()} />);

    expect(screen.getByTestId("input-search-vacancies")).toBeInTheDocument();
    expect(await screen.findByTestId("vacancy-row-4")).toHaveTextContent("Editor");
  });

  it("shows the empty state when there are no vacancies", async () => {
    renderWithProviders(<VacanciesPage onNavigate={vi.fn()} />);
    expect(await screen.findByText("No vacancies found")).toBeInTheDocument();
  });
});
