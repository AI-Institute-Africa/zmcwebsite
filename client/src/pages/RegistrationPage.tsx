import { useState, useEffect } from "react";
import { Building2, FileText, CheckCircle, X, ExternalLink } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";

interface RegistrationPageProps {
  onNavigate: (page: string) => void;
}

// Portal URL for media registration
const PORTAL_URL = "https://zmc--portal.replit.app/";

export default function RegistrationPage({ onNavigate }: RegistrationPageProps) {
  const { t } = useLanguage();
  const [showModal, setShowModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);

  useEffect(() => {
    if (showModal) {
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
  }, [showModal]);
  
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
    { number: 2, title: "Complete Form", desc: "Fill out the registration form" },
    { number: 3, title: "Submit & Pay", desc: "Submit application and pay the application fee" },
    { number: 4, title: "Confirm & Pay", desc: "Confirm your approval and proceed to pay the registration fee" },
    { number: 5, title: "Get Registered", desc: "Receive your registration certificate" },
  ];

  const localRequirements = [
    "Projected cash flow statement for three years",
    "Projected balance sheet for three years",
    "Editorial charter",
    "Code of ethics",
    "Code of conduct for employees",
    "Market analysis",
    "Certified IDs for directors",
    "In-house style book",
    "Attach dummy (copy of publication)",
    "Attach mission statement",
    "Attach certificate of incorporation",
    "Attach memorandum of association",
    "The application and registration fee as prescribed by Statutory Instrument 65 of 2022",
  ];

  const foreignRequirements = [
    "Clearance to operate a representative office from the Ministry of Information, Publicity and Broadcasting Services",
    "Proof of registration in home country",
    "Names and certified copies of IDs of people running the representative office",
    "Contact details (Physical address, email and telephone)",
    "The application and registration fee as prescribed by Statutory Instrument 65 of 2022",
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
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.registration.title}
        subtitle={t.pages.registration.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Registration" }]}
      />

      {/* Content */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* Description */}
          <div className="bg-white rounded-[20px] p-6 md:p-10 mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
              Registration of Mass Media Services
            </h2>
            <p className="leading-relaxed text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              Registration of mass media services is provided for by Statutory Instrument 169C of 2002 as read with Statutory Instrument 10 of 2004. The Statutory Instrument prescribes 
              the manner and form in which an individual can lodge an application for registration with the Zimbabwe Media Commission. 
              Statutory Instrument 65 of 2022 prescribes the fees payable for registration.
            </p>
          </div>

          {/* Requirements - Local */}
          <div className="rounded-2xl p-6 md:p-8 mb-6" style={{ background: "var(--primary-soft)", borderLeft: "4px solid var(--primary)" }}>
            <h3 className="flex items-center gap-2 mb-4" style={{ color: "var(--primary)" }}>
              <FileText className="w-6 h-6" />
              Requirements - Local Media Organizations
            </h3>
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
              Requirements - Representative Office of Foreign Mass Media Service
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

          {/* Fees Note */}
          <div className="rounded-2xl p-6 mb-8" style={{ background: "var(--accent-soft)" }}>
            <p className="text-sm md:text-base m-0" style={{ color: "var(--neutral-700)" }}>
              {t.pages.registration.nbNote}
            </p>
          </div>

          {/* Process Steps */}
          <div className="bg-white rounded-[20px] p-10 mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>Registration Process</h2>
            <div className="grid grid-cols-2 md:grid-cols-5 gap-6 mt-8">
              {steps.map((step) => (
                <div key={step.number} className="text-center p-6 rounded-2xl transition-all hover:-translate-y-1 hover:border-[var(--primary)]" style={{ border: "2px solid var(--neutral-200)" }}>
                  <div className="w-12 h-12 rounded-full mx-auto mb-4 flex items-center justify-center text-xl font-bold text-white" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}>
                    {step.number}
                  </div>
                  <h4 className="text-lg mb-2" style={{ color: "var(--neutral-800)" }}>{step.title}</h4>
                  <p className="text-[0.9rem] m-0" style={{ color: "var(--neutral-500)" }}>{step.desc}</p>
                </div>
              ))}
            </div>
          </div>

          {/* Quick Register Box */}
          <div
            className="rounded-3xl p-8"
            style={{ background: "linear-gradient(135deg, var(--primary-lighter) 0%, var(--accent-soft) 100%)", border: "2px solid var(--primary-light)" }}
          >
            <h2 className="text-center mb-6" style={{ color: "var(--primary-dark)" }}>
              <Building2 className="w-7 h-7 inline mr-2" />
              Register Your Media Service
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div
                className="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all hover:-translate-y-1"
                style={{ boxShadow: "0 4px 15px rgba(0,0,0,0.08)" }}
                onClick={openPortal}
              >
                <div className="w-[70px] h-[70px] rounded-full mx-auto mb-4 flex items-center justify-center" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                  <Building2 className="w-8 h-8" />
                </div>
                <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>New Registration</h3>
                <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>Register a new media service</p>
                <button
                  className="w-full py-3 rounded-xl font-semibold text-white border-none cursor-pointer flex items-center justify-center gap-2 transition-transform"
                  style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                  data-testid="button-new-registration"
                >
                  Register Now <ExternalLink className="w-4 h-4" />
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
                <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>Renew Registration</h3>
                <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>Renew existing registration</p>
                <button
                  className="w-full py-3 rounded-xl font-bold border-none cursor-pointer flex items-center justify-center gap-2 transition-transform"
                  style={{ background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)", color: "var(--zim-black)" }}
                  data-testid="button-renew-registration"
                >
                  Renew Now <ExternalLink className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Modal */}
      {showModal && (
        <div
          className="modal-overlay fixed inset-0 flex items-center justify-center z-[2000] p-4"
          style={{ background: "rgba(0,0,0,0.45)" }}
          onClick={() => setShowModal(false)}
        >
          <div
            className="modal-content bg-white rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            {formSubmitted ? (
              <div className="p-12 text-center">
                <div className="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-10 h-10" />
                </div>
                <h3 className="mb-4" style={{ color: "var(--primary)" }}>Application Submitted!</h3>
                <p style={{ color: "var(--neutral-600)" }}>Your registration application has been received. We will contact you within 5-7 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-7 flex justify-between items-center" style={{ borderBottom: "1px solid var(--neutral-200)" }}>
                  <h3 style={{ color: "var(--primary)" }}>Media Registration Application</h3>
                  <button onClick={() => setShowModal(false)} className="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer transition-all border-none" style={{ background: "var(--neutral-100)" }} data-testid="button-close-reg-modal">
                    <X className="w-5 h-5" />
                  </button>
                </div>
                <form onSubmit={handleSubmit} className="p-8">
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Company/Media Name *</label>
                    <input type="text" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-company-name" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Media Type *</label>
                    <select required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="select-media-type">
                      <option value="">Select type...</option>
                      <option value="newspaper">Newspaper</option>
                      <option value="magazine">Magazine</option>
                      <option value="radio">Radio Station</option>
                      <option value="tv">Television Station</option>
                      <option value="online">Online Publication</option>
                      <option value="news-agency">News Agency</option>
                    </select>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Contact Person *</label>
                    <input type="text" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-contact-person" />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Email *</label>
                      <input type="email" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-reg-email" />
                    </div>
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Phone *</label>
                      <input type="tel" required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-reg-phone" />
                    </div>
                  </div>
                  <div className="mb-6">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Physical Address *</label>
                    <textarea required className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none min-h-[100px]" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-address" />
                  </div>
                  <div className="flex justify-between items-center flex-wrap gap-4" style={{ borderTop: "1px solid var(--neutral-200)", paddingTop: "1.25rem" }}>
                    <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("downloads"); setShowModal(false); }} className="text-[0.85rem] font-semibold" style={{ color: "var(--primary)" }}>Download PDF Form</a>
                    <div className="flex gap-3">
                      <button type="button" onClick={() => setShowModal(false)} className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer" style={{ background: "var(--neutral-200)", color: "var(--neutral-700)" }}>Cancel</button>
                      <button type="submit" className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }} data-testid="button-submit-registration">Submit Application</button>
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
