export interface PdfColumn {
  header: string;
  key: string;
}

export interface PdfExportOptions {
  title: string;
  subtitle?: string;
  columns: PdfColumn[];
  rows: Record<string, string>[];
  filename: string;
}

const PRIMARY: [number, number, number] = [46, 125, 86];

export async function exportTableToPdf({ title, subtitle, columns, rows, filename }: PdfExportOptions) {
  const [{ jsPDF }, { autoTable }] = await Promise.all([import("jspdf"), import("jspdf-autotable")]);

  const doc = new jsPDF({ orientation: "landscape", unit: "pt", format: "a4" });
  const pageWidth = doc.internal.pageSize.getWidth();
  const generatedOn = new Date().toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });

  doc.setFont("helvetica", "bold");
  doc.setFontSize(15);
  doc.setTextColor(PRIMARY[0], PRIMARY[1], PRIMARY[2]);
  doc.text("Zimbabwe Media Commission", pageWidth / 2, 40, { align: "center" });

  doc.setFontSize(11.5);
  doc.setTextColor(30, 30, 30);
  doc.text(title, pageWidth / 2, 60, { align: "center" });

  let startY = 76;
  if (subtitle) {
    doc.setFont("helvetica", "normal");
    doc.setFontSize(9);
    doc.setTextColor(120, 120, 120);
    doc.text(subtitle, pageWidth / 2, 76, { align: "center" });
    startY = 90;
  }

  autoTable(doc, {
    startY,
    head: [columns.map((c) => c.header)],
    body: rows.map((row) => columns.map((c) => row[c.key] ?? "")),
    styles: { fontSize: 8.5, cellPadding: 5, overflow: "linebreak", textColor: [45, 45, 45] },
    headStyles: { fillColor: PRIMARY, textColor: 255, fontStyle: "bold", halign: "left" },
    alternateRowStyles: { fillColor: [245, 249, 246] },
    margin: { left: 28, right: 28, bottom: 30 },
    didDrawPage: () => {
      const pageHeight = doc.internal.pageSize.getHeight();
      doc.setFont("helvetica", "normal");
      doc.setFontSize(8);
      doc.setTextColor(150, 150, 150);
      doc.text(
        `Generated ${generatedOn}  •  Zimbabwe Media Commission  •  zmc.org.zw`,
        pageWidth / 2,
        pageHeight - 15,
        { align: "center" },
      );
    },
  });

  doc.save(filename);
}
