import { useEffect, useRef } from "react";
import { ArrowLeft } from "lucide-react";
import { useLocation } from "wouter";

// Pages that already carry their own, more specific "Back to ..." link, plus the home page.
const NO_BACK = [/^\/$/, /^\/(events|notices|vacancies|tenders|press-releases)\/[^/]+\/?$/];

export function shouldShowBack(path: string): boolean {
  return !NO_BACK.some((re) => re.test(path));
}

/**
 * Floating "Back" button for every inner page.
 * If the visitor has moved between pages in this session it steps back in history;
 * if they landed here directly (search result, shared link) it goes to the home page instead
 * of leaving the site.
 */
export default function BackButton() {
  const [location, setLocation] = useLocation();
  const first = useRef(true);
  const navigated = useRef(false);

  useEffect(() => {
    if (first.current) {
      first.current = false;
      return;
    }
    navigated.current = true;
  }, [location]);

  if (!shouldShowBack(location)) return null;

  const goBack = () => {
    if (navigated.current && window.history.length > 1) {
      window.history.back();
    } else {
      setLocation("/");
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  return (
    <button
      type="button"
      onClick={goBack}
      aria-label="Go back"
      title="Go back"
      className="fixed bottom-4 left-4 md:bottom-8 md:left-8 z-[900] inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold cursor-pointer transition-all hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 print:hidden"
      style={{
        background: "#ffffff",
        color: "var(--primary-dark)",
        border: "1px solid var(--neutral-200)",
        boxShadow: "0 4px 14px rgba(0,0,0,0.12)",
      }}
      data-testid="button-back"
    >
      <ArrowLeft className="w-4 h-4" aria-hidden="true" />
      <span>Back</span>
    </button>
  );
}
