import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import AccreditationPage from "./AccreditationPage";
import { openMediaPractitionerAuth } from "../lib/portalAuth";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/portalAuth", () => ({ openMediaPractitionerAuth: vi.fn() }));

const openMediaPractitionerAuthMock = openMediaPractitionerAuth as unknown as ReturnType<
  typeof vi.fn
>;

describe("AccreditationPage", () => {
  beforeEach(() => {
    openMediaPractitionerAuthMock.mockClear();
  });

  it("renders the process steps and apply actions", () => {
    renderWithProviders(<AccreditationPage onNavigate={vi.fn()} />);

    expect(screen.getByText("Gather Documents")).toBeInTheDocument();
    expect(screen.getByText("Complete Form")).toBeInTheDocument();
    expect(screen.getByTestId("button-apply-accreditation")).toBeInTheDocument();
    expect(screen.getByTestId("button-renew-accreditation")).toBeInTheDocument();
  });

  it("opens the media practitioner signup when applying", async () => {
    const user = userEvent.setup();
    renderWithProviders(<AccreditationPage onNavigate={vi.fn()} />);

    await user.click(screen.getByTestId("button-apply-accreditation"));

    expect(openMediaPractitionerAuthMock).toHaveBeenCalledWith("signup");
  });

  it("opens the media practitioner login when renewing", async () => {
    const user = userEvent.setup();
    renderWithProviders(<AccreditationPage onNavigate={vi.fn()} />);

    await user.click(screen.getByTestId("button-renew-accreditation"));

    expect(openMediaPractitionerAuthMock).toHaveBeenCalledWith("login");
  });
});
