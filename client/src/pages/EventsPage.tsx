import { useState, useEffect } from "react";
import { Calendar, MapPin, Clock, ArrowRight, CalendarDays, Trophy, GraduationCap, Users, Megaphone, Bell, Loader2 } from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import PageHero from "../components/PageHero";
import { useSubscribe } from "../components/SubscribeDialog";
import { portalApi } from "../lib/api";

interface EventsPageProps {
  onNavigate: (page: string) => void;
}

const iconFor = (type?: string) => {
  const map: Record<string, typeof Calendar> = {
    Awards: Trophy,
    Training: GraduationCap,
    Forum: Users,
    Symposium: Megaphone,
  };
  return (type && map[type]) || CalendarDays;
};

const fmtDate = (iso?: string) => (iso ? new Date(iso).toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" }) : null);
const fmtTime = (iso?: string) => (iso ? new Date(iso).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : null);

interface EventItem {
  id: string;
  title: string;
  type?: string;
  image?: string;
  startsAt?: string;
  location?: string;
  description?: string;
}

export default function EventsPage({ onNavigate }: EventsPageProps) {
  const { t } = useLanguage();
  const subscribe = useSubscribe();
  const [events, setEvents] = useState<EventItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchData() {
      try {
        setIsLoading(true);
        const portalResponse = await portalApi.getEvents(1, 20).catch(() => null);

        let portalEvents: EventItem[] = [];
        if (portalResponse) {
          const list: any[] = Array.isArray(portalResponse) ? portalResponse : portalResponse.data || portalResponse.content || [];
          portalEvents = list.map((e) => ({
            id: String(e.id),
            title: e.title,
            type: e.event_category || e.type,
            image: e.cover_image_url || e.image_url || e.image,
            startsAt: e.starts_at || e.date,
            location: e.location,
            description: e.description || e.body,
          }));
        }

        setEvents(portalEvents);
      } catch (err: any) {
        console.error("Error fetching events:", err);
        setError(err.message || "Failed to load events");
      } finally {
        setIsLoading(false);
      }
    }
    fetchData();
  }, []);

  const goToEvent = (id: string) => onNavigate(`events/${id}`);

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      <PageHero
        title={t.pages.events.title}
        subtitle="Upcoming events, workshops and activities"
        breadcrumbs={[{ label: "Home", onClick: () => onNavigate("home") }, { label: "Events" }]}
      />

      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1100px] mx-auto">
          <div className="mb-12">
            <h2 className="flex items-center gap-3 mb-8" style={{ color: "var(--primary-dark)", fontSize: "1.75rem" }}>
              <CalendarDays className="w-7 h-7" />
              Upcoming Events
            </h2>

            {isLoading ? (
              <div className="flex flex-col items-center justify-center py-20 gap-4">
                <Loader2 className="w-10 h-10 animate-spin" style={{ color: "var(--primary)" }} />
                <p className="font-medium" style={{ color: "var(--neutral-500)" }}>Loading events...</p>
              </div>
            ) : error ? (
              <div className="text-center py-20 bg-white rounded-3xl border" style={{ borderColor: "var(--neutral-100)" }}>
                <p className="mb-4" style={{ color: "var(--neutral-600)" }}>{error}</p>
                <button
                  onClick={() => window.location.reload()}
                  className="py-2 px-6 rounded-xl font-semibold text-white border-none cursor-pointer"
                  style={{ background: "var(--primary)" }}
                >
                  Retry
                </button>
              </div>
            ) : events.length === 0 ? (
              <div className="text-center py-12 rounded-2xl border border-dashed" style={{ borderColor: "var(--neutral-300)", background: "var(--neutral-50)" }}>
                <Calendar className="w-12 h-12 mx-auto mb-3" style={{ color: "var(--neutral-300)" }} />
                <p style={{ color: "var(--neutral-500)" }}>No upcoming events scheduled at this time.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {events.map((event) => {
                  const Icon = iconFor(event.type);
                  return (
                    <div
                      key={event.id}
                      className="bg-white rounded-[20px] overflow-hidden transition-all hover:-translate-y-1 cursor-pointer flex flex-col"
                      style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                      onClick={() => goToEvent(event.id)}
                      data-testid={`event-card-${event.id}`}
                    >
                      <div className="h-48 relative overflow-hidden bg-neutral-200 flex items-center justify-center">
                        {event.image ? (
                          <img src={event.image} alt={event.title} loading="lazy" className="w-full h-full object-cover" />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center" style={{ background: "var(--primary-lighter)" }}>
                            <Icon className="w-14 h-14" style={{ color: "var(--primary)" }} />
                          </div>
                        )}
                        <span className="absolute top-3 left-3 py-1 px-3 rounded-full text-xs font-semibold uppercase text-white" style={{ background: "var(--primary)" }}>
                          {event.type || "Event"}
                        </span>
                      </div>
                      <div className="p-6 flex-1 flex flex-col">
                        <h3 className="mb-3" style={{ color: "var(--neutral-800)" }}>{event.title}</h3>
                        {event.description && (
                          <p className="text-[0.9rem] mb-4 line-clamp-3" style={{ color: "var(--neutral-600)" }}>{event.description}</p>
                        )}
                        <div className="flex flex-wrap gap-x-4 gap-y-2 text-sm mt-auto" style={{ color: "var(--neutral-500)" }}>
                          {fmtDate(event.startsAt) && (
                            <span className="flex items-center gap-2 whitespace-nowrap">
                              <Calendar className="w-4 h-4" style={{ color: "var(--primary)" }} />
                              {fmtDate(event.startsAt)}
                            </span>
                          )}
                          {fmtTime(event.startsAt) && (
                            <span className="flex items-center gap-2 whitespace-nowrap">
                              <Clock className="w-4 h-4" style={{ color: "var(--primary)" }} />
                              {fmtTime(event.startsAt)}
                            </span>
                          )}
                          <span className="flex items-center gap-2">
                            <MapPin className="w-4 h-4" style={{ color: "var(--primary)" }} />
                            {event.location || "TBA"}
                          </span>
                        </div>
                        <button
                          className="mt-5 py-2.5 px-5 rounded-xl font-semibold text-sm border-none cursor-pointer text-white flex items-center gap-2 self-start"
                          style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                          onClick={(e) => {
                            e.stopPropagation();
                            goToEvent(event.id);
                          }}
                        >
                          View Details
                          <ArrowRight className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          <div
            className="rounded-[20px] p-10 text-center"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
          >
            <h3 className="text-white mb-3">Never Miss an Update</h3>
            <p className="text-white/85 mb-6 max-w-[500px] mx-auto">
              Subscribe to our newsletter to receive updates about upcoming events.
            </p>
            <div className="flex justify-center">
              <button
                onClick={() => subscribe.open()}
                className="flex items-center gap-2 py-3 px-7 rounded-xl font-bold border-none cursor-pointer"
                style={{ background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)", color: "var(--zim-black)" }}
              >
                <Bell className="w-5 h-5" />
                Subscribe for Free
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
