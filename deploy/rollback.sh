#!/usr/bin/env bash
# Restores the theme + plugin folders from the last deploy backup (files only).
#
#   deploy/rollback.sh deploy/staging.env [backup-folder-name]
#
# The database is NOT restored automatically: orders placed after the deploy
# would be lost. If a database restore is truly needed, do it manually from
# <backup>/database.sql.gz after confirming no new orders exist.
set -euo pipefail

ENV_FILE=${1:?usage: deploy/rollback.sh <env-file> [backup-folder-name]}
BACKUP_NAME=${2:-}
# shellcheck disable=SC1090
. "$ENV_FILE"
: "${DEPLOY_HOST:?}" "${DEPLOY_USER:?}" "${WP_PATH:?}"
DEPLOY_PORT=${DEPLOY_PORT:-22}
KEY_OPT=()
[ -n "${SSH_KEY:-}" ] && KEY_OPT=(-i "$SSH_KEY")

ssh "${KEY_OPT[@]}" -p "$DEPLOY_PORT" "$DEPLOY_USER@$DEPLOY_HOST" bash -s -- "$WP_PATH" "$BACKUP_NAME" <<'REMOTE'
set -euo pipefail
WP_PATH=$1 NAME=$2
if [ -n "$NAME" ]; then BACKUP=~/veloria-releases/$NAME; else BACKUP=$(cat ~/veloria-releases/LAST_BACKUP); fi
[ -d "$BACKUP" ] || { echo "Backup not found: $BACKUP" >&2; exit 1; }
cd "$WP_PATH/wp-content"
for pair in "themes:veloria" "plugins:veloria-commerce"; do
	parent=${pair%%:*} name=${pair##*:}
	if [ -f "$BACKUP/$name.tar.gz" ]; then
		rm -rf "$parent/$name.rollback-tmp"
		mkdir "$parent/$name.rollback-tmp"
		tar -xzf "$BACKUP/$name.tar.gz" -C "$parent/$name.rollback-tmp"
		# Keep the replaced copy outside wp-content so WordPress does not list it.
		[ -d "$parent/$name" ] && mv "$parent/$name" ~/veloria-releases/"$name.replaced-$(date -u +%s)"
		mv "$parent/$name.rollback-tmp/$name" "$parent/$name"
		rmdir "$parent/$name.rollback-tmp"
		echo "Restored $parent/$name from $BACKUP"
	else
		echo "No $name archive in $BACKUP (it did not exist before that deploy); leaving current files." >&2
	fi
done
REMOTE
