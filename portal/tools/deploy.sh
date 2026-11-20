#!/bin/sh
# Deploy ONLY the files listed in a manifest (never the whole tree), backing up what it overwrites.
# Run ON THE SERVER as a user that can write to the app (www-data or sudo).
# Usage: sudo sh deploy.sh <path-to-portal-folder> <manifest-file>
#   manifest = one path per line, relative to the app root (e.g. resources/views/layouts/portal.blade.php)
set -e
SRC="${1:?path to the portal/ folder}"; MANIFEST="${2:?manifest file}"
# tolerate Windows (CRLF) line endings in the manifest
CLEAN=$(mktemp); tr -d '' < "$MANIFEST" > "$CLEAN"; MANIFEST="$CLEAN"
APP=/var/www/portal.zmc.org.zw; BK=/home/zmc/backups/ux-$(date +%Y%m%d-%H%M%S)
mkdir -p "$BK"
# 1) lint PHP before touching anything
grep -E '\.php$' "$MANIFEST" | grep -v '\.blade\.php$' | while read -r f; do
  [ -f "$SRC/$f" ] && php -l "$SRC/$f" >/dev/null || { echo "PHP lint failed: $f"; exit 1; }
done
# 2) back up + copy
grep -v '^\s*$' "$MANIFEST" | while read -r f; do
  [ -f "$SRC/$f" ] || { echo "missing in source: $f"; exit 1; }
  [ -f "$APP/$f" ] && { mkdir -p "$BK/$(dirname "$f")"; cp -p "$APP/$f" "$BK/$f"; }
  mkdir -p "$APP/$(dirname "$f")"; cp "$SRC/$f" "$APP/$f"
done
# static assets (own folder, safe to sync whole)
for d in public/vendor/zmc; do [ -d "$SRC/$d" ] && { mkdir -p "$APP/$d"; cp -r "$SRC/$d/." "$APP/$d/"; }; done
chown -R www-data:www-data "$APP/app" "$APP/resources/views" "$APP/public/css" "$APP/public/js" "$APP/public/vendor/zmc" 2>/dev/null || true
cd "$APP" && php artisan view:clear
echo "Deployed $(grep -vc '^\s*$' "$MANIFEST") files. Backup: $BK"
echo "Rollback: cp -a $BK/. $APP/ && (cd $APP && php artisan view:clear)"
echo "New files have no backup; to remove them on rollback delete the paths in the manifest that are not in the backup."
