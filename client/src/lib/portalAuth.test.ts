import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import type { MockInstance } from "vitest";
import { openMediaHouseAuth } from "./portalAuth";
import { MEDIA_HOUSE_SIGNUP_URL, PORTAL_CHOOSE_URL } from "./api";

const POPUP_NAME = "zmc-media-house";

function portalChoiceForm(form: HTMLFormElement | null) {
  return {
    action: form?.action,
    method: form?.method,
    target: form?.target,
    portal: (form?.querySelector('input[name="portal"]') as HTMLInputElement | null)?.value,
  };
}

describe("openMediaHouseAuth", () => {
  let submitSpy: MockInstance;
  let submittedForm: HTMLFormElement | null;

  beforeEach(() => {
    submittedForm = null;
    vi.useFakeTimers();
    submitSpy = vi
      .spyOn(HTMLFormElement.prototype, "submit")
      .mockImplementation(function (this: HTMLFormElement) {
        submittedForm = this;
      });
  });

  afterEach(() => {
    submitSpy.mockRestore();
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it("posts the mass media portal choice in a new tab for login", () => {
    const openSpy = vi.spyOn(window, "open").mockReturnValue(null);

    openMediaHouseAuth("login");

    expect(openSpy).not.toHaveBeenCalled();
    expect(portalChoiceForm(submittedForm)).toEqual({
      action: PORTAL_CHOOSE_URL,
      method: "post",
      target: "_blank",
      portal: "mass_media",
    });
  });

  it("posts the choice in a popup then opens media house signup once the portal responds", () => {
    const popup = { closed: false, location: { href: "" } } as unknown as Window;
    const openSpy = vi.spyOn(window, "open").mockReturnValue(popup);

    openMediaHouseAuth("signup");

    expect(openSpy).toHaveBeenNthCalledWith(1, "about:blank", POPUP_NAME);
    expect(portalChoiceForm(submittedForm)).toMatchObject({
      action: PORTAL_CHOOSE_URL,
      target: POPUP_NAME,
      portal: "mass_media",
    });

    Object.defineProperty(popup, "location", {
      configurable: true,
      get() {
        throw new Error("SecurityError: cross-origin");
      },
    });
    vi.advanceTimersByTime(100);

    expect(openSpy).toHaveBeenNthCalledWith(2, MEDIA_HOUSE_SIGNUP_URL, POPUP_NAME);
  });

  it("falls back to media house signup when the portal never responds", () => {
    const popup = { closed: false, location: { href: "" } } as unknown as Window;
    const openSpy = vi.spyOn(window, "open").mockReturnValue(popup);

    openMediaHouseAuth("signup");
    vi.advanceTimersByTime(5000);

    expect(openSpy).toHaveBeenNthCalledWith(2, MEDIA_HOUSE_SIGNUP_URL, POPUP_NAME);
  });

  it("stops waiting when the popup is closed", () => {
    const popup = { closed: true, location: { href: "" } } as unknown as Window;
    const openSpy = vi.spyOn(window, "open").mockReturnValue(popup);

    openMediaHouseAuth("signup");
    vi.advanceTimersByTime(10000);

    expect(openSpy).toHaveBeenCalledTimes(1);
  });

  it("falls back to a new tab when the popup is blocked", () => {
    vi.spyOn(window, "open").mockReturnValue(null);

    openMediaHouseAuth("signup");

    expect(portalChoiceForm(submittedForm).target).toBe("_blank");
  });
});
