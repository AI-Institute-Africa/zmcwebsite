import { describe, it, expect, vi } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import CommissionPage from "./CommissionersPage";
import { renderWithProviders } from "../test/render";
import { portalApi } from "../lib/api";

describe("CommissionersPage", () => {
  it("renders profiles managed by the Portal", async () => {
    vi.spyOn(portalApi, "getWebsitePeople").mockResolvedValue({
      data: [
        { id: 1, name: "Alex Chair", position: "Chairperson", image_url: "/storage/website/people/chair.jpg" },
        { id: 2, name: "Tariro Deputy", position: "Deputy Chairperson" },
        { id: 3, name: "Sam Commissioner", position: "Commissioner" },
      ],
    });

    renderWithProviders(<CommissionPage onNavigate={vi.fn()} />);

    await waitFor(() => expect(screen.getByText("Alex Chair")).toBeInTheDocument());
    expect(screen.getByText("Tariro Deputy")).toBeInTheDocument();
    expect(screen.getByText("Sam Commissioner")).toBeInTheDocument();
    expect(screen.getByAltText("Alex Chair")).toHaveAttribute(
      "src",
      "https://portal.zmc.org.zw/storage/website/people/chair.jpg",
    );
  });
});
