import { useEffect, useState } from "react";
import { GraduationCap, User, X, ZoomIn } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import { portalApi, portalPublicUrl } from "../lib/api";

import imgResearchTraining from "@assets/Manager_Research,_Training_and_Development_1771232078116.jpg";
import imgProcurement from "@assets/Manager_Procurement_1771232078118.jpg";
import imgHR from "@assets/Manager_Human_Resources_1771232078120.jpg";
import imgAudit from "@assets/Manager_Audit_1771232078121.jpg";
import imgDirectorMDG from "@assets/Director_Media_Development_and_Governance_(Acting_Executive_Se_1771232078121.jpg";
import imgActingChiefAccountant from "@assets/Acting_Chief_Accountant_1771232078122.jpg";
import imgRegistrar from "@assets/Registrar_1771233019279.jpg";
import imgExecutiveSecretary from "@assets/Executive_Secretary_1775034529169.jpg";
import imgPrincipalDirector from "@assets/Principal_Director_1_1775034529168.jpg";
import imgAdminManager from "@assets/Administration_Manager_1775034529169.jpg";

interface SecretariatPageProps {
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

export default function SecretariatPage({ onNavigate }: SecretariatPageProps) {
  const { t } = useLanguage();
  const [zoomedImage, setZoomedImage] = useState<{ src: string; title: string } | null>(null);
  const [managedPeople, setManagedPeople] = useState<WebsitePerson[] | null>(null);

  useEffect(() => {
    async function fetchPeople() {
      try {
        const response = await portalApi.getWebsitePeople("secretariat");
        const people = Array.isArray(response?.data) ? response.data : [];
        setManagedPeople(people);
      } catch (error) {
        console.error("Unable to load managed secretariat profiles:", error);
      }
    }
    fetchPeople();
  }, []);

  interface TeamMember {
    title: string;
    department?: string;
    role?: string;
    scope?: string;
    image?: string;
  }

  const secretariatTeam = {
    head: {
      title: "Executive Secretary",
      scope: "Chief Executive Officer of the Commission, leading the Secretariat",
      image: imgExecutiveSecretary,
    },
    principalDirector: {
      title: "Principal Director",
      scope: "Supports the Secretary in strategic and operational matters",
      image: imgPrincipalDirector,
    },
    managers: [
      {
        title: "Director",
        department: "Media Development and Governance",
        image: imgDirectorMDG,
      },
      {
        title: "Director",
        department: "Public Information Compliance and Promotion",
      },
      {
        title: "Director",
        department: "Finance, Administration and HR",
      },
      {
        title: "Director",
        department: "Legal and Corporate Affairs",
      },
    ] as TeamMember[],
    staff: [
      { title: "Manager", department: "Procurement", image: imgProcurement },
      { title: "Manager", department: "Research, Training and Development", image: imgResearchTraining },
      { title: "Manager", department: "Human Resources", image: imgHR },
      { title: "Manager", department: "Legal Services" },
      { title: "Manager", department: "Audit", image: imgAudit },
      { title: "Registrar", department: "Registration & Accreditation", image: imgRegistrar },
      { title: "Acting Chief Accountant", department: "Finance", image: imgActingChiefAccountant },
      { title: "Manager", department: "Public Information Compliance" },
      { title: "Manager", department: "IT" },
      { title: "Manager", department: "Admin", image: imgAdminManager },
      { title: "Manager", department: "Communications and PR" },
    ] as TeamMember[],
  };

  const executiveSecretary = managedPeople?.find((person) => person.tier === 1) ?? managedPeople?.find((person) => /executive secretary/i.test(person.position || ""));
  const principalDirector = managedPeople?.find((person) => person.tier === 2) ?? managedPeople?.find((person) => /principal director/i.test(person.position || ""));
  const tierThreePeople = managedPeople?.filter((person) => (person.tier ?? 3) === 3 && person.id !== executiveSecretary?.id && person.id !== principalDirector?.id);
  const directors = managedPeople?.filter((person) => person.id !== executiveSecretary?.id && person.id !== principalDirector?.id && /director/i.test(person.position || ""));
  const staff = managedPeople?.filter((person) => person.id !== executiveSecretary?.id && person.id !== principalDirector?.id && !/director/i.test(person.position || ""));
  const hasManagedPeople = Boolean(managedPeople?.length);

  const personSubtitle = (person: WebsitePerson) =>
    [person.position, person.department].filter(Boolean).join(" • ");

  const PersonCard = ({ title, subtitle, scope, size = "md", highlight = false, image }: {
    title: string;
    subtitle?: string;
    scope?: string;
    size?: "lg" | "md" | "sm";
    highlight?: boolean;
    image?: string;
  }) => {
    const avatarSize = size === "lg" ? "w-32 h-32 md:w-40 md:h-40" : size === "md" ? "w-24 h-24 md:w-28 md:h-28" : "w-20 h-20 md:w-24 md:h-24";
    const iconSize = size === "lg" ? "w-12 h-12 md:w-14 md:h-14" : size === "md" ? "w-8 h-8 md:w-10 md:h-10" : "w-6 h-6 md:w-7 md:h-7";

    return (
      <div
        className={`bg-white rounded-2xl text-center transition-all hover:-translate-y-1 ${size === "lg" ? "p-6 md:p-8" : size === "md" ? "p-4 md:p-6" : "p-3 md:p-4"}`}
        style={{
          boxShadow: highlight ? "0 8px 32px rgba(46, 125, 86, 0.15)" : "var(--shadow-sm)",
          border: highlight ? "3px solid var(--primary)" : "1px solid var(--neutral-200)",
        }}
      >
        <div
          className={`${avatarSize} rounded-full mx-auto mb-3 flex items-center justify-center overflow-hidden relative group cursor-pointer`}
          style={{
            background: image
              ? "transparent"
              : highlight
                ? "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)"
                : "var(--neutral-100)",
          }}
          onClick={() => image && setZoomedImage({ src: image, title: `${title}${subtitle ? ' - ' + subtitle : ''}` })}
        >
          {image ? (
            <>
              <img
                src={image}
                alt={title}
                className="w-full h-full object-cover object-top"
              />
              <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center">
                <ZoomIn className="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-all" />
              </div>
            </>
          ) : (
            <User className={`${iconSize} ${highlight ? "text-white" : ""}`} style={highlight ? {} : { color: "var(--neutral-400)" }} />
          )}
        </div>
        <h4
          className={`mb-1 ${size === "lg" ? "text-lg md:text-xl" : size === "md" ? "text-base md:text-lg" : "text-sm md:text-base"}`}
          style={{ color: "var(--primary-dark)" }}
        >
          {title}
        </h4>
        {subtitle && (
          <p className="text-xs md:text-sm font-semibold mb-1" style={{ color: "var(--primary)" }}>
            {subtitle}
          </p>
        )}
        {scope && (
          <p className="text-xs md:text-sm" style={{ color: "var(--neutral-600)" }}>
            {scope}
          </p>
        )}
      </div>
    );
  };

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.secretariat.title}
        subtitle={t.pages.secretariat.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "About", onClick: () => onNavigate("about") }, { label: "Secretariat" }]}
      />

      {/* Team Section */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          {/* Introduction */}
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }}>Our Team</h2>
            <p className="max-w-[700px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Secretariat is headed by the Secretary who is the Chief Executive Officer. The Secretariat implements
              the policies and decisions of the Commission and manages day-to-day operations.
            </p>
          </div>

          {hasManagedPeople && (
            <>
              <div className="mb-10">
                <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>Tier 1 — Executive Secretary</h3>
                <div className="flex justify-center"><div className="max-w-[380px] w-full">
                  {executiveSecretary ? <PersonCard title={executiveSecretary.position || "Executive Secretary"} subtitle={executiveSecretary.name} scope={executiveSecretary.biography || undefined} image={portalPublicUrl(executiveSecretary.image_url) || undefined} size="lg" highlight /> : <div className="rounded-2xl border border-dashed p-8 text-center text-sm" style={{ borderColor: "var(--primary-light)", color: "var(--neutral-500)" }}>Position currently vacant</div>}
                </div></div>
              </div>
              <div className="mb-10">
                <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>Tier 2 — Principal Director</h3>
                <div className="flex justify-center"><div className="max-w-[340px] w-full">
                  {principalDirector ? <PersonCard title={principalDirector.position || "Principal Director"} subtitle={principalDirector.name} scope={principalDirector.biography || undefined} image={portalPublicUrl(principalDirector.image_url) || undefined} size="md" highlight /> : <div className="rounded-2xl border border-dashed p-8 text-center text-sm" style={{ borderColor: "var(--primary-light)", color: "var(--neutral-500)" }}>Position currently vacant</div>}
                </div></div>
              </div>
              <div className="mb-12">
                <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>Tier 3 — Secretariat</h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 md:gap-6 max-w-[1200px] mx-auto">
                  {(tierThreePeople ?? []).map((person) => <PersonCard key={person.id} title={person.position || "Secretariat"} subtitle={person.name} scope={person.biography || undefined} image={portalPublicUrl(person.image_url) || undefined} size="md" />)}
                </div>
              </div>
            </>
          )}

          {/* Executive Secretary - Top Level (static fallback) */}
          {!hasManagedPeople && (!hasManagedPeople || executiveSecretary) && (
            <div className="flex justify-center mb-8 md:mb-10">
              <div className="max-w-[380px] w-full">
              {hasManagedPeople && executiveSecretary ? (
                <PersonCard
                  title={executiveSecretary.name}
                  subtitle={personSubtitle(executiveSecretary)}
                  scope={executiveSecretary.biography || undefined}
                  image={portalPublicUrl(executiveSecretary.image_url) || undefined}
                  size="lg"
                  highlight
                />
              ) : (
                <PersonCard
                  title={secretariatTeam.head.title}
                  scope={secretariatTeam.head.scope}
                  image={secretariatTeam.head.image}
                  size="lg"
                  highlight
                />
              )}
              </div>
            </div>
          )}

          {/* Connecting Line */}
          {!hasManagedPeople && (!hasManagedPeople || executiveSecretary) && (
            <div className="hidden md:flex justify-center mb-6">
              <div className="w-0.5 h-8" style={{ background: "var(--primary-light)" }} />
            </div>
          )}

          {/* Principal Director */}
          {!hasManagedPeople && (!hasManagedPeople || principalDirector) && (
            <div className="flex justify-center mb-8 md:mb-10">
              <div className="max-w-[340px] w-full">
              {hasManagedPeople && principalDirector ? (
                <PersonCard
                  title={principalDirector.name}
                  subtitle={personSubtitle(principalDirector)}
                  scope={principalDirector.biography || undefined}
                  image={portalPublicUrl(principalDirector.image_url) || undefined}
                  size="md"
                  highlight
                />
              ) : (
                <PersonCard
                  title={secretariatTeam.principalDirector.title}
                  scope={secretariatTeam.principalDirector.scope}
                  image={secretariatTeam.principalDirector.image}
                  size="md"
                  highlight
                />
              )}
              </div>
            </div>
          )}

          {/* Connecting Line */}
          {!hasManagedPeople && (!hasManagedPeople || principalDirector) && (
            <div className="hidden md:flex justify-center mb-6">
              <div className="w-0.5 h-8" style={{ background: "var(--primary-light)" }} />
            </div>
          )}

          {/* Directors */}
          {!hasManagedPeople && (!hasManagedPeople || directors?.length) && (
            <div className="mb-10">
            <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>
              Directors
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 max-w-[1100px] mx-auto">
              {(hasManagedPeople ? directors ?? [] : secretariatTeam.managers).map((person, index) => (
                <PersonCard
                  key={hasManagedPeople ? (person as WebsitePerson).id : index}
                  title={hasManagedPeople ? (person as WebsitePerson).name : (person as TeamMember).title}
                  subtitle={hasManagedPeople ? personSubtitle(person as WebsitePerson) : (person as TeamMember).department}
                  scope={hasManagedPeople ? (person as WebsitePerson).biography || undefined : (person as TeamMember).scope}
                  image={hasManagedPeople ? portalPublicUrl((person as WebsitePerson).image_url) || undefined : (person as TeamMember).image}
                  size="md"
                />
              ))}
            </div>
            </div>
          )}

          {/* Connecting Line */}
          {!hasManagedPeople && (!hasManagedPeople || directors?.length) && (
            <div className="hidden md:flex justify-center mb-6">
              <div className="w-0.5 h-8" style={{ background: "var(--neutral-300)" }} />
            </div>
          )}

          {/* Managers / Staff */}
          {!hasManagedPeople && (!hasManagedPeople || staff?.length) && (
            <div className="mb-12">
            <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>
              Managers & Senior Staff
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {(hasManagedPeople ? staff ?? [] : secretariatTeam.staff).map((person, index) => (
                <PersonCard
                  key={hasManagedPeople ? (person as WebsitePerson).id : index}
                  title={hasManagedPeople ? (person as WebsitePerson).name : (person as TeamMember).title}
                  subtitle={hasManagedPeople ? personSubtitle(person as WebsitePerson) : (person as TeamMember).department}
                  scope={hasManagedPeople ? (person as WebsitePerson).biography || undefined : undefined}
                  image={hasManagedPeople ? portalPublicUrl((person as WebsitePerson).image_url) || undefined : (person as TeamMember).image}
                  size="sm"
                />
              ))}
            </div>
            </div>
          )}

          {/* Additional Info */}
          <div
            className="p-6 md:p-10 rounded-2xl text-center"
            style={{ background: "var(--primary-lighter)", border: "1px solid var(--primary-light)" }}
          >
            <GraduationCap className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
            <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Our Commitment</h3>
            <p className="max-w-[600px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Secretariat is committed to providing efficient and professional services to all stakeholders
              in the media industry. We strive to uphold the principles of transparency, accountability, and
              excellence in all our operations.
            </p>
          </div>

          {/* Organogram CTA */}
          <div className="mt-8 p-6 rounded-2xl text-center" style={{ background: "var(--accent-soft)", border: "1px solid var(--accent)" }}>
            <p className="text-sm md:text-base m-0 mb-3" style={{ color: "var(--neutral-700)" }}>
              Download the full ZMC Organogram below.
            </p>
            <a
              href="/documents/zmc-organogram.pdf"
              download
              className="inline-block py-3 px-6 rounded-xl font-semibold no-underline cursor-pointer text-white transition-all hover:-translate-y-0.5"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-download-organogram"
            >
              {t.pages.commissioners.downloadOrganogram}
            </a>
          </div>
        </div>
      </div>

      {/* Image Zoom Modal */}
      {zoomedImage && (
        <div
          className="fixed inset-0 z-[2000] flex items-center justify-center p-4"
          style={{ background: "rgba(0,0,0,0.8)" }}
          onClick={() => setZoomedImage(null)}
        >
          <button
            onClick={() => setZoomedImage(null)}
            className="absolute top-4 right-4 w-10 h-10 rounded-full flex items-center justify-center border-none cursor-pointer text-white z-10"
            style={{ background: "rgba(255,255,255,0.2)" }}
            data-testid="button-close-zoom"
          >
            <X className="w-6 h-6" />
          </button>
          <div className="max-w-[500px] w-full" onClick={(e) => e.stopPropagation()}>
            <img
              src={zoomedImage.src}
              alt={zoomedImage.title}
              className="w-full h-auto rounded-2xl"
              style={{ maxHeight: "80vh", objectFit: "contain" }}
            />
            <p className="text-white text-center mt-4 text-lg font-semibold">{zoomedImage.title}</p>
          </div>
        </div>
      )}
    </div>
  );
}
