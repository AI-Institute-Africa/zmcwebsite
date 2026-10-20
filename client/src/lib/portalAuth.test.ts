import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import type { MockInstance } from "vitest";
import { openMediaHouseAuth, openMediaPractitionerAuth } from "./portalAuth";
import {
  MEDIA_HOUSE_SIGNUP_URL,
  MEDIA_PRACTITIONER_SIGNUP_URL,
  PORTAL_CHOOSE_URL,
} from "./api";

const STREAMS = [
  {
    name: "media house",
    open: openMediaHouseAuth,
    stream: "mass_media",
    signupUrl: MEDIA_HOUSE_SIGNUP_URL,
    popupName: "zmc-mass_media-auth",
  },
  {
    name: "media practitioner",
    open: openMediaPractitionerAuth,
    stream: "journalist",
    signupUrl: MEDIA_PRACTITIONER_SIGNUP_URL,
    popupName: "zmc-journalist-auth",
  },
] as const;

function submittedChoice(form: HTMLFormElement | null) {
  return {
    action: form?.action,
    method: form?.method,
    target: form?.target,
    portal: (form?.querySelector('input[name="portal"]') as HTMLInputElement | null)?.value,
  };
}

describe("portalAuth", () => {
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

  for (const stream of STREAMS) {
    describe(stream.name, () => {
      it("posts the portal choice in a new tab for login", () => {
        const openSpy = vi.spyOn(window, "open").mockReturnValue(null);

        stream.open("login");

        expect(openSpy).not.toHaveBeenCalled();
        expect(submittedChoice(submittedForm)).toEqual({
          action: PORTAL_CHOOSE_URL,
          method: "post",
          target: "_blank",
          portal: stream.stream,
        });
      });

      it("posts the choice in a popup then opens signup once the portal responds", () => {
        const popup = { closed: false, location: { href: "" } } as unknown as Window;
        const openSpy = vi.spyOn(window, "open").mockReturnValue(popup);

        stream.open("signup");

        expect(openSpy).toHaveBeenNthCalledWith(1, "about:blank", stream.popupName);
        expect(submittedChoice(submittedForm)).toMatchObject({
          action: PORTAL_CHOOSE_URL,
          target: stream.popupName,
          portal: stream.stream,
        });

        Object.defineProperty(popup, "location", {
          configurable: true,
          get() {
            throw new Error("SecurityError: cross-origin");
          },
        });
        vi.advanceTimersByTime(100);

        expect(openSpy).toHaveBeenNthCalledWith(2, stream.signupUrl, stream.popupName);
      });

      it("falls back to signup when the portal never responds", () => {
        const popup = { closed: false, location: { href: "" } } as unknown as Window;
        const openSpy = vi.spyOn(window, "open").mockReturnValue(popup);

        stream.open("signup");
        vi.advanceTimersByTime(5000);

        expect(openSpy).toHaveBeenNthCalledWith(2, stream.signupUrl, stream.popupName);
      });

      it("stops waiting when the popup is closed", () => {
        const popup = { closed: true, location: { href: "" } } as unknown as Window;
        const openSpy = vi.spyOn(window, "open").mockReturnValue(popup);

        stream.open("signup");
        vi.advanceTimersByTime(10000);

        expect(openSpy).toHaveBeenCalledTimes(1);
      });

      it("falls back to a new tab when the popup is blocked", () => {
        vi.spyOn(window, "open").mockReturnValue(null);

        stream.open("signup");

        expect(submittedChoice(submittedForm).target).toBe("_blank");
      });
    });
  }
});
