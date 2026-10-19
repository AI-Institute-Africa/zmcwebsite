import { useState, useRef, useEffect } from "react";
import { Link, useLocation } from "wouter";
import {
  Phone,
  Mail,
  Building2,
  BadgeCheck,
  ExternalLink,
  ChevronDown,
  Menu,
  X,
  Scale,
  FolderOpen,
} from "lucide-react";
import zmcLogo from "@assets/zmc_logo-removebg-preview_1771225841865.png";
import { useLanguage } from "../i18n/LanguageContext";
import { PORTAL_BASE_URL, PORTAL_LOGIN_URL } from "../lib/api";

interface HeaderProps {
  currentPage: string;
  onNavigate: (page: string) => void;
}

export default function Header({ currentPage, onNavigate }: HeaderProps) {
  const { t } = useLanguage();
  const [aboutOpen, setAboutOpen] = useState(false);
  const [servicesOpen, setServicesOpen] = useState(false);
  const [accreditationOpen, setAccreditationOpen] = useState(false);
  const [registrationOpen, setRegistrationOpen] = useState(false);
  const [opportunitiesOpen, setOpportunitiesOpen] = useState(false);
  const [mediaCentreOpen, setMediaCentreOpen] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [, setLocation] = useLocation();

  const headerRef = useRef<HTMLElement>(null);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (headerRef.current && !headerRef.current.contains(event.target as Node)) {
        closeAllDropdowns();
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
    setAccreditationOpen(false);
    setRegistrationOpen(false);
    setOpportunitiesOpen(false);
    setMediaCentreOpen(false);
  };

  const handleMobileNavClick = (path: string) => {
    setMobileMenuOpen(false);
    setAboutOpen(false);
    setServicesOpen(false);
    setAccreditationOpen(false);
    setRegistrationOpen(false);
    setOpportunitiesOpen(false);
    setMediaCentreOpen(false);
  };

  return (
    <header ref={headerRef} className="fixed w-full top-0 z-[1000]" style={{ background: "linear-gradient(180deg, rgba(255,255,255,0.82) 0%, rgba(255,255,255,0.72) 100%)", backdropFilter: "blur(24px) saturate(200%)", WebkitBackdropFilter: "blur(24px) saturate(200%)", boxShadow: "0 1px 16px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.6)", borderBottom: "1px solid rgba(255,255,255,0.45)" }}>
      {/* Top Bar */}
      <div
        className="py-2 md:py-3 px-4 md:px-8 flex justify-between items-center flex-wrap gap-2 md:gap-4"
        style={{ background: "linear-gradient(90deg, rgba(46, 125, 86, 0.9) 0%, rgba(74, 158, 114, 0.85) 35%, rgba(180, 160, 60, 0.8) 65%, rgba(212, 175, 55, 0.85) 100%)", backdropFilter: "blur(16px) saturate(180%)", WebkitBackdropFilter: "blur(16px) saturate(180%)", borderBottom: "1px solid rgba(255,255,255,0.15)" }}
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

        </div>

        <div className="flex gap-2 md:gap-3 items-center">
          {/* Desktop buttons */}
          <div className="hidden lg:flex gap-3">
             <a
               href="/registration"
               className="py-2.5 px-5 rounded-[10px] font-semibold text-sm flex items-center gap-2 text-white transition-all no-underline hover:brightness-110"
              style={{
                background: "linear-gradient(135deg, rgba(46, 125, 86, 0.85) 0%, rgba(27, 94, 63, 0.9) 100%)",
                border: "1px solid rgba(255,255,255,0.2)",
                backdropFilter: "blur(6px)",
                WebkitBackdropFilter: "blur(6px)",
                boxShadow: "0 2px 8px rgba(46, 125, 86, 0.3), inset 0 1px 0 rgba(255,255,255,0.15)",
              }}
              data-testid="button-registration-header"
            >
              <Building2 className="w-4 h-4" />
              {t.nav.registration}
            </a>
             <a
               href="/accreditation"
               className="py-2.5 px-5 rounded-[10px] font-semibold text-sm flex items-center gap-2 text-white transition-all no-underline cursor-pointer hover:brightness-110"
               style={{
                 background: "linear-gradient(135deg, rgba(46, 125, 86, 0.85) 0%, rgba(27, 94, 63, 0.9) 100%)",
                 border: "1px solid rgba(255,255,255,0.2)",
                 backdropFilter: "blur(6px)",
                 WebkitBackdropFilter: "blur(6px)",
                 boxShadow: "0 2px 8px rgba(46, 125, 86, 0.3), inset 0 1px 0 rgba(255,255,255,0.15)",
               }}
               data-testid="button-accreditation-header"
               onClick={() => onNavigate("accreditation")}
             >
               <BadgeCheck className="w-4 h-4" />
               {t.nav.getAccredited}
             </a>
            <Link
              href="/downloads"
              className="py-2.5 px-5 rounded-[10px] font-semibold text-sm flex items-center gap-2 text-white transition-all no-underline hover:brightness-110"
              style={{
                background: "linear-gradient(135deg, rgba(46, 125, 86, 0.85) 0%, rgba(27, 94, 63, 0.9) 100%)",
                border: "1px solid rgba(255,255,255,0.2)",
                backdropFilter: "blur(6px)",
                WebkitBackdropFilter: "blur(6px)",
                boxShadow: "0 2px 8px rgba(46, 125, 86, 0.3), inset 0 1px 0 rgba(255,255,255,0.15)",
              }}
              data-testid="button-reports-documents-header"
            >
              <FolderOpen className="w-4 h-4" />
              Reports & Documents
            </Link>
            <Link
              href="/complaints"
              className="py-2.5 px-5 rounded-[10px] font-semibold text-sm flex items-center gap-2 text-white transition-all no-underline hover:brightness-110"
              style={{
                background: "linear-gradient(135deg, rgba(198, 40, 40, 0.85) 0%, rgba(153, 27, 27, 0.9) 100%)",
                border: "1px solid rgba(255,255,255,0.2)",
                backdropFilter: "blur(6px)",
                WebkitBackdropFilter: "blur(6px)",
                boxShadow: "0 2px 8px rgba(198, 40, 40, 0.3), inset 0 1px 0 rgba(255,255,255,0.15)",
              }}
              data-testid="button-complaints-header"
            >
              <Scale className="w-4 h-4" />
              {t.nav.mediaComplaints}
            </Link>
            <Link
              href="/appeals"
              className="py-2.5 px-5 rounded-[10px] font-semibold text-sm flex items-center gap-2 text-white transition-all no-underline hover:brightness-110"
              style={{
                background: "linear-gradient(135deg, rgba(37, 99, 235, 0.85) 0%, rgba(29, 78, 216, 0.9) 100%)",
                border: "1px solid rgba(255,255,255,0.2)",
                backdropFilter: "blur(6px)",
                WebkitBackdropFilter: "blur(6px)",
                boxShadow: "0 2px 8px rgba(37, 99, 235, 0.3), inset 0 1px 0 rgba(255,255,255,0.15)",
              }}
              data-testid="button-appeals-header"
            >
              <Scale className="w-4 h-4" />
              {t.nav.foiaAppeals}
            </Link>
            <a
              href={`${PORTAL_BASE_URL}/`}
              target="_blank"
              rel="noopener noreferrer"
              className="py-2.5 px-5 rounded-[10px] font-bold text-sm flex items-center gap-2 transition-all no-underline cursor-pointer hover:brightness-110"
              style={{
                background: "linear-gradient(135deg, rgba(212, 175, 55, 0.9) 0%, rgba(184, 134, 11, 0.95) 100%)",
                color: "var(--zim-black)",
                border: "1px solid rgba(255,255,255,0.25)",
                backdropFilter: "blur(6px)",
                WebkitBackdropFilter: "blur(6px)",
                boxShadow: "0 2px 8px rgba(212, 175, 55, 0.35), inset 0 1px 0 rgba(255,255,255,0.2)",
              }}
              data-testid="button-portal"
            >
              <ExternalLink className="w-4 h-4" />
              {t.nav.portal}
            </a>
          </div>
        </div>
      </div>

      {/* Main Navigation */}
      <div className="px-4 md:pl-14 md:pr-8 flex justify-between items-center">
        <Link href="/" className="flex items-center gap-3 md:gap-4 py-3 md:py-4 no-underline">
          <img 
            src={zmcLogo} 
            alt="Zimbabwe Media Commission Logo" 
            className="w-14 h-14 md:w-[80px] md:h-[80px] object-contain"
          />
          <div className="flex flex-col">
            <span className="text-base md:text-[1.4rem] font-bold leading-tight">
              <span style={{ color: "#212121" }}>Zimbabwe</span>{" "}
              <span style={{ color: "var(--primary)" }}>Media</span>{" "}
              <span className="hidden sm:inline" style={{ color: "#212121" }}>Commission</span>
              <span className="sm:hidden" style={{ color: "#212121" }}>Comm.</span>
            </span>
            <div className="hidden sm:block overflow-hidden max-w-[220px] md:max-w-[280px]">
              <div
                className="whitespace-nowrap text-xs tracking-wider animate-marquee"
                style={{ color: "rgba(0,0,0,0.6)", fontStyle: "italic" }}
              >
                {t.vision}
              </div>
            </div>
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
                  color: currentPage === "home" ? "var(--primary)" : "#212121",
                }}
                data-testid="nav-home"
              >
                {t.nav.home}
                <span
                  className="absolute bottom-0 left-1/2 -translate-x-1/2 h-[3px] rounded-sm transition-all"
                  style={{
                    width: currentPage === "home" ? "60%" : "0",
                    background: currentPage === "home" ? "var(--primary)" : "#212121",
                  }}
                />
              </Link>
            </li>

            {/* About Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  const next = !aboutOpen;
                  closeAllDropdowns();
                  setAboutOpen(next);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
                data-testid="nav-about"
              >
                {t.nav.about}
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
                  { label: t.nav.aboutZmc, path: "/about" },
                  { label: t.nav.commissioners, path: "/commissioners" },
                  { label: t.nav.secretariat, path: "/secretariat" },
                ].map((item) => (
                  <Link
                    key={item.path}
                    href={item.path}
                    onClick={() => { closeAllDropdowns(); }}
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
                  const next = !servicesOpen;
                  closeAllDropdowns();
                  setServicesOpen(next);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
                data-testid="nav-services"
              >
                {t.nav.services}
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
                <Link
                  href="/complaints"
                  onClick={() => { closeAllDropdowns(); }}
                  className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                  style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                  data-testid="nav-complaints"
                >
                  {t.nav.mediaComplaints}
                </Link>
                <Link
                  href="/appeals"
                  onClick={() => { closeAllDropdowns(); }}
                  className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                  style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                  data-testid="nav-appeals"
                >
                  {t.nav.foiaAppeals}
                </Link>

                <button
                  type="button"
                  onClick={() => setAccreditationOpen(!accreditationOpen)}
                  className="w-full py-3.5 px-5 text-[0.9rem] border-b transition-all flex items-center justify-between bg-transparent cursor-pointer"
                  style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                  data-testid="nav-accreditation-group"
                >
                  {t.nav.accreditation}
                  <ChevronDown className={`w-4 h-4 transition-transform ${accreditationOpen ? "rotate-180" : ""}`} />
                </button>
                <div
                  className={`overflow-hidden transition-all ${accreditationOpen ? "max-h-60" : "max-h-0"}`}
                  style={{ background: "var(--neutral-50)" }}
                >
                  {[
                    { label: t.nav.howToGetAccredited, path: "/accreditation" },
                    { label: t.nav.accreditedPractitioners, path: "/accredited-practitioners" },
                    { label: t.nav.verifyAccreditation, path: "/verify-accreditation" },
                  ].map((item) => (
                    <Link
                      key={item.path}
                      href={item.path}
                      onClick={() => { closeAllDropdowns(); }}
                      className="block py-2.5 pl-9 pr-5 text-[0.85rem] no-underline transition-all hover:pl-10"
                      style={{ color: "var(--neutral-600)" }}
                      data-testid={`nav-${item.path.slice(1)}`}
                    >
                      {item.label}
                    </Link>
                  ))}
                </div>

                <button
                  type="button"
                  onClick={() => setRegistrationOpen(!registrationOpen)}
                  className="w-full py-3.5 px-5 text-[0.9rem] border-b transition-all flex items-center justify-between bg-transparent cursor-pointer"
                  style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                  data-testid="nav-registration-group"
                >
                  {t.nav.registration}
                  <ChevronDown className={`w-4 h-4 transition-transform ${registrationOpen ? "rotate-180" : ""}`} />
                </button>
                <div
                  className={`overflow-hidden transition-all ${registrationOpen ? "max-h-40" : "max-h-0"}`}
                  style={{ background: "var(--neutral-50)" }}
                >
                  {[
                    { label: t.nav.howToRegister, path: "/registration" },
                    { label: t.nav.registeredMediaHouses, path: "/registered-media-houses" },
                  ].map((item) => (
                    <Link
                      key={item.path}
                      href={item.path}
                      onClick={() => { closeAllDropdowns(); }}
                      className="block py-2.5 pl-9 pr-5 text-[0.85rem] no-underline transition-all hover:pl-10"
                      style={{ color: "var(--neutral-600)" }}
                      data-testid={`nav-${item.path.slice(1)}`}
                    >
                      {item.label}
                    </Link>
                  ))}
                </div>

                <Link
                  href="/media-hub"
                  onClick={() => { closeAllDropdowns(); }}
                  className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                  style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                  data-testid="nav-training"
                >
                  {t.nav.training}
                </Link>
              </div>
            </li>

            {/* Opportunities Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  const next = !opportunitiesOpen;
                  closeAllDropdowns();
                  setOpportunitiesOpen(next);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
                data-testid="nav-opportunities"
              >
                {t.nav.opportunities}
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
                  { label: t.nav.vacancies, path: "/vacancies", testId: "nav-vacancies" },
                  { label: t.nav.tenders, path: "/tenders", testId: "nav-tenders" },
                  { label: t.nav.training, path: "/media-hub", testId: "nav-training" },
                ].map((item) =>
                  (item as any).external ? (
                    <a
                      key={item.testId}
                      href={item.path}
                      target="_blank"
                      rel="noopener noreferrer"
                      onClick={() => { closeAllDropdowns(); }}
                      className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                      style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                      data-testid={item.testId}
                    >
                      {item.label}
                    </a>
                  ) : (
                    <Link
                      key={item.testId}
                      href={item.path}
                      onClick={() => { closeAllDropdowns(); }}
                      className="block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline"
                      style={{ color: "var(--neutral-700)", borderColor: "var(--neutral-100)" }}
                      data-testid={item.testId}
                    >
                      {item.label}
                    </Link>
                  )
                )}
              </div>
            </li>

            {/* Media Centre Dropdown */}
            <li className="relative">
              <button
                onClick={() => {
                  const next = !mediaCentreOpen;
                  closeAllDropdowns();
                  setMediaCentreOpen(next);
                }}
                className="block py-6 px-4 font-medium text-[0.95rem] flex items-center gap-1 transition-all hover:text-[var(--primary)] bg-transparent border-none cursor-pointer"
                style={{ color: "#212121" }}
                data-testid="nav-media-centre"
              >
                {t.nav.mediaCentre}
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
                  { label: t.nav.downloads, path: "/downloads" },
                  { label: t.nav.photos, path: "/photos" },
                  { label: t.nav.pressReleases, path: "/press-releases" },
                  { label: t.nav.magazine, path: "/magazine" },
                  { label: t.nav.mediaHub, path: "/media-hub", highlight: true, testId: "nav-media-hub" },
                ].map((item) => {
                  const isHighlight = (item as any).highlight;
                  const cls = `block py-3.5 px-5 text-[0.9rem] border-b transition-all hover:pl-6 no-underline flex items-center justify-between ${isHighlight ? "font-semibold" : ""}`;
                  const sty = {
                    color: isHighlight ? "var(--primary-dark)" : "var(--neutral-700)",
                    borderColor: "var(--neutral-100)",
                    background: isHighlight ? "var(--accent-soft)" : "transparent",
                  };
                  const inner = (
                    <>
                      {item.label}
                      {isHighlight && (
                        <span
                          className="text-[0.65rem] font-bold py-0.5 px-2 rounded-full"
                          style={{ background: "var(--accent)", color: "var(--zim-black)" }}
                        >
                          {t.newBadge}
                        </span>
                      )}
                    </>
                  );
                  return (item as any).external ? (
                    <a
                      key={item.path}
                      href={item.path}
                      target="_blank"
                      rel="noopener noreferrer"
                      onClick={() => { closeAllDropdowns(); }}
                      className={cls}
                      style={sty}
                      data-testid={(item as any).testId}
                    >
                      {inner}
                    </a>
                  ) : (
                    <Link
                      key={item.path}
                      href={item.path}
                      onClick={() => { closeAllDropdowns(); }}
                      className={cls}
                      style={sty}
                      data-testid={`nav-${item.path.slice(1)}`}
                    >
                      {inner}
                    </Link>
                  );
                })}
              </div>
            </li>

            {[
              { label: t.nav.events, path: "/events" },
              { label: t.nav.contact, path: "/contact" },
            ].map((item) => (
              <li key={item.path}>
                <Link
                  href={item.path}
                  className="block py-6 px-4 font-medium text-[0.95rem] relative transition-all hover:text-[var(--primary)] no-underline"
                  style={{
                    color: currentPage === item.path.slice(1) ? "var(--primary)" : "#212121",
                  }}
                  data-testid={`nav-${item.path.slice(1)}`}
                >
                  {item.label}
                  <span
                    className="absolute bottom-0 left-1/2 -translate-x-1/2 h-[3px] rounded-sm transition-all"
                    style={{
                      width: currentPage === item.path.slice(1) ? "60%" : "0",
                      background: "var(--primary)",
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
        className={`lg:hidden fixed inset-0 top-[140px] md:top-[170px] z-[999] transition-all ${
          mobileMenuOpen ? "opacity-100 visible" : "opacity-0 invisible pointer-events-none"
        }`}
        style={{ background: "rgba(0,0,0,0.5)" }}
        onClick={() => setMobileMenuOpen(false)}
      />

      {/* Mobile Menu */}
      <div
        className={`lg:hidden fixed top-[140px] md:top-[170px] left-0 right-0 z-[1000] overflow-y-auto transition-all ${
          mobileMenuOpen ? "max-h-[calc(100vh-140px)] opacity-100" : "max-h-0 opacity-0 overflow-hidden"
        }`}
        style={{ background: "rgba(255, 255, 255, 0.82)", backdropFilter: "saturate(180%) blur(20px)", WebkitBackdropFilter: "saturate(180%) blur(20px)", boxShadow: "0 4px 30px rgba(0,0,0,0.08)" }}
      >
        <div className="p-4">
          {/* Quick Actions */}
          <div className="grid grid-cols-4 gap-2 mb-4">
            <a
               href={PORTAL_LOGIN_URL}
              target="_blank"
              rel="noopener noreferrer"
              onClick={() => setMobileMenuOpen(false)}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
            >
              <BadgeCheck className="w-4 h-4" />
              {t.nav.accreditation}
            </a>
            <Link
              href="/complaints"
              onClick={() => handleMobileNavClick("/complaints")}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--zim-red) 0%, #991b1b 100%)" }}
            >
              <Scale className="w-4 h-4" />
              {t.nav.mediaComplaints}
            </Link>
            <Link
              href="/appeals"
              onClick={() => handleMobileNavClick("/appeals")}
              className="py-2.5 px-1 rounded-xl font-semibold text-[10px] flex flex-col items-center justify-center gap-1.5 text-white no-underline"
              style={{ background: "linear-gradient(135deg, var(--blue, #2563eb) 0%, #1d4ed8 100%)" }}
              data-testid="button-appeals-mobile"
            >
              <Scale className="w-4 h-4" />
              {t.nav.foiaAppeals}
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
              {t.nav.home}
            </Link>

            {/* About Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setAboutOpen(!aboutOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>{t.nav.about}</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${aboutOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${aboutOpen ? "max-h-40" : "max-h-0"}`}>
                {[
                  { label: t.nav.aboutZmc, path: "/about" },
                  { label: t.nav.commissioners, path: "/commissioners" },
                  { label: t.nav.secretariat, path: "/secretariat" },
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
                <span>{t.nav.services}</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${servicesOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${servicesOpen ? "max-h-[600px]" : "max-h-0"}`}>
                <Link
                  href="/complaints"
                  onClick={() => handleMobileNavClick("/complaints")}
                  className="block py-2 px-8 text-sm no-underline"
                  style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                >
                  {t.nav.mediaComplaints}
                </Link>
                <Link
                  href="/appeals"
                  onClick={() => handleMobileNavClick("/appeals")}
                  className="block py-2 px-8 text-sm no-underline"
                  style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                >
                  {t.nav.foiaAppeals}
                </Link>

                <button
                  type="button"
                  onClick={() => setAccreditationOpen(!accreditationOpen)}
                  className="w-full py-2 px-8 flex justify-between items-center bg-transparent border-none cursor-pointer text-sm"
                  style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                >
                  <span>{t.nav.accreditation}</span>
                  <ChevronDown className={`w-4 h-4 transition-transform ${accreditationOpen ? "rotate-180" : ""}`} />
                </button>
                <div className={`overflow-hidden transition-all ${accreditationOpen ? "max-h-40" : "max-h-0"}`}>
                  {[
                    { label: t.nav.howToGetAccredited, path: "/accreditation" },
                    { label: t.nav.accreditedPractitioners, path: "/accredited-practitioners" },
                    { label: t.nav.verifyAccreditation, path: "/verify-accreditation" },
                  ].map((item) => (
                    <Link
                      key={item.path}
                      href={item.path}
                      onClick={() => handleMobileNavClick(item.path)}
                      className="block py-2 pl-12 pr-8 text-sm no-underline"
                      style={{ color: "var(--neutral-500)", background: "var(--neutral-100)" }}
                    >
                      {item.label}
                    </Link>
                  ))}
                </div>

                <button
                  type="button"
                  onClick={() => setRegistrationOpen(!registrationOpen)}
                  className="w-full py-2 px-8 flex justify-between items-center bg-transparent border-none cursor-pointer text-sm"
                  style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                >
                  <span>{t.nav.registration}</span>
                  <ChevronDown className={`w-4 h-4 transition-transform ${registrationOpen ? "rotate-180" : ""}`} />
                </button>
                <div className={`overflow-hidden transition-all ${registrationOpen ? "max-h-40" : "max-h-0"}`}>
                  {[
                    { label: t.nav.howToRegister, path: "/registration" },
                    { label: t.nav.registeredMediaHouses, path: "/registered-media-houses" },
                  ].map((item) => (
                    <Link
                      key={item.path}
                      href={item.path}
                      onClick={() => handleMobileNavClick(item.path)}
                      className="block py-2 pl-12 pr-8 text-sm no-underline"
                      style={{ color: "var(--neutral-500)", background: "var(--neutral-100)" }}
                    >
                      {item.label}
                    </Link>
                  ))}
                </div>

                <Link
                  href="/media-hub"
                  onClick={() => handleMobileNavClick("/media-hub")}
                  className="block py-2 px-8 text-sm no-underline"
                  style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                  data-testid="nav-training"
                >
                  {t.nav.training}
                </Link>
              </div>
            </div>

            {/* Opportunities Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setOpportunitiesOpen(!opportunitiesOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>{t.nav.opportunities}</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${opportunitiesOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${opportunitiesOpen ? "max-h-40" : "max-h-0"}`}>
                {[
                  { label: t.nav.vacancies, path: "/vacancies", key: "vacancies" },
                  { label: t.nav.tenders, path: "/tenders", key: "tenders" },
                  { label: t.nav.training, path: "/media-hub", key: "training", testId: "nav-training" },
                ].map((item) =>
                  (item as any).external ? (
                    <a
                      key={item.key}
                      href={item.path}
                      target="_blank"
                      rel="noopener noreferrer"
                      onClick={() => handleMobileNavClick(item.path)}
                      className="block py-2 px-8 text-sm no-underline"
                      style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                      data-testid={(item as any).testId}
                    >
                      {item.label}
                    </a>
                  ) : (
                    <Link
                      key={item.key}
                      href={item.path}
                      onClick={() => handleMobileNavClick(item.path)}
                      className="block py-2 px-8 text-sm no-underline"
                      style={{ color: "var(--neutral-600)", background: "var(--neutral-50)" }}
                    >
                      {item.label}
                    </Link>
                  )
                )}
              </div>
            </div>

            {/* Media Centre Section */}
            <div className="border-b" style={{ borderColor: "var(--neutral-100)" }}>
              <button
                onClick={() => setMediaCentreOpen(!mediaCentreOpen)}
                className="w-full py-3 px-4 flex justify-between items-center bg-transparent border-none cursor-pointer"
                style={{ color: "var(--neutral-700)" }}
              >
                <span>{t.nav.mediaCentre}</span>
                <ChevronDown className={`w-4 h-4 transition-transform ${mediaCentreOpen ? "rotate-180" : ""}`} />
              </button>
              <div className={`overflow-hidden transition-all ${mediaCentreOpen ? "max-h-60" : "max-h-0"}`}>
                {[
                  { label: t.nav.downloads, path: "/downloads" },
                  { label: t.nav.photos, path: "/photos" },
                  { label: t.nav.pressReleases, path: "/press-releases" },
                  { label: t.nav.magazine, path: "/magazine" },
                  { label: t.nav.mediaHub, path: "/media-hub", highlight: true, testId: "nav-media-hub" },
                ].map((item) => {
                  const isHighlight = (item as any).highlight;
                  const cls = `block py-2 px-8 text-sm no-underline flex items-center justify-between ${isHighlight ? "font-semibold" : ""}`;
                  const sty = {
                    color: isHighlight ? "var(--primary-dark)" : "var(--neutral-600)",
                    background: isHighlight ? "var(--accent-soft)" : "var(--neutral-50)",
                  };
                  const inner = (
                    <>
                      {item.label}
                      {isHighlight && (
                        <span
                          className="text-[0.6rem] font-bold py-0.5 px-1.5 rounded-full"
                          style={{ background: "var(--accent)", color: "var(--zim-black)" }}
                        >
                          {t.newBadge}
                        </span>
                      )}
                    </>
                  );
                  return (item as any).external ? (
                    <a
                      key={item.path}
                      href={item.path}
                      target="_blank"
                      rel="noopener noreferrer"
                      onClick={() => handleMobileNavClick(item.path)}
                      className={cls}
                      style={sty}
                      data-testid={(item as any).testId}
                    >
                      {inner}
                    </a>
                  ) : (
                    <Link
                      key={item.path}
                      href={item.path}
                      onClick={() => handleMobileNavClick(item.path)}
                      className={cls}
                      style={sty}
                    >
                      {inner}
                    </Link>
                  );
                })}
              </div>
            </div>

            {[
              { label: t.nav.events, path: "/events" },
              { label: t.nav.contact, path: "/contact" },
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
