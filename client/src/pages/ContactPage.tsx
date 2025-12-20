import { useState } from "react";
import { MapPin, Phone, Mail, Clock, Send, CheckCircle, Building2 } from "lucide-react";

interface ContactPageProps {
  onNavigate: (page: string) => void;
}

export default function ContactPage({ onNavigate }: ContactPageProps) {
  const [formSubmitted, setFormSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setFormSubmitted(true);
    setTimeout(() => setFormSubmitted(false), 5000);
  };

  const offices = [
    {
      name: "Head Office - Harare",
      address: "108 Swan Drive, Alexandra Park, Harare",
      phone: "+263 242 253509/10",
      email: "info@zmc.org.zw",
      hours: "Mon - Fri: 8:00 AM - 4:30 PM",
    },
    {
      name: "Regional Office - Bulawayo",
      address: "Corner 9th Ave & Fort St, Bulawayo",
      phone: "+263 29 263 5678",
      email: "bulawayo@zmc.org.zw",
      hours: "Mon - Fri: 8:00 AM - 4:30 PM",
    },
  ];

  return (
    <div className="animate-fadeIn pt-[130px]">
      {/* Page Header */}
      <div
        className="py-16 px-8 text-center relative overflow-hidden"
        style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
      >
        <h1 className="text-white mb-3 relative">Contact Us</h1>
        <p className="text-white/85 max-w-[600px] mx-auto text-lg relative">
          Get in touch with the Zimbabwe Media Commission
        </p>
        <div className="flex justify-center gap-2 mt-6 text-[0.9rem]">
          <a href="#" onClick={(e) => { e.preventDefault(); onNavigate("home"); }} className="text-white/70 hover:text-white">Home</a>
          <span className="text-white/70">/</span>
          <span style={{ color: "var(--accent-light)" }}>Contact</span>
        </div>
      </div>

      {/* Content */}
      <div className="py-16 px-8">
        <div className="max-w-[1200px] mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {/* Contact Form */}
            <div>
              <div
                className="bg-white rounded-[20px] p-10"
                style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
              >
                <h2 className="mb-6" style={{ color: "var(--primary)", borderBottom: "2px solid var(--primary-lighter)", paddingBottom: "0.75rem" }}>
                  Send Us a Message
                </h2>

                {formSubmitted ? (
                  <div className="text-center py-12">
                    <div
                      className="w-20 h-20 rounded-full mx-auto mb-6 flex items-center justify-center text-white"
                      style={{ background: "linear-gradient(135deg, #22c55e 0%, #16a34a 100%)" }}
                    >
                      <CheckCircle className="w-10 h-10" />
                    </div>
                    <h3 className="mb-3" style={{ color: "var(--primary)" }}>Message Sent!</h3>
                    <p style={{ color: "var(--neutral-600)" }}>Thank you for contacting us. We'll respond within 24-48 hours.</p>
                  </div>
                ) : (
                  <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                      <div>
                        <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>First Name *</label>
                        <input
                          type="text"
                          required
                          className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
                          style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                          data-testid="input-contact-firstname"
                        />
                      </div>
                      <div>
                        <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Last Name *</label>
                        <input
                          type="text"
                          required
                          className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
                          style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                          data-testid="input-contact-lastname"
                        />
                      </div>
                    </div>
                    <div className="mb-4">
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Email Address *</label>
                      <input
                        type="email"
                        required
                        className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-contact-email"
                      />
                    </div>
                    <div className="mb-4">
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Phone Number</label>
                      <input
                        type="tel"
                        className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="input-contact-phone"
                      />
                    </div>
                    <div className="mb-4">
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Subject *</label>
                      <select
                        required
                        className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="select-subject"
                      >
                        <option value="">Select subject...</option>
                        <option value="general">General Inquiry</option>
                        <option value="accreditation">Accreditation Query</option>
                        <option value="registration">Registration Query</option>
                        <option value="complaint">Complaint Status</option>
                        <option value="media">Media Inquiry</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                    <div className="mb-6">
                      <label className="block mb-2 font-medium text-[0.9rem]" style={{ color: "var(--neutral-700)" }}>Message *</label>
                      <textarea
                        required
                        rows={5}
                        className="w-full py-3 px-4 rounded-xl text-base transition-all focus:outline-none"
                        style={{ border: "2px solid var(--neutral-200)", background: "var(--neutral-50)" }}
                        data-testid="textarea-message"
                      />
                    </div>
                    <button
                      type="submit"
                      className="w-full py-4 rounded-xl font-semibold text-base border-none cursor-pointer text-white flex items-center justify-center gap-2"
                      style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                      data-testid="button-send-message"
                    >
                      <Send className="w-5 h-5" />
                      Send Message
                    </button>
                  </form>
                )}
              </div>
            </div>

            {/* Contact Info */}
            <div>
              {offices.map((office, index) => (
                <div
                  key={index}
                  className="bg-white rounded-[20px] p-8 mb-6"
                  style={{ boxShadow: "var(--shadow-sm)", border: "1px solid var(--neutral-100)" }}
                >
                  <div className="flex items-center gap-3 mb-6">
                    <div
                      className="w-12 h-12 rounded-xl flex items-center justify-center"
                      style={{ background: "var(--primary-lighter)", color: "var(--primary)" }}
                    >
                      <Building2 className="w-6 h-6" />
                    </div>
                    <h3 className="m-0" style={{ color: "var(--primary-dark)" }}>{office.name}</h3>
                  </div>
                  <div className="space-y-4">
                    <div className="flex items-start gap-4">
                      <MapPin className="w-5 h-5 mt-1 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <div>
                        <p className="font-medium text-[0.9rem] mb-1" style={{ color: "var(--neutral-700)" }}>Address</p>
                        <p className="text-[0.95rem]" style={{ color: "var(--neutral-600)" }}>{office.address}</p>
                      </div>
                    </div>
                    <div className="flex items-start gap-4">
                      <Phone className="w-5 h-5 mt-1 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <div>
                        <p className="font-medium text-[0.9rem] mb-1" style={{ color: "var(--neutral-700)" }}>Phone</p>
                        <p className="text-[0.95rem] whitespace-nowrap" style={{ color: "var(--neutral-600)" }}>{office.phone}</p>
                      </div>
                    </div>
                    <div className="flex items-start gap-4">
                      <Mail className="w-5 h-5 mt-1 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <div>
                        <p className="font-medium text-[0.9rem] mb-1" style={{ color: "var(--neutral-700)" }}>Email</p>
                        <a href={`mailto:${office.email}`} className="text-[0.95rem]" style={{ color: "var(--primary)" }}>{office.email}</a>
                      </div>
                    </div>
                    <div className="flex items-start gap-4">
                      <Clock className="w-5 h-5 mt-1 flex-shrink-0" style={{ color: "var(--primary)" }} />
                      <div>
                        <p className="font-medium text-[0.9rem] mb-1" style={{ color: "var(--neutral-700)" }}>Office Hours</p>
                        <p className="text-[0.95rem] whitespace-nowrap" style={{ color: "var(--neutral-600)" }}>{office.hours}</p>
                      </div>
                    </div>
                  </div>
                </div>
              ))}

              {/* Map Placeholder */}
              <div
                className="rounded-[20px] overflow-hidden h-[250px] flex items-center justify-center"
                style={{ background: "var(--primary-lighter)", border: "1px solid var(--neutral-200)" }}
              >
                <div className="text-center">
                  <MapPin className="w-12 h-12 mx-auto mb-3" style={{ color: "var(--primary)" }} />
                  <p style={{ color: "var(--primary-dark)" }}>108 Swan Drive, Alexandra Park, Harare</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
