#!/usr/bin/env bash
# Safe to schedule before cutover. Enable only after reviewing the live job backlog.
set -euo pipefail
# hPanel cron runs with a minimal PATH that lacks wp-cli (/usr/local/bin).
export PATH="${PATH:-/usr/bin:/bin}:/usr/local/bin:/usr/bin:/bin"
WP_PATH=/home/veloria/domains/veloriavault.com/public_html/api
GATE=/home/veloria/veloria-tools/veloria-cutover-approved.flag
[ -f "$GATE" ] || exit 0
READY=$(HTTP_HOST=www.veloriavault.com wp --path="$WP_PATH" eval 'echo ("veloria" === get_template() && "production" === wp_get_environment_type() && "https://www.veloriavault.com/" === home_url("/") && !(defined("VELORIA_NOINDEX") && VELORIA_NOINDEX)) ? "ready" : "blocked";' --quiet)
[ "$READY" = ready ] || { echo 'Veloria cron blocked: storefront cutover checks failed.' >&2; exit 78; }
exec /usr/bin/php "$WP_PATH/wp-cron.php"
