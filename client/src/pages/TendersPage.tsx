import { ExternalLink, FileText, Building2 } from "lucide-react";
import PageHero from "../components/PageHero";

interface TendersPageProps {
  onNavigate: (page: string) => void;
}

export default function TendersPage({ onNavigate }: TendersPageProps) {
  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title="Tenders"
        subtitle="Current tender opportunities at the Zimbabwe Media Commission"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Tenders" }]}
      />

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
              className="inline-flex items-center gap-3 py-4 px-8 rounded-xl font-semibold text-base border-none cursor-pointer text-white transition-all hover:scale-105"
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
