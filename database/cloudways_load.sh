#!/usr/bin/env bash
# Import database/backup.sql into the Cloudways *staging* database.
#
# Cloudways layout (this is why public_html is not a git repo):
#   ~/applications/<app>/git_repo      ← git clone (often root-owned)
#   ~/applications/<app>/public_html   ← WordPress + copied repo files
#
# Git hooks never run in public_html. After you press Pull, either:
#   1. SSH:  bash ~/applications/londonparkour_staging/public_html/database/cloudways_load.sh
#   2. Cron on staging only (makes Pull automatic, ~1 min later):
#        * * * * * /bin/bash /home/master/applications/londonparkour_staging/public_html/database/cloudways_load.sh
#
# One-time (from public_html; the app root is root-owned and not writable):
#   touch .staging-import-enabled
#
# Cloudways Git Pull does not delete extra files (wp-config.php survives), so
# this lock stays. Do not create it on live.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DUMP="$REPO_ROOT/database/backup.sql"
FROM_URL="http://localhost:8102"
FORCE=0

usage() {
	cat <<'EOF'
Usage: database/cloudways_load.sh [--force]

Import database/backup.sql into this WordPress database (Cloudways staging).
Refuses unless .staging-import-enabled exists in public_html
(or next to it, if that directory is writable).
Skips when the dump SHA matches .staging-import-sha, unless --force.
EOF
}

die() {
	printf 'cloudways_load: %s\n' "$*" >&2
	exit 1
}

log() {
	printf 'cloudways_load: %s\n' "$*"
}

file_sha() {
	if command -v sha256sum >/dev/null 2>&1; then
		sha256sum "$1" | awk '{ print $1 }'
	else
		shasum -a 256 "$1" | awk '{ print $1 }'
	fi
}

strip_dump() {
	# Dump targets Docker's `wordpress` database. Cloudways already has a DB.
	sed -E \
		'/^(\/\*![0-9]+[[:space:]]+)?DROP DATABASE/d; /^CREATE DATABASE/d; /^USE[[:space:]]/d' \
		"$1"
}

resolve_wp_root() {
	if [[ -f "$REPO_ROOT/wp-config.php" ]]; then
		# Script lives in public_html (usual Cloudways copy destination).
		printf '%s\n' "$REPO_ROOT"
	elif [[ -f "$REPO_ROOT/../public_html/wp-config.php" ]]; then
		# Script lives in git_repo; WordPress is the sibling public_html.
		cd "$REPO_ROOT/../public_html" && pwd
	else
		die "cannot find wp-config.php (looked in $REPO_ROOT and $REPO_ROOT/../public_html)"
	fi
}

for arg in "$@"; do
	case "$arg" in
		--force) FORCE=1 ;;
		-h | --help)
			usage
			exit 0
			;;
		*)
			die "unknown option: $arg"
			;;
	esac
done

WP_ROOT="$(resolve_wp_root)"
APP_ROOT="$(cd "$WP_ROOT/.." && pwd)"

MARKER=""
for candidate in "$WP_ROOT/.staging-import-enabled" "$APP_ROOT/.staging-import-enabled"; do
	if [[ -f "$candidate" ]]; then
		MARKER="$candidate"
		break
	fi
done
[[ -n "$MARKER" ]] || die "missing $WP_ROOT/.staging-import-enabled — refusing to import (staging lock)"

STAMP_DIR="$(dirname "$MARKER")"
STAMP="$STAMP_DIR/.staging-import-sha"

[[ -f "$DUMP" ]] || die "missing $DUMP"
command -v wp >/dev/null 2>&1 || die "wp-cli not found on PATH (run from public_html, or use the Cloudways app SSH user)"

wp_cmd() {
	command wp --path="$WP_ROOT" --skip-plugins --skip-themes "$@"
}

if ! wp_cmd core is-installed >/dev/null 2>&1; then
	die "WordPress is not installed at $WP_ROOT"
fi

DUMP_SHA="$(file_sha "$DUMP")"
if [[ "$FORCE" -eq 0 && -f "$STAMP" ]] && [[ "$(cat "$STAMP")" == "$DUMP_SHA" ]]; then
	log "dump unchanged (sha $DUMP_SHA) — skip. Use --force to import anyway."
	exit 0
fi

WP_HOME="$(wp_cmd config get WP_HOME --type=constant 2>/dev/null || true)"
if [[ -z "$WP_HOME" ]]; then
	WP_HOME="$(wp_cmd option get home)"
fi
WP_HOME="${WP_HOME%/}"

[[ -n "$WP_HOME" ]] || die "could not read WP_HOME or option home"
case "$WP_HOME" in
	*localhost*) die "target URL is still localhost ($WP_HOME) — abort" ;;
esac

TMP="$(mktemp "${TMPDIR:-/tmp}/cloudways-backup.XXXXXX.sql")"
trap 'rm -f "$TMP"' EXIT

log "stripping DATABASE/USE from dump"
strip_dump "$DUMP" >"$TMP"

if grep -E '^(\/\*![0-9]+[[:space:]]+)?DROP DATABASE|^CREATE DATABASE|^USE[[:space:]]' "$TMP" >/dev/null; then
	die "stripped dump still contains DROP/CREATE DATABASE or USE"
fi

log "importing into Cloudways database ($WP_ROOT)"
wp_cmd db import "$TMP"

log "search-replace $FROM_URL → $WP_HOME"
wp_cmd search-replace "$FROM_URL" "$WP_HOME" --all-tables --report-changed-only

# Load plugins/themes so CPT rewrite rules are registered (skip-* hides them).
command wp --path="$WP_ROOT" rewrite flush >/dev/null
command wp --path="$WP_ROOT" cache flush >/dev/null 2>&1 || true

printf '%s\n' "$DUMP_SHA" >"$STAMP"
log "done (sha $DUMP_SHA)"
