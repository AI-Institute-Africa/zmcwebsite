import { useEffect, useState } from "react";
import { Search, Loader2, AlertCircle, Building2, User, Inbox } from "lucide-react";
import { portalApi } from "../lib/api";

type DirectoryVariant = "practitioners" | "mediahouses";

interface PublicDirectoryProps {
  variant: DirectoryVariant;
}

interface DirectoryRecord {
  first_name?: string;
  surname?: string;
  full_name?: string;
  organisation_name?: string;
  accreditation_number?: string;
  registration_no?: string;
}

const PER_PAGE = 24;

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
    ? "Journalists and media practitioners currently accredited by the Zimbabwe Media Commission."
    : "Mass media services currently registered with the Zimbabwe Media Commission.";
  const placeholder = isPractitioner ? "Search by name" : "Search by media house name";

  const displayName = (record: DirectoryRecord) => {
    if (!isPractitioner) return record.organisation_name?.trim() || "Unnamed media house";
    const firstAndSurname = [record.first_name, record.surname].filter(Boolean).join(" ").trim();
    return firstAndSurname || record.full_name?.trim() || "Unnamed practitioner";
  };

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
              ? "Try a different name."
              : "This directory will be populated as records are approved on the ZMC portal."}
          </p>
        </div>
      )}

      {!loading && !error && records.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {records.map((record, index) => (
            <div
              key={`${record.accreditation_number ?? record.registration_no ?? index}`}
              className="flex items-center gap-4 bg-white rounded-2xl px-5 py-4 transition-all hover:-translate-y-0.5"
              style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
              data-testid={`directory-item-${index}`}
            >
              <span
                className="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
              >
                {isPractitioner ? <User className="w-5 h-5" /> : <Building2 className="w-5 h-5" />}
              </span>
              <span className="text-base font-semibold leading-snug" style={{ color: "var(--neutral-800)" }}>
                {displayName(record)}
              </span>
            </div>
          ))}
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
