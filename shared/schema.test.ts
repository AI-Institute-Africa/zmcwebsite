import { describe, it, expect } from "vitest";
import { insertSubscriberSchema, complaintSchema } from "./schema";

describe("insertSubscriberSchema", () => {
  it("accepts a valid subscriber and trims values", () => {
    const result = insertSubscriberSchema.parse({
      fullName: "  Jane Doe  ",
      email: "  jane@example.com  ",
    });
    expect(result).toEqual({ fullName: "Jane Doe", email: "jane@example.com" });
  });

  it("rejects a name shorter than 2 characters", () => {
    const result = insertSubscriberSchema.safeParse({ fullName: "J", email: "j@e.com" });
    expect(result.success).toBe(false);
  });

  it("rejects an invalid email", () => {
    const result = insertSubscriberSchema.safeParse({ fullName: "Jane", email: "not-an-email" });
    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues.some((i) => /valid email/i.test(i.message))).toBe(true);
    }
  });

  it("requires both fields", () => {
    expect(insertSubscriberSchema.safeParse({}).success).toBe(false);
    expect(insertSubscriberSchema.safeParse({ fullName: "Jane" }).success).toBe(false);
  });
});

describe("complaintSchema", () => {
  const valid = {
    name: "Jane Doe",
    email: "jane@example.com",
    description: "This is a detailed complaint description.",
  };

  it("accepts a minimal valid complaint", () => {
    const result = complaintSchema.safeParse(valid);
    expect(result.success).toBe(true);
  });

  it("requires a name of at least 2 characters", () => {
    expect(complaintSchema.safeParse({ ...valid, name: "J" }).success).toBe(false);
  });

  it("requires a valid email", () => {
    expect(complaintSchema.safeParse({ ...valid, email: "bad" }).success).toBe(false);
  });

  it("requires a description of at least 10 characters", () => {
    expect(complaintSchema.safeParse({ ...valid, description: "short" }).success).toBe(false);
  });

  it("trims string fields", () => {
    const result = complaintSchema.parse({
      name: "  Jane  ",
      email: "  jane@example.com  ",
      description: "   A sufficiently long complaint.   ",
    });
    expect(result.name).toBe("Jane");
    expect(result.email).toBe("jane@example.com");
    expect(result.description).toBe("A sufficiently long complaint.");
  });

  it("allows an empty evidence link but rejects a non-URL", () => {
    expect(complaintSchema.safeParse({ ...valid, evidenceLink: "" }).success).toBe(true);
    expect(complaintSchema.safeParse({ ...valid, evidenceLink: "not a url" }).success).toBe(false);
    expect(complaintSchema.safeParse({ ...valid, evidenceLink: "https://example.com/x" }).success).toBe(true);
  });

  it("accepts attachments up to 5 files", () => {
    const attachments = Array.from({ length: 5 }, (_, i) => ({
      filename: `f${i}.pdf`,
      content: "base64",
    }));
    expect(complaintSchema.safeParse({ ...valid, attachments }).success).toBe(true);
  });

  it("rejects more than 5 attachments", () => {
    const attachments = Array.from({ length: 6 }, (_, i) => ({
      filename: `f${i}.pdf`,
      content: "base64",
    }));
    const result = complaintSchema.safeParse({ ...valid, attachments });
    expect(result.success).toBe(false);
    if (!result.success) {
      expect(result.error.issues.some((i) => /maximum of 5/i.test(i.message))).toBe(true);
    }
  });

  it("requires filename and content on each attachment", () => {
    expect(
      complaintSchema.safeParse({ ...valid, attachments: [{ filename: "f.pdf" }] }).success,
    ).toBe(false);
  });
});
