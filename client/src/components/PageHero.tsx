interface PageHeroProps {
  title: string;
  subtitle?: string;
  breadcrumbs: { label: string; onClick?: () => void }[];
}

export default function PageHero({ title, subtitle, breadcrumbs }: PageHeroProps) {
  return (
    <div
      className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
      style={{ background: "linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 40%, var(--primary-dark) 100%)" }}
    >
      {/* Diagonal lines — bottom left */}
      <svg
        className="absolute bottom-0 left-0 opacity-20"
        width="160"
        height="160"
        viewBox="0 0 160 160"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        {[0, 18, 36, 54, 72, 90].map((offset, i) => (
          <line
            key={i}
            x1={-10 + offset}
            y1={170}
            x2={170}
            y2={-10 + offset}
            stroke="white"
            strokeWidth="2.5"
          />
        ))}
      </svg>

      {/* Dot grid — top right */}
      <svg
        className="absolute top-3 right-16 opacity-25"
        width="140"
        height="90"
        viewBox="0 0 140 90"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        {Array.from({ length: 6 }, (_, row) =>
          Array.from({ length: 10 }, (_, col) => (
            <circle
              key={`${row}-${col}`}
              cx={col * 14 + 7}
              cy={row * 14 + 7}
              r="2.5"
              fill="white"
            />
          ))
        )}
      </svg>

      {/* Concentric arcs — bottom right */}
      <svg
        className="absolute bottom-0 right-0 opacity-20"
        width="130"
        height="130"
        viewBox="0 0 130 130"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        {[20, 40, 60, 80, 100, 120].map((r, i) => (
          <path
            key={i}
            d={`M ${130} ${130 - r} A ${r} ${r} 0 0 0 ${130 - r} ${130}`}
            stroke="white"
            strokeWidth="2.5"
            fill="none"
          />
        ))}
      </svg>

      <h1 className="text-white mb-3 relative text-2xl md:text-4xl font-bold">{title}</h1>
      {subtitle && (
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative mb-0">
          {subtitle}
        </p>
      )}
      <div className="flex justify-center gap-2 mt-4 text-[0.9rem] flex-wrap relative">
        {breadcrumbs.map((crumb, i) => (
          <span key={i} className="flex items-center gap-2">
            {i > 0 && <span className="text-white/60">/</span>}
            {crumb.onClick ? (
              <a
                href="#"
                onClick={(e) => { e.preventDefault(); crumb.onClick!(); }}
                className="text-white/70 hover:text-white transition-colors"
              >
                {crumb.label}
              </a>
            ) : (
              <span className="font-semibold" style={{ color: "var(--accent-light, #fde68a)" }}>
                {crumb.label}
              </span>
            )}
          </span>
        ))}
      </div>
    </div>
  );
}
