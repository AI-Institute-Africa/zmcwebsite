import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import MediaHubPage from "./MediaHubPage";
import { renderWithProviders } from "../test/render";

vi.mock("../lib/api", () => ({ TRAINING_HUB_URL: "https://traininghub.zmc.org.zw" }));

describe("MediaHubPage", () => {
  it("renders the training hub information and register link", () => {
    renderWithProviders(<MediaHubPage onNavigate={vi.fn()} />);

    expect(screen.getByRole("heading", { name: "About the Media Hub" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "How to Register" })).toBeInTheDocument();
    expect(screen.getByTestId("button-register-training-hub")).toHaveAttribute(
      "href",
      "https://traininghub.zmc.org.zw",
    );
  });
});
