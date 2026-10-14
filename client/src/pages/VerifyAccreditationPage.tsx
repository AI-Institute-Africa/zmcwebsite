import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import VerifyAccreditation from "../components/VerifyAccreditation";

interface VerifyAccreditationPageProps {
  onNavigate: (page: string) => void;
}

export default function VerifyAccreditationPage({ onNavigate }: VerifyAccreditationPageProps) {
  const { t } = useLanguage();

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.nav.verifyAccreditation}
        subtitle="Confirm that a media practitioner's accreditation number is valid and view the holder's details."
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: t.nav.accreditation, onClick: () => onNavigate("accreditation") },
          { label: t.nav.verifyAccreditation },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <VerifyAccreditation />
        </div>
      </div>
    </div>
  );
}
