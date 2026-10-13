import { useState } from "react";
import {
  Search,
  Loader2,
  AlertCircle,
  BadgeCheck,
  BadgeX,
  ShieldCheck,
  ShieldX,
  UserRound,
  Building2,
  Briefcase,
} from "lucide-react";
import { portalApi } from "../lib/api";

interface VerificationData {
  accreditation_number?: string;
  certificate_no?: string;
  first_name?: string;
  surname?: string;
  full_name?: string;
  organisation?: string;
  category?: string;
  designation?: string;
  employment_type?: string | null;
  medium?: string;
  nationality?: string;
  status?: string;
  issued_at?: string;
  expires_at?: string;
  accreditation_year?: number | string;
}

interface VerificationResult {
  status?: string;
  valid?: boolean;
  message?: string;
  record_status?: string;
  data?: VerificationData;
}

const EMPLOYMENT_LABELS: Record<string, string> = {
  employed: "Employed (worker)",
  freelancer: "Freelancer",
  student: "Student",
  unemployed: "Not employed",
};

function formatDate(value?: string) {
  if (!value) return "";
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString("en-GB", { day: "numeric", month: "long", year: "numeric" });
}

function personName(data: VerificationData) {
  const firstAndSurname = [data.first_name, data.surname].filter(Boolean).join(" ").trim();
  return firstAndSurname || data.full_name?.trim() || "—";
}

function Field({ label, value, icon: Icon }: { label: string; value?: string; icon: React.ComponentType<{ className?: string; style?: React.CSSProperties }> }) {
  if (!value) return null;
  return (
    <div className="flex items-start gap-3 py-3" style={{ borderTop: "1px solid var(--neutral-100)" }}>
      <Icon className="w-5 h-5 mt-0.5 flex-shrink-0" style={{ color: "var(--primary)" }} />
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide m-0" style={{ color: "var(--neutral-400)" }}>
          {label}
        </p>
        <p className="text-base m-0 mt-0.5" style={{ color: "var(--neutral-800)" }}>
          {value}
        </p>
      </div>
    </div>
  );
}

export default function VerifyAccreditation() {
  const [number, setNumber] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<VerificationResult | null>(null);

  const handleVerify = async (e: React.FormEvent) => {
    e.preventDefault();
    const value = number.trim();
    if (!value) return;

    setLoading(true);
    setError(null);
    setResult(null);

    try {
      const res = (await portalApi.verifyAccreditation(value)) as VerificationResult;
      setResult(res);
    } catch (err: any) {
      setError(err?.message || "Verification failed. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const reset = () => {
    setNumber("");
    setResult(null);
    setError(null);
  };

  const data = result?.data;
  const isSuccess = result?.status === "success" && result?.valid === true;
  const isInactive = result?.status === "invalid";
  const isNotFound = result?.status === "not_found";
  const employmentLabel = data?.employment_type ? EMPLOYMENT_LABELS[data.employment_type] ?? data.employment_type : "";

  return (
    <div>
      <div
        className="bg-white rounded-[20px] p-6 md:p-10 mb-6"
        style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
      >
        <h2 className="m-0" style={{ color: "var(--primary)" }}>
          Verify an Accreditation Number
        </h2>
        <p className="text-sm md:text-base mt-2 mb-6 leading-relaxed" style={{ color: "var(--neutral-600)" }}>
          Enter a media practitioner's accreditation number to confirm it is valid and to view the holder's details.
        </p>

        <form onSubmit={handleVerify} className="flex flex-col sm:flex-row gap-3">
          <input
            type="text"
            value={number}
            onChange={(e) => setNumber(e.target.value)}
            placeholder="e.g. ZMC/ACC/2026/0001"
            aria-label="Accreditation number"
            className="flex-1 py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
            style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)", color: "var(--neutral-700)" }}
            data-testid="input-verify-accreditation"
          />
          <button
            type="submit"
            disabled={loading || !number.trim()}
            className="py-3 px-8 rounded-xl font-semibold text-white border-none cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-transform"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
            data-testid="button-verify-accreditation"
          >
            {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : <Search className="w-5 h-5" />}
            Verify
          </button>
        </form>
      </div>

      {error && (
        <div
          className="flex items-start gap-3 rounded-2xl p-6 mb-6"
          style={{ background: "var(--red-light)", border: "1px solid var(--red)" }}
          role="alert"
        >
          <AlertCircle className="w-6 h-6 flex-shrink-0" style={{ color: "var(--red)" }} />
          <div>
            <p className="font-semibold m-0 mb-1" style={{ color: "var(--red)" }}>
              Verification unavailable
            </p>
            <p className="text-sm m-0" style={{ color: "var(--neutral-700)" }}>
              {error}
            </p>
          </div>
        </div>
      )}

      {isNotFound && (
        <div
          className="flex items-start gap-4 rounded-[20px] p-8"
          style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-200)" }}
        >
          <ShieldX className="w-10 h-10 flex-shrink-0" style={{ color: "var(--neutral-400)" }} />
          <div>
            <h3 className="mb-1" style={{ color: "var(--neutral-700)" }}>
              No matching record found
            </h3>
            <p className="text-sm m-0" style={{ color: "var(--neutral-500)" }}>
              {result?.message || "We could not find an accreditation record for that number. Please check the number and try again."}
            </p>
          </div>
        </div>
      )}

      {isInactive && (
        <div
          className="flex items-start gap-4 rounded-[20px] p-8"
          style={{ background: "var(--accent-soft)", border: "1px solid var(--accent-dark)" }}
        >
          <ShieldX className="w-10 h-10 flex-shrink-0" style={{ color: "var(--accent-dark)" }} />
          <div>
            <h3 className="mb-1" style={{ color: "var(--accent-dark)" }}>
              Not valid &mdash; record is not active
            </h3>
            <p className="text-sm m-0" style={{ color: "var(--neutral-700)" }}>
              {result?.message || "This accreditation number exists but is no longer active."}
            </p>
          </div>
        </div>
      )}

      {isSuccess && data && (
        <div
          className="rounded-[20px] overflow-hidden"
          style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--primary-lighter)" }}
        >
          <div
            className="flex items-center gap-3 px-6 md:px-8 py-5"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
          >
            <BadgeCheck className="w-8 h-8 flex-shrink-0" style={{ color: "white" }} />
            <div>
              <p className="text-sm font-semibold m-0" style={{ color: "rgba(255,255,255,0.85)" }}>
                Valid &amp; Active Accreditation
              </p>
              <p className="text-lg font-bold m-0" style={{ color: "white" }}>
                {data.accreditation_number || data.certificate_no}
              </p>
            </div>
          </div>

          <div className="bg-white p-6 md:p-8">
            <p className="text-xs font-semibold uppercase tracking-wide m-0 mb-1" style={{ color: "var(--neutral-400)" }}>
              Practitioner
            </p>
            <h3 className="m-0 mb-4" style={{ color: "var(--neutral-900)" }}>
              {personName(data)}
            </h3>

            <div className="flex flex-wrap gap-2 mb-2">
              {employmentLabel && (
                <span
                  className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                  style={
                    data.employment_type === "freelancer"
                      ? { background: "var(--accent-soft)", color: "var(--accent-dark)" }
                      : { background: "var(--primary-lighter)", color: "var(--primary-dark)" }
                  }
                >
                  <Briefcase className="w-3.5 h-3.5" />
                  {employmentLabel}
                </span>
              )}
              {data.medium && (
                <span
                  className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                  style={{ background: "var(--neutral-100)", color: "var(--neutral-600)" }}
                >
                  {data.medium}
                </span>
              )}
              {data.nationality && (
                <span
                  className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                  style={{ background: "var(--neutral-100)", color: "var(--neutral-600)" }}
                >
                  {data.nationality}
                </span>
              )}
            </div>

            <Field label="Designation" value={data.designation} icon={UserRound} />
            {data.organisation && <Field label="Organisation" value={data.organisation} icon={Building2} />}
            <Field label="Valid Until" value={formatDate(data.expires_at)} icon={ShieldCheck} />

            <button
              type="button"
              onClick={reset}
              className="mt-6 py-2.5 px-6 rounded-xl font-semibold cursor-pointer border-none"
              style={{ background: "var(--neutral-100)", color: "var(--neutral-700)" }}
            >
              Verify another number
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
