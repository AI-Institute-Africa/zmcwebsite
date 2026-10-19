import { describe, it, expect, vi } from "vitest";
import { fireEvent, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import ContactPage from "./ContactPage";
import { SubscribeProvider } from "../components/SubscribeDialog";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({
  CONTACT_EMAIL: "info@zmc.org.zw",
}));

function setup() {
  return renderWithProviders(
    <SubscribeProvider>
      <ContactPage onNavigate={vi.fn()} />
    </SubscribeProvider>,
  );
}

describe("ContactPage", () => {
  it("renders the hero and head office details", () => {
    setup();
    expect(screen.getByRole("heading", { level: 1, name: "Contact Us" })).toBeInTheDocument();
    expect(screen.getByTestId("text-office-name")).toHaveTextContent("Head Office - Harare");
    expect(screen.getByTestId("text-office-address")).toHaveTextContent("108 Swan Drive");
    expect(screen.getByTestId("text-postal-address")).toHaveTextContent("P.O Box BE 33, Harare");
    expect(screen.getByTestId("text-switchboard-0")).toHaveTextContent("+263 242 253509/10");
    expect(screen.getByTestId("text-directline-0")).toHaveTextContent("+263 242 253463");
    expect(screen.getByTestId("text-hotline")).toHaveTextContent("0719 299 150");
    expect(screen.getByTestId("text-whatsapp")).toHaveTextContent("0719 299 150");
    expect(screen.getByTestId("text-email")).toHaveAttribute("href", "mailto:info@zmc.org.zw");
    expect(screen.getByTestId("text-hours")).toHaveTextContent("Mon - Fri");
  });

  it("renders the Bulawayo regional office and the map embed", () => {
    setup();
    expect(screen.getByTestId("card-office-bulawayo")).toHaveTextContent("+263 713 879 044");
    expect(screen.getByTestId("iframe-google-map")).toHaveAttribute("title", "Head Office - Harare Location");
  });

  it("shows a success message after submitting the form", async () => {
    const user = userEvent.setup();
    setup();

    await user.type(screen.getByTestId("input-contact-firstname"), "Jane");
    await user.type(screen.getByTestId("input-contact-lastname"), "Doe");
    await user.type(screen.getByTestId("input-contact-email"), "jane@example.com");
    await user.selectOptions(screen.getByTestId("select-subject"), "general");
    await user.type(screen.getByTestId("textarea-message"), "Hello ZMC");

    fireEvent.submit(screen.getByTestId("button-send-message").closest("form") as HTMLFormElement);

    expect(screen.getByText("Message Sent!")).toBeInTheDocument();
  });

  it("opens the subscribe dialog from the contact form", async () => {
    const user = userEvent.setup();
    setup();
    await user.click(screen.getByTestId("button-subscribe-contact"));
    expect(await screen.findByTestId("dialog-subscribe")).toBeInTheDocument();
  });
});
