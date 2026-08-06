#!/usr/bin/env bash
#
# Migrate all tutorials from v7 into v8 (lp_tutorial + taxonomies + ACF + thumbnails).
#
# Usage: themes/londonparkour_v8/bin/migrate-tutorials-from-v7.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"

docker compose exec -T wordpress php /var/www/html/wp-content/themes/londonparkour_v8/bin/migrate-tutorials-from-v7.php "$@"
