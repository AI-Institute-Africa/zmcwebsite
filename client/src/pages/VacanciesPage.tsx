import { useState, useEffect } from "react";
import { Building2, Calendar, Loader2, Search, MapPin, FileText } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import { portalApi } from "../lib/api";

interface VacanciesPageProps {
  onNavigate: (page: string) => void;
}

interface VacancyRow {
  id: string;
  title: string;
  reference?: string;
  closingAt?: string;
  type?: string;
  station?: string;
  department?: string;
}

const formatDate = (value?: string) =>
  value ? new Date(value).toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" }) : "TBA";

const isClosed = (vacancy: VacancyRow) => {
  if (!vacancy.closingAt) return false;
  return new Date(vacancy.closingAt).getTime() < Date.now();
};

const fromPortal = (v: any): VacancyRow => ({
  id: String(v.id),
  title: v.title,
  reference: v.vacancy_reference,
  closingAt: v.closing_at,
  type: v.employment_type || "Full-time",
  station: v.duty_station || "Not Specified",
});

export default function VacanciesPage({ onNavigate }: VacanciesPageProps) {
  const { t } = useLanguage();
  const [vacancies, setVacancies] = useState<VacancyRow[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");

  useEffect(() => {
    async function fetchVacancies() {
      try {
        setIsLoading(true);
        const response = await portalApi.getVacancies(1, 50).catch(() => null);
        let portalVacancies: VacancyRow[] = [];
        if (response) {
          const list: any[] = Array.isArray(response) ? response : response.data || response.content || [];
          portalVacancies = list.map(fromPortal);
        }
        setVacancies(portalVacancies);
      } catch (err: any) {
        console.error("Error fetching vacancies:", err);
        setError(err.message || "Failed to load vacancies");
      } finally {
        setIsLoading(false);
      }
    }
    fetchVacancies();
  }, []);

  const filteredVacancies = vacancies.filter(
    (v) =>
      (v.title || "").toLowerCase().includes(search.toLowerCase()) ||
      (v.reference || "").toLowerCase().includes(search.toLowerCase()) ||
      (v.type || "").toLowerCase().includes(search.toLowerCase()) ||
      (v.station || "").toLowerCase().includes(search.toLowerCase()),
  );

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.nav.vacancies}
        subtitle="Current job opportunities at the Zimbabwe Media Commission"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: t.nav.vacancies }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <div className="relative mb-10">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5" style={{ color: "var(--neutral-400)" }} />
            <input
              type="text"
              placeholder="Search vacancies by position, reference, type or duty station..."
              className="w-full py-4 pl-12 pr-4 rounded-2xl transition-all focus:outline-none"
              style={{ border: "2px solid var(--neutral-200)", background: "white" }}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              data-testid="input-search-vacancies"
            />
          </div>

          {isLoading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading opportunities...</p>
            </div>
          ) : error ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <p className="mb-4" style={{ color: "var(--neutral-600)" }}>{error}</p>
              <button
                onClick={() => window.location.reload()}
                className="py-2 px-6 rounded-xl font-semibold text-white border-none cursor-pointer"
                style={{ background: "var(--primary)" }}
              >
                Retry
              </button>
            </div>
          ) : filteredVacancies.length === 0 ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <Building2 className="w-16 h-16 mx-auto mb-4" style={{ color: "var(--neutral-200)" }} />
              <h3 className="text-xl font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>No vacancies found</h3>
              <p style={{ color: "var(--neutral-500)" }}>We currently have no open positions matching your search. Please check back later.</p>
            </div>
          ) : (
            <div className="bg-white rounded-2xl overflow-hidden" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
              <div
                className="hidden md:grid grid-cols-12 gap-4 px-6 py-4 text-xs font-bold uppercase tracking-wide border-b"
                style={{ color: "var(--neutral-400)", borderColor: "var(--neutral-100)" }}
              >
                <div className="col-span-4">Position Advertised</div>
                <div className="col-span-2">Reference No.</div>
                <div className="col-span-2">Closing Date</div>
                <div className="col-span-2">Type</div>
                <div className="col-span-2">Duty Station</div>
              </div>

              {filteredVacancies.map((vacancy) => {
                const closed = isClosed(vacancy);
                const go = () => onNavigate(`vacancies/${vacancy.id}`);
                return (
                  <div
                    key={vacancy.id}
                    className="md:grid grid-cols-12 gap-4 items-center px-6 py-5 border-b transition-colors hover:bg-neutral-50 md:cursor-pointer"
                    style={{ borderColor: "var(--neutral-100)", background: closed ? "var(--neutral-50)" : "white" }}
                    onClick={go}
                    data-testid={`vacancy-row-${vacancy.id}`}
                  >
                    <div className="md:hidden space-y-2">
                      <div className="flex items-start justify-between gap-3">
                        <h3 className="font-bold" style={{ color: "var(--primary-dark)" }}>{vacancy.title}</h3>
                        <span
                          className="shrink-0 py-1 px-2.5 rounded-full text-[0.7rem] font-bold text-white"
                          style={{ background: closed ? "var(--neutral-400)" : "var(--primary)" }}
                        >
                          {closed ? "Closed" : "Open"}
                        </span>
                      </div>
                      <p className="text-xs" style={{ color: "var(--neutral-500)" }}>Ref: {vacancy.reference || "—"}</p>
                      <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs mt-1" style={{ color: "var(--neutral-600)" }}>
                        <span className="flex items-center gap-1"><Calendar className="w-3.5 h-3.5" /> Closes: {formatDate(vacancy.closingAt)}</span>
                        <span className="flex items-center gap-1"><FileText className="w-3.5 h-3.5" /> {vacancy.type}</span>
                        <span className="flex items-center gap-1"><MapPin className="w-3.5 h-3.5" /> {vacancy.station}</span>
                      </div>
                    </div>

                    <div className="hidden md:block col-span-4">
                      <span className="font-semibold" style={{ color: "var(--primary-dark)" }}>{vacancy.title}</span>
                      {vacancy.department && <p className="text-xs m-0 mt-1" style={{ color: "var(--neutral-400)" }}>{vacancy.department}</p>}
                    </div>
                    <div className="hidden md:block col-span-2 text-sm" style={{ color: "var(--neutral-600)" }}>{vacancy.reference || "—"}</div>
                    <div className="hidden md:block col-span-2 text-sm" style={{ color: "var(--neutral-600)" }}>{formatDate(vacancy.closingAt)}</div>
                    <div className="hidden md:block col-span-2 text-sm" style={{ color: "var(--neutral-600)" }}>{vacancy.type}</div>
                    <div className="hidden md:flex col-span-2 items-center justify-between gap-2 text-sm" style={{ color: "var(--neutral-600)" }}>
                      <span>{vacancy.station}</span>
                      <button
                        className="shrink-0 py-1.5 px-3 rounded-lg text-xs font-semibold text-white transition-all hover:brightness-110 cursor-pointer border-none"
                        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                        onClick={(e) => {
                          e.stopPropagation();
                          go();
                        }}
                      >
                        View Details
                      </button>
                    </div>
                  </div>
                );
              })}

              <div className="px-6 py-4 text-xs" style={{ color: "var(--neutral-400)" }}>
                {filteredVacancies.length} position{filteredVacancies.length === 1 ? "" : "s"} found. Click any row to view full details.
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
