import { Download, FileText, FilePen, Scale, BookOpen, Shield, Coins, FolderOpen, Users } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";

interface DownloadsPageProps {
  onNavigate: (page: string) => void;
}

export default function DownloadsPage({ onNavigate }: DownloadsPageProps) {
  const { t } = useLanguage();
  const categories = [
    {
      title: "Registration & Accreditation Forms",
      icon: FilePen,
      color: "var(--primary)",
      bg: "var(--primary-lighter)",
      files: [
        { name: "Registration of a Mass Media Service - Form AP1", format: "DOC", size: "38 KB", desc: "For registering newspapers, radio, TV and online media houses", url: "/documents/registration-mass-media-service-AP1.doc" },
        { name: "Registration of a News Agency - Form AP2", format: "DOC", size: "37 KB", desc: "For registering a news agency", url: "/documents/registration-news-agency-AP2.doc" },
        { name: "Foreign Representative Office Permission - Form AP4", format: "DOC", size: "35 KB", desc: "For permission to operate a representative office of a foreign mass media service", url: "/documents/registration-foreign-representative-office-AP4.doc" },
        { name: "Accreditation of a Journalist (Local) - Form AP3", format: "DOC", size: "63 KB", desc: "For accreditation of local journalists and media practitioners", url: "/documents/accreditation-journalist-local-AP3.doc" },
        { name: "Accreditation of a Journalist (Foreign) - Form AP3", format: "DOC", size: "48 KB", desc: "For accreditation of foreign and temporary journalists", url: "/documents/accreditation-journalist-foreign-AP3.doc" },
        { name: "Renewal of Registration / Accreditation - Form AP5", format: "DOC", size: "35 KB", desc: "For renewing existing registration, accreditation or permission", url: "/documents/renewal-registration-accreditation-AP5.doc" },
        { name: "Mass Media Levy - Form AP6", format: "DOC", size: "35 KB", desc: "Levy return form for registered mass media owners", url: "/documents/mass-media-levy-AP6.doc" },
      ],
    },
    {
      title: "Freedom of Information Act Forms",
      icon: Scale,
      color: "var(--red)",
      bg: "var(--red-light)",
      files: [
        { name: "Freedom of Information Request Form", format: "PDF", size: "538 KB", desc: "Request for access to information (First Schedule, page 1479 of SI 229 of 2021)", url: "/documents/si-229-of-2021-foia-regulations.pdf#page=15" },
        { name: "Form of Acknowledgement by Information Officer", format: "PDF", size: "538 KB", desc: "Acknowledgement of a request by an information officer (Second Schedule, page 1483 of SI 229 of 2021)", url: "/documents/si-229-of-2021-foia-regulations.pdf#page=19" },
        { name: "FOIA Appeal Form", format: "PDF", size: "538 KB", desc: "Appeal against a decision of an information officer (Fourth Schedule, page 1483 of SI 229 of 2021)", url: "/documents/si-229-of-2021-foia-regulations.pdf#page=19" },
        { name: "Form of Subpoena", format: "PDF", size: "538 KB", desc: "Subpoena issued by the Commission (Fifth Schedule, page 1484 of SI 229 of 2021)", url: "/documents/si-229-of-2021-foia-regulations.pdf#page=20" },
      ],
    },
    {
      title: "Organizational Documents",
      icon: Users,
      color: "var(--blue)",
      bg: "var(--blue-light)",
      files: [
        { name: "ZMC Service Charter", format: "PDF", size: "4.8 MB", desc: "Service standards, turnaround times, and commitments to stakeholders", url: "/documents/zmc-service-charter.pdf" },
        { name: "ZMC Organogram", format: "PDF", size: "1.9 MB", desc: "Organizational structure chart of the Zimbabwe Media Commission", url: "/documents/zmc-organogram.pdf" },
      ],
    },
    {
      title: "Policies & Guidelines",
      icon: BookOpen,
      color: "var(--purple)",
      bg: "var(--purple-light)",
      files: [
        { name: "Summary of the Freedom of Information Act", format: "PDF", size: "214 KB", desc: "Plain-language summary of the Freedom of Information Act (Chapter 10:33)", url: "/documents/summary-of-freedom-of-information-act.pdf" },
      ],
    },
    {
      title: "Legislation",
      icon: Shield,
      color: "var(--accent-dark)",
      bg: "var(--accent-soft)",
      files: [
        { name: "Zimbabwe Media Commission Act [Chapter 10:35]", format: "PDF", size: "623 KB", desc: "The enabling Act establishing the Zimbabwe Media Commission", url: "/documents/zmc-act-chapter-10-35.pdf" },
        { name: "Freedom of Information Act [Chapter 10:33]", format: "PDF", size: "1.7 MB", desc: "Act governing public access to information held by public bodies", url: "/documents/freedom-of-information-act-2020.pdf" },
        { name: "Constitution of Zimbabwe (2023)", format: "PDF", size: "1.3 MB", desc: "The Constitution of Zimbabwe, as amended up to 20 June 2023", url: "/documents/constitution-of-zimbabwe-2023.pdf" },
      ],
    },
    {
      title: "Statutory Instruments",
      icon: Coins,
      color: "var(--orange)",
      bg: "var(--orange-light)",
      files: [
        { name: "SI 65 of 2022 - Fee Schedule", format: "PDF", size: "112 KB", desc: "Prescribes accreditation, registration and levy fees", url: "/documents/si-65-of-2022-fee-schedule.pdf" },
        { name: "SI 229 of 2021 - Freedom of Information (General) Regulations", format: "PDF", size: "538 KB", desc: "Regulations governing access-to-information requests and appeals", url: "/documents/si-229-of-2021-foia-regulations.pdf" },
        { name: "SI 169C of 2002 - AIPPA Regulations", format: "PDF", size: "8.9 MB", desc: "Registration, Accreditation and Levy Regulations", url: "/documents/si-169c-of-2002.pdf" },
        { name: "SI 10 of 2004 - AIPPA Amendment Regulations", format: "PDF", size: "1.9 MB", desc: "Amendment to the Registration, Accreditation and Levy Regulations", url: "/documents/si-10-of-2004.pdf" },
      ],
    },
  ];

  const handleDownload = (fileName: string) => {
    alert(`This document is coming soon: ${fileName}`);
  };

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.downloads.title}
        subtitle={t.pages.downloads.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Reports & Documents" }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {categories.map((category, index) => (
              <div
                key={index}
                className="bg-white rounded-[20px] overflow-hidden transition-all"
                style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
              >
                <div
                  className="p-6 flex items-center gap-4"
                  style={{ background: category.bg, borderBottom: `3px solid ${category.color}` }}
                >
                  <div
                    className="w-12 h-12 rounded-xl flex items-center justify-center"
                    style={{ background: category.color, color: "white" }}
                  >
                    <category.icon className="w-6 h-6" />
                  </div>
                  <h3 className="m-0" style={{ color: "var(--neutral-800)" }}>{category.title}</h3>
                </div>
                <div className="p-4">
                  {category.files.map((file, fileIndex) => {
                    const fileUrl = (file as { url?: string }).url;
                    const isPageAnchor = !!fileUrl && fileUrl.includes("#");
                    const rowClass = "flex items-center justify-between p-4 rounded-xl mb-2 cursor-pointer transition-all group hover:bg-[var(--neutral-50)] no-underline";
                    const rowStyle = { border: "1px solid var(--neutral-100)" };
                    const inner = (
                      <>
                        <div className="flex items-center gap-3 min-w-0">
                          <FileText className="w-5 h-5 flex-shrink-0" style={{ color: category.color }} />
                          <div className="min-w-0">
                            <p className="text-[0.95rem] font-medium m-0 truncate" style={{ color: "var(--neutral-700)" }}>{file.name}</p>
                            <p className="text-xs m-0 mt-0.5 line-clamp-1" style={{ color: "var(--neutral-500)" }}>{file.desc}</p>
                            <span className="text-xs whitespace-nowrap" style={{ color: "var(--neutral-400)" }}>{file.format}&nbsp;|&nbsp;{file.size}</span>
                          </div>
                        </div>
                        <span
                          className="w-10 h-10 rounded-full flex items-center justify-center transition-all group-hover:scale-110 flex-shrink-0"
                          style={{ background: category.bg, color: category.color }}
                        >
                          <Download className="w-5 h-5" />
                        </span>
                      </>
                    );
                    return fileUrl ? (
                      <a
                        key={fileIndex}
                        href={fileUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        {...(isPageAnchor ? {} : { download: true })}
                        className={rowClass}
                        style={rowStyle}
                        data-testid={`download-item-${index}-${fileIndex}`}
                      >
                        {inner}
                      </a>
                    ) : (
                      <button
                        key={fileIndex}
                        type="button"
                        className={`${rowClass} w-full text-left bg-transparent`}
                        style={rowStyle}
                        onClick={() => handleDownload(file.name)}
                        data-testid={`download-item-${index}-${fileIndex}`}
                      >
                        {inner}
                      </button>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>

          <div
            className="mt-12 rounded-[20px] p-10 text-center"
            style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}
          >
            <FolderOpen className="w-12 h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
            <h3 className="mb-3" style={{ color: "var(--primary-dark)" }}>Need Help Finding a Document?</h3>
            <p style={{ color: "var(--neutral-600)", maxWidth: "500px", margin: "0 auto 1.5rem" }}>
              If you can't find what you're looking for, please contact our office and we'll be happy to assist you.
            </p>
            <button
              onClick={() => onNavigate("contact")}
              className="py-3 px-8 rounded-xl font-semibold border-none cursor-pointer text-white"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-contact-downloads"
            >
              Contact Us
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
