import { Newspaper } from "lucide-react";
import PageHero from "../components/PageHero";

interface PressReleasesPageProps {
  onNavigate: (page: string) => void;
}

export default function PressReleasesPage({ onNavigate }: PressReleasesPageProps) {
  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title="Press Releases"
        subtitle="Official press releases from the Zimbabwe Media Commission"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Media Centre" }, { label: "Press Releases" }]}
      />

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
              <Newspaper className="w-10 h-10 md:w-12 md:h-12" style={{ color: "var(--primary)" }} />
            </div>

            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }} data-testid="text-press-releases-title">
              Press Releases Coming Soon
            </h2>

            <p className="text-sm md:text-base mb-6" style={{ color: "var(--neutral-600)", maxWidth: "500px", margin: "0 auto 1.5rem" }} data-testid="text-press-releases-message">
              Press releases will be uploaded shortly. Please check back soon for the latest updates from the Zimbabwe Media Commission.
            </p>

            <button
              onClick={() => onNavigate("contact")}
              className="inline-flex items-center gap-3 py-4 px-8 rounded-xl font-semibold text-base border-none cursor-pointer text-white transition-all hover:-translate-y-0.5"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-contact-press-releases"
            >
              Contact Us
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
