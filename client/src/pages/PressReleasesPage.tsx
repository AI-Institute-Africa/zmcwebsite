import { useState, useEffect } from "react";
import { Newspaper, Calendar, Loader2, ArrowLeft, ExternalLink, BellRing, ArrowRight } from "lucide-react";
import { useLocation, useParams } from "wouter";
import PageHero from "../components/PageHero";
import { portalApi, portalAssetUrl } from "../lib/api";

interface PressReleasesPageProps {
  onNavigate: (page: string) => void;
}

const imageUrl = (item: any) => portalAssetUrl(item.image_path);

const excerptOf = (item: any) => {
  const raw = item.excerpt || "";
  if (raw) return raw;
  const fromBody = (item.body || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
  return fromBody.length > 180 ? fromBody.slice(0, 180) + "…" : fromBody;
};

interface NoticeRow {
  id: string;
  title: string;
  category?: string;
  summary?: string;
  date?: string;
}

const fromNotice = (n: any): NoticeRow => ({
  id: String(n.id),
  title: n.title,
  category: n.notice_category || n.category,
  summary: excerptOf(n),
  date: n.published_at || n.created_at || n.date,
});

const formatDate = (value: string) =>
  new Date(value).toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });

function PressReleaseDetail({ id }: { id: string }) {
  const [release, setRelease] = useState<any | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [, setLocation] = useLocation();

  useEffect(() => {
    async function fetchDetail() {
      try {
        setIsLoading(true);
        const data = await portalApi.getSingleRecord("news", id);
        setRelease(data);
      } catch (err: any) {
        console.error("Error fetching press release:", err);
        setError(err.message || "Failed to load press release");
      } finally {
        setIsLoading(false);
      }
    }
    fetchDetail();
  }, [id]);

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center py-24 gap-4">
        <Loader2 className="w-10 h-10 animate-spin text-primary" />
        <p className="text-neutral-500 font-medium">Loading release...</p>
      </div>
    );
  }

  if (error || !release) {
    return (
      <div className="text-center py-20 bg-white rounded-3xl border border-neutral-100 shadow-sm max-w-[700px] mx-auto">
        <p className="text-neutral-600 mb-6">{error || "Press release not found."}</p>
        <button
          onClick={() => setLocation("/")}
          className="py-2 px-6 rounded-xl font-semibold text-white"
          style={{ background: "var(--primary)" }}
        >
          Back to Home
        </button>
      </div>
    );
  }

  const containsHtml = /<[a-zA-Z][\s\S]*>/.test(release.body || "");
  const img = imageUrl(release);

  return (
    <div className="py-12 md:py-16 px-4 md:px-8">
      <div className="max-w-[860px] mx-auto">
        <button
          onClick={() => setLocation("/press-releases")}
          className="inline-flex items-center gap-2 mb-6 text-sm font-semibold cursor-pointer bg-transparent border-none"
          style={{ color: "var(--primary)" }}
        >
          <ArrowLeft className="w-4 h-4" /> Back to Releases
        </button>

        <article
          className="bg-white rounded-3xl overflow-hidden"
          style={{ boxShadow: "var(--shadow-lg)", border: "1px solid var(--neutral-100)" }}
        >
          {img && (
            <div className="relative h-[240px] md:h-[360px] w-full" style={{ background: "#111" }}>
              <img src={img} alt={release.image_alt_text || release.title} className="w-full h-full object-cover" />
            </div>
          )}

          <div className="p-6 md:p-10">
            <span
              className="inline-block py-1 px-3 rounded text-[0.7rem] font-semibold mb-4 uppercase tracking-wide text-white"
              style={{ background: "var(--primary)" }}
            >
              Press Release
            </span>
            <h1 className="text-2xl md:text-3xl mb-3 leading-tight" style={{ color: "var(--primary-dark)" }}>
              {release.title}
            </h1>
            <div className="flex items-center gap-1.5 text-sm mb-6" style={{ color: "var(--neutral-500)" }}>
              <Calendar className="w-4 h-4" />
              {release.published_at ? formatDate(release.published_at) : formatDate(release.created_at)}
              {release.author_name && <span> • {release.author_name}</span>}
            </div>

            <div
              className="text-[0.975rem] leading-7 whitespace-pre-line"
              style={{ color: "var(--neutral-700)" }}
              dangerouslySetInnerHTML={containsHtml ? { __html: release.body } : undefined}
            >
              {containsHtml ? undefined : release.body}
            </div>
          </div>
        </article>
      </div>
    </div>
  );
}

function PressReleaseList({ onNavigate }: PressReleasesPageProps) {
  const [, setLocation] = useLocation();
  const [releases, setReleases] = useState<any[]>([]);
  const [notices, setNotices] = useState<NoticeRow[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchReleases() {
      try {
        setIsLoading(true);
        const [response, noticesResponse] = await Promise.all([
          portalApi.getNews(1, 50).catch(() => null),
          portalApi.getNotices(1, 50).catch(() => null),
        ]);
        const items = response
          ? Array.isArray(response.data)
            ? response.data
            : Array.isArray(response)
              ? response
              : []
          : [];
        setReleases(items);

        const noticeList: any[] = noticesResponse
          ? Array.isArray(noticesResponse.data)
            ? noticesResponse.data
            : Array.isArray(noticesResponse)
              ? noticesResponse
              : []
          : [];
        const merged = noticeList.map(fromNotice);
        merged.sort((a, b) => new Date(b.date || 0).getTime() - new Date(a.date || 0).getTime());
        setNotices(merged);
      } catch (err: any) {
        console.error("Error fetching releases:", err);
        setError(err.message || "Failed to load press releases");
      } finally {
        setIsLoading(false);
      }
    }
    fetchReleases();
  }, []);

  return (
    <div className="py-12 md:py-16 px-4 md:px-8">
      <div className="max-w-[1000px] mx-auto">
        {isLoading ? (
          <div className="flex flex-col items-center justify-center py-24 gap-4">
            <Loader2 className="w-10 h-10 animate-spin text-primary" />
            <p className="text-neutral-500 font-medium">Loading releases...</p>
          </div>
        ) : error ? (
          <div className="text-center py-20 bg-white rounded-3xl border border-neutral-100 shadow-sm">
            <p className="text-neutral-600 mb-6">{error}</p>
            <button
              onClick={() => window.location.reload()}
              className="py-2 px-6 rounded-xl font-semibold text-white"
              style={{ background: "var(--primary)" }}
            >
              Retry
            </button>
          </div>
        ) : releases.length === 0 ? (
          <div className="bg-white rounded-2xl p-8 md:p-12 text-center" style={{ boxShadow: "var(--shadow-lg)", border: "1px solid var(--neutral-100)" }}>
            <div className="w-20 h-20 md:w-24 md:h-24 rounded-full mx-auto mb-6 flex items-center justify-center" style={{ background: "var(--primary-lighter)" }}>
              <Newspaper className="w-10 h-10 md:w-12 md:h-12" style={{ color: "var(--primary)" }} />
            </div>
            <h2 className="text-xl md:text-2xl mb-4" style={{ color: "var(--primary-dark)" }}>
              No Press Releases Yet
            </h2>
            <p className="text-sm md:text-base" style={{ color: "var(--neutral-600)" }}>
              Press releases will be uploaded shortly. Please check back soon for the latest updates from the Zimbabwe Media Commission.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {releases.map((item) => {
              const img = imageUrl(item);
              return (
                <div
                  key={item.id}
                  className="bg-white rounded-2xl overflow-hidden transition-all hover:-translate-y-1 cursor-pointer"
                  style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                  onClick={() => setLocation(`/press-releases/${item.id}`)}
                  data-testid={`release-card-${item.id}`}
                >
                  {img ? (
                    <div className="h-44 w-full overflow-hidden" style={{ background: "#111" }}>
                      <img src={img} alt={item.image_alt_text || item.title} className="w-full h-full object-cover transition-transform duration-500 hover:scale-105" />
                    </div>
                  ) : (
                    <div className="h-44 w-full flex items-center justify-center" style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}>
                      <Newspaper className="w-12 h-12 text-white/80" />
                    </div>
                  )}
                  <div className="p-6">
                    <span className="inline-block py-1 px-3 rounded text-[0.7rem] font-semibold mb-3 uppercase tracking-wide text-white" style={{ background: "var(--primary)" }}>
                      Press Release
                    </span>
                    <h3 className="text-lg font-bold mb-2 leading-snug" style={{ color: "var(--primary-dark)" }}>
                      {item.title}
                    </h3>
                    <p className="text-[0.9rem] line-clamp-3 mb-4" style={{ color: "var(--neutral-600)" }}>
                      {excerptOf(item)}
                    </p>
                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-1.5 text-xs" style={{ color: "var(--neutral-400)" }}>
                        <Calendar className="w-3.5 h-3.5" />
                        {item.created_at ? formatDate(item.created_at) : ""}
                      </span>
                      <span className="inline-flex items-center gap-1 text-xs font-semibold" style={{ color: "var(--primary)" }}>
                        Read Full Release <ExternalLink className="w-3.5 h-3.5" />
                      </span>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {notices.length > 0 && (
          <section className="mt-14" data-testid="notices-section">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-11 h-11 rounded-xl flex items-center justify-center" style={{ background: "var(--primary-lighter)" }}>
                <BellRing className="w-6 h-6" style={{ color: "var(--primary)" }} />
              </div>
              <div>
                <h2 className="text-xl md:text-2xl font-bold m-0" style={{ color: "var(--primary-dark)" }}>Notices &amp; Statements</h2>
                <p className="text-sm m-0" style={{ color: "var(--neutral-500)" }}>Official public notices issued by the Commission</p>
              </div>
            </div>

            <div className="space-y-3">
              {notices.map((notice) => (
                <div
                  key={notice.id}
                  className="bg-white rounded-2xl p-5 flex items-start justify-between gap-4 flex-wrap transition-all hover:-translate-y-0.5 cursor-pointer"
                  style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                  onClick={() => setLocation(`/notices/${notice.id}`)}
                  data-testid={`notice-card-${notice.id}`}
                >
                  <div className="flex-1 min-w-[240px]">
                    <div className="flex items-center gap-2 flex-wrap mb-2">
                      <span className="py-0.5 px-2.5 rounded-full text-[0.7rem] font-bold uppercase text-white" style={{ background: "var(--accent)" }}>
                        Notice
                      </span>
                      {notice.category && (
                        <span className="py-0.5 px-2.5 rounded-full text-[0.7rem] font-bold uppercase" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                          {notice.category}
                        </span>
                      )}
                    </div>
                    <h3 className="font-bold mb-1.5" style={{ color: "var(--primary-dark)" }}>{notice.title}</h3>
                    {notice.summary && (
                      <p className="text-sm line-clamp-2 mb-2" style={{ color: "var(--neutral-600)" }}>{notice.summary}</p>
                    )}
                    {notice.date && (
                      <span className="flex items-center gap-1.5 text-xs" style={{ color: "var(--neutral-400)" }}>
                        <Calendar className="w-3.5 h-3.5" /> {formatDate(notice.date)}
                      </span>
                    )}
                  </div>
                  <button
                    className="shrink-0 py-2.5 px-5 rounded-xl font-semibold text-sm text-white border-none cursor-pointer flex items-center gap-2"
                    style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                    onClick={(e) => {
                      e.stopPropagation();
                      setLocation(`/notices/${notice.id}`);
                    }}
                  >
                    View Notice <ArrowRight className="w-4 h-4" />
                  </button>
                </div>
              ))}
            </div>
          </section>
        )}
      </div>
    </div>
  );
}

export default function PressReleasesPage({ onNavigate }: PressReleasesPageProps) {
  const params = useParams();
  const itemId = params?.id;

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      {itemId ? (
        <>
          <PageHero
            title="Press Release"
            subtitle="Full press release from the Zimbabwe Media Commission"
            breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Media Centre" }, { label: "Press Releases" }, { label: "View Release" }]}
          />
          <PressReleaseDetail id={itemId} />
        </>
      ) : (
        <>
          <PageHero
            title="Press Releases"
            subtitle="Official press releases from the Zimbabwe Media Commission"
            breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Media Centre" }, { label: "Press Releases" }]}
          />
          <PressReleaseList onNavigate={onNavigate} />
        </>
      )}
    </div>
  );
}