import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import AboutPage from "./AboutPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({ PORTAL_LOGIN_URL: "https://portal.test/login" }));

describe("AboutPage", () => {
  it("renders the mandate, functions and CTA", () => {
    renderWithProviders(<AboutPage onNavigate={vi.fn()} />);

    expect(screen.getByRole("heading", { name: "Purpose & General Mandate" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "Functions of the ZMC" })).toBeInTheDocument();
    expect(screen.getByText("Uphold Media Freedom")).toBeInTheDocument();
    expect(screen.getByTestId("button-about-accreditation")).toHaveAttribute("href", "https://portal.test/login");
  });

  it("navigates to contact when the contact CTA is clicked", async () => {
    const onNavigate = vi.fn();
    const user = userEvent.setup();
    renderWithProviders(<AboutPage onNavigate={onNavigate} />);

    await user.click(screen.getByTestId("button-about-contact"));

    expect(onNavigate).toHaveBeenCalledWith("contact");
  });
});
