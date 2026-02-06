import { Users, Scale } from "lucide-react";
import zmcStructureImg from "@assets/zmc_board_structure.png";

interface CommissionersPageProps {
  onNavigate: (page: string) => void;
}

export default function CommissionersPage({ onNavigate }: CommissionersPageProps) {
  return (
    <div className="animate-fadeIn pt-[100px] md:pt-[130px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Board of Commissioners</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Leadership and governance of the Zimbabwe Media Commission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-home">Home</a>
          <span className="text-white/70">/</span>
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("about"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-about">About</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Board of Commissioners</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          {/* Introduction */}
          <div 
            className="text-center mb-12 md:mb-16 p-6 md:p-10 rounded-2xl"
            style={{ background: "var(--primary-lighter)", border: "1px solid var(--primary-light)" }}
          >
            <Scale className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
            <h2 className="mb-4 text-xl md:text-2xl" style={{ color: "var(--primary-dark)" }}>Constitutional Mandate</h2>
            <p className="max-w-[800px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Zimbabwe Media Commission is established under Section 249 of the Constitution of Zimbabwe. 
              The Board of Commissioners is appointed by the President after consultation with the Committee 
              on Standing Rules and Orders. Commissioners serve to promote and protect freedom of expression 
              and media freedom in Zimbabwe.
            </p>
          </div>

          {/* Proposed Structure - Exact Image */}
          <div className="mb-12 md:mb-16">
            <h2 className="text-center text-xl md:text-2xl mb-6" style={{ color: "var(--primary-dark)" }}>
              Proposed / Revised Structure
            </h2>
            <div 
              className="bg-white rounded-2xl p-4 md:p-8 overflow-hidden"
              style={{ boxShadow: "var(--shadow-lg)", border: "1px solid var(--neutral-200)" }}
            >
              <img 
                src={zmcStructureImg} 
                alt="Zimbabwe Media Commission Proposed / Revised Structure - Establishment 108" 
                className="w-full h-auto rounded-lg"
                style={{ maxWidth: "100%" }}
                data-testid="img-board-structure"
              />
            </div>
            <p className="text-center mt-4 text-xs md:text-sm" style={{ color: "var(--neutral-500)" }}>
              Zimbabwe Media Commission Proposed / Revised Structure: Establishment 108
            </p>
          </div>

          {/* Powers and Functions */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
              className="bg-white rounded-xl p-6 md:p-8"
              style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-200)" }}
            >
              <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Powers of the Commission</h3>
              <ul className="space-y-3">
                {[
                  "Regulate the media industry in Zimbabwe",
                  "Register and accredit media practitioners",
                  "Receive and adjudicate complaints against media",
                  "Conduct research on media issues",
                  "Promote best practices in the media industry",
                ].map((item, idx) => (
                  <li key={idx} className="flex items-start gap-3">
                    <span 
                      className="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 text-xs"
                      style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                    >
                      {idx + 1}
                    </span>
                    <span className="text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>{item}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div 
              className="bg-white rounded-xl p-6 md:p-8"
              style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-200)" }}
            >
              <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Functions of the Commission</h3>
              <ul className="space-y-3">
                {[
                  "Promote freedom of expression and media freedom",
                  "Protect journalists from harassment",
                  "Ensure fair and accurate reporting",
                  "Uphold ethical standards in journalism",
                  "Foster a diverse and pluralistic media environment",
                ].map((item, idx) => (
                  <li key={idx} className="flex items-start gap-3">
                    <span 
                      className="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 text-xs"
                      style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
                    >
                      {idx + 1}
                    </span>
                    <span className="text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>{item}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* CTA */}
          <div className="mt-12 text-center">
            <button
              onClick={() => onNavigate("about")}
              className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white transition-all hover:-translate-y-0.5"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-about-zmc"
            >
              Learn More About ZMC
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
