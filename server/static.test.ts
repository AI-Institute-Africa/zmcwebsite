// @vitest-environment node
import { describe, it, expect, vi, beforeEach } from "vitest";

const { existsSyncMock } = vi.hoisted(() => ({ existsSyncMock: vi.fn() }));
vi.mock("fs", () => ({ default: { existsSync: existsSyncMock }, existsSync: existsSyncMock }));

import { serveStatic } from "./static";

const DIST_PATH = "/home/zmc/app/public";

function fakeApp() {
  return { use: vi.fn() } as any;
}

describe("serveStatic", () => {
  beforeEach(() => {
    existsSyncMock.mockReset();
  });

  it("throws when the build directory is missing", () => {
    existsSyncMock.mockReturnValue(false);
    expect(() => serveStatic(fakeApp())).toThrow(/Could not find the build directory/);
  });

  it("checks the expected production build path", () => {
    existsSyncMock.mockReturnValue(false);
    try {
      serveStatic(fakeApp());
    } catch {
      /* expected */
    }
    expect(existsSyncMock).toHaveBeenCalledWith(DIST_PATH);
  });

  it("registers a static middleware and a catch-all route when the dir exists", () => {
    existsSyncMock.mockReturnValue(true);
    const app = fakeApp();
    serveStatic(app);

    expect(app.use).toHaveBeenCalledTimes(2);
    expect(typeof app.use.mock.calls[0][0]).toBe("function");
    expect(app.use.mock.calls[1][0]).toBe("*");
    expect(typeof app.use.mock.calls[1][1]).toBe("function");
  });

  it("serves index.html from the build directory for unknown routes", () => {
    existsSyncMock.mockReturnValue(true);
    const app = fakeApp();
    serveStatic(app);

    const handler = app.use.mock.calls[1][1];
    const sendFile = vi.fn();
    handler({}, { sendFile });

    expect(sendFile).toHaveBeenCalledTimes(1);
    expect(sendFile.mock.calls[0][0]).toMatch(/index\.html$/);
  });
});
