import { useEffect, useState, type ReactNode } from "react";
import {
  Search,
  Loader2,
  AlertCircle,
  Building2,
  MapPin,
  Phone,
  Mail,
  Globe,
  Inbox,
} from "lucide-react";
import { portalApi } from "../lib/api";

type DirectoryVariant = "practitioners" | "mediahouses";

interface PublicDirectoryProps {
  variant: DirectoryVariant;
}

interface DirectoryRecord {
  accreditation_number?: string;
  full_name?: string;
  designation?: string;
  organisation?: string;
  medium?: string;
  nationality?: string;
  accreditation_year?: number | string;
  registration_no?: string;
  organisation_name?: string;
  physical_address?: string;
  town?: string;
  province?: string;
  phone?: string;
  email?: string;
  website?: string;
  category?: string;
  issued_at?: string;
  expires_at?: string;
}

const PER_PAGE = 12;

function initials(name?: string) {
  if (!name) return "?";
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "?";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function formatDate(value?: string) {
  if (!value) return "";
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
}

function Chip({ children, tone = "primary" }: { children: ReactNode; tone?: "primary" | "neutral" }) {
  const styles =
    tone === "primary"
      ? { background: "var(--primary-lighter)", color: "var(--primary-dark)" }
      : { background: "var(--neutral-100)", color: "var(--neutral-600)" };
  return (
    <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style={styles}>
      {children}
    </span>
  );
}

function DetailRow({ label, value }: { label: string; value?: string }) {
  if (!value) return null;
  return (
    <div className="flex items-start justify-between gap-4 py-1.5" style={{ borderTop: "1px dashed var(--neutral-100)" }}>
      <span className="text-xs font-medium uppercase tracking-wide" style={{ color: "var(--neutral-400)" }}>
        {label}
      </span>
      <span className="text-sm text-right" style={{ color: "var(--neutral-700)" }}>
        {value}
      </span>
    </div>
  );
}

export default function PublicDirectory({ variant }: PublicDirectoryProps) {
  const isPractitioner = variant === "practitioners";

  const [records, setRecords] = useState<DirectoryRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  useEffect(() => {
    const id = window.setTimeout(() => {
      setSearch(searchInput.trim());
      setPage(1);
    }, 400);
    return () => window.clearTimeout(id);
  }, [searchInput]);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);

    const params: { page: number; per_page: number; search?: string } = { page, per_page: PER_PAGE };
    if (search) params.search = search;

    const request = isPractitioner
      ? portalApi.getAccreditedPractitioners(params)
      : portalApi.getRegisteredMediaHouses(params);

    request
      .then((res: any) => {
        if (cancelled) return;
        const list = Array.isArray(res?.data) ? (res.data as DirectoryRecord[]) : [];
        setRecords(list);
        setLastPage(res?.meta?.last_page ?? 1);
        setTotal(res?.meta?.total ?? list.length);
      })
      .catch((err: any) => {
        if (cancelled) return;
        setRecords([]);
        setError(err?.message || "We could not load this directory right now. Please try again later.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [isPractitioner, page, search]);

  const title = isPractitioner ? "Accredited Practitioners" : "Registered Media Houses";
  const blurb = isPractitioner
    ? "Browse journalists and media practitioners currently accredited by the Zimbabwe Media Commission."
    : "Browse mass media services currently registered with the Zimbabwe Media Commission.";
  const placeholder = isPractitioner
    ? "Search by name, organisation or accreditation number"
    : "Search by name, town or registration number";

  return (
    <div>
      <div
        className="bg-white rounded-[20px] p-6 md:p-8 mb-6"
        style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
      >
        <div className="flex flex-wrap items-start justify-between gap-4 mb-5">
          <div>
            <h2 className="m-0" style={{ color: "var(--primary)" }}>
              {title}
            </h2>
            <p className="text-sm mt-1 mb-0" style={{ color: "var(--neutral-500)" }}>
              {blurb}
            </p>
          </div>
          {!loading && !error && (
            <span
              className="px-4 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap"
              style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
            >
              {total} {total === 1 ? "record" : "records"}
            </span>
          )}
        </div>

        <div className="relative">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5" style={{ color: "var(--neutral-400)" }} />
          <input
            type="search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            placeholder={placeholder}
            aria-label={placeholder}
            className="w-full py-3 pl-12 pr-4 rounded-xl text-base transition-all focus:outline-none"
            style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)", color: "var(--neutral-700)" }}
            data-testid={`input-directory-search-${variant}`}
          />
        </div>
      </div>

      {loading && (
        <div className="flex items-center justify-center gap-3 py-16" style={{ color: "var(--neutral-500)" }}>
          <Loader2 className="w-6 h-6 animate-spin" />
          Loading directory…
        </div>
      )}

      {!loading && error && (
        <div
          className="flex items-start gap-3 rounded-2xl p-6"
          style={{ background: "var(--red-light)", border: "1px solid var(--red)", color: "var(--neutral-700)" }}
          role="alert"
        >
          <AlertCircle className="w-6 h-6 flex-shrink-0" style={{ color: "var(--red)" }} />
          <div>
            <p className="font-semibold m-0 mb-1" style={{ color: "var(--red)" }}>
              Directory unavailable
            </p>
            <p className="text-sm m-0">{error}</p>
          </div>
        </div>
      )}

      {!loading && !error && records.length === 0 && (
        <div
          className="rounded-[20px] p-12 text-center"
          style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}
        >
          <Inbox className="w-12 h-12 mx-auto mb-4" style={{ color: "var(--neutral-300)" }} />
          <h3 className="mb-2" style={{ color: "var(--neutral-700)" }}>
            {search ? "No matches found" : "No records to display yet"}
          </h3>
          <p className="text-sm m-0" style={{ color: "var(--neutral-500)" }}>
            {search
              ? "Try a different name, organisation or number."
              : "This directory will be populated as records are approved on the ZMC portal."}
          </p>
        </div>
      )}

      {!loading && !error && records.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {records.map((record, index) => {
            if (isPractitioner) {
              const name = record.full_name || "Unnamed practitioner";
              const subtitle = [record.designation, record.organisation].filter(Boolean).join(" • ");
              return (
                <article
                  key={`${record.accreditation_number ?? index}`}
                  className="bg-white rounded-2xl p-6 transition-all hover:-translate-y-1"
                  style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                  data-testid={`directory-card-${index}`}
                >
                  <div className="flex items-start gap-4">
                    <div
                      className="w-14 h-14 rounded-full flex items-center justify-center font-bold text-lg text-white flex-shrink-0"
                      style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                    >
                      {initials(record.full_name)}
                    </div>
                    <div className="min-w-0">
                      <h3 className="text-lg mb-1 leading-snug" style={{ color: "var(--neutral-800)" }}>
                        {name}
                      </h3>
                      {subtitle && (
                        <p className="text-sm m-0 truncate" style={{ color: "var(--neutral-500)" }}>
                          {subtitle}
                        </p>
                      )}
                      <div className="flex flex-wrap gap-2 mt-3">
                        {record.category && <Chip>{record.category}</Chip>}
                        {record.medium && <Chip tone="neutral">{record.medium}</Chip>}
                        {record.nationality && <Chip tone="neutral">{record.nationality}</Chip>}
                      </div>
                    </div>
                  </div>
                  <div className="mt-4">
                    <DetailRow label="Accreditation No." value={record.accreditation_number} />
                    <DetailRow
                      label="Accredited"
                      value={record.accreditation_year ? String(record.accreditation_year) : formatDate(record.issued_at)}
                    />
                    <DetailRow label="Valid Until" value={formatDate(record.expires_at)} />
                  </div>
                </article>
              );
            }

            const name = record.organisation_name || "Unnamed media house";
            const location = [record.town, record.province].filter(Boolean).join(", ");
            return (
              <article
                key={`${record.registration_no ?? index}`}
                className="bg-white rounded-2xl p-6 transition-all hover:-translate-y-1"
                style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                data-testid={`directory-card-${index}`}
              >
                <div className="flex items-start gap-4">
                  <div
                    className="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0"
                    style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                  >
                    <Building2 className="w-7 h-7" />
                  </div>
                  <div className="min-w-0">
                    <h3 className="text-lg mb-1 leading-snug" style={{ color: "var(--neutral-800)" }}>
                      {name}
                    </h3>
                    {record.category && (
                      <div className="flex flex-wrap gap-2 mt-1">
                        <Chip>{record.category}</Chip>
                      </div>
                    )}
                  </div>
                </div>

                <div className="mt-4 space-y-2 text-sm" style={{ color: "var(--neutral-600)" }}>
                  {location && (
                    <p className="flex items-center gap-2 m-0">
                      <MapPin className="w-4 h-4 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      {record.physical_address ? `${record.physical_address}, ${location}` : location}
                    </p>
                  )}
                  {record.phone && (
                    <p className="flex items-center gap-2 m-0">
                      <Phone className="w-4 h-4 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <a href={`tel:${record.phone}`} style={{ color: "var(--neutral-600)" }}>
                        {record.phone}
                      </a>
                    </p>
                  )}
                  {record.email && (
                    <p className="flex items-center gap-2 m-0 break-all">
                      <Mail className="w-4 h-4 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <a href={`mailto:${record.email}`} style={{ color: "var(--neutral-600)" }}>
                        {record.email}
                      </a>
                    </p>
                  )}
                  {record.website && (
                    <p className="flex items-center gap-2 m-0 break-all">
                      <Globe className="w-4 h-4 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <a
                        href={record.website.startsWith("http") ? record.website : `https://${record.website}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        style={{ color: "var(--primary)" }}
                      >
                        {record.website}
                      </a>
                    </p>
                  )}
                </div>

                <div className="mt-4">
                  <DetailRow label="Registration No." value={record.registration_no} />
                  <DetailRow label="Registered" value={formatDate(record.issued_at)} />
                  <DetailRow label="Valid Until" value={formatDate(record.expires_at)} />
                </div>
              </article>
            );
          })}
        </div>
      )}

      {!loading && !error && lastPage > 1 && (
        <div className="flex items-center justify-center gap-4 mt-8">
          <button
            type="button"
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={page <= 1}
            className="px-5 py-2.5 rounded-xl font-semibold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed border-none"
            style={{ background: "var(--primary-lighter)", color: "var(--primary-dark)" }}
          >
            Previous
          </button>
          <span className="text-sm" style={{ color: "var(--neutral-500)" }}>
            Page {page} of {lastPage}
          </span>
          <button
            type="button"
            onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
            disabled={page >= lastPage}
            className="px-5 py-2.5 rounded-xl font-semibold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed border-none"
            style={{ background: "var(--primary-lighter)", color: "var(--primary-dark)" }}
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
}
