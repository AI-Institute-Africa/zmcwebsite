import { describe, it, expect, vi } from "vitest";
import { screen } from "@testing-library/react";
import PhotosPage from "./PhotosPage";
import { renderWithProviders } from "../test/render";

describe("PhotosPage", () => {
  it("renders the empty gallery state when there is no media", async () => {
    renderWithProviders(<PhotosPage onNavigate={vi.fn()} />);

    expect(await screen.findByText("No media available yet")).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "Gallery" })).toBeInTheDocument();
  });
});
