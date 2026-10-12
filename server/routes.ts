import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { insertSubscriberSchema, complaintSchema, type ComplaintInput } from "@shared/schema";
import { fromZodError } from "zod-validation-error";

const SENDPULSE_URL = "https://api.sendpulse.com/smtp/emails";

// ---------------------------------------------------------------------------
// Email
// ---------------------------------------------------------------------------
async function sendComplaintEmail(complaint: ComplaintInput): Promise<void> {
  const apiKey = process.env.SENDPULSE_API_KEY;
  const fromEmail = process.env.MAIL_FROM;
  const recipient = process.env.COMPLAINT_RECIPIENT;

  if (!apiKey || !fromEmail || !recipient) {
    throw new Error("Mail service is not configured on the server.");
  }

  const attachmentNames = complaint.attachments?.length
    ? `\nSupporting Documents (${complaint.attachments.length}):\n${complaint.attachments
        .map((a) => `  - ${a.filename}`)
        .join("\n")}`
    : "";

  const text = [
    `A new media complaint has been submitted on the ZMC website.`,
    ``,
    `Submitted: ${new Date().toLocaleString()}`,
    `Complainant Name: ${complaint.name}`,
    `Complainant Email: ${complaint.email}`,
    `Evidence Link: ${complaint.evidenceLink || "None provided"}`,
    attachmentNames,
    ``,
    `Complaint Description:`,
    complaint.description,
  ].join("\n");

  const res = await fetch(SENDPULSE_URL, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      email: {
        subject: `Media Complaint from ${complaint.name}`,
        text,
        from: { email: fromEmail },
        to: [{ email: recipient }],
        attachments_binary: Object.fromEntries(
          (complaint.attachments || []).map((a) => [a.filename, a.content]),
        ),
      },
    }),
  });

  if (!res.ok) {
    const detail = (await res.text().catch(() => "")) || res.statusText;
    console.error(`SendPulse error ${res.status}: ${detail}`);
    throw new Error("The email provider rejected the request.");
  }
}

// ---------------------------------------------------------------------------
// Routes
// ---------------------------------------------------------------------------
export async function registerRoutes(httpServer: Server, app: Express): Promise<Server> {
  // ---- Newsletter ---------------------------------------------------------
  app.post("/api/subscribe", async (req, res) => {
    const parsed = insertSubscriberSchema.safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ message: fromZodError(parsed.error).toString() });
    }
    const existing = await storage.getSubscriberByEmail(parsed.data.email);
    if (existing) {
      return res.status(200).json({ alreadySubscribed: true, subscriber: existing });
    }
    const subscriber = await storage.createSubscriber(parsed.data);
    return res.status(201).json({ alreadySubscribed: false, subscriber });
  });

  // ---- Complaints ---------------------------------------------------------
  app.post("/api/complaints", async (req, res) => {
    const parsed = complaintSchema.safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ message: fromZodError(parsed.error).toString() });
    }
    try {
      await sendComplaintEmail(parsed.data);
      return res.status(200).json({ success: true });
    } catch (err: any) {
      console.error("Failed to send complaint email:", err);
      return res.status(500).json({ message: err.message || "Failed to send complaint email." });
    }
  });

  return httpServer;
}
