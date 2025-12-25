import { Users, Briefcase, Shield, FileText, Scale, Megaphone, Building2, GraduationCap, Coins, Monitor } from "lucide-react";

interface SecretariatPageProps {
  onNavigate: (page: string) => void;
}

export default function SecretariatPage({ onNavigate }: SecretariatPageProps) {
  const secretariatTeam = {
    head: {
      title: "Secretary",
      scope: "Chief Executive Officer responsible for administration and policy implementation",
      icon: Briefcase,
    },
    departments: [
      {
        title: "Legal Services",
        head: "Director - Legal Services",
        description: "Handles legal matters, complaints adjudication, media law compliance, and regulatory enforcement.",
        icon: Scale,
        functions: ["Complaints handling", "Legal advisory", "Regulatory compliance", "Appeals processing"],
      },
      {
        title: "Corporate Services",
        head: "Director - Corporate Services",
        description: "Manages human resources, finance, procurement, and administrative functions.",
        icon: Building2,
        functions: ["Human resources", "Finance management", "Procurement", "Administration"],
      },
      {
        title: "Accreditation & Registration",
        head: "Director - Accreditation",
        description: "Oversees media practitioner accreditation and media house registration processes.",
        icon: Shield,
        functions: ["Journalist accreditation", "Media house registration", "Renewals", "Database management"],
      },
      {
        title: "Research & Development",
        head: "Director - Research",
        description: "Conducts media research, policy development, and industry analysis.",
        icon: FileText,
        functions: ["Media research", "Policy development", "Industry analysis", "Publications"],
      },
      {
        title: "Communications & Outreach",
        head: "Director - Communications",
        description: "Manages public relations, media liaison, and stakeholder engagement.",
        icon: Megaphone,
        functions: ["Public relations", "Media liaison", "Stakeholder engagement", "Events coordination"],
      },
      {
        title: "ICT & Digital Services",
        head: "Director - ICT",
        description: "Manages information technology infrastructure and digital transformation initiatives.",
        icon: Monitor,
        functions: ["IT infrastructure", "Digital systems", "Cybersecurity", "Technical support"],
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
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">The Secretariat</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Administrative structure of the Zimbabwe Media Commission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem] flex-wrap">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("about"); }} className="text-white/70 hover:text-white">About</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Secretariat</span>
        </div>
      </div>

      {/* Organogram Section */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1200px] mx-auto">
          {/* Introduction */}
          <div className="text-center mb-12 md:mb-16">
            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }}>Organizational Structure</h2>
            <p className="max-w-[700px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Secretariat is headed by the Secretary who is the Chief Executive Officer. The Secretariat implements 
              the policies and decisions of the Commission and manages day-to-day operations.
            </p>
          </div>

          {/* Organogram */}
          <div className="relative">
            {/* Secretary - Top Level */}
            <div className="flex justify-center mb-8 md:mb-12">
              <div
                className="bg-white rounded-2xl p-6 md:p-8 text-center max-w-[350px] w-full relative"
                style={{ 
                  boxShadow: "0 8px 32px rgba(27, 94, 32, 0.15)", 
                  border: "3px solid var(--primary)",
                }}
              >
                <div 
                  className="w-16 h-16 md:w-20 md:h-20 rounded-full mx-auto mb-4 flex items-center justify-center"
                  style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                >
                  <Briefcase className="w-8 h-8 md:w-10 md:h-10 text-white" />
                </div>
                <h3 className="text-lg md:text-xl mb-2" style={{ color: "var(--primary-dark)", fontFamily: "var(--font-serif)" }}>
                  {secretariatTeam.head.title}
                </h3>
                <p className="text-xs md:text-sm mb-3" style={{ color: "var(--neutral-600)" }}>
                  {secretariatTeam.head.scope}
                </p>
                <span 
                  className="inline-block py-1 px-3 rounded-full text-xs font-semibold"
                  style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
                >
                  Chief Executive
                </span>
              </div>
            </div>

            {/* Connecting Line */}
            <div className="hidden md:flex justify-center mb-4">
              <div className="w-1 h-12" style={{ background: "var(--primary-light)" }} />
            </div>

            {/* Horizontal Line for Desktop */}
            <div className="hidden md:block relative mb-4">
              <div 
                className="absolute left-[8.33%] right-[8.33%] h-1 top-0"
                style={{ background: "var(--primary-light)" }}
              />
            </div>

            {/* Departments Grid */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
              {secretariatTeam.departments.map((dept, index) => {
                const IconComponent = dept.icon;
                return (
                <div
                  key={index}
                  className="bg-white rounded-xl p-5 md:p-6 relative transition-all hover:-translate-y-1"
                  style={{ 
                    boxShadow: "var(--shadow-md)", 
                    border: "1px solid var(--neutral-200)",
                  }}
                >
                  {/* Connecting line for desktop */}
                  <div 
                    className="hidden md:block absolute -top-4 left-1/2 w-1 h-4"
                    style={{ background: "var(--primary-light)", transform: "translateX(-50%)" }}
                  />
                  
                  <div 
                    className="w-12 h-12 md:w-14 md:h-14 rounded-xl mb-4 flex items-center justify-center"
                    style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                  >
                    <IconComponent className="w-6 h-6 md:w-7 md:h-7" />
                  </div>
                  <h4 className="text-base md:text-lg mb-2" style={{ color: "var(--primary-dark)", fontFamily: "var(--font-serif)" }}>
                    {dept.title}
                  </h4>
                  <p className="text-xs md:text-sm font-medium mb-2" style={{ color: "var(--primary)" }}>
                    {dept.head}
                  </p>
                  <p className="text-xs md:text-sm mb-4" style={{ color: "var(--neutral-600)" }}>
                    {dept.description}
                  </p>
                  <div className="flex flex-wrap gap-1.5">
                    {dept.functions.map((func, idx) => (
                      <span 
                        key={idx}
                        className="py-1 px-2 rounded-md text-[10px] md:text-xs"
                        style={{ background: "var(--neutral-100)", color: "var(--neutral-700)" }}
                      >
                        {func}
                      </span>
                    ))}
                  </div>
                </div>
              );
              })}
            </div>
          </div>

          {/* Additional Info */}
          <div 
            className="mt-12 md:mt-16 p-6 md:p-10 rounded-2xl text-center"
            style={{ background: "var(--primary-lighter)", border: "1px solid var(--primary-light)" }}
          >
            <GraduationCap className="w-10 h-10 md:w-12 md:h-12 mx-auto mb-4" style={{ color: "var(--primary)" }} />
            <h3 className="mb-4 text-lg md:text-xl" style={{ color: "var(--primary-dark)" }}>Our Commitment</h3>
            <p className="max-w-[600px] mx-auto text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              The Secretariat is committed to providing efficient and professional services to all stakeholders 
              in the media industry. We strive to uphold the principles of transparency, accountability, and 
              excellence in all our operations.
            </p>
            <button
              onClick={() => onNavigate("contact")}
              className="mt-6 py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white transition-all hover:-translate-y-0.5 active:scale-95"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="button-contact-secretariat"
            >
              Contact the Secretariat
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
