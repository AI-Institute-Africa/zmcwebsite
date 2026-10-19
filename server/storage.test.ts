// @vitest-environment node
import { describe, it, expect } from "vitest";
import { MemStorage } from "./storage";

describe("MemStorage", () => {
  it("creates a user with a generated id and retrieves it", async () => {
    const storage = new MemStorage();
    const user = await storage.createUser({ username: "alice", password: "secret" });
    expect(user.id).toBeTruthy();
    expect(user.username).toBe("alice");
    expect(await storage.getUser(user.id)).toEqual(user);
  });

  it("finds a user by username", async () => {
    const storage = new MemStorage();
    await storage.createUser({ username: "bob", password: "pw" });
    const found = await storage.getUserByUsername("bob");
    expect(found?.username).toBe("bob");
  });

  it("returns undefined for missing users", async () => {
    const storage = new MemStorage();
    expect(await storage.getUser("nope")).toBeUndefined();
    expect(await storage.getUserByUsername("nope")).toBeUndefined();
  });

  it("creates subscribers with unique generated ids", async () => {
    const storage = new MemStorage();
    const a = await storage.createSubscriber({ fullName: "A", email: "a@x.com" });
    const b = await storage.createSubscriber({ fullName: "B", email: "b@x.com" });
    expect(a.id).not.toBe(b.id);
  });

  it("looks up a subscriber by email case-insensitively", async () => {
    const storage = new MemStorage();
    await storage.createSubscriber({ fullName: "A", email: "Mixed@Example.com" });
    const found = await storage.getSubscriberByEmail("mixed@example.com");
    expect(found?.email).toBe("Mixed@Example.com");
  });

  it("returns undefined for an unknown subscriber email", async () => {
    const storage = new MemStorage();
    expect(await storage.getSubscriberByEmail("none@example.com")).toBeUndefined();
  });

  it("keeps each instance isolated", async () => {
    const a = new MemStorage();
    const b = new MemStorage();
    await a.createSubscriber({ fullName: "A", email: "a@x.com" });
    expect(await b.getSubscriberByEmail("a@x.com")).toBeUndefined();
  });
});
