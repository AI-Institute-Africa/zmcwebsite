import type { ReactElement, ReactNode } from "react";
import { render, type RenderResult } from "@testing-library/react";
import { Router } from "wouter";
import { memoryLocation } from "wouter/memory-location";
import { LanguageProvider } from "../i18n/LanguageContext";

interface RenderOptions {
  route?: string;
}

/**
 * Renders a component wrapped in the app's global providers plus a wouter
 * memory router so navigation hooks (useLocation/useParams/Link) work.
 * Returns the render result and the stub `history` so navigation can be
 * asserted.
 */
export function renderWithProviders(
  ui: ReactElement,
  { route = "/" }: RenderOptions = {},
): RenderResult & { history: string[]; navigate: (to: string) => void } {
  const location = memoryLocation({ path: route, record: true });

  const Wrapper = ({ children }: { children: ReactNode }) => (
    <LanguageProvider>
      <Router hook={location.hook}>{children}</Router>
    </LanguageProvider>
  );

  const result = render(ui, { wrapper: Wrapper });
  return Object.assign(result, {
    history: location.history,
    navigate: location.navigate,
  });
}

/** Simple provider wrapper for components that don't need routing. */
export function Providers({ children }: { children: ReactNode }) {
  return <LanguageProvider>{children}</LanguageProvider>;
}
