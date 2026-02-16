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
  Menu,
  X,
  Scale,
} from "lucide-react";
import zmcLogo from "@assets/zmc_logo-removebg-preview_1771225841865.png";

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
  const [mediaCentreOpen, setMediaCentreOpen] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
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

  useEffect(() => {
    if (mobileMenuOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [mobileMenuOpen]);

  const closeAllDropdowns = () => {
    setAboutOpen(false);
    setServicesOpen(false);
    setOpportunitiesOpen(false);
    setMediaCentreOpen(false);
  };

  const markAllRead = () => {
    setUnreadCount(0);
  };

  const handleMobileNavClick = (path: string) => {
    setMobileMenuOpen(false);
    closeAllDropdowns();
  };

  return (
    <header className="fixed w-full top-0 z-[1000]" style={{ background: "linear-gradient(135deg, #D4AF37 0%, #C49A2C 100%)", boxShadow: "0 2px 12px rgba(0,0,0,0.15)" }}>
      {/* Top Bar */}
      <div
        className="py-2 md:py-3 px-4 md:px-8 flex justify-between items-center flex-wrap gap-2 md:gap-4"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <div className="hidden md:flex items-center gap-6 flex-wrap">
          <div className="flex gap-6 text-sm text-white/90">
            <span className="flex items-center gap-2 whitespace-nowrap">
              <Phone className="w-4 h-4 flex-shrink-0" />
              <span>+263 242 253509/10</span>
            </span>
            <span className="flex items-center gap-2 whitespace-nowrap">
              <Mail className="w-4 h-4 flex-shrink-0" />
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

        {/* Mobile: Language toggle only */}
        <div className="flex md:hidden items-center gap-2">
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
                className={`py-1 px-2 text-xs font-semibold border-none cursor-pointer transition-all ${
                  language === lang
                    ? "text-neutral-900"
                    : "text-white/80"
                }`}
                style={{
                  background: language === lang ? "var(--accent)" : "transparent",
                }}
                data-testid={`button-lang-mobile-${lang}`}
              >
                {lang.toUpperCase()}
              </button>
            ))}
          </div>
        </div>

        <div className="flex gap-2 md:gap-3 items-center">
          {/* Notification Bell */}
          <div className="relative" ref={notificationRef}>
            <button
              onClick={() => setNotificationsOpen(!notificationsOpen)}
              className="w-9 h-9 md:w-[42px] md:h-[42px] rounded-full flex items-center justify-center cursor-pointer transition-all text-white hover:scale-105"
              style={{
                background: "rgba(255,255,255,0.15)",
                border: "2px solid rgba(255,255,255,0.3)",
              }}
              data-testid="button-notifications"
            >
              <Bell className="w-4 h-4 md:w-5 md:h-5" />
              {unreadCount > 0 && (
                <span
                  className="absolute -top-1 -right-1 text-white text-[0.65rem] font-bold min-w-4 h-4 md:min-w-5 md:h-5 rounded-full flex items-center justify-center animate-pulse-badge"
                  style={{ background: "var(--zim-red)" }}
                >
                  {unreadCount}
                </span>
              )}
            </button>

            {/* Notification Dropdown */}
            <div
              className={`absolute top-[calc(100%+10px)] right-0 w-[300px] md:w-[360px] bg-white rounded-2xl overflow-hidden z-[2000] transition-all ${
                notificationsOpen
                  ? "opacity-100 visible translate-y-0"
                  : "opacity-0 invisible -translate-y-2.5"
              }`}
              style={{ boxShadow: "var(--shadow-xl)" }}
            >
              <div
                className="flex justify-between items-center py-3 md:py-4 px-4 md:px-5 border-b"
                style={{ background: "var(--primary-lighter)", borderColor: "var(--neutral-200)" }}
              >
                <h4 className="text-sm md:text-base m-0" style={{ color: "var(--primary-dark)" }}>
                  Notifications
                </h4>
                <button
                  onClick={markAllRead}
                  className="bg-transparent border-none text-xs md:text-sm cursor-pointer font-semibold hover:underline"
                  style={{ color: "var(--primary)" }}
                  data-testid="button-mark-all-read"
                >
                  Mark all read
                </button>
              </div>
              <div className="max-h-60 md:max-h-80 overflow-y-auto">
                {notifications.map((notif) => (
                  <div
                    key={notif.id}
                    className={`flex gap-3 md:gap-4 py-3 md:py-4 px-4 md:px-5 border-b cursor-pointer transition-all ${
                      notif.unread && unreadCount > 0 ? "hover:bg-[#FFF3CD]" : "hover:bg-neutral-50"
                    }`}
                    style={{
                      background: notif.unread && unreadCount > 0 ? "var(--accent-soft)" : "transparent",
                      borderColor: "var(--neutral-100)",
                    }}
                    data-testid={`notification-item-${notif.id}`}
                  >
                    <div
                      className="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center flex-shrink-0"
                      style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                    >
                      <notif.icon className="w-4 h-4 md:w-5 md:h-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold text-xs md:text-sm mb-1" style={{ color: "var(--neutral-800)" }}>
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
                className="py-2 md:py-3 px-4 md:px-5 text-center border-t"
                style={{ background: "var(--neutral-50)", borderColor: "var(--neutral-200)" }}
              >
                <Link
                  href="/events"
                  onClick={() => setNotificationsOpen(false)}
                  className="text-xs md:text-sm font-semibold hover:underline"
                  style={{ color: "var(--primary)" }}
                >
                  View all notifications
                </Link>
              </div>
            </div>
          </div>

          {/* Desktop buttons */}
          <div className="hidden lg:flex gap-3">
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
            <a
              href="https://f17c25d1-8d60-4751-b64c-aadbdeaf0836-00-mtsfdj8ol3sm.worf.replit.dev/"
              target="_blank"
              rel="noopener noreferrer"
              className="py-2.5 px-5 rounded-[10px] font-semibold text-sm border-none flex items-center gap-2 text-white transition-all hover:-translate-y-0.5 no-underline cursor-pointer"
              style={{
                background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
                boxShadow: "0 3px 12px rgba(27, 94, 32, 0.25)",
              }}
              data-testid="button-accreditation-header"
            >
              <BadgeCheck className="w-4 h-4" />
              Get Accredited
            </a>
            <Link
              href="/complaints"
              className="py-2.5 px-5 rounded-[10px] font-semibold text-sm border-none flex items-center gap-2 text-white transition-all hover:-translate-y-0.5 no-underline"
              style={{
                background: "linear-gradient(135deg, var(--zim-red) 0%, #991b1b 100%)",
                boxShadow: "0 3px 12px rgba(198, 40, 40, 0.25)",
              }}
              data-testid="button-complaints-header"
            >
              <Scale className="w-4 h-4" />
              Complaints
            </Link>
            <Link
              href="/appeals"
              className="py-2.5 px-5 rounded-[10px] font-semibold text-sm border-none flex items-center gap-2 text-white transition-all hover:-translate-y-0.5 no-underline"
              style={{
                background: "linear-gradient(135deg, var(--blue, #2563eb) 0%, #1d4ed8 100%)",
                boxShadow: "0 3px 12px rgba(37, 99, 235, 0.25)",
              }}
              data-testid="button-appeals-header"
            >
              <Scale className="w-4 h-4" />
              Appeals
            </Link>
            <a
              href="https://f17c25d1-8d60-4751-b64c-aadbdeaf0836-00-mtsfdj8ol3sm.worf.replit.dev/"
              target="_blank"
              rel="noopener noreferrer"
              className="py-2.5 px-5 rounded-[10px] font-bold text-sm border-none flex items-center gap-2 transition-all hover:-translate-y-0.5 no-underline cursor-pointer"
              style={{
                background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)",
                color: "var(--zim-black)",
                boxShadow: "0 3px 12px rgba(212, 175, 55, 0.3)",
              }}
              data-testid="button-portal"
            >
              <ExternalLink className="w-4 h-4" />
              Portal
            </a>
          </div>
        </div>
      </div>

      {/* Main Navigation */}
      <div className="px-4 md:px-8 flex justify-between items-center">
        <Link href="/" className="flex items-center gap-3 md:gap-4 py-3 md:py-4 no-underline">
          <img 
            src={zmcLogo} 
            alt="Zimbabwe Media Commission Logo" 
            className="w-14 h-14 md:w-[80px] md:h-[80px] object-contain"
          />
          <div className="flex flex-col">
            <span className="text-base md:text-[1.4rem] font-bold leading-tight" style={{ fontFamily: "var(--font-serif)" }}>
              <span style={{ color: "#212121" }}>Zimbabwe</span>{" "}
              <span style={{ color: "#1B5E20" }}>Media</span>{" "}
              <span className="hidden sm:inline" style={{ color: "#212121" }}>Commission</span>
              <span className="sm:hidden" style={{ color: "#212121" }}>Comm.</span>
            </span>
            <span className="hidden sm:block text-xs uppercase tracking-wider" style={{ color: "rgba(0,0,0,0.6)" }}>
              Promoting Media Freedom
            </span>
          </div>
        </Link>

        {/* Mobile Menu Toggle */}
        <button
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="lg:hidden p-2 rounded-lg"
          style={{ background: "rgba(0,0,0,0.1)", color: "#212121" }}
          data-testid="button-mobile-menu"
        >
          {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>

        {/* Desktop Navigation */}
        <nav className="hidden lg:block">
          <ul className="flex list-none gap-2">
            <li>
              <Link
                href="/"
                className={`block py-6 px-4 font-medium text-[0.95rem] relative transition-all no-underline ${
                  currentPage === "home" ? "font-semibold" : ""
                }`}
                style={{
                  color: currentPage === "home" ? "#1B5E20" : "#212121",
                }}
                data-testid="nav-home"
              >
                Home
                <span
                  className="absolute bottom-0 left-1/2 -translate-x-1/2 h-[3px] rounded-sm transition-all"
                  style={{
                    width: currentPage === "home" ? "60%" : "0",
                    background: currentPage === "home" ? "#1B5E20" : "#212121",
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
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[#1B5E20] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
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
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[#1B5E20] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
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
                  { label: "Complaints", path: "/complaints" },
                  { label: "Appeals (FOIA)", path: "/appeals" },
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
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[#1B5E20] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
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

            {/* Media Centre Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  setMediaCentreOpen(!mediaCentreOpen);
                  setAboutOpen(false);
                  setServicesOpen(false);
                  setOpportunitiesOpen(false);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[#1B5E20] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
                data-testid="nav-media-centre"
              >
                Media Centre
                <ChevronDown
                  className={`w-3 h-3 transition-transform ${mediaCentreOpen ? "rotate-180" : ""}`}
                />
              </button>
              <div
                className={`absolute top-full left-0 bg-white min-w-[220px] rounded-2xl overflow-hidden z-[100] transition-all ${
                  mediaCentreOpen ? "opacity-100 visible translate-y-0" : "opacity-0 invisible translate-y-2.5"
                }`}
                style={{ boxShadow: "var(--shadow-lg)" }}
              >
                {[
                  { label: "Downloads", path: "/downloads" },
                  { label: "Press Releases", path: "/press-releases" },
                  { label: "Magazine", path: "/magazine" },
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
              { label: "Events", path: "/events" },
              { label: "Contact", path: "/contact" },
            ].map((item) => (
              <li key={item.path}>
                <Link
                  href={item.path}
                  className="block py-6 px-4 font-medium text-[0.95rem] relative transition-all hover:text-[#1B5E20] no-underline"
                  style={{
                    color: currentPage === item.path.slice(1) ? "#1B5E20" : "#212121",
                  }}
                  data-testid={`nav-${item.path.slice(1)}`}
                >
                  {item.label}
                  <span
                    className="absolute bottom-0 left-1/2 -translate-x-1/2 h-[3px] rounded-sm transition-all"
                    style={{
                      width: currentPage === item.path.slice(1) ? "60%" : "0",
                      background: "#1B5E20",
                    }}
                  />
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </div>

      {/* Mobile Menu Overlay */}
      <div
        className={`lg:hidden fixed inset-0 top-[100px] md:top-[120px] z-[999] transition-all ${
          mobileMenuOpen ? "opacity-100 visible" : "opacity-0 invisible pointer-events-none"
        }`}
        style={{ background: "rgba(0,0,0,0.5)" }}
        onClick={() => setMobileMenuOpen(false)}
      />

      {/* Mobile Menu */}
      <div
        className={`lg:hidden fixed top-[100px] md:top-[120px] left-0 right-0 z-[1000] overflow-y-auto transition-all ${
          mobileMenuOpen ? "max-h-[calc(100vh-100px)] opacity-100" : "max-h-0 opacity-0 overflow-hidden"
        }`}
        style={{ background: "rgba(255, 255, 255, 0.82)", backdropFilter: "saturate(180%) blur(20px)", WebkitBackdropFilter: "saturate(180%) blur(20px)", boxShadow: "0 4px 30px rgba(0,0,0,0.08)" }}
      >
        <div className="p-4">
          {/* Quick Actions */}
          <div className="grid grid-cols-4 gap-2 mb-4">
            <Link
              href="/registration"
              onClick={() => handleMobileNavClick("/registration")}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
            >
              <Building2 className="w-4 h-4" />
              Register
            </Link>
            <Link
              href="/accreditation"
              onClick={() => handleMobileNavClick("/accreditation")}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
            >
              <BadgeCheck className="w-4 h-4" />
              Accredit
            </Link>
            <Link
              href="/complaints"
              onClick={() => handleMobileNavClick("/complaints")}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--zim-red) 0%, #991b1b 100%)" }}
            >
              <Scale className="w-4 h-4" />
              Complaints
            </Link>
            <Link
              href="/appeals"
              onClick={() => handleMobileNavClick("/appeals")}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--blue, #2563eb) 0%, #1d4ed8 100%)" }}
              data-testid="button-appeals-mobile"
            >
              <Scale className="w-4 h-4" />
              Appeals
            </Link>
          </div>

          {/* Nav Links */}
          <nav>
            <Link
              href="/"
              onClick={() => handleMobileNavClick("/")}
              className="block py-3 px-4 border-b no-underline"
              style={{ borderColor: "var(--neutral-100)", color: currentPage === "home" ? "var(--primary)" : "var(--neutral-700)" }}
            >
              Home
            </Link>

            {/* About Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setAboutOpen(!aboutOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>About</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${aboutOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${aboutOpen ? "max-h-40" : "max-h-0"}`}>
                {[
                  { label: "About ZMC", path: "/about" },
                  { label: "Board of Commissioners", path: "/commissioners" },
                  { label: "Secretariat", path: "/secretariat" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => handleMobileNavClick(item.path)}
                    className="block py-2 px-8 text-sm no-underline"
                    style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>

            {/* Services Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setServicesOpen(!servicesOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>Services</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${servicesOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${servicesOpen ? "max-h-40" : "max-h-0"}`}>
                {[
                  { label: "Accreditation", path: "/accreditation" },
                  { label: "Registration", path: "/registration" },
                  { label: "Complaints", path: "/complaints" },
                  { label: "Appeals (FOIA)", path: "/appeals" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => handleMobileNavClick(item.path)}
                    className="block py-2 px-8 text-sm no-underline"
                    style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>

            {/* Opportunities Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setOpportunitiesOpen(!opportunitiesOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>Opportunities</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${opportunitiesOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${opportunitiesOpen ? "max-h-24" : "max-h-0"}`}>
                {[
                  { label: "Vacancies", path: "/vacancies" },
                  { label: "Tenders", path: "/tenders" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => handleMobileNavClick(item.path)}
                    className="block py-2 px-8 text-sm no-underline"
                    style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>

            {/* Media Centre Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setMediaCentreOpen(!mediaCentreOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>Media Centre</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${mediaCentreOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${mediaCentreOpen ? "max-h-40" : "max-h-0"}`}>
                {[
                  { label: "Downloads", path: "/downloads" },
                  { label: "Press Releases", path: "/press-releases" },
                  { label: "Magazine", path: "/magazine" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => handleMobileNavClick(item.path)}
                    className="block py-2 px-8 text-sm no-underline"
                    style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>

            {[
              { label: "Events", path: "/events" },
              { label: "Contact", path: "/contact" },
            ].map((item) => (
              <Link
                key={item.path}
                href={item.path}
                onClick={() => handleMobileNavClick(item.path)}
                className="block py-3 px-4 border-b no-underline"
                style={{ borderColor: "var(--neutral-100)", color: currentPage === item.path.slice(1) ? "var(--primary)" : "var(--neutral-700)" }}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          {/* Contact info for mobile */}
          <div className="mt-4 pt-4 border-t" style={{ borderColor: "var(--neutral-200)" }}>
            <div className="flex items-center gap-2 text-sm mb-2" style={{ color: "var(--neutral-600)" }}>
              <Phone className="w-4 h-4" />
              +263 242 253509/10
            </div>
            <div className="flex items-center gap-2 text-sm" style={{ color: "var(--neutral-600)" }}>
              <Mail className="w-4 h-4" />
              info@zmc.org.zw
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
