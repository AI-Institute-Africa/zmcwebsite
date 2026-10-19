import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import MagazinePage from "./MagazinePage";
import { renderWithProviders } from "../test/render";

describe("MagazinePage", () => {
  it("renders the coming-soon message and contact CTA", async () => {
    const onNavigate = vi.fn();
    const user = userEvent.setup();
    renderWithProviders(<MagazinePage onNavigate={onNavigate} />);

    expect(screen.getByTestId("text-magazine-title")).toHaveTextContent("ZMC Magazine Coming Soon");
    expect(screen.getByTestId("text-magazine-message")).toBeInTheDocument();

    await user.click(screen.getByTestId("button-contact-magazine"));
    expect(onNavigate).toHaveBeenCalledWith("contact");
  });
});
