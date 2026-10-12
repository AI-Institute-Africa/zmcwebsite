import { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { Scale, CheckCircle, Download, CreditCard, ExternalLink, Loader2, AlertCircle } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import { portalApi, CONTACT_EMAIL } from "../lib/api";

interface AppealsPageProps {
  onNavigate: (page: string) => void;
}

const MAX_FILE_SIZE = 10 * 1024 * 1024;

export default function AppealsPage({ onNavigate }: AppealsPageProps) {
  const { t } = useLanguage();
  const [showAppealModal, setShowAppealModal] = useState(false);
  const [step, setStep] = useState<"details" | "payment" | "done">("details");
  const [fileError, setFileError] = useState("");
  const [formError, setFormError] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isPaying, setIsPaying] = useState(false);
  const [paymentError, setPaymentError] = useState("");

  const [appeal, setAppeal] = useState<{ id: string; fee: number } | null>(null);
  const [form, setForm] = useState({
    fullName: "",
    email: "",
    phone: "",
    entity: "",
    reference: "",
    details: "",
  });

  const [paymentBanner, setPaymentBanner] = useState<"paid" | "cancelled" | "unknown" | null>(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const status = params.get("payment");
    if (status) {
      if (status === "paid") setPaymentBanner("paid");
      else if (status === "cancelled" || status === "canceled") setPaymentBanner("cancelled");
      else setPaymentBanner("unknown");
      const url = new URL(window.location.href);
      url.searchParams.delete("payment");
      window.history.replaceState({}, "", url.toString());
    }
  }, []);

  useEffect(() => {
    if (showAppealModal) {
      document.body.style.overflow = "hidden";
      document.documentElement.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
      document.documentElement.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
      document.documentElement.style.overflow = "";
    };
  }, [showAppealModal]);

  const resetModal = () => {
    setStep("details");
    setAppeal(null);
    setFormError("");
    setPaymentError("");
    setFileError("");
    setForm({ fullName: "", email: "", phone: "", entity: "", reference: "", details: "" });
  };

  const closeModal = () => {
    setShowAppealModal(false);
    setTimeout(resetModal, 200);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files) return;
    for (let i = 0; i < files.length; i++) {
      if (files[i].size > MAX_FILE_SIZE) {
        setFileError(`File "${files[i].name}" exceeds the 10MB size limit.`);
        e.target.value = "";
        return;
      }
    }
    setFileError("");
  };

  const update = (key: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
    setForm((f) => ({ ...f, [key]: e.target.value }));

  const handleDetailsSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (fileError) return;
    if (!form.fullName.trim() || !form.email.trim() || !form.entity.trim() || !form.details.trim()) {
      setFormError("Please complete all required fields.");
      return;
    }
    setFormError("");
    setIsSubmitting(true);
    try {
      const res: any = await portalApi.submitAppeal(form);
      const data = res?.data ?? res;
      const id = data?.appeal_id ?? data?.id;
      const fee = Number(data?.fee ?? data?.appeal_fee) || 10;
      setAppeal({ id: String(id), fee });
      setStep("payment");
    } catch (err: any) {
      setFormError(err.message || "Failed to submit your appeal. Please try again.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handlePaynow = async () => {
    if (!appeal) return;
    setPaymentError("");
    setIsPaying(true);
    try {
      const res: any = await portalApi.initiateAppealPayment({ appeal_id: Number(appeal.id), email: form.email });
      const data = res?.data ?? res;
      const redirectUrl = data?.redirectUrl ?? data?.redirect_url;
      if (redirectUrl) {
        window.location.href = redirectUrl;
        return;
      }
      setPaymentError(
        data?.message ||
          "Online payment is temporarily unavailable. Please contact the Commission to complete your payment.",
      );
    } catch (err: any) {
      setPaymentError(err.message || "Unable to start the Paynow payment. Please try again.");
    } finally {
      setIsPaying(false);
    }
  };

  const inputStyle = { border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" } as const;

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.appeals.title}
        subtitle={t.pages.appeals.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "FOIA Appeals" }]}
      />

      {paymentBanner && (
        <div className="px-4 md:px-8 pt-6">
          <div
            className="max-w-[1000px] mx-auto p-4 rounded-xl flex items-center gap-3 text-sm"
            style={{
              background: paymentBanner === "paid" ? "#ecfdf5" : paymentBanner === "cancelled" ? "#fef3c7" : "var(--neutral-50)",
              border: `1px solid ${paymentBanner === "paid" ? "#a7f3d0" : paymentBanner === "cancelled" ? "#fde68a" : "var(--neutral-200)"}`,
              color: "var(--neutral-700)",
            }}
            data-testid="text-payment-banner"
          >
            {paymentBanner === "paid" ? (
              <>
                <CheckCircle className="w-5 h-5" style={{ color: "#16a34a" }} />
                <span>Thank you — your Paynow payment was received. Your appeal is now being processed.</span>
              </>
            ) : (
              <>
                <AlertCircle className="w-5 h-5" style={{ color: "#b45309" }} />
                <span>
                  We could not confirm your payment. If you were charged, please contact us at {CONTACT_EMAIL}.
                </span>
              </>
            )}
          </div>
        </div>
      )}

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <div className="bg-white rounded-xl md:rounded-[20px] p-6 md:p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 className="text-xl md:text-2xl" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>{t.pages.appeals.aboutTitle}</h2>
            <p className="leading-relaxed text-sm md:text-base mt-4" style={{ color: "var(--neutral-700)" }}>
              Under the <strong>Freedom of Information Act (FOIA)</strong>, the Zimbabwe Media Commission is mandated to review decisions
              relating to access to information held by public entities. The Commission receives appeals from members of the
              public who are denied access to requested information from government and public bodies.
            </p>
            <p className="leading-relaxed text-sm md:text-base mt-4" style={{ color: "var(--neutral-700)" }}>
              If a public entity has refused your request for information, delayed unreasonably, charged excessive fees, or provided incomplete information, you have the right to appeal to the Zimbabwe Media Commission.
            </p>
          </div>
        </div>
      </div>

      <div
        className="py-8 md:py-12 px-4 md:px-8"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <div className="max-w-[1000px] mx-auto">
          <div className="bg-white/10 backdrop-blur-sm rounded-2xl p-5 md:p-8 border border-white/20">
            <h2 className="text-white text-lg md:text-2xl font-bold mb-3 flex items-center gap-2">
              <Scale className="w-5 h-5 md:w-6 md:h-6" /> How to Submit a FOIA Appeal
            </h2>
            <p className="text-white/90 text-sm md:text-base mb-4 leading-relaxed">
              Under the <strong>Freedom of Information Act (FOIA)</strong>, you can appeal to the ZMC if a public entity has refused your information request, delayed unreasonably (over 21 days plus a 14-day extension), charged excessive fees, or provided incomplete information.
            </p>
            <ol className="list-decimal pl-5 space-y-1.5 mb-5 text-white/90 text-sm md:text-base">
              <li>Download the FOIA Appeal Form using the button below</li>
              <li>Fill in the form with details of your request and the entity's response</li>
              <li>Click <strong>"Submit Your Appeal"</strong> and provide your details</li>
              <li>Pay the appeal fee securely via <strong>Paynow</strong></li>
            </ol>
            <div className="flex flex-col sm:flex-row gap-3 justify-center items-center">
              <a
                href="/downloads/foia-appeal-form.pdf"
                download
                className="py-3 px-6 rounded-xl font-semibold cursor-pointer flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base w-full sm:w-auto justify-center no-underline"
                style={{ background: "var(--accent)", color: "var(--zim-black)" }}
                data-testid="button-download-appeal-form"
              >
                <Download className="w-5 h-5 flex-shrink-0" /> {t.pages.appeals.downloadForm}
              </a>
              <button
                onClick={() => setShowAppealModal(true)}
                className="py-3 px-6 rounded-xl font-semibold cursor-pointer text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base w-full sm:w-auto justify-center border-2 border-white/40"
                style={{ background: "rgba(255,255,255,0.1)" }}
                data-testid="button-lodge-appeal-top"
              >
                <Scale className="w-5 h-5 flex-shrink-0" /> {t.pages.appeals.submitTitle}
              </button>
            </div>
          </div>
        </div>
      </div>

      {showAppealModal && createPortal(
        <>
          <div
            className="fixed inset-0 z-[2000]"
            style={{ background: "rgba(0,0,0,0.55)", backdropFilter: "blur(4px)" }}
            onClick={closeModal}
          />
          <div className="fixed top-4 bottom-4 left-0 right-0 mx-auto w-[calc(100%-2rem)] max-w-[600px] bg-white rounded-2xl md:rounded-3xl shadow-2xl flex flex-col z-[2001]">
            {step === "done" ? (
              <div className="p-8 md:p-12 text-center flex-1 flex flex-col items-center justify-center">
                <div className="w-16 h-16 md:w-20 md:h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-8 h-8 md:w-10 md:h-10" />
                </div>
                <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>Appeal Submitted!</h3>
                <p className="text-sm md:text-base mb-6" style={{ color: "var(--neutral-600)" }}>
                  Your FOIA appeal has been received. We will review and contact you within 7–14 business days.
                </p>
                <button
                  onClick={closeModal}
                  className="py-2.5 px-6 rounded-xl font-semibold text-white border-none cursor-pointer"
                  style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                >
                  Done
                </button>
              </div>
            ) : (
              <>
                <div className="p-5 md:p-6 rounded-t-2xl md:rounded-t-3xl flex-shrink-0" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}>
                  <h3 className="text-white m-0 text-lg md:text-xl">
                    {step === "details" ? "Submit FOIA Appeal" : "Pay Appeal Fee"}
                  </h3>
                  <p className="text-white/80 text-xs md:text-sm m-0 mt-1">
                    {step === "details"
                      ? "Provide your details so we can process your appeal"
                      : `Complete payment of US$${appeal?.fee?.toFixed(2) ?? "10.00"} to lodge your appeal`}
                  </p>
                </div>

                {step === "details" ? (
                  <form onSubmit={handleDetailsSubmit} className="flex flex-col flex-1 min-h-0">
                    <div className="p-5 md:p-8 overflow-y-auto flex-1">
                      <div className="mb-4">
                        <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Your Full Name *</label>
                        <input type="text" required value={form.fullName} onChange={update("fullName")} placeholder="Enter your full name" className="w-full py-2.5 px-4 rounded-xl text-sm outline-none" style={inputStyle} data-testid="input-appeal-name" />
                      </div>
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                          <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Email Address *</label>
                          <input type="email" required value={form.email} onChange={update("email")} placeholder="you@example.com" className="w-full py-2.5 px-4 rounded-xl text-sm outline-none" style={inputStyle} data-testid="input-appeal-email" />
                        </div>
                        <div>
                          <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Phone Number</label>
                          <input type="tel" value={form.phone} onChange={update("phone")} placeholder="+263..." className="w-full py-2.5 px-4 rounded-xl text-sm outline-none" style={inputStyle} data-testid="input-appeal-phone" />
                        </div>
                      </div>
                      <div className="mb-4">
                        <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Public Entity / Authority *</label>
                        <input type="text" required value={form.entity} onChange={update("entity")} placeholder="Name of the public entity you are appealing against" className="w-full py-2.5 px-4 rounded-xl text-sm outline-none" style={inputStyle} data-testid="input-appeal-entity" />
                      </div>
                      <div className="mb-4">
                        <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Your Request Reference Number</label>
                        <input type="text" value={form.reference} onChange={update("reference")} placeholder="Reference number of your original information request" className="w-full py-2.5 px-4 rounded-xl text-sm outline-none" style={inputStyle} data-testid="input-appeal-reference" />
                      </div>
                      <div className="mb-4">
                        <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Details of Your Appeal *</label>
                        <textarea required rows={5} value={form.details} onChange={update("details")} placeholder="Describe the information you requested, the entity's response, and the grounds for your appeal." className="w-full py-2.5 px-4 rounded-xl text-sm outline-none resize-y" style={inputStyle} data-testid="input-appeal-details" />
                      </div>
                      <div className="mb-2">
                        <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Supporting Documents (optional)</label>
                        <input type="file" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onChange={handleFileChange} className="w-full py-2.5 px-4 rounded-xl text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer" style={inputStyle} data-testid="input-appeal-file-upload" />
                        <p className="text-xs mt-1" style={{ color: "var(--neutral-500)" }}>Accepted: PDF, DOC, DOCX, JPG, PNG (max 10MB each). You may also email documents to {CONTACT_EMAIL}.</p>
                        {fileError && <p className="text-xs mt-1" style={{ color: "#dc2626" }} data-testid="text-file-error-appeal">{fileError}</p>}
                      </div>
                      {formError && (
                        <div className="flex items-center gap-2 text-sm p-3 rounded-xl" style={{ background: "#fef2f2", color: "#b91c1c" }}>
                          <AlertCircle className="w-4 h-4 flex-shrink-0" /> {formError}
                        </div>
                      )}
                    </div>
                    <div className="flex gap-3 justify-end flex-wrap p-4 md:px-8 md:py-4 border-t flex-shrink-0" style={{ borderColor: "var(--neutral-200)", background: "var(--neutral-50)", borderRadius: "0 0 1rem 1rem" }}>
                      <button type="button" onClick={closeModal} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none transition-all text-sm md:text-base cursor-pointer" style={{ background: "var(--neutral-200)" }} data-testid="button-cancel-appeal">Cancel</button>
                      <button type="submit" disabled={isSubmitting} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none text-white transition-all text-sm md:text-base cursor-pointer flex items-center gap-2 disabled:opacity-60" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }} data-testid="button-continue-payment">
                        {isSubmitting ? <Loader2 className="w-4 h-4 animate-spin" /> : null}
                        {isSubmitting ? "Submitting..." : "Continue to Payment"}
                      </button>
                    </div>
                  </form>
                ) : (
                  <div className="flex flex-col flex-1 min-h-0">
                    <div className="p-5 md:p-8 overflow-y-auto flex-1">
                      <div className="p-4 rounded-xl mb-4" style={{ background: "var(--primary-lighter)", border: "1px solid var(--primary-light)" }}>
                        <div className="flex items-center justify-between flex-wrap gap-2">
                          <span className="text-sm font-medium" style={{ color: "var(--neutral-700)" }}>Appeal Fee</span>
                          <span className="text-2xl font-bold" style={{ color: "var(--primary)" }}>US${appeal?.fee?.toFixed(2) ?? "10.00"}</span>
                        </div>
                        {appeal && <p className="text-xs mt-2 mb-0" style={{ color: "var(--neutral-500)" }}>Appeal ID: {appeal.id}</p>}
                      </div>

                      <p className="text-sm mb-4" style={{ color: "var(--neutral-600)" }}>
                        Pay securely using <strong>Paynow</strong> (EcoCash, OneMoney, Telecash, VISA, Mastercard and bank transfer).
                        You will be redirected to the Paynow payment page.
                      </p>

                      <button
                        onClick={handlePaynow}
                        disabled={isPaying}
                        className="w-full py-3.5 px-6 rounded-xl font-semibold text-white border-none cursor-pointer flex items-center justify-center gap-2 disabled:opacity-60 text-sm md:text-base"
                        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                        data-testid="button-pay-paynow"
                      >
                        {isPaying ? <Loader2 className="w-5 h-5 animate-spin" /> : <CreditCard className="w-5 h-5" />}
                        {isPaying ? "Starting payment..." : `Pay US$${appeal?.fee?.toFixed(2) ?? "10.00"} with Paynow`}
                      </button>

                      {paymentError && (
                        <div className="mt-4 p-3 rounded-xl flex items-start gap-2 text-sm" style={{ background: "#fef3c7", color: "#92400e" }}>
                          <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                          <span data-testid="text-payment-error">{paymentError}</span>
                        </div>
                      )}

                      <div className="mt-6 pt-4 border-t" style={{ borderColor: "var(--neutral-100)" }}>
                        <p className="text-xs" style={{ color: "var(--neutral-500)" }}>
                          Prefer to pay later? You can continue and we will contact you with payment options. Your appeal will be
                          held until payment is received.
                        </p>
                        <button
                          onClick={() => setStep("done")}
                          className="mt-3 text-sm font-semibold bg-transparent border-none cursor-pointer flex items-center gap-1.5"
                          style={{ color: "var(--primary)" }}
                          data-testid="button-skip-payment"
                        >
                          Continue without paying now <ExternalLink className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                    <div className="flex gap-3 justify-end flex-wrap p-4 md:px-8 md:py-4 border-t flex-shrink-0" style={{ borderColor: "var(--neutral-200)", background: "var(--neutral-50)", borderRadius: "0 0 1rem 1rem" }}>
                      <button type="button" onClick={() => setStep("done")} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none text-white transition-all text-sm md:text-base cursor-pointer" style={{ background: "var(--neutral-400)" }} data-testid="button-close-appeal">Close</button>
                    </div>
                  </div>
                )}
              </>
            )}
          </div>
        </>,
        document.body
      )}
    </div>
  );
}
