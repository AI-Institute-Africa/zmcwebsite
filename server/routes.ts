import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { insertSubscriberSchema } from "@shared/schema";
import { fromZodError } from "zod-validation-error";

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {
  // put application routes here
  // prefix all routes with /api

  app.post("/api/subscribe", async (req, res) => {
    const parsed = insertSubscriberSchema.safeParse(req.body);
    if (!parsed.success) {
      return res
        .status(400)
        .json({ message: fromZodError(parsed.error).toString() });
    }

    const existing = await storage.getSubscriberByEmail(parsed.data.email);
    if (existing) {
      return res
        .status(200)
        .json({ alreadySubscribed: true, subscriber: existing });
    }

    const subscriber = await storage.createSubscriber(parsed.data);
    return res.status(201).json({ alreadySubscribed: false, subscriber });
  });

  return httpServer;
}
