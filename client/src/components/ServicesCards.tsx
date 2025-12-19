import { FilePen, Building2, ClipboardList, Download, ArrowRight } from "lucide-react";

interface ServicesCardsProps {
  onNavigate: (page: string) => void;
}

export default function ServicesCards({ onNavigate }: ServicesCardsProps) {
  const services = [
    {
      icon: FilePen,
      title: "Accreditation",
      description: "Get accredited as a media practitioner and join the professional community.",
      action: "Apply Now",
      page: "accreditation",
    },
    {
      icon: Building2,
      title: "Registration",
      description: "Register your mass media service and operate legally in Zimbabwe.",
      action: "Register Now",
      page: "registration",
    },
    {
      icon: ClipboardList,
      title: "Complaints & Appeals",
      description: "Download forms to lodge complaints against media breaches or appeal decisions.",
      action: "Download Forms",
      page: "complaints",
    },
    {
      icon: Download,
      title: "Downloads",
      description: "Access forms, policies, and other important documents.",
      action: "Browse Downloads",
      page: "downloads",
    },
  ];

  return (
    <section className="py-20 px-8" style={{ background: "var(--white)" }}>
      <div className="max-w-[1200px] mx-auto">
        <div className="text-center mb-12">
          <h2>Our Services</h2>
          <p style={{ color: "var(--neutral-500)", maxWidth: "600px", margin: "0 auto" }}>
            Explore what we offer to the media industry
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-7">
          {services.map((service, index) => (
            <div
              key={index}
              onClick={() => onNavigate(service.page)}
              className="p-9 rounded-[20px] cursor-pointer transition-all relative overflow-hidden group hover:-translate-y-2"
              style={{
                background: "var(--white)",
                border: "1px solid var(--neutral-200)",
              }}
              data-testid={`service-card-${service.page}`}
            >
              <div
                className="absolute top-0 left-0 right-0 h-1 transition-transform origin-left scale-x-0 group-hover:scale-x-100"
                style={{
                  background: "linear-gradient(90deg, var(--primary), var(--accent))",
                }}
              />
              <div
                className="w-16 h-16 rounded-2xl flex items-center justify-center mb-5 transition-all group-hover:scale-110"
                style={{
                  background: "var(--primary-lighter)",
                  color: "var(--primary)",
                }}
              >
                <service.icon className="w-7 h-7 group-hover:text-white" />
              </div>
              <h3 className="text-[1.35rem] mb-3" style={{ color: "var(--neutral-800)" }}>
                {service.title}
              </h3>
              <p className="text-[0.95rem] mb-5" style={{ color: "var(--neutral-500)" }}>
                {service.description}
              </p>
              <span
                className="flex items-center gap-2 font-semibold text-[0.9rem] transition-all group-hover:gap-3"
                style={{ color: "var(--primary)" }}
              >
                {service.action}
                <ArrowRight className="w-4 h-4" />
              </span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
