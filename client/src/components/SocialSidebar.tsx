import { Facebook, Instagram, Youtube, MessageCircle } from "lucide-react";

export default function SocialSidebar() {
  const socials = [
    { icon: Facebook, href: "https://www.facebook.com/zimbabwemediacommission", bg: "#1877f2", label: "Facebook" },
    { icon: null, href: "https://x.com/Zim_Media_Com", bg: "#000000", label: "X", isX: true },
    { icon: Instagram, href: "https://www.instagram.com/zim_media_com/", bg: "linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)", label: "Instagram" },
    { icon: Youtube, href: "https://www.youtube.com/results?search_query=zimbabwe+media+commission", bg: "#ff0000", label: "YouTube" },
    { icon: MessageCircle, href: "https://wa.me/2632422535509", bg: "#25d366", label: "WhatsApp" },
  ];

  return (
    <div className="fixed left-0 top-1/2 -translate-y-1/2 flex flex-col z-[1400] hidden md:flex">
      {socials.map((social, index) => (
        <a
          key={index}
          href={social.href}
          target="_blank"
          rel="noopener noreferrer"
          className="w-[45px] h-[45px] flex items-center justify-center text-white transition-all hover:w-[55px] hover:pl-2.5"
          style={{ background: social.bg }}
          title={social.label}
          data-testid={`social-sidebar-${social.label.toLowerCase()}`}
        >
          {social.isX ? (
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
          ) : social.icon ? (
            <social.icon className="w-5 h-5" />
          ) : null}
        </a>
      ))}
    </div>
  );
}
