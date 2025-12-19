import { Newspaper, Building2, Scale, GraduationCap } from "lucide-react";

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
        className="min-h-screen flex items-center relative overflow-hidden"
        style={{
          background: "linear-gradient(135deg, rgba(27, 94, 32, 0.85) 0%, rgba(13, 59, 16, 0.9) 100%)",
          padding: "8rem 2rem 4rem",
        }}
      >
        <div className="absolute inset-0 opacity-5">
          <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse">
                <path d="M50 0L100 50L50 100L0 50Z" fill="none" stroke="rgba(255,255,255,0.1)" strokeWidth="0.5"/>
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grain)"/>
          </svg>
        </div>

        <div className="max-w-[1200px] mx-auto w-full relative z-10">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div className="text-white animate-fadeIn">
              <h1 className="mb-6 leading-tight">
                <span className="text-white/90">Welcome to</span>
                <br />
                <span className="text-white">Zimbabwe Media Commission</span>
              </h1>
              <p className="text-xl text-white/85 mb-8 max-w-lg">
                Promoting and protecting freedom of expression and the media.
              </p>
              <div className="flex gap-4 flex-wrap">
                <button
                  onClick={() => onNavigate("about")}
                  className="py-4 px-8 rounded-xl font-semibold text-base border-none cursor-pointer transition-all hover:-translate-y-0.5 text-white"
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
                  className="py-4 px-8 rounded-xl font-bold text-base border-none cursor-pointer transition-all hover:-translate-y-0.5"
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
                className="p-8 rounded-3xl"
                style={{
                  background: "rgba(255, 255, 255, 0.1)",
                  backdropFilter: "blur(10px)",
                  border: "1px solid rgba(255, 255, 255, 0.2)",
                }}
              >
                <h3 className="text-white text-2xl mb-6 text-center">Quick Stats</h3>
                <div className="grid grid-cols-2 gap-4">
                  {[
                    { number: "500+", label: "Accredited Journalists" },
                    { number: "150+", label: "Registered Media" },
                    { number: "20+", label: "Years of Service" },
                    { number: "100%", label: "Commitment" },
                  ].map((stat, index) => (
                    <div
                      key={index}
                      className="p-4 rounded-xl text-center"
                      style={{ background: "rgba(255, 255, 255, 0.1)" }}
                    >
                      <div
                        className="text-3xl font-bold mb-1"
                        style={{ color: "var(--accent)", fontFamily: "var(--font-serif)" }}
                      >
                        {stat.number}
                      </div>
                      <div className="text-sm text-white/80">{stat.label}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Bar */}
      <section style={{ background: "var(--white)", padding: "3rem 2rem" }}>
        <div className="max-w-[1200px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-6">
          {stats.map((stat, index) => (
            <div
              key={index}
              className="text-center p-6 rounded-2xl transition-all hover:scale-105"
              style={{
                background: "var(--primary-lighter)",
                border: "1px solid var(--neutral-200)",
              }}
              data-testid={`stat-box-${index}`}
            >
              <div className="mb-3 animate-float" style={{ color: "var(--primary)" }}>
                <stat.icon className="w-10 h-10 mx-auto" />
              </div>
              <div
                className="text-[2.75rem] font-bold"
                style={{ color: "var(--primary)", fontFamily: "var(--font-serif)" }}
              >
                {stat.number}
              </div>
              <div className="text-[0.9rem]" style={{ color: "var(--neutral-500)" }}>
                {stat.label}
              </div>
            </div>
          ))}
        </div>
      </section>
    </>
  );
}
