import { Users, Award, Scale, GraduationCap, Globe } from "lucide-react";

interface CommissionersPageProps {
  onNavigate: (page: string) => void;
}

export default function CommissionersPage({ onNavigate }: CommissionersPageProps) {
  const commissioners = {
    chairperson: {
      title: "Chairperson",
      name: "To be announced",
      description: "Leads the Board of Commissioners and presides over all Commission meetings. Provides strategic direction and oversight for the Commission's mandate.",
      qualifications: ["Media Law Expert", "Constitutional Law", "Public Administration"],
    },
    viceChairperson: {
      title: "Vice Chairperson",
      name: "To be announced",
      description: "Assists the Chairperson and assumes leadership responsibilities in the Chairperson's absence.",
      qualifications: ["Media Industry Expert", "Journalism", "Ethics"],
    },
    members: [
      {
        title: "Commissioner",
        name: "To be announced",
        portfolio: "Legal Affairs",
        description: "Oversees legal and regulatory matters, ensuring compliance with media laws.",
        qualifications: ["Legal Practice", "Media Law"],
      },
      {
        title: "Commissioner",
        name: "To be announced",
        portfolio: "Ethics & Standards",
        description: "Champions ethical journalism and professional standards in the media industry.",
        qualifications: ["Media Ethics", "Journalism"],
      },
      {
        title: "Commissioner",
        name: "To be announced",
        portfolio: "Accreditation",
        description: "Oversees the accreditation and registration processes for media practitioners.",
        qualifications: ["Human Resources", "Media Management"],
      },
      {
        title: "Commissioner",
        name: "To be announced",
        portfolio: "Research & Development",
        description: "Leads research initiatives and policy development for the media sector.",
        qualifications: ["Research", "Policy Development"],
      },
      {
        title: "Commissioner",
        name: "To be announced",
        portfolio: "Public Relations",
        description: "Manages stakeholder relations and public communications.",
        qualifications: ["Communications", "Public Relations"],
      },
    ],
  };

  return (
    <div className="animate-fadeIn pt-[100px] md:pt-[130px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Board of Commissioners</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Leadership and governance of the Zimbabwe Media Commission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("about"); }} className="text-white/70 hover:text-white">About</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Board of Commissioners</span>
        </div>
      </div>

      {/* Introduction */}
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

          {/* Organogram */}
          <div className="relative">
            {/* Chairperson - Top Level */}
            <div className="flex justify-center mb-6 md:mb-10">
              <div
                className="bg-white rounded-2xl p-6 md:p-8 text-center max-w-[400px] w-full"
                style={{ 
                  boxShadow: "0 8px 32px rgba(212, 175, 55, 0.2)", 
                  border: "3px solid var(--accent)",
                }}
              >
                <div 
                  className="w-20 h-20 md:w-24 md:h-24 rounded-full mx-auto mb-4 flex items-center justify-center"
                  style={{ background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)" }}
                >
                  <Award className="w-10 h-10 md:w-12 md:h-12" style={{ color: "var(--zim-black)" }} />
                </div>
                <span 
                  className="inline-block py-1 px-4 rounded-full text-xs font-bold mb-3"
                  style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
                >
                  {commissioners.chairperson.title}
                </span>
                <h3 className="text-lg md:text-xl mb-2" style={{ color: "var(--primary-dark)", fontFamily: "var(--font-serif)" }}>
                  {commissioners.chairperson.name}
                </h3>
                <p className="text-xs md:text-sm mb-4" style={{ color: "var(--neutral-600)" }}>
                  {commissioners.chairperson.description}
                </p>
                <div className="flex flex-wrap justify-center gap-2">
                  {commissioners.chairperson.qualifications.map((qual, idx) => (
                    <span 
                      key={idx}
                      className="py-1 px-2 rounded-md text-[10px] md:text-xs"
                      style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                    >
                      {qual}
                    </span>
                  ))}
                </div>
              </div>
            </div>

            {/* Connecting Line from Chairperson */}
            <div className="hidden md:flex justify-center">
              <div className="w-0.5 h-8" style={{ background: "var(--accent)" }} />
            </div>

            {/* Vice Chairperson */}
            <div className="flex justify-center mb-6 md:mb-8">
              <div
                className="bg-white rounded-xl p-5 md:p-6 text-center max-w-[350px] w-full"
                style={{ 
                  boxShadow: "var(--shadow-lg)", 
                  border: "2px solid var(--primary-light)",
                }}
              >
                <div 
                  className="w-14 h-14 md:w-16 md:h-16 rounded-full mx-auto mb-3 flex items-center justify-center"
                  style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                >
                  <Users className="w-7 h-7 md:w-8 md:h-8 text-white" />
                </div>
                <span 
                  className="inline-block py-1 px-3 rounded-full text-xs font-semibold mb-2"
                  style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                >
                  {commissioners.viceChairperson.title}
                </span>
                <h4 className="text-base md:text-lg mb-2" style={{ color: "var(--primary-dark)", fontFamily: "var(--font-serif)" }}>
                  {commissioners.viceChairperson.name}
                </h4>
                <p className="text-xs md:text-sm" style={{ color: "var(--neutral-600)" }}>
                  {commissioners.viceChairperson.description}
                </p>
              </div>
            </div>

            {/* Connecting Line to Commissioners */}
            <div className="hidden md:flex justify-center">
              <div className="w-0.5 h-6" style={{ background: "var(--primary-light)" }} />
            </div>

            {/* Commissioners Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 md:gap-5 pt-2">
              {commissioners.members.map((member, index) => (
                <div
                  key={index}
                  className="bg-white rounded-xl p-4 md:p-5 text-center relative transition-all hover:-translate-y-1"
                  style={{ 
                    boxShadow: "var(--shadow-sm)", 
                    border: "1px solid var(--neutral-200)",
                  }}
                >
                  
                  <div 
                    className="w-12 h-12 md:w-14 md:h-14 rounded-full mx-auto mb-3 flex items-center justify-center"
                    style={{ background: "var(--neutral-100)", color: "var(--primary)" }}
                  >
                    <GraduationCap className="w-6 h-6 md:w-7 md:h-7" />
                  </div>
                  <span 
                    className="inline-block py-0.5 px-2 rounded-md text-[10px] font-medium mb-2"
                    style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
                  >
                    {member.portfolio}
                  </span>
                  <h5 className="text-sm md:text-base mb-1" style={{ color: "var(--primary-dark)", fontFamily: "var(--font-serif)" }}>
                    {member.name}
                  </h5>
                  <p className="text-xs" style={{ color: "var(--neutral-500)" }}>
                    {member.title}
                  </p>
                </div>
              ))}
            </div>
          </div>

          {/* Powers and Functions */}
          <div className="mt-12 md:mt-16 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
              className="bg-white rounded-xl p-6 md:p-8"
              style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-200)" }}
            >
              <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Powers of the Commission</h3>
              <ul className="space-y-3">
                {[
                  "Regulate the media industry in Zimbabwe",
                  "Register and accredit media practitioners",
                  "Receive and adjudicate complaints against media",
                  "Conduct research on media issues",
                  "Promote best practices in the media industry",
                ].map((item, idx) => (
                  <li key={idx} className="flex items-start gap-3">
                    <span 
                      className="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                      style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                    >
                      {idx + 1}
                    </span>
                    <span className="text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>{item}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div 
              className="bg-white rounded-xl p-6 md:p-8"
              style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-200)" }}
            >
              <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Functions of the Commission</h3>
              <ul className="space-y-3">
                {[
                  "Promote freedom of expression and media freedom",
                  "Protect journalists from harassment",
                  "Ensure fair and accurate reporting",
                  "Uphold ethical standards in journalism",
                  "Foster a diverse and pluralistic media environment",
                ].map((item, idx) => (
                  <li key={idx} className="flex items-start gap-3">
                    <span 
                      className="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                      style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
                    >
                      {idx + 1}
                    </span>
                    <span className="text-sm md:text-base" style={{ color: "var(--neutral-700)" }}>{item}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* CTA */}
          <div className="mt-12 text-center">
            <button
              onClick={() => onNavigate("about")}
              className="py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white transition-all hover:-translate-y-0.5 active:scale-95"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-about-zmc"
            >
              Learn More About ZMC
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
