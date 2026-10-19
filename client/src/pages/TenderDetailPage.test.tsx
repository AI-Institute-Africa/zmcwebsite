import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { Router, Route } from "wouter";
import { memoryLocation } from "wouter/memory-location";
import TenderDetailPage from "./TenderDetailPage";
import { LanguageProvider } from "../i18n/LanguageContext";

vi.mock("../lib/api", () => ({
  portalApi: { getTenders: vi.fn() },
  PRAZ_EGP_URL: "https://egp.praz.org.zw/",
}));

import { portalApi } from "../lib/api";

const getTenders = portalApi.getTenders as unknown as ReturnType<typeof vi.fn>;

function renderDetail(path: string) {
  const location = memoryLocation({ path, record: true });
  return render(
    <LanguageProvider>
      <Router hook={location.hook}>
        <Route path="/tenders/:id" component={() => <TenderDetailPage onNavigate={vi.fn()} />} />
      </Router>
    </LanguageProvider>,
  );
}

describe("TenderDetailPage", () => {
  beforeEach(() => {
    getTenders.mockReset().mockResolvedValue({ data: [] });
  });

  it("renders the full tender details for a matching id", async () => {
    getTenders.mockResolvedValue({
      data: [
        {
          id: 3,
          title: "Supply of Broadcasting Equipment",
          reference_number: "ZMC/TEN/2026/01",
          description: "Tender details go here.",
          closing_at: "2099-01-01T00:00:00Z",
        },
      ],
    });

    renderDetail("/tenders/3");

    expect((await screen.findAllByText("Supply of Broadcasting Equipment")).length).toBeGreaterThan(0);
    expect(screen.getByText(/ZMC\/TEN\/2026\/01/)).toBeInTheDocument();
    expect(screen.getByTestId("link-egp-detail")).toBeInTheDocument();
  });

  it("shows not-found when no tender matches the id", async () => {
    getTenders.mockResolvedValue({ data: [] });
    renderDetail("/tenders/999");
    expect(await screen.findByText("Tender not found")).toBeInTheDocument();
  });
});
