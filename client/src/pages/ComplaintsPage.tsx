import { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { FilePen, CheckCircle, AlertTriangle, Newspaper, Radio, Tv, Globe } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";

interface ComplaintsPageProps {
  onNavigate: (page: string) => void;
}

export default function ComplaintsPage({ onNavigate }: ComplaintsPageProps) {
  const { t } = useLanguage();
  const [showComplaintModal, setShowComplaintModal] = useState(false);
  const [formSubmitted, setFormSubmitted] = useState(false);
  const [fileError, setFileError] = useState("");

  const MAX_FILE_SIZE = 10 * 1024 * 1024;

  useEffect(() => {
    if (showComplaintModal) {
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
  }, [showComplaintModal]);

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

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.complaints.title}
        subtitle={t.pages.complaints.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Media Complaints" }]}
      />

      <div className="py-8 md:py-12 px-4 md:px-8" style={{ background: "var(--accent-soft)" }}>
        <div className="max-w-[1000px] mx-auto">
          <div className="flex items-center gap-3 mb-4 justify-center">
            <AlertTriangle className="w-5 h-5 md:w-6 md:h-6 flex-shrink-0" style={{ color: "var(--accent-dark)" }} />
            <h3 className="m-0 text-base md:text-lg" style={{ color: "var(--accent-dark)" }}>Media-Specific Complaints Only</h3>
          </div>
          <p className="text-center text-sm md:text-base mb-6" style={{ color: "var(--neutral-700)" }}>
            The ZMC handles complaints related to media and media practitioners. 
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

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <div className="bg-white rounded-xl md:rounded-[20px] p-6 md:p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 className="text-xl md:text-2xl" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>{t.pages.complaints.aboutTitle}</h2>
            <p className="leading-relaxed text-sm md:text-base mt-4" style={{ color: "var(--neutral-700)" }}>
              The Zimbabwe Media Commission receive and consider complaints from the public and, where appropriate, to take action against journalists and other persons employed in the media or broadcasting who are found to have breached any law or any code of conduct applicable to them.
            </p>
            <p className="leading-relaxed text-sm md:text-base mt-4" style={{ color: "var(--neutral-700)" }}>
              To lodge a complaint, click the <strong>"Upload Complaint Documents"</strong> button above and upload any supporting 
              documents such as articles, screenshots, audio or video recordings related to the complaint.
            </p>
            <div className="mt-6 p-4 rounded-xl" style={{ background: "var(--primary-lighter)" }}>
              <p className="text-sm md:text-base m-0" style={{ color: "var(--neutral-700)" }}>
                <strong>How to submit:</strong> Click "Upload Complaint Documents," attach your supporting evidence (articles, screenshots, 
                recordings, etc.), and submit. Our team will review your complaint and contact you as soon as possible.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="py-8 md:py-12 px-4 md:px-8" style={{ background: "var(--primary-lighter)" }}>
        <div className="max-w-[1000px] mx-auto flex justify-center">
          <button
            onClick={() => setShowComplaintModal(true)}
            className="py-3 px-8 rounded-xl font-semibold border-none cursor-pointer text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 text-sm md:text-base justify-center"
            style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}
            data-testid="button-lodge-complaint-top"
          >
            <FilePen className="w-5 h-5 flex-shrink-0" /> {t.pages.complaints.uploadDocuments}
          </button>
        </div>
      </div>

      {showComplaintModal && createPortal(
        <>
        <div
          className="fixed inset-0 z-[2000]"
          style={{ background: "rgba(0,0,0,0.55)", backdropFilter: "blur(4px)" }}
          onClick={() => setShowComplaintModal(false)}
        />
        <div
          className="fixed top-4 bottom-4 left-0 right-0 mx-auto w-[calc(100%-2rem)] max-w-[600px] bg-white rounded-2xl md:rounded-3xl shadow-2xl flex flex-col z-[2001]"
        >
            {formSubmitted ? (
              <div className="p-8 md:p-12 text-center flex-1 flex flex-col items-center justify-center">
                <div className="w-16 h-16 md:w-20 md:h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white" style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}>
                  <CheckCircle className="w-8 h-8 md:w-10 md:h-10" />
                </div>
                <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary)" }}>Documents Submitted!</h3>
                <p className="text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>Your complaint documents have been received. We will review and contact you as soon as possible.</p>
              </div>
            ) : (
              <>
                <div className="p-5 md:p-6 rounded-t-2xl md:rounded-t-3xl flex-shrink-0" style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }}>
                  <h3 className="text-white m-0 text-lg md:text-xl">Upload Complaint Documents</h3>
                  <p className="text-white/80 text-xs md:text-sm m-0 mt-1">Attach your supporting evidence and submit your complaint</p>
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
                        data-testid="input-complaint-name"
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
                        data-testid="input-complaint-email"
                      />
                    </div>
                    <div className="mb-4">
                      <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Brief Description of Complaint *</label>
                      <textarea
                        required
                        rows={3}
                        placeholder="Briefly describe your complaint..."
                        className="w-full py-2.5 px-4 rounded-xl text-sm outline-none resize-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-complaint-description"
                      />
                    </div>
                    <div className="mb-4">
                      <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Supporting Documents (Optional)</label>
                      <input
                        type="file"
                        multiple
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.mp3,.mp4,.avi,.mov,.wav,.webm"
                        onChange={handleFileChange}
                        className="w-full py-2.5 px-4 rounded-xl text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:cursor-pointer"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-complaint-file-upload"
                      />
                      <p className="text-xs mt-1" style={{ color: "var(--neutral-500)" }}>Accepted: PDF, DOC, DOCX, JPG, PNG, MP3, MP4, AVI, MOV, WAV, WEBM (max 10MB each). Include articles, screenshots, audio/video recordings, or other evidence.</p>
                      {fileError && <p className="text-xs mt-1" style={{ color: "#dc2626" }} data-testid="text-file-error">{fileError}</p>}
                    </div>
                    <div className="mb-2">
                      <label className="block mb-1.5 font-medium text-sm" style={{ color: "var(--neutral-700)" }}>Evidence Link (Optional)</label>
                      <input
                        type="url"
                        placeholder="Paste a link to online evidence (e.g. https://...)"
                        className="w-full py-2.5 px-4 rounded-xl text-sm outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-complaint-evidence-link"
                      />
                      <p className="text-xs mt-1" style={{ color: "var(--neutral-500)" }}>If the evidence is on a website, paste the link here.</p>
                    </div>
                  </div>
                  <div className="flex gap-3 justify-end flex-wrap p-4 md:px-8 md:py-4 border-t flex-shrink-0" style={{ borderColor: "var(--neutral-200)", background: "var(--neutral-50)", borderRadius: "0 0 1rem 1rem" }}>
                    <button type="button" onClick={() => setShowComplaintModal(false)} className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none transition-all text-sm md:text-base cursor-pointer" style={{ background: "var(--neutral-200)" }} data-testid="button-cancel-complaint">Cancel</button>
                    <button type="submit" className="py-2.5 md:py-3 px-5 md:px-6 rounded-xl font-semibold border-none text-white transition-all text-sm md:text-base cursor-pointer" style={{ background: "linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)" }} data-testid="button-submit-complaint">Submit Complaint</button>
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
