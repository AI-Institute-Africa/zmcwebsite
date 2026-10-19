import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import CommissionPage from "./CommissionersPage";
import { renderWithProviders } from "../test/render";

describe("CommissionersPage", () => {
  it("renders the board cards", () => {
    renderWithProviders(<CommissionPage onNavigate={vi.fn()} />);

    expect(screen.getByTestId("card-commissioner-chairperson")).toBeInTheDocument();
    expect(screen.getByTestId("card-commissioner-deputy-chairperson")).toBeInTheDocument();
    expect(screen.getAllByTestId("card-commissioner-commissioner").length).toBeGreaterThan(0);
  });
});
