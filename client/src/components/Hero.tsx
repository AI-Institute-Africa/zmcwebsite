import { Newspaper, Building2, Scale, GraduationCap } from "lucide-react";
import zmcBuilding from "@assets/486716629_1064504462365594_5654935487513045845_n_1766177505816.jpg";
import { useLanguage } from "../i18n/LanguageContext";

interface HeroProps {
  onNavigate: (page: string) => void;
}

export default function Hero({ onNavigate }: HeroProps) {
  const { t } = useLanguage();

  const stats = [
    { icon: Newspaper, number: "500+", label: t.hero.accreditedJournalists },
    { icon: Building2, number: "150+", label: t.hero.registeredMediaHouses },
    { icon: Scale, number: "1000+", label: t.hero.complaintsResolved },
    { icon: GraduationCap, number: "50+", label: t.hero.trainingPrograms },
  ];

  return (
    <>
      <section className="min-h-[100svh] flex items-center relative overflow-hidden pt-[140px] md:pt-[180px] pb-8 md:pb-16 px-4 md:px-8">
        <div className="absolute inset-0" style={{ background: "#0a1a0f" }} />

        <img
          src={zmcBuilding}
          alt=""
          className="absolute inset-0 w-full h-full"
          style={{
            objectFit: "cover",
            objectPosition: "center",
            filter: "saturate(1.4) contrast(1.1) brightness(1.05)",
          }}
        />

        <div
          className="absolute inset-0"
          style={{ background: "linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.45) 100%)" }}
        />

        <div className="max-w-[1200px] mx-auto w-full relative z-10">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <div className="text-white animate-fadeIn text-center lg:text-left">
              <h1 className="mb-4 md:mb-6 leading-tight text-3xl md:text-4xl lg:text-5xl">
                <span className="text-white/90">{t.hero.welcomeTo}</span>
                <br />
                <span className="text-white">{t.hero.title}</span>
              </h1>
              <p className="text-base md:text-xl text-white/85 mb-6 md:mb-8 max-w-lg mx-auto lg:mx-0">
                {t.hero.subtitle}
              </p>
              <div className="flex gap-3 md:gap-4 flex-wrap justify-center lg:justify-start">
                <button
                  onClick={() => onNavigate("about")}
                  className="py-3 md:py-4 px-6 md:px-8 rounded-xl font-bold text-sm md:text-base border-none cursor-pointer transition-all hover:-translate-y-0.5 text-white uppercase tracking-wide"
                  style={{
                    background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
                    boxShadow: "0 4px 15px rgba(46, 125, 86, 0.4)",
                  }}
                  data-testid="button-learn-more"
                >
                  {t.hero.learnMore}
                </button>
                <a
                  href="https://zmc--portal.replit.app/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="py-3 md:py-4 px-6 md:px-8 rounded-xl font-bold text-sm md:text-base border-none cursor-pointer transition-all hover:-translate-y-0.5 no-underline uppercase tracking-wide"
                  style={{
                    background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)",
                    color: "var(--zim-black)",
                    boxShadow: "0 4px 15px rgba(212, 175, 55, 0.4)",
                  }}
                  data-testid="button-get-accredited"
                >
                  {t.hero.getAccredited}
                </a>
              </div>
            </div>

            <div className="animate-fadeIn" style={{ animationDelay: "0.2s" }}>
              <div
                className="p-5 md:p-8 rounded-2xl md:rounded-3xl"
                style={{
                  background: "rgba(255, 255, 255, 0.1)",
                  backdropFilter: "blur(10px)",
                  border: "1px solid rgba(255, 255, 255, 0.2)",
                }}
              >
                <h3 className="text-white text-xl md:text-2xl mb-4 md:mb-6 text-center">{t.hero.quickStats}</h3>
                <div className="grid grid-cols-2 gap-3 md:gap-4">
                  {[
                    { number: "500+", label: t.hero.accreditedJournalists },
                    { number: "150+", label: t.hero.registeredMedia },
                    { number: "20+", label: t.hero.yearsOfService },
                    { number: "100%", label: t.hero.commitment },
                  ].map((stat, index) => (
                    <div
                      key={index}
                      className="p-3 md:p-5 rounded-xl text-center"
                      style={{ background: "rgba(255, 255, 255, 0.1)" }}
                    >
                      <div
                        className="text-xl md:text-3xl font-bold mb-1 md:mb-2 whitespace-nowrap"
                        style={{ color: "var(--accent)" }}
                      >
                        {stat.number}
                      </div>
                      <div className="text-[10px] md:text-sm text-white/80 leading-tight">{stat.label}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="py-8 md:py-12 px-4 md:px-8" style={{ background: "rgba(255, 255, 255, 0.7)", backdropFilter: "blur(16px)", WebkitBackdropFilter: "blur(16px)" }}>
        <div className="max-w-[1200px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
          {stats.map((stat, index) => (
            <div
              key={index}
              className="text-center p-5 md:p-6 rounded-xl md:rounded-2xl transition-all hover:scale-105"
              style={{
                background: "rgba(255, 255, 255, 0.5)",
                backdropFilter: "blur(8px)",
                WebkitBackdropFilter: "blur(8px)",
                border: "1px solid rgba(255, 255, 255, 0.6)",
              }}
              data-testid={`stat-box-${index}`}
            >
              <div className="mb-2 md:mb-3 animate-float" style={{ color: "var(--primary)" }}>
                <stat.icon className="w-7 h-7 md:w-10 md:h-10 mx-auto" />
              </div>
              <div
                className="text-lg md:text-[2.25rem] font-bold whitespace-nowrap tracking-tight"
                style={{ color: "var(--primary)" }}
              >
                {stat.number}
              </div>
              <div className="text-[11px] md:text-[0.9rem] mt-1" style={{ color: "var(--neutral-500)" }}>
                {stat.label}
              </div>
            </div>
          ))}
        </div>
      </section>
    </>
  );
}
