# ZMC portal UI/UX changes

Changes to the Laravel staff portal (`/var/www/portal.zmc.org.zw`), kept in this folder of the website repo. Paths below `resources/` and `public/` mirror the app root. Baseline commit = original server files.

- Visible scrollbars, readable type scale, focus rings (`layouts/portal.blade.php`)
- Reusable tab bar (`staff/partials/tabs.blade.php`), used on PR, officer, accounts, registrar, auditor dashboards
- 16 pages moved from `layouts.staff` to `layouts.portal`; auditor complaints script fixed
- Success colour is green again; sidebar inline styles moved to CSS; sidebar a11y labels
- Bootstrap 5.3.3 + Remix Icon 4.6.0 self-hosted in `public/vendor/zmc`

Deploy: `sudo sh tools/deploy.sh <path-to-this-portal-folder>` on the server. It backs up overwritten files and runs `php artisan view:clear`.
`tools/changed-files.txt` lists the touched views; `tools/blade_check.php` compiles templates with the server Blade compiler.
