# ZMC Systems — UI/UX Audit Report

Date: 2026-11-19 · Scope: Admin/staff portal (Laravel, `portal.zmc.org.zw`) and the public website (this repo).
Method: source review only (Blade/CSS on the server, React/Tailwind locally). Nothing was rendered or clicked, so every finding is **code-based, not visually confirmed**. The uxdesign.cc article returned HTTP 403, so findings are grouped under the standard principles that "58 rules" style lists cover (hierarchy, spacing, typography, contrast, consistency, feedback, accessibility, responsiveness), not numbered against the article.

## 1. Summary

The admin's problems are structural, not cosmetic. It has **two layout shells, five-plus navigation patterns, no shared tab component, and a type scale that starts at 9px**. Your two complaints trace to specific causes:

| Complaint | Root cause (evidence) |
|---|---|
| **No scroll bar** | The sidebar is `position:fixed; overflow:hidden` and only the inner list scrolls (`layouts/portal.blade.php` ~L211-322). That list uses the browser default scrollbar on top of a dark photo overlay, so it is nearly invisible. No page or content area has any scrollbar styling. Only the IT dashboard nav and a country dropdown do. |
| **No tabs** | `staff/pr/dashboard.blade.php` still contains six `.tab-pane` panels (notices, events, news, tenders, vacancies, downloads) but **no tab bar**. One "workspace" is chosen from the sidebar, so users can't switch in-page or see where they are. Officer, registrar, accounts, production, auditor and admin dashboards have **zero** tab bars (no `role="tablist"`, `nav-tabs` or `nav-pills`). Only the IT dashboard has real tabs. |

Overall: the **public site is in decent shape** (alt text present, one `h1` per page via `PageHero`, reduced-motion handled). The **admin needs a design-system pass** rather than page-by-page patching.

## 2. Admin portal findings

### Critical
1. **Invisible/inconsistent scrolling** — see table above. Fix: style `.sidebar-menu` scrollbar (thin, light thumb), add a bottom fade as a scroll cue, make the main content area the single scroll container, and apply one shared scrollbar style site-wide.
2. **No in-page tab navigation on role dashboards** — PR dashboard has orphaned `tab-pane` markup; other roles rely on sidebar-only navigation. Fix: one reusable Blade tab component (`<x-tabs>`) with `role="tablist"`, `aria-selected`, keyboard arrows, active state, and a URL-synced active tab (the PR page already has `$activeTab`).
3. **Two layout shells.** `layouts.portal` (sidebar + topbar) serves ~190 views, but `layouts.staff` (plain top navbar, no sidebar, no scroll handling, no `@media`) serves 16 views, e.g. all of `registration_compliance` (6) and `research_training` (4), plus some `accounts`, `auditor`, `it`, `pr` and `superadmin` pages. `staff/dashboards` uses a third (`layouts.app`). Staff switching pages see the whole chrome change.

### Major
4. **Type scale too small.** Tokens: `--font-size-xs: 9px`, `--font-size-sm: 10px`, base 13px. Sidebar links are 10px, table headers 11px, about 300 declarations at 8-10px across views. Below accessible reading size for dense admin work. Fix: floor at 12px, base 14px, sidebar 13px.
5. **Colour semantics broken.** `.btn-primary`/`.btn-success` are forced to black with yellow text and `.text-success`/`.bg-success` to yellow (`layouts/staff.blade.php`), so "success" is not green and a primary action looks like a status. Yellow on white (`.text-success` = `#eab308`) fails contrast (~2:1). Muted text `#64748b` is borderline on grey; dark mode uses `#94A3B8`.
6. **Accessibility gaps.** `sidebar_staff.blade.php` has 0 `aria-*`; no `aria-label` on the nav, no `aria-current` on the active link; 16 `outline:none` rules in layouts/views without a replacement focus style; the accreditation form has 82 `required` fields but 0 `aria-invalid`/`invalid-feedback` markers, so inline error feedback is missing or generic.
7. **Heavy inline styling and `!important`.** Sidebar section titles repeat the same long inline `style=` on every item; the layout carries ~2,000 lines of inline CSS. Hard to keep consistent and a main cause of "messy" drift.
8. **Dashboards are monoliths** (PR dashboard 156KB; officer 974 lines, accounts 1,202). Dense, little visual hierarchy, no `@media` rules in any role dashboard (officer, registrar, accounts, production, auditor, admin, appeals), so layout depends only on Bootstrap's grid.
9. **Floating "pilot notice"** (`.zmc-pilot-notice`, `position:fixed`, z-index 3000) overlays content at the top. Fix: make it a dismissible inline banner.

### Minor
10. Mixed patterns for empty/loading states (spinners in only 11 views; empty-state text in many, differently worded).
11. Bootstrap and icon fonts load from jsDelivr CDN; if the ZARNet link or CDN is slow, styling breaks. Self-host.
12. Housekeeping: 61 `.bak-*` files sit in `resources/views` (8.2MB). Not user-facing, but they show repeated hot-patching on the live server. Move to git and delete.
13. `onclick=` inline handlers in the sidebar (5) and several `<script>` blocks in the layout; prefer one deferred script.

## 3. Public website findings (this repo)

### Good
- All sampled `<img>` tags have `alt`; one `h1` per page via `components/PageHero.tsx`; `prefers-reduced-motion` handled (`index.css:351`); shadcn/Radix primitives give keyboard support for dialogs/menus.

### To improve
1. **No skip-to-content link** and `Header.tsx` has only one `aria-` attribute for a 921-line component, so keyboard and screen-reader users must tab through the whole header on every page.
2. **Heavy inline styles** (Header 71 `style={{}}`, ContactPage 65, AccreditationPage 55, RegistrationPage 54, AppealsPage 46) bypass the Tailwind/shadcn tokens, which makes spacing and colour inconsistent and breaks dark/light theming.
3. **32 `outline-none` uses** on pages/components (outside `ui/`); check each has a `focus-visible` ring.
4. **Tiny text:** six `text-[10px]/[11px]` uses; raise to 12px+.
5. **Form feedback:** Complaints/Appeals/Registration have little validation markup (3, 5 and 7 hits for `required`/`errors`/`aria-invalid`). Add inline field errors and `aria-describedby`.
6. **New "Testing phase" banner** (`Header.tsx:201`) is a 42px full-width bar between the top strip and nav on every page. Make it dismissible (remember in `localStorage`) and keep text at 13px.
7. `Header.tsx` is 921 lines. Splitting out the nav, language switcher and top strip would make mobile behaviour easier to test.

## 4. Recommended order of work

**Quick wins (1-2 days, low risk, admin):**
1. Style scrollbars (sidebar + main) and add the scroll fade.
2. Add a reusable tab component and restore tabs on the PR dashboard (use the existing `$activeTab`).
3. Raise font floor to 12px and sidebar links to 13px via the existing CSS variables.
4. Add `aria-label`/`aria-current` to the sidebar, restore focus rings, make the pilot notice dismissible.

**Medium (about a week):**
5. Migrate the 16 `layouts.staff` views (and `layouts.app` dashboard) to `layouts.portal`.
6. Fix colour semantics: real green for success, a distinct primary, contrast-checked yellow accent.
7. Replace the repeated inline sidebar styles with classes; self-host Bootstrap and icons.

**Larger:**
8. Break up the monolithic dashboards into partials/components with consistent cards, tables and empty/loading states.
9. Public site: skip link, move inline styles to Tailwind classes, add form validation feedback.

## 5. Caveats
- Not visually verified; no screenshots were taken (needs a staff login). A short screenshot pass of 4-5 key pages would confirm severity.
- The admin source lives on the server (`/var/www/portal.zmc.org.zw`), not in this repo, and the server's git history for it wasn't checked, so fixes there need a deploy path decided first (git remote or release folder).
