import { useEffect } from "react";
import { Bell, FileText } from "lucide-react";
import { Switch, Route, useLocation } from "wouter";
import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./lib/queryClient";
import { Toaster } from "@/components/ui/toaster";
import { LanguageProvider, useLanguage } from "./i18n/LanguageContext";
import Header from "./components/Header";
import Footer from "./components/Footer";
import Chatbot from "./components/Chatbot";
import SocialSidebar from "./components/SocialSidebar";
import HomePage from "./pages/HomePage";
import AboutPage from "./pages/AboutPage";
import AccreditationPage from "./pages/AccreditationPage";
import RegistrationPage from "./pages/RegistrationPage";
import ComplaintsPage from "./pages/ComplaintsPage";
import AppealsPage from "./pages/AppealsPage";
import DownloadsPage from "./pages/DownloadsPage";
import EventsPage from "./pages/EventsPage";
import ContactPage from "./pages/ContactPage";
import SecretariatPage from "./pages/SecretariatPage";
import CommissionersPage from "./pages/CommissionersPage";
import TendersPage from "./pages/TendersPage";
import PressReleasesPage from "./pages/PressReleasesPage";
import MagazinePage from "./pages/MagazinePage";
import PhotosPage from "./pages/PhotosPage";
import PageHero from "./components/PageHero";
import { SubscribeProvider, useSubscribe } from "./components/SubscribeDialog";

function PlaceholderPage({ title, description, breadcrumbLabel }: { title: string; description: string; breadcrumbLabel?: string }) {
  const [, setLocation] = useLocation();
  const { t } = useLanguage();
  const subscribe = useSubscribe();

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={title}
        subtitle={description}
        breadcrumbs={[
          { label: "Home", onClick: () => setLocation("/") },
          { label: breadcrumbLabel || title },
        ]}
      />
      <div className="py-12 md:py-16 px-4 md:px-8">
        {subscribe.isSubscribed ? (
          <div className="max-w-[1000px] mx-auto" data-testid="section-unlocked-content">
            <div
              className="flex items-center gap-3 mb-8 p-4 rounded-xl"
              style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}
            >
              <div
                className="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-white"
                style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}
              >
                <Bell className="w-5 h-5" />
              </div>
              <p className="m-0 text-[0.95rem]" style={{ color: "var(--neutral-700)" }}>
                You're subscribed. Below are the latest {title.toLowerCase()} — we'll also email you whenever new ones are posted.
              </p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {[1, 2, 3].map((i) => (
                <div
                  key={i}
                  className="bg-white rounded-[20px] p-6"
                  style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                  data-testid={`card-placeholder-item-${i}`}
                >
                  <div
                    className="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                    style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                  >
                    <FileText className="w-6 h-6" />
                  </div>
                  <h3 className="mb-2" style={{ color: "var(--primary-dark)" }}>
                    {title} Listing {i}
                  </h3>
                  <p className="text-[0.9rem] mb-4" style={{ color: "var(--neutral-600)" }}>
                    Placeholder details for this {title.toLowerCase().replace(/s$/, "")}. Full information will appear here once published.
                  </p>
                  <span
                    className="inline-block py-1 px-3 rounded-full text-xs font-semibold"
                    style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
                  >
                    Coming Soon
                  </span>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="text-center">
            <div
              className="max-w-[600px] mx-auto p-12 rounded-[20px]"
              style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}
            >
              <h3 className="mb-4" style={{ color: "var(--primary-dark)" }}>
                {t.noCurrent} {title}
              </h3>
              <p style={{ color: "var(--neutral-600)" }}>
                {t.noCurrentText.replace("{title}", title.toLowerCase())}
              </p>
              <button
                onClick={() => subscribe.open()}
                className="mt-6 py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white"
                style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                data-testid="button-subscribe-updates"
              >
                {t.subscribeForUpdates}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function Router() {
  const [, setLocation] = useLocation();
  const { t } = useLanguage();

  const handleNavigate = (page: string) => {
    const path = page === "home" ? "/" : `/${page}`;
    setLocation(path);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <Switch>
      <Route path="/">
        <HomePage onNavigate={handleNavigate} />
      </Route>
      <Route path="/about">
        <AboutPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/commissioners">
        <CommissionersPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/secretariat">
        <SecretariatPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/accreditation">
        <AccreditationPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/registration">
        <RegistrationPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/complaints">
        <ComplaintsPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/appeals">
        <AppealsPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/downloads">
        <DownloadsPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/events">
        <EventsPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/contact">
        <ContactPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/vacancies">
        <PlaceholderPage title={t.nav.vacancies} description="Current job opportunities at ZMC" />
      </Route>
      <Route path="/tenders">
        <TendersPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/press-releases">
        <PressReleasesPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/magazine">
        <MagazinePage onNavigate={handleNavigate} />
      </Route>
      <Route path="/photos">
        <PhotosPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/media-hub">
        <PlaceholderPage title={t.placeholder.title} description={t.placeholder.description} />
      </Route>
      <Route>
        <HomePage onNavigate={handleNavigate} />
      </Route>
    </Switch>
  );
}

function AppContent() {
  const [location, setLocation] = useLocation();
  
  const currentPage = location === "/" ? "home" : location.slice(1);

  const handleNavigate = (page: string) => {
    const path = page === "home" ? "/" : `/${page}`;
    setLocation(path);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  useEffect(() => {
    const handleScrollAnimations = () => {
      const elements = document.querySelectorAll(".scroll-animate");
      elements.forEach((el) => {
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight - 100) {
          el.classList.add("visible");
        }
      });
    };

    window.addEventListener("scroll", handleScrollAnimations);
    handleScrollAnimations();

    return () => window.removeEventListener("scroll", handleScrollAnimations);
  }, [location]);

  return (
    <div className="min-h-screen flex flex-col" style={{ background: "var(--neutral-50)" }}>
      <Header currentPage={currentPage} onNavigate={handleNavigate} />
      <main className="flex-1">
        <Router />
      </main>
      <Footer onNavigate={handleNavigate} />
      <SocialSidebar />
      <Chatbot onNavigate={handleNavigate} />
      <Toaster />
    </div>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <LanguageProvider>
        <SubscribeProvider>
          <AppContent />
        </SubscribeProvider>
      </LanguageProvider>
    </QueryClientProvider>
  );
}

export default App;
