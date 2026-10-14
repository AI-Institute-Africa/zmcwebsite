import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import PublicDirectory from "../components/PublicDirectory";

interface RegisteredMediaHousesPageProps {
  onNavigate: (page: string) => void;
}

export default function RegisteredMediaHousesPage({ onNavigate }: RegisteredMediaHousesPageProps) {
  const { t } = useLanguage();

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.nav.registeredMediaHouses}
        subtitle="Search the register of mass media services registered with the Zimbabwe Media Commission."
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: t.nav.registration, onClick: () => onNavigate("registration") },
          { label: t.nav.registeredMediaHouses },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          <PublicDirectory variant="mediahouses" />
        </div>
      </div>
    </div>
  );
}
