import { Target, Eye, Heart, Users, Shield, Award } from "lucide-react";

interface AboutPageProps {
  onNavigate: (page: string) => void;
}

export default function AboutPage({ onNavigate }: AboutPageProps) {
  return (
    <div className="animate-fadeIn pt-[130px]">
      {/* Page Header */}
      <div
        className="py-16 px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <div className="absolute top-0 right-0 w-1/2 h-full" style={{ background: "radial-gradient(circle at 70% 50%, rgba(255,255,255,0.1) 0%, transparent 50%)" }} />
        <h1 className="text-white mb-3 relative">About ZMC</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-lg relative">
          Learn about the Zimbabwe Media Commission and our mission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem]">
          <a
            href="#"
            onClick={(e) => { e.preventDefault(); onNavigate("home"); }}
            className="text-white/70 hover:text-white"
          >
            Home
          </a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>About ZMC</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-16 px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* Introduction */}
          <div
            className="bg-white rounded-[20px] p-10 mb-8 transition-all"
            style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
          >
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
              Who We Are
            </h2>
            <p className="text-lg leading-relaxed">
              The Zimbabwe Media Commission (ZMC) is a constitutional body established under Section 249 of the Constitution of Zimbabwe to uphold, promote and develop freedom of the media. The Commission is independent and not subject to the direction or control of anyone.
            </p>
            <p className="leading-relaxed">
              ZMC is mandated to register and accredit media practitioners, receive and process complaints from the public, promote ethical conduct in the media, and ensure that the media covers all aspects of Zimbabwean society in a fair and balanced manner.
            </p>
          </div>

          {/* Mission, Vision, Values */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {[
              {
                icon: Target,
                title: "Our Mission",
                content: "To promote and protect freedom of expression and the media while ensuring responsible journalism and a well-informed public.",
                color: "var(--primary)",
                bg: "var(--primary-lighter)",
              },
              {
                icon: Eye,
                title: "Our Vision",
                content: "A media environment that is free, responsible, and accountable, contributing to a democratic and informed society.",
                color: "var(--blue)",
                bg: "var(--blue-light)",
              },
              {
                icon: Heart,
                title: "Our Values",
                content: "Integrity, Independence, Professionalism, Accountability, Transparency, and Respect for Human Rights.",
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

          {/* Functions */}
          <div
            className="bg-white rounded-[20px] p-10 mb-8 transition-all"
            style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
          >
            <h2 style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
              Our Functions
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
              {[
                { icon: Users, title: "Media Accreditation", desc: "Accredit journalists and other media practitioners" },
                { icon: Shield, title: "Media Registration", desc: "Register mass media services and products" },
                { icon: Award, title: "Ethical Standards", desc: "Promote high professional and ethical standards" },
                { icon: Target, title: "Complaints Handling", desc: "Receive and process complaints from the public" },
              ].map((func, index) => (
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
                href="https://zmc-portalfinal--devgithub1.replit.app"
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
