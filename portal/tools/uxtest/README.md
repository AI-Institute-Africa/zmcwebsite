# Portal UX test harness (browser-only, no PHP or database)

Renders every Blade view in the portal (Blade directives stripped, dynamic values replaced by sample text) inside the real
layout CSS in headless Chrome and measures it. It cannot test controllers, permissions, form posts or real data.

## Setup
- Chrome at `C:/Program Files/Google/Chrome/Application/chrome.exe`, Node 18+, Python 3.10+ with Pillow.
- `cd pp && npm install puppeteer-core@23` (only needed for `run_page.js` / `charts_test.js`).
- Serve a work folder over HTTP, e.g. `python -m http.server 8767 --directory work/srv`. Put in `work/srv/vendor/` copies of
  `bootstrap/` and `remixicon/` (from `portal/public/vendor/zmc`), plus `axe.min.js`, `apexcharts.min.js`, `chart.umd.min.js`.
- Environment: `UXTEST_WORK` (default `./work`) and `PORTAL_ROOT` (default: this repo's `portal/`).

## Run
1. `python sweep_build.py` – strips every view into `work/srv/frag/N.html`.
2. `python audit_page.py dark` / `light` – builds the audit page (add `?only=12,40` to the URL to test a few views).
3. `node pp/run_page.js audit_dark.html work/dark.json` (add `phone` as 3rd arg for a 390 px viewport).
4. `python analyze_audit.py dark` (reads `work/dark.json` written by step 3).
5. `python shots.py render <label> <view-substring,...>` then `python shots.py sheet <label>` for contact sheets.
6. `python mkux.py` / `mkmodal.py` / `mkcharts.py` build the behaviour, frozen-modal and chart-theme test pages.
7. Regenerate the dark-mode CSS after view changes: `python scan_colors.py && python gen_dark.py` (writes `public/css/zmc-dark.css`
   and the inline block in `layouts/portal.blade.php`).

## What it checks
Text contrast (AA, large-text aware) in light and dark, axe-core violations, modals on top / Esc / scroll lock, table sort / filter /
CSV, delete-confirm dialogs, dropdown stability, inline validation, horizontal overflow, performance of the UX scripts,
chart theming. Measure contrast only after CSS transitions finish (about 350 ms), or buttons read stale colours.
