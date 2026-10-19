import { describe, it, expect } from "vitest";
import en, { type Translations } from "./en";
import sn from "./sn";
import nd from "./nd";

type AnyRecord = Record<string, any>;

/** Collect every leaf key path in a nested translation object. */
function leafPaths(obj: AnyRecord, prefix = ""): string[] {
  return Object.entries(obj).flatMap(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (value !== null && typeof value === "object" && !Array.isArray(value)) {
      return leafPaths(value, path);
    }
    return [path];
  });
}

function getAt(obj: AnyRecord, path: string): any {
  return path.split(".").reduce((acc, part) => acc?.[part], obj);
}

const enPaths = leafPaths(en).sort();

describe("translation completeness", () => {
  it.each([
    ["sn", sn],
    ["nd", nd],
  ])("%s exposes exactly the same keys as en", (_locale, translation) => {
    const paths = leafPaths(translation as unknown as AnyRecord).sort();
    expect(paths).toEqual(enPaths);
  });

  it.each([
    ["sn", sn],
    ["nd", nd],
  ])("%s has no empty string leaves", (_locale, translation) => {
    for (const path of leafPaths(translation as unknown as AnyRecord)) {
      const value = getAt(translation as unknown as AnyRecord, path);
      if (typeof value === "string") {
        expect(value.trim(), `empty value at ${path}`).not.toBe("");
      }
    }
  });

  it("array-valued keys have matching lengths across locales", () => {
    const arrayKeys = ["pages.about.mandateItems", "pages.about.values"];
    for (const key of arrayKeys) {
      const enArr = getAt(en as unknown as AnyRecord, key);
      const snArr = getAt(sn as unknown as AnyRecord, key);
      const ndArr = getAt(nd as unknown as AnyRecord, key);
      expect(Array.isArray(enArr)).toBe(true);
      expect(snArr).toHaveLength(enArr.length);
      expect(ndArr).toHaveLength(enArr.length);
    }
  });

  it("the English translations type is structurally compatible with sn and nd", () => {
    const _sn: Translations = sn;
    const _nd: Translations = nd;
    expect(_sn).toBeTruthy();
    expect(_nd).toBeTruthy();
  });

  it("en contains the complaints upload button copy used by the site", () => {
    expect(en.pages.complaints.uploadDocuments).toBe("Upload Complaint Documents");
    expect(en.pages.appeals.uploadDocuments).toBe("Upload Documents");
  });

  it("does not contain the legacy 'Read Full Release' copy anywhere", () => {
    const serialized = JSON.stringify({ en, sn, nd });
    expect(serialized).not.toMatch(/Read Full Release/i);
  });
});
