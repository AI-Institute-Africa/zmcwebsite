import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import AccreditationPage from "./AccreditationPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({ PORTAL_LOGIN_URL: "https://portal.test/login" }));

describe("AccreditationPage", () => {
  it("renders the process steps and apply actions", () => {
    renderWithProviders(<AccreditationPage onNavigate={vi.fn()} />);

    expect(screen.getByText("Gather Documents")).toBeInTheDocument();
    expect(screen.getByText("Complete Form")).toBeInTheDocument();
    expect(screen.getByTestId("button-apply-accreditation")).toBeInTheDocument();
    expect(screen.getByTestId("button-renew-accreditation")).toBeInTheDocument();
  });

  it("opens the portal when applying", async () => {
    const openSpy = vi.spyOn(window, "open").mockImplementation(() => null);
    const user = userEvent.setup();
    renderWithProviders(<AccreditationPage onNavigate={vi.fn()} />);

    await user.click(screen.getByTestId("button-apply-accreditation"));

    expect(openSpy).toHaveBeenCalledWith("https://portal.test/login", "_blank", "noopener,noreferrer");
    openSpy.mockRestore();
  });
});
