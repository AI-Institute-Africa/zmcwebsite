import { useState, useRef, useEffect } from "react";
import { MessageCircle, X, Bot, Send, Hand, HelpCircle, MapPin, Phone, Mail, Building2, Clock, FilePen, ClipboardList, Coins, Smile } from "lucide-react";

interface ChatbotProps {
  onNavigate: (page: string) => void;
}

interface Message {
  type: "user" | "bot";
  content: string;
}

export default function Chatbot({ onNavigate }: ChatbotProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState<Message[]>([
    {
      type: "bot",
      content: `Hello! Welcome to Zimbabwe Media Commission. How can I help you today?

You can ask me about:
- Accreditation
- Registration
- Complaints & Appeals
- Contact Information`,
    },
  ]);
  const [input, setInput] = useState("");
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const getBotResponse = (message: string): string => {
    const lowerMessage = message.toLowerCase();

    if (lowerMessage.includes("accreditation") || lowerMessage.includes("accredit")) {
      return "For accreditation, you need: certified ID copy, passport photo, qualifications or work samples, and employer letter. Visit the Accreditation page for details.";
    } else if (lowerMessage.includes("registration") || lowerMessage.includes("register")) {
      return "Media registration requires: cash flow projections, editorial charter, code of ethics, and incorporation documents. Visit the Registration page for full requirements.";
    } else if (lowerMessage.includes("complaint") || lowerMessage.includes("appeal")) {
      return "To lodge a complaint or appeal, download the forms from our Downloads page and submit them to our office.";
    } else if (
      lowerMessage.includes("contact") ||
      lowerMessage.includes("phone") ||
      lowerMessage.includes("email") ||
      lowerMessage.includes("address")
    ) {
      return `Contact us at:
Phone: +263 242 253509/10
Email: info@zmc.org.zw
Address: 108 Swan Drive, Alexandra Park, Harare
Hours: Mon-Fri: 8am - 4:30pm`;
    } else if (
      lowerMessage.includes("fee") ||
      lowerMessage.includes("cost") ||
      lowerMessage.includes("price") ||
      lowerMessage.includes("pay")
    ) {
      return "Fees are prescribed by SI 65 of 2022. Visit our Downloads page for the fee schedule.";
    } else if (
      lowerMessage.includes("hello") ||
      lowerMessage.includes("hi") ||
      lowerMessage.includes("hey")
    ) {
      return "Hello! Welcome to ZMC. How can I help you today? You can ask about accreditation, registration, complaints, or contact information.";
    } else if (lowerMessage.includes("thank")) {
      return "You're welcome! Is there anything else I can help you with?";
    } else {
      return `I can help you with information about:
- Accreditation
- Registration
- Complaints & Appeals
- Contact Information
- Fees

Please ask a specific question or visit our Contact page for more help.`;
    }
  };

  const sendMessage = () => {
    if (!input.trim()) return;

    const userMessage = input.trim();
    setMessages((prev) => [...prev, { type: "user", content: userMessage }]);
    setInput("");

    setTimeout(() => {
      const botResponse = getBotResponse(userMessage);
      setMessages((prev) => [...prev, { type: "bot", content: botResponse }]);
    }, 800);
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === "Enter") {
      sendMessage();
    }
  };

  return (
    <>
      {/* Chat Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="fixed bottom-8 right-8 w-16 h-16 rounded-full flex items-center justify-center cursor-pointer z-[1500] transition-all hover:scale-110"
        style={{
          background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
          boxShadow: "0 4px 20px rgba(74, 124, 111, 0.4)",
        }}
        data-testid="button-chatbot-toggle"
      >
        {isOpen ? (
          <X className="w-6 h-6 text-white" />
        ) : (
          <MessageCircle className="w-7 h-7 text-white" />
        )}
      </button>

      {/* Chat Window */}
      <div
        className={`fixed bottom-28 right-8 w-[380px] max-w-[calc(100vw-60px)] h-[500px] max-h-[calc(100vh-150px)] bg-white rounded-[20px] z-[1500] flex flex-col overflow-hidden transition-all ${
          isOpen
            ? "opacity-100 visible translate-y-0 scale-100"
            : "opacity-0 invisible translate-y-5 scale-95"
        }`}
        style={{ boxShadow: "0 10px 40px rgba(0,0,0,0.15)" }}
      >
        {/* Chat Header */}
        <div
          className="py-4 px-5 flex justify-between items-center"
          style={{ background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)" }}
        >
          <div className="flex items-center gap-3">
            <span
              className="w-11 h-11 rounded-full flex items-center justify-center text-white"
              style={{ background: "rgba(255,255,255,0.2)" }}
            >
              <Bot className="w-6 h-6" />
            </span>
            <div>
              <h4 className="text-white text-base font-semibold m-0" style={{ fontFamily: "var(--font-sans)" }}>
                ZMC Assistant
              </h4>
              <span className="text-xs text-white/80 flex items-center gap-1.5">
                <span className="w-2 h-2 bg-green-400 rounded-full" />
                Online
              </span>
            </div>
          </div>
          <button
            onClick={() => setIsOpen(false)}
            className="w-9 h-9 rounded-full flex items-center justify-center cursor-pointer transition-all text-white border-none"
            style={{ background: "rgba(255,255,255,0.2)" }}
            data-testid="button-chatbot-close"
          >
            <X className="w-4.5 h-4.5" />
          </button>
        </div>

        {/* Chat Messages */}
        <div
          className="flex-1 overflow-y-auto p-5 flex flex-col gap-4"
          style={{ background: "var(--neutral-50)" }}
        >
          {messages.map((message, index) => (
            <div
              key={index}
              className={`flex max-w-[85%] ${
                message.type === "bot" ? "self-start" : "self-end"
              }`}
            >
              <div
                className="py-3.5 px-4.5 text-[0.9rem] leading-relaxed"
                style={{
                  borderRadius: message.type === "bot" ? "18px 18px 18px 4px" : "18px 18px 4px 18px",
                  background:
                    message.type === "bot"
                      ? "var(--white)"
                      : "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
                  color: message.type === "bot" ? "var(--neutral-700)" : "white",
                  boxShadow: message.type === "bot" ? "0 2px 8px rgba(0,0,0,0.05)" : "none",
                  whiteSpace: "pre-line",
                }}
              >
                {message.content}
              </div>
            </div>
          ))}
          <div ref={messagesEndRef} />
        </div>

        {/* Chat Input */}
        <div
          className="p-4 flex gap-3"
          style={{ background: "var(--white)", borderTop: "1px solid var(--neutral-200)" }}
        >
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyPress={handleKeyPress}
            placeholder="Type your message..."
            className="flex-1 py-3.5 px-4 rounded-[25px] text-[0.95rem] transition-all focus:outline-none"
            style={{
              border: "2px solid var(--neutral-200)",
              fontFamily: "var(--font-sans)",
            }}
            data-testid="input-chat-message"
          />
          <button
            onClick={sendMessage}
            className="w-12 h-12 rounded-full flex items-center justify-center cursor-pointer transition-all hover:scale-105 border-none text-white"
            style={{
              background: "linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)",
            }}
            data-testid="button-send-message"
          >
            <Send className="w-5 h-5" />
          </button>
        </div>
      </div>
    </>
  );
}
