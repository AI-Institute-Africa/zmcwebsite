import { ExternalLink, FileText, Building2 } from "lucide-react";

interface TendersPageProps {
  onNavigate: (page: string) => void;
}

export default function TendersPage({ onNavigate }: TendersPageProps) {
  return (
    <div className="animate-fadeIn pt-[100px] md:pt-[130px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Tenders</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Current tender opportunities at the Zimbabwe Media Commission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Tenders</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[800px] mx-auto">
          {/* PRAZ Portal Link */}
          <div
            className="bg-white rounded-2xl p-8 md:p-12 text-center"
            style={{ boxShadow: "var(--shadow-lg)", border: "1px solid var(--neutral-100)" }}
          >
            <div 
              className="w-20 h-20 md:w-24 md:h-24 rounded-full mx-auto mb-6 flex items-center justify-center"
              style={{ background: "var(--primary-lighter)" }}
            >
              <Building2 className="w-10 h-10 md:w-12 md:h-12" style={{ color: "var(--primary)" }} />
            </div>
            
            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }}>
              Procurement Regulatory Authority of Zimbabwe
            </h2>
            
            <p className="text-sm md:text-base mb-6" style={{ color: "var(--neutral-600)", maxWidth: "500px", margin: "0 auto 1.5rem" }}>
              All Zimbabwe Media Commission tenders are published on the PRAZ e-Procurement Portal. 
              Visit the portal to view current opportunities and submit your bids.
            </p>

            <a
              href="https://egp.praz.org.zw/"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-3 py-4 px-8 rounded-xl font-semibold text-base border-none cursor-pointer text-white transition-all hover:scale-105 active:scale-95"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="link-praz-portal"
            >
              <FileText className="w-5 h-5" />
              Visit PRAZ Portal
              <ExternalLink className="w-4 h-4" />
            </a>

            <p className="text-xs mt-6" style={{ color: "var(--neutral-400)" }}>
              You will be redirected to the official PRAZ e-Procurement Portal
            </p>
          </div>

          {/* Additional Info */}
          <div 
            className="mt-8 rounded-xl p-6 text-center"
            style={{ background: "var(--accent-soft)", border: "1px solid var(--accent-light)" }}
          >
            <p className="text-sm" style={{ color: "var(--neutral-700)" }}>
              For tender-related inquiries, please contact our Procurement Unit at{" "}
              <a 
                href="mailto:procurement@zmc.org.zw" 
                style={{ color: "var(--primary)" }}
                className="font-medium"
              >
                procurement@zmc.org.zw
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
