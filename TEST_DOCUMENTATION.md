# ZMC Website — Master Test Documentation

**System:** Zimbabwe Media Commission (ZMC) public website
**Repository:** `ZMC-Website-1` (React + Vite SPA client, Express server)
**Backends:** Local Express server (`/api/subscribe`, `/api/complaints`) + external ZMC Portal API (`https://portal.zmc.org.zw/api/v1`) + SendPulse (email) + Paynow (payments)
**Document version:** 1.0
**Status:** Living document — mirrors the current codebase

---

## Table of Contents

1. [Purpose & Scope](#1-purpose--scope)
2. [Test Strategy & Levels](#2-test-strategy--levels)
3. [Environments & Test Data](#3-environments--test-data)
4. [How to Run the Automated Suite](#4-how-to-run-the-automated-suite)
5. [Module Index](#5-module-index)
6. [M1 — Application Shell & Global Layout](#m1--application-shell--global-layout)
7. [M2 — Home Page](#m2--home-page)
8. [M3 — About & Governance](#m3--about--governance)
9. [M4 — Accreditation](#m4--accreditation)
10. [M5 — Registration](#m5--registration)
11. [M6 — Media Complaints](#m6--media-complaints)
12. [M7 — FOIA Appeals & Payments](#m7--foia-appeals--payments)
13. [M8 — Media Centre](#m8--media-centre)
14. [M9 — Opportunities (Vacancies, Tenders, Events)](#m9--opportunities-vacancies-tenders-events)
15. [M10 — Contact & Newsletter](#m10--contact--newsletter)
16. [M11 — ZMC Assistant (Chatbot)](#m11--zmc-assistant-chatbot)
17. [M12 — Backend API, Storage & Static Hosting](#m12--backend-api-storage--static-hosting)
18. [M13 — Shared Libraries](#m13--shared-libraries)
19. [M14 — Internationalisation (i18n)](#m14--internationalisation-i18n)
20. [M15 — Cross-Cutting Concerns](#m15--cross-cutting-concerns)
21. [Existing Automated Test Inventory](#16-existing-automated-test-inventory)
22. [Requirements Traceability Matrix](#17-requirements-traceability-matrix)
23. [Known Limitations / Risks Observed](#18-known-limitations--risks-observed)

---

## 1. Purpose & Scope

This document defines the complete test coverage required to verify **every feature and functionality** of the ZMC website. It is organised by functional module. Each module lists:

- **Features** — what the module does.
- **Test Cases** — numbered, with steps and expected results.
- **Test IDs / selectors** — `data-testid` hooks and exact visible strings for automation.
- **Edge cases & negative cases**.

**In scope**

- All 27 client routes/pages and 20 components.
- All client library modules (`api`, `utils`, `pdfExport`, `queryClient`).
- Express server routes, in-memory storage, and static hosting.
- i18n (English, Shona, Ndebele).
- Global chrome (header, footer, social sidebar, chatbot, toaster).

**Out of scope**

- The external Laravel portal UI at `portal.zmc.org.zw` (tested via API contract only).
- Infrastructure/edge proxy (ZCHPC) TLS and caching.
- Third-party SendPulse and Paynow dashboards.

---

## 2. Test Strategy & Levels

| Level | Scope | Tooling |
|---|---|---|
| **Unit** | Pure functions: `utils.cn`, `pdfExport.exportTableToPdf`, `api` helpers, zod schemas, `storage` | Vitest |
| **Component** | React components in isolation with mocked `portalApi` / `fetch` | Vitest + React Testing Library + jsdom |
| **Integration** | Page-level flows (forms, modals, routing) with mocked API | Vitest + RTL + wouter memory router |
| **API** | Express routes with a real HTTP server and mocked SendPulse | Vitest (node env) + supertest-style fetch |
| **E2E / Manual** | Full browser flows against staging/prod (portal + Paynow + SendPulse) | Manual checklist / Playwright (recommended) |
| **Non-functional** | Responsive, accessibility, performance, security | Manual + axe |

**Entry criteria:** build compiles (`npm run check`), dev server boots.
**Exit criteria:** all automated suites green; all manual/blocked cases signed off.

---

## 3. Environments & Test Data

| Environment | Client | API base | Notes |
|---|---|---|---|
| Local dev | `http://localhost:3000` (Vite) or `5000` | `https://portal.zmc.org.zw/api/v1` | `NODE_ENV` ≠ `production` → Vite middleware |
| Production | `https://zmc.org.zw` | `https://portal.zmc.org.zw/api/v1` | Served from `/home/zmc/app/public`, PM2 `zmc-website` |

**Key test data**

- Accreditation number format example: `ZMC/ACC/2026/0001`
- Complaint max attachment: **5** files, **10 MB** each
- Appeal default fee: **US$10.00**
- Contact email: `info@zmc.org.zw`; Phone `+263 242 253509/10`; WhatsApp `0719 299 150`
- Head office: `108 Swan Drive, Alexandra Park, Harare`
- External URLs: Portal `https://portal.zmc.org.zw/login`; Training Hub `https://traininghub.zmc.org.zw`; e-GP `https://egp.praz.org.zw/`
- Profile `?payment=paid | cancelled | canceled | <anything>` on `/appeals`

---

## 4. How to Run the Automated Suite

```bash
npm test               # vitest run (all suites)
npm run test:watch     # watch mode
npm run test:coverage  # coverage report
npm run check          # TypeScript type check (tsc)
```

- Config: `vitest.config.ts` — aliases `@`→`client/src`, `@shared`→`shared`, `@assets`→`attached_assets`; `jsdom` env; setup `client/src/test/setup.ts`.
- Helpers: `client/src/test/render.tsx` (`renderWithProviders`), `client/src/test/setup.ts` (DOM stubs).
- Server tests opt into node env with `// @vitest-environment node`.

---

## 5. Module Index

| ID | Module | Primary files |
|---|---|---|
| M1 | Application Shell & Global Layout | `App.tsx`, `Header`, `Footer`, `SocialSidebar`, `Toaster` |
| M2 | Home Page | `HomePage`, `Hero`, `NewsSlider`, `ServicesCards` |
| M3 | About & Governance | `AboutPage`, `CommissionersPage`, `SecretariatPage` |
| M4 | Accreditation | `AccreditationPage`, `AccreditedPractitionersPage`, `VerifyAccreditation` |
| M5 | Registration | `RegistrationPage`, `RegisteredMediaHousesPage`, `PublicDirectory` |
| M6 | Media Complaints | `ComplaintsPage` |
| M7 | FOIA Appeals & Payments | `AppealsPage` |
| M8 | Media Centre | `DownloadsPage`, `PressReleasesPage`, `NoticeDetailPage`, `MagazinePage`, `PhotosPage`, `MediaHubPage`, `NewsSlider` |
| M9 | Opportunities | `VacanciesPage`, `VacancyDetailPage`, `TendersPage`, `TenderDetailPage`, `EventsPage`, `EventDetailPage` |
| M10 | Contact & Newsletter | `ContactPage`, `SubscribeDialog` |
| M11 | Chatbot | `Chatbot` |
| M12 | Backend | `server/routes.ts`, `storage.ts`, `static.ts`, `index.ts` |
| M13 | Shared Libraries | `lib/api.ts`, `lib/utils.ts`, `lib/pdfExport.ts`, `lib/queryClient.ts`, `shared/schema.ts` |
| M14 | i18n | `i18n/en.ts`, `sn.ts`, `nd.ts`, `LanguageContext.tsx` |
| M15 | Cross-Cutting | Navigation, responsive, a11y, error states |

---

## M1 — Application Shell & Global Layout

### Features
- Provider composition: `QueryClientProvider` → `LanguageProvider` → `SubscribeProvider` → `AppContent`.
- Fixed `Header` with top action bar, logo, desktop dropdown nav, mobile hamburger + overlay menu.
- `Footer` with 4 columns (about/social, quick links, services, contact) and dynamic year.
- Fixed left `SocialSidebar` (desktop only) with Facebook, X, Instagram, TikTok, YouTube, WhatsApp.
- Floating `Chatbot` launcher + Media Hub promo banner.
- Global `Toaster` for toast notifications.
- Scroll animation handler adds `.visible` to `.scroll-animate` elements.
- Catch-all route renders `HomePage`.

### Test Cases

| ID | Case | Steps | Expected |
|---|---|---|---|
| GLB-001 | Providers mount | Render `<App/>` | No crash; header/footer/chatbot render |
| GLB-002 | Home route | Navigate `/` | `HomePage` renders (Hero present) |
| GLB-003 | Unknown route fallback | Navigate `/does-not-exist` | `HomePage` renders (catch-all) |
| GLB-004 | Desktop nav – Home link | Click `nav-home` | Route becomes `/` |
| GLB-005 | About dropdown open/close | Click `nav-about` | 3 items visible: About ZMC, Commissioners, Secretariat |
| GLB-006 | About dropdown item nav | Click `nav-about`, then `nav-commissioners` | Route `/commissioners` |
| GLB-007 | Services dropdown | Click `nav-services` | Items: Media Complaints, FOIA Appeals, Accreditation group, Registration group, Training |
| GLB-008 | Accreditation subgroup | Click `nav-services` → `nav-accreditation-group` | Reveals `nav-accreditation`, `nav-accredited-practitioners`, `nav-verify-accreditation` |
| GLB-009 | Registration subgroup | `nav-registration-group` | Reveals `nav-registration`, `nav-registered-media-houses` |
| GLB-010 | Opportunities dropdown | Click `nav-opportunities` | Items `nav-vacancies`, `nav-tenders`, `nav-training` |
| GLB-011 | Media Centre dropdown | Click `nav-media-centre` | Downloads, Photos, Press Releases, Magazine, Media Hub (+"New" badge) |
| GLB-012 | Outside click closes dropdowns | Open a dropdown, `mousedown` outside `header` | Dropdown closes |
| GLB-013 | Desktop quick actions | Render header | `button-registration-header` → `/registration`; `button-accreditation-header` calls onNavigate("accreditation"); `button-reports-documents-header` → `/downloads`; `button-complaints-header` → `/complaints`; `button-appeals-header` → `/appeals`; `button-portal` opens `PORTAL_LOGIN_URL` in new tab |
| GLB-014 | Top bar contact info | Render header | Phone `+263 242 253509/10`; mailto `info@zmc.org.zw` |
| GLB-015 | Logo home link | Click logo | Route `/` |
| GLB-016 | Mobile menu toggle | Click `button-mobile-menu` | Menu opens; body overflow `hidden`; icon switches X |
| GLB-017 | Mobile menu quick actions | Open mobile menu | Accreditation portal link, Complaints, `button-appeals-mobile` |
| GLB-018 | Mobile nav click closes menu | Open menu, click Home | Menu closes |
| GLB-019 | Footer columns | Render footer | zmcName, Quick Links, Our Services, Contact Us headers present |
| GLB-020 | Footer social links | Render footer | `social-link-0..3` present with correct hrefs |
| GLB-021 | Footer quick links | Render footer | `footer-link-about/accreditation/registration/downloads/contact` |
| GLB-022 | Footer services | Render footer | `footer-service-accreditation/registration/complaints/appeals/events` |
| GLB-023 | Footer copyright year | Render footer | Shows current year `© {year}` |
| GLB-024 | Social sidebar | Render | `social-sidebar-facebook/x/instagram/tiktok/youtube/whatsapp` with correct hrefs |
| GLB-025 | Toaster in DOM | Render app | Toaster region present (empty) |
| GLB-026 | Scroll animation class | Trigger `scroll` | `.scroll-animate` elements below viewport gain `.visible` |

### Selectors
`nav-home`, `nav-about`, `nav-services`, `nav-opportunities`, `nav-media-centre`, `nav-{path}`, `button-mobile-menu`, `button-registration-header`, `button-accreditation-header`, `button-reports-documents-header`, `button-complaints-header`, `button-appeals-header`, `button-appeals-mobile`, `button-portal`, `button-download-organogram`, `social-link-{i}` (footer), `social-sidebar-{label}`, `footer-link-{path}`, `footer-service-{path}`.

---

## M2 — Home Page

Composition: `<Hero/> + <NewsSlider/> + <ServicesCards/>`.

### M2.1 Hero

**Features:** full-viewport hero with background image; headline/subtitle; CTAs; two stat sections.

| ID | Case | Expected |
|---|---|---|
| HOME-001 | Renders headline | `t.hero.welcomeTo` + `t.hero.title` (Zimbabwe Media Commission) |
| HOME-002 | Subtitle | `t.hero.subtitle` present |
| HOME-003 | LEARN MORE CTA | `button-learn-more` → `onNavigate("about")` |
| HOME-004 | GET ACCREDITED CTA | `button-get-accredited` href = `PORTAL_LOGIN_URL`, `target=_blank` |
| HOME-005 | Quick stats panel | "Quick Stats" heading; accredited + registered counts from `portalApi.getHomeStats()`; 20+, 100% static |
| HOME-006 | Stats strip | `stat-box-0..3`; accredited, registered media houses and complaints resolved from `getHomeStats()`; 50+ training static |

> Note: number `500+` appears in both the quick-stats panel and the stats strip → use `getAllByText("500+")` in automation.

### M2.2 NewsSlider

**Features:** fetches latest 8 news items (`portalApi.getNews(1,8)`); auto-advance every 5s; prev/next; dots; click-through to `/press-releases/:id`; category badge; excerpt fallback from body; image handling.

| ID | Case | Expected |
|---|---|---|
| HOME-010 | Loading state | `Loading latest news...` with spinner |
| HOME-011 | Loads slides | Titles render; `slide-click-{index}` present |
| HOME-012 | Category badge | Badge text = `notice_category \|\| category \|\| news_category`; falls back to `t.news.announcement` when absent |
| HOME-013 | No legacy text | "Read Full Release" **must not** appear |
| HOME-014 | Excerpt fallback | Missing `excerpt` derives from `body` with tags stripped, truncated ~220 chars + `…` |
| HOME-015 | Navigate on slide click | Click `slide-click-1` → route `/press-releases/{id}` |
| HOME-016 | Next / Prev | `button-next-slide` / `button-prev-slide` advance/wrap index |
| HOME-017 | Dots | `slide-dot-{i}` selects slide |
| HOME-018 | Auto-advance | After 5s active slide increments (use fake timers) |
| HOME-019 | Empty state | `getNews` empty → "No news available" |
| HOME-020 | Error state | `getNews` rejects → "Something went wrong" + error message |
| HOME-021 | Accepts bare array response | Response as array renders slides |
| HOME-022 | Image URL | `image_path` mapped through `portalAssetUrl` |

### M2.3 ServicesCards

**Features:** 5 service cards; external portal cards (Accreditation, Registration) open portal in new tab; internal cards navigate.

| ID | Case | Expected |
|---|---|---|
| HOME-030 | Renders 5 cards | `service-card-accreditation/registration/complaints/appeals/downloads` |
| HOME-031 | Accreditation opens portal | Click card → `window.open(PORTAL_LOGIN_URL)` |
| HOME-032 | Registration opens portal | Click card → `window.open(PORTAL_LOGIN_URL)` |
| HOME-033 | Complaints navigates | Click card → `onNavigate("complaints")` |
| HOME-034 | Appeals navigates | Click card → `onNavigate("appeals")` |
| HOME-035 | Downloads navigates | Click card → `onNavigate("downloads")` |
| HOME-036 | Titles/actions | Card titles & action labels from `t.services.*` |

---

## M3 — About & Governance

### M3.1 AboutPage (`/about`) — static

| ID | Case | Expected |
|---|---|---|
| ABOUT-001 | Hero | `h1` "About ZMC"; subtitle; breadcrumbs Home / About ZMC |
| ABOUT-002 | Sections | Purpose & General Mandate; Functions of the ZMC; Our Strategic Objective; Want to Learn More? |
| ABOUT-003 | 10 function cards | Exact titles (Uphold Media Freedom … Research & Reform) |
| ABOUT-004 | Get Accredited CTA | `button-about-accreditation` href = `PORTAL_LOGIN_URL`, `target=_blank` |
| ABOUT-005 | Contact CTA | `button-about-contact` → `onNavigate("contact")` |

### M3.2 CommissionersPage (`/commissioners`) — static

| ID | Case | Expected |
|---|---|---|
| COMM-001 | Hero | "Board of Commissioners" + subtitle |
| COMM-002 | Constitutional Mandate panel | Present with Section 249 text |
| COMM-003 | Cards | `card-commissioner-chairperson`, `card-commissioner-deputy-chairperson`, and 7× `card-commissioner-commissioner` |
| COMM-004 | Placeholders | No names rendered; "photos and names will be published" note |
| COMM-005 | Our Commitment | Closing panel present |

### M3.3 SecretariatPage (`/secretariat`) — static + zoom modal

| ID | Case | Expected |
|---|---|---|
| SEC-001 | Hero | "Secretariat" + subtitle |
| SEC-002 | Sections | Our Team; Directors; Managers & Senior Staff; Our Commitment |
| SEC-003 | Head cards | Executive Secretary, Principal Director highlighted |
| SEC-004 | Avatar zoom open | Click avatar image → modal shows image + caption |
| SEC-005 | Zoom close (X) | `button-close-zoom` closes modal |
| SEC-006 | Zoom close (backdrop) | Click backdrop closes modal |
| SEC-007 | Organogram download | `button-download-organogram` href `/documents/zmc-organogram.pdf` with `download` |

---

## M4 — Accreditation

### M4.1 AccreditationPage (`/accreditation`) — info + fallback modal

**Features:** legal basis; local/foreign requirements; 4-step process; two CTA cards that open the media practitioner portal auth in a new tab; a fallback application modal (unreachable since the portal URL is always set).

| ID | Case | Expected |
|---|---|---|
| ACC-001 | Hero | "Accreditation" + subtitle |
| ACC-002 | Requirements lists | Local (6) and Foreign (4) exact items |
| ACC-003 | Process steps | 4 steps: Gather Documents, Complete Form, Submit & Pay, Get Accredited |
| ACC-004 | NB fee note | `t.pages.accreditation.nbNote` present |
| ACC-005 | Apply Now opens portal | `button-apply-accreditation` → `openMediaPractitionerAuth("signup")` (posts `portal=journalist` to `/choose-portal`, then opens `/media-practitioner-signup`) |
| ACC-006 | Renew Now opens portal | `button-renew-accreditation` → `openMediaPractitionerAuth("login")` (posts `portal=journalist` to `/choose-portal`, lands on `/media-practitioner-login`) |
| ACC-007 | Modal (conditional) | With `PORTAL_URL` empty, clicking a card opens modal "Accreditation Application" |
| ACC-008 | Modal fields | `input-first-name`, `input-last-name`, `input-email`, `input-phone`, `select-category`, `input-employer` |
| ACC-009 | Modal submit | Valid submit → "Application Submitted!" then auto-close after 3s |
| ACC-010 | Modal cancel/close | `button-close-modal` closes; background click closes; body overflow restored |
| ACC-011 | Download PDF Form link | Navigates to `downloads` and closes modal |

### M4.2 AccreditedPractitionersPage (`/accredited-practitioners`) → `PublicDirectory variant="practitioners"`

| ID | Case | Expected |
|---|---|---|
| ACC-P-001 | Hero & breadcrumbs | "Accredited Practitioners"; Home / Accreditation / Accredited Practitioners |
| ACC-P-002 | Table columns | #, Full Name, Category, Media House, Accreditation Number, Approved On, Valid Until, Status |
| ACC-P-003 | Data load | `portalApi.getAccreditedPractitioners({page, per_page:24})` |
| ACC-P-004 | Loading | "Loading directory…" |
| ACC-P-005 | Error | "Directory unavailable" + message; `role=alert` |
| ACC-P-006 | Empty | "No records to display yet" |
| ACC-P-007 | Empty w/ search | "No matches found" / "Try a different name." |
| ACC-P-008 | Search debounce | Input `input-directory-search-practitioners` → 400ms → fetch with `search`, page reset 1 |
| ACC-P-009 | Status computation | `expires_at` past → "Expired"; else/absent → "Active" |
| ACC-P-010 | Pagination | `LastPage>1` → Previous/Next + "Page X of Y" |
| ACC-P-011 | Export PDF | `button-export-pdf-practitioners` → PDF "Accredited Media Practitioners"; disabled while exporting |
| ACC-012 | Export error | `text-export-error` shown on failure / "There are no records to export." |
| ACC-P-013 | Record count | `{total} record(s)` pill |
| ACC-P-014 | Row testids | `directory-row-{index}`, `directory-table-practitioners` |

### M4.3 VerifyAccreditationPage (`/verify-accreditation`) → `VerifyAccreditation`

| ID | Case | Expected |
|---|---|---|
| VER-001 | Hero & breadcrumbs | "Verify Accreditation"; Home / Accreditation / Verify Accreditation |
| VER-002 | Form | Input `input-verify-accreditation`; button `button-verify-accreditation` |
| VER-003 | Disabled submit | Empty input → button disabled |
| VER-004 | Verify call | Submits `portalApi.verifyAccreditation(number)` (trimmed) |
| VER-005 | Success | "Valid & Active Accreditation" + practitioner name, badges, fields |
| VER-006 | Not found | `status=not_found` → "No matching record found" |
| VER-007 | Invalid/inactive | `status=invalid` → "Not valid — record is not active" |
| VER-008 | Error | `role=alert` "Verification unavailable" |
| VER-009 | Verify another | "Verify another number" resets form |
| VER-010 | Field mapping | employment_type labels: employed/freelancer/student/unemployed |
| VER-011 | Date format | Valid Until formatted e.g. "1 January 2026" |

---

## M5 — Registration

### M5.1 RegistrationPage (`/registration`) — info + fallback modal

| ID | Case | Expected |
|---|---|---|
| REG-001 | Hero | "Registration" + subtitle |
| REG-002 | Requirements | Local (13) and Foreign (5) exact items |
| REG-003 | Process steps | 5 steps including "Confirm & Pay" |
| REG-004 | NB fee note | Present |
| REG-005 | Register Now opens portal | `button-new-registration` → `openMediaHouseAuth("signup")` (posts `portal=mass_media` to `/choose-portal`, then opens `/media-house-signup`) |
| REG-006 | Renew Now opens portal | `button-renew-registration` → `openMediaHouseAuth("login")` (posts `portal=mass_media` to `/choose-portal`, lands on `/media-house-login`) |
| REG-007 | Modal (conditional) | Title "Media Registration Application" |
| REG-008 | Modal fields | `input-company-name`, `select-media-type`, `input-contact-person`, `input-reg-email`, `input-reg-phone`, `input-address` |
| REG-009 | Modal submit | Success panel "Application Submitted!" then auto-close |
| REG-010 | Modal cancel/close | `button-close-reg-modal`; overlay; overflow restored |

### M5.2 RegisteredMediaHousesPage (`/registered-media-houses`) → `PublicDirectory variant="mediahouses"`

| ID | Case | Expected |
|---|---|---|
| RMH-001 | Hero & breadcrumbs | "Registered Media Houses"; Home / Registration / Registered Media Houses |
| RMH-002 | Table columns | #, Media House, Registration Number, Province, Approved On, Valid Until (no Status) |
| RMH-003 | Data load | `portalApi.getRegisteredMediaHouses({page, per_page:24})` |
| RMH-004 | Loading/error/empty | Same strings as M4.2 |
| RMH-005 | Search | `input-directory-search-mediahouses` debounced |
| RMH-006 | Pagination | Previous/Next + "Page X of Y" |
| RMH-007 | Export PDF | `button-export-pdf-mediahouses` |
| RMH-008 | Legacy list | `button-download-legacy-list` → PDF "Registered Media Houses — Legacy List", ignores active search filter |
| RMH-009 | Row testids | `directory-row-{index}`, `directory-table-mediahouses` |

---

## M6 — Media Complaints

**Route:** `/complaints` — `ComplaintsPage`
**Backend:** `portalApi.submitComplaint(FormData)` → `POST /api/v1/complaints`

### Features
- Media categories grid (Print, Radio, Television, Online).
- "Upload Complaint Documents" button opens a portal-rendered modal.
- Optional attachment (single) + optional evidence link; combined into the `message` field.
- 10 MB file size guard; 3.5s auto-close on success.

### Test Cases

| ID | Case | Steps | Expected |
|---|---|---|---|
| CMP-001 | Hero | Render | `h1` "Media Complaints"; breadcrumbs Home / Media Complaints |
| CMP-002 | Media categories | Render | Print Media, Radio, Television, Online Media |
| CMP-003 | Open modal | Click `button-lodge-complaint-top` | Modal heading "Upload Complaint Documents"; `body.overflow=hidden` |
| CMP-004 | Close (Cancel) | Click `button-cancel-complaint` | Modal closes; overflow restored |
| CMP-005 | Close (backdrop) | Click backdrop | Modal closes |
| CMP-006 | File > 10MB | Upload 11MB file | `text-file-error` "exceeds the 10MB size limit"; submit blocked |
| CMP-007 | Valid file accepted | Upload 1MB PDF | No file error |
| CMP-008 | Submit success | Fill name/email/description/evidence + file, click `button-submit-complaint` | `portalApi.submitComplaint` called once; FormData has `full_name, email, phone="", subject="Public Complaint", message` (+ evidence link appended), `attachment`; success "Documents Submitted!" |
| CMP-009 | Network error | Mock reject | `text-submit-error` shows error; modal stays |
| CMP-010 | API status:error | Resolve `{status:"error", message:"Rejected"}` | `text-submit-error` "Rejected" |
| CMP-011 | Required fields | Submit empty | Native required validation blocks; no API call |
| CMP-012 | Evidence link optional | Omit evidence | FormData message = description only |
| CMP-013 | Auto-close success | After success | Modal closes after 3.5s |

### Selectors
`button-lodge-complaint-top`, `button-cancel-complaint`, `input-complaint-name`, `input-complaint-email`, `input-complaint-description`, `input-complaint-file-upload`, `input-complaint-evidence-link`, `text-file-error`, `text-submit-error`, `button-submit-complaint`.

---

## M7 — FOIA Appeals & Payments

**Route:** `/appeals` — `AppealsPage`
**Backend:** `submitAppeal`, `initiateAppealPayment`, `uploadAppealPaymentProof`, `getAppealPaymentStatus` (portal).

### Features
- Two-step flow: **details → payment**; optional **proof of payment** upload; final **done** step.
- Payment status banner driven by `?payment=paid|cancelled|error`.
- Paynow redirect or graceful fallback message.
- Skip-payment path ("Continue without paying now").
- 10 MB file guards on both supporting document and proof of payment.

### Test Cases

| ID | Case | Expected |
|---|---|---|
| APP-001 | Hero | `h1` "FOIA Appeals"; breadcrumbs Home / FOIA Appeals |
| APP-002 | Download form link | `button-download-appeal-form` href `/downloads/foia-appeal-form.pdf` `download` |
| APP-003 | `?payment=paid` banner | `text-payment-banner` "your Paynow payment was received" (green) |
| APP-004 | `?payment=cancelled` banner | "could not confirm your payment" (amber) with contact email |
| APP-005 | `?payment=error` banner | Same failure banner |
| APP-006 | Query param cleanup | After mount, `payment` param removed from URL |
| APP-007 | Open modal | `button-lodge-appeal-top` → "Submit FOIA Appeal"; overflow hidden |
| APP-008 | Empty submit validation | "Please complete all required fields."; no API call |
| APP-009 | Missing document | Fill fields, no file → "Please attach at least one supporting document." |
| APP-010 | File > 10MB | `text-file-error-appeal` shown |
| APP-011 | Details submit success | `submitAppeal` called; FormData `full_name, email, phone, subject="FOIA Appeal - {entity}", message, attachment`; advances to payment; reference shown (`text-appeal-reference`) |
| APP-012 | Details submit error | Error message rendered, stays on details |
| APP-013 | Paynow redirect | `initiateAppealPayment` returns `redirectUrl` → `window.location.href = redirectUrl` |
| APP-014 | Paynow no URL | Returns `{message}` → `text-payment-error` shown |
| APP-015 | Paynow throws | `text-payment-error` shows error |
| APP-016 | Fee display | Header/fee shows `US$10.00` default or returned amount |
| APP-017 | Proof upload success | `uploadAppealPaymentProof` called with `appeal_id, payment_reference?, payment_proof`; step done; "Appeal Submitted!"; success copy; reference `text-done-reference` |
| APP-018 | Proof disabled | `button-upload-proof` disabled until a proof file is chosen |
| APP-019 | Proof > 10MB | `text-proof-error` shown |
| APP-020 | Proof error response | `{status:"error"}` → `text-proof-error` message |
| APP-021 | Skip payment | `button-skip-payment` → done step; "FOIA appeal has been received" copy |
| APP-022 | Close | `button-close-appeal` → done; backdrop/`button-cancel-appeal` closes modal; state reset after 200ms |
| APP-023 | appeal_id typing | `initiateAppealPayment` receives numeric `appeal_id` |

### Selectors
`button-download-appeal-form`, `button-lodge-appeal-top`, `input-appeal-name`, `input-appeal-email`, `input-appeal-phone`, `input-appeal-entity`, `input-appeal-file-upload`, `text-file-error-appeal`, `button-continue-payment`, `text-appeal-reference`, `button-pay-paynow`, `text-payment-error`, `input-appeal-proof-upload`, `input-appeal-proof-ref`, `button-upload-proof`, `text-proof-error`, `button-skip-payment`, `button-close-appeal`, `button-cancel-appeal`, `text-payment-banner`, `text-done-reference`.

---

## M8 — Media Centre

### M8.1 DownloadsPage (`/downloads`) — static catalogue

| ID | Case | Expected |
|---|---|---|
| DL-001 | Hero | "Reports & Documents" |
| DL-002 | 6 categories | Registration & Accreditation Forms; FOIA Forms; Organizational; Policies; Legislation; Statutory Instruments |
| DL-003 | File rows | `download-item-{cat}-{file}` present |
| DL-004 | Download hrefs | Exact `/documents/...` hrefs; FOIA `#page=` links omit `download` attribute |
| DL-005 | Contact CTA | `button-contact-downloads` → `onNavigate("contact")` |
| DL-006 | Format/size | e.g. `DOC | 38 KB` |

### M8.2 PressReleasesPage (`/press-releases`, `/press-releases/:id`)

| ID | Case | Expected |
|---|---|---|
| PR-001 | List hero | "Press Releases" |
| PR-002 | Loading | "Loading releases..." |
| PR-003 | List render | Cards `release-card-{id}` with title/category/excerpt/date |
| PR-004 | Category badge | `notice_category \|\| category \|\| "Press Release"` |
| PR-005 | No legacy text | "Read Full Release" absent |
| PR-006 | Excerpt fallback | Derived from `body` when `excerpt` absent |
| PR-007 | Navigate to detail | Click card → `/press-releases/{id}` |
| PR-008 | Image | `image_path` via `portalAssetUrl` |
| PR-009 | Empty state | "No Press Releases Yet" |
| PR-010 | Feed failure | Falls back to empty state (errors swallowed per-call) |
| PR-011 | Notices section | `notices-section`, `notice-card-{id}` sorted newest-first |
| PR-012 | Detail hero | "Press Release" |
| PR-013 | Detail load | `portalApi.getSingleRecord("news", id)`; renders title, badge, body |
| PR-014 | Detail HTML body | `dangerouslySetInnerHTML` when body contains HTML |
| PR-015 | Detail back | "Back to Releases" → `/press-releases` |
| PR-016 | Detail error | "Back to Home" button + error/"Press release not found." |

### M8.3 NoticeDetailPage (`/notices/:id`)

| ID | Case | Expected |
|---|---|---|
| NTC-001 | Hero | "Notice Details"; breadcrumb Home / Press Releases / Notice |
| NTC-002 | Loading | "Loading notice..." |
| NTC-003 | List-scan lookup | `getNotices(1,100)` then `find`; found notice renders |
| NTC-004 | Skip notice | "Notice not found" / "This notice may have been withdrawn or removed." |
| NTC-005 | Badges | "Notice", category, "{urgency} priority" |
| NTC-006 | Meta | Published date, Ref, Summary, Notice Details, Issuing Authority, Signed By, Target Audience, Effective Date, Expires, Legal Reference |
| NTC-007 | Contact block | "Contact for Enquiries" when contact fields present |
| NTC-008 | Attachment | Filename shown when `attachment_original_name` present |
| NTC-009 | Back button | `button-back-notices` "Back to Press Releases" |

### M8.4 MagazinePage (`/magazine`) — static "coming soon"

| ID | Case | Expected |
|---|---|---|
| MAG-001 | Hero | "ZMC Magazine"; breadcrumbs Home / Media Centre / Magazine |
| MAG-002 | Coming soon | `text-magazine-title` "ZMC Magazine Coming Soon"; `text-magazine-message` |
| MAG-003 | Contact CTA | `button-contact-magazine` → `onNavigate("contact")` |

### M8.5 PhotosPage (`/photos`) — gallery (⚠ no data source)

| ID | Case | Expected |
|---|---|---|
| PHO-001 | Hero | "Gallery" |
| PHO-002 | Loading→empty | Immediately resolves to empty state: "No media available yet" (albums/videos are never populated) |
| PHO-003 | Empty copy | "Photos and videos will appear here once they are published." |
| PHO-004 | Album navigation (**conditional**) | If albums existed: `album-card-{id}` → `inline-photo-viewer`, `button-lightbox-prev/next`, `photo-{id}`, `button-back-to-albums` |
| PHO-005 | Video player (**conditional**) | If videos existed: `video-card-{id}` → `inline-video-player`, `button-close-video` |

> Risk registered in §18: `PhotosPage` has no `portalApi` wiring, so all album/video cases are currently unreachable.

### M8.6 MediaHubPage (`/media-hub`) — static

| ID | Case | Expected |
|---|---|---|
| HUB-001 | Hero | "Media Hub"; breadcrumbs Home / Media Hub |
| HUB-002 | About | "About the Media Hub" paragraph |
| HUB-003 | Steps | "How to Register" with 6 numbered steps |
| HUB-004 | Register link | `button-register-training-hub` href `https://traininghub.zmc.org.zw`, `target=_blank` |

---

## M9 — Opportunities (Vacancies, Tenders, Events)

### M9.1 VacanciesPage (`/vacancies`)

| ID | Case | Expected |
|---|---|---|
| VAC-001 | Hero | "Vacancies" |
| VAC-002 | Loading | "Loading opportunities..." |
| VAC-003 | Error | Message + "Retry" (reload) |
| VAC-004 | Empty | "No vacancies found" / "We currently have no open positions matching your search." |
| VAC-005 | List | Rows `vacancy-row-{id}`; columns Position Advertised, Reference No., Closing Date, Type, Duty Station |
| VAC-006 | Search | `input-search-vacancies` filters title/reference/type/station |
| VAC-007 | Status | "Open"/"Closed" pill by `closing_at` |
| VAC-008 | Navigate | Row/View Details → `/vacancies/{id}` |
| VAC-009 | Footer count | "{n} position(s) found. Click any row to view full details." |
| VAC-010 | Feed failure | Falls back to empty state |

### M9.2 VacancyDetailPage (`/vacancies/:id`)

| ID | Case | Expected |
|---|---|---|
| VAC-D-001 | Hero | `vacancy.title` or "Vacancy Details" |
| VAC-D-002 | Loading | "Loading vacancy..." |
| VAC-D-003 | Not found | "Vacancy not found" / "This position may have been closed or removed." |
| VAC-D-004 | List-scan lookup | `getVacancies(1,100)` then `find` |
| VAC-D-005 | Content | Job Description (HTML), Requirements (Qualifications/Experience/Skills), Reference, Closing Date, Type, Duty Station |
| VAC-D-006 | Apply mailto | "How to Apply" → `mailto:{submission_email}` |
| VAC-D-007 | Back | `button-back-vacancies` → `/vacancies` |

### M9.3 TendersPage (`/tenders`)

| ID | Case | Expected |
|---|---|---|
| TEN-001 | Hero | "Tenders" |
| TEN-002 | e-GP banner | `link-praz-portal` href `https://egp.praz.org.zw/` |
| TEN-003 | Loading | "Loading tender notices..." |
| TEN-004 | Error | Message + "Retry" |
| TEN-005 | Empty | "No Tender Notices at the Moment" + "Visit e-GP System" |
| TEN-006 | List | `tender-card-{id}`; Open/Closed badge; category/reference/org; Published/Closes dates |
| TEN-007 | Search | `input-search-tenders` filters title/reference/org |
| TEN-008 | Navigate | Card/View Details → `/tenders/{id}` |

### M9.4 TenderDetailPage (`/tenders/:id`)

| ID | Case | Expected |
|---|---|---|
| TEN-D-001 | Hero | `tender.title` or "Tender Details" |
| TEN-D-002 | Loading | "Loading tender..." |
| TEN-D-003 | Not found | "Tender not found" / "This tender may have closed or been removed." |
| TEN-D-004 | Meta | Published, Closing Date, Issuing Organisation (fallback "Zimbabwe Media Commission") |
| TEN-D-005 | Body | "Tender Details" (HTML or description); Quantity if present |
| TEN-D-006 | Submit panel | "Submit Your Bid" + `link-egp-detail` (tender `egpUrl` or e-GP) |
| TEN-D-007 | Back | `button-back-tenders` → `/tenders` |

### M9.5 EventsPage (`/events`)

| ID | Case | Expected |
|---|---|---|
| EVT-001 | Hero | "Events" |
| EVT-002 | Loading | "Loading events..." |
| EVT-003 | Error | Message + "Retry" |
| EVT-004 | Empty | "No upcoming events scheduled at this time." |
| EVT-005 | List | `event-card-{id}`; type badge (fallback "Event"); date/time; location (fallback "TBA") |
| EVT-006 | Navigate | Card/View Details → `/events/{id}` |
| EVT-007 | Subscribe CTA | "Subscribe for Free" → `subscribe.open()` |
| EVT-008 | Feed failure | Falls back to empty (errors swallowed) |

### M9.6 EventDetailPage (`/events/:id`)

| ID | Case | Expected |
|---|---|---|
| EVT-D-001 | Hero | "Event Details" |
| EVT-D-002 | Loading | "Loading event..." |
| EVT-D-003 | Not found | "Event not found" / "This event may have ended or been removed." |
| EVT-D-004 | List-scan lookup | `getEvents(1,100)` then `find` |
| EVT-D-005 | Blocks | About This Event, Agenda, Organiser, Cost (0→"Free"), Target Audience |
| EVT-D-006 | Registration | "Registration Required" panel; "Register for this Event" link if URL, else fallback text |
| EVT-D-007 | Virtual link | "Join Online" panel |
| EVT-D-008 | Contact | "Event Contact" panel |
| EVT-D-009 | Back | `button-back-events` → `/events` |

---

## M10 — Contact & Newsletter

### M10.1 ContactPage (`/contact`)

| ID | Case | Expected |
|---|---|---|
| CON-001 | Hero | "Contact Us" |
| CON-002 | Head office card | `card-office-head`: name "Head Office - Harare"; address; postal; switchboard-0; directline-0; hotline; WhatsApp; email; hours |
| CON-003 | Regional office | `card-office-bulawayo` with phone `+263 713 879 044` |
| CON-004 | Map embed | `iframe-google-map` with title "Head Office - Harare Location" |
| CON-005 | Form submit | Fill fields + `select-subject`; submit → "Message Sent!" |
| CON-006 | Email link | `text-email` href `mailto:info@zmc.org.zw` |
| CON-007 | Subscribe button | `button-subscribe-contact` opens `dialog-subscribe` |
| CON-008 | Required validation | Empty submit blocked by required attributes |

### M10.2 SubscribeDialog (`SubscribeProvider`)

| ID | Case | Expected |
|---|---|---|
| SUB-001 | Default not subscribed | `isSubscribed=false` |
| SUB-002 | Persisted subscription | `localStorage.zmc_subscribed="true"` → `isSubscribed=true` |
| SUB-003 | Open dialog | `open()` → `dialog-subscribe`; title "Subscribe for Free Updates" |
| SUB-004 | Validation | Empty submit → "Please enter your full name"; no fetch |
| SUB-005 | Successful subscribe | POST `/api/subscribe`; persist localStorage; toast; close dialog |
| SUB-006 | Already subscribed | `{alreadySubscribed:true}` → toast "You're already subscribed" |
| SUB-007 | Failure | 500 response → dialog stays open; localStorage not set |
| SUB-008 | Fields | `input-subscribe-fullname`, `input-subscribe-email`, `button-subscribe-submit` |

---

## M11 — ZMC Assistant (Chatbot)

**Features:** toggle panel; welcome greeting; timed category menu; 5 categories; fee lookups; free-text intent matching; inline action buttons and links; clear chat; Media Hub promo banner.

| ID | Case | Expected |
|---|---|---|
| BOT-001 | Toggle open | `button-chatbot-toggle` opens panel; greeting shown |
| BOT-002 | Toggle close | Click again closes panel |
| BOT-003 | Media Hub banner | `banner-media-hub` points to Training Hub; hidden when open |
| BOT-004 | Category menu | Appears after 1.5s; `category-accreditation/registration/complaints/appeals/training` |
| BOT-005 | Select category | Adds user bubble + bot content + actions/link |
| BOT-006 | Fees — accreditation | `action-fees:accreditation` shows US$ figures |
| BOT-007 | Fees — registration | `action-fees:registration` shows US$ figures |
| BOT-008 | Main menu action | `action-menu` shows menu + prompt |
| BOT-009 | Back to category | `action-cat:accreditation` returns category content |
| BOT-010 | Link button | `link-{page}` navigates (media-hub opens Training Hub in new tab) |
| BOT-011 | Send message | `input-chat-message` + `button-send-message` or Enter |
| BOT-012 | Intent: accreditation | "accredit"→ accreditation content |
| BOT-013 | Intent: fees | "how much" → asks fees sub-choice; with "regist" → registration fees |
| BOT-014 | Intent: contact | "phone/email/address" → contact info |
| BOT-015 | Intent: greeting | "hello/hi" → greeting reply |
| BOT-016 | Intent: thanks | "thank" → "You're welcome!" |
| BOT-017 | Fallback | Unknown text → fallback help message |
| BOT-018 | Clear chat | `button-chatbot-clear` resets to greeting |
| BOT-019 | Close button | `button-chatbot-close` closes panel |
| BOT-020 | Empty input ignored | Send with blank input → no new message |

---

## M12 — Backend API, Storage & Static Hosting

### M12.1 `POST /api/subscribe`

| ID | Case | Request | Expected |
|---|---|---|---|
| API-SUB-001 | Valid new | `{fullName:"Jane Doe", email:"jane@example.com"}` | `201 {alreadySubscribed:false, subscriber}` |
| API-SUB-002 | Invalid name | `{fullName:"J", email:"jane@example.com"}` | `400` + zod message |
| API-SUB-003 | Invalid email | `{fullName:"Jane Doe", email:"bad"}` | `400` + zod message |
| API-SUB-004 | Duplicate (case-insensitive) | Existing email | `200 {alreadySubscribed:true, subscriber}` |
| API-SUB-005 | Trimmed input | Whitespace around values | Stored trimmed |

### M12.2 `POST /api/complaints`

| ID | Case | Expected |
|---|---|---|
| API-CMP-001 | Valid | `200 {success:true}`; SendPulse POST made |
| API-CMP-002 | Missing fields | `400` + zod message; no SendPulse |
| API-CMP-003 | Description < 10 | `400` |
| API-CMP-004 | Bad evidence URL | `400` |
| API-CMP-005 | >5 attachments | `400` |
| API-CMP-006 | Mail not configured | Missing env vars → `500 "Mail service is not configured on the server."` |
| API-CMP-007 | SendPulse rejects | Non-OK → `500 "The email provider rejected the request."` |
| API-CMP-008 | Payload shape | SendPulse body includes subject `Media Complaint from {name}`, from/to, attachments_binary |

### M12.3 Storage (`MemStorage`)

| ID | Case | Expected |
|---|---|---|
| STO-001 | createUser | Returns user with generated UUID |
| STO-002 | getUserByUsername | Finds matching user |
| STO-003 | getUser miss | `undefined` |
| STO-004 | createSubscriber | Persists with UUID |
| STO-005 | getSubscriberByEmail | Case-insensitive match |

### M12.4 Static hosting (`serveStatic`)

| ID | Case | Expected |
|---|---|---|
| STA-001 | Missing build dir | Throws "Could not find the build directory: /home/zmc/app/public" |
| STA-002 | Serves assets | Static files served from `/home/zmc/app/public` |
| STA-003 | SPA fallback | Unknown path returns `index.html` |
| STA-004 | Production only | Static used only when `NODE_ENV=production`; else Vite middleware |

### M12.5 Server bootstrap (`server/index.ts`)

| ID | Case | Expected |
|---|---|---|
| SRV-001 | JSON body limit | Accepts up to 40 MB |
| SRV-002 | Request logging | Logs `/api` requests with method/path/status/duration |
| SRV-003 | Error middleware | Returns `{message}` with status |
| SRV-004 | Port | Uses `PORT` env or 5000, host `0.0.0.0` |
| SRV-005 | Uncaught handlers | `uncaughtException` / `unhandledRejection` logged |

---

## M13 — Shared Libraries

### M13.1 `lib/api.ts`

| ID | Case | Expected |
|---|---|---|
| API-001 | Base URL | `${PORTAL_BASE_URL}/api/v1` |
| API-002 | portalAssetUrl truthy | `https://portal.zmc.org.zw/storage/{path}` |
| API-003 | portalAssetUrl falsy | `null` |
| API-004 | getNews defaults | `?page=1&per_page=20` |
| API-005 | getSingleRecord | `/{type}/{id}` with valid type |
| API-006 | verifyAccreditation encoding | `encodeURIComponent(number)` |
| API-007 | List query serialization | URLSearchParams for practitioner/house params |
| API-008 | getHomeStats | `GET /public-stats` |
| API-008 | submitContact object | JSON content type |
| API-009 | submitContact FormData | No forced JSON content type |
| API-010 | submitAppeal headers | `Accept: application/json` |
| API-011 | initiateAppealPayment body | `{appeal_id, email}` JSON |
| API-012 | uploadAppealPaymentProof | FormData + `Accept` |
| API-013 | getAppealPaymentStatus | `{appeal_id, poll_url}` |
| API-014 | handleResponse error | Throws `{status, message, errors}` |
| API-015 | pr.login stores token | `localStorage.pr_token` set when token present |
| API-016 | pr.logout | Removes `pr_token` |
| API-017 | authenticatedRequest | Adds `Authorization: Bearer` from localStorage; base `/pr` |
| API-018 | pr CRUD methods | Correct verbs/paths for resources; `togglePublish` excludes downloads |
| API-019 | Constants | `CONTACT_EMAIL`, `PORTAL_LOGIN_URL`, `TRAINING_HUB_URL`, `PRAZ_EGP_URL`, `SOCIAL_LINKS` |

### M13.2 `lib/utils.ts`

| ID | Case | Expected |
|---|---|---|
| UTIL-001 | cn merges classes | Merges conditional classes; later Tailwind class wins |

### M13.3 `lib/pdfExport.ts`

| ID | Case | Expected |
|---|---|---|
| PDF-001 | Generates & saves | `jsPDF.save(filename)` called |
| PDF-002 | Landscape A4 | Correct doc config |
| PDF-003 | Headers/rows | Columns map to head/body; missing keys → "" |
| PDF-004 | Subtitle optional | `startY` shifts when subtitle given |
| PDF-005 | Footer per page | "Generated … • Zimbabwe Media Commission • zmc.org.zw" |

### M13.4 `lib/queryClient.ts`

| ID | Case | Expected |
|---|---|---|
| QC-001 | apiRequest returns Response | On OK |
| QC-002 | apiRequest throws | `` `${status}: ${text}` `` on non-OK |
| QC-003 | queryFn 401 returnNull | Returns `null` |
| QC-004 | queryFn 401 throw | Throws |
| QC-005 | default options | retry false, staleTime Infinity, etc. |

### M13.5 `shared/schema.ts`

| ID | Case | Expected |
|---|---|---|
| SCH-001 | insertSubscriberSchema valid | Passes |
| SCH-002 | fullName min 2 | Rejects length 1 |
| SCH-003 | email format | Rejects invalid |
| SCH-004 | Trims values | Whitespace trimmed |
| SCH-005 | complaintSchema valid | Passes |
| SCH-006 | description min 10 | Rejects < 10 |
| SCH-007 | evidenceLink empty allowed | `""` valid; invalid URL rejected |
| SCH-008 | attachments max 5 | 6 rejected |
| SCH-009 | insertUserSchema | username/password only |

---

## M14 — Internationalisation (i18n)

**Languages:** `en`, `sn`, `nd`. Default `en`. No persistence (reset on reload).

| ID | Case | Expected |
|---|---|---|
| I18N-001 | Default language | `en` |
| I18N-002 | setLanguage switches | `t` updates to selected bundle |
| I18N-003 | Key parity en/sn/nd | All three expose identical key structure |
| I18N-004 | Known strings | Complaint upload button "Upload Complaint Documents"; appeals "Upload Documents" |
| I18N-005 | Nav translation | `t.nav.*` present across languages |
| I18N-006 | useLanguage outside provider | Returns default context (no throw) |
| I18N-007 | Placeholder strings | e.g. `noCurrentText` contains `{title}` |

---

## M15 — Cross-Cutting Concerns

| ID | Area | Case | Expected |
|---|---|---|---|
| XC-001 | Routing | All declared routes resolve to correct page | Match `App.tsx` route table |
| XC-002 | Navigation scroll | Any nav sets `window.scrollTo({top:0, behavior:"smooth"})` | Called |
| XC-003 | Loading states | Every data page shows a loading indicator | Per-module strings |
| XC-004 | Empty states | Every list page shows empty copy | Per-module strings |
| XC-005 | Error states | Data pages surface errors (portal lists often fall back to empty) | Per-module |
| XC-006 | Responsive | Desktop nav ≥ lg; hamburger < lg; social sidebar hidden on mobile | Visual |
| XC-007 | Accessibility | Landmarks, `aria-label`s, `role=alert`, alt text | axe passes/no critical |
| XC-008 | Body scroll lock | Modals/mobile menu lock body scroll and restore on close | `body.overflow` toggles |
| XC-009 | New-tab links | All `target=_blank` include `rel="noopener noreferrer"` | Verified |
| XC-010 | File size guards | Complaints/Appeals 10 MB limit | Error text shown |
| XC-011 | Network failure | API failures do not crash; graceful messaging | Verified |
| XC-012 | Security | No secrets in client bundle; only `pr_token` in localStorage | Verified |

---

## 16. Existing Automated Test Inventory

| Suite | File | Coverage |
|---|---|---|
| API lib | `client/src/lib/api.test.ts` | portalApi methods, errors |
| Utils | `client/src/lib/utils.test.ts` | `cn` |
| PDF | `client/src/lib/pdfExport.test.ts` | table export |
| i18n | `client/src/i18n/i18n.test.ts` | en/sn/nd parity |
| Language context | `client/src/i18n/LanguageContext.test.tsx` | provider/switch |
| Schema | `shared/schema.test.ts` | zod rules |
| Storage | `server/storage.test.ts` | MemStorage |
| Server routes | `server/routes.test.ts` | subscribe/complaints |
| Static | `server/static.test.ts` | serveStatic |
| PageHero | `client/src/components/PageHero.test.tsx` | hero |
| SocialSidebar | `client/src/components/SocialSidebar.test.tsx` | links |
| ServicesCards | `client/src/components/ServicesCards.test.tsx` | cards/nav |
| Footer | `client/src/components/Footer.test.tsx` | columns/links |
| Hero | `client/src/components/Hero.test.tsx` | CTAs/stats |
| NewsSlider | `client/src/components/NewsSlider.test.tsx` | slides/badges/regression |
| Header | `client/src/components/Header.test.tsx` | nav/dropdowns/mobile |
| Chatbot | `client/src/components/Chatbot.test.tsx` | intents/actions |
| SubscribeDialog | `client/src/components/SubscribeDialog.test.tsx` | subscribe flow |
| VerifyAccreditation | `client/src/components/VerifyAccreditation.test.tsx` | form/valid/not-found/inactive/error/reset |
| PublicDirectory | `client/src/components/PublicDirectory.test.tsx` | both variants/rows/status/empty/error/search/paginate/export |
| HomePage | `client/src/pages/HomePage.test.tsx` | composition + news feed |
| AboutPage | `client/src/pages/AboutPage.test.tsx` | mandate/functions/CTA |
| CommissionersPage | `client/src/pages/CommissionersPage.test.tsx` | board cards |
| SecretariatPage | `client/src/pages/SecretariatPage.test.tsx` | roles/organogram |
| AccreditationPage | `client/src/pages/AccreditationPage.test.tsx` | steps/apply opens portal |
| AccreditedPractitionersPage | `client/src/pages/AccreditedPractitionersPage.test.tsx` | directory render |
| VerifyAccreditationPage | `client/src/pages/VerifyAccreditationPage.test.tsx` | form render |
| RegistrationPage | `client/src/pages/RegistrationPage.test.tsx` | steps/register opens portal |
| RegisteredMediaHousesPage | `client/src/pages/RegisteredMediaHousesPage.test.tsx` | directory + legacy export |
| ComplaintsPage | `client/src/pages/ComplaintsPage.test.tsx` | modal/submit/errors |
| AppealsPage | `client/src/pages/AppealsPage.test.tsx` | 2-step flow/payment/proof |
| DownloadsPage | `client/src/pages/DownloadsPage.test.tsx` | categories/items/CTA |
| EventsPage | `client/src/pages/EventsPage.test.tsx` | list/nav/empty |
| EventDetailPage | `client/src/pages/EventDetailPage.test.tsx` | detail/not-found |
| PressReleasesPage | `client/src/pages/PressReleasesPage.test.tsx` | list/detail/notices |
| NoticeDetailPage | `client/src/pages/NoticeDetailPage.test.tsx` | detail/not-found |
| ContactPage | `client/src/pages/ContactPage.test.tsx` | offices/form/subscribe |
| VacanciesPage | `client/src/pages/VacanciesPage.test.tsx` | list/search/empty |
| VacancyDetailPage | `client/src/pages/VacancyDetailPage.test.tsx` | detail/not-found |
| TendersPage | `client/src/pages/TendersPage.test.tsx` | list/e-GP link |
| TenderDetailPage | `client/src/pages/TenderDetailPage.test.tsx` | detail/not-found |
| MagazinePage | `client/src/pages/MagazinePage.test.tsx` | coming-soon/CTA |
| PhotosPage | `client/src/pages/PhotosPage.test.tsx` | empty gallery state |
| MediaHubPage | `client/src/pages/MediaHubPage.test.tsx` | info/register link |

**Current status:** 44 test files / 220 tests passing (`npx vitest run`), `npm run check` (tsc) clean.

**Remaining gaps (not yet automated):** `not-found` page (unrouted), i18n page-level copy assertions, and true E2E for Paynow/SendPulse (require live services).

---

## 17. Requirements Traceability Matrix

| Feature | Module | Test IDs | Automated? |
|---|---|---|---|
| Global nav & footer | M1 | GLB-001..026 | Partial |
| Home landing | M2 | HOME-001..036 | Yes |
| About/Commissioners/Secretariat | M3 | ABOUT/SEC/COMM-xxx | Yes |
| Accreditation info + modal | M4 | ACC-xxx | Yes |
| Practitioner directory | M4 | ACC-P-xxx | Yes |
| Verify accreditation | M4 | VER-xxx | Yes |
| Registration info + modal | M5 | REG-xxx | Yes |
| Media houses directory | M5 | RMH-xxx | Yes |
| Media complaints | M6 | CMP-xxx | Yes |
| FOIA appeals + payment/proof | M7 | APP-xxx | Yes |
| Downloads | M8 | DL-xxx | Yes |
| Press releases + notices | M8 | PR/NTC-xxx | Yes |
| Magazine | M8 | MAG-xxx | Yes |
| Photos gallery | M8 | PHO-xxx | Yes |
| Media Hub | M8 | HUB-xxx | Yes |
| Vacancies | M9 | VAC/VAC-D-xxx | Yes |
| Tenders | M9 | TEN/TEN-D-xxx | Yes |
| Events | M9 | EVT/EVT-D-xxx | Yes |
| Contact | M10 | CON-xxx | Yes |
| Newsletter subscribe | M10 | SUB-xxx | Yes |
| Chatbot | M11 | BOT-xxx | Yes |
| Backend API | M12 | API-*/STO-*/STA-*/SRV-* | Yes |
| Shared libs | M13 | API/UTIL/PDF/QC/SCH-xxx | Yes |
| i18n | M14 | I18N-xxx | Yes |
| Cross-cutting | M15 | XC-xxx | Partial |

---

## 18. Known Limitations / Risks Observed

These are behaviours/quirks to account for when writing tests and triaging failures:

1. **`PhotosPage` has no backend wiring** — `albums`/`videos` are initialised empty and never fetched, so album/video cases (PHO-004/005) are unreachable until data loading is implemented.
2. **`not-found.tsx` is not routed** — the catch-all in `App.tsx` renders `HomePage`; the 404 component never mounts.
3. **Detail pages scan list endpoints** (`getEvents/getTenders/getVacancies/getNotices(1,100)` then `find`) instead of `getSingleRecord`; records beyond page 100 are unreachable.
4. **Swallowed errors** — Events, Press Releases, Vacancies and Tenders wrap fetches with `.catch(() => null)`, so hard failures render the *empty* state rather than an error state.
5. **Portal fallback modals are unreachable** — `AccreditationPage`/`RegistrationPage` show their local modal only when `PORTAL_LOGIN_URL` is falsy, which it never is.
6. **Complaints POSTs `message` twice** — the evidence link is appended as a second `message` form field; `FormData.get("message")` returns only the first. Assert with `getAll("message")`.
7. **"Read Full Release" regression** — must never reappear in sliders/press releases; guarded by regression tests (HOME-013, PR-005).
8. **i18n has no persistence** — language resets to English on reload.
9. **Native form validation** — `required` fields block submit in jsdom; trigger submissions with `fireEvent.submit(form)` in tests.
10. **`portalAssetUrl` base** — uses `PORTAL_BASE_URL` (no `/api/v1`), returning `/storage/...`.
11. **Chatbot free-text matching is substring-based** — order of checks matters (fees before category, contact before greeting).
12. **`PUBLIC` file-type filters** — `userEvent.upload` respects `accept`; ensure test files match accepted types.

---

### Sign-off

| Role | Name | Date | Signature |
|---|---|---|---|
| QA Lead | | | |
| Dev Lead | | | |
| Product Owner | | | |
