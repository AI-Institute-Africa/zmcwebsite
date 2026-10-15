import { useState, useEffect } from "react";
import { Megaphone, AlertTriangle, Building2, Calendar, ChevronLeft, ChevronRight, Newspaper, Loader2, ExternalLink } from "lucide-react";
import { useLocation } from "wouter";
import { useLanguage } from "../i18n/LanguageContext";
import { portalApi, portalAssetUrl } from "../lib/api";

export default function NewsSlider() {
  const { t } = useLanguage();
  const [, setLocation] = useLocation();
  const [currentSlide, setCurrentSlide] = useState(0);
  const [news, setNews] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchNews() {
      try {
        setIsLoading(true);
        const response = await portalApi.getNews(1, 8);
        if (response.data && Array.isArray(response.data)) {
          setNews(response.data);
        } else if (Array.isArray(response)) {
          setNews(response);
        }
      } catch (err: any) {
        console.error("Error fetching news:", err);
        setError(err.message || "Failed to load news");
      } finally {
        setIsLoading(false);
      }
    }
    fetchNews();
  }, []);

  const slideImage = (item: any) => portalAssetUrl(item.image_path);

  const slideText = (item: any) => {
    const raw = item.excerpt || item.text || "";
    if (raw) return raw;
    const fromBody = (item.body || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
    return fromBody.length > 220 ? fromBody.slice(0, 220) + "…" : fromBody;
  };

  const slides = news.length > 0 
    ? news.map((item, index) => ({
        id: item.id,
        icon: index === 0 ? Megaphone : index === 1 ? AlertTriangle : Building2,
        badge: t.news.announcement,
        title: item.title,
        text: slideText(item),
        date: item.created_at ? new Date(item.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
        imageUrl: slideImage(item),
        bgClass: index === 0 ? "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" : 
                  index === 1 ? "linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)" : 
                  "linear-gradient(135deg, var(--primary-dark) 0%, #0a2f0d 100%)",
      }))
    : [];

  useEffect(() => {
    if (slides.length === 0) return;
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 5000);
    return () => clearInterval(timer);
  }, [slides.length]);

  const goToSlide = (index: number) => {
    setCurrentSlide(index);
  };

  const nextSlide = () => {
    if (slides.length === 0) return;
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const prevSlide = () => {
    if (slides.length === 0) return;
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  if (isLoading) {
    return (
      <section className="py-20 px-8 flex items-center justify-center" style={{ background: "linear-gradient(180deg, var(--neutral-50) 0%, var(--white) 100%)" }}>
        <div className="flex flex-col items-center gap-3">
          <Loader2 className="w-10 h-10 animate-spin text-primary" />
          <p className="text-sm font-medium" style={{ color: "var(--neutral-500)" }}>Loading latest news...</p>
        </div>
      </section>
    );
  }

  if (error || slides.length === 0) {
    return (
      <section className="py-20 px-8 text-center" style={{ background: "linear-gradient(180deg, var(--neutral-50) 0%, var(--white) 100%)" }}>
        <div className="max-w-md mx-auto p-8 rounded-2xl bg-white border" style={{ boxShadow: "var(--shadow-sm)" }}>
          <Newspaper className="w-12 h-12 mx-auto mb-4 text-neutral-300" />
          <h3 className="text-lg font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>
            {error ? "Something went wrong" : "No news available"}
          </h3>
          <p className="text-sm" style={{ color: "var(--neutral-500)" }}>
            {error || "Check back later for the latest updates from ZMC."}
          </p>
        </div>
      </section>
    );
  }

  return (
    <section
      className="py-20 px-8"
      style={{ background: "linear-gradient(180deg, var(--neutral-50) 0%, var(--white) 100%)" }}
    >
      <div className="text-center mb-10">
        <h2 className="flex items-center justify-center gap-3" style={{ color: "var(--primary-dark)", fontSize: "1.75rem" }}>
          <Newspaper className="w-7 h-7" />
          {t.news.title}
        </h2>
        <p style={{ color: "var(--neutral-500)" }}>{t.news.subtitle}</p>
      </div>

      <div className="max-w-[900px] mx-auto relative">
        <div
          className="overflow-hidden rounded-xl"
          style={{
            boxShadow: "0 4px 24px rgba(0,0,0,0.08)",
            border: "1px solid var(--neutral-200)",
          }}
        >
          <div
            className="flex transition-transform duration-500 ease-in-out"
            style={{ transform: `translateX(-${currentSlide * 100}%)` }}
          >
            {slides.map((slide, index) => (
              <div key={index} className="min-w-full relative">
                <div
                  className="min-h-[320px] md:min-h-[340px] flex items-center justify-center relative overflow-hidden cursor-pointer"
                  style={{
                    background: slide.imageUrl
                      ? `url("${slide.imageUrl}") center/cover no-repeat`
                      : slide.bgClass,
                  }}
                  onClick={() => setLocation(`/press-releases/${slide.id}`)}
                  data-testid={`slide-click-${index}`}
                >
                  {slide.imageUrl ? (
                    <>
                      <div
                        className="absolute inset-0"
                        style={{ background: "linear-gradient(180deg, rgba(0,0,0,0.10) 0%, rgba(0,0,0,0.45) 100%)" }}
                      />
                    </>
                  ) : (
                    <>
                      <div className="absolute inset-0 opacity-50">
                        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                          <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                              <path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.03)" strokeWidth="0.5"/>
                            </pattern>
                          </defs>
                          <rect width="100%" height="100%" fill="url(#grid)"/>
                        </svg>
                      </div>
                      <span
                        className="relative z-10 p-6 rounded-full"
                        style={{
                          background: "rgba(255,255,255,0.1)",
                          border: "2px solid rgba(255,255,255,0.2)",
                          color: "rgba(255,255,255,0.9)",
                        }}
                      >
                        <slide.icon className="w-14 h-14" />
                      </span>
                    </>
                  )}
                </div>
                <div
                  className="p-7 bg-white cursor-pointer transition-colors"
                  style={{ borderTop: "3px solid var(--accent)" }}
                  onClick={() => setLocation(`/press-releases/${slide.id}`)}
                >
                  <span
                    className="inline-block py-1 px-3 rounded text-[0.7rem] font-semibold mb-3 uppercase tracking-wide text-white"
                    style={{ background: "var(--primary)" }}
                  >
                    {slide.badge}
                  </span>
                  <h3 className="mb-2 text-[1.25rem]" style={{ color: "var(--neutral-800)" }}>
                    {slide.title}
                  </h3>
                  <p className="text-[0.9rem] leading-relaxed" style={{ color: "var(--neutral-600)" }}>
                    {slide.text}
                  </p>
                  <div
                    className="flex items-center justify-between mt-3"
                  >
                    <div className="flex items-center gap-1.5 text-[0.8rem]" style={{ color: "var(--neutral-400)" }}>
                      <Calendar className="w-3.5 h-3.5" />
                      {slide.date}
                    </div>
                    <span
                      className="inline-flex items-center gap-1 text-[0.8rem] font-semibold"
                      style={{ color: "var(--primary)" }}
                    >
                      {slide.badge} <ExternalLink className="w-3.5 h-3.5" />
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Arrows */}
        <div className="absolute top-1/2 w-full flex justify-between -translate-y-1/2 px-4 pointer-events-none">
          <button
            onClick={prevSlide}
            className="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer transition-all pointer-events-auto hover:bg-[var(--primary)] hover:text-white hover:border-[var(--primary)]"
            style={{
              background: "rgba(255,255,255,0.95)",
              border: "1px solid var(--neutral-200)",
              color: "var(--primary-dark)",
              boxShadow: "0 2px 8px rgba(0,0,0,0.08)",
            }}
            data-testid="button-prev-slide"
          >
            <ChevronLeft className="w-4.5 h-4.5" />
          </button>
          <button
            onClick={nextSlide}
            className="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer transition-all pointer-events-auto hover:bg-[var(--primary)] hover:text-white hover:border-[var(--primary)]"
            style={{
              background: "rgba(255,255,255,0.95)",
              border: "1px solid var(--neutral-200)",
              color: "var(--primary-dark)",
              boxShadow: "0 2px 8px rgba(0,0,0,0.08)",
            }}
            data-testid="button-next-slide"
          >
            <ChevronRight className="w-4.5 h-4.5" />
          </button>
        </div>

        {/* Dots */}
        <div className="flex justify-center gap-2 mt-8">
          {slides.map((_, index) => (
            <button
              key={index}
              onClick={() => goToSlide(index)}
              className={`h-2 rounded-full cursor-pointer transition-all ${
                index === currentSlide ? "w-6" : "w-2"
              }`}
              style={{
                background: index === currentSlide ? "var(--primary)" : "var(--neutral-300)",
              }}
              data-testid={`slide-dot-${index}`}
            />
          ))}
        </div>
      </div>
    </section>
  );
}
