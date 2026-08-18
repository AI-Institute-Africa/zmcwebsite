import { Facebook, Instagram, Youtube, MessageCircle } from "lucide-react";

export default function SocialSidebar() {
  const socials = [
    { icon: Facebook, href: "https://www.facebook.com/zimbabwemediacommission", bg: "#1877f2", label: "Facebook" },
    { icon: null, href: "https://x.com/Zim_Media_Com", bg: "#000000", label: "X", isX: true },
    { icon: Instagram, href: "https://www.instagram.com/zim_media_com/", bg: "linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)", label: "Instagram" },
    { icon: null, href: "https://www.tiktok.com/@zim.mediacommission?_r=1&_t=ZS-96xQqrloUvF", bg: "#000000", label: "TikTok", isTikTok: true },
    { icon: Youtube, href: "https://www.youtube.com/results?search_query=zimbabwe+media+commission", bg: "#ff0000", label: "YouTube" },
    { icon: MessageCircle, href: "https://wa.me/263719299150", bg: "#25d366", label: "WhatsApp" },
  ];

  return (
    <div className="fixed left-0 top-1/2 -translate-y-1/2 flex flex-col z-[1400] hidden md:flex rounded-r-lg overflow-hidden shadow-lg">
      {socials.map((social, index) => (
        <a
          key={index}
          href={social.href}
          target="_blank"
          rel="noopener noreferrer"
          className="w-[36px] h-[36px] flex items-center justify-center text-white transition-all hover:opacity-80"
          style={{ background: social.bg }}
          title={social.label}
          data-testid={`social-sidebar-${social.label.toLowerCase()}`}
        >
          {social.isX ? (
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
          ) : social.isTikTok ? (
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
              <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
            </svg>
          ) : social.icon ? (
            <social.icon className="w-4 h-4" />
          ) : null}
        </a>
      ))}
    </div>
  );
}
