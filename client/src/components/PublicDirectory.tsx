import { useEffect, useState } from "react";
import { Search, Loader2, AlertCircle, Inbox, FileDown, Download } from "lucide-react";
import { portalApi } from "../lib/api";
import { exportTableToPdf, type PdfColumn } from "../lib/pdfExport";

type DirectoryVariant = "practitioners" | "mediahouses";

interface PublicDirectoryProps {
  variant: DirectoryVariant;
}

interface DirectoryRecord {
  first_name?: string;
  surname?: string;
  full_name?: string;
  organisation_name?: string;
  organisation?: string;
  category?: string;
  accreditation_number?: string;
  registration_no?: string;
  province?: string;
  issued_at?: string | null;
  expires_at?: string | null;
}

const PER_PAGE = 24;

const formatDate = (value?: string | null) => {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
};

const resolveStatus = (expiresAt?: string | null) => {
  if (!expiresAt) return "Active";
  const d = new Date(expiresAt);
  if (Number.isNaN(d.getTime())) return "Active";
  return d.getTime() < Date.now() ? "Expired" : "Active";
};

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
  const [exporting, setExporting] = useState<null | "pdf" | "legacy">(null);
  const [exportError, setExportError] = useState("");

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

  const title = isPractitioner ? "Accredited Media Practitioners" : "Registered Media Houses";
  const blurb = isPractitioner
    ? "Journalists and media practitioners currently accredited by the Zimbabwe Media Commission."
    : "Mass media services currently registered with the Zimbabwe Media Commission.";
  const placeholder = isPractitioner
    ? "Search by name, media house or accreditation number"
    : "Search by media house, registration number or province";

  const columns: PdfColumn[] = isPractitioner
    ? [
        { header: "#", key: "index" },
        { header: "Full Name", key: "fullName" },
        { header: "Category", key: "category" },
        { header: "Media House", key: "mediaHouse" },
        { header: "Accreditation Number", key: "accreditationNumber" },
        { header: "Approved On", key: "approvedOn" },
        { header: "Valid Until", key: "validUntil" },
        { header: "Status", key: "status" },
      ]
    : [
        { header: "#", key: "index" },
        { header: "Media House", key: "mediaHouse" },
        { header: "Registration Number", key: "registrationNumber" },
        { header: "Province", key: "province" },
        { header: "Approved On", key: "approvedOn" },
        { header: "Valid Until", key: "validUntil" },
      ];

  const displayName = (record: DirectoryRecord) => {
    if (!isPractitioner) return record.organisation_name?.trim() || "Unnamed media house";
    const firstAndSurname = [record.first_name, record.surname].filter(Boolean).join(" ").trim();
    return firstAndSurname || record.full_name?.trim() || "Unnamed practitioner";
  };

  const toRow = (record: DirectoryRecord, index: number): Record<string, string> =>
    isPractitioner
      ? {
          index: String(index),
          fullName: displayName(record),
          category: record.category?.trim() || "—",
          mediaHouse: record.organisation?.trim() || "—",
          accreditationNumber: record.accreditation_number?.trim() || "—",
          approvedOn: formatDate(record.issued_at),
          validUntil: formatDate(record.expires_at),
          status: resolveStatus(record.expires_at),
        }
      : {
          index: String(index),
          mediaHouse: displayName(record),
          registrationNumber: record.registration_no?.trim() || "—",
          province: record.province?.trim() || "—",
          approvedOn: formatDate(record.issued_at),
          validUntil: formatDate(record.expires_at),
        };

  const fetchAllRecords = async (applySearch: boolean): Promise<DirectoryRecord[]> => {
    const all: DirectoryRecord[] = [];
    let currentPage = 1;
    let totalPages = 1;
    do {
      const params: { page: number; per_page: number; search?: string } = { page: currentPage, per_page: 100 };
      if (applySearch && search) params.search = search;
      const res: any = isPractitioner
        ? await portalApi.getAccreditedPractitioners(params)
        : await portalApi.getRegisteredMediaHouses(params);
      const list = Array.isArray(res?.data) ? (res.data as DirectoryRecord[]) : [];
      all.push(...list);
      totalPages = res?.meta?.last_page ?? 1;
      currentPage += 1;
    } while (currentPage <= totalPages && currentPage <= 100);
    return all;
  };

  const runExport = async (mode: "pdf" | "legacy") => {
    if (exporting) return;
    setExporting(mode);
    setExportError("");
    try {
      const all = await fetchAllRecords(mode === "pdf");
      if (all.length === 0) {
        setExportError("There are no records to export.");
        return;
      }
      const rows = all.map((record, index) => toRow(record, index + 1));
      if (mode === "legacy") {
        await exportTableToPdf({
          title: "Registered Media Houses — Legacy List",
          subtitle: `${rows.length} ${rows.length === 1 ? "record" : "records"}  •  Zimbabwe Media Commission`,
          columns,
          rows,
          filename: "registered-media-houses-legacy-list.pdf",
        });
      } else {
        await exportTableToPdf({
          title,
          subtitle: `${rows.length} ${rows.length === 1 ? "record" : "records"}${
            search ? `  •  Filtered by "${search}"` : ""
          }  •  Zimbabwe Media Commission`,
          columns,
          rows,
          filename: isPractitioner ? "accredited-media-practitioners.pdf" : "registered-media-houses.pdf",
        });
      }
    } catch (err: any) {
      setExportError(err?.message || "We could not generate the PDF. Please try again.");
    } finally {
      setExporting(null);
    }
  };

  const statusBadge = (value: string) => {
    const active = value.toLowerCase() === "active";
    return (
      <span
        className="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold"
        style={{ background: active ? "var(--accent-soft)" : "var(--red-light)", color: active ? "var(--accent-dark)" : "var(--red)" }}
      >
        {value}
      </span>
    );
  };

  const exportButtonStyle = { background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" } as const;
  const legacyButtonStyle = { background: "#fff", color: "var(--primary-dark)", border: "2px solid var(--primary)" } as const;

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
          <div className="flex flex-wrap items-center gap-3">
            {!loading && !error && (
              <span
                className="px-4 py-1.5 rounded-full text-sm font-semibold whitespace-nowrap"
                style={{ background: "var(--accent-soft)", color: "var(--accent-dark)" }}
              >
                {total} {total === 1 ? "record" : "records"}
              </span>
            )}
            {!isPractitioner && (
              <button
                type="button"
                onClick={() => runExport("legacy")}
                disabled={!!exporting || loading}
                className="py-2.5 px-4 rounded-xl font-semibold cursor-pointer flex items-center gap-2 text-sm disabled:opacity-60 disabled:cursor-not-allowed"
                style={legacyButtonStyle}
                data-testid="button-download-legacy-list"
              >
                {exporting === "legacy" ? <Loader2 className="w-4 h-4 animate-spin" /> : <FileDown className="w-4 h-4" />}
                Download Legacy List
              </button>
            )}
            <button
              type="button"
              onClick={() => runExport("pdf")}
              disabled={!!exporting || loading}
              className="py-2.5 px-4 rounded-xl font-semibold cursor-pointer flex items-center gap-2 text-sm text-white border-none disabled:opacity-60 disabled:cursor-not-allowed"
              style={exportButtonStyle}
              data-testid={`button-export-pdf-${variant}`}
            >
              {exporting === "pdf" ? <Loader2 className="w-4 h-4 animate-spin" /> : <Download className="w-4 h-4" />}
              Export as PDF
            </button>
          </div>
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

        {exportError && (
          <div
            className="flex items-center gap-2 text-sm p-3 rounded-xl mt-4"
            style={{ background: "#fef2f2", color: "#b91c1c" }}
            data-testid="text-export-error"
          >
            <AlertCircle className="w-4 h-4 flex-shrink-0" /> {exportError}
          </div>
        )}
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
        <div
          className="overflow-x-auto rounded-2xl bg-white"
          style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
        >
          <table className="w-full border-collapse text-sm" data-testid={`directory-table-${variant}`}>
            <thead>
              <tr style={{ background: "var(--primary)" }}>
                {columns.map((col) => (
                  <th
                    key={col.key}
                    className="text-left font-semibold px-4 py-3 whitespace-nowrap"
                    style={{ color: "#fff" }}
                  >
                    {col.header}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {records.map((record, index) => {
                const row = toRow(record, (page - 1) * PER_PAGE + index + 1);
                return (
                  <tr
                    key={`${record.accreditation_number ?? record.registration_no ?? index}`}
                    style={{ borderTop: "1px solid var(--neutral-100)", background: index % 2 ? "var(--neutral-50)" : "#fff" }}
                    data-testid={`directory-row-${index}`}
                  >
                    {columns.map((col) => (
                      <td key={col.key} className="px-4 py-3 align-top" style={{ color: "var(--neutral-700)" }}>
                        {col.key === "status" ? statusBadge(row[col.key]) : row[col.key]}
                      </td>
                    ))}
                  </tr>
                );
              })}
            </tbody>
          </table>
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
