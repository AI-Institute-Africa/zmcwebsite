import "@testing-library/jest-dom/vitest";
import { afterEach, vi } from "vitest";

afterEach(() => {
  vi.restoreAllMocks();
});

// jsdom does not implement several browser APIs that Radix/shadcn and our
// components rely on. Install lightweight stubs directly on globalThis (not
// via vi.stubGlobal, so they survive vi.restoreAllMocks between tests).
if (typeof window !== "undefined") {
  if (typeof (window as any).matchMedia !== "function") {
    Object.defineProperty(window, "matchMedia", {
      writable: true,
      configurable: true,
      value: (query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: () => {},
        removeListener: () => {},
        addEventListener: () => {},
        removeEventListener: () => {},
        dispatchEvent: () => false,
      }),
    });
  }

  if (typeof (globalThis as any).ResizeObserver !== "function") {
    (globalThis as any).ResizeObserver = class {
      observe() {}
      unobserve() {}
      disconnect() {}
    };
  }

  if (typeof (globalThis as any).IntersectionObserver !== "function") {
    (globalThis as any).IntersectionObserver = class {
      root = null;
      rootMargin = "";
      thresholds: number[] = [];
      observe() {}
      unobserve() {}
      disconnect() {}
      takeRecords() {
        return [];
      }
    };
  }

  if (typeof window.scrollTo !== "function") {
    window.scrollTo = (() => {}) as typeof window.scrollTo;
  }

  if (typeof Element !== "undefined" && !Element.prototype.scrollIntoView) {
    Element.prototype.scrollIntoView = () => {};
  }

  if (!URL.createObjectURL) {
    URL.createObjectURL = () => "blob:mock";
    URL.revokeObjectURL = () => {};
  }
}
