import { sql } from "drizzle-orm";
import { pgTable, text, varchar } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

export const users = pgTable("users", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  username: text("username").notNull().unique(),
  password: text("password").notNull(),
});

export const insertUserSchema = createInsertSchema(users).pick({
  username: true,
  password: true,
});

export type InsertUser = z.infer<typeof insertUserSchema>;
export type User = typeof users.$inferSelect;

export const subscribers = pgTable("subscribers", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  fullName: text("full_name").notNull(),
  email: text("email").notNull().unique(),
});

export const insertSubscriberSchema = createInsertSchema(subscribers)
  .omit({ id: true })
  .extend({
    fullName: z.string().trim().min(2, "Please enter your full name"),
    email: z.string().trim().email("Please enter a valid email address"),
  });

export type InsertSubscriber = z.infer<typeof insertSubscriberSchema>;
export type Subscriber = typeof subscribers.$inferSelect;

export const complaintSchema = z.object({
  name: z.string().trim().min(2, "Please enter your full name"),
  email: z.string().trim().email("Please enter a valid email address"),
  description: z
    .string()
    .trim()
    .min(10, "Please provide a description of your complaint"),
  evidenceLink: z.union([z.literal(""), z.string().trim().url("Enter a valid URL")]).optional(),
  attachments: z
    .array(
      z.object({
        filename: z.string(),
        type: z.string().optional(),
        content: z.string(),
      }),
    )
    .max(5, "A maximum of 5 supporting documents is allowed")
    .optional(),
});

export type ComplaintInput = z.infer<typeof complaintSchema>;
