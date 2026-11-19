#!/bin/sh
# Deploy the staff-portal UI/UX files. Run ON THE SERVER as a user that can write to the app (www-data or sudo).
# Usage: sudo sh portal/tools/deploy.sh /path/to/zmcwebsite/portal
# Backs up every file it overwrites, copies resources/ and public/ over the app, then clears compiled views.
set -e
SRC="${1:?path to the portal/ folder}"; APP=/var/www/portal.zmc.org.zw; BK=/home/zmc/backups/uiux-$(date +%Y%m%d-%H%M%S)
mkdir -p "$BK"
( cd "$SRC" && find resources public -type f ) | while read f; do
  [ -f "$APP/$f" ] && { mkdir -p "$BK/$(dirname "$f")"; cp -p "$APP/$f" "$BK/$f"; }
  mkdir -p "$APP/$(dirname "$f")"; cp "$SRC/$f" "$APP/$f"
done
chown -R www-data:www-data "$APP/public/vendor/zmc" "$APP/resources/views" 2>/dev/null || true
cd "$APP" && php artisan view:clear
echo "Deployed. Backup of overwritten files: $BK"
echo "Rollback: cp -a $BK/. $APP/ && php artisan view:clear"
