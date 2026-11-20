import { useEffect } from "react";
import { Link } from "wouter";

export default function NotFound() {
  useEffect(() => {
    const prev = document.title;
    document.title = "Page not found | Zimbabwe Media Commission";
    const meta = document.createElement("meta");
    meta.name = "robots";
    meta.content = "noindex";
    document.head.appendChild(meta);
    return () => {
      document.title = prev;
      meta.remove();
    };
  }, []);

  return (
    <div className="min-h-[60vh] flex items-center justify-center px-4 pt-32 pb-16">
      <div className="max-w-md text-center">
        <p className="text-sm font-semibold mb-2" style={{ color: "var(--primary-dark)" }}>404</p>
        <h1 className="text-3xl font-bold mb-3" style={{ color: "var(--neutral-900)" }}>Page not found</h1>
        <p className="mb-6" style={{ color: "var(--neutral-600)" }}>
          The page you are looking for does not exist or has moved.
        </p>
        <Link
          href="/"
          className="inline-block px-6 py-3 rounded-xl font-semibold text-white"
          style={{ background: "var(--primary-dark)" }}
          data-testid="link-home"
        >
          Back to home
        </Link>
      </div>
    </div>
  );
}
