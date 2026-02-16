import { Calendar, MapPin, Clock, Users, ArrowRight, CalendarDays, Trophy, GraduationCap, Megaphone } from "lucide-react";

interface EventsPageProps {
  onNavigate: (page: string) => void;
}

export default function EventsPage({ onNavigate }: EventsPageProps) {
  const upcomingEvents = [
    {
      icon: Trophy,
      title: "2024 Media Awards Ceremony",
      date: "December 15, 2024",
      time: "6:00 PM - 10:00 PM",
      location: "Harare International Conference Centre",
      description: "Annual celebration of excellence in Zimbabwean journalism. Join us for an evening honoring outstanding media practitioners.",
      type: "Awards",
      color: "var(--accent-dark)",
      bg: "var(--accent-soft)",
    },
    {
      icon: GraduationCap,
      title: "Digital Journalism Workshop",
      date: "January 20, 2025",
      time: "9:00 AM - 4:00 PM",
      location: "ZMC Training Centre, Harare",
      description: "A comprehensive workshop on digital journalism tools, fact-checking, and ethical reporting in the digital age.",
      type: "Training",
      color: "var(--blue)",
      bg: "var(--blue-light)",
    },
    {
      icon: Users,
      title: "Media Stakeholders Forum",
      date: "February 5, 2025",
      time: "10:00 AM - 2:00 PM",
      location: "Rainbow Towers, Harare",
      description: "Quarterly meeting bringing together media houses, journalists, and regulatory bodies to discuss industry challenges.",
      type: "Forum",
      color: "var(--primary)",
      bg: "var(--primary-lighter)",
    },
    {
      icon: Megaphone,
      title: "Press Freedom Symposium",
      date: "March 3, 2025",
      time: "9:00 AM - 5:00 PM",
      location: "HICC, Harare",
      description: "Annual symposium celebrating World Press Freedom Day with discussions on media freedom and journalist safety.",
      type: "Symposium",
      color: "var(--purple)",
      bg: "var(--purple-light)",
    },
  ];

  const pastEvents = [
    { title: "Accreditation Renewal Drive", date: "October 2024", attendees: 250 },
    { title: "Ethics Training Program", date: "September 2024", attendees: 120 },
    { title: "Regional Outreach - Bulawayo", date: "August 2024", attendees: 85 },
  ];

  return (
    <div className="animate-fadeIn pt-[140px] md:pt-[180px]">
      {/* Page Header */}
      <div
        className="py-12 md:py-16 px-4 md:px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative text-2xl md:text-4xl">Events & Programs</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-base md:text-lg relative">
          Stay updated with ZMC events, workshops, and training programs
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem]">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Events</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-12 md:py-16 px-4 md:px-8">
        <div className="max-w-[1000px] mx-auto">
          {/* Upcoming Events */}
          <div className="mb-12">
            <h2 className="flex items-center gap-3 mb-8" style={{ color: "var(--primary-dark)" }}>
              <CalendarDays className="w-7 h-7" />
              Upcoming Events
            </h2>
            <div className="space-y-6">
              {upcomingEvents.map((event, index) => (
                <div
                  key={index}
                  className="bg-white rounded-[20px] overflow-hidden transition-all hover:-translate-y-1"
                  style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                  data-testid={`event-card-${index}`}
                >
                  <div className="flex flex-col md:flex-row">
                    <div
                      className="w-full md:w-[200px] p-8 flex flex-col items-center justify-center text-center"
                      style={{ background: event.bg }}
                    >
                      <event.icon className="w-12 h-12 mb-3" style={{ color: event.color }} />
                      <span
                        className="py-1 px-3 rounded-full text-xs font-semibold uppercase"
                        style={{ background: event.color, color: "white" }}
                      >
                        {event.type}
                      </span>
                    </div>
                    <div className="flex-1 p-8">
                      <h3 className="mb-3" style={{ color: "var(--neutral-800)" }}>{event.title}</h3>
                      <p className="text-[0.95rem] mb-4" style={{ color: "var(--neutral-600)" }}>{event.description}</p>
                      <div className="flex flex-wrap gap-x-4 gap-y-2 text-sm" style={{ color: "var(--neutral-500)" }}>
                        <span className="flex items-center gap-2 whitespace-nowrap">
                          <Calendar className="w-4 h-4 flex-shrink-0" style={{ color: event.color }} />
                          {event.date}
                        </span>
                        <span className="flex items-center gap-2 whitespace-nowrap">
                          <Clock className="w-4 h-4 flex-shrink-0" style={{ color: event.color }} />
                          {event.time}
                        </span>
                        <span className="flex items-center gap-2">
                          <MapPin className="w-4 h-4 flex-shrink-0" style={{ color: event.color }} />
                          {event.location}
                        </span>
                      </div>
                      <button
                        className="mt-5 py-2.5 px-5 rounded-xl font-semibold text-sm border-none cursor-pointer text-white flex items-center gap-2"
                        style={{ background: `linear-gradient(135deg, ${event.color} 0%, ${event.color} 100%)` }}
                      >
                        Learn More
                        <ArrowRight className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Past Events */}
          <div className="bg-white rounded-[20px] p-10" style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}>
            <h2 className="mb-6" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
              Past Events
            </h2>
            <div className="space-y-4">
              {pastEvents.map((event, index) => (
                <div
                  key={index}
                  className="flex items-center justify-between p-4 rounded-xl"
                  style={{ background: "var(--neutral-50)", border: "1px solid var(--neutral-200)" }}
                >
                  <div>
                    <h4 className="text-base font-semibold m-0 mb-1" style={{ fontFamily: "var(--font-sans)", color: "var(--neutral-700)" }}>{event.title}</h4>
                    <span className="text-sm" style={{ color: "var(--neutral-500)" }}>{event.date}</span>
                  </div>
                  <div className="flex items-center gap-2 text-sm" style={{ color: "var(--primary)" }}>
                    <Users className="w-4 h-4" />
                    {event.attendees} attendees
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Newsletter CTA */}
          <div
            className="mt-12 rounded-[20px] p-10 text-center"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
          >
            <h3 className="text-white mb-3">Never Miss an Event</h3>
            <p className="text-white/85 mb-6 max-w-[500px] mx-auto">
              Subscribe to our newsletter to receive updates about upcoming events and training programs.
            </p>
            <div className="flex gap-3 max-w-[400px] mx-auto">
              <input
                type="email"
                placeholder="Enter your email"
                className="flex-1 py-3 px-4 rounded-xl text-base border-none"
                style={{ background: "rgba(255,255,255,0.95)" }}
                data-testid="input-newsletter-email"
              />
              <button
                className="py-3 px-6 rounded-xl font-bold border-none cursor-pointer"
                style={{ background: "linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%)", color: "var(--zim-black)" }}
                data-testid="button-subscribe"
              >
                Subscribe
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
