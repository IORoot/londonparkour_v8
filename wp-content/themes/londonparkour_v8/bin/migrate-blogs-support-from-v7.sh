#!/usr/bin/env bash
#
# Migrate blogs + support from v7, sync ACF groups, remove demo posts.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"

wp() { docker compose exec -T cli wp --url=http://localhost:8102 "$@"; }

echo "Syncing new ACF field groups..."
wp acf json sync

echo "Running migration..."
docker compose exec -T wordpress php /var/www/html/wp-content/themes/londonparkour_v8/bin/migrate-blogs-support-from-v7.php
