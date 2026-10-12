import { useState, useEffect } from "react";
import { ExternalLink, Loader2, FileText, Calendar, Tag, Search, Clock, Building2, ArrowRight } from "lucide-react";
import PageHero from "../components/PageHero";
import { portalApi, PRAZ_EGP_URL } from "../lib/api";

interface TendersPageProps {
  onNavigate: (page: string) => void;
}

interface TenderRow {
  id: string;
  title: string;
  reference?: string;
  category?: string;
  issuingOrg?: string;
  publicationDate?: string;
  closingDate?: string;
}

const field = (obj: any, keys: string[]) => {
  const key = keys.find((k) => obj && obj[k] !== null && obj[k] !== undefined && obj[k] !== "");
  return key ? obj[key] : undefined;
};

const formatDate = (value?: string) =>
  value ? new Date(value).toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" }) : "TBA";

const isClosed = (value?: string) => (value ? new Date(value).getTime() < Date.now() : false);

const fromPortal = (t: any): TenderRow => ({
  id: String(t.id),
  title: field(t, ["title", "tender_name", "name"]) || "Tender Notice",
  reference: field(t, ["reference_number", "tender_reference", "reference", "ref_number"]),
  category: field(t, ["category", "tender_category"]),
  issuingOrg: field(t, ["issuing_organization", "issuing_org", "organization"]),
  publicationDate: field(t, ["published_at", "publication_date", "created_at"]),
  closingDate: field(t, ["closing_at", "closing_date", "deadline", "closing"]),
});

export default function TendersPage({ onNavigate }: TendersPageProps) {
  const [tenders, setTenders] = useState<TenderRow[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");

  useEffect(() => {
    async function fetchTenders() {
      try {
        setIsLoading(true);
        const response = await portalApi.getTenders(1, 50).catch(() => null);
        let portal: TenderRow[] = [];
        if (response) {
          const list: any[] = Array.isArray(response) ? response : response.data || response.content || [];
          portal = list.map(fromPortal);
        }
        setTenders(portal);
      } catch (err: any) {
        console.error("Error fetching tenders:", err);
        setError(err.message || "Failed to load tenders");
      } finally {
        setIsLoading(false);
      }
    }
    fetchTenders();
  }, []);

  const filtered = tenders.filter(
    (t) =>
      (t.title || "").toLowerCase().includes(search.toLowerCase()) ||
      (t.reference || "").toLowerCase().includes(search.toLowerCase()) ||
      (t.issuingOrg || "").toLowerCase().includes(search.toLowerCase()),
  );

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title="Tenders"
        subtitle="Current tender notices from the Zimbabwe Media Commission"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Tenders" }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          <div
            className="p-4 md:p-5 rounded-2xl mb-8 flex flex-wrap items-center justify-between gap-4"
            style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}
          >
            <p className="text-sm m-0" style={{ color: "var(--neutral-700)" }}>
              All public procurement opportunities are also published on the e-Government Procurement (e-GP) portal.
            </p>
            <a
              href={PRAZ_EGP_URL}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 py-2 px-4 rounded-xl font-semibold text-sm text-white transition-all hover:-translate-y-0.5"
              style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              data-testid="link-praz-portal"
            >
              Visit e-GP Portal <ExternalLink className="w-4 h-4" />
            </a>
          </div>

          <div className="relative mb-8">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5" style={{ color: "var(--neutral-400)" }} />
            <input
              type="text"
              placeholder="Search tenders by title, reference or organisation..."
              className="w-full py-4 pl-12 pr-4 rounded-2xl transition-all focus:outline-none"
              style={{ border: "2px solid var(--neutral-200)", background: "white" }}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              data-testid="input-search-tenders"
            />
          </div>

          {isLoading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin text-primary" />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading tender notices...</p>
            </div>
          ) : error ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)", boxShadow: "var(--shadow-sm)" }}>
              <p className="mb-6" style={{ color: "var(--neutral-600)" }}>{error}</p>
              <button
                onClick={() => window.location.reload()}
                className="py-2 px-6 rounded-xl font-semibold text-white border-none cursor-pointer"
                style={{ background: "var(--primary)" }}
              >
                Retry
              </button>
            </div>
          ) : filtered.length === 0 ? (
            <div className="bg-white rounded-2xl p-8 md:p-12 text-center" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
              <div className="w-20 h-20 md:w-24 md:h-24 rounded-full mx-auto mb-6 flex items-center justify-center" style={{ background: "var(--primary-lighter)" }}>
                <FileText className="w-10 h-10 md:w-12 md:h-12" style={{ color: "var(--primary)" }} />
              </div>
              <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }}>
                No Tender Notices at the Moment
              </h2>
              <p className="text-sm md:text-base mb-6" style={{ color: "var(--neutral-600)", maxWidth: "520px", margin: "0 auto 1.5rem" }}>
                All Zimbabwe Media Commission tenders are published on the e-Government Procurement (e-GP) portal. Visit the portal to view current opportunities and submit your bids.
              </p>
              <a
                href={PRAZ_EGP_URL}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-3 py-3 px-8 rounded-xl font-semibold text-base text-white transition-all hover:-translate-y-0.5"
                style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
              >
                <ExternalLink className="w-5 h-5" /> Visit e-GP System
              </a>
            </div>
          ) : (
            <div className="space-y-4">
              {filtered.map((tender) => {
                const closed = isClosed(tender.closingDate);
                const go = () => onNavigate(`tenders/${tender.id}`);
                return (
                  <div
                    key={tender.id}
                    className="bg-white rounded-2xl p-5 md:p-6 transition-all hover:-translate-y-0.5 cursor-pointer"
                    style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)", borderLeft: `4px solid ${closed ? "var(--neutral-300)" : "var(--primary)"}` }}
                    onClick={go}
                    data-testid={`tender-card-${tender.id}`}
                  >
                    <div className="flex items-start justify-between gap-4 flex-wrap">
                      <div className="flex-1 min-w-[240px]">
                        <div className="flex items-center gap-2 flex-wrap mb-2">
                          <span
                            className="py-0.5 px-2.5 rounded-full text-[0.7rem] font-bold uppercase text-white"
                            style={{ background: closed ? "var(--neutral-400)" : "var(--primary)" }}
                          >
                            {closed ? "Closed" : "Open"}
                          </span>
                          {tender.category && (
                            <span className="py-0.5 px-2.5 rounded-full text-[0.7rem] font-bold uppercase" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                              {tender.category}
                            </span>
                          )}
                        </div>
                        <h3 className="text-lg font-bold mb-2" style={{ color: "var(--primary-dark)" }}>{tender.title}</h3>
                        <div className="flex flex-wrap gap-x-5 gap-y-1.5 text-sm" style={{ color: "var(--neutral-600)" }}>
                          {tender.reference && (
                            <span className="flex items-center gap-1.5"><Tag className="w-4 h-4" style={{ color: "var(--primary)" }} /> {tender.reference}</span>
                          )}
                          {tender.issuingOrg && (
                            <span className="flex items-center gap-1.5"><Building2 className="w-4 h-4" style={{ color: "var(--primary)" }} /> {tender.issuingOrg}</span>
                          )}
                          <span className="flex items-center gap-1.5"><Calendar className="w-4 h-4" style={{ color: "var(--primary)" }} /> Published: {formatDate(tender.publicationDate)}</span>
                          <span className="flex items-center gap-1.5"><Clock className="w-4 h-4" style={{ color: "var(--primary)" }} /> Closes: {formatDate(tender.closingDate)}</span>
                        </div>
                      </div>
                      <button
                        className="shrink-0 py-2.5 px-5 rounded-xl font-semibold text-sm text-white border-none cursor-pointer flex items-center gap-2"
                        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                        onClick={(e) => {
                          e.stopPropagation();
                          go();
                        }}
                      >
                        View Details <ArrowRight className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
