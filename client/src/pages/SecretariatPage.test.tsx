import { describe, it, expect, vi } from "vitest";
import { screen, waitFor } from "@testing-library/react";
import SecretariatPage from "./SecretariatPage";
import { renderWithProviders } from "../test/render";
import { portalApi } from "../lib/api";

describe("SecretariatPage", () => {
  it("renders profiles managed by the Portal and the organogram download", async () => {
    vi.spyOn(portalApi, "getWebsitePeople").mockResolvedValue({
      data: [
        { id: 1, name: "Rudo Moyo", position: "Executive Secretary", image_url: "/storage/website/people/executive.jpg" },
        { id: 2, name: "Tendai Ncube", position: "Principal Director" },
        { id: 3, name: "Nyasha Dube", position: "Director", department: "Media Development" },
        { id: 4, name: "Kudakwashe Banda", position: "Manager", department: "Communications and PR" },
      ],
    });

    renderWithProviders(<SecretariatPage onNavigate={vi.fn()} />);

    await waitFor(() => expect(screen.getByText("Rudo Moyo")).toBeInTheDocument());
    expect(screen.getByText("Tendai Ncube")).toBeInTheDocument();
    expect(screen.getByText("Nyasha Dube")).toBeInTheDocument();
    expect(screen.getByText("Kudakwashe Banda")).toBeInTheDocument();
    expect(screen.getByTestId("button-download-organogram")).toHaveAttribute(
      "href",
      "/documents/zmc-organogram.pdf",
    );
  });
});
