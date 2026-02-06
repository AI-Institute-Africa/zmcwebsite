import { useState } from "react";
import { FilePen, CheckCircle, Download, AlertTriangle, Newspaper, Radio, Tv, Globe, Scale } from "lucide-react";

interface ComplaintsPageProps {
  onNavigate: (page: string) => void;
}

export default function ComplaintsPage({ onNavigate }: ComplaintsPageProps) {
  const [showComplaintModal, setShowComplaintModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);
  const [fileError, setFileError] = useState("");

  const MAX_FILE_SIZE = 10 * 1024 * 1024;

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
      setShowComplaintModal(false);
      setFormSubmitted(false);
    }, 3000);
  };

  const mediaTypes = [
    { icon: Newspaper, label: "Print Media", desc: "Newspapers, magazines, publications" },
    { icon: Radio, label: "Radio", desc: "Radio stations and programs" },
    { icon: Tv, label: "Television", desc: "TV stations and broadcasts" },
    { icon: Globe, label: "Online Media", desc: "News websites, digital platforms" },
  ];

  const complaintReasons = [
    "Breach of media ethics and professional standards",
    "Defamation or invasion of privacy by media",
    "Biased, inaccurate, or misleading reporting",
    "Unprofessional conduct by journalists",
    "Violation of media laws and regulations",
    "Failure to correct published errors",
  ];

  const processSteps = [
    { number: 1, title: "Submission", desc: "Your complaint is received and logged" },
    { number: 2, title: "Review", desc: "Commission reviews the matter" },
    { number: 3, title: "Investigation", desc: "Parties are contacted for information" },
    { number: 4, title: "Decision", desc: "Final decision is communicated" },
  ];

  return (
    <div className="animate-fadeIn pt-[100px] md:pt-[130px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Media Complaints</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Lodge complaints against media houses and journalists
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-home">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Complaints</span>
        </div>
      </div>

      {/* Top Action Buttons */}
      <div className="py-6 md:py-8 px-4 md:px-8" style={{ background: "var(--primary-lighter)" }}>
        <div className="max-w-[1000px] mx-auto flex flex-col sm:flex-row gap-4 justify-center items-center">
          <button
            onClick={() => setShowComplaintModal(true)}
            className="py-3 px-8 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base w-full sm:w-auto justify-center"
            style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}
            data-testid="button-lodge-complaint-top"
          >
            <FilePen className="w-5 h-5 flex-shrink-0" /> Lodge a Complaint
          </button>
          <button
            onClick={() => onNavigate("appeals")}
            className="py-3 px-8 rounded-xl font-semibold cursor-pointer flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base w-full sm:w-auto justify-center"
            style={{ background: "transparent", border: "2px solid var(--primary)", color: "var(--primary)" }}
            data-testid="button-go-to-appeals"
          >
            <Scale className="w-5 h-5 flex-shrink-0" /> Go to Appeals (FOIA)
          </button>
        </div>
      </div>

      {/* Media Types Notice */}
      <div className="py-8 md:py-12 px-4 md:px-8" style={{ background: "var(--accent-soft)" }}>
        <div className="max-w-[1000px] mx-auto">
          <div className="flex items-center gap-3 mb-4 justify-center">
            <AlertTriangle className="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" style={{ color: "var(--accent-dark)" }} />
            <h3 className="m-0 text-base md:text-lg" style={{ color: "var(--accent-dark)" }}>Media-Specific Complaints Only</h3>
          </div>
          <p className="text-center text-sm md:text-base mb-6" style={{ color: "var(--neutral-700)" }}>
            ZMC only handles complaints related to registered media houses and accredited journalists. 
            We address issues concerning:
          </p>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            {mediaTypes.map((type, index) => (
              <div 
                key={index}
                className="bg-white rounded-xl p-4 text-center"
                style={{ boxShadow: "var(--shadow-sm)" }}
              >
                <type.icon className="w-8 h-8 mx-auto mb-2" style={{ color: "var(--primary)" }} />
                <h4 className="text-sm font-semibold mb-1" style={{ color: "var(--primary-dark)" }}>{type.label}</h4>
                <p className="text-xs m-0" style={{ color: "var(--neutral-600)" }}>{type.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* About Media Complaints */}
          <div className="bg-white rounded-xl md:rounded-[20px] p-6 md:p-10 mb-6 md:mb-8" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 className="text-xl md:text-2xl" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>About Media Complaints</h2>
            <p className="leading-relaxed text-sm md:text-base">
              The Zimbabwe Media Commission receives complaints against <strong>media practitioners and media organizations</strong> that 
              have breached media laws and ethics. This includes newspapers, TV and radio stations, online news platforms, and 
              accredited journalists. Individuals are encouraged to lodge a formal complaint in writing.
            </p>
            <h3 className="mt-6 mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>What Can You Complain About?</h3>
            <ul className="list-none pl-0">
              {complaintReasons.map((reason, index) => (
                <li key={index} className="relative pl-6 mb-2 text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>
                  <span className="absolute left-0 font-bold" style={{ color: "var(--primary)" }}>&#8226;</span>
                  {reason}
                </li>
              ))}
            </ul>
            <div className="flex gap-3 md:gap-4 flex-wrap mt-6">
              <button 
                onClick={() => setShowComplaintModal(true)} 
                className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base" 
                style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }} 
                data-testid="button-complaint-online"
              >
                <FilePen className="w-4 h-4 flex-shrink-0" /> Lodge Complaint Online
              </button>
              <button 
                onClick={() => onNavigate("downloads")} 
                className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold cursor-pointer flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base" 
                style={{ background: "transparent", border: "2px solid var(--primary)", color: "var(--primary)" }} 
                data-testid="button-download-complaint-form"
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
                  <div className="w-12 h-12 md:w-[60px] md:h-[60px] rounded-full mx-auto mb-3 md:mb-4 flex items-center justify-center text-lg md:text-xl font-bold" style={{ background: index === 3 ? "var(--accent-soft)" : "var(--primary-lighter)", color: index === 3 ? "var(--accent-dark)" : "var(--primary)" }}>
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

      {/* Complaint Modal */}
      {showComplaintModal && (
        <div className="fixed inset-0 flex items-center justify-center z-[2000] p-4" style={{ background: "rgba(0,0,0,0.5)" }} onClick={() => setShowComplaintModal(false)}>
          <div className="bg-white rounded-2xl md:rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-y-auto" style={{ animation: "modalFadeIn 0.5s ease" }} onClick={(e) => e.stopPropagation()}>
            {formSubmitted ? (
              <div className="p-8 md:p-12 text-center">
                <div className="w-16 h-16 md:w-20 md:h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-8 h-8 md:w-10 md:h-10" />
                </div>
                <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>Complaint Submitted!</h3>
                <p className="text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>Your media complaint has been received. We will review and contact you within 7-14 business days.</p>
              </div>
            ) : (
              <>
                <div className="p-5 md:p-6" style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}>
                  <h3 className="text-white m-0 text-lg md:text-xl">Lodge a Media Complaint</h3>
                  <p className="text-white/80 text-xs md:text-sm m-0 mt-1">Complaint against a media house or journalist</p>
                </div>
                <form onSubmit={handleSubmit} className="p-5 md:p-8">
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Your Full Name *</label>
                    <input type="text" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-complaint-name" />
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Email *</label>
                      <input type="email" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-complaint-email" />
                    </div>
                    <div>
                      <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Phone *</label>
                      <input type="tel" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-complaint-phone" />
                    </div>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Type of Media *</label>
                    <select required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="select-complaint-media-type">
                      <option value="">Select media type</option>
                      <option value="newspaper">Newspaper/Print Media</option>
                      <option value="radio">Radio Station</option>
                      <option value="television">Television Station</option>
                      <option value="online">Online/Digital Media</option>
                    </select>
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Media House/Journalist Name *</label>
                    <input type="text" required placeholder="e.g., The Herald, ZBC, Name of Journalist" className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-complaint-media-name" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Date of Incident *</label>
                    <input type="date" required className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="input-complaint-date" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Complaint Details *</label>
                    <textarea required rows={4} placeholder="Describe your complaint in detail..." className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base" style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} data-testid="textarea-complaint-details" />
                  </div>
                  <div className="mb-4">
                    <label className="block mb-2 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Upload Supporting Documents</label>
                    <div className="relative">
                      <input 
                        type="file" 
                        multiple 
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                        onChange={handleFileChange}
                        className="w-full py-2.5 md:py-3 px-4 rounded-xl text-sm md:text-base file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer" 
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }} 
                        data-testid="input-complaint-file-upload"
                      />
                    </div>
                    <p className="text-xs mt-1" style={{ color: "var(--neutral-500)" }}>Accepted: PDF, DOC, DOCX, JPG, PNG (max 10MB each)</p>
                    {fileError && <p className="text-xs mt-1" style={{ color: "#dc2626" }} data-testid="text-file-error">{fileError}</p>}
                  </div>
                  <div className="flex gap-3 justify-end flex-wrap">
                    <button type="button" onClick={() => setShowComplaintModal(false)} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none transition-all text-sm md:text-base cursor-pointer" style={{ background: "var(--neutral-200)" }} data-testid="button-cancel-complaint">Cancel</button>
                    <button type="submit" className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none text-white transition-all text-sm md:text-base cursor-pointer" style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }} data-testid="button-submit-complaint">Submit Complaint</button>
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
