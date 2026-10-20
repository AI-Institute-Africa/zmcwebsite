import { MEDIA_HOUSE_SIGNUP_URL, MEDIA_PRACTITIONER_SIGNUP_URL, PORTAL_CHOOSE_URL } from "./api";

type PortalStream = "mass_media" | "journalist";
type AuthDestination = "login" | "signup";

const SIGNUP_URLS: Record<PortalStream, string> = {
  mass_media: MEDIA_HOUSE_SIGNUP_URL,
  journalist: MEDIA_PRACTITIONER_SIGNUP_URL,
};

const POLL_INTERVAL_MS = 100;
const POLL_TIMEOUT_MS = 5000;

function popupName(stream: PortalStream): string {
  return `zmc-${stream}-auth`;
}

function submitPortalChoice(target: string, stream: PortalStream): void {
  const form = document.createElement("form");
  form.method = "POST";
  form.action = PORTAL_CHOOSE_URL;
  form.target = target;
  form.style.display = "none";

  const field = document.createElement("input");
  field.type = "hidden";
  field.name = "portal";
  field.value = stream;
  form.appendChild(field);

  document.body.appendChild(form);
  form.submit();
  form.remove();
}

function hasLeftAboutBlank(popup: Window): boolean {
  try {
    return popup.location.href !== "";
  } catch {
    // Cross-origin access means the portal responded and the session is set.
    return true;
  }
}

function openPortalAuth(stream: PortalStream, destination: AuthDestination): void {
  if (destination === "login") {
    submitPortalChoice("_blank", stream);
    return;
  }

  const target = popupName(stream);
  const popup = window.open("about:blank", target);
  if (!popup) {
    submitPortalChoice("_blank", stream);
    return;
  }

  submitPortalChoice(target, stream);

  let elapsed = 0;
  const timer = window.setInterval(() => {
    elapsed += POLL_INTERVAL_MS;

    if (popup.closed) {
      window.clearInterval(timer);
      return;
    }

    if (hasLeftAboutBlank(popup) || elapsed >= POLL_TIMEOUT_MS) {
      window.clearInterval(timer);
      window.open(SIGNUP_URLS[stream], target);
    }
  }, POLL_INTERVAL_MS);
}

export function openMediaHouseAuth(destination: AuthDestination): void {
  openPortalAuth("mass_media", destination);
}

export function openMediaPractitionerAuth(destination: AuthDestination): void {
  openPortalAuth("journalist", destination);
}
