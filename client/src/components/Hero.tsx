import { Newspaper, Building2, Scale, GraduationCap } from "lucide-react";
import zmcBuilding from "@assets/486716629_1064504462365594_5654935487513045845_n_1766177505816.jpg";

interface HeroProps {
  onNavigate: (page: string) => void;
}

export default function Hero({ onNavigate }: HeroProps) {
  const stats = [
    { icon: Newspaper, number: "500+", label: "Accredited Journalists" },
    { icon: Building2, number: "150+", label: "Registered Media Houses" },
    { icon: Scale, number: "1000+", label: "Complaints Resolved" },
    { icon: GraduationCap, number: "50+", label: "Training Programs" },
  ];

  return (
    <>
      <section
        className="min-h-[100svh] flex items-center relative overflow-hidden pt-[100px] md:pt-[130px] pb-8 md:pb-16 px-4 md:px-8"
      >
        <div 
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url(${zmcBuilding})` }}
        />
        <div 
          className="absolute inset-0"
          style={{ background: "linear-gradient(135deg, rgba(27, 94, 32, 0.88) 0%, rgba(13, 59, 16, 0.92) 100%)" }}
        />

        <div className="max-w-[1200px] mx-auto w-full relative z-10">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            <div className="text-white animate-fadeIn text-center lg:text-left">
              <h1 className="mb-4 md:mb-6 leading-tight text-3xl md:text-4xl lg:text-5xl">
                <span className="text-white/90">Welcome to</span>
                <br />
                <span className="text-white">Zimbabwe Media Commission</span>
              </h1>
              <p className="text-base md:text-xl text-white/85 mb-6 md:mb-8 max-w-lg mx-auto lg:mx-0">
                Promoting and protecting freedom of expression and the media.
              </p>
              <div className="flex gap-3 md:gap-4 flex-wrap justify-center lg:justify-start">
                <button
                  onClick={() => onNavigate("about")}
                  className="py-3 md:py-4 px-6 md:px-8 rounded-xl font-semibold text-sm md:text-base border-none cursor-pointer transition-all hover:-translate-y-0.5 text-white"
                  style={{
                    background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
                    boxShadow: "0 4px 15px rgba(27, 94, 32, 0.4)",
                  }}
                  data-testid="button-learn-more"
                >
                  Learn More
                </button>
                <button
                  onClick={() => onNavigate("accreditation")}
                  className="py-3 md:py-4 px-6 md:px-8 rounded-xl font-bold text-sm md:text-base border-none cursor-pointer transition-all hover:-translate-y-0.5"
                  style={{
                    background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)",
                    color: "var(--zim-black)",
                    boxShadow: "0 4px 15px rgba(212, 175, 55, 0.4)",
                  }}
                  data-testid="button-get-accredited"
                >
                  Get Accredited
                </button>
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
                <h3 className="text-white text-xl md:text-2xl mb-4 md:mb-6 text-center">Quick Stats</h3>
                <div className="grid grid-cols-2 gap-3 md:gap-4">
                  {[
                    { number: "500+", label: "Accredited Journalists" },
                    { number: "150+", label: "Registered Media" },
                    { number: "20+", label: "Years of Service" },
                    { number: "100%", label: "Commitment" },
                  ].map((stat, index) => (
                    <div
                      key={index}
                      className="p-3 md:p-4 rounded-xl text-center"
                      style={{ background: "rgba(255, 255, 255, 0.1)" }}
                    >
                      <div
                        className="text-2xl md:text-3xl font-bold mb-1"
                        style={{ color: "var(--accent)", fontFamily: "var(--font-serif)" }}
                      >
                        {stat.number}
                      </div>
                      <div className="text-xs md:text-sm text-white/80">{stat.label}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Bar */}
      <section className="py-8 md:py-12 px-4 md:px-8" style={{ background: "var(--white)" }}>
        <div className="max-w-[1200px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
          {stats.map((stat, index) => (
            <div
              key={index}
              className="text-center p-4 md:p-6 rounded-xl md:rounded-2xl transition-all hover:scale-105"
              style={{
                background: "var(--primary-lighter)",
                border: "1px solid var(--neutral-200)",
              }}
              data-testid={`stat-box-${index}`}
            >
              <div className="mb-2 md:mb-3 animate-float" style={{ color: "var(--primary)" }}>
                <stat.icon className="w-8 h-8 md:w-10 md:h-10 mx-auto" />
              </div>
              <div
                className="text-2xl md:text-[2.75rem] font-bold"
                style={{ color: "var(--primary)", fontFamily: "var(--font-serif)" }}
              >
                {stat.number}
              </div>
              <div className="text-xs md:text-[0.9rem]" style={{ color: "var(--neutral-500)" }}>
                {stat.label}
              </div>
            </div>
          ))}
        </div>
      </section>
    </>
  );
}
