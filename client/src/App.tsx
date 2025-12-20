import { useEffect } from "react";
import { Switch, Route, useLocation } from "wouter";
import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./lib/queryClient";
import { Toaster } from "@/components/ui/toaster";
import Header from "./components/Header";
import Footer from "./components/Footer";
import Chatbot from "./components/Chatbot";
import SocialSidebar from "./components/SocialSidebar";
import HomePage from "./pages/HomePage";
import AboutPage from "./pages/AboutPage";
import AccreditationPage from "./pages/AccreditationPage";
import RegistrationPage from "./pages/RegistrationPage";
import ComplaintsPage from "./pages/ComplaintsPage";
import DownloadsPage from "./pages/DownloadsPage";
import EventsPage from "./pages/EventsPage";
import ContactPage from "./pages/ContactPage";
import SecretariatPage from "./pages/SecretariatPage";
import CommissionersPage from "./pages/CommissionersPage";

function PlaceholderPage({ title, description }: { title: string; description: string }) {
  const [, setLocation] = useLocation();
  
  return (
    <div className="animate-fadeIn pt-[130px]">
      <div
        className="py-16 px-8 text-center"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3">{title}</h1>
        <p className="text-white/85">{description}</p>
      </div>
      <div className="py-16 px-8 text-center">
        <div
          className="max-w-[600px] mx-auto p-12 rounded-[20px]"
          style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}
        >
          <h3 className="mb-4" style={{ color: "var(--primary-dark)" }}>
            No Current {title}
          </h3>
          <p style={{ color: "var(--neutral-600)" }}>
            There are currently no open {title.toLowerCase()}. Please check back later or subscribe to our newsletter for updates.
          </p>
          <button
            onClick={() => setLocation("/events")}
            className="mt-6 py-3 px-6 rounded-xl font-semibold border-none cursor-pointer text-white"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
          >
            Subscribe for Updates
          </button>
        </div>
      </div>
    </div>
  );
}

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
      <Route path="/registration">
        <RegistrationPage onNavigate={handleNavigate} />
      </Route>
      <Route path="/complaints">
        <ComplaintsPage onNavigate={handleNavigate} />
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
        <PlaceholderPage title="Vacancies" description="Current job opportunities at ZMC" />
      </Route>
      <Route path="/tenders">
        <PlaceholderPage title="Tenders" description="Current tender opportunities" />
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
      <AppContent />
    </QueryClientProvider>
  );
}

export default App;
