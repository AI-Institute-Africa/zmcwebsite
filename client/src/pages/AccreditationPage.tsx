import { useState } from "react";
import { BadgeCheck, FileText, Camera, Briefcase, CreditCard, CheckCircle, X, ExternalLink } from "lucide-react";

interface AccreditationPageProps {
  onNavigate: (page: string) => void;
}

// Portal URL for accreditation applications
const PORTAL_URL = "https://f17c25d1-8d60-4751-b64c-aadbdeaf0836-00-mtsfdj8ol3sm.worf.replit.dev/";

export default function AccreditationPage({ onNavigate }: AccreditationPageProps) {
  const [showModal, setShowModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);
  
  const openPortal = () => {
    if (PORTAL_URL && PORTAL_URL !== "#") {
      window.open(PORTAL_URL, "_blank", "noopener,noreferrer");
    } else {
      // Fallback to modal if portal URL not yet set
      setShowModal(true);
    }
  };

  const steps = [
    { number: 1, title: "Gather Documents", desc: "Prepare all required documents" },
    { number: 2, title: "Complete Form", desc: "Fill out the application form" },
    { number: 3, title: "Submit & Pay", desc: "Submit application and pay fees" },
    { number: 4, title: "Get Accredited", desc: "Receive your accreditation card" },
  ];

  const localRequirements = [
    "Certified copy of National Identity card",
    "One passport sized photograph",
    "Certified copy of professional and academic qualifications or sample of past work done",
    "Where certificates are not available, applicant must submit five samples of past work done",
    "Any other relevant reference materials such as a stamped letter from the employer or media house or professional association, testimonial, and/or referral from a media organisation/institution",
    "An accreditation fee as prescribed",
  ];

  const foreignRequirements = [
    "Copy of a valid passport",
    "One passport size photograph",
    "Temporary Employment Permit (TEP) / Clearance from the Ministry of Information, Publicity and Broadcasting Services",
    "An accreditation fee as prescribed",
  ];

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setFormSubmitted(true);
    setTimeout(() => {
      setShowModal(false);
      setFormSubmitted(false);
    }, 3000);
  };

  return (
    <div className="animate-fadeIn pt-[130px]">
      {/* Page Header */}
      <div
        className="py-16 px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <div className="absolute top-0 right-0 w-1/2 h-full" style={{ background: "radial-gradient(circle at 70% 50%, rgba(255,255,255,0.1) 0%, transparent 50%)" }} />
        <h1 className="text-white mb-3 relative">Media Accreditation</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-lg relative">
          Get accredited as a media practitioner
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem]">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Accreditation</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-16 px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* Quick Apply Box */}
          <div
            className="rounded-3xl p-8 mb-8"
            style={{ background: "linear-gradient(135deg, var(--primary-lighter) 0%, var(--accent-soft) 100%)", border: "2px solid var(--primary-light)" }}
          >
            <h2 className="text-center mb-6" style={{ color: "var(--primary-dark)" }}>
              <BadgeCheck className="w-7 h-7 inline mr-2" />
              Apply for Accreditation
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div
                className="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all hover:-translate-y-1"
                style={{ boxShadow: "0 4px 15px rgba(0,0,0,0.08)" }}
                onClick={openPortal}
              >
                <div className="w-[70px] h-[70px] rounded-full mx-auto mb-4 flex items-center justify-center" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                  <BadgeCheck className="w-8 h-8" />
                </div>
                <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>New Accreditation</h3>
                <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>Apply for first-time accreditation</p>
                <button
                  className="w-full py-3 rounded-xl font-semibold text-white border-none cursor-pointer flex items-center justify-center gap-2 active:scale-95 transition-transform"
                  style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                  data-testid="button-apply-accreditation"
                >
                  Apply Now <ExternalLink className="w-4 h-4" />
                </button>
              </div>
              <div
                className="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all hover:-translate-y-1"
                style={{ boxShadow: "0 4px 15px rgba(0,0,0,0.08)" }}
                onClick={openPortal}
              >
                <div className="w-[70px] h-[70px] rounded-full mx-auto mb-4 flex items-center justify-center" style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}>
                  <FileText className="w-8 h-8" />
                </div>
                <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>Renew Accreditation</h3>
                <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>Renew your existing accreditation</p>
                <button
                  className="w-full py-3 rounded-xl font-bold border-none cursor-pointer flex items-center justify-center gap-2 active:scale-95 transition-transform"
                  style={{ background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)", color: "var(--zim-black)" }}
                  data-testid="button-renew-accreditation"
                >
                  Renew Now <ExternalLink className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          {/* Process Steps */}
          <div className="bg-white rounded-[20px] p-10 mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>Application Process</h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
              {steps.map((step) => (
                <div key={step.number} className="text-center p-6 rounded-2xl transition-all hover:-translate-y-1 hover:border-[var(--primary)]" style={{ border: "2px solid var(--neutral-200)" }}>
                  <div className="w-12 h-12 rounded-full mx-auto mb-4 flex items-center justify-center text-xl font-bold text-white" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}>
                    {step.number}
                  </div>
                  <h4 className="text-lg mb-2" style={{ fontFamily: "var(--font-serif)", color: "var(--neutral-800)" }}>{step.title}</h4>
                  <p className="text-[0.9rem] m-0" style={{ color: "var(--neutral-500)" }}>{step.desc}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Description */}
          <div className="bg-white rounded-[20px] p-6 md:p-10 mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
              Accreditation of Journalists and Media Practitioners
            </h2>
            <p className="leading-relaxed text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              Accreditation of journalists and media practitioners employed in the media is provided for by Statutory 169C of 2002. 
              The Statutory Instrument prescribes the manner and form in which an individual can lodge an application for accreditation 
              with the Zimbabwe Media Commission. Statutory Instrument 65 of 2022 prescribes the fees payable for accreditation.
            </p>
          </div>

          {/* Requirements - Local */}
          <div className="rounded-2xl p-6 md:p-8 mb-6" style={{ background: "var(--primary-soft)", borderLeft: "4px solid var(--primary)" }}>
            <h3 className="flex items-center gap-2 mb-4" style={{ color: "var(--primary)" }}>
              <FileText className="w-6 h-6" />
              Requirements - Local Media Practitioners
            </h3>
            <p className="text-sm mb-4" style={{ color: "var(--neutral-600)" }}>
              An application form for accreditation must be accompanied by the following:
            </p>
            <ul className="list-none pl-4 md:pl-6">
              {localRequirements.map((req, index) => (
                <li key={index} className="relative pl-6 mb-3 text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>
                  <span className="absolute left-0 font-bold text-lg" style={{ color: "var(--primary)" }}>•</span>
                  {req}
                </li>
              ))}
            </ul>
          </div>

          {/* Requirements - Foreign */}
          <div className="rounded-2xl p-6 md:p-8 mb-8" style={{ background: "var(--accent-soft)", borderLeft: "4px solid var(--accent)" }}>
            <h3 className="flex items-center gap-2 mb-4" style={{ color: "var(--accent-dark)" }}>
              <FileText className="w-6 h-6" />
              Requirements - Foreign Media Practitioners
            </h3>
            <ul className="list-none pl-4 md:pl-6">
              {foreignRequirements.map((req, index) => (
                <li key={index} className="relative pl-6 mb-3 text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>
                  <span className="absolute left-0 font-bold text-lg" style={{ color: "var(--accent-dark)" }}>•</span>
                  {req}
                </li>
              ))}
            </ul>
          </div>

          {/* Fees */}
          <div className="rounded-2xl p-6 flex flex-wrap items-center justify-between gap-4" style={{ background: "var(--accent-soft)" }}>
            <div>
              <h4 className="text-base font-semibold mb-2" style={{ color: "var(--neutral-700)", fontFamily: "var(--font-sans)" }}>Application Fee</h4>
              <span className="text-2xl font-bold whitespace-nowrap" style={{ fontFamily: "var(--font-serif)", color: "var(--accent-dark)" }}>USD&nbsp;$50.00</span>
            </div>
            <button
              onClick={() => onNavigate("downloads")}
              className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer"
              style={{ background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)", color: "var(--zim-black)" }}
              data-testid="button-view-fees"
            >
              View Full Fee Schedule
            </button>
          </div>
        </div>
      </div>

      {/* Modal */}
      {showModal && (
        <div
          className="fixed inset-0 flex items-center justify-center z-[2000] p-4"
          style={{ background: "rgba(0,0,0,0.5)" }}
          onClick={() => setShowModal(false)}
        >
          <div
            className="bg-white rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-y-auto"
            style={{ animation: "modalFadeIn 0.5s ease" }}
            onClick={(e) => e.stopPropagation()}
          >
            {formSubmitted ? (
              <div className="p-12 text-center">
                <div className="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-10 h-10" />
                </div>
                <h3 className="mb-4" style={{ color: "var(--primary)" }}>Application Submitted!</h3>
                <p style={{ color: "var(--neutral-600)" }}>Your accreditation application has been received. We will contact you within 5-7 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-7 flex justify-between items-center" style={{ borderBottom: "1px solid var(--neutral-200)" }}>
                  <h3 style={{ color: "var(--primary)" }}>Accreditation Application</h3>
                  <button onClick={() => setShowModal(false)} className="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer transition-all border-none" style={{ background: "var(--neutral-100)" }} data-testid="button-close-modal">
                    <X className="w-5 h-5" />
                  </button>
                </div>
                <form onSubmit={handleSubmit} className="p-8">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>First Name *</label>
                      <input type="text" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-first-name" />
                    </div>
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Last Name *</label>
                      <input type="text" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-last-name" />
                    </div>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Email Address *</label>
                    <input type="email" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-email" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Phone Number *</label>
                    <input type="tel" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-phone" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Media Category *</label>
                    <select required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="select-category">
                      <option value="">Select category...</option>
                      <option value="print">Print Journalist</option>
                      <option value="broadcast">Broadcast Journalist</option>
                      <option value="digital">Digital/Online Journalist</option>
                      <option value="photographer">Photojournalist</option>
                      <option value="cameraman">Cameraman</option>
                    </select>
                  </div>
                  <div className="mb-6">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Current Employer</label>
                    <input type="text" className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-employer" />
                  </div>
                  <div className="flex justify-between items-center flex-wrap gap-4" style={{ borderTop: "1px solid var(--neutral-200)", paddingTop: "1.25rem" }}>
                    <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("downloads"); setShowModal(false); }} className="text-[0.85rem] font-semibold" style={{ color: "var(--primary)" }}>Download PDF Form</a>
                    <div className="flex gap-3">
                      <button type="button" onClick={() => setShowModal(false)} className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer" style={{ background: "var(--neutral-200)", color: "var(--neutral-700)" }}>Cancel</button>
                      <button type="submit" className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }} data-testid="button-submit-application">Submit Application</button>
                    </div>
                  </div>
                </form>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
