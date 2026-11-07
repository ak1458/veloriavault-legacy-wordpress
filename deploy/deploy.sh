#!/usr/bin/env bash
# Deploys the release ZIPs in dist/ to a WordPress server over SSH.
#
#   deploy/deploy.sh deploy/staging.env
#   deploy/deploy.sh deploy/production.env --confirm-production
#
# What it does:
#   1. Verifies dist/SHA256SUMS locally.
#   2. Uploads both ZIPs to ~/veloria-releases/<version>/ on the server.
#   3. Backs up the current theme + plugin folders and the database (wp db export)
#      to ~/veloria-releases/backup-<timestamp>/.
#   4. Unpacks into a temporary folder, then swaps each folder in with mv.
#
# What it never does: activate/deactivate plugins or themes, run seeders,
# change options, import a database, or touch uploads. Those are explicit,
# manual cutover steps (see README "Cutover").
set -euo pipefail

ENV_FILE=${1:?usage: deploy/deploy.sh <env-file> [--confirm-production] [--skip-db-backup]}
shift || true
CONFIRM_PROD=0
DB_BACKUP=1
for arg in "$@"; do
	case "$arg" in
		--confirm-production) CONFIRM_PROD=1 ;;
		--skip-db-backup) DB_BACKUP=0 ;;
		*) echo "Unknown option: $arg" >&2; exit 2 ;;
	esac
done

# shellcheck disable=SC1090
. "$ENV_FILE"
: "${DEPLOY_HOST:?}" "${DEPLOY_USER:?}" "${WP_PATH:?}" "${DEPLOY_TARGET:?}"
DEPLOY_PORT=${DEPLOY_PORT:-22}
KEY_OPT=()
[ -n "${SSH_KEY:-}" ] && KEY_OPT=(-i "$SSH_KEY")

if [ "$DEPLOY_TARGET" = production ] && [ "$CONFIRM_PROD" -ne 1 ]; then
	echo "Refusing to deploy to production without --confirm-production." >&2
	exit 1
fi

cd "$(dirname "$0")/.."
(cd dist && sha256sum -c SHA256SUMS)
PLUGIN_ZIP=$(ls dist/veloria-commerce-*.zip)
THEME_ZIP=$(ls dist/veloria-[0-9]*.zip)
VERSION=$(basename "$PLUGIN_ZIP" .zip | sed 's/^veloria-commerce-//')
[ "$(basename "$THEME_ZIP" .zip)" = "veloria-$VERSION" ] || { echo "Theme and plugin versions differ." >&2; exit 1; }

SSH=(ssh "${KEY_OPT[@]}" -p "$DEPLOY_PORT" "$DEPLOY_USER@$DEPLOY_HOST")
REMOTE_DIR="veloria-releases/$VERSION"

echo "Deploying $VERSION to $DEPLOY_TARGET ($DEPLOY_HOST:$WP_PATH)"
"${SSH[@]}" "mkdir -p ~/$REMOTE_DIR"
scp "${KEY_OPT[@]}" -P "$DEPLOY_PORT" "$PLUGIN_ZIP" "$THEME_ZIP" dist/SHA256SUMS "$DEPLOY_USER@$DEPLOY_HOST:~/$REMOTE_DIR/"

"${SSH[@]}" bash -s -- "$WP_PATH" "$REMOTE_DIR" "$VERSION" "$DB_BACKUP" <<'REMOTE'
set -euo pipefail
WP_PATH=$1 REMOTE_DIR=$2 VERSION=$3 DB_BACKUP=$4
cd ~/"$REMOTE_DIR"
sha256sum -c SHA256SUMS
[ -f "$WP_PATH/wp-config.php" ] || { echo "No wp-config.php in $WP_PATH" >&2; exit 1; }

STAMP=$(date -u +%Y%m%dT%H%M%SZ)
BACKUP=~/veloria-releases/backup-$STAMP
mkdir -p "$BACKUP"
cd "$WP_PATH/wp-content"
for dir in themes/veloria plugins/veloria-commerce; do
	if [ -d "$dir" ]; then tar -czf "$BACKUP/$(basename "$dir").tar.gz" -C "$(dirname "$dir")" "$(basename "$dir")"; fi
done
if [ "$DB_BACKUP" = 1 ]; then
	# Hostinger disables exec(), so `wp db export` cannot run; call mysqldump with a
	# private, temporary client config built from wp-config.php (never printed).
	command -v wp >/dev/null && command -v mysqldump >/dev/null || { echo "wp-cli/mysqldump not found; rerun with --skip-db-backup only after a manual DB backup." >&2; exit 1; }
	CNF=$(mktemp); chmod 600 "$CNF"
	printf '[client]\nuser=%s\npassword=%s\nhost=%s\n' "$(wp --path="$WP_PATH" config get DB_USER)" "$(wp --path="$WP_PATH" config get DB_PASSWORD)" "$(wp --path="$WP_PATH" config get DB_HOST)" > "$CNF"
	mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --triggers --no-tablespaces "$(wp --path="$WP_PATH" config get DB_NAME)" | gzip > "$BACKUP/database.sql.gz"
	rm -f "$CNF"
	zcat "$BACKUP/database.sql.gz" | tail -1 | grep -q 'Dump completed' || { echo "Database backup incomplete; aborting before any file change." >&2; exit 1; }
fi
echo "$VERSION" > "$BACKUP/deployed-version"
echo "Backup: $BACKUP"

TMP=$(mktemp -d "$WP_PATH/wp-content/.veloria-deploy-XXXXXX")
trap 'rm -rf "$TMP"' EXIT
unzip -q ~/"$REMOTE_DIR/veloria-commerce-$VERSION.zip" -d "$TMP"
unzip -q ~/"$REMOTE_DIR/veloria-$VERSION.zip" -d "$TMP"
swap() { # $1 = new dir, $2 = live dir
	if [ -d "$2" ]; then mv "$2" "$2.old-$STAMP"; fi
	mv "$1" "$2"
	rm -rf "$2.old-$STAMP"
}
swap "$TMP/veloria-commerce" "$WP_PATH/wp-content/plugins/veloria-commerce"
swap "$TMP/veloria" "$WP_PATH/wp-content/themes/veloria"
echo "$BACKUP" > ~/veloria-releases/LAST_BACKUP
echo "Installed $VERSION files. Nothing was activated."
# The anonymous page cache otherwise retains old templates and old asset URLs
# even though the runtime files have been replaced successfully.
if command -v wp >/dev/null; then
	wp --path="$WP_PATH" eval 'if ( has_action( "litespeed_purge_all" ) ) { do_action( "litespeed_purge_all" ); echo "LiteSpeed page cache purged.\n"; }'
fi
REMOTE

if [ -n "${SITE_URL:-}" ]; then
	code=$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/" || true)
	echo "Health check $SITE_URL/ -> HTTP $code"
fi
