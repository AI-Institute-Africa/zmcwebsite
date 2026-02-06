import { GraduationCap, User } from "lucide-react";

interface SecretariatPageProps {
  onNavigate: (page: string) => void;
}

export default function SecretariatPage({ onNavigate }: SecretariatPageProps) {
  const secretariatTeam = {
    head: {
      title: "Secretary",
      role: "Chief Executive Officer",
      scope: "Responsible for administration and policy implementation",
    },
    directors: [
      {
        title: "Principal Director",
        role: "Deputy Secretary",
        scope: "Supports the Secretary in strategic and operational matters",
      },
    ],
    managers: [
      {
        title: "Director",
        department: "Public Information Compliance and Promotion",
      },
      {
        title: "Director",
        department: "Media Development and Governance",
      },
      {
        title: "Director",
        department: "Finance, Administration and HR",
      },
      {
        title: "Director",
        department: "Legal and Corporate Affairs",
      },
    ],
    staff: [
      { title: "Manager", department: "Public Information Compliance" },
      { title: "Registrar", department: "Registration & Accreditation" },
      { title: "Manager", department: "Research, Training and Development" },
      { title: "Chief Accountant", department: "Finance" },
      { title: "Manager", department: "Human Resources" },
      { title: "Manager", department: "IT" },
      { title: "Manager", department: "Admin" },
      { title: "Manager", department: "Communications and PR" },
      { title: "Manager", department: "Audit" },
      { title: "Manager", department: "Procurement" },
      { title: "Manager", department: "Legal Services" },
    ],
  };

  const PersonCard = ({ title, subtitle, scope, size = "md", highlight = false }: {
    title: string;
    subtitle?: string;
    scope?: string;
    size?: "lg" | "md" | "sm";
    highlight?: boolean;
  }) => {
    const avatarSize = size === "lg" ? "w-24 h-24 md:w-28 md:h-28" : size === "md" ? "w-16 h-16 md:w-20 md:h-20" : "w-12 h-12 md:w-14 md:h-14";
    const iconSize = size === "lg" ? "w-12 h-12 md:w-14 md:h-14" : size === "md" ? "w-8 h-8 md:w-10 md:h-10" : "w-6 h-6 md:w-7 md:h-7";
    
    return (
      <div
        className={`bg-white rounded-2xl text-center transition-all hover:-translate-y-1 ${size === "lg" ? "p-6 md:p-8" : size === "md" ? "p-4 md:p-6" : "p-3 md:p-4"}`}
        style={{
          boxShadow: highlight ? "0 8px 32px rgba(27, 94, 32, 0.15)" : "var(--shadow-sm)",
          border: highlight ? "3px solid var(--primary)" : "1px solid var(--neutral-200)",
        }}
      >
        <div
          className={`${avatarSize} rounded-full mx-auto mb-3 flex items-center justify-center overflow-hidden`}
          style={{ 
            background: highlight 
              ? "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" 
              : "var(--neutral-100)",
          }}
        >
          <User className={`${iconSize} ${highlight ? "text-white" : ""}`} style={highlight ? {} : { color: "var(--neutral-400)" }} />
        </div>
        <p className="text-xs mb-1 font-medium" style={{ color: "var(--accent-dark)" }}>Photo Coming Soon</p>
        <h4 
          className={`mb-1 ${size === "lg" ? "text-lg md:text-xl" : size === "md" ? "text-base md:text-lg" : "text-sm md:text-base"}`} 
          style={{ color: "var(--primary-dark)", fontFamily: "var(--font-serif)" }}
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
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-home">Home</a>
          <span className="text-white/70">/</span>
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("about"); }} className="text-white/70 hover:text-white" data-testid="link-breadcrumb-about">About</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Secretariat</span>
        </div>
      </div>

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

          {/* Secretary - Top Level */}
          <div className="flex justify-center mb-8 md:mb-10">
            <div className="max-w-[380px] w-full">
              <PersonCard 
                title={secretariatTeam.head.title} 
                subtitle={secretariatTeam.head.role} 
                scope={secretariatTeam.head.scope} 
                size="lg" 
                highlight 
              />
            </div>
          </div>

          {/* Connecting Line */}
          <div className="hidden md:flex justify-center mb-6">
            <div className="w-0.5 h-8" style={{ background: "var(--primary-light)" }} />
          </div>

          {/* Principal Director */}
          <div className="flex justify-center mb-8 md:mb-10">
            <div className="max-w-[340px] w-full">
              <PersonCard 
                title={secretariatTeam.directors[0].title} 
                subtitle={secretariatTeam.directors[0].role} 
                scope={secretariatTeam.directors[0].scope} 
                size="md" 
                highlight 
              />
            </div>
          </div>

          {/* Connecting Line */}
          <div className="hidden md:flex justify-center mb-6">
            <div className="w-0.5 h-8" style={{ background: "var(--primary-light)" }} />
          </div>

          {/* Directors */}
          <div className="mb-10">
            <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>
              Directors
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
              {secretariatTeam.managers.map((person, index) => (
                <PersonCard 
                  key={index} 
                  title={person.title} 
                  subtitle={person.department} 
                  size="md" 
                />
              ))}
            </div>
          </div>

          {/* Connecting Line */}
          <div className="hidden md:flex justify-center mb-6">
            <div className="w-0.5 h-8" style={{ background: "var(--neutral-300)" }} />
          </div>

          {/* Managers / Staff */}
          <div className="mb-12">
            <h3 className="text-center text-lg md:text-xl mb-6" style={{ color: "var(--primary-dark)" }}>
              Managers & Senior Staff
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {secretariatTeam.staff.map((person, index) => (
                <PersonCard 
                  key={index} 
                  title={person.title} 
                  subtitle={person.department} 
                  size="sm" 
                />
              ))}
            </div>
          </div>

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
            <button
              onClick={() => onNavigate("contact")}
              className="mt-6 py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white transition-all hover:-translate-y-0.5"
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
