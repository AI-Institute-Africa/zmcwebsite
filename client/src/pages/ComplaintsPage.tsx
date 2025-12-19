import { useState } from "react";
import { ClipboardList, FilePen, Scale, CheckCircle, X, Download } from "lucide-react";

interface ComplaintsPageProps {
  onNavigate: (page: string) => void;
}

export default function ComplaintsPage({ onNavigate }: ComplaintsPageProps) {
  const [showComplaintModal, setShowComplaintModal] = useState(false);
  const [showAppealModal, setShowAppealModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setFormSubmitted(true);
    setTimeout(() => {
      setShowComplaintModal(false);
      setShowAppealModal(false);
      setFormSubmitted(false);
    }, 3000);
  };

  const complaintReasons = [
    "Breach of media ethics and professional standards",
    "Defamation or invasion of privacy",
    "Biased or inaccurate reporting",
    "Unprofessional conduct by journalists",
    "Violation of media laws",
  ];

  const appealReasons = [
    "When a public entity refuses to provide requested information",
    "When there is unreasonable delay in providing information",
    "When you believe the fees charged are excessive",
    "When information provided is incomplete",
  ];

  const processSteps = [
    { number: 1, title: "Submission", desc: "Your complaint/appeal is received and logged" },
    { number: 2, title: "Review", desc: "Commission reviews the matter" },
    { number: 3, title: "Investigation", desc: "Parties are contacted for information" },
    { number: 4, title: "Decision", desc: "Final decision is communicated" },
  ];

  return (
    <div className="animate-fadeIn pt-[130px]">
      {/* Page Header */}
      <div
        className="py-16 px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative">Complaints & Appeals</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-lg relative">
          Lodge complaints or appeals with ZMC
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem]">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Complaints & Appeals</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-16 px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* Quick Actions */}
          <div
            className="rounded-3xl p-8 mb-8"
            style={{ background: "linear-gradient(135deg, var(--primary-lighter) 0%, var(--accent-soft) 100%)", border: "2px solid var(--primary-light)" }}
          >
            <h2 className="text-center mb-6" style={{ color: "var(--primary-dark)" }}>
              <ClipboardList className="w-7 h-7 inline mr-2" />
              Submit a Complaint or Appeal
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div
                className="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all hover:-translate-y-1"
                style={{ boxShadow: "0 4px 15px rgba(0,0,0,0.08)" }}
                onClick={() => setShowComplaintModal(true)}
              >
                <div className="w-[70px] h-[70px] rounded-full mx-auto mb-4 flex items-center justify-center" style={{ background: "var(--red-light)", color: "var(--red)" }}>
                  <FilePen className="w-8 h-8" />
                </div>
                <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>Lodge a Complaint</h3>
                <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>Report media law or ethics breaches</p>
                <button
                  className="w-full py-3 rounded-xl font-semibold text-white border-none cursor-pointer"
                  style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}
                  data-testid="button-lodge-complaint"
                >
                  Start Complaint
                </button>
              </div>
              <div
                className="bg-white rounded-2xl p-6 text-center cursor-pointer transition-all hover:-translate-y-1"
                style={{ boxShadow: "0 4px 15px rgba(0,0,0,0.08)" }}
                onClick={() => setShowAppealModal(true)}
              >
                <div className="w-[70px] h-[70px] rounded-full mx-auto mb-4 flex items-center justify-center" style={{ background: "var(--blue-light)", color: "var(--blue)" }}>
                  <Scale className="w-8 h-8" />
                </div>
                <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>Lodge an Appeal</h3>
                <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>Appeal denied information access (FOIA)</p>
                <button
                  className="w-full py-3 rounded-xl font-semibold text-white border-none cursor-pointer"
                  style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }}
                  data-testid="button-lodge-appeal"
                >
                  Start Appeal
                </button>
              </div>
            </div>
          </div>

          {/* About Complaints */}
          <div className="bg-white rounded-[20px] p-10 mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>About Complaints</h2>
            <p className="leading-relaxed">
              The Commission receives complaints against media practitioners and media organizations that would have breached media laws and ethics. Individuals are encouraged to lodge a formal complaint in writing.
            </p>
            <h3 className="mt-6 mb-4" style={{ color: "var(--primary)" }}>What Can You Complain About?</h3>
            <ul className="list-none pl-0">
              {complaintReasons.map((reason, index) => (
                <li key={index} className="relative pl-6 mb-2" style={{ color: "var(--neutral-700)" }}>
                  <span className="absolute left-0 font-bold" style={{ color: "var(--primary)" }}>•</span>
                  {reason}
                </li>
              ))}
            </ul>
            <div className="flex gap-4 flex-wrap mt-6">
              <button onClick={() => setShowComplaintModal(true)} className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }} data-testid="button-complaint-online">
                <FilePen className="w-4 h-4" /> Lodge Complaint Online
              </button>
              <button onClick={() => onNavigate("downloads")} className="py-3 px-6 rounded-xl font-semibold cursor-pointer flex items-center gap-2" style={{ background: "transparent", border: "2px solid var(--primary)", color: "var(--primary)" }} data-testid="button-download-complaint-form">
                <Download className="w-4 h-4" /> Download Form
              </button>
            </div>
          </div>

          {/* About Appeals */}
          <div className="bg-white rounded-[20px] p-10 mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>About Appeals (FOIA)</h2>
            <p className="leading-relaxed">
              Under the Freedom of Information Act (FOIA), the Zimbabwe Media Commission is required to review decisions relating to access to information held by public entities. The Commission receives appeals from members of the public who are denied access to requested information.
            </p>
            <h3 className="mt-6 mb-4" style={{ color: "var(--primary)" }}>When Can You Appeal?</h3>
            <ul className="list-none pl-0">
              {appealReasons.map((reason, index) => (
                <li key={index} className="relative pl-6 mb-2" style={{ color: "var(--neutral-700)" }}>
                  <span className="absolute left-0 font-bold" style={{ color: "var(--primary)" }}>•</span>
                  {reason}
                </li>
              ))}
            </ul>
            <div className="flex gap-4 flex-wrap mt-6">
              <button onClick={() => setShowAppealModal(true)} className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2" style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }} data-testid="button-appeal-online">
                <Scale className="w-4 h-4" /> Lodge Appeal Online
              </button>
              <button onClick={() => onNavigate("downloads")} className="py-3 px-6 rounded-xl font-semibold cursor-pointer flex items-center gap-2" style={{ background: "transparent", border: "2px solid var(--primary)", color: "var(--primary)" }} data-testid="button-download-appeal-form">
                <Download className="w-4 h-4" /> Download Form
              </button>
            </div>
          </div>

          {/* Process Timeline */}
          <div className="bg-white rounded-[20px] p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>What Happens After Submission?</h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
              {processSteps.map((step, index) => (
                <div key={index} className="text-center p-6">
                  <div className="w-[60px] h-[60px] rounded-full mx-auto mb-4 flex items-center justify-center text-xl font-bold" style={{ background: index === 3 ? "var(--accent-soft)" : "var(--primary-lighter)", color: index === 3 ? "var(--accent-dark)" : "var(--primary)" }}>
                    {step.number}
                  </div>
                  <h4 className="text-lg mb-2" style={{ fontFamily: "var(--font-serif)" }}>{step.title}</h4>
                  <p className="text-[0.9rem] m-0" style={{ color: "var(--neutral-600)" }}>{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Complaint Modal */}
      {showComplaintModal && (
        <div className="fixed inset-0 flex items-center justify-center z-[2000] p-4" style={{ background: "rgba(0,0,0,0.5)" }} onClick={() => setShowComplaintModal(false)}>
          <div className="bg-white rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-y-auto" style={{ animation: "modalFadeIn 0.5s ease" }} onClick={(e) => e.stopPropagation()}>
            {formSubmitted ? (
              <div className="p-12 text-center">
                <div className="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-10 h-10" />
                </div>
                <h3 className="mb-4" style={{ color: "var(--primary)" }}>Complaint Submitted!</h3>
                <p style={{ color: "var(--neutral-600)" }}>Your complaint has been received. We will review and contact you within 7-14 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-6" style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}>
                  <h3 className="text-white m-0">Lodge a Complaint</h3>
                  <p className="text-white/80 text-sm m-0 mt-1">Zimbabwe Media Commission - Complaints against media houses or journalists</p>
                </div>
                <form onSubmit={handleSubmit} className="p-8">
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Full Name *</label>
                    <input type="text" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Email *</label>
                      <input type="email" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                    </div>
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Phone *</label>
                      <input type="tel" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                    </div>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Media House/Journalist Name *</label>
                    <input type="text" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Complaint Details *</label>
                    <textarea required rows={4} className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                  </div>
                  <div className="flex gap-3 justify-end">
                    <button type="button" onClick={() => setShowComplaintModal(false)} className="py-3 px-6 rounded-xl font-semibold border-none" style={{ background: "var(--neutral-200)" }}>Cancel</button>
                    <button type="submit" className="py-3 px-6 rounded-xl font-semibold border-none text-white" style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}>Submit</button>
                  </div>
                </form>
              </>
            )}
          </div>
        </div>
      )}

      {/* Appeal Modal */}
      {showAppealModal && (
        <div className="fixed inset-0 flex items-center justify-center z-[2000] p-4" style={{ background: "rgba(0,0,0,0.5)" }} onClick={() => setShowAppealModal(false)}>
          <div className="bg-white rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-y-auto" style={{ animation: "modalFadeIn 0.5s ease" }} onClick={(e) => e.stopPropagation()}>
            {formSubmitted ? (
              <div className="p-12 text-center">
                <div className="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-10 h-10" />
                </div>
                <h3 className="mb-4" style={{ color: "var(--primary)" }}>Appeal Submitted!</h3>
                <p style={{ color: "var(--neutral-600)" }}>Your appeal has been received. We will review and contact you within 7-14 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-6" style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }}>
                  <h3 className="text-white m-0">Lodge an Appeal (FOIA)</h3>
                  <p className="text-white/80 text-sm m-0 mt-1">Zimbabwe Media Commission - Freedom of Information Act Appeals</p>
                </div>
                <form onSubmit={handleSubmit} className="p-8">
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Full Name *</label>
                    <input type="text" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Email *</label>
                      <input type="email" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                    </div>
                    <div>
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Phone *</label>
                      <input type="tel" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                    </div>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Public Entity Name *</label>
                    <input type="text" required className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Appeal Details *</label>
                    <textarea required rows={4} className="w-full py-3 px-4 rounded-xl" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} />
                  </div>
                  <div className="flex gap-3 justify-end">
                    <button type="button" onClick={() => setShowAppealModal(false)} className="py-3 px-6 rounded-xl font-semibold border-none" style={{ background: "var(--neutral-200)" }}>Cancel</button>
                    <button type="submit" className="py-3 px-6 rounded-xl font-semibold border-none text-white" style={{ background: "linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%)" }}>Submit</button>
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
