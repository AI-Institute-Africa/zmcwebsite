import { describe, it, expect, vi } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Chatbot from "./Chatbot";
import { TRAINING_HUB_URL } from "../lib/api";
import { renderWithProviders } from "../test/render";

const WAIT = { timeout: 4000 };

describe("Chatbot", () => {
  it("starts closed and shows the Media Hub banner", () => {
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    expect(screen.getByTestId("button-chatbot-toggle")).toHaveAttribute("aria-label", "Open chat assistant");
    const banner = screen.getByTestId("banner-media-hub");
    expect(banner).toHaveAttribute("href", TRAINING_HUB_URL);
  });

  it("opens the chat panel when the toggle is clicked", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    await user.click(screen.getByTestId("button-chatbot-toggle"));
    expect(screen.getByTestId("button-chatbot-toggle")).toHaveAttribute("aria-label", "Close chat assistant");
    expect(screen.getByText("ZMC Assistant")).toBeInTheDocument();
  });

  it("shows the category menu shortly after opening", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    await user.click(screen.getByTestId("button-chatbot-toggle"));
    await waitFor(() => expect(screen.getByTestId("category-accreditation")).toBeInTheDocument(), WAIT);
  });

  it("responds to a category selection", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    await user.click(screen.getByTestId("button-chatbot-toggle"));
    await waitFor(() => expect(screen.getByTestId("category-accreditation")).toBeInTheDocument(), WAIT);

    await user.click(screen.getByTestId("category-accreditation"));
    await waitFor(() => expect(screen.getByText(/Accreditation of journalists/)).toBeInTheDocument(), WAIT);
  });

  it("responds to a typed message", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    await user.click(screen.getByTestId("button-chatbot-toggle"));

    await user.type(screen.getByTestId("input-chat-message"), "hello");
    await user.click(screen.getByTestId("button-send-message"));
    await waitFor(
      () => expect(screen.getByText(/Hello! How can I help you today\?/)).toBeInTheDocument(),
      WAIT,
    );
  });

  it("gives a fallback answer for unrecognised input", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    await user.click(screen.getByTestId("button-chatbot-toggle"));
    await user.type(screen.getByTestId("input-chat-message"), "zzzzz");
    await user.click(screen.getByTestId("button-send-message"));
    await waitFor(
      () => expect(screen.getByText(/I can help with Accreditation/)).toBeInTheDocument(),
      WAIT,
    );
  });

  it("clears the chat back to the greeting", async () => {
    const user = userEvent.setup();
    renderWithProviders(<Chatbot onNavigate={vi.fn()} />);
    await user.click(screen.getByTestId("button-chatbot-toggle"));
    await user.type(screen.getByTestId("input-chat-message"), "hello");
    await user.click(screen.getByTestId("button-send-message"));
    await waitFor(() => expect(screen.getByText(/Hello! How can I help you today\?/)).toBeInTheDocument(), WAIT);

    await user.click(screen.getByTestId("button-chatbot-clear"));
    expect(screen.queryByText(/Hello! How can I help you today\?/)).not.toBeInTheDocument();
  });
});
