#!/usr/bin/env bash
# Import database/backup.sql into the Cloudways *staging* database.
#
# Cloudways layout (this is why public_html is not a git repo):
#   ~/applications/<app>/git_repo      ← git clone (often root-owned)
#   ~/applications/<app>/public_html   ← WordPress + copied repo files
#
# Safe to invoke from any cwd (cron, SSH home, etc.) — paths are taken from
# this file, then the script cds into WordPress before calling wp-cli.
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

# Always resolve from this file, never from $PWD — cron and SSH may start anywhere.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DUMP="$REPO_ROOT/database/backup.sql"
LOG_FILE="$SCRIPT_DIR/db.log"
FROM_URL="http://localhost:8102"
FORCE=0
PATH="/usr/local/bin:/usr/bin:/bin:${PATH:-}"

usage() {
	cat <<'EOF'
Usage: /path/to/database/cloudways_load.sh [--force]

Import database/backup.sql into this WordPress database (Cloudways staging).
May be run from any directory. Refuses unless .staging-import-enabled exists
in public_html (or next to it, if that directory is writable).
Skips when the dump SHA matches .staging-import-sha, unless --force.
Appends one line per run to database/db.log (datetime, success|failure, message).
EOF
}

# One line: "YYYY-MM-DD HH:MM:SS success|failure message"
LOGGED=0
write_log() {
	local status="$1"
	shift
	local msg="$*"
	msg="${msg//$'\n'/ }"
	msg="${msg//$'\r'/ }"
	printf '%s %s %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$status" "$msg" >>"$LOG_FILE" 2>/dev/null || true
	LOGGED=1
}

die() {
	write_log failure "$*"
	printf 'cloudways_load: %s\n' "$*" >&2
	exit 1
}

log() {
	printf 'cloudways_load: %s\n' "$*"
}

trap '[[ ${LOGGED:-0} -eq 0 ]] && write_log failure "command failed at line $LINENO"' ERR

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
cd "$WP_ROOT" || die "cannot cd to $WP_ROOT"
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

WP_BIN=""
for candidate in wp /usr/local/bin/wp /usr/bin/wp; do
	if command -v "$candidate" >/dev/null 2>&1; then
		WP_BIN="$(command -v "$candidate")"
		break
	elif [[ -x "$candidate" ]]; then
		WP_BIN="$candidate"
		break
	fi
done
[[ -n "$WP_BIN" ]] || die "wp-cli not found (looked on PATH, /usr/local/bin/wp, /usr/bin/wp)"

wp_cmd() {
	"$WP_BIN" --path="$WP_ROOT" --skip-plugins --skip-themes "$@"
}

if ! wp_cmd core is-installed >/dev/null 2>&1; then
	die "WordPress is not installed at $WP_ROOT"
fi

DUMP_SHA="$(file_sha "$DUMP")"
if [[ "$FORCE" -eq 0 && -f "$STAMP" ]] && [[ "$(cat "$STAMP")" == "$DUMP_SHA" ]]; then
	log "dump unchanged (sha $DUMP_SHA) — skip. Use --force to import anyway."
	write_log success "dump unchanged (sha $DUMP_SHA) — skip"
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
import_out="$(wp_cmd db import "$TMP" 2>&1)" || die "db import failed: $import_out"

log "search-replace $FROM_URL → $WP_HOME"
replace_out="$(wp_cmd search-replace "$FROM_URL" "$WP_HOME" --all-tables --report-changed-only 2>&1)" || die "search-replace failed: $replace_out"

# Load plugins/themes so CPT rewrite rules are registered (skip-* hides them).
"$WP_BIN" --path="$WP_ROOT" rewrite flush >/dev/null
"$WP_BIN" --path="$WP_ROOT" cache flush >/dev/null 2>&1 || true

printf '%s\n' "$DUMP_SHA" >"$STAMP"
log "done (sha $DUMP_SHA)"
write_log success "imported sha $DUMP_SHA ($FROM_URL → $WP_HOME)"
