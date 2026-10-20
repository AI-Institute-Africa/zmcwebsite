import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ServicesCards from "./ServicesCards";
import en from "../i18n/en";
import { PORTAL_LOGIN_URL, TRAINING_HUB_URL } from "../lib/api";
import { renderWithProviders } from "../test/render";

describe("ServicesCards", () => {
  it("renders all five service cards with translated titles", () => {
    renderWithProviders(<ServicesCards onNavigate={vi.fn()} />);
    expect(screen.getByTestId("service-card-accreditation")).toBeInTheDocument();
    expect(screen.getByTestId("service-card-registration")).toBeInTheDocument();
    expect(screen.getByTestId("service-card-complaints")).toBeInTheDocument();
    expect(screen.getByTestId("service-card-appeals")).toBeInTheDocument();
    expect(screen.getByTestId("service-card-training")).toBeInTheDocument();
    expect(screen.getByText(en.services.accreditationTitle)).toBeInTheDocument();
    expect(screen.getByText(en.services.trainingTitle)).toBeInTheDocument();
  });

  describe("navigation", () => {
    beforeEach(() => {
      vi.spyOn(window, "open").mockImplementation(() => null);
    });
    afterEach(() => {
      vi.restoreAllMocks();
    });

    it("navigates internally for complaints", async () => {
      const user = userEvent.setup();
      const onNavigate = vi.fn();
      renderWithProviders(<ServicesCards onNavigate={onNavigate} />);
      await user.click(screen.getByTestId("service-card-complaints"));
      expect(onNavigate).toHaveBeenCalledWith("complaints");
      expect(window.open).not.toHaveBeenCalled();
    });

    it("navigates internally for appeals", async () => {
      const user = userEvent.setup();
      const onNavigate = vi.fn();
      renderWithProviders(<ServicesCards onNavigate={onNavigate} />);
      await user.click(screen.getByTestId("service-card-appeals"));
      expect(onNavigate).toHaveBeenCalledWith("appeals");
    });

    it("opens the training hub in a new tab", async () => {
      const user = userEvent.setup();
      const onNavigate = vi.fn();
      renderWithProviders(<ServicesCards onNavigate={onNavigate} />);
      await user.click(screen.getByTestId("service-card-training"));
      expect(window.open).toHaveBeenCalledWith(TRAINING_HUB_URL, "_blank", "noopener,noreferrer");
      expect(onNavigate).not.toHaveBeenCalled();
    });

    it("opens the portal in a new tab for accreditation and registration", async () => {
      const user = userEvent.setup();
      const onNavigate = vi.fn();
      renderWithProviders(<ServicesCards onNavigate={onNavigate} />);
      await user.click(screen.getByTestId("service-card-accreditation"));
      await user.click(screen.getByTestId("service-card-registration"));
      expect(window.open).toHaveBeenCalledTimes(2);
      expect(window.open).toHaveBeenCalledWith(PORTAL_LOGIN_URL, "_blank", "noopener,noreferrer");
      expect(onNavigate).not.toHaveBeenCalled();
    });
  });
});
