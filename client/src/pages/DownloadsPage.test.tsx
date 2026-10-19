import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import DownloadsPage from "./DownloadsPage";
import { renderWithProviders } from "../test/render";

describe("DownloadsPage", () => {
  it("renders the document categories and items", () => {
    renderWithProviders(<DownloadsPage onNavigate={vi.fn()} />);

    expect(screen.getByRole("heading", { name: "Registration & Accreditation Forms" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "Legislation" })).toBeInTheDocument();
    expect(screen.getByTestId("download-item-0-0")).toHaveTextContent("Registration of a Mass Media Service");
  });

  it("navigates to contact from the help CTA", async () => {
    const onNavigate = vi.fn();
    const user = userEvent.setup();
    renderWithProviders(<DownloadsPage onNavigate={onNavigate} />);

    await user.click(screen.getByTestId("button-contact-downloads"));

    expect(onNavigate).toHaveBeenCalledWith("contact");
  });
});
