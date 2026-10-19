import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { Router, Route } from "wouter";
import { memoryLocation } from "wouter/memory-location";
import VacancyDetailPage from "./VacancyDetailPage";
import { LanguageProvider } from "../i18n/LanguageContext";

vi.mock("../lib/api", () => ({
  portalApi: { getVacancies: vi.fn() },
}));

import { portalApi } from "../lib/api";

const getVacancies = portalApi.getVacancies as unknown as ReturnType<typeof vi.fn>;

function renderDetail(path: string) {
  const location = memoryLocation({ path, record: true });
  return render(
    <LanguageProvider>
      <Router hook={location.hook}>
        <Route path="/vacancies/:id" component={() => <VacancyDetailPage onNavigate={vi.fn()} />} />
      </Router>
    </LanguageProvider>,
  );
}

describe("VacancyDetailPage", () => {
  beforeEach(() => {
    getVacancies.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders the full vacancy details for a matching id", async () => {
    getVacancies.mockResolvedValue({
      data: [
        {
          id: 4,
          title: "Editor",
          vacancy_reference: "ZMC/VAC/2026/04",
          employment_type: "Full-time",
          duty_station: "Harare",
          body: "Job description paragraph.",
        },
      ],
    });

    renderDetail("/vacancies/4");

    expect((await screen.findAllByText("Editor")).length).toBeGreaterThan(0);
    expect(screen.getByText("Job description paragraph.")).toBeInTheDocument();
    expect(screen.getByTestId("button-back-vacancies")).toBeInTheDocument();
  });

  it("shows not-found when no vacancy matches the id", async () => {
    getVacancies.mockResolvedValue({ data: [{ id: 1, title: "Other" }] });
    renderDetail("/vacancies/999");
    expect(await screen.findByText("Vacancy not found")).toBeInTheDocument();
  });
});
