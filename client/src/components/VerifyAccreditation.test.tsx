import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import VerifyAccreditation from "./VerifyAccreditation";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: {
    verifyAccreditation: vi.fn(),
  },
}));

import { portalApi } from "../lib/api";

const verifyAccreditation = portalApi.verifyAccreditation as unknown as ReturnType<typeof vi.fn>;

async function submitNumber(number: string) {
  renderWithProviders(<VerifyAccreditation />);
  const user = userEvent.setup();
  const input = screen.getByTestId("input-verify-accreditation");
  await user.type(input, number);
  await user.click(screen.getByTestId("button-verify-accreditation"));
  return user;
}

describe("VerifyAccreditation", () => {
  beforeEach(() => {
    verifyAccreditation.mockReset();
  });

  it("renders the search form with the button disabled until a number is entered", async () => {
    renderWithProviders(<VerifyAccreditation />);

    expect(screen.getByRole("heading", { name: "Verify an Accreditation Number" })).toBeInTheDocument();
    const button = screen.getByTestId("button-verify-accreditation");
    expect(button).toBeDisabled();

    const user = userEvent.setup();
    await user.type(screen.getByTestId("input-verify-accreditation"), "ZMC/ACC/2026/0001");
    expect(button).toBeEnabled();
  });

  it("shows a valid and active accreditation with holder details", async () => {
    verifyAccreditation.mockResolvedValue({
      status: "success",
      valid: true,
      data: {
        accreditation_number: "ZMC/ACC/2026/0001",
        first_name: "Tendai",
        surname: "Moyo",
        designation: "Senior Reporter",
        organisation: "Daily News",
        medium: "Print",
        nationality: "Zimbabwean",
        employment_type: "employed",
        expires_at: "2027-06-30T00:00:00Z",
      },
    });

    await submitNumber("ZMC/ACC/2026/0001");

    expect(await screen.findByText("Valid & Active Accreditation")).toBeInTheDocument();
    expect(screen.getByText("Tendai Moyo")).toBeInTheDocument();
    expect(screen.getByText("Employed (worker)")).toBeInTheDocument();
    expect(screen.getByText("Senior Reporter")).toBeInTheDocument();
    expect(screen.getByText("Daily News")).toBeInTheDocument();
    expect(screen.getByText("Print")).toBeInTheDocument();
    expect(verifyAccreditation).toHaveBeenCalledWith("ZMC/ACC/2026/0001");
  });

  it("labels a freelancer with the freelancer badge", async () => {
    verifyAccreditation.mockResolvedValue({
      status: "success",
      valid: true,
      data: { full_name: "Rudo Chikafu", employment_type: "freelancer" },
    });

    await submitNumber("ZMC/ACC/2026/0002");

    expect(await screen.findByText("Freelancer")).toBeInTheDocument();
    expect(screen.getByText("Rudo Chikafu")).toBeInTheDocument();
  });

  it("shows the not-found state when there is no matching record", async () => {
    verifyAccreditation.mockResolvedValue({
      status: "not_found",
      message: "No record for that number.",
    });

    await submitNumber("ZMC/ACC/0000/0000");

    expect(await screen.findByText("No matching record found")).toBeInTheDocument();
    expect(screen.getByText("No record for that number.")).toBeInTheDocument();
  });

  it("shows the inactive state for an existing but inactive record", async () => {
    verifyAccreditation.mockResolvedValue({ status: "invalid" });

    await submitNumber("ZMC/ACC/2024/0009");

    expect(await screen.findByText(/Not valid/)).toBeInTheDocument();
  });

  it("shows an error alert when verification throws", async () => {
    verifyAccreditation.mockRejectedValue(new Error("Network down"));

    await submitNumber("ZMC/ACC/2026/0003");

    const alert = await screen.findByRole("alert");
    expect(alert).toHaveTextContent("Verification unavailable");
    expect(alert).toHaveTextContent("Network down");
  });

  it("resets back to the form when verifying another number", async () => {
    verifyAccreditation.mockResolvedValue({
      status: "success",
      valid: true,
      data: { full_name: "Reset Person", accreditation_number: "ZMC/ACC/2026/0004" },
    });

    const user = await submitNumber("ZMC/ACC/2026/0004");
    await user.click(await screen.findByRole("button", { name: "Verify another number" }));

    await waitFor(() => expect(screen.queryByText("Valid & Active Accreditation")).not.toBeInTheDocument());
    expect(screen.getByTestId("input-verify-accreditation")).toHaveValue("");
  });
});
