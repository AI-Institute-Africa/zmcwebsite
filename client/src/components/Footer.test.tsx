import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import Footer from "./Footer";
import en from "../i18n/en";
import { SOCIAL_LINKS, CONTACT_EMAIL } from "../lib/api";
import { renderWithProviders } from "../test/render";

describe("Footer", () => {
  it("renders the org name, mission and copyright", () => {
    renderWithProviders(<Footer onNavigate={() => {}} />);
    expect(screen.getByText(en.footer.zmcName)).toBeInTheDocument();
    expect(screen.getByText(en.footer.mission)).toBeInTheDocument();
    expect(screen.getByText(new RegExp(String(new Date().getFullYear())))).toBeInTheDocument();
  });

  it("shows the head office contact details", () => {
    renderWithProviders(<Footer onNavigate={() => {}} />);
    expect(screen.getByText("108 Swan Drive, Alexandra Park, Harare")).toBeInTheDocument();
    expect(screen.getByText("+263 242 253509/10")).toBeInTheDocument();
    expect(screen.getByText(CONTACT_EMAIL)).toBeInTheDocument();
  });

  it("renders quick links and services pointing at the right paths", () => {
    renderWithProviders(<Footer onNavigate={() => {}} />);
    expect(screen.getByTestId("footer-link-about")).toHaveAttribute("href", "/about");
    expect(screen.getByTestId("footer-link-contact")).toHaveAttribute("href", "/contact");
    expect(screen.getByTestId("footer-service-complaints")).toHaveAttribute("href", "/complaints");
    expect(screen.getByTestId("footer-service-appeals")).toHaveAttribute("href", "/appeals");
  });

  it("renders the four social links", () => {
    renderWithProviders(<Footer onNavigate={() => {}} />);
    expect(screen.getByTestId("social-link-0")).toHaveAttribute("href", SOCIAL_LINKS.facebook);
    expect(screen.getByTestId("social-link-1")).toHaveAttribute("href", SOCIAL_LINKS.x);
    expect(screen.getByTestId("social-link-2")).toHaveAttribute("href", SOCIAL_LINKS.instagram);
    expect(screen.getByTestId("social-link-3")).toHaveAttribute("href", SOCIAL_LINKS.youtube);
  });
});
