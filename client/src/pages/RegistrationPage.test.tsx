import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import RegistrationPage from "./RegistrationPage";
import { openMediaHouseAuth } from "../lib/portalAuth";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/portalAuth", () => ({ openMediaHouseAuth: vi.fn() }));

const openMediaHouseAuthMock = openMediaHouseAuth as unknown as ReturnType<typeof vi.fn>;

describe("RegistrationPage", () => {
  beforeEach(() => {
    openMediaHouseAuthMock.mockClear();
  });

  it("renders the registration steps and actions", () => {
    renderWithProviders(<RegistrationPage onNavigate={vi.fn()} />);

    expect(screen.getByText("Gather Documents")).toBeInTheDocument();
    expect(screen.getByTestId("button-new-registration")).toBeInTheDocument();
    expect(screen.getByTestId("button-renew-registration")).toBeInTheDocument();
  });

  it("opens the media house signup when registering", async () => {
    const user = userEvent.setup();
    renderWithProviders(<RegistrationPage onNavigate={vi.fn()} />);

    await user.click(screen.getByTestId("button-new-registration"));

    expect(openMediaHouseAuthMock).toHaveBeenCalledWith("signup");
  });

  it("opens the media house login when renewing", async () => {
    const user = userEvent.setup();
    renderWithProviders(<RegistrationPage onNavigate={vi.fn()} />);

    await user.click(screen.getByTestId("button-renew-registration"));

    expect(openMediaHouseAuthMock).toHaveBeenCalledWith("login");
  });
});
