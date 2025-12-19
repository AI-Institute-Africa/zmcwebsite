import { useState, useRef, useEffect } from "react";
import { Link, useLocation } from "wouter";
import {
  Phone,
  Mail,
  Globe,
  Bell,
  Building2,
  BadgeCheck,
  ExternalLink,
  ChevronDown,
  Megaphone,
  FileText,
} from "lucide-react";
import zmcLogo from "@assets/zmc-logo_1766177505802.png";

interface HeaderProps {
  currentPage: string;
  onNavigate: (page: string) => void;
}

export default function Header({ currentPage, onNavigate }: HeaderProps) {
  const [language, setLanguage] = useState<"en" | "sn" | "nd">("en");
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [aboutOpen, setAboutOpen] = useState(false);
  const [servicesOpen, setServicesOpen] = useState(false);
  const [opportunitiesOpen, setOpportunitiesOpen] = useState(false);
  const [unreadCount, setUnreadCount] = useState(3);
  const [, setLocation] = useLocation();

  const notificationRef = useRef<HTMLDivElement>(null);

  const notifications = [
    {
      id: 1,
      icon: Megaphone,
      title: "Accreditation Renewal 2025",
      text: "Renew your accreditation before December 1st",
      time: "2 hours ago",
      unread: true,
    },
    {
      id: 2,
      icon: Building2,
      title: "New Bulawayo Office",
      text: "ZMC Regional Office now open in Bulawayo",
      time: "1 day ago",
      unread: true,
    },
    {
      id: 3,
      icon: FileText,
      title: "Policy Update",
      text: "New Sexual Harassment Policy launched",
      time: "3 days ago",
      unread: true,
    },
  ];

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (notificationRef.current && !notificationRef.current.contains(event.target as Node)) {
        setNotificationsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const closeAllDropdowns = () => {
    setAboutOpen(false);
    setServicesOpen(false);
    setOpportunitiesOpen(false);
  };

  const markAllRead = () => {
    setUnreadCount(0);
  };

  return (
    <header className="fixed w-full top-0 z-[1000] bg-white" style={{ boxShadow: "var(--shadow-md)" }}>
      {/* Top Bar */}
      <div
        className="py-3 px-8 flex justify-between items-center flex-wrap gap-4"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <div className="flex items-center gap-6 flex-wrap">
          <div className="flex gap-6 text-sm text-white/90">
            <span className="flex items-center gap-2">
              <Phone className="w-4 h-4" />
              +263 242 253509/10
            </span>
            <span className="flex items-center gap-2">
              <Mail className="w-4 h-4" />
              <a href="mailto:info@zmc.org.zw" className="text-white/90 hover:text-white">
                info@zmc.org.zw
              </a>
            </span>
          </div>

          {/* Language Toggle */}
          <div
            className="flex items-center gap-3 py-2 px-4 rounded-full cursor-pointer transition-all"
            style={{
              background: "rgba(255,255,255,0.2)",
              border: "2px solid rgba(255,255,255,0.4)",
            }}
          >
            <Globe className="w-4 h-4 text-white" />
            <span className="text-white font-semibold text-sm">Language</span>
            <div
              className="flex rounded-[20px] overflow-hidden"
              style={{ background: "rgba(0,0,0,0.2)" }}
            >
              {(["en", "sn", "nd"] as const).map((lang) => (
                <button
                  key={lang}
                  onClick={(e) => {
                    e.stopPropagation();
                    setLanguage(lang);
                  }}
                  className={`py-1.5 px-3 text-xs font-semibold border-none cursor-pointer transition-all ${
                    language === lang
                      ? "text-neutral-900"
                      : "text-white/80 hover:bg-white/10 hover:text-white"
                  }`}
                  style={{
                    background: language === lang ? "var(--accent)" : "transparent",
                  }}
                  data-testid={`button-lang-${lang}`}
                >
                  {lang.toUpperCase()}
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className="flex gap-3 items-center">
          {/* Notification Bell */}
          <div className="relative" ref={notificationRef}>
            <button
              onClick={() => setNotificationsOpen(!notificationsOpen)}
              className="w-[42px] h-[42px] rounded-full flex items-center justify-center cursor-pointer transition-all text-white hover:scale-105"
              style={{
                background: "rgba(255,255,255,0.15)",
                border: "2px solid rgba(255,255,255,0.3)",
              }}
              data-testid="button-notifications"
            >
              <Bell className="w-5 h-5" />
              {unreadCount > 0 && (
                <span
                  className="absolute -top-1.5 -right-1.5 text-white text-[0.7rem] font-bold min-w-5 h-5 rounded-full flex items-center justify-center animate-pulse-badge"
                  style={{ background: "var(--zim-red)" }}
                >
                  {unreadCount}
                </span>
              )}
            </button>

            {/* Notification Dropdown */}
            <div
              className={`absolute top-[calc(100%+10px)] right-0 w-[360px] bg-white rounded-2xl overflow-hidden z-[2000] transition-all ${
                notificationsOpen
                  ? "opacity-100 visible translate-y-0"
                  : "opacity-0 invisible -translate-y-2.5"
              }`}
              style={{ boxShadow: "var(--shadow-xl)" }}
            >
              <div
                className="flex justify-between items-center py-4 px-5 border-b"
                style={{ background: "var(--primary-lighter)", borderColor: "var(--neutral-200)" }}
              >
                <h4 className="text-base m-0" style={{ color: "var(--primary-dark)" }}>
                  Notifications
                </h4>
                <button
                  onClick={markAllRead}
                  className="bg-transparent border-none text-sm cursor-pointer font-semibold hover:underline"
                  style={{ color: "var(--primary)" }}
                  data-testid="button-mark-all-read"
                >
                  Mark all read
                </button>
              </div>
              <div className="max-h-80 overflow-y-auto">
                {notifications.map((notif) => (
                  <div
                    key={notif.id}
                    className={`flex gap-4 py-4 px-5 border-b cursor-pointer transition-all ${
                      notif.unread && unreadCount > 0 ? "hover:bg-[#FFF3CD]" : "hover:bg-neutral-50"
                    }`}
                    style={{
                      background: notif.unread && unreadCount > 0 ? "var(--accent-soft)" : "transparent",
                      borderColor: "var(--neutral-100)",
                    }}
                    data-testid={`notification-item-${notif.id}`}
                  >
                    <div
                      className="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                      style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                    >
                      <notif.icon className="w-5 h-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold text-sm mb-1" style={{ color: "var(--neutral-800)" }}>
                        {notif.title}
                      </p>
                      <p
                        className="text-xs mb-1 whitespace-nowrap overflow-hidden text-ellipsis"
                        style={{ color: "var(--neutral-600)" }}
                      >
                        {notif.text}
                      </p>
                      <span className="text-xs" style={{ color: "var(--neutral-500)" }}>
                        {notif.time}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
              <div
                className="py-3 px-5 text-center border-t"
                style={{ background: "var(--neutral-50)", borderColor: "var(--neutral-200)" }}
              >
                <Link
                  href="/events"
                  onClick={() => setNotificationsOpen(false)}
                  className="text-sm font-semibold hover:underline"
                  style={{ color: "var(--primary)" }}
                >
                  View all notifications
                </Link>
              </div>
            </div>
          </div>

          <Link
            href="/registration"
            className="py-2.5 px-5 rounded-[10px] font-semibold text-sm border-none flex items-center gap-2 text-white transition-all hover:-translate-y-0.5 no-underline"
            style={{
              background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
              boxShadow: "0 3px 12px rgba(27, 94, 32, 0.25)",
            }}
            data-testid="button-registration-header"
          >
            <Building2 className="w-4 h-4" />
            Registration
          </Link>
          <Link
            href="/accreditation"
            className="py-2.5 px-5 rounded-[10px] font-semibold text-sm border-none flex items-center gap-2 text-white transition-all hover:-translate-y-0.5 no-underline"
            style={{
              background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
              boxShadow: "0 3px 12px rgba(27, 94, 32, 0.25)",
            }}
            data-testid="button-accreditation-header"
          >
            <BadgeCheck className="w-4 h-4" />
            Accreditation
          </Link>
          <button
            onClick={() => window.open("#portal", "_blank")}
            className="py-2.5 px-5 rounded-[10px] font-bold text-sm border-none flex items-center gap-2 transition-all hover:-translate-y-0.5"
            style={{
              background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)",
              color: "var(--zim-black)",
              boxShadow: "0 3px 12px rgba(212, 175, 55, 0.3)",
            }}
            data-testid="button-portal"
          >
            <ExternalLink className="w-4 h-4" />
            Portal
          </button>
        </div>
      </div>

      {/* Main Navigation */}
      <div className="px-8 flex justify-between items-center bg-white">
        <Link href="/" className="flex items-center gap-4 py-4 no-underline">
          <img 
            src={zmcLogo} 
            alt="Zimbabwe Media Commission Logo" 
            className="w-[60px] h-[60px] object-contain"
          />
          <div className="flex flex-col">
            <span className="text-[1.4rem] font-bold leading-tight" style={{ fontFamily: "var(--font-serif)" }}>
              <span style={{ color: "var(--zim-black)" }}>Zimbabwe</span>{" "}
              <span style={{ color: "var(--primary)" }}>Media</span>{" "}
              <span style={{ color: "var(--zim-black)" }}>Commission</span>
            </span>
            <span className="text-xs uppercase tracking-wider" style={{ color: "var(--neutral-500)" }}>
              Promoting Media Freedom
            </span>
          </div>
        </Link>

        <nav>
          <ul className="flex list-none gap-2">
            <li>
              <Link
                href="/"
                className={`block py-6 px-4 font-medium text-[0.95rem] relative transition-all no-underline ${
                  currentPage === "home" ? "font-semibold" : ""
                }`}
                style={{
                  color: currentPage === "home" ? "var(--primary)" : "var(--neutral-700)",
                }}
                data-testid="nav-home"
              >
                Home
                <span
                  className="absolute bottom-0 left-1/2 -translate-x-1/2 h-[3px] rounded-sm transition-all"
                  style={{
                    width: currentPage === "home" ? "60%" : "0",
                    background: currentPage === "home" ? "var(--primary)" : "var(--accent)",
                  }}
                />
              </Link>
            </li>

            {/* About Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  setAboutOpen(!aboutOpen);
                  setServicesOpen(false);
                  setOpportunitiesOpen(false);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
                data-testid="nav-about"
              >
                About
                <ChevronDown
                  className={`w-3 h-3 transition-transform ${aboutOpen ? "rotate-180" : ""}`}
                />
              </button>
              <div
                className={`absolute top-full left-0 bg-white min-w-[220px] rounded-2xl overflow-hidden z-[100] transition-all ${
                  aboutOpen ? "opacity-100 visible translate-y-0" : "opacity-0 invisible translate-y-2.5"
                }`}
                style={{ boxShadow: "var(--shadow-lg)" }}
              >
                {[
                  { label: "About ZMC", path: "/about" },
                  { label: "Board of Commissioners", path: "/commissioners" },
                  { label: "Secretariat", path: "/secretariat" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => closeAllDropdowns()}
                    className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                    style={{
                      color: "var(--neutral-700)",
                      borderColor: "var(--neutral-100)",
                    }}
                    data-testid={`nav-${item.path.slice(1)}`}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </li>

            {/* Services Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  setServicesOpen(!servicesOpen);
                  setAboutOpen(false);
                  setOpportunitiesOpen(false);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
                data-testid="nav-services"
              >
                Services
                <ChevronDown
                  className={`w-3 h-3 transition-transform ${servicesOpen ? "rotate-180" : ""}`}
                />
              </button>
              <div
                className={`absolute top-full left-0 bg-white min-w-[220px] rounded-2xl overflow-hidden z-[100] transition-all ${
                  servicesOpen ? "opacity-100 visible translate-y-0" : "opacity-0 invisible translate-y-2.5"
                }`}
                style={{ boxShadow: "var(--shadow-lg)" }}
              >
                {[
                  { label: "Accreditation", path: "/accreditation" },
                  { label: "Registration", path: "/registration" },
                  { label: "Complaints & Appeals", path: "/complaints" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => closeAllDropdowns()}
                    className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                    style={{
                      color: "var(--neutral-700)",
                      borderColor: "var(--neutral-100)",
                    }}
                    data-testid={`nav-${item.path.slice(1)}`}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </li>

            {/* Opportunities Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  setOpportunitiesOpen(!opportunitiesOpen);
                  setAboutOpen(false);
                  setServicesOpen(false);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
                data-testid="nav-opportunities"
              >
                Opportunities
                <ChevronDown
                  className={`w-3 h-3 transition-transform ${opportunitiesOpen ? "rotate-180" : ""}`}
                />
              </button>
              <div
                className={`absolute top-full left-0 bg-white min-w-[220px] rounded-2xl overflow-hidden z-[100] transition-all ${
                  opportunitiesOpen ? "opacity-100 visible translate-y-0" : "opacity-0 invisible translate-y-2.5"
                }`}
                style={{ boxShadow: "var(--shadow-lg)" }}
              >
                {[
                  { label: "Vacancies", path: "/vacancies" },
                  { label: "Tenders", path: "/tenders" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => closeAllDropdowns()}
                    className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                    style={{
                      color: "var(--neutral-700)",
                      borderColor: "var(--neutral-100)",
                    }}
                    data-testid={`nav-${item.path.slice(1)}`}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </li>

            {[
              { label: "Downloads", path: "/downloads" },
              { label: "Events", path: "/events" },
              { label: "Contact", path: "/contact" },
            ].map((item) => (
              <li key={item.path}>
                <Link
                  href={item.path}
                  className="block py-6 px-4 font-medium text-[0.95rem] relative transition-all hover:text-[var(--primary)] no-underline"
                  style={{
                    color: currentPage === item.path.slice(1) ? "var(--primary)" : "var(--neutral-700)",
                  }}
                  data-testid={`nav-${item.path.slice(1)}`}
                >
                  {item.label}
                  <span
                    className="absolute bottom-0 left-1/2 -translate-x-1/2 h-[3px] rounded-sm transition-all"
                    style={{
                      width: currentPage === item.path.slice(1) ? "60%" : "0",
                      background: "var(--accent)",
                    }}
                  />
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </div>
    </header>
  );
}
