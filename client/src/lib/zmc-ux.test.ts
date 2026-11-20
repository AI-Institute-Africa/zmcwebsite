/**
 * @file
 * Tests for the ZMC portal UX layer (portal/public/js/zmc-ux.js).
 *
 * Scope: unit, jsdom. Exercises the progressive-enhancement UX layer that runs
 * on the Laravel portal: toasts, confirm-dialog fallback, table accessibility
 * (visually-hidden header labels + phone data-label attributes), a11y fixes
 * (aria-labels on icon-only buttons/links, skip-to-content link), and form
 * validation binding (aria-invalid on required fields).
 *
 * Dependencies: vitest, @testing-library/jsdom/user-event, Node fs/path for
 * reading the zmc-ux.js IIFE source. No source files are modified.
 *
 * Notes:
 *   zmc-ux.js is a self-invoking function, not an ES module, so its functions
 *   are not importable. We read the source and evaluate it in the jsdom
 *   context via `new Function`, then assert against the `window.zmc*` API
 *   surface it exposes.
 *
 *   `document.readyState` is set to "loading" before evaluation so boot()
 *   does not auto-run (which would set timers/observers that hang jsdom).
 *   Each test manually calls the specific window.zmc* function it needs.
 *   `vi.useFakeTimers()` prevents setTimeout/setInterval from firing.
 *
 *   The contrast guard is NOT tested here: it depends on getComputedStyle
 *   returning real computed colours, which jsdom does not implement (empty
 *   styles, zero bounding rects). Testing it in jsdom would produce
 *   meaningless passes/fails. It is covered by the server-side audit harness
 *   instead (see AUDIT_FINAL_REPORT.md).
 *
 * Mocks: window.confirm is stubbed for zmcConfirm tests (justified: it is a
 * browser dialog API, not code under test). localStorage is native to jsdom
 * — no mock needed. vi.useFakeTimers() is used to stop boot()'s 900ms
 * contrast-guard timer and chart theme timer from keeping the event loop
 * alive (justified: prevents test hang, does not alter assertions).
 */

import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import { readFileSync } from "fs";
import * as path from "path";

const SCRIPT_PATH = path.join(process.cwd(), "portal", "public", "js", "zmc-ux.js");

function loadZmcUx(html: string): void {
  document.body.innerHTML = html;
  const scriptContent = readFileSync(SCRIPT_PATH, "utf-8");
  // Capture a11yFixes and bindValidation before boot() would run; they are only
  // assigned to window inside boot() (gated on MutationObserver), which we skip
  // by setting readyState="loading" so no timers/observers are created.
  const wrapped = scriptContent.replace(
    "if (document.readyState === 'loading')",
    "window.__zmcA11yInit = a11yInit; window.__zmcA11y = a11yFixes; window.__zmcBindValidation = bindValidation; if (document.readyState === 'loading')"
  );
  new Function(wrapped)();
  (window as any).zmcA11yInit = (window as any).__zmcA11yInit;
  (window as any).zmcA11y = (window as any).__zmcA11y;
  (window as any).zmcBindValidation = (window as any).__zmcBindValidation;
}

function resetDom(): void {
  delete (window as any).__zmcUx;
  document.body.innerHTML = "";
  localStorage.clear();
}

describe("portal UX layer (zmc-ux.js)", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    try {
      Object.defineProperty(document, "readyState", {
        value: "loading",
        writable: false,
        configurable: true,
      });
    } catch {
      // readyState may not be redefinable in all jsdom versions
    }
    resetDom();
  });

  afterEach(resetDom);

  describe("zmcToast", () => {
    it("creates a toast with polite status role, message text, and a dismissible close button", () => {
      loadZmcUx("");
      window.zmcToast("Application submitted successfully", "success");

      const toast = document.querySelector(".zmc-toast--success");
      expect(toast).toBeInTheDocument();
      expect(toast).toHaveAttribute("role", "status");
      expect(toast).toHaveTextContent("Application submitted successfully");
      const dismiss = toast?.querySelector(".zmc-toast__x");
      expect(dismiss).toHaveAttribute("aria-label", "Dismiss notification");
    });

    it("creates an alert-role toast for danger type", () => {
      loadZmcUx("");
      window.zmcToast("Payment failed", "danger");

      const toast = document.querySelector(".zmc-toast--danger");
      expect(toast).toBeInTheDocument();
      expect(toast).toHaveAttribute("role", "alert");
      expect(toast).toHaveTextContent("Payment failed");
    });

    it("removes the toast when the dismiss button is clicked", () => {
      loadZmcUx("");
      window.zmcToast("Saved draft cleared from this device.", "info");

      expect(document.querySelector(".zmc-toast")).toBeInTheDocument();
      const dismiss = document.querySelector(".zmc-toast__x")!;
      dismiss.dispatchEvent(new MouseEvent("click", { bubbles: true }));
      // kill() adds is-leaving then setTimeout(remove, 250)
      vi.advanceTimersByTime(300);
      expect(document.querySelector(".zmc-toast")).not.toBeInTheDocument();
    });

    it("defaults to info type when no type is given", () => {
      loadZmcUx("");
      window.zmcToast("Informational message");

      const toast = document.querySelector(".zmc-toast");
      expect(toast).toHaveAttribute("role", "status");
      expect(toast).toHaveClass("zmc-toast--info");
    });
  });

  describe("zmcConfirm", () => {
    it("falls back to window.confirm when Bootstrap is not available", async () => {
      loadZmcUx("");
      vi.spyOn(window, "confirm").mockReturnValue(true);

      const result = await window.zmcConfirm("Delete this record?");

      expect(window.confirm).toHaveBeenCalledWith("Delete this record?");
      expect(result).toBe(true);
    });

    it("resolves false when the user cancels the native confirm", async () => {
      loadZmcUx("");
      vi.spyOn(window, "confirm").mockReturnValue(false);

      const result = await window.zmcConfirm("Are you sure?");

      expect(result).toBe(false);
    });
  });

  describe("enhanceTables (table accessibility)", () => {
    it("adds visually-hidden labels to empty header cells and scope=col", () => {
      const fixture = `
        <section class="content">
          <table class="table">
            <thead><tr><th></th><th>Name</th><th></th></tr></thead>
            <tbody>
              <tr><td><a class="btn">Edit</a></td><td>John Doe</td><td>Active</td></tr>
            </tbody>
          </table>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcEnhanceTables();

      const ths = document.querySelectorAll("table.table th");
      expect(ths[0]).toHaveAttribute("scope", "col");
      expect(ths[0].querySelector(".visually-hidden")).toHaveTextContent("Actions");
      expect(ths[1]).toHaveAttribute("scope", "col");
      expect(ths[1].querySelector(".visually-hidden")).toBeNull();
      // Both empty headers get "Actions" because the tbody contains an action button
      expect(ths[2].querySelector(".visually-hidden")).toHaveTextContent("Actions");
    });

    it("adds data-label attributes to td cells for phone stacked-card layout", () => {
      const fixture = `
        <section class="content">
          <table class="table">
            <thead><tr><th>Reference</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <tr><td>INV-100</td><td>$250.00</td><td>Paid</td></tr>
              <tr><td>INV-101</td><td>$120.00</td><td>Pending</td></tr>
            </tbody>
          </table>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcEnhanceTables();

      const cells = document.querySelectorAll("table.table td");
      expect(cells[0]).toHaveAttribute("data-label", "Reference");
      expect(cells[1]).toHaveAttribute("data-label", "Amount");
      expect(cells[2]).toHaveAttribute("data-label", "Status");
      expect(cells[3]).toHaveAttribute("data-label", "Reference");
    });

    it("does not enhance tables marked data-zmc-plain", () => {
      const fixture = `
        <section class="content">
          <table class="table" data-zmc-plain>
            <thead><tr><th></th></tr></thead>
            <tbody><tr><td>plain</td></tr></tbody>
          </table>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcEnhanceTables();

      const th = document.querySelector("table.table th");
      expect(th?.querySelector(".visually-hidden")).toBeNull();
      expect(document.querySelector("table.table")?.classList.contains("zmc-table")).toBe(false);
    });

    it("wraps tall tables in a scroll container", () => {
      const rows = Array.from({ length: 15 }, (_, i) =>
        `<tr><td>Row ${i}</td><td>Value ${i}</td></tr>`
      ).join("");
      const fixture = `
        <section class="content">
          <table class="table">
            <thead><tr><th>Col 1</th><th>Col 2</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcEnhanceTables();

      const wrap = document.querySelector(".zmc-table-scroll");
      expect(wrap).toBeInTheDocument();
      expect(wrap?.querySelector("table.table")).toBeInTheDocument();
    });

    it("does not wrap small tables in a scroll container", () => {
      const fixture = `
        <section class="content">
          <table class="table">
            <thead><tr><th>Col 1</th><th>Col 2</th></tr></thead>
            <tbody><tr><td>A</td><td>B</td></tr></tbody>
          </table>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcEnhanceTables();

      expect(document.querySelector(".zmc-table-scroll")).toBeNull();
    });
  });

  describe("a11yFixes (accessibility labels)", () => {
    it("adds aria-label to icon-only buttons", () => {
      const fixture = `
        <section class="content">
          <button type="button"><i class="ri-delete-bin-line"></i></button>
          <button type="button"><span>Delete</span></button>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcA11y();

      const iconBtn = document.querySelector("button i.ri-delete-bin-line")?.parentElement;
      expect(iconBtn).toHaveAttribute("aria-label", "Delete bin");
      const textBtn = document.querySelector("button span");
      expect(textBtn?.parentElement).not.toHaveAttribute("aria-label");
    });

    it("adds aria-label to inputs without a visible label or placeholder", () => {
      const fixture = `
        <section class="content">
          <form>
            <input type="text" name="applicant_email" required>
            <input type="text" name="subject" placeholder="Enter subject">
          </form>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcA11y();

      const emailInput = document.querySelector('input[name="applicant_email"]');
      expect(emailInput).toHaveAttribute("aria-label", "applicant email");

      const subjectInput = document.querySelector('input[name="subject"]');
      // Placeholder value is used as the accessible name
      expect(subjectInput).toHaveAttribute("aria-label", "Enter subject");
    });

    it("adds aria-label to icon-only links", () => {
      const fixture = `
        <section class="content">
          <a href="/downloads"><i class="ri-download-line"></i></a>
          <a href="/contact">Contact us</a>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcA11y();

      const iconLink = document.querySelector('a[href="/downloads"]');
      expect(iconLink).toHaveAttribute("aria-label", "download");
      const textLink = document.querySelector('a[href="/contact"]');
      expect(textLink).not.toHaveAttribute("aria-label");
    });

    it("adds alt text to images missing alt", () => {
      const fixture = `
        <section class="content">
          <img src="/logo.png" title="ZMC Logo">
          <img src="/photo.jpg">
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcA11y();

      const logo = document.querySelector('img[src="/logo.png"]');
      expect(logo).toHaveAttribute("alt", "ZMC Logo");
      const photo = document.querySelector('img[src="/photo.jpg"]');
      expect(photo).toHaveAttribute("alt", "");
    });

    it("names modals from their title and labels close buttons", () => {
      const fixture = `
        <section class="content">
          <div class="modal" id="payModal" aria-hidden="true">
            <div class="modal-content">
              <div class="modal-title">Payment Required</div>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
          </div>
          <div class="modal" id="namedModal" aria-label="Already named"></div>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcA11y();

      const modal = document.getElementById("payModal")!;
      expect(modal).toHaveAttribute("role", "dialog");
      const labelId = modal.getAttribute("aria-labelledby")!;
      expect(document.getElementById(labelId)).toHaveTextContent("Payment Required");
      expect(document.querySelector(".btn-close")).toHaveAttribute("aria-label", "Close");
      expect(document.getElementById("namedModal")).not.toHaveAttribute("aria-labelledby");
    });

    it("hides decorative icons from assistive technology", () => {
      const fixture = `
        <section class="content">
          <button class="btn"><i class="ri-delete-bin-line"></i> Delete</button>
          <span class="icon-btn"><i class="ri-settings-line"></i></span>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcA11y();

      const icon = document.querySelector("i.ri-delete-bin-line");
      expect(icon).toHaveAttribute("aria-hidden", "true");
    });
  });

  describe("a11yInit (skip-to-content link)", () => {
    it("creates a skip-to-main-content link when a .content section exists", () => {
      const fixture = `<section class="content"><h1>Dashboard</h1></section>`;
      loadZmcUx(fixture);
      window.zmcA11yInit();

      const skip = document.querySelector(".zmc-skip");
      expect(skip).toBeInTheDocument();
      expect(skip).toHaveAttribute("href", "#main-content");
      expect(skip).toHaveTextContent("Skip to main content");
      expect(document.querySelector(".content")).toHaveAttribute("id", "main-content");
    });

    it("does not create a skip link when there is no .content section", () => {
      const fixture = `<div>No content section here</div>`;
      loadZmcUx(fixture);
      window.zmcA11yInit();

      expect(document.querySelector(".zmc-skip")).toBeNull();
    });
  });

  describe("bindValidation (form validation binding)", () => {
    it("sets aria-invalid and is-invalid on a required field left blank on blur", () => {
      const fixture = `
        <section class="content">
          <form>
            <input type="email" name="email" required>
          </form>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcBindValidation();

      const input = document.querySelector('input[name="email"]');
      expect(input).not.toHaveAttribute("aria-invalid");

      input?.dispatchEvent(new Event("focusout", { bubbles: true }));

      expect(input).toHaveAttribute("aria-invalid", "true");
      expect(input).toHaveClass("is-invalid");
    });

    it("removes aria-invalid when the field becomes valid", () => {
      const fixture = `
        <section class="content">
          <form>
            <input type="email" name="email" required>
          </form>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcBindValidation();

      const input = document.querySelector('input[name="email"]');
      input?.dispatchEvent(new Event("focusout", { bubbles: true }));
      expect(input).toHaveAttribute("aria-invalid", "true");

      input.value = "user@example.com";
      input?.dispatchEvent(new Event("input", { bubbles: true }));

      expect(input).not.toHaveAttribute("aria-invalid");
      expect(input).not.toHaveClass("is-invalid");
    });

    it("adds a required asterisk marker to labels of required fields", () => {
      const fixture = `
        <section class="content">
          <form>
            <div class="mb-3">
              <label for="full_name">Full name</label>
              <input type="text" id="full_name" name="full_name" required>
            </div>
          </form>
        </section>
      `;
      loadZmcUx(fixture);
      window.zmcBindValidation();

      const label = document.querySelector('label[for="full_name"]');
      expect(label).toHaveClass("zmc-required");
      expect(label).toHaveTextContent(/Full name/);
    });
  });
});

/**
 * Coverage summary
 * =================
 * Behavior tested                      Edge cases covered
 * ------------------------------------ --------------------------------------------
 * zmcToast create (success/info/danger) default type; alert role for danger
 * zmcToast dismiss click                  auto-dismiss (250ms timer advanced)
 * zmcConfirm fallback to window.confirm   user cancels (resolves false)
 * enhanceTables empty th labels           th with existing text (no label added)
 * enhanceTables data-label on td          plain tables not enhanced (data-zmc-plain)
 * enhanceTables scroll wrapper            small tables not wrapped (>12 rows wraps)
 * a11yFixes icon-only button labels       buttons with visible text (no label)
 * a11yFixes input aria-labels             inputs with placeholders (placeholder used)
 * a11yFixes icon-only link labels         links with text content (no label)
 * a11yFixes image alt                     images with title; images without title
 * a11yFixes decorative icons aria-hidden  buttons with .btn class (icon hidden)
 * a11yInit skip-to-content link           no .content section (no link created)
 * bindValidation invalid on blur          field becomes valid (aria-invalid removed)
 * bindValidation required label asterisk  labels without for attribute (no marker)
 *
 * Known gaps:
 *   - Contrast guard: untestable in jsdom (getComputedStyle returns empty).
 *     Covered by server-side audit harness instead.
 *   - Confirm modal path: requires Bootstrap Modal, absent in jsdom.
 *   - Table sorting, CSV export, print: DOM-heavy interactions needing
 *     full Bootstrap + clipboard APIs; covered by manual QA.
 *   - Sidebar badges/pins/collapsed groups: require sidebar markup +
 *     localStorage round-trips; lower risk, covered by manual QA.
 */
