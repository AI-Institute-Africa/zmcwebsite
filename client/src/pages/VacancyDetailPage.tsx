import { useEffect, useState } from "react";
import { useParams } from "wouter";
import { Building2, Calendar, Loader2, MapPin, FileText, GraduationCap, Mail, ChevronLeft, Briefcase, ListChecks } from "lucide-react";
import PageHero from "../components/PageHero";
import { portalApi } from "../lib/api";

interface VacancyDetailPageProps {
  onNavigate: (page: string) => void;
}

const formatDate = (value?: string) => (value ? new Date(value).toLocaleDateString(undefined, { year: "numeric", month: "long", day: "numeric" }) : "TBA");

interface NormalisedVacancy {
  id: string;
  title: string;
  reference?: string;
  closingAt?: string;
  type?: string;
  station?: string;
  bodyHtml?: string;
  today?: boolean;
  qualifications?: string;
  experience?: string;
  skills?: string;
  requirements?: string[];
  duties?: string[];
  department?: string;
  grade?: string;
  applyEmail?: string;
}

function normalisePortal(v: any): NormalisedVacancy {
  return {
    id: String(v.id),
    title: v.title,
    reference: v.vacancy_reference,
    closingAt: v.closing_at,
    type: v.employment_type || "Full-time",
    station: v.duty_station || "Not Specified",
    bodyHtml: v.body,
    qualifications: v.qualifications_required,
    experience: v.experience_required,
    skills: v.skills_competencies,
    applyEmail: v.submission_email,
  };
}

export default function VacancyDetailPage({ onNavigate }: VacancyDetailPageProps) {
  const params = useParams<{ id: string }>();
  const id = params.id || "";
  const [vacancy, setVacancy] = useState<NormalisedVacancy | null>(null);
  const [notFound, setNotFound] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    async function load() {
      setLoading(true);
      setNotFound(false);
      try {
        const response = await portalApi.getVacancies(1, 100);
        const list: any[] = Array.isArray(response) ? response : response.data || response.content || [];
        const match = list.find((v) => String(v.id) === id);
        if (mounted) {
          if (match) setVacancy(normalisePortal(match));
          else setNotFound(true);
        }
      } catch (err) {
        console.error("Error loading vacancy:", err);
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

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={vacancy?.title || "Vacancy Details"}
        subtitle="Job opportunity at the Zimbabwe Media Commission"
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: "Vacancies", onClick: () => onNavigate("vacancies") },
          { label: "Details" },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[900px] mx-auto">
          <button
            onClick={() => onNavigate("vacancies")}
            className="flex items-center gap-2 text-sm font-medium mb-8 bg-transparent border-none cursor-pointer hover:opacity-80 p-0"
            style={{ color: "var(--primary)" }}
            data-testid="button-back-vacancies"
          >
            <ChevronLeft className="w-4 h-4" /> Back to all vacancies
          </button>

          {loading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading vacancy...</p>
            </div>
          ) : notFound || !vacancy ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <Building2 className="w-16 h-16 mx-auto mb-4" style={{ color: "var(--neutral-200)" }} />
              <h3 className="text-xl font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>Vacancy not found</h3>
              <p style={{ color: "var(--neutral-500)" }}>This position may have been closed or removed.</p>
            </div>
          ) : (
            <div className="bg-white rounded-2xl p-6 md:p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
              <div className="flex items-center gap-3 flex-wrap mb-6">
                <span className="py-1 px-3 rounded-full text-xs font-bold uppercase text-white" style={{ background: "var(--primary)" }}>
                  {vacancy.type}
                </span>
                {vacancy.department && (
                  <span className="py-1 px-3 rounded-full text-xs font-bold uppercase" style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}>
                    {vacancy.department}
                  </span>
                )}
              </div>

              <h1 className="text-2xl md:text-3xl font-bold mb-2" style={{ color: "var(--primary-dark)" }}>{vacancy.title}</h1>
              {vacancy.reference && (
                <p className="text-sm mb-6" style={{ color: "var(--neutral-500)" }}>Reference: {vacancy.reference}</p>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <Calendar className="w-5 h-5" style={{ color: "var(--primary)" }} />
                  <div className="text-sm">
                    <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Closing Date</p>
                    <p className="font-semibold m-0">{formatDate(vacancy.closingAt)}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <Briefcase className="w-5 h-5" style={{ color: "var(--primary)" }} />
                  <div className="text-sm">
                    <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Type</p>
                    <p className="font-semibold m-0">{vacancy.type}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                  <MapPin className="w-5 h-5" style={{ color: "var(--primary)" }} />
                  <div className="text-sm">
                    <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Duty Station</p>
                    <p className="font-semibold m-0">{vacancy.station}</p>
                  </div>
                </div>
              </div>

              {vacancy.bodyHtml && (
                <div className="space-y-3 mb-8">
                  <h4 className="font-bold text-lg flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <FileText className="w-5 h-5" /> Job Description
                  </h4>
                  <div
                    className="p-4 rounded-xl leading-relaxed"
                    style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)", color: "var(--neutral-700)" }}
                    dangerouslySetInnerHTML={{ __html: vacancy.bodyHtml }}
                  />
                </div>
              )}

              {(vacancy.qualifications || vacancy.experience || vacancy.skills || vacancy.requirements?.length) && (
                <div className="space-y-4 mb-8">
                  <h4 className="font-bold text-lg flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <GraduationCap className="w-5 h-5" /> Requirements
                  </h4>
                  {vacancy.requirements?.length ? (
                    <ul className="space-y-2 pl-0 list-none">
                      {vacancy.requirements.map((req, i) => (
                        <li key={i} className="flex gap-2 text-sm" style={{ color: "var(--neutral-700)" }}>
                          <span style={{ color: "var(--primary)" }}>•</span> {req}
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {vacancy.qualifications && (
                        <div className="p-4 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                          <p className="text-xs font-bold uppercase mb-2" style={{ color: "var(--neutral-400)" }}>Qualifications</p>
                          <div className="text-sm whitespace-pre-line" style={{ color: "var(--neutral-600)" }}>{vacancy.qualifications}</div>
                        </div>
                      )}
                      {vacancy.experience && (
                        <div className="p-4 rounded-xl bg-white border" style={{ borderColor: "var(--neutral-100)" }}>
                          <p className="text-xs font-bold uppercase mb-2" style={{ color: "var(--neutral-400)" }}>Experience</p>
                          <div className="text-sm whitespace-pre-line" style={{ color: "var(--neutral-600)" }}>{vacancy.experience}</div>
                        </div>
                      )}
                      {vacancy.skills && (
                        <div className="p-4 rounded-xl bg-white border md:col-span-2" style={{ borderColor: "var(--neutral-100)" }}>
                          <p className="text-xs font-bold uppercase mb-2" style={{ color: "var(--neutral-400)" }}>Skills &amp; Competencies</p>
                          <div className="text-sm whitespace-pre-line" style={{ color: "var(--neutral-600)" }}>{vacancy.skills}</div>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              )}

              {vacancy.duties?.length ? (
                <div className="space-y-4 mb-8">
                  <h4 className="font-bold text-lg flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <ListChecks className="w-5 h-5" /> Key Duties
                  </h4>
                  <ul className="space-y-2 pl-0 list-none">
                    {vacancy.duties.map((duty, i) => (
                      <li key={i} className="flex gap-2 text-sm" style={{ color: "var(--neutral-700)" }}>
                        <span style={{ color: "var(--primary)" }}>•</span> {duty}
                      </li>
                    ))}
                  </ul>
                </div>
              ) : null}

              {vacancy.applyEmail && (
                <div className="space-y-3">
                  <h4 className="font-bold text-lg flex items-center gap-2" style={{ color: "var(--primary-dark)" }}>
                    <Mail className="w-5 h-5" /> How to Apply
                  </h4>
                  <div className="p-4 rounded-xl" style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}>
                    <p className="text-sm m-0 mb-2" style={{ color: "var(--neutral-700)" }}>
                      Submit your application to:
                    </p>
                    <a href={`mailto:${vacancy.applyEmail}`} className="flex items-center gap-2 text-sm font-semibold" style={{ color: "var(--primary)" }}>
                      <Mail className="w-4 h-4" /> {vacancy.applyEmail}
                    </a>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
