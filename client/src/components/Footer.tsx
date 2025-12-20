import { Link } from "wouter";
import { MapPin, Phone, Mail, Clock, ChevronRight, Facebook, Twitter, Instagram, Youtube } from "lucide-react";

interface FooterProps {
  onNavigate: (page: string) => void;
}

export default function Footer({ onNavigate }: FooterProps) {
  const quickLinks = [
    { label: "About ZMC", path: "/about" },
    { label: "Accreditation", path: "/accreditation" },
    { label: "Registration", path: "/registration" },
    { label: "Downloads", path: "/downloads" },
    { label: "Contact Us", path: "/contact" },
  ];

  const services = [
    { label: "Media Accreditation", path: "/accreditation" },
    { label: "Media Registration", path: "/registration" },
    { label: "Complaints & Appeals", path: "/complaints" },
    { label: "Training Programs", path: "/events" },
  ];

  return (
    <footer
      className="pt-16 pb-8 px-8"
      style={{
        background: "linear-gradient(135deg, var(--neutral-800) 0%, var(--neutral-900) 100%)",
        color: "var(--neutral-300)",
      }}
    >
      <div className="max-w-[1200px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
        {/* About Section */}
        <div>
          <h4
            className="text-white mb-5 text-[1.35rem]"
            style={{ fontFamily: "var(--font-serif)" }}
          >
            Zimbabwe Media Commission
          </h4>
          <p className="text-[0.95rem] leading-relaxed" style={{ color: "var(--neutral-400)" }}>
            Promoting and protecting freedom of expression and the media. Ensuring responsible
            journalism, a well-informed public, and a diverse, plural and ethical media.
          </p>
          <div className="flex gap-4 mt-6">
            {[
              { icon: Facebook, href: "https://facebook.com" },
              { icon: Twitter, href: "https://twitter.com" },
              { icon: Instagram, href: "https://instagram.com" },
              { icon: Youtube, href: "https://youtube.com" },
            ].map((social, index) => (
              <a
                key={index}
                href={social.href}
                target="_blank"
                rel="noopener noreferrer"
                className="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:-translate-y-1"
                style={{
                  background: "var(--primary-lighter)",
                  color: "var(--primary)",
                }}
                data-testid={`social-link-${index}`}
              >
                <social.icon className="w-5 h-5" />
              </a>
            ))}
          </div>
        </div>

        {/* Quick Links */}
        <div>
          <h4
            className="text-white mb-5 text-[1.35rem]"
            style={{ fontFamily: "var(--font-serif)" }}
          >
            Quick Links
          </h4>
          <ul className="list-none">
            {quickLinks.map((link, index) => (
              <li key={index} className="mb-3">
                <Link
                  href={link.path}
                  className="flex items-center gap-2 text-[0.95rem] transition-all hover:pl-2 no-underline"
                  style={{ color: "var(--neutral-400)" }}
                  data-testid={`footer-link-${link.path.slice(1)}`}
                >
                  <ChevronRight className="w-4 h-4" />
                  {link.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        {/* Services */}
        <div>
          <h4
            className="text-white mb-5 text-[1.35rem]"
            style={{ fontFamily: "var(--font-serif)" }}
          >
            Our Services
          </h4>
          <ul className="list-none">
            {services.map((service, index) => (
              <li key={index} className="mb-3">
                <Link
                  href={service.path}
                  className="flex items-center gap-2 text-[0.95rem] transition-all hover:pl-2 no-underline"
                  style={{ color: "var(--neutral-400)" }}
                  data-testid={`footer-service-${service.path.slice(1)}`}
                >
                  <ChevronRight className="w-4 h-4" />
                  {service.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        {/* Contact Info */}
        <div>
          <h4
            className="text-white mb-5 text-[1.35rem]"
            style={{ fontFamily: "var(--font-serif)" }}
          >
            Contact Us
          </h4>
          <div className="space-y-4">
            <div className="flex items-start gap-3">
              <MapPin className="w-5 h-5 mt-1" style={{ color: "var(--accent)" }} />
              <p className="text-[0.95rem] m-0" style={{ color: "var(--neutral-400)" }}>
                108 Swan Drive,
                <br />
                Alexandra Park, Harare
              </p>
            </div>
            <div className="flex items-center gap-3">
              <Phone className="w-5 h-5 flex-shrink-0" style={{ color: "var(--accent)" }} />
              <p className="text-[0.95rem] m-0 whitespace-nowrap" style={{ color: "var(--neutral-400)" }}>
                +263 242 253509/10
              </p>
            </div>
            <div className="flex items-center gap-3">
              <Mail className="w-5 h-5" style={{ color: "var(--accent)" }} />
              <a
                href="mailto:info@zmc.org.zw"
                className="text-[0.95rem]"
                style={{ color: "var(--neutral-400)" }}
              >
                info@zmc.org.zw
              </a>
            </div>
            <div className="flex items-center gap-3">
              <Clock className="w-5 h-5 flex-shrink-0" style={{ color: "var(--accent)" }} />
              <p className="text-[0.95rem] m-0 whitespace-nowrap" style={{ color: "var(--neutral-400)" }}>
                Mon - Fri: 8:00 AM - 4:30 PM
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div
        className="mt-12 pt-8 text-center text-[0.9rem]"
        style={{
          borderTop: "1px solid var(--neutral-700)",
          color: "var(--neutral-500)",
        }}
      >
        <p className="m-0">
          &copy; {new Date().getFullYear()} Zimbabwe Media Commission. All rights reserved.
        </p>
      </div>
    </footer>
  );
}
