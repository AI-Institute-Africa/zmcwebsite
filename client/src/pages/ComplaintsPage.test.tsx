import { describe, it, expect, vi, beforeEach } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ComplaintsPage from "./ComplaintsPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: { submitComplaint: vi.fn() },
}));

import { portalApi } from "../lib/api";

const submitComplaint = portalApi.submitComplaint as unknown as ReturnType<typeof vi.fn>;

function setup() {
  return renderWithProviders(<ComplaintsPage onNavigate={vi.fn()} />);
}

describe("ComplaintsPage", () => {
  beforeEach(() => {
    submitComplaint.mockReset().mockResolvedValue({ status: "ok" });
  });

  it("renders the hero and the media complaint categories", () => {
    setup();
    expect(screen.getByRole("heading", { level: 1, name: "Media Complaints" })).toBeInTheDocument();
    expect(screen.getByText("Print Media")).toBeInTheDocument();
    expect(screen.getByText("Radio")).toBeInTheDocument();
    expect(screen.getByText("Television")).toBeInTheDocument();
    expect(screen.getByText("Online Media")).toBeInTheDocument();
  });

  it("opens and closes the complaint modal", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-complaint-top"));
    expect(screen.getByRole("heading", { name: "Upload Complaint Documents" })).toBeInTheDocument();
    expect(document.body.style.overflow).toBe("hidden");

    await user.click(screen.getByTestId("button-cancel-complaint"));
    await waitFor(() => expect(screen.queryByTestId("button-submit-complaint")).not.toBeInTheDocument());
    expect(document.body.style.overflow).toBe("");
  });

  it("rejects files larger than 10MB", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-complaint-top"));

    const big = new File([new ArrayBuffer(11 * 1024 * 1024)], "big.pdf", { type: "application/pdf" });
    await user.upload(screen.getByTestId("input-complaint-file-upload"), big);

    expect(screen.getByTestId("text-file-error")).toHaveTextContent(/exceeds the 10MB size limit/i);
  });

  it("submits the complaint to the portal and shows success", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-complaint-top"));

    await user.type(screen.getByTestId("input-complaint-name"), "Jane Doe");
    await user.type(screen.getByTestId("input-complaint-email"), "jane@example.com");
    await user.type(screen.getByTestId("input-complaint-description"), "A detailed complaint about a broadcast.");
    await user.type(screen.getByTestId("input-complaint-evidence-link"), "https://example.com/proof");

    const file = new File(["hello"], "evidence.pdf", { type: "application/pdf" });
    await user.upload(screen.getByTestId("input-complaint-file-upload"), file);

    await user.click(screen.getByTestId("button-submit-complaint"));

    await waitFor(() => expect(submitComplaint).toHaveBeenCalledOnce());
    const formData = submitComplaint.mock.calls[0][0] as FormData;
    expect(formData).toBeInstanceOf(FormData);
    expect(formData.get("full_name")).toBe("Jane Doe");
    expect(formData.get("email")).toBe("jane@example.com");
    expect(formData.get("subject")).toBe("Public Complaint");
    const messages = formData.getAll("message").map(String).join("\n");
    expect(messages).toContain("A detailed complaint about a broadcast.");
    expect(messages).toContain("Evidence Link: https://example.com/proof");
    expect(formData.get("attachment")).toBeInstanceOf(File);

    expect(await screen.findByText("Documents Submitted!")).toBeInTheDocument();
  });

  it("shows an error message when submission fails", async () => {
    submitComplaint.mockRejectedValue(new Error("Network down"));
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-complaint-top"));
    await user.type(screen.getByTestId("input-complaint-name"), "Jane Doe");
    await user.type(screen.getByTestId("input-complaint-email"), "jane@example.com");
    await user.type(screen.getByTestId("input-complaint-description"), "A detailed complaint here.");
    await user.click(screen.getByTestId("button-submit-complaint"));

    expect(await screen.findByTestId("text-submit-error")).toHaveTextContent("Network down");
  });

  it("surfaces a status:error response as an error", async () => {
    submitComplaint.mockResolvedValue({ status: "error", message: "Rejected" });
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-complaint-top"));
    await user.type(screen.getByTestId("input-complaint-name"), "Jane Doe");
    await user.type(screen.getByTestId("input-complaint-email"), "jane@example.com");
    await user.type(screen.getByTestId("input-complaint-description"), "A detailed complaint here.");
    await user.click(screen.getByTestId("button-submit-complaint"));

    expect(await screen.findByTestId("text-submit-error")).toHaveTextContent("Rejected");
  });
});
