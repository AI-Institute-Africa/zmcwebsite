import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { LanguageProvider, useLanguage } from "./LanguageContext";
import en from "./en";
import sn from "./sn";
import nd from "./nd";

function Probe() {
  const { language, setLanguage, t } = useLanguage();
  return (
    <div>
      <span data-testid="lang">{language}</span>
      <span data-testid="home">{t.nav.home}</span>
      <button onClick={() => setLanguage("sn")}>to-sn</button>
      <button onClick={() => setLanguage("nd")}>to-nd</button>
      <button onClick={() => setLanguage("en")}>to-en</button>
    </div>
  );
}

function renderProbe() {
  return render(
    <LanguageProvider>
      <Probe />
    </LanguageProvider>,
  );
}

describe("LanguageContext", () => {
  it("defaults to English translations", () => {
    renderProbe();
    expect(screen.getByTestId("lang")).toHaveTextContent("en");
    expect(screen.getByTestId("home")).toHaveTextContent(en.nav.home);
  });

  it("switches to Shona", async () => {
    const user = userEvent.setup();
    renderProbe();
    await user.click(screen.getByText("to-sn"));
    expect(screen.getByTestId("lang")).toHaveTextContent("sn");
    expect(screen.getByTestId("home")).toHaveTextContent(sn.nav.home);
  });

  it("switches to Ndebele", async () => {
    const user = userEvent.setup();
    renderProbe();
    await user.click(screen.getByText("to-nd"));
    expect(screen.getByTestId("lang")).toHaveTextContent("nd");
    expect(screen.getByTestId("home")).toHaveTextContent(nd.nav.home);
  });

  it("can switch back to English", async () => {
    const user = userEvent.setup();
    renderProbe();
    await user.click(screen.getByText("to-sn"));
    await user.click(screen.getByText("to-en"));
    expect(screen.getByTestId("lang")).toHaveTextContent("en");
    expect(screen.getByTestId("home")).toHaveTextContent(en.nav.home);
  });

  it("returns English defaults when used without a provider", () => {
    render(<Probe />);
    expect(screen.getByTestId("lang")).toHaveTextContent("en");
    expect(screen.getByTestId("home")).toHaveTextContent(en.nav.home);
  });
});
