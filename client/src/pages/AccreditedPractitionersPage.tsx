import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import PublicDirectory from "../components/PublicDirectory";

interface AccreditedPractitionersPageProps {
  onNavigate: (page: string) => void;
}

export default function AccreditedPractitionersPage({ onNavigate }: AccreditedPractitionersPageProps) {
  const { t } = useLanguage();

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.nav.accreditedPractitioners}
        subtitle="Search the register of journalists and media practitioners accredited by the Zimbabwe Media Commission."
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: t.nav.accreditation, onClick: () => onNavigate("accreditation") },
          { label: t.nav.accreditedPractitioners },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <PublicDirectory variant="practitioners" />
        </div>
      </div>
    </div>
  );
}
