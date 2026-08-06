#!/usr/bin/env bash
#
# Build all ACF JSON from PHP + sync into the WordPress database.
#
# Usage: themes/londonparkour_v8/bin/sync-acf-to-admin.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
cd "$ROOT"

say() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }

wp() { docker compose exec -T cli wp --url=http://localhost:8102 "$@"; }

say "Building ACF JSON from PHP and syncing to database"
wp lp acf:build --sync

say "Done — check Custom Fields in wp-admin"
