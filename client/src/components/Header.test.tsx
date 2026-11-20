import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Header from "./Header";
import { PORTAL_BASE_URL } from "../lib/api";
import { renderWithProviders } from "../test/render";

describe("Header", () => {
  it("renders the logo and primary navigation items", () => {
    renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);
    expect(screen.getByAltText("Zimbabwe Media Commission Logo")).toBeInTheDocument();
    expect(screen.getByTestId("nav-home")).toBeInTheDocument();
    expect(screen.getAllByTestId("nav-about").length).toBeGreaterThan(0);
    expect(screen.getByTestId("nav-services")).toBeInTheDocument();
    expect(screen.getByTestId("nav-opportunities")).toBeInTheDocument();
    expect(screen.getByTestId("nav-media-centre")).toBeInTheDocument();
    expect(screen.getByTestId("nav-events")).toBeInTheDocument();
    expect(screen.getByTestId("nav-contact")).toBeInTheDocument();
  });

  it("renders the quick-action buttons with the right destinations", () => {
    renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);
    expect(screen.getByTestId("button-registration-header")).toHaveAttribute("href", "/registration");
    expect(screen.getByTestId("button-reports-documents-header")).toHaveAttribute("href", "/downloads");
    expect(screen.getByTestId("button-complaints-header")).toHaveAttribute("href", "/complaints");
    expect(screen.getByTestId("button-appeals-header")).toHaveAttribute("href", "/appeals");
  });

  it("links the portal button to the portal home", () => {
    renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);
    const portal = screen.getByTestId("button-portal");
    expect(portal).toHaveAttribute("href", `${PORTAL_BASE_URL}/`);
    expect(portal).toHaveAttribute("target", "_blank");
  });

  it("calls onNavigate when the accreditation quick-action is clicked", async () => {
    const user = userEvent.setup();
    const onNavigate = vi.fn();
    renderWithProviders(<Header currentPage="home" onNavigate={onNavigate} />);
    await user.click(screen.getByTestId("button-accreditation-header"));
    expect(onNavigate).toHaveBeenCalledWith("accreditation");
  });

  it("toggles the mobile menu and locks body scroll", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);
    const toggle = screen.getByTestId("button-mobile-menu");

    await user.click(toggle);
    expect(document.body.style.overflow).toBe("hidden");

    await user.click(toggle);
    expect(document.body.style.overflow).toBe("");
  });

  it("marks the active page with a distinguishing style", () => {
    renderWithProviders(<Header currentPage="events" onNavigate={vi.fn()} />);
    expect(screen.getByTestId("nav-events")).toBeInTheDocument();
  });

  describe("testing phase notice", () => {
    beforeEach(() => {
      localStorage.removeItem("zmc_testing_notice_closed");
    });

    afterEach(() => {
      localStorage.removeItem("zmc_testing_notice_closed");
    });

    it("renders the testing phase notice by default with a dismissible close button", () => {
      renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);

      const notice = screen.getByRole("status", { name: /portal testing notice/i });
      expect(notice).toBeInTheDocument();
      expect(screen.getByText(/Testing phase:/)).toBeInTheDocument();
      expect(screen.getByText(/some features may not work as expected/)).toBeInTheDocument();
      expect(screen.getByLabelText(/Dismiss testing notice/)).toBeInTheDocument();
    });

    it("hides the notice and persists dismissal in localStorage when the close button is clicked", async () => {
      const user = userEvent.setup();
      renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);

      expect(screen.getByRole("status", { name: /portal testing notice/i })).toBeInTheDocument();
      expect(localStorage.getItem("zmc_testing_notice_closed")).toBeNull();

      await user.click(screen.getByLabelText(/Dismiss testing notice/));

      expect(screen.queryByRole("status", { name: /portal testing notice/i })).not.toBeInTheDocument();
      expect(localStorage.getItem("zmc_testing_notice_closed")).toBe("1");
    });

    it("does not render the notice when it was already dismissed in localStorage", () => {
      localStorage.setItem("zmc_testing_notice_closed", "1");
      renderWithProviders(<Header currentPage="home" onNavigate={vi.fn()} />);

      expect(screen.queryByRole("status", { name: /portal testing notice/i })).not.toBeInTheDocument();
      expect(screen.queryByText(/Testing phase:/)).not.toBeInTheDocument();
    });

    it("keeps the rest of the header functional after the notice is dismissed", async () => {
      const user = userEvent.setup();
      const onNavigate = vi.fn();
      renderWithProviders(<Header currentPage="home" onNavigate={onNavigate} />);

      await user.click(screen.getByLabelText(/Dismiss testing notice/));
      expect(screen.queryByRole("status", { name: /portal testing notice/i })).not.toBeInTheDocument();

      await user.click(screen.getByTestId("button-portal"));
      expect(onNavigate).not.toHaveBeenCalled(); // portal link opens in new tab, does not call onNavigate

      await user.click(screen.getByTestId("button-accreditation-header"));
      expect(onNavigate).toHaveBeenCalledWith("accreditation");
    });
  });
});
