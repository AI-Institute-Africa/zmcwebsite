import { BookOpen } from "lucide-react";

interface MagazinePageProps {
  onNavigate: (page: string) => void;
}

export default function MagazinePage({ onNavigate }: MagazinePageProps) {
  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">ZMC Magazine</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          The official magazine of the Zimbabwe Media Commission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-home">Home</a>
          <span className="text-white/70">/</span>
          <span className="text-white/70">Media Centre</span>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Magazine</span>
        </div>
      </div>

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[800px] mx-auto">
          <div
            className="bg-white rounded-2xl p-8 md:p-12 text-center"
            style={{ boxShadow: "var(--shadow-lg)", border: "1px solid var(--neutral-100)" }}
          >
            <div
              className="w-20 h-20 md:w-24 md:h-24 rounded-full mx-auto mb-6 flex items-center justify-center"
              style={{ background: "var(--primary-lighter)" }}
            >
              <BookOpen className="w-10 h-10 md:w-12 md:h-12" style={{ color: "var(--primary)" }} />
            </div>

            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }} data-testid="text-magazine-title">
              ZMC Magazine Coming Soon
            </h2>

            <p className="text-sm md:text-base mb-6" style={{ color: "var(--neutral-600)", maxWidth: "500px", margin: "0 auto 1.5rem" }} data-testid="text-magazine-message">
              ZMC Magazine editions will be uploaded shortly. Please check back soon.
            </p>

            <button
              onClick={() => onNavigate("contact")}
              className="inline-flex items-center gap-3 py-4 px-8 rounded-xl font-semibold text-base border-none cursor-pointer text-white transition-all hover:-translate-y-0.5"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-contact-magazine"
            >
              Contact Us
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
