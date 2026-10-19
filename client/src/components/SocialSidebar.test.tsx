import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import SocialSidebar from "./SocialSidebar";
import { SOCIAL_LINKS } from "../lib/api";

describe("SocialSidebar", () => {
  it("renders a link for each social platform", () => {
    render(<SocialSidebar />);
    for (const label of ["facebook", "x", "instagram", "tiktok", "youtube", "whatsapp"]) {
      expect(screen.getByTestId(`social-sidebar-${label}`)).toBeInTheDocument();
    }
  });

  it("points each link to the configured URL and opens in a new tab", () => {
    render(<SocialSidebar />);
    const facebook = screen.getByTestId("social-sidebar-facebook");
    expect(facebook).toHaveAttribute("href", SOCIAL_LINKS.facebook);
    expect(facebook).toHaveAttribute("target", "_blank");
    expect(facebook).toHaveAttribute("rel", "noopener noreferrer");
  });

  it("renders brand SVGs for X and TikTok", () => {
    render(<SocialSidebar />);
    expect(screen.getByTestId("social-sidebar-x").querySelector("svg")).toBeInTheDocument();
    expect(screen.getByTestId("social-sidebar-tiktok").querySelector("svg")).toBeInTheDocument();
  });
});
