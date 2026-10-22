import { useEffect, useState } from "react";
import { Scale, User, GraduationCap } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import { portalApi, portalPublicUrl } from "../lib/api";

interface CommissionersPageProps {
  onNavigate: (page: string) => void;
}

interface WebsitePerson {
  id: number;
  name: string;
  position?: string | null;
  department?: string | null;
  biography?: string | null;
  image_url?: string | null;
  tier?: number | null;
}

export default function CommissionersPage({ onNavigate }: CommissionersPageProps) {
  const { t } = useLanguage();
  const [managedPeople, setManagedPeople] = useState<WebsitePerson[] | null>(null);

  useEffect(() => {
    async function fetchPeople() {
      try {
        const response = await portalApi.getWebsitePeople("commissioners");
        const people = Array.isArray(response?.data) ? response.data : [];
        setManagedPeople(people);
      } catch (error) {
        console.error("Unable to load managed commissioner profiles:", error);
      }
    }
    fetchPeople();
  }, []);

  interface Commissioner {
    title: string;
    name?: string;
    scope?: string;
  }

  const board = {
    chair: {
      title: "Chairperson",
      scope: "Leads the Board of Commissioners and chairs all meetings of the Commission",
    } as Commissioner,
    deputyChair: {
      title: "Deputy Chairperson",
      scope: "Supports the Chairperson and acts in their place when required",
    } as Commissioner,
    commissioners: [
      { title: "Commissioner" },
      { title: "Commissioner" },
      { title: "Commissioner" },
      { title: "Commissioner" },
      { title: "Commissioner" },
      { title: "Commissioner" },
      { title: "Commissioner" },
    ] as Commissioner[],
  };

  const PersonCard = ({ title, name, scope, image, size = "md", highlight = false }: {
    title: string;
    name?: string;
    scope?: string;
    image?: string | null;
    size?: "lg" | "md" | "sm";
    highlight?: boolean;
  }) => {
    const avatarSize = size === "lg" ? "w-32 h-32 md:w-40 md:h-40" : size === "md" ? "w-24 h-24 md:w-28 md:h-28" : "w-20 h-20 md:w-24 md:h-24";
    const iconSize = size === "lg" ? "w-12 h-12 md:w-14 md:h-14" : size === "md" ? "w-8 h-8 md:w-10 md:h-10" : "w-6 h-6 md:w-7 md:h-7";

    return (
      <div
        className={`bg-white rounded-2xl text-center transition-all hover:-translate-y-1 h-full min-h-[220px] ${size === "lg" ? "p-6 md:p-8 min-h-[360px]" : size === "md" ? "p-5 md:p-7 min-h-[290px]" : "p-5 md:p-6 min-h-[250px]"}`}
        style={{
          boxShadow: highlight ? "0 8px 32px rgba(46, 125, 86, 0.15)" : "var(--shadow-sm)",
          border: highlight ? "3px solid var(--primary)" : "1px solid var(--neutral-200)",
        }}
        data-testid={`card-commissioner-${title.toLowerCase().replace(/\s+/g, "-")}`}
      >
        <div
          className={`${avatarSize} rounded-full mx-auto mb-3 flex items-center justify-center overflow-hidden relative`}
          style={{
            background: highlight
              ? "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)"
              : "var(--neutral-100)",
          }}
        >
          {image ? (
            <img src={portalPublicUrl(image) || ""} alt={name || title} className="w-full h-full object-cover object-top" />
          ) : (
            <User className={`${iconSize} ${highlight ? "text-white" : ""}`} style={highlight ? {} : { color: "var(--neutral-400)" }} />
          )}
        </div>
        <p className="inline-flex items-center justify-center rounded-full px-3 py-1 mb-2 text-[11px] md:text-xs font-bold uppercase tracking-wide" style={{ color: "var(--primary-dark)", background: "var(--primary-lighter)" }}>
          {title}
        </p>
        {name && (
          <h4 className={`${size === "lg" ? "text-lg md:text-xl" : size === "md" ? "text-base md:text-lg" : "text-base md:text-lg"} font-semibold leading-snug break-words`} style={{ color: "var(--primary)" }}>
            {name}
          </h4>
        )}
        {scope && (
          <p className="text-xs md:text-sm" style={{ color: "var(--neutral-600)" }}>
            {scope}
          </p>
        )}
      </div>
    );
  };

  const explicitChairperson = managedPeople?.find((person) => /^chairperson$/i.test(person.position || ""));
  const deputyChairperson = managedPeople?.find((person) => /deputy chairperson/i.test(person.position || ""));
  const hasManagedPeople = Boolean(managedPeople?.length);
  const chairperson = managedPeople?.find((person) => person.tier === 1) ?? explicitChairperson;
  const deputy = managedPeople?.find((person) => person.tier === 2) ?? deputyChairperson;
  const commissioners = managedPeople?.filter((person) => (person.tier ?? 3) === 3 && person.id !== chairperson?.id && person.id !== deputy?.id);
  const hasChairperson = !hasManagedPeople || Boolean(chairperson);
  const hasDeputy = !hasManagedPeople || Boolean(deputy);

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.commissioners.title}
        subtitle={t.pages.commissioners.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "About", onClick: () => onNavigate("about") }, { label: "Board of Commissioners" }]}
      />

      {/* Constitutional Mandate */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
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

          {/* Board Structure */}
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }}>Board of Commissioners</h2>
            <p className="max-w-[700px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Commission consists of a Chairperson, Deputy Chairperson, and seven other Commissioners
              appointed in accordance with Section 237 of the Constitution.
            </p>
          </div>

          {hasManagedPeople && (
            <>
              <div className="mb-10">
                <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>Tier 1 — Chairperson</h3>
                <div className="flex justify-center">
                  <div className="max-w-[380px] w-full">
                    {chairperson ? <PersonCard title={chairperson.position || "Chairperson"} name={chairperson.name} scope={chairperson.biography || undefined} image={chairperson.image_url} size="lg" highlight /> : <div className="rounded-2xl border border-dashed p-8 text-center text-sm" style={{ borderColor: "var(--primary-light)", color: "var(--neutral-500)" }}>Position currently vacant</div>}
                  </div>
                </div>
              </div>
              <div className="mb-10">
                <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>Tier 2 — Vice Chairperson</h3>
                <div className="flex justify-center">
                  <div className="max-w-[340px] w-full">
                    {deputy ? <PersonCard title={deputy.position || "Vice Chairperson"} name={deputy.name} scope={deputy.biography || undefined} image={deputy.image_url} size="md" highlight /> : <div className="rounded-2xl border border-dashed p-8 text-center text-sm" style={{ borderColor: "var(--primary-light)", color: "var(--neutral-500)" }}>Position currently vacant</div>}
                  </div>
                </div>
              </div>
            </>
          )}

          {/* Chairperson - Top Level (static fallback) */}
          {!hasManagedPeople && hasChairperson && (
            <div className="flex justify-center mb-8 md:mb-10">
              <div className="max-w-[380px] w-full">
                {hasManagedPeople && chairperson ? (
                  <PersonCard title={chairperson.position || board.chair.title} name={chairperson.name} scope={chairperson.biography || undefined} image={chairperson.image_url} size="lg" highlight />
                ) : (
                  <PersonCard title={board.chair.title} scope={board.chair.scope} size="lg" highlight />
                )}
              </div>
            </div>
          )}

          {/* Connecting Line */}
          {!hasManagedPeople && hasChairperson && (
            <div className="hidden md:flex justify-center mb-6">
              <div className="w-0.5 h-8" style={{ background: "var(--primary-light)" }} />
            </div>
          )}

          {/* Deputy Chairperson */}
          {!hasManagedPeople && hasDeputy && (
            <div className="flex justify-center mb-8 md:mb-10">
              <div className="max-w-[340px] w-full">
                {hasManagedPeople && deputyChairperson ? (
                  <PersonCard title={deputyChairperson.position || board.deputyChair.title} name={deputyChairperson.name} scope={deputyChairperson.biography || undefined} image={deputyChairperson.image_url} size="md" highlight />
                ) : (
                  <PersonCard title={board.deputyChair.title} scope={board.deputyChair.scope} size="md" highlight />
                )}
              </div>
            </div>
          )}

          {/* Connecting Line */}
          {!hasManagedPeople && hasDeputy && (
            <div className="hidden md:flex justify-center mb-6">
              <div className="w-0.5 h-8" style={{ background: "var(--neutral-300)" }} />
            </div>
          )}

          {/* Commissioners */}
          <div className="mb-12">
            <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>
              {hasManagedPeople ? "Tier 3 — Commissioners" : "Commissioners"}
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 md:gap-6 max-w-[1200px] mx-auto">
              {(hasManagedPeople ? commissioners ?? [] : board.commissioners).map((person, index) => (
                <PersonCard
                  key={hasManagedPeople ? (person as WebsitePerson).id : index}
                  title={hasManagedPeople ? (person as WebsitePerson).position || "Commissioner" : (person as Commissioner).title}
                  name={hasManagedPeople ? (person as WebsitePerson).name : (person as Commissioner).name}
                  scope={hasManagedPeople ? (person as WebsitePerson).biography || undefined : undefined}
                  image={hasManagedPeople ? (person as WebsitePerson).image_url : undefined}
                  size="md"
                />
              ))}
            </div>
          </div>

          {/* Commitment */}
          <div
            className="p-6 md:p-10 rounded-2xl text-center"
            style={{ background: "var(--primary-lighter)", border: "1px solid var(--primary-light)" }}
          >
            <GraduationCap className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
            <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Our Commitment</h3>
            <p className="max-w-[600px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Board of Commissioners is committed to upholding the principles of media freedom, transparency
              and accountability set out in the Constitution, and to serving the people of Zimbabwe with
              integrity and impartiality.
            </p>
          </div>

        </div>
      </div>
    </div>
  );
}
