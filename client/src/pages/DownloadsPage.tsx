import { Download, FileText, FilePen, ClipboardList, Scale, BookOpen, Shield, Coins, FolderOpen } from "lucide-react";

interface DownloadsPageProps {
  onNavigate: (page: string) => void;
}

export default function DownloadsPage({ onNavigate }: DownloadsPageProps) {
  const categories = [
    {
      title: "Accreditation Forms",
      icon: FilePen,
      color: "var(--primary)",
      bg: "var(--primary-lighter)",
      files: [
        { name: "New Accreditation Application Form", format: "PDF", size: "245 KB" },
        { name: "Accreditation Renewal Form", format: "PDF", size: "198 KB" },
        { name: "Freelance Journalist Application", format: "PDF", size: "312 KB" },
        { name: "Foreign Correspondent Application", format: "PDF", size: "287 KB" },
      ],
    },
    {
      title: "Registration Forms",
      icon: ClipboardList,
      color: "var(--blue)",
      bg: "var(--blue-light)",
      files: [
        { name: "Media Registration Application", format: "PDF", size: "356 KB" },
        { name: "Registration Renewal Form", format: "PDF", size: "201 KB" },
        { name: "Change of Particulars Form", format: "PDF", size: "178 KB" },
      ],
    },
    {
      title: "Complaints & Appeals",
      icon: Scale,
      color: "var(--red)",
      bg: "var(--red-light)",
      files: [
        { name: "Complaint Form", format: "PDF", size: "234 KB" },
        { name: "FOIA Appeal Form", format: "PDF", size: "267 KB" },
        { name: "Complaint Guidelines", format: "PDF", size: "189 KB" },
      ],
    },
    {
      title: "Policies & Guidelines",
      icon: BookOpen,
      color: "var(--purple)",
      bg: "var(--purple-light)",
      files: [
        { name: "Media Ethics Code", format: "PDF", size: "456 KB" },
        { name: "Sexual Harassment Policy", format: "PDF", size: "312 KB" },
        { name: "Editorial Guidelines", format: "PDF", size: "523 KB" },
        { name: "Social Media Policy", format: "PDF", size: "287 KB" },
      ],
    },
    {
      title: "Legislation",
      icon: Shield,
      color: "var(--accent-dark)",
      bg: "var(--accent-soft)",
      files: [
        { name: "Zimbabwe Media Commission Act", format: "PDF", size: "678 KB" },
        { name: "Freedom of Information Act", format: "PDF", size: "534 KB" },
        { name: "Access to Information Act", format: "PDF", size: "423 KB" },
      ],
    },
    {
      title: "Fee Schedules",
      icon: Coins,
      color: "var(--orange)",
      bg: "var(--orange-light)",
      files: [
        { name: "SI 65 of 2022 - Fee Schedule", format: "PDF", size: "189 KB" },
        { name: "Payment Guidelines", format: "PDF", size: "145 KB" },
      ],
    },
  ];

  const handleDownload = (fileName: string) => {
    alert(`Downloading: ${fileName}`);
  };

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Downloads</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Access forms, policies, and important documents
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem]">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Downloads</span>
        </div>
      </div>

      {/* Content */}
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
                  {category.files.map((file, fileIndex) => (
                    <div
                      key={fileIndex}
                      className="flex items-center justify-between p-4 rounded-xl mb-2 cursor-pointer transition-all group hover:bg-[var(--neutral-50)]"
                      style={{ border: "1px solid var(--neutral-100)" }}
                      onClick={() => handleDownload(file.name)}
                      data-testid={`download-item-${index}-${fileIndex}`}
                    >
                      <div className="flex items-center gap-3 min-w-0">
                        <FileText className="w-5 h-5 flex-shrink-0" style={{ color: category.color }} />
                        <div className="min-w-0">
                          <p className="text-[0.95rem] font-medium m-0 truncate" style={{ color: "var(--neutral-700)" }}>{file.name}</p>
                          <span className="text-xs whitespace-nowrap" style={{ color: "var(--neutral-400)" }}>{file.format}&nbsp;•&nbsp;{file.size}</span>
                        </div>
                      </div>
                      <button
                        className="w-10 h-10 rounded-full flex items-center justify-center border-none cursor-pointer transition-all group-hover:scale-110"
                        style={{ background: category.bg, color: category.color }}
                      >
                        <Download className="w-5 h-5" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>

          {/* All Documents Link */}
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
