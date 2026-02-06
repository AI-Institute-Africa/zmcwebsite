import { useState, useEffect } from "react";
import { Scale, CheckCircle, Download, FilePen } from "lucide-react";

interface AppealsPageProps {
  onNavigate: (page: string) => void;
}

export default function AppealsPage({ onNavigate }: AppealsPageProps) {
  const [showAppealModal, setShowAppealModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);
  const [fileError, setFileError] = useState("");

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
    setFormSubmitted(true);
    setTimeout(() => {
      setShowAppealModal(false);
      setFormSubmitted(false);
    }, 3000);
  };

  const appealReasons = [
    "Public entity refused to provide requested information",
    "Unreasonable delay in providing information (over 21 days)",
    "Excessive fees charged for information access",
    "Information provided is incomplete or inadequate",
    "Improper application of exemptions to disclosure",
  ];

  const processSteps = [
    { number: 1, title: "Submission", desc: "Your appeal is received and logged" },
    { number: 2, title: "Review", desc: "Commission reviews the appeal" },
    { number: 3, title: "Investigation", desc: "Public entity is contacted" },
    { number: 4, title: "Decision", desc: "Final decision is communicated" },
  ];

  return (
    <div className="animate-fadeIn pt-[100px] md:pt-[130px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Appeals (FOIA)</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Appeal denied information access under the Freedom of Information Act
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-home">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Appeals</span>
        </div>
      </div>

      {/* Top Action Buttons */}
      <div className="py-6 md:py-8 px-4 md:px-8" style={{ background: "var(--blue-light, #dbeafe)" }}>
        <div className="max-w-[1000px] mx-auto flex flex-col sm:flex-row gap-4 justify-center items-center">
          <button
            onClick={() => setShowAppealModal(true)}
            className="py-3 px-8 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base w-full sm:w-auto justify-center"
            style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }}
            data-testid="button-lodge-appeal-top"
          >
            <Scale className="w-5 h-5 flex-shrink-0" /> Lodge an Appeal
          </button>
          <button
            onClick={() => onNavigate("complaints")}
            className="py-3 px-8 rounded-xl font-semibold cursor-pointer flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base w-full sm:w-auto justify-center"
            style={{ background: "transparent", border: "2px solid var(--primary)", color: "var(--primary)" }}
            data-testid="button-go-to-complaints"
          >
            <FilePen className="w-5 h-5 flex-shrink-0" /> Go to Complaints
          </button>
        </div>
      </div>

      {/* Content */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* About Appeals */}
          <div className="bg-white rounded-xl md:rounded-[20px] p-6 md:p-10 mb-6 md:mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 className="text-xl md:text-2xl" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>About Appeals (FOIA)</h2>
            <p className="leading-relaxed text-sm md:text-base">
              Under the Freedom of Information Act (FOIA), the Zimbabwe Media Commission is required to review decisions 
              relating to access to information held by public entities. The Commission receives appeals from members of the 
              public who are denied access to requested information from government and public bodies.
            </p>
            <h3 className="mt-6 mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>When Can You Appeal?</h3>
            <ul className="list-none pl-0">
              {appealReasons.map((reason, index) => (
                <li key={index} className="relative pl-6 mb-2 text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>
                  <span className="absolute left-0 font-bold" style={{ color: "var(--primary)" }}>&#8226;</span>
                  {reason}
                </li>
              ))}
            </ul>
            <div className="flex gap-3 md:gap-4 flex-wrap mt-6">
              <button 
                onClick={() => setShowAppealModal(true)} 
                className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base" 
                style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }} 
                data-testid="button-appeal-online"
              >
                <Scale className="w-4 h-4 flex-shrink-0" /> Lodge Appeal Online
              </button>
              <button 
                onClick={() => onNavigate("downloads")} 
                className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold cursor-pointer flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base" 
                style={{ background: "transparent", border: "2px solid var(--primary)", color: "var(--primary)" }} 
                data-testid="button-download-appeal-form"
              >
                <Download className="w-4 h-4 flex-shrink-0" /> Download Form
              </button>
            </div>
          </div>

          {/* Process Timeline */}
          <div className="bg-white rounded-xl md:rounded-[20px] p-6 md:p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 className="text-xl md:text-2xl" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>What Happens After Submission?</h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mt-6 md:mt-8">
              {processSteps.map((step, index) => (
                <div key={index} className="text-center p-4 md:p-6">
                  <div className="w-12 h-12 md:w-[60px] md:h-[60px] rounded-full mx-auto mb-3 md:mb-4 flex items-center justify-center text-lg md:text-xl font-bold" style={{ background: index === 3 ? "var(--accent-soft)" : "var(--blue-light, #dbeafe)", color: index === 3 ? "var(--accent-dark)" : "var(--blue, #2563eb)" }}>
                    {step.number}
                  </div>
                  <h4 className="text-base md:text-lg mb-2" style={{ fontFamily: "var(--font-serif)" }}>{step.title}</h4>
                  <p className="text-xs md:text-[0.9rem] m-0" style={{ color: "var(--neutral-600)" }}>{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Appeal Modal */}
      {showAppealModal && (
        <div className="modal-overlay fixed inset-0 flex items-center justify-center z-[2000] p-4" style={{ background: "rgba(0,0,0,0.45)" }} onClick={() => setShowAppealModal(false)}>
          <div className="modal-content bg-white rounded-2xl md:rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            {formSubmitted ? (
              <div className="p-8 md:p-12 text-center">
                <div className="w-16 h-16 md:w-20 md:h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-8 h-8 md:w-10 md:h-10" />
                </div>
                <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>Appeal Submitted!</h3>
                <p className="text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>Your FOIA appeal has been received. We will review and contact you within 7-14 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-5 md:p-6" style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }}>
                  <h3 className="text-white m-0 text-lg md:text-xl">Lodge an Appeal (FOIA)</h3>
                  <p className="text-white/80 text-xs md:text-sm m-0 mt-1">Appeal denied access to information from a public entity</p>
                </div>
                <form onSubmit={handleSubmit} className="p-5 md:p-8">
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Your Full Name *</label>
                    <input type="text" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-appeal-name" />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Email *</label>
                      <input type="email" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-appeal-email" />
                    </div>
                    <div>
                      <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Phone *</label>
                      <input type="tel" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-appeal-phone" />
                    </div>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Public Entity Name *</label>
                    <input type="text" required placeholder="e.g., Ministry of Health, Harare City Council" className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-appeal-entity" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Date of Original Request *</label>
                    <input type="date" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-appeal-date" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Reason for Appeal *</label>
                    <select required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="select-appeal-reason">
                      <option value="">Select reason</option>
                      <option value="refusal">Information request was refused</option>
                      <option value="delay">Unreasonable delay (over 21 days)</option>
                      <option value="fees">Excessive fees charged</option>
                      <option value="incomplete">Incomplete information provided</option>
                      <option value="exemption">Improper exemption applied</option>
                    </select>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Appeal Details *</label>
                    <textarea required rows={4} placeholder="Describe the information you requested and why you believe the decision should be reviewed..." className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="textarea-appeal-details" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Upload Appeal Application Form & Documents</label>
                    <div className="relative">
                      <input 
                        type="file" 
                        multiple 
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                        onChange={handleFileChange}
                        className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer" 
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} 
                        data-testid="input-appeal-file-upload"
                      />
                    </div>
                    <p className="text-xs mt-1" style={{ color: "var(--neutral-500)" }}>Accepted: PDF, DOC, DOCX, JPG, PNG (max 10MB each)</p>
                    {fileError && <p className="text-xs mt-1" style={{ color: "#dc2626" }} data-testid="text-file-error-appeal">{fileError}</p>}
                  </div>
                  <div className="flex gap-3 justify-end flex-wrap">
                    <button type="button" onClick={() => setShowAppealModal(false)} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none transition-all text-sm md:text-base cursor-pointer" style={{ background: "var(--neutral-200)" }} data-testid="button-cancel-appeal">Cancel</button>
                    <button type="submit" className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none text-white transition-all text-sm md:text-base cursor-pointer" style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }} data-testid="button-submit-appeal">Submit Appeal</button>
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
