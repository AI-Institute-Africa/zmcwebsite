import { useState, useEffect } from "react";
import { Megaphone, AlertTriangle, Building2, Calendar, ChevronLeft, ChevronRight, Newspaper } from "lucide-react";

export default function NewsSlider() {
  const [currentSlide, setCurrentSlide] = useState(0);

  const slides = [
    {
      icon: Megaphone,
      badge: "Announcement",
      title: "2024 Media Awards Nominations Open",
      text: "Submit your nominations for the annual Zimbabwe Media Excellence Awards. Categories include print, broadcast, and digital media.",
      date: "December 1, 2024",
      bgClass: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
    },
    {
      icon: AlertTriangle,
      badge: "Urgent",
      title: "Accreditation Renewal Deadline Approaching",
      text: "All media practitioners are reminded to renew their accreditation before December 31, 2024 to avoid penalties.",
      date: "November 28, 2024",
      bgClass: "linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)",
    },
    {
      icon: Building2,
      badge: "Opening",
      title: "New Regional Office in Bulawayo",
      text: "ZMC has opened a new regional office in Bulawayo to better serve media practitioners in Matabeleland provinces.",
      date: "November 25, 2024",
      bgClass: "linear-gradient(135deg, var(--primary-dark) 0%, #0a2f0d 100%)",
    },
  ];

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 5000);
    return () => clearInterval(timer);
  }, [slides.length]);

  const goToSlide = (index: number) => {
    setCurrentSlide(index);
  };

  const nextSlide = () => {
    setCurrentSlide((prev) => (prev + 1) % slides.length);
  };

  const prevSlide = () => {
    setCurrentSlide((prev) => (prev - 1 + slides.length) % slides.length);
  };

  return (
    <section
      className="py-20 px-8"
      style={{ background: "linear-gradient(180deg, var(--neutral-50) 0%, var(--white) 100%)" }}
    >
      <div className="text-center mb-10">
        <h2 className="flex items-center justify-center gap-3" style={{ color: "var(--primary-dark)", fontSize: "1.75rem" }}>
          <Newspaper className="w-7 h-7" />
          Latest News & Updates
        </h2>
        <p style={{ color: "var(--neutral-500)" }}>Stay informed with the latest from ZMC</p>
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
                  className="min-h-[320px] flex items-center justify-center relative overflow-hidden"
                  style={{ background: slide.bgClass }}
                >
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
                </div>
                <div
                  className="p-7 bg-white"
                  style={{ borderTop: "3px solid var(--accent)" }}
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
                    className="flex items-center gap-1.5 mt-3 text-[0.8rem]"
                    style={{ color: "var(--neutral-400)" }}
                  >
                    <Calendar className="w-3.5 h-3.5" />
                    {slide.date}
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
