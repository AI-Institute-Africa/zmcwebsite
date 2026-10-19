import { MEDIA_HOUSE_SIGNUP_URL, PORTAL_CHOOSE_URL } from "./api";

const PORTAL_STREAM = "mass_media";
const POPUP_NAME = "zmc-media-house";
const POLL_INTERVAL_MS = 100;
const POLL_TIMEOUT_MS = 5000;

function submitPortalChoice(target: string): void {
  const form = document.createElement("form");
  form.method = "POST";
  form.action = PORTAL_CHOOSE_URL;
  form.target = target;
  form.style.display = "none";

  const field = document.createElement("input");
  field.type = "hidden";
  field.name = "portal";
  field.value = PORTAL_STREAM;
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

export function openMediaHouseAuth(destination: "login" | "signup"): void {
  if (destination === "login") {
    submitPortalChoice("_blank");
    return;
  }

  const popup = window.open("about:blank", POPUP_NAME);
  if (!popup) {
    submitPortalChoice("_blank");
    return;
  }

  submitPortalChoice(POPUP_NAME);

  let elapsed = 0;
  const timer = window.setInterval(() => {
    elapsed += POLL_INTERVAL_MS;

    if (popup.closed) {
      window.clearInterval(timer);
      return;
    }

    if (hasLeftAboutBlank(popup) || elapsed >= POLL_TIMEOUT_MS) {
      window.clearInterval(timer);
      window.open(MEDIA_HOUSE_SIGNUP_URL, POPUP_NAME);
    }
  }, POLL_INTERVAL_MS);
}
