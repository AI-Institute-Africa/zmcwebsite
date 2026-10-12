import { useEffect, useState } from "react";
import { useParams } from "wouter";
import { Calendar, Loader2, ArrowLeft, FileText, Mail, Phone, Zap, BellRing } from "lucide-react";
import PageHero from "../components/PageHero";
import { portalApi } from "../lib/api";

interface NoticeDetailPageProps {
  onNavigate: (page: string) => void;
  backTo?: { label: string; page: string };
}

const fmtDate = (iso?: string) => (iso ? new Date(iso).toLocaleDateString(undefined, { year: "numeric", month: "long", day: "numeric" }) : null);

interface NormalisedNotice {
  id: string;
  title: string;
  category?: string;
  urgency?: string;
  date?: string;
  reference?: string;
  summary?: string;
  bodyHtml?: string;
  issuingAuthority?: string;
  signedBy?: string;
  audience?: string;
  effectiveDate?: string;
  expiresAt?: string;
  language?: string;
  legalReference?: string;
  contactName?: string;
  contactPhone?: string;
  contactEmail?: string;
  attachmentName?: string;
}

function fromPortal(n: any): NormalisedNotice {
  return {
    id: String(n.id),
    title: n.title,
    category: n.notice_category,
    urgency: n.urgency_level,
    date: n.published_at,
    reference: n.reference_number,
    summary: n.summary,
    bodyHtml: n.body || n.text,
    issuingAuthority: n.issuing_authority,
    signedBy: n.signed_by,
    audience: n.target_audience,
    effectiveDate: n.effective_date,
    expiresAt: n.expires_at,
    language: n.language,
    legalReference: n.related_legal_reference,
    contactName: n.contact_name,
    contactPhone: n.contact_phone,
    contactEmail: n.contact_email,
    attachmentName: n.attachment_original_name,
  };
}

export default function NoticeDetailPage({ onNavigate, backTo }: NoticeDetailPageProps) {
  const params = useParams<{ id: string }>();
  const id = params.id || "";
  const [notice, setNotice] = useState<NormalisedNotice | null>(null);
  const [notFound, setNotFound] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    async function load() {
      setLoading(true);
      setNotFound(false);
      try {
        const response = await portalApi.getNotices(1, 100);
        const list: any[] = Array.isArray(response) ? response : response.data || response.content || [];
        const match = list.find((n) => String(n.id) === id);
        if (mounted) {
          if (match) setNotice(fromPortal(match));
          else setNotFound(true);
        }
      } catch (err) {
        console.error("Error loading notice:", err);
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

  const urgencyColor = (u?: string) => {
    const v = (u || "").toLowerCase();
    if (v === "high") return { bg: "#fef2f2", color: "#b91c1c" };
    if (v === "medium") return { bg: "#fef3c7", color: "#92400e" };
    return { bg: "var(--primary-lighter)", color: "var(--primary)" };
  };

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title="Notice Details"
        subtitle="Full details of the selected notice"
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: backTo?.label || "Press Releases", onClick: () => onNavigate(backTo?.page || "press-releases") },
          { label: "Notice" },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[900px] mx-auto">
          <button
            onClick={() => onNavigate(backTo?.page || "press-releases")}
            className="flex items-center gap-2 text-sm font-medium mb-8 bg-transparent border-none cursor-pointer hover:opacity-80 p-0"
            style={{ color: "var(--primary)" }}
            data-testid="button-back-notices"
          >
            <ArrowLeft className="w-4 h-4" /> Back to {backTo?.label || "notices"}
          </button>

          {loading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading notice...</p>
            </div>
          ) : notFound || !notice ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <BellRing className="w-16 h-16 mx-auto mb-4" style={{ color: "var(--neutral-200)" }} />
              <h3 className="text-xl font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>Notice not found</h3>
              <p style={{ color: "var(--neutral-500)" }}>This notice may have been withdrawn or removed.</p>
            </div>
          ) : (
            <article className="bg-white rounded-2xl p-6 md:p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
              <div className="flex items-center gap-2 mb-4 flex-wrap">
                <span className="py-1 px-3 rounded-full text-xs font-bold uppercase text-white" style={{ background: "var(--accent)" }}>
                  Notice
                </span>
                {notice.category && (
                  <span className="py-1 px-3 rounded-full text-xs font-bold uppercase" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                    {notice.category}
                  </span>
                )}
                {notice.urgency && (
                  <span className="py-1 px-3 rounded-full text-xs font-bold uppercase" style={{ background: urgencyColor(notice.urgency).bg, color: urgencyColor(notice.urgency).color }}>
                    {notice.urgency} priority
                  </span>
                )}
              </div>

              <h1 className="text-2xl md:text-3xl font-bold mb-3" style={{ color: "var(--primary-dark)" }}>{notice.title}</h1>
              <div className="flex items-center gap-4 flex-wrap text-sm mb-8" style={{ color: "var(--neutral-500)" }}>
                {fmtDate(notice.date) && (
                  <span className="flex items-center gap-1.5"><Calendar className="w-4 h-4" /> Published: {fmtDate(notice.date)}</span>
                )}
                {notice.reference && <span>Ref: {notice.reference}</span>}
              </div>

              {notice.summary && (
                <div className="p-4 rounded-xl mb-6" style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}>
                  <h4 className="font-bold mb-2 flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <Zap className="w-5 h-5" /> Summary
                  </h4>
                  <p className="text-sm leading-relaxed whitespace-pre-line m-0" style={{ color: "var(--neutral-700)" }}>{notice.summary}</p>
                </div>
              )}

              {notice.bodyHtml && (
                <div className="mb-6">
                  <h4 className="font-bold text-lg mb-3 flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <FileText className="w-5 h-5" /> Notice Details
                  </h4>
                  <div
                    className="p-5 rounded-xl leading-relaxed"
                    style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)", color: "var(--neutral-700)" }}
                    dangerouslySetInnerHTML={{ __html: notice.bodyHtml }}
                  />
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                {notice.issuingAuthority && (
                  <div className="p-3 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                    <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Issuing Authority</p>
                    <p className="text-sm font-medium m-0">{notice.issuingAuthority}</p>
                  </div>
                )}
                {notice.signedBy && (
                  <div className="p-3 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                    <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Signed By</p>
                    <p className="text-sm font-medium m-0">{notice.signedBy}</p>
                  </div>
                )}
                {notice.audience && (
                  <div className="p-3 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                    <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Target Audience</p>
                    <p className="text-sm font-medium m-0">{notice.audience}</p>
                  </div>
                )}
                {fmtDate(notice.effectiveDate) && (
                  <div className="p-3 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                    <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Effective Date</p>
                    <p className="text-sm font-medium m-0">{fmtDate(notice.effectiveDate)}</p>
                  </div>
                )}
                {fmtDate(notice.expiresAt) && (
                  <div className="p-3 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                    <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Expires</p>
                    <p className="text-sm font-medium m-0">{fmtDate(notice.expiresAt)}</p>
                  </div>
                )}
              </div>

              {notice.legalReference && (
                <div className="p-4 rounded-xl mb-6" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Legal Reference</p>
                  <p className="text-sm m-0" style={{ color: "var(--neutral-700)" }}>{notice.legalReference}</p>
                </div>
              )}

              {(notice.contactName || notice.contactPhone || notice.contactEmail) && (
                <div className="p-4 rounded-xl mb-6" style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}>
                  <h4 className="font-bold mb-3" style={{ color: "var(--primary-dark)" }}>Contact for Enquiries</h4>
                  <div className="space-y-2 text-sm" style={{ color: "var(--neutral-700)" }}>
                    {notice.contactName && <p className="m-0"><strong>{notice.contactName}</strong></p>}
                    {notice.contactPhone && <p className="flex items-center gap-2 m-0"><Phone className="w-4 h-4" /> {notice.contactPhone}</p>}
                    {notice.contactEmail && <p className="flex items-center gap-2 m-0"><Mail className="w-4 h-4" /> {notice.contactEmail}</p>}
                  </div>
                </div>
              )}

              {notice.attachmentName && (
                <div className="p-4 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                  <p className="text-xs font-bold uppercase mb-1" style={{ color: "var(--neutral-400)" }}>Attachment</p>
                  <p className="text-sm flex items-center gap-2 m-0" style={{ color: "var(--neutral-700)" }}>
                    <FileText className="w-4 h-4" /> {notice.attachmentName}
                  </p>
                </div>
              )}
            </article>
          )}
        </div>
      </div>
    </div>
  );
}
