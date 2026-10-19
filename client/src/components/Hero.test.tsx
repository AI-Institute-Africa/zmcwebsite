import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Hero from "./Hero";
import en from "../i18n/en";
import { PORTAL_LOGIN_URL } from "../lib/api";
import { renderWithProviders } from "../test/render";

describe("Hero", () => {
  it("renders the welcome heading and subtitle", () => {
    renderWithProviders(<Hero onNavigate={vi.fn()} />);
    expect(screen.getByText(en.hero.welcomeTo)).toBeInTheDocument();
    expect(screen.getByText(en.hero.title)).toBeInTheDocument();
    expect(screen.getByText(en.hero.subtitle)).toBeInTheDocument();
  });

  it("navigates to about when Learn More is clicked", async () => {
    const user = userEvent.setup();
    const onNavigate = vi.fn();
    renderWithProviders(<Hero onNavigate={onNavigate} />);
    await user.click(screen.getByTestId("button-learn-more"));
    expect(onNavigate).toHaveBeenCalledWith("about");
  });

  it("links Get Accredited to the portal login in a new tab", () => {
    renderWithProviders(<Hero onNavigate={vi.fn()} />);
    const link = screen.getByTestId("button-get-accredited");
    expect(link).toHaveAttribute("href", PORTAL_LOGIN_URL);
    expect(link).toHaveAttribute("target", "_blank");
  });

  it("renders the quick stats grid and the four stat boxes", () => {
    renderWithProviders(<Hero onNavigate={vi.fn()} />);
    expect(screen.getByText(en.hero.quickStats)).toBeInTheDocument();
    for (let i = 0; i < 4; i++) {
      expect(screen.getByTestId(`stat-box-${i}`)).toBeInTheDocument();
    }
    expect(screen.getAllByText("500+").length).toBeGreaterThan(0);
    expect(screen.getAllByText("150+").length).toBeGreaterThan(0);
  });
});
