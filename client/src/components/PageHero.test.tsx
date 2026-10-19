import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import PageHero from "./PageHero";

describe("PageHero", () => {
  it("renders the title and subtitle", () => {
    render(<PageHero title="Media Complaints" subtitle="Lodge a complaint" breadcrumbs={[]} />);
    expect(screen.getByRole("heading", { level: 1, name: "Media Complaints" })).toBeInTheDocument();
    expect(screen.getByText("Lodge a complaint")).toBeInTheDocument();
  });

  it("omits the subtitle when not provided", () => {
    render(<PageHero title="Only Title" breadcrumbs={[]} />);
    expect(screen.getByText("Only Title")).toBeInTheDocument();
  });

  it("renders breadcrumbs and separates them", () => {
    render(
      <PageHero
        title="T"
        breadcrumbs={[{ label: "Home", onClick: vi.fn() }, { label: "Media Centre" }, { label: "Press Releases" }]}
      />,
    );
    expect(screen.getByText("Home")).toBeInTheDocument();
    expect(screen.getByText("Media Centre")).toBeInTheDocument();
    expect(screen.getByText("Press Releases")).toBeInTheDocument();
    expect(screen.getAllByText("/")).toHaveLength(2);
  });

  it("calls the breadcrumb onClick handler", async () => {
    const user = userEvent.setup();
    const onClick = vi.fn();
    render(<PageHero title="T" breadcrumbs={[{ label: "Home", onClick }]} />);
    await user.click(screen.getByText("Home"));
    expect(onClick).toHaveBeenCalledOnce();
  });

  it("renders non-clickable breadcrumbs as plain text", () => {
    render(<PageHero title="T" breadcrumbs={[{ label: "Current Page" }]} />);
    expect(screen.getByText("Current Page").tagName).not.toBe("A");
  });
});
