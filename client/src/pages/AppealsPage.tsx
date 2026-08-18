import { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { Scale, CheckCircle, Download, CreditCard } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";

interface AppealsPageProps {
  onNavigate: (page: string) => void;
}

export default function AppealsPage({ onNavigate }: AppealsPageProps) {
  const { t } = useLanguage();
  const [showAppealModal, setShowAppealModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);
  const [fileError, setFileError] = useState("");
  const [paymentRef, setPaymentRef] = useState("");
  const [paymentError, setPaymentError] = useState("");

  const MAX_FILE_SIZE = 10 * 1024 * 1024;

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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (fileError) return;
    if (!paymentRef.trim()) {
      setPaymentError("Please enter your Pay Now payment reference before submitting.");
      return;
    }
    setPaymentError("");
    setFormSubmitted(true);
    setTimeout(() => {
      setShowAppealModal(false);
      setFormSubmitted(false);
      setPaymentRef("");
    }, 3000);
  };

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.appeals.title}
        subtitle={t.pages.appeals.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "FOIA Appeals" }]}
      />

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
              <li>Make payment via <strong>Pay Now</strong> and note your payment reference number</li>
              <li>Click <strong>"Submit Your Appeal"</strong> below, upload your form and supporting documents, and enter your Pay Now reference</li>
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
          onClick={() => setShowAppealModal(false)}
        />
        <div
          className="fixed top-4 bottom-4 left-0 right-0 mx-auto w-[calc(100%-2rem)] max-w-[600px] bg-white rounded-2xl md:rounded-3xl shadow-2xl flex flex-col z-[2001]"
        >
            {formSubmitted ? (
              <div className="p-8 md:p-12 text-center flex-1 flex flex-col items-center justify-center">
                <div className="w-16 h-16 md:w-20 md:h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-8 h-8 md:w-10 md:h-10" />
                </div>
                <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>Appeal Submitted!</h3>
                <p className="text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>Your FOIA appeal has been received. We will review and contact you within 7–14 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-5 md:p-6 rounded-t-2xl md:rounded-t-3xl flex-shrink-0" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}>
                  <h3 className="text-white m-0 text-lg md:text-xl">Submit FOIA Appeal</h3>
                  <p className="text-white/80 text-xs md:text-sm m-0 mt-1">Upload your completed appeal form, supporting documents, and enter your payment reference</p>
                </div>
                <form onSubmit={handleSubmit} className="flex flex-col flex-1 min-h-0">
                  <div className="p-5 md:p-8 overflow-y-auto flex-1">
                    <div className="mb-4">
                      <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Your Full Name *</label>
                      <input
                        type="text"
                        required
                        placeholder="Enter your full name"
                        className="w-full py-2.5 px-4 rounded-xl text-sm outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-appeal-name"
                      />
                    </div>
                    <div className="mb-4">
                      <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Email Address *</label>
                      <input
                        type="email"
                        required
                        placeholder="Enter your email address"
                        className="w-full py-2.5 px-4 rounded-xl text-sm outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-appeal-email"
                      />
                    </div>
                    <div className="mb-4">
                      <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Upload Completed Appeal Form & Supporting Documents *</label>
                      <input
                        type="file"
                        multiple
                        required
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        onChange={handleFileChange}
                        className="w-full py-2.5 px-4 rounded-xl text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-appeal-file-upload"
                      />
                      <p className="text-xs mt-1" style={{ color: "var(--neutral-500)" }}>Accepted: PDF, DOC, DOCX, JPG, PNG (max 10MB each). Include your appeal form, original request letter, and the entity's denial letter.</p>
                      {fileError && <p className="text-xs mt-1" style={{ color: "#dc2626" }} data-testid="text-file-error-appeal">{fileError}</p>}
                    </div>

                    <div className="mb-2 p-4 rounded-xl" style={{ background: "var(--primary-lighter)", border: "1px solid var(--primary-light)" }}>
                      <div className="flex items-center gap-2 mb-3">
                        <CreditCard className="w-5 h-5 flex-shrink-0" style={{ color: "var(--primary)" }} />
                        <label className="font-semibold text-sm" style={{ color: "var(--primary)" }}>Pay Now Payment Reference *</label>
                      </div>
                      <p className="text-xs mb-3" style={{ color: "var(--neutral-600)" }}>
                        Payment must be made via <strong>Pay Now</strong> before submitting your appeal. Enter the payment reference number from your Pay Now receipt below.
                      </p>
                      <input
                        type="text"
                        value={paymentRef}
                        onChange={(e) => { setPaymentRef(e.target.value); setPaymentError(""); }}
                        placeholder="e.g. PAYNOW-2026-XXXXXXXX"
                        className="w-full py-2.5 px-4 rounded-xl text-sm outline-none"
                        style={{ border: "2px solid var(--primary-light)", background: "white" }}
                        data-testid="input-payment-reference"
                      />
                      {paymentError && <p className="text-xs mt-1.5" style={{ color: "#dc2626" }} data-testid="text-payment-error">{paymentError}</p>}
                    </div>
                  </div>

                  <div className="flex gap-3 justify-end flex-wrap p-4 md:px-8 md:py-4 border-t flex-shrink-0" style={{ borderColor: "var(--neutral-200)", background: "var(--neutral-50)", borderRadius: "0 0 1rem 1rem" }}>
                    <button type="button" onClick={() => setShowAppealModal(false)} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none transition-all text-sm md:text-base cursor-pointer" style={{ background: "var(--neutral-200)" }} data-testid="button-cancel-appeal">Cancel</button>
                    <button type="submit" className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none text-white transition-all text-sm md:text-base cursor-pointer" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }} data-testid="button-submit-appeal">Submit Appeal</button>
                  </div>
                </form>
              </>
            )}
          </div>
        </>,
        document.body
      )}
    </div>
  );
}
