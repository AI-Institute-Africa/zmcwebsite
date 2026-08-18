import { useState, useRef, useEffect } from "react";
import {
  X,
  Send,
  Sparkles,
  BadgeCheck,
  Building2,
  AlertTriangle,
  Scale,
  GraduationCap,
  DollarSign,
  ArrowRight,
  LayoutGrid,
  RotateCcw,
} from "lucide-react";
import { useLanguage } from "../i18n/LanguageContext";
import zmcLogo from "@assets/zmc_logo-removebg-preview_1771225841865.png";

interface ChatbotProps {
  onNavigate: (page: string) => void;
}

interface ChatAction {
  label: string;
  value: string;
  icon?: "fees" | "link" | "menu";
}

interface Message {
  type: "user" | "bot";
  content: string;
  actions?: ChatAction[];
  link?: { label: string; page: string };
}

type CategoryKey = "accreditation" | "registration" | "complaints" | "appeals" | "training";

interface Category {
  key: CategoryKey;
  label: string;
  icon: typeof BadgeCheck;
  color: string;
  page: string;
}

const CATEGORIES: Category[] = [
  { key: "accreditation", label: "Accreditation", icon: BadgeCheck, color: "#2E7D56", page: "accreditation" },
  { key: "registration", label: "Registration", icon: Building2, color: "#1B5E3F", page: "registration" },
  { key: "complaints", label: "Media Complaints", icon: AlertTriangle, color: "#C62828", page: "complaints" },
  { key: "appeals", label: "FOIA Appeals", icon: Scale, color: "#0277BD", page: "appeals" },
  { key: "training", label: "Training & Capacitation", icon: GraduationCap, color: "#D4AF37", page: "media-hub" },
];

export default function Chatbot({ onNavigate }: ChatbotProps) {
  const { t } = useLanguage();
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState("");
  const [showMenu, setShowMenu] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const prevLangRef = useRef(t);
  const pendingTimeouts = useRef<number[]>([]);
  const menuTimeoutRef = useRef<number | null>(null);

  const greeting: Message = {
    type: "bot",
    content:
      "Hello! Welcome to the Zimbabwe Media Commission. I'm the ZMC Assistant.\n\nChoose a category below to get started, or type your question.",
  };

  const scheduleMenu = () => {
    if (menuTimeoutRef.current) clearTimeout(menuTimeoutRef.current);
    setShowMenu(false);
    menuTimeoutRef.current = window.setTimeout(() => setShowMenu(true), 1500);
  };

  useEffect(() => {
    setMessages([greeting]);
    scheduleMenu();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (prevLangRef.current !== t) {
      setMessages([greeting]);
      scheduleMenu();
      prevLangRef.current = t;
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [t]);

  const clearChat = () => {
    pendingTimeouts.current.forEach((id) => clearTimeout(id));
    pendingTimeouts.current = [];
    setMessages([greeting]);
    scheduleMenu();
    setInput("");
  };

  useEffect(() => {
    return () => {
      pendingTimeouts.current.forEach((id) => clearTimeout(id));
      if (menuTimeoutRef.current) clearTimeout(menuTimeoutRef.current);
    };
  }, []);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const categoryContent = (key: CategoryKey): Message => {
    switch (key) {
      case "accreditation":
        return {
          type: "bot",
          content: `📋 Accreditation

Accreditation of journalists and media practitioners is provided for by Statutory Instrument 169C of 2002. SI 65 of 2022 prescribes the fees payable.

Requirements — Local Practitioners:
• Certified copy of National ID
• One passport-sized photograph
• Certified academic & professional qualifications (or samples of past work)
• Reference materials (employer letter, testimonial, or referral)
• Accreditation fee (SI 65 of 2022)

Requirements — Foreign Practitioners:
• Temporary Employment Permit (TEP) / Ministry clearance
• Copy of a valid passport
• One passport-sized photograph
• Accreditation fee (SI 65 of 2022)`,
          actions: [
            { label: "View Fees", value: "fees:accreditation", icon: "fees" },
            { label: "Main Menu", value: "menu", icon: "menu" },
          ],
          link: { label: "More on Accreditation", page: "accreditation" },
        };
      case "registration":
        return {
          type: "bot",
          content: `🏢 Mass Media Service Registration

Registration of mass media services is provided for by SI 169C of 2002 as read with SI 10 of 2004. SI 65 of 2022 prescribes the fees payable.

Requirements — Local Media Organizations:
• Projected cash flow & balance sheet (3 years)
• Editorial charter, code of ethics & code of conduct
• Market analysis and in-house style book
• Certified IDs for directors
• Dummy copy, mission statement
• Certificate of incorporation & memorandum of association
• Application & registration fee (SI 65 of 2022)

Requirements — Foreign Representative Office:
• Ministry clearance to operate a representative office
• Proof of registration in home country
• Names & certified IDs of office personnel
• Zimbabwe contact details (address, email, phone)
• Application & registration fee (SI 65 of 2022)`,
          actions: [
            { label: "View Fees", value: "fees:registration", icon: "fees" },
            { label: "Main Menu", value: "menu", icon: "menu" },
          ],
          link: { label: "More on Registration", page: "registration" },
        };
      case "complaints":
        return {
          type: "bot",
          content: `⚖️ Media Complaints

The Zimbabwe Media Commission handles complaints related to media and media practitioners — covering print, broadcast and digital media.

The Commission receives and considers complaints from the public and, where appropriate, takes action against journalists or persons employed in the media who breach any law or applicable code of conduct.`,
          actions: [{ label: "Main Menu", value: "menu", icon: "menu" }],
          link: { label: "How to Lodge a Complaint", page: "complaints" },
        };
      case "appeals":
        return {
          type: "bot",
          content: `📑 Freedom of Information Act (FOIA) Appeals

Under the FOIA, the Zimbabwe Media Commission reviews decisions relating to access to information held by public entities, and receives appeals from members of the public denied access to requested information.

You have the right to appeal if a public entity has refused your request, delayed unreasonably, charged excessive fees, or provided incomplete information.

The FOIA forms (the Request Form and the Appeal Form) are contained in the Statutory Instrument documents — specifically SI 229 of 2021, the Freedom of Information (General) Regulations — which you can download from the Reports & Documents page.`,
          actions: [{ label: "Main Menu", value: "menu", icon: "menu" }],
          link: { label: "How to Submit an Appeal", page: "appeals" },
        };
      case "training":
        return {
          type: "bot",
          content: `🎓 Training & Capacitation

In line with its mandate to promote freedom of expression and uphold professional standards, the ZMC provides targeted capacity-building programs for media practitioners.

These focus on enhancing digital skills, enforcing media ethics and professional standards, ensuring journalist safety, advancing inclusive policies, and delivering decentralized training across all provinces.`,
          actions: [{ label: "Main Menu", value: "menu", icon: "menu" }],
          link: { label: "Visit the Media Hub", page: "media-hub" },
        };
    }
  };

  const feesContent = (key: "accreditation" | "registration"): Message => {
    if (key === "accreditation") {
      return {
        type: "bot",
        content: `💵 Accreditation Fees — SI 65 of 2022:

Local Journalist (AP3):
• Accreditation (first applicant): US$20
• Renewal of accreditation: US$15
• Lost card replacement: US$10

Local Journalist for a Foreign Media House:
• Application: US$50
• Accreditation: US$150
• Renewal: US$150

Foreign Journalist (representative office):
• Application: US$50
• Accreditation: US$300
• Renewal: US$300

Temporary Accreditation (Foreign Journalist):
• Application: US$50
• Accreditation: US$150
• Extension of period: US$50
• Productions / Projects: US$2 500

Special Accreditation:
• Application: US$15
• SADC: US$30
• Rest of Africa: US$40
• Others: US$50

NB: Fees for local journalists are also payable in local currency at the prevailing interbank rate on the day of payment.`,
        actions: [
          { label: "Back to Accreditation", value: "cat:accreditation", icon: "link" },
          { label: "Main Menu", value: "menu", icon: "menu" },
        ],
      };
    }
    return {
      type: "bot",
      content: `💵 Registration Fees — SI 65 of 2022:

Mass Media Service (AP1):
• Application: US$300
• Registration: US$2 000
• Renewal of registration: US$1 000
• Late renewal fine: US$500

Community Mass Media Service:
• Application: US$200
• Registration: US$1 000
• Renewal of registration: US$800
• Late renewal fine: US$500

Productions & Digital Platforms:
• Application: US$200
• Registration: US$1 000
• Renewal of registration: US$800

News Agency (AP2):
• Application: US$200
• Registration: US$1 000
• Renewal of registration: US$800

Foreign Mass Media — Representative Office (AP4):
• International: Application US$500 · Permit US$3 000 · Renewal US$2 500
• SADC & Others: Application US$200 · Permit US$1 500 · Renewal US$1 000

NB: Fees for local mass media services are also payable in local currency at the prevailing interbank rate on the day of payment.`,
      actions: [
        { label: "Back to Registration", value: "cat:registration", icon: "link" },
        { label: "Main Menu", value: "menu", icon: "menu" },
      ],
    };
  };

  const pushBot = (msg: Message) => {
    const id = window.setTimeout(() => {
      setMessages((prev) => [...prev, msg]);
      pendingTimeouts.current = pendingTimeouts.current.filter((t) => t !== id);
    }, 400);
    pendingTimeouts.current.push(id);
  };

  const selectCategory = (cat: Category) => {
    setShowMenu(false);
    setMessages((prev) => [...prev, { type: "user", content: cat.label }]);
    pushBot(categoryContent(cat.key));
  };

  const handleAction = (value: string) => {
    if (value === "menu") {
      setShowMenu(true);
      pushBot({
        type: "bot",
        content: "Here are the main categories — pick one to continue.",
      });
      return;
    }
    if (value.startsWith("fees:")) {
      const key = value.split(":")[1] as "accreditation" | "registration";
      setMessages((prev) => [...prev, { type: "user", content: "View Fees" }]);
      pushBot(feesContent(key));
      return;
    }
    if (value.startsWith("cat:")) {
      const key = value.split(":")[1] as CategoryKey;
      pushBot(categoryContent(key));
      return;
    }
  };

  const getBotResponse = (message: string): Message => {
    const m = message.toLowerCase();

    const matched = CATEGORIES.find((c) => {
      if (c.key === "accreditation") return m.includes("accredit") || m.includes("press card") || m.includes("journalist card");
      if (c.key === "registration") return m.includes("regist") || m.includes("media house");
      if (c.key === "complaints") return m.includes("complain");
      if (c.key === "appeals") return m.includes("appeal") || m.includes("foia") || m.includes("freedom of information");
      if (c.key === "training") return m.includes("train") || m.includes("media hub") || m.includes("course") || m.includes("capacit");
      return false;
    });

    if ((m.includes("fee") || m.includes("cost") || m.includes("price") || m.includes("how much") || m.includes("pay")) ) {
      if (m.includes("accredit")) return feesContent("accreditation");
      if (m.includes("regist")) return feesContent("registration");
      return {
        type: "bot",
        content: "Fees are prescribed by SI 65 of 2022. Which would you like to see?",
        actions: [
          { label: "Accreditation Fees", value: "fees:accreditation", icon: "fees" },
          { label: "Registration Fees", value: "fees:registration", icon: "fees" },
        ],
      };
    }

    if (matched) return categoryContent(matched.key);

    if (m.includes("contact") || m.includes("phone") || m.includes("email") || m.includes("address") || m.includes("where")) {
      return {
        type: "bot",
        content: `📞 Contact Information:
Phone: +263 242 253509/10
WhatsApp: 0719 299 150
Email: info@zmc.org.zw
Address: 108 Swan Drive, Alexandra Park, Harare

Office Hours: Mon–Fri, 8:00 AM – 4:30 PM`,
        actions: [{ label: "Main Menu", value: "menu", icon: "menu" }],
      };
    }

    if (m.includes("hello") || m.includes("hi") || m.includes("hey") || m.includes("good morning") || m.includes("good afternoon")) {
      return { type: "bot", content: "Hello! How can I help you today? Tap the Main Menu button to browse categories, or just type your question.", actions: [{ label: "Main Menu", value: "menu", icon: "menu" }] };
    }

    if (m.includes("thank")) {
      return { type: "bot", content: "You're welcome! Is there anything else I can help you with?", actions: [{ label: "Main Menu", value: "menu", icon: "menu" }] };
    }

    return {
      type: "bot",
      content: "I can help with Accreditation, Registration, Media Complaints, FOIA Appeals, Training & Capacitation, fees, and contact details. Tap the Main Menu button below to browse categories, or rephrase your question.",
      actions: [{ label: "Main Menu", value: "menu", icon: "menu" }],
    };
  };

  const sendMessage = () => {
    if (!input.trim()) return;
    const userMessage = input.trim();
    setMessages((prev) => [...prev, { type: "user", content: userMessage }]);
    setInput("");
    setShowMenu(false);
    pushBot(getBotResponse(userMessage));
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") sendMessage();
  };

  const renderActionIcon = (icon?: ChatAction["icon"]) => {
    if (icon === "fees") return <DollarSign className="w-3.5 h-3.5" />;
    if (icon === "menu") return <LayoutGrid className="w-3.5 h-3.5" />;
    if (icon === "link") return <ArrowRight className="w-3.5 h-3.5" />;
    return null;
  };

  const renderMessageContent = (message: Message, index: number) => {
    if (index === 0 && message.type === "bot") {
      let wordIdx = 0;
      return message.content.split(/(\s+)/).map((token, i) => {
        if (/^\s+$/.test(token) || token === "") return token;
        const delay = wordIdx * 0.045;
        wordIdx += 1;
        const clean = token.replace(/[^A-Za-z]/g, "");
        const isBrand = clean === "Zimbabwe" || clean === "Media" || clean === "Commission";
        return (
          <span
            key={i}
            className="welcome-word"
            style={{
              animationDelay: `${delay}s`,
              fontWeight: isBrand ? 700 : undefined,
              color: clean === "Media" ? "var(--primary)" : undefined,
            }}
          >
            {token}
          </span>
        );
      });
    }
    return message.content;
  };

  return (
    <>
      {!isOpen && (
        <a
          href="/media-hub"
          className="fixed bottom-[72px] right-4 md:bottom-[108px] md:right-8 z-[1500] no-underline animate-bounce-gentle"
          style={{ animationDuration: "2s" }}
          data-testid="banner-media-hub-coming-soon"
        >
          <div
            className="flex items-center gap-2 py-2 px-3 md:py-2.5 md:px-4 rounded-xl text-white text-xs md:text-sm font-bold shadow-lg"
            style={{
              background: "linear-gradient(135deg, #D4AF37 0%, #b8960f 100%)",
              boxShadow: "0 4px 16px rgba(212, 175, 55, 0.4)",
            }}
          >
            <Sparkles className="w-3.5 h-3.5 md:w-4 md:h-4" />
            {t.mediaHubComingSoon}
          </div>
        </a>
      )}

      <button
        onClick={() => setIsOpen(!isOpen)}
        className="fixed bottom-4 right-4 md:bottom-8 md:right-8 w-14 h-14 md:w-[72px] md:h-[72px] rounded-full flex items-center justify-center cursor-pointer z-[1500] transition-all hover:scale-110"
        style={{
          background: "#ffffff",
          border: "3px solid var(--primary)",
          boxShadow: "0 4px 20px rgba(46, 125, 86, 0.3)",
        }}
        aria-label={isOpen ? "Close chat assistant" : "Open chat assistant"}
        data-testid="button-chatbot-toggle"
      >
        {isOpen ? (
          <X className="w-6 h-6 md:w-7 md:h-7" style={{ color: "var(--primary)" }} />
        ) : (
          <img src={zmcLogo} alt="ZMC Assistant" className="w-11 h-11 md:w-14 md:h-14 object-contain" />
        )}
      </button>

      <div
        className={`fixed bottom-20 md:bottom-28 right-2 md:right-8 w-[calc(100vw-16px)] md:w-[400px] max-w-[400px] h-[72vh] md:h-[560px] max-h-[calc(100vh-110px)] bg-white rounded-2xl md:rounded-[24px] z-[1500] flex flex-col overflow-hidden transition-all duration-300 ${
          isOpen
            ? "opacity-100 visible translate-y-0 scale-100"
            : "opacity-0 invisible translate-y-5 scale-95"
        }`}
        style={{ boxShadow: "0 24px 64px rgba(27, 94, 63, 0.22), 0 6px 18px rgba(0,0,0,0.08)", border: "1px solid var(--primary-lighter)", fontFamily: "var(--font-chat)", letterSpacing: "-0.01em" }}
      >
        {/* Header */}
        <div
          className="py-4 px-5 flex justify-between items-center relative overflow-hidden"
          style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
        >
          <div
            className="absolute -top-8 -right-6 w-28 h-28 rounded-full pointer-events-none"
            style={{ background: "radial-gradient(circle, rgba(255,255,255,0.22) 0%, transparent 70%)" }}
          />
          <div
            className="absolute -bottom-10 left-10 w-24 h-24 rounded-full pointer-events-none"
            style={{ background: "radial-gradient(circle, rgba(212,175,55,0.18) 0%, transparent 70%)" }}
          />
          <div className="flex items-center gap-3">
            <span
              className="w-11 h-11 rounded-full flex items-center justify-center bg-white overflow-hidden"
            >
              <img src={zmcLogo} alt="ZMC Assistant" className="w-10 h-10 object-contain" />
            </span>
            <div>
              <h4 className="text-white text-base font-semibold m-0">
                ZMC Assistant
              </h4>
              <span className="text-xs text-white/80 flex items-center gap-1.5">
                <span className="w-2 h-2 bg-green-400 rounded-full" />
                Online
              </span>
            </div>
          </div>
          <div className="flex items-center gap-2 relative z-10">
            <button
              onClick={clearChat}
              className="h-9 px-3 rounded-full flex items-center gap-1.5 cursor-pointer transition-all text-white border-none text-xs font-semibold hover:bg-white/30"
              style={{ background: "rgba(255,255,255,0.2)" }}
              aria-label="Clear chat"
              data-testid="button-chatbot-clear"
            >
              <RotateCcw className="w-4 h-4" />
              Clear
            </button>
            <button
              onClick={() => setIsOpen(false)}
              className="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer transition-all text-white border-none hover:bg-white/30"
              style={{ background: "rgba(255,255,255,0.2)" }}
              aria-label="Close chat"
              data-testid="button-chatbot-close"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Messages */}
        <div className="chat-scroll flex-1 overflow-y-auto p-4 flex flex-col gap-3" style={{ background: "linear-gradient(180deg, var(--neutral-50) 0%, #ffffff 100%)" }}>
          {messages.map((message, index) => (
            <div key={index} className={`animate-chat-bubble flex flex-col max-w-[88%] ${message.type === "bot" ? "self-start items-start" : "self-end items-end"}`}>
              <div className={`flex items-end gap-2 ${message.type === "bot" ? "" : "flex-row-reverse"}`}>
                {message.type === "bot" && (
                  <span
                    className="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 bg-white border mb-1 overflow-hidden"
                    style={{ borderColor: "var(--neutral-200)" }}
                  >
                    <img src={zmcLogo} alt="ZMC" className="w-6 h-6 object-contain" />
                  </span>
                )}
                <div
                  className={`py-3 px-4 text-[0.875rem] leading-relaxed ${index === 0 && message.type === "bot" ? "animate-welcome-glow" : ""}`}
                  style={{
                    borderRadius: message.type === "bot" ? "16px 16px 16px 4px" : "16px 16px 4px 16px",
                    background:
                      message.type === "bot"
                        ? "var(--white)"
                        : "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
                    color: message.type === "bot" ? "var(--neutral-700)" : "white",
                    boxShadow: message.type === "bot" ? "0 2px 10px rgba(0,0,0,0.06)" : "none",
                    border: message.type === "bot" ? "1px solid var(--neutral-100)" : "none",
                    whiteSpace: "pre-line",
                  }}
                >
                  {renderMessageContent(message, index)}
                </div>
              </div>

              {/* Inline action buttons + link */}
              {(message.actions || message.link) && message.type === "bot" && (
                <div className="flex flex-wrap gap-2 mt-2 ml-9">
                  {message.actions?.map((action) => (
                    <button
                      key={action.value}
                      onClick={() => handleAction(action.value)}
                      className="flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-semibold cursor-pointer transition-all hover:-translate-y-0.5"
                      style={{
                        background: action.icon === "fees" ? "var(--accent-soft)" : "var(--primary-lighter)",
                        color: action.icon === "fees" ? "var(--accent-dark)" : "var(--primary-dark)",
                        border: `1px solid ${action.icon === "fees" ? "var(--accent)" : "var(--primary-light)"}`,
                      }}
                      data-testid={`action-${action.value}`}
                    >
                      {renderActionIcon(action.icon)}
                      {action.label}
                    </button>
                  ))}
                  {message.link && (
                    <button
                      onClick={() => { onNavigate(message.link!.page); setIsOpen(false); }}
                      className="flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-semibold cursor-pointer transition-all hover:-translate-y-0.5 text-white border-none"
                      style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
                      data-testid={`link-${message.link.page}`}
                    >
                      {message.link.label}
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              )}
            </div>
          ))}

          {/* Category menu */}
          {showMenu && (
            <div className="self-start w-full mt-1">
              <p className="text-xs font-semibold mb-2 ml-1" style={{ color: "var(--neutral-500)" }}>
                SELECT A CATEGORY
              </p>
              <div className="grid grid-cols-1 gap-2">
                {CATEGORIES.map((cat) => {
                  const Icon = cat.icon;
                  return (
                    <button
                      key={cat.key}
                      onClick={() => selectCategory(cat)}
                      className="flex items-center gap-3 py-3 px-4 rounded-xl bg-white cursor-pointer transition-all hover:-translate-y-0.5 text-left"
                      style={{ border: "1px solid var(--neutral-200)", boxShadow: "0 2px 8px rgba(0,0,0,0.04)" }}
                      data-testid={`category-${cat.key}`}
                    >
                      <span
                        className="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 text-white"
                        style={{ background: cat.color }}
                      >
                        <Icon className="w-5 h-5" />
                      </span>
                      <span className="text-sm font-semibold" style={{ color: "var(--neutral-800)" }}>
                        {cat.label}
                      </span>
                      <ArrowRight className="w-4 h-4 ml-auto" style={{ color: "var(--neutral-400)" }} />
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          <div ref={messagesEndRef} />
        </div>

        {/* Input */}
        <div className="p-3 flex items-center gap-2" style={{ background: "var(--white)", borderTop: "1px solid var(--neutral-200)" }}>
          {!showMenu && (
            <button
              onClick={() => handleAction("menu")}
              className="w-10 h-10 rounded-full flex items-center justify-center cursor-pointer transition-all hover:scale-105 flex-shrink-0"
              style={{ background: "var(--primary-lighter)", color: "var(--primary-dark)", border: "1px solid var(--primary-light)" }}
              aria-label="Open main menu"
              data-testid="button-open-menu"
              title="Main menu"
            >
              <LayoutGrid className="w-5 h-5" />
            </button>
          )}
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyPress={handleKeyPress}
            placeholder="Type your message..."
            className="flex-1 py-3 px-4 rounded-[25px] text-[0.9rem] transition-all focus:outline-none"
            style={{ border: "2px solid var(--neutral-200)", fontFamily: "var(--font-chat)" }}
            data-testid="input-chat-message"
          />
          <button
            onClick={sendMessage}
            className="w-11 h-11 rounded-full flex items-center justify-center cursor-pointer transition-all hover:scale-105 border-none text-white flex-shrink-0"
            style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
            aria-label="Send message"
            data-testid="button-send-message"
          >
            <Send className="w-5 h-5" />
          </button>
        </div>
      </div>
    </>
  );
}
