import { useEffect, useState } from "react";
import { useParams } from "wouter";
import { Calendar, MapPin, Clock, Loader2, ArrowLeft, Users, Mail, Phone, ExternalLink, Ticket } from "lucide-react";
import PageHero from "../components/PageHero";
import { portalApi } from "../lib/api";

interface EventDetailPageProps {
  onNavigate: (page: string) => void;
}

const fmtDate = (iso?: string) =>
  iso ? new Date(iso).toLocaleDateString(undefined, { weekday: "long", year: "numeric", month: "long", day: "numeric" }) : null;

const fmtTime = (iso?: string) => (iso ? new Date(iso).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : null);

interface NormalisedEvent {
  id: string;
  title: string;
  type?: string;
  image?: string;
  startsAt?: string;
  endsAt?: string;
  location?: string;
  bodyHtml?: string;
  organizer?: string;
  cost?: string | number;
  capacity?: string | number;
  audience?: string;
  registrationRequired?: boolean;
  registrationUrl?: string;
  virtualLink?: string;
  contactName?: string;
  contactPhone?: string;
  contactEmail?: string;
  agenda?: string;
}

function fromPortal(e: any): NormalisedEvent {
  return {
    id: String(e.id),
    title: e.title,
    type: e.event_category || e.type || "Event",
    image: e.cover_image_url || e.image_url || e.image,
    startsAt: e.starts_at || e.date,
    endsAt: e.ends_at,
    location: e.location,
    bodyHtml: e.description || e.body,
    organizer: e.organizer,
    cost: e.cost,
    capacity: e.capacity,
    audience: e.target_audience,
    registrationRequired: e.registration_required === true || e.registration_required === 1,
    registrationUrl: e.registration_url,
    virtualLink: e.virtual_link,
    contactName: e.contact_name,
    contactPhone: e.contact_phone,
    contactEmail: e.contact_email,
    agenda: e.agenda,
  };
}

export default function EventDetailPage({ onNavigate }: EventDetailPageProps) {
  const params = useParams<{ id: string }>();
  const id = params.id || "";
  const [event, setEvent] = useState<NormalisedEvent | null>(null);
  const [notFound, setNotFound] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;
    async function load() {
      setLoading(true);
      setNotFound(false);
      try {
        const response = await portalApi.getEvents(1, 100);
        const list: any[] = Array.isArray(response) ? response : response.data || response.content || [];
        const match = list.find((e) => String(e.id) === id);
        if (mounted) {
          if (match) setEvent(fromPortal(match));
          else setNotFound(true);
        }
      } catch (err) {
        console.error("Error loading event:", err);
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
        title="Event Details"
        subtitle="Full details of the selected ZMC event"
        breadcrumbs={[
          { label: "Home", onClick: () => onNavigate("home") },
          { label: "Events", onClick: () => onNavigate("events") },
          { label: "Details" },
        ]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[900px] mx-auto">
          <button
            onClick={() => onNavigate("events")}
            className="flex items-center gap-2 text-sm font-medium mb-8 bg-transparent border-none cursor-pointer hover:opacity-80 p-0"
            style={{ color: "var(--primary)" }}
            data-testid="button-back-events"
          >
            <ArrowLeft className="w-4 h-4" /> Back to all events
          </button>

          {loading ? (
            <div className="flex flex-col items-center justify-center py-20 gap-4">
              <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
              <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading event...</p>
            </div>
          ) : notFound || !event ? (
            <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
              <Calendar className="w-16 h-16 mx-auto mb-4" style={{ color: "var(--neutral-200)" }} />
              <h3 className="text-xl font-semibold mb-2" style={{ color: "var(--neutral-800)" }}>Event not found</h3>
              <p style={{ color: "var(--neutral-500)" }}>This event may have ended or been removed.</p>
            </div>
          ) : (
            <div className="bg-white rounded-2xl overflow-hidden" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
              {event.image && (
                <div className="h-56 md:h-80 bg-neutral-200">
                  <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
                </div>
              )}
              <div className="p-6 md:p-10">
                <div className="flex items-center gap-3 flex-wrap mb-4">
                  <span className="py-1 px-3 rounded-full text-xs font-bold uppercase text-white" style={{ background: "var(--primary)" }}>
                    {event.type}
                  </span>
                </div>

                <h1 className="text-2xl md:text-3xl font-bold mb-6" style={{ color: "var(--primary-dark)" }}>{event.title}</h1>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                  {fmtDate(event.startsAt) && (
                    <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                      <Calendar className="w-5 h-5" style={{ color: "var(--primary)" }} />
                      <div className="text-sm">
                        <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Date</p>
                        <p className="font-semibold m-0">{fmtDate(event.startsAt)}</p>
                      </div>
                    </div>
                  )}
                  {fmtTime(event.startsAt) && (
                    <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                      <Clock className="w-5 h-5" style={{ color: "var(--primary)" }} />
                      <div className="text-sm">
                        <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Time</p>
                        <p className="font-semibold m-0">
                          {fmtTime(event.startsAt)}
                          {event.endsAt ? ` – ${fmtTime(event.endsAt)}` : ""}
                        </p>
                      </div>
                    </div>
                  )}
                  <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                    <MapPin className="w-5 h-5" style={{ color: "var(--primary)" }} />
                    <div className="text-sm">
                      <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Location</p>
                      <p className="font-semibold m-0">{event.location || "TBA"}</p>
                    </div>
                  </div>
                  {event.organizer && (
                    <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                      <Users className="w-5 h-5" style={{ color: "var(--primary)" }} />
                      <div className="text-sm">
                        <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Organiser</p>
                        <p className="font-semibold m-0">{event.organizer}</p>
                      </div>
                    </div>
                  )}
                  {event.cost != null && event.cost !== "" && (
                    <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                      <Ticket className="w-5 h-5" style={{ color: "var(--primary)" }} />
                      <div className="text-sm">
                        <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Cost</p>
                        <p className="font-semibold m-0">{event.cost === 0 || event.cost === "0" ? "Free" : event.cost}</p>
                      </div>
                    </div>
                  )}
                  {event.audience && (
                    <div className="flex items-center gap-3 p-3 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                      <Users className="w-5 h-5" style={{ color: "var(--primary)" }} />
                      <div className="text-sm">
                        <p className="text-xs m-0" style={{ color: "var(--neutral-500)" }}>Target Audience</p>
                        <p className="font-semibold m-0">{event.audience}</p>
                      </div>
                    </div>
                  )}
                </div>

                {event.bodyHtml && (
                  <div className="mb-8">
                    <h4 className="font-bold text-lg mb-3" style={{ color: "var(--primary-dark)" }}>About This Event</h4>
                    <div
                      className="p-4 rounded-xl leading-relaxed"
                      style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)", color: "var(--neutral-700)" }}
                      dangerouslySetInnerHTML={{ __html: event.bodyHtml }}
                    />
                  </div>
                )}

                {event.agenda && (
                  <div className="mb-8">
                    <h4 className="font-bold text-lg mb-3" style={{ color: "var(--primary-dark)" }}>Agenda</h4>
                    <div
                      className="p-4 rounded-xl leading-relaxed whitespace-pre-line"
                      style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)", color: "var(--neutral-700)" }}
                      dangerouslySetInnerHTML={{ __html: event.agenda }}
                    />
                  </div>
                )}

                {event.registrationRequired && (
                  <div className="p-4 rounded-xl mb-8" style={{ background: "var(--primary-soft)", border: "1px solid var(--primary-lighter)" }}>
                    <h4 className="font-bold mb-2" style={{ color: "var(--primary-dark)" }}>Registration Required</h4>
                    {event.registrationUrl ? (
                      <a
                        href={event.registrationUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl font-semibold text-sm text-white"
                        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                      >
                        Register for this Event <ExternalLink className="w-4 h-4" />
                      </a>
                    ) : (
                      <p className="text-sm m-0" style={{ color: "var(--neutral-700)" }}>
                        Registration is required. Please contact the organisers below.
                      </p>
                    )}
                  </div>
                )}

                {event.virtualLink && (
                  <div className="p-4 rounded-xl mb-8" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                    <h4 className="font-bold mb-2" style={{ color: "var(--primary-dark)" }}>Join Online</h4>
                    <a href={event.virtualLink} target="_blank" rel="noopener noreferrer" className="text-sm font-semibold break-all" style={{ color: "var(--primary)" }}>
                      {event.virtualLink}
                    </a>
                  </div>
                )}

                {(event.contactName || event.contactPhone || event.contactEmail) && (
                  <div className="p-4 rounded-xl" style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-100)" }}>
                    <h4 className="font-bold mb-3" style={{ color: "var(--primary-dark)" }}>Event Contact</h4>
                    <div className="space-y-2 text-sm" style={{ color: "var(--neutral-700)" }}>
                      {event.contactName && <p className="m-0"><strong>{event.contactName}</strong></p>}
                      {event.contactPhone && <p className="flex items-center gap-2 m-0"><Phone className="w-4 h-4" /> {event.contactPhone}</p>}
                      {event.contactEmail && <p className="flex items-center gap-2 m-0"><Mail className="w-4 h-4" /> {event.contactEmail}</p>}
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
