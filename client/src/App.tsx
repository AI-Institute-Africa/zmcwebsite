import { useEffect } from "react";
import { Switch, Route, useLocation } from "wouter";
import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./lib/queryClient";
import { Toaster } from "@/components/ui/toaster";
import { LanguageProvider } from "./i18n/LanguageContext";
import Header from "./components/Header";
import Footer from "./components/Footer";
import Chatbot from "./components/Chatbot";
import SocialSidebar from "./components/SocialSidebar";
import HomePage from "./pages/HomePage";
import AboutPage from "./pages/AboutPage";
import AccreditationPage from "./pages/AccreditationPage";
import AccreditedPractitionersPage from "./pages/AccreditedPractitionersPage";
import VerifyAccreditationPage from "./pages/VerifyAccreditationPage";
import RegistrationPage from "./pages/RegistrationPage";
import RegisteredMediaHousesPage from "./pages/RegisteredMediaHousesPage";
import ComplaintsPage from "./pages/ComplaintsPage";
import AppealsPage from "./pages/AppealsPage";
import DownloadsPage from "./pages/DownloadsPage";
import EventsPage from "./pages/EventsPage";
import EventDetailPage from "./pages/EventDetailPage";
import ContactPage from "./pages/ContactPage";
import SecretariatPage from "./pages/SecretariatPage";
import CommissionersPage from "./pages/CommissionersPage";
import TendersPage from "./pages/TendersPage";
import TenderDetailPage from "./pages/TenderDetailPage";
import PressReleasesPage from "./pages/PressReleasesPage";
import NoticeDetailPage from "./pages/NoticeDetailPage";
import MagazinePage from "./pages/MagazinePage";
import PhotosPage from "./pages/PhotosPage";
import MediaHubPage from "./pages/MediaHubPage";
import VacanciesPage from "./pages/VacanciesPage";
import VacancyDetailPage from "./pages/VacancyDetailPage";
import { SubscribeProvider } from "./components/SubscribeDialog";

function Router() {
  const [, setLocation] = useLocation();

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
      <Route path="/accredited-practitioners">
        <AccreditedPractitionersPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/verify-accreditation">
        <VerifyAccreditationPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/registration">
        <RegistrationPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/registered-media-houses">
        <RegisteredMediaHousesPage onNavigate={handleNavigate} />
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
      <Route path="/events/:id">
        <EventDetailPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/notices/:id">
        <NoticeDetailPage onNavigate={handleNavigate} backTo={{ label: "Press Releases", page: "press-releases" }} />
      </Route>
      <Route path="/contact">
        <ContactPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/vacancies">
        <VacanciesPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/vacancies/:id">
        <VacancyDetailPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/tenders">
        <TendersPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/tenders/:id">
        <TenderDetailPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/press-releases">
        <PressReleasesPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/press-releases/:id">
        <PressReleasesPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/magazine">
        <MagazinePage onNavigate={handleNavigate} />
      </Route>
      <Route path="/photos">
        <PhotosPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/media-hub">
        <MediaHubPage onNavigate={handleNavigate} />
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
