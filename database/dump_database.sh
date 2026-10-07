#!/usr/bin/env bash
# Dump the WordPress database to database/backup_YYYYMMDD.sql
#
# Local Docker:  mysqldump inside MARIADB_CONTAINER_REF (.env)
# Cloudways:     wp db export (wp-config.php credentials, no Docker)
#
# Safe to invoke from any cwd.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="$REPO_ROOT/.env"
DUMP_FILENAME="backup_$(date +%Y%m%d).sql"
DUMP_PATH="$SCRIPT_DIR/$DUMP_FILENAME"
PATH="/usr/local/bin:/usr/bin:/bin:${PATH:-}"

FAIL_ENV=1
FAIL_CONTAINER=2
FAIL_USERNAME=3
FAIL_PASSWORD=4
FAIL_DATABASE=5
FAIL_CONTAINER_EXISTS=6
FAIL_MYSQLDUMP=7
FAIL_DUMP_DB=8
FAIL_WP=9

TEXT_RED_500='\e[38;2;239;68;68m'
TEXT_EMERALD_500='\e[38;2;16;185;129m'
TEXT_GRAY_500='\e[38;2;107;114;128m'
RESET_TEXT='\e[39m'
ICON_TICK=✅
ICON_BARREL=🛢

die() {
	printf "\n${TEXT_RED_500}Error: %s${RESET_TEXT}\n" "$*" >&2
	exit "$FAIL_DUMP_DB"
}

load_env() {
	if [ -f "$ENV_FILE" ]; then
		set -o allexport
		# shellcheck disable=SC1090
		source "$ENV_FILE"
		set +o allexport
	fi
}

docker_ready() {
	command -v docker >/dev/null 2>&1 || return 1
	[ -n "${MARIADB_CONTAINER_REF:-}" ] || return 1
	docker ps -a --format '{{.Names}}' | grep -qx "$MARIADB_CONTAINER_REF" || return 1
	docker exec "$MARIADB_CONTAINER_REF" mysqldump --version >/dev/null 2>&1
}

resolve_wp_root() {
	if [[ -f "$REPO_ROOT/wp-config.php" ]]; then
		printf '%s\n' "$REPO_ROOT"
	elif [[ -f "$REPO_ROOT/../public_html/wp-config.php" ]]; then
		cd "$REPO_ROOT/../public_html" && pwd
	fi
}

find_wp_bin() {
	local candidate
	for candidate in wp /usr/local/bin/wp /usr/bin/wp; do
		if command -v "$candidate" >/dev/null 2>&1; then
			command -v "$candidate"
			return 0
		elif [[ -x "$candidate" ]]; then
			printf '%s\n' "$candidate"
			return 0
		fi
	done
	return 1
}

dump_via_docker() {
	if [ -z "${MARIADB_CONTAINER_REF:-}" ]; then
		printf "\n${TEXT_RED_500}Error: MARIADB_CONTAINER_REF variable not set in .env${RESET_TEXT}\n"
		exit "$FAIL_CONTAINER"
	fi
	if [ -z "${MARIADB_ROOT_USERNAME:-}" ]; then
		printf "\n${TEXT_RED_500}Error: MARIADB_ROOT_USERNAME variable not set in .env${RESET_TEXT}\n"
		exit "$FAIL_USERNAME"
	fi
	if [ -z "${MARIADB_ROOT_PASSWORD:-}" ]; then
		printf "\n${TEXT_RED_500}Error: MARIADB_ROOT_PASSWORD variable not set in .env${RESET_TEXT}\n"
		exit "$FAIL_PASSWORD"
	fi
	if [ -z "${MARIADB_DATABASE_NAME:-}" ]; then
		printf "\n${TEXT_RED_500}Error: MARIADB_DATABASE_NAME variable not set in .env${RESET_TEXT}\n"
		exit "$FAIL_DATABASE"
	fi

	if ! docker ps -a --format '{{.Names}}' | grep -qx "$MARIADB_CONTAINER_REF"; then
		printf "\n${TEXT_RED_500}Error: Container %s does not exist${RESET_TEXT}\n" "$MARIADB_CONTAINER_REF"
		exit "$FAIL_CONTAINER_EXISTS"
	fi

	if ! docker exec "$MARIADB_CONTAINER_REF" mysqldump --version >/dev/null 2>&1; then
		printf "\n${TEXT_RED_500}Error: Container %s does not have a mysqldump command${RESET_TEXT}\n" "$MARIADB_CONTAINER_REF"
		exit "$FAIL_MYSQLDUMP"
	fi

	if ! docker exec "$MARIADB_CONTAINER_REF" \
		mysqldump --add-drop-database -u"$MARIADB_ROOT_USERNAME" -p"$MARIADB_ROOT_PASSWORD" --single-transaction --databases "$MARIADB_DATABASE_NAME" \
		> "$DUMP_PATH"; then
		rm -f "$DUMP_PATH"
		printf "\n${TEXT_RED_500}Error: Failed to dump database to %s${RESET_TEXT}\n" "$DUMP_PATH"
		printf "${TEXT_GRAY_500}Check .env: MARIADB_ROOT_USERNAME must be root (not the wordpress app user).${RESET_TEXT}\n"
		exit "$FAIL_DUMP_DB"
	fi
}

dump_via_wpcli() {
	local wp_root wp_bin
	wp_root="$(resolve_wp_root)"
	[ -n "$wp_root" ] || die "cannot find wp-config.php (looked in $REPO_ROOT and $REPO_ROOT/../public_html)"

	wp_bin="$(find_wp_bin)" || {
		printf "\n${TEXT_RED_500}Error: wp-cli not found (looked on PATH, /usr/local/bin/wp, /usr/bin/wp)${RESET_TEXT}\n"
		exit "$FAIL_WP"
	}

	if ! "$wp_bin" --path="$wp_root" --skip-plugins --skip-themes core is-installed >/dev/null 2>&1; then
		die "WordPress is not installed at $wp_root"
	fi

	if ! "$wp_bin" --path="$wp_root" --skip-plugins --skip-themes db export "$DUMP_PATH" --add-drop-table >/dev/null; then
		rm -f "$DUMP_PATH"
		die "wp db export failed"
	fi
}

main() {
	printf "\n%s  %s\n" "$ICON_BARREL" "Dumping database"
	mkdir -p "$SCRIPT_DIR"
	load_env

	if docker_ready; then
		dump_via_docker
	else
		dump_via_wpcli
	fi

	[ -s "$DUMP_PATH" ] || die "dump file is missing or empty: $DUMP_PATH"
	printf "\n%s  ${TEXT_EMERALD_500}%s${RESET_TEXT}\n\n" "$ICON_TICK" "Database dumped to $DUMP_PATH"
}

main
