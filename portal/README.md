# ZMC portal UI/UX changes

Changes to the Laravel staff portal (`/var/www/portal.zmc.org.zw`), kept in this folder of the website repo. Paths below `resources/` and `public/` mirror the app root. Baseline commit = original server files.

- Visible scrollbars, readable type scale, focus rings (`layouts/portal.blade.php`)
- Reusable tab bar (`staff/partials/tabs.blade.php`), used on PR, officer, accounts, registrar, auditor dashboards
- 16 pages moved from `layouts.staff` to `layouts.portal`; auditor complaints script fixed
- Success colour is green again; sidebar inline styles moved to CSS; sidebar a11y labels
- Bootstrap 5.3.3 + Remix Icon 4.6.0 self-hosted in `public/vendor/zmc`

Deploy: `sudo sh tools/deploy.sh <path-to-this-portal-folder>` on the server. It backs up overwritten files and runs `php artisan view:clear`.
`tools/changed-files.txt` lists the touched views; `tools/blade_check.php` compiles templates with the server Blade compiler.

## UX layer (queue strip, status chips, tables, search, sidebar, drafts)
- `public/js/zmc-ux.js`, `public/css/zmc-ux.css`; Blade partials in `resources/views/staff/partials/` (`status-chip`, `queue-strip`, `application-timeline`, `tabs`).
- PHP: `app/Support/ApplicationStatus.php` (shared colours/icons, text from `Application::statusLabel`), `app/Support/StaffQueue.php` (per-role queue counts, SLA from `config('zmc.workflow.sla_hours')`), sidebar-badge composer in `AppServiceProvider`.
- Deploy only the changed files: `sudo sh tools/deploy.sh <path-to-portal-folder> tools/deploy-manifest.txt` (lints PHP, backs up, copies, clears compiled views).
