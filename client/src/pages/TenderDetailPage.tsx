import { useEffect, useState } from "react";
import { useParams } from "wouter";
import { FileText, Calendar, Loader2, ChevronLeft, Tag, Package, ExternalLink, Clock, Building2 } from "lucide-react";
import PageHero from "../components/PageHero";
import { portalApi, PRAZ_EGP_URL } from "../lib/api";

interface TenderDetailPageProps {
  onNavigate: (page: string) => void;
}

const formatDate = (value?: string) =>
  value ? new Date(value).toLocaleDateString(undefined, { year: "numeric", month: "long", day: "numeric" }) : "TBA";

const isClosed = (value?: string) => (value ? new Date(value).getTime() < Date.now() : false);

const field = (obj: any, keys: string[]) => {
  const key = keys.find((k) => obj && obj[k] !== null && obj[k] !== undefined && obj[k] !== "");
  return key ? obj[key] : undefined;
};

interface NormalisedTender {
  id: string;
  title: string;
  reference?: string;
  description?: string;
  bodyHtml?: string;
  category?: string;
  issuingOrg?: string;
  quantity?: string | number;
  publicationDate?: string;
  closingDate?: string;
  egpUrl?: string;
}

function normalisePortal(t: any): NormalisedTender {
  return {
    id: String(t.id),
    title: field(t, ["title", "tender_name", "name"]) || "Tender Notice",
    reference: field(t, ["reference_number", "tender_reference", "reference", "ref_number"]),
    description: field(t, ["description", "summary"]),
    bodyHtml: field(t, ["body"]),
    category: field(t, ["category", "tender_category"]),
    issuingOrg: field(t, ["issuing_organization", "issuing_org", "organization"]),
    quantity: field(t, ["quantity", "number_of_items"]),
    publicationDate: field(t, ["published_at", "publication_date", "created_at"]),
    closingDate: field(t, ["closing_at", "closing_date", "deadline", "closing"]),
    egpUrl: field(t, ["praz_link", "egp_url", "external_url", "tender_url"]),
  };
}

export default function TenderDetailPage({ onNavigate }: TenderDetailPageProps) {
  const params = useParams<{ id: string }>();
  const id = params.id || "";
  const [tender, setTender] = useState<NormalisedTender | null>(null);
  const [notFound, setNotFound] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    async function load() {
      setLoading(true);
      setNotFound(false);
      try {
        const response = await portalApi.getTenders(1, 100);
        const list: any[] = Array.isArray(response) ? response : response.data || response.content || [];
        const match = list.find((t) => String(t.id) === id);
        if (mounted) {
          if (match) setTender(normalisePortal(match));
          else setNotFound(true);
        }
      } catch (err) {
        console.error("Error loading tender:", err);
        if (mounted) setNotFound(true);
      } finally {
        if (mounted) setLoading(false);
      }
    }
    load();
    return () => {
      mounted = false;
    };
  }, [id]);

  const closed = tender ? isClosed(tender.closingDate) : false;
  const egpLink = tender?.egpUrl || PRAZ_EGP_URL;

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={tender?.title || "Tender Details"}
        subtitle="Procurement opportunity with the Zimbabwe Media Commission"
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: "Tenders", onClick: () => onNavigate("tenders") },
          { label: "Details" },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[900px] mx-auto">
          <button
            onClick={() => onNavigate("tenders")}
            className="flex items-center gap-2 text-sm font-medium mb-8 bg-transparent border-none cursor-pointer hover:opacity-80 p-0"
            style={{ color: "var(--primary)" }}
            data-testid="button-back-tenders"
          >
            <ChevronLeft className="w-4 h-4" /> Back to all tenders
          </button>

          {loading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading tender...</p>
            </div>
          ) : notFound || !tender ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <FileText className="w-16 h-16 mx-auto mb-4" style={{ color: "var(--neutral-200)" }} />
              <h3 className="text-xl font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>Tender not found</h3>
              <p style={{ color: "var(--neutral-500)" }}>This tender may have closed or been removed.</p>
            </div>
          ) : (
            <div className="bg-white rounded-2xl p-6 md:p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
              <div className="flex items-center gap-3 flex-wrap mb-4">
                <span
                  className="py-1 px-3 rounded-full text-xs font-bold uppercase text-white"
                  style={{ background: closed ? "var(--neutral-400)" : "var(--primary)" }}
                >
                  {closed ? "Closed" : "Open"}
                </span>
                {tender.category && (
                  <span className="py-1 px-3 rounded-full text-xs font-bold uppercase" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                    {tender.category}
                  </span>
                )}
              </div>

              <h1 className="text-2xl md:text-3xl font-bold mb-2" style={{ color: "var(--primary-dark)" }}>{tender.title}</h1>
              {tender.reference && (
                <p className="text-sm mb-6" style={{ color: "var(--neutral-500)" }}>Reference: {tender.reference}</p>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <Calendar className="w-5 h-5" style={{ color: "var(--primary)" }} />
                  <div className="text-sm">
                    <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Published</p>
                    <p className="font-semibold m-0">{formatDate(tender.publicationDate)}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <Clock className="w-5 h-5" style={{ color: "var(--primary)" }} />
                  <div className="text-sm">
                    <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Closing Date</p>
                    <p className="font-semibold m-0">{formatDate(tender.closingDate)}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <Building2 className="w-5 h-5" style={{ color: "var(--primary)" }} />
                  <div className="text-sm">
                    <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Issuing Organisation</p>
                    <p className="font-semibold m-0">{tender.issuingOrg || "Zimbabwe Media Commission"}</p>
                  </div>
                </div>
              </div>

              {tender.quantity && (
                <div className="flex items-center gap-2 text-sm mb-6" style={{ color: "var(--neutral-600)" }}>
                  <Package className="w-4 h-4" style={{ color: "var(--primary)" }} />
                  <span><strong>Quantity:</strong> {tender.quantity}</span>
                </div>
              )}

              {(tender.bodyHtml || tender.description) && (
                <div className="mb-8">
                  <h4 className="font-bold text-lg mb-3 flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <FileText className="w-5 h-5" /> Tender Details
                  </h4>
                  {tender.bodyHtml ? (
                    <div
                      className="p-4 rounded-xl leading-relaxed"
                      style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)", color: "var(--neutral-700)" }}
                      dangerouslySetInnerHTML={{ __html: tender.bodyHtml }}
                    />
                  ) : (
                    <p className="text-sm leading-relaxed" style={{ color: "var(--neutral-600)" }}>{tender.description}</p>
                  )}
                </div>
              )}

              <div className="p-4 rounded-xl" style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}>
                <h4 className="font-bold mb-2" style={{ color: "var(--primary-dark)" }}>Submit Your Bid</h4>
                <p className="text-sm mb-3" style={{ color: "var(--neutral-700)" }}>
                  Tenders for public procurement are managed on the e-Government Procurement (e-GP) portal. Visit the portal for full
                  requirements and to submit your bid.
                </p>
                <a
                  href={egpLink}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl font-semibold text-sm text-white"
                  style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                  data-testid="link-egp-detail"
                >
                  Go to e-GP Portal <ExternalLink className="w-4 h-4" />
                </a>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
