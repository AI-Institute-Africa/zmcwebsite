import { Target, Eye, Heart, Users, Shield, Award, BookOpen, Globe, Radio, Scale, Megaphone, Lightbulb } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";

interface AboutPageProps {
  onNavigate: (page: string) => void;
}

export default function AboutPage({ onNavigate }: AboutPageProps) {
  const { t } = useLanguage();
  const functions = [
    { icon: Shield, title: "Uphold Media Freedom", desc: "To uphold, promote and develop freedom of the media" },
    { icon: Award, title: "Ethics & Good Practice", desc: "To promote and enforce good practices and ethics in the media" },
    { icon: Radio, title: "Monitor Broadcasting", desc: "To monitor broadcasting in the public interest and ensure fairness and diversity of views broadly representing the Zimbabwean society" },
    { icon: BookOpen, title: "Codes of Conduct", desc: "To encourage the formulation of codes of conduct for persons employed in the media and, where no such code exists, to formulate and enforce one" },
    { icon: Scale, title: "Complaints Handling", desc: "To receive and consider complaints from the public and take action against journalists and other persons employed in the media or broadcasting who are found to have breached any law or code of conduct" },
    { icon: Globe, title: "Access to Information", desc: "To ensure that the people of Zimbabwe have fair and wide access to information" },
    { icon: Megaphone, title: "Language Development", desc: "To encourage the use and development of all the officially recognised languages of Zimbabwe" },
    { icon: Lightbulb, title: "New Technology", desc: "To encourage the adoption of new technology in the media and in the dissemination of information" },
    { icon: Users, title: "Fair Competition", desc: "To promote fair competition and diversity in the media" },
    { icon: Target, title: "Research & Reform", desc: "To conduct research into issues relating to freedom of the press and of expression, and to promote reforms in the law" },
  ];

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.about.title}
        subtitle={t.pages.about.subtitle}
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "About ZMC" }]}
      />

      {/* Content */}
      <div className="py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* Introduction / Purpose & General Mandate */}
          <div
            className="bg-white rounded-[20px] p-6 md:p-10 mb-8 transition-all"
            style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)", letterSpacing: "0.02em" }}
          >
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem", letterSpacing: "0.02em" }}>
              Purpose & General Mandate
            </h2>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              The Zimbabwe Media Commission (ZMC) is one of the Chapter 12 Independent Commissions whose purpose is the 
              entrenchment of a democratic society driven by respect for the Constitution, rule of law, democracy and human rights 
              among other objectives. The Commission's major focus is on the promotion and protection of freedom of expression 
              and of the media; and the promotion of accountable governance through facilitating public access to information held 
              by public entities for the purposes of transparency, accountability and protection of human rights.
            </p>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              The ZMC plays its critical role in the media and information sector as part of the Chapter 12 Independent Commissions 
              whose major purpose is to contribute towards a democratic Zimbabwe with constitutionalism as a shared value. The ZMC, 
              like other Independent Commissions, is expected to monitor compliance with Constitutional provisions across the public 
              and private sectors, to receive complaints from the public and to take appropriate action where necessary.
            </p>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              Apart from Constitutional provisions, the ZMC is guided by the Freedom of Information Act (FOIA) [Chapter 10:33] and 
              the Zimbabwe Media Commission Act [Chapter 10:35]. Under the FOIA, the Commission is required to review decisions 
              relating to access to information held by public entities and to rectify any failure to meet prescribed standards. The 
              Commission receives appeals from members of the public who are denied access to requested information as per the 
              provisions of the law.
            </p>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              The ZMC Act also requires the Commission to investigate any action that threatens freedom of the press and of expression. 
              The Commission can institute inquiries and investigations on any development that threatens the right to free expression 
              and the right of the media as provided by Section 61 of the Constitution.
            </p>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              In line with Statutory Instrument 169C of 2002 and Statutory Instrument 10 of 2004, the Commission registers all mass 
              media services and accredits journalists who choose to be accredited. The Commission keeps a register of both accredited 
              media practitioners and registered mass media services.
            </p>
            <p className="text-base leading-relaxed mt-4" style={{ color: "var(--neutral-700)" }}>
              Through the ZMC Act the Commission administers the Media Fund whose major purpose is the standardization of mass media 
              services in Zimbabwe.
            </p>
          </div>

          {/* Mission, Vision, Objective */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {[
              {
                icon: Target,
                title: t.pages.about.ourMission,
                content: t.pages.about.missionText,
                color: "var(--primary)",
                bg: "var(--primary-lighter)",
              },
              {
                icon: Eye,
                title: t.pages.about.ourVision,
                content: t.pages.about.visionText,
                color: "var(--blue)",
                bg: "var(--blue-light)",
              },
              {
                icon: Heart,
                title: "Our Strategic Objective",
                content: "To facilitate the development of an accessible and professional media",
                color: "var(--accent-dark)",
                bg: "var(--accent-soft)",
              },
            ].map((item, index) => (
              <div
                key={index}
                className="p-7 rounded-2xl transition-all hover:-translate-y-1"
                style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-200)" }}
              >
                <div
                  className="w-[70px] h-[70px] rounded-full flex items-center justify-center mx-auto mb-4"
                  style={{ background: item.bg, color: item.color }}
                >
                  <item.icon className="w-8 h-8" />
                </div>
                <h3 className="text-center text-xl mb-3" style={{ color: "var(--neutral-800)" }}>
                  {item.title}
                </h3>
                <p className="text-center text-[0.95rem]" style={{ color: "var(--neutral-600)" }}>
                  {item.content}
                </p>
              </div>
            ))}
          </div>

          {/* Functions of the ZMC - Section 249 */}
          <div
            className="bg-white rounded-[20px] p-6 md:p-10 mb-8 transition-all"
            style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
          >
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
              Functions of the ZMC
            </h2>
            <p className="text-sm md:text-base mb-6" style={{ color: "var(--neutral-600)" }}>
              Section 249 of the Constitution spells out the functions of the Commission as follows:
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
              {functions.map((func, index) => (
                <div
                  key={index}
                  className="flex gap-4 p-4 rounded-xl transition-all hover:bg-[var(--primary-soft)]"
                  style={{ background: "var(--neutral-50)" }}
                >
                  <div
                    className="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                    style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                  >
                    <func.icon className="w-6 h-6" />
                  </div>
                  <div>
                    <h4 className="text-base font-semibold mb-1" style={{ color: "var(--neutral-800)", fontFamily: "var(--font-sans)" }}>
                      {func.title}
                    </h4>
                    <p className="text-[0.9rem] m-0" style={{ color: "var(--neutral-600)" }}>
                      {func.desc}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* CTA */}
          <div
            className="rounded-[20px] p-12 text-center relative overflow-hidden"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
          >
            <div className="absolute top-0 right-0 w-1/2 h-full" style={{ background: "radial-gradient(circle at 70% 30%, rgba(255,255,255,0.15) 0%, transparent 50%)" }} />
            <h3 className="text-white mb-4 relative">Want to Learn More?</h3>
            <p className="text-white/90 mb-6 relative">
              Explore our services or get in touch with us
            </p>
            <div className="flex gap-4 justify-center relative flex-wrap">
              <a
                href="https://zmc--portal.replit.app/"
                target="_blank"
                rel="noopener noreferrer"
                className="py-3 px-8 rounded-xl font-semibold text-base border-none cursor-pointer transition-all hover:-translate-y-0.5 no-underline"
                style={{
                  background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)",
                  color: "var(--zim-black)",
                }}
                data-testid="button-about-accreditation"
              >
                Get Accredited
              </a>
              <button
                onClick={() => onNavigate("contact")}
                className="py-3 px-8 rounded-xl font-semibold text-base cursor-pointer transition-all hover:-translate-y-0.5"
                style={{
                  background: "transparent",
                  border: "2px solid rgba(255,255,255,0.3)",
                  color: "white",
                }}
                data-testid="button-about-contact"
              >
                Contact Us
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
