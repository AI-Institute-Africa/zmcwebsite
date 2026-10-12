import { GraduationCap, ExternalLink } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import { TRAINING_HUB_URL } from "../lib/api";

interface MediaHubPageProps {
  onNavigate: (page: string) => void;
}

const steps = [
  "Visit the ZMC Training Hub at traininghub.zmc.org.zw.",
  'Click the "Register" button on the Training Hub home page.',
  "Enter your full name, email address and a secure password.",
  "Open the verification email sent to your inbox and confirm your address.",
  "Log in to your new account.",
  "Browse the available courses and enrol in the ones you want to take.",
];

export default function MediaHubPage({ onNavigate }: MediaHubPageProps) {
  const { t } = useLanguage();

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.nav.mediaHub}
        subtitle="Online trainings and capacity building for media practitioners"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: t.nav.mediaHub }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <div
            className="bg-white rounded-[20px] p-6 md:p-10 mb-8"
            style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
          >
            <h2
              className="flex items-center gap-3"
              style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}
            >
              <GraduationCap className="w-6 h-6" />
              About the Media Hub
            </h2>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              The ZMC Media Hub is the Commission's online learning and capacity-building platform for media
              practitioners. It hosts accredited courses, workshops and practical resources designed to strengthen
              professional journalism, media ethics and digital skills across Zimbabwe — available to journalists,
              editors and media houses at any time, from anywhere.
            </p>
          </div>

          <div
            className="bg-white rounded-[20px] p-6 md:p-10"
            style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
          >
            <h2
              style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}
            >
              How to Register
            </h2>
            <p className="text-base leading-relaxed mt-4 mb-6" style={{ color: "var(--neutral-700)" }}>
              Follow these steps to create your free account on the Training Hub and start learning.
            </p>

            <ol className="list-none pl-0 m-0 space-y-4">
              {steps.map((step, i) => (
                <li key={i} className="flex items-start gap-4">
                  <span
                    className="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center font-bold text-white"
                    style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                  >
                    {i + 1}
                  </span>
                  <p className="m-0 text-[0.95rem] leading-relaxed pt-1.5" style={{ color: "var(--neutral-700)" }}>
                    {step}
                  </p>
                </li>
              ))}
            </ol>

            <div className="mt-8 pt-6" style={{ borderTop: "1px solid var(--neutral-100)" }}>
              <a
                href={TRAINING_HUB_URL}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 py-3 px-7 rounded-xl font-semibold text-white no-underline transition-all hover:-translate-y-0.5"
                style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                data-testid="button-register-training-hub"
              >
                Register
                <ExternalLink className="w-5 h-5" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
