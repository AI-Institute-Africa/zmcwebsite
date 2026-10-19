import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import VerifyAccreditationPage from "./VerifyAccreditationPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: { verifyAccreditation: vi.fn() },
}));

import { portalApi } from "../lib/api";

const verifyAccreditation = portalApi.verifyAccreditation as unknown as ReturnType<typeof vi.fn>;

describe("VerifyAccreditationPage", () => {
  beforeEach(() => {
    verifyAccreditation.mockReset();
  });

  it("renders the verification form", () => {
    renderWithProviders(<VerifyAccreditationPage onNavigate={vi.fn()} />);
    expect(screen.getByRole("heading", { name: "Verify an Accreditation Number" })).toBeInTheDocument();
    expect(screen.getByTestId("input-verify-accreditation")).toBeInTheDocument();
  });
});
