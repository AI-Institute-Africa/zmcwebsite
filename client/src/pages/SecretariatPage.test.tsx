import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import SecretariatPage from "./SecretariatPage";
import { renderWithProviders } from "../test/render";

describe("SecretariatPage", () => {
  it("renders the leadership roles and organogram download", () => {
    renderWithProviders(<SecretariatPage onNavigate={vi.fn()} />);

    expect(screen.getAllByText("Executive Secretary").length).toBeGreaterThan(0);
    expect(screen.getAllByText("Principal Director").length).toBeGreaterThan(0);
    expect(screen.getByTestId("button-download-organogram")).toHaveAttribute(
      "href",
      "/documents/zmc-organogram.pdf",
    );
  });
});
