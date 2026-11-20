# ZMC deployment runbook

Two independent deployments. Do the portal first (the website only reads its API).
**No credentials belong in this repo.** Use the access details ZCHPC emailed you, and change the temporary passwords after first login.

| | Website (zmc.org.zw) | Portal (portal.zmc.org.zw) |
|---|---|---|
| Stack | React + Express bundle (`dist/index.cjs`, `dist/public/`) | Laravel (PHP), Blade views |
| Server path | `/home/zmc/app` (`index.cjs` + `public/`) | `/var/www/portal.zmc.org.zw` |
| Process | PM2 app `zmc-website`, port 3000, nginx proxy (`zmc_nginx.conf`) | php-fpm + nginx |
| Deploy tool | manual copy + `pm2 reload` | `portal/tools/deploy.sh` + manifest |

## 0. Pre-flight (on your machine)

```bash
git pull && git status          # clean, on main
npx tsc --noEmit                # 0 errors
npx vitest run                  # all pass
npm run build                   # -> dist/index.cjs, dist/public/
```

## 1. Portal

**Use `portal/tools/deploy-manifest-full.txt` (321 files).** Do not use `deploy-manifest-ux.txt`: it omits `layouts/portal.blade.php`, `public/css/zmc-dark.css` and the favicons, so the page would load without matching assets. `deploy-manifest.txt` (9 files) is the legacy layout-only list.

1. Copy the `portal/` folder to the server (e.g. `~/portal-src`), plus nothing else.
2. On the server:
   ```bash
   sudo sh ~/portal-src/tools/deploy.sh ~/portal-src ~/portal-src/tools/deploy-manifest-full.txt
   ```
   The script lints non-Blade PHP, backs up every overwritten file to `/home/zmc/backups/ux-<timestamp>`, copies the files, fixes ownership, runs `php artisan view:clear`, and prints the rollback command.
3. Optional Blade compile check before deploying: `php portal/tools/blade_check.php <files>` (needs the server's `vendor/`).
4. Smoke test in a browser (both light and dark theme): staff login, one dashboard per role, open a modal and press Esc, accreditation form -> payment modal, a table with sort/filter, phone width.
5. Rollback: `cp -a <backup>/. /var/www/portal.zmc.org.zw/ && cd /var/www/portal.zmc.org.zw && php artisan view:clear`. Files that were new have no backup; delete them by hand.

## 2. Website

1. Upload to the server: `dist/index.cjs` -> `/home/zmc/app/index.cjs`, `dist/public/*` -> `/home/zmc/app/public/`, and `ecosystem.config.cjs` if it changed. Keep the previous `public/` and `index.cjs` as `*.prev` for rollback.
   The server serves **only** `/home/zmc/app/public` (hard-coded in `server/static.ts`), so `public/` must sit next to `index.cjs`.
2. Environment on the server (PM2 env or shell, never committed): `NODE_ENV=production`, `PORT=3000`, `SENDPULSE_API_KEY`, `MAIL_FROM`, `COMPLAINT_RECIPIENT`, optional `DATABASE_URL`. The complaint email endpoint needs `SENDPULSE_API_KEY`, `MAIL_FROM` and `COMPLAINT_RECIPIENT` all set, otherwise it refuses to send.
3. `pm2 reload ecosystem.config.cjs --update-env && pm2 save`
4. Smoke test: `/`, `/contact` (labels, skip link via Tab), `/complaints`, an unknown URL (should show the 404 page), mobile menu, social links. Check `pm2 logs zmc-website` for errors.
5. Rollback: restore the `*.prev` files and `pm2 reload`.

## Known limitations

- Unknown website URLs show the 404 page but the server still answers HTTP 200 (SPA fallback). The page is marked `noindex`. A real 404 status needs a route allow-list in `server/static.ts`.
- `zmc_nginx.conf` listens on port 80 only. TLS is assumed to be handled by CloudPanel/the host; confirm HTTPS and HSTS after deploy.
- The portal UX test harness (`portal/tools/uxtest`) renders Blade views with sample data in headless Chrome. It cannot test controllers, permissions, form posts or the real layout, so a manual smoke test on the server is still required.
