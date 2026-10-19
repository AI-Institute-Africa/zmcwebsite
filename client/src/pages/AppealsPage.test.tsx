import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { fireEvent, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import AppealsPage from "./AppealsPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  portalApi: {
    submitAppeal: vi.fn(),
    initiateAppealPayment: vi.fn(),
    uploadAppealPaymentProof: vi.fn(),
  },
  CONTACT_EMAIL: "info@zmc.org.zw",
}));

import { portalApi } from "../lib/api";

const submitAppeal = portalApi.submitAppeal as unknown as ReturnType<typeof vi.fn>;
const initiateAppealPayment = portalApi.initiateAppealPayment as unknown as ReturnType<typeof vi.fn>;
const uploadAppealPaymentProof = portalApi.uploadAppealPaymentProof as unknown as ReturnType<typeof vi.fn>;

function setup() {
  return renderWithProviders(<AppealsPage onNavigate={vi.fn()} />);
}

async function fillDetails(user: ReturnType<typeof userEvent.setup>) {
  await user.type(screen.getByTestId("input-appeal-name"), "John Doe");
  await user.type(screen.getByTestId("input-appeal-email"), "john@example.com");
  await user.type(screen.getByTestId("input-appeal-entity"), "Ministry of Info");
  const file = new File(["doc"], "appeal.pdf", { type: "application/pdf" });
  await user.upload(screen.getByTestId("input-appeal-file-upload"), file);
}

function submitDetails() {
  const button = screen.getByTestId("button-continue-payment");
  fireEvent.submit(button.closest("form") as HTMLFormElement);
}

describe("AppealsPage", () => {
  beforeEach(() => {
    submitAppeal.mockReset().mockResolvedValue({ data: { appeal_id: 42, reference: "ZMC-42", amount: 10 } });
    initiateAppealPayment.mockReset();
    uploadAppealPaymentProof.mockReset();
    window.history.replaceState({}, "", "/appeals");
  });

  afterEach(() => {
    window.history.replaceState({}, "", "/appeals");
  });

  it("renders the hero and the FOIA appeal form controls", () => {
    setup();
    expect(screen.getByRole("heading", { level: 1, name: "FOIA Appeals" })).toBeInTheDocument();
    expect(screen.getByTestId("button-download-appeal-form")).toHaveAttribute("href", "/downloads/foia-appeal-form.pdf");
    expect(screen.getByTestId("button-lodge-appeal-top")).toBeInTheDocument();
  });

  it("shows a paid banner from the ?payment=paid query param", () => {
    window.history.replaceState({}, "", "/appeals?payment=paid");
    setup();
    expect(screen.getByTestId("text-payment-banner")).toHaveTextContent(/payment was received/i);
  });

  it("shows a failure banner for an unknown payment status", () => {
    window.history.replaceState({}, "", "/appeals?payment=error");
    setup();
    expect(screen.getByTestId("text-payment-banner")).toHaveTextContent(/could not confirm your payment/i);
  });

  it("validates that required fields and a document are provided", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    submitDetails();
    expect(await screen.findByText("Please complete all required fields.")).toBeInTheDocument();
    expect(submitAppeal).not.toHaveBeenCalled();
  });

  it("requires a supporting document", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    await user.type(screen.getByTestId("input-appeal-name"), "John Doe");
    await user.type(screen.getByTestId("input-appeal-email"), "john@example.com");
    await user.type(screen.getByTestId("input-appeal-entity"), "Ministry of Info");
    submitDetails();
    expect(await screen.findByText("Please attach at least one supporting document.")).toBeInTheDocument();
  });

  it("submits appeal details then advances to the payment step", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    await fillDetails(user);
    submitDetails();

    await waitFor(() => expect(submitAppeal).toHaveBeenCalledOnce());
    const payload = submitAppeal.mock.calls[0][0] as FormData;
    expect(payload.get("full_name")).toBe("John Doe");
    expect(payload.get("email")).toBe("john@example.com");
    expect(String(payload.get("subject"))).toContain("FOIA Appeal - Ministry of Info");
    expect(payload.get("attachment")).toBeInstanceOf(File);

    expect(await screen.findByTestId("text-appeal-reference")).toHaveTextContent("ZMC-42");
    expect(screen.getByTestId("button-pay-paynow")).toBeInTheDocument();
  });

  it("rejects appeal files larger than 10MB", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    const big = new File([new ArrayBuffer(11 * 1024 * 1024)], "big.pdf", { type: "application/pdf" });
    await user.upload(screen.getByTestId("input-appeal-file-upload"), big);
    expect(screen.getByTestId("text-file-error-appeal")).toHaveTextContent(/exceeds the 10MB size limit/i);
  });

  it("redirects to the Paynow URL returned by the portal", async () => {
    initiateAppealPayment.mockResolvedValue({ data: { redirectUrl: "https://paynow.test/checkout" } });
    const descriptor = Object.getOwnPropertyDescriptor(window, "location");
    const fakeLocation = { href: "http://localhost/appeals", search: "", hash: "", pathname: "/appeals" };
    Object.defineProperty(window, "location", { configurable: true, writable: true, value: fakeLocation });

    try {
      const user = userEvent.setup();
      setup();
      await user.click(screen.getByTestId("button-lodge-appeal-top"));
      await fillDetails(user);
      submitDetails();
      await screen.findByTestId("button-pay-paynow");

      await user.click(screen.getByTestId("button-pay-paynow"));
      await waitFor(() => expect(initiateAppealPayment).toHaveBeenCalledWith({ appeal_id: 42, email: "john@example.com" }));
      expect(fakeLocation.href).toBe("https://paynow.test/checkout");
    } finally {
      if (descriptor) Object.defineProperty(window, "location", descriptor);
    }
  });

  it("shows a payment error when no redirect URL is returned", async () => {
    initiateAppealPayment.mockResolvedValue({ data: { message: "Paynow unavailable" } });
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    await fillDetails(user);
    submitDetails();
    await screen.findByTestId("button-pay-paynow");

    await user.click(screen.getByTestId("button-pay-paynow"));
    expect(await screen.findByTestId("text-payment-error")).toHaveTextContent("Paynow unavailable");
  });

  it("uploads proof of payment and shows the done step with a reference", async () => {
    uploadAppealPaymentProof.mockResolvedValue({ data: { status: "ok" } });
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    await fillDetails(user);
    submitDetails();
    await screen.findByTestId("button-upload-proof");

    const proof = new File(["proof"], "proof.pdf", { type: "application/pdf" });
    await user.upload(screen.getByTestId("input-appeal-proof-upload"), proof);
    await user.type(screen.getByTestId("input-appeal-proof-ref"), "TXN-99");
    await user.click(screen.getByTestId("button-upload-proof"));

    await waitFor(() => expect(uploadAppealPaymentProof).toHaveBeenCalledOnce());
    const payload = uploadAppealPaymentProof.mock.calls[0][0] as FormData;
    expect(payload.get("appeal_id")).toBe("42");
    expect(payload.get("payment_reference")).toBe("TXN-99");
    expect(payload.get("payment_proof")).toBeInstanceOf(File);

    expect(await screen.findByText("Appeal Submitted!")).toBeInTheDocument();
    expect(screen.getByTestId("text-done-reference")).toHaveTextContent("ZMC-42");
    expect(screen.getByText(/proof of payment has been received/i)).toBeInTheDocument();
  });

  it("requires a proof file before uploading", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-lodge-appeal-top"));
    await fillDetails(user);
    submitDetails();
    await screen.findByTestId("button-upload-proof");

    expect(screen.getByTestId("button-upload-proof")).toBeDisabled();
    await user.click(screen.getByTestId("button-skip-payment"));
    expect(screen.getByText("Appeal Submitted!")).toBeInTheDocument();
    expect(screen.getByText(/FOIA appeal has been received/i)).toBeInTheDocument();
    expect(screen.queryByText(/proof of payment has been received/i)).not.toBeInTheDocument();
  });
});
