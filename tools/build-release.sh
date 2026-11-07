#!/bin/sh
# Builds upload-ready zips in dist/: veloria-commerce-<ver>.zip and veloria-<ver>.zip.
# Only runtime files are copied into the release. A failed check leaves existing releases intact.
set -e
cd "$(dirname "$0")/.."
ROOT=$(pwd)
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT HUP INT TERM

PV=$(sed -n 's/.*Version: *\([0-9.]*\).*/\1/p' wp-content/plugins/veloria-commerce/veloria-commerce.php | head -1)
TV=$(sed -n 's/^Version: *\([0-9.]*\).*/\1/p' wp-content/themes/veloria/style.css | head -1)

[ -n "$PV" ] && [ -n "$TV" ] || { echo "Missing release version" >&2; exit 1; }
command -v php >/dev/null
command -v zip >/dev/null
command -v sha256sum >/dev/null
(cd wp-content/themes/veloria && npm ci && npm run build && node tools/verify-css.mjs)
find wp-content/plugins/veloria-commerce wp-content/themes/veloria -type f -name '*.php' > "$STAGE/php-files.txt"
while IFS= read -r file; do php -l "$file"; done < "$STAGE/php-files.txt"
php wp-content/plugins/veloria-commerce/tests/parity/run-parity.php wp-content/plugins/veloria-commerce/tests/parity/vectors.json

mkdir -p "$STAGE/veloria-commerce" "$STAGE/veloria"
cp wp-content/plugins/veloria-commerce/veloria-commerce.php "$STAGE/veloria-commerce/"
cp -R wp-content/plugins/veloria-commerce/src wp-content/plugins/veloria-commerce/content wp-content/plugins/veloria-commerce/assets "$STAGE/veloria-commerce/"
# The installed CLI parity command uses this small fixture when --coupons is omitted.
mkdir -p "$STAGE/veloria-commerce/tests/parity"
cp wp-content/plugins/veloria-commerce/tests/parity/coupons.fixture.json "$STAGE/veloria-commerce/tests/parity/"
for file in wp-content/themes/veloria/*.php wp-content/themes/veloria/style.css wp-content/themes/veloria/screenshot.png; do cp "$file" "$STAGE/veloria/"; done
for dir in inc woocommerce page-templates templates template-parts public; do cp -R "wp-content/themes/veloria/$dir" "$STAGE/veloria/"; done
mkdir -p "$STAGE/veloria/assets"
cp -R wp-content/themes/veloria/assets/dist wp-content/themes/veloria/assets/fonts "$STAGE/veloria/assets/"
(cd "$STAGE" && find veloria veloria-commerce -type f | LC_ALL=C sort > manifest.txt && zip -qr "veloria-commerce-$PV.zip" veloria-commerce && zip -qr "veloria-$TV.zip" veloria && sha256sum "veloria-commerce-$PV.zip" "veloria-$TV.zip" > SHA256SUMS)
mkdir -p dist
for file in "veloria-commerce-$PV.zip" "veloria-$TV.zip" manifest.txt SHA256SUMS; do mv "$STAGE/$file" "$ROOT/dist/$file"; done
cat dist/SHA256SUMS
