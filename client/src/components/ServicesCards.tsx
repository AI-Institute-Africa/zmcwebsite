import { FilePen, Building2, Scale, Download, ArrowRight, MessageSquareWarning } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";

interface ServicesCardsProps {
  onNavigate: (page: string) => void;
}

export default function ServicesCards({ onNavigate }: ServicesCardsProps) {
  const { t } = useLanguage();

  const PORTAL_URL = "https://zmc--portal.replit.app/";
  const services = [
    { icon: FilePen, title: t.services.accreditationTitle, description: t.services.accreditationDesc, action: t.services.applyNow, page: "accreditation", external: PORTAL_URL },
    { icon: Building2, title: t.services.registrationTitle, description: t.services.registrationDesc, action: t.services.registerNow, page: "registration", external: PORTAL_URL },
    { icon: MessageSquareWarning, title: t.services.complaintsTitle, description: t.services.complaintsDesc, action: t.services.fileComplaint, page: "complaints", external: null },
    { icon: Scale, title: t.services.appealsTitle, description: t.services.appealsDesc, action: t.services.fileAppeal, page: "appeals", external: null },
    { icon: Download, title: t.services.downloadsTitle, description: t.services.downloadsDesc, action: t.services.browseDownloads, page: "downloads", external: null },
  ];

  return (
    <section className="py-20 px-8" style={{ background: "var(--white)" }}>
      <div className="max-w-[1200px] mx-auto">
        <div className="text-center mb-12">
          <h2>{t.services.title}</h2>
          <p style={{ color: "var(--neutral-500)", maxWidth: "600px", margin: "0 auto" }}>
            {t.services.subtitle}
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-7">
          {services.map((service, index) => (
            <div
              key={index}
              onClick={() => {
                if (service.external) {
                  window.open(service.external, "_blank", "noopener,noreferrer");
                } else {
                  onNavigate(service.page);
                }
              }}
              className="p-7 rounded-[20px] cursor-pointer transition-all relative overflow-hidden group hover:-translate-y-2"
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
                className="w-14 h-14 rounded-2xl flex items-center justify-center mb-5 transition-all group-hover:scale-110"
                style={{
                  background: "var(--primary-lighter)",
                  color: "var(--primary)",
                }}
              >
                <service.icon className="w-6 h-6 group-hover:text-white" />
              </div>
              <h3 className="text-[1.1rem] mb-3" style={{ color: "var(--neutral-800)" }}>
                {service.title}
              </h3>
              <p className="text-[0.88rem] mb-5 leading-relaxed" style={{ color: "var(--neutral-500)" }}>
                {service.description}
              </p>
              <span
                className="flex items-center gap-2 font-semibold text-[0.85rem] transition-all group-hover:gap-3"
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
