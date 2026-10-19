import { describe, it, expect, vi, beforeEach } from "vitest";

const docState = {
  text: [] as Array<{ text: string; x: number; y: number; options?: unknown }>,
  save: vi.fn(),
  setFont: vi.fn(),
  setFontSize: vi.fn(),
  setTextColor: vi.fn(),
  width: 842,
  height: 595,
};

vi.mock("jspdf", () => {
  class MockJsPDF {
    internal = {
      pageSize: {
        getWidth: () => docState.width,
        getHeight: () => docState.height,
      },
    };
    setFont = docState.setFont;
    setFontSize = docState.setFontSize;
    setTextColor = docState.setTextColor;
    text = (text: string, x: number, y: number, options?: unknown) => {
      docState.text.push({ text, x, y, options });
    };
    save = docState.save;
  }
  return { jsPDF: MockJsPDF };
});

const autoTableMock = vi.fn();
vi.mock("jspdf-autotable", () => ({
  autoTable: (...args: unknown[]) => autoTableMock(...args),
}));

import { exportTableToPdf, type PdfColumn } from "./pdfExport";

const columns: PdfColumn[] = [
  { header: "Name", key: "name" },
  { header: "Province", key: "province" },
];

describe("exportTableToPdf", () => {
  beforeEach(() => {
    docState.text = [];
    docState.save.mockClear();
    docState.setFont.mockClear();
    docState.setFontSize.mockClear();
    docState.setTextColor.mockClear();
    autoTableMock.mockClear();
  });

  it("writes the ZMC company heading, title and a footer", async () => {
    autoTableMock.mockImplementation((_doc: unknown, opts: any) => opts.didDrawPage?.());
    await exportTableToPdf({
      title: "Accredited Practitioners",
      columns,
      rows: [{ name: "Jane", province: "Harare" }],
      filename: "practitioners.pdf",
    });

    const texts = docState.text.map((t) => t.text);
    expect(texts[0]).toBe("Zimbabwe Media Commission");
    expect(texts[1]).toBe("Accredited Practitioners");
    expect(texts.some((t) => t.includes("Generated") && t.includes("zmc.org.zw"))).toBe(true);
    expect(docState.save).toHaveBeenCalledWith("practitioners.pdf");
  });

  it("maps rows to the configured column order", async () => {
    await exportTableToPdf({
      title: "T",
      columns,
      rows: [{ name: "Jane", province: "Harare", extra: "ignored" }],
      filename: "t.pdf",
    });
    const opts = autoTableMock.mock.calls[0][1];
    expect(opts.head).toEqual([["Name", "Province"]]);
    expect(opts.body).toEqual([["Jane", "Harare"]]);
  });

  it("fills missing cells with empty strings", async () => {
    await exportTableToPdf({
      title: "T",
      columns,
      rows: [{ name: "OnlyName" } as Record<string, string>],
      filename: "t.pdf",
    });
    const opts = autoTableMock.mock.calls[0][1];
    expect(opts.body).toEqual([["OnlyName", ""]]);
  });

  it("includes the subtitle when provided", async () => {
    await exportTableToPdf({
      title: "T",
      subtitle: "Registered media houses",
      columns,
      rows: [],
      filename: "t.pdf",
    });
    const texts = docState.text.map((t) => t.text);
    expect(texts).toContain("Registered media houses");
    const opts = autoTableMock.mock.calls[0][1];
    expect(opts.startY).toBe(90);
  });

  it("uses a default startY when the subtitle is absent", async () => {
    await exportTableToPdf({ title: "T", columns, rows: [], filename: "t.pdf" });
    expect(autoTableMock.mock.calls[0][1].startY).toBe(76);
  });
});
