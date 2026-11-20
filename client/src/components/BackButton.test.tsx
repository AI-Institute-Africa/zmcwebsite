import { describe, it, expect, vi, afterEach } from "vitest";
import { act, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import BackButton, { shouldShowBack } from "./BackButton";
import { renderWithProviders } from "../test/render";

describe("shouldShowBack", () => {
  it("hides on the home page and on detail pages that have their own back link", () => {
    expect(shouldShowBack("/")).toBe(false);
    expect(shouldShowBack("/events/12")).toBe(false);
    expect(shouldShowBack("/notices/3")).toBe(false);
    expect(shouldShowBack("/tenders/9")).toBe(false);
    expect(shouldShowBack("/vacancies/4")).toBe(false);
    expect(shouldShowBack("/press-releases/7")).toBe(false);
  });

  it("shows on every other page", () => {
    expect(shouldShowBack("/about")).toBe(true);
    expect(shouldShowBack("/contact")).toBe(true);
    expect(shouldShowBack("/events")).toBe(true);
    expect(shouldShowBack("/does-not-exist")).toBe(true);
  });
});

describe("BackButton", () => {
  afterEach(() => vi.restoreAllMocks());

  it("is not rendered on the home page", () => {
    renderWithProviders(<BackButton />, { route: "/" });
    expect(screen.queryByTestId("button-back")).not.toBeInTheDocument();
  });

  it("is an accessible button on an inner page", () => {
    renderWithProviders(<BackButton />, { route: "/about" });
    const btn = screen.getByRole("button", { name: "Go back" });
    expect(btn).toBeInTheDocument();
    expect(btn).toHaveTextContent("Back");
  });

  it("goes to the home page when the visitor landed directly on the page", async () => {
    const back = vi.spyOn(window.history, "back").mockImplementation(() => {});
    vi.spyOn(window, "scrollTo").mockImplementation(() => {});
    const { history } = renderWithProviders(<BackButton />, { route: "/about" });
    await userEvent.click(screen.getByTestId("button-back"));
    expect(back).not.toHaveBeenCalled();
    expect(history[history.length - 1]).toBe("/");
  });

  it("steps back in browser history after moving between pages", async () => {
    const back = vi.spyOn(window.history, "back").mockImplementation(() => {});
    vi.spyOn(window.history, "length", "get").mockReturnValue(3);
    const { navigate } = renderWithProviders(<BackButton />, { route: "/about" });
    act(() => navigate("/contact"));
    await userEvent.click(screen.getByTestId("button-back"));
    expect(back).toHaveBeenCalledTimes(1);
  });
});
