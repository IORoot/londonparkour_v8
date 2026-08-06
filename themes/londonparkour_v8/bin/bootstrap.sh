#!/usr/bin/env bash
#
# Bring a clean Docker environment to a working LondonParkour install.
#
# Idempotent — safe to re-run. It never touches content; see bin/seed.php for
# that (which this script deliberately does NOT run).
#
# Usage:  themes/londonparkour_v8/bin/bootstrap.sh
#         (or from the repo root: ./themes/londonparkour_v8/bin/bootstrap.sh)

set -euo pipefail

# Repo root = three levels up from this script (bin/ -> theme -> themes -> root).
ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
cd "$ROOT"

SITE_URL="http://localhost:8102"
SITE_TITLE="London Parkour"
ADMIN_USER="admin"
ADMIN_PASS="admin"
ADMIN_EMAIL="hello@londonparkour.com"
THEME="londonparkour_v8"

say() { printf '\n\033[1m▸ %s\033[0m\n' "$1"; }

say "Starting containers"
docker compose up -d >/dev/null 2>&1

wp() { docker compose exec -T cli wp --url="$SITE_URL" "$@"; }

say "Waiting for the database"
for _ in $(seq 1 30); do
	if wp db check >/dev/null 2>&1 || wp core version >/dev/null 2>&1; then break; fi
	sleep 2
done

say "Installing WordPress core"
if wp core is-installed >/dev/null 2>&1; then
	echo "  already installed — skipping"
else
	wp core install \
		--url="$SITE_URL" \
		--title="$SITE_TITLE" \
		--admin_user="$ADMIN_USER" \
		--admin_password="$ADMIN_PASS" \
		--admin_email="$ADMIN_EMAIL" \
		--skip-email
	echo "  installed — login $ADMIN_USER / $ADMIN_PASS"
fi

say "Activating the theme"
wp theme activate "$THEME"

say "Plugins"
# Classic Editor is not vendored in plugins/ — pull it from the .org repo.
if wp plugin is-installed classic-editor >/dev/null 2>&1; then
	wp plugin activate classic-editor || true
else
	wp plugin install classic-editor --activate
fi
# ACF Pro is vendored in plugins/ and only needs activating.
wp plugin activate advanced-custom-fields-pro || echo "  ! ACF Pro did not activate — check plugins/advanced-custom-fields-pro"

say "Permalinks"
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard

say "Navigation menus"
for pair in "Primary:primary" "Footer:footer"; do
	name="${pair%%:*}"; loc="${pair##*:}"
	if ! wp menu list --format=csv --fields=name 2>/dev/null | grep -qx "$name"; then
		wp menu create "$name"
	fi
	wp menu location assign "$name" "$loc" || true
done

say "Front page"
if ! wp post list --post_type=page --name=home --format=count 2>/dev/null | grep -q '^1$'; then
	wp post create --post_type=page --post_title='Home' --post_name=home --post_status=publish
fi
HOME_ID="$(wp post list --post_type=page --name=home --field=ID --format=csv | head -1)"
if [ -n "$HOME_ID" ]; then
	wp option update show_on_front page
	wp option update page_on_front "$HOME_ID"
	echo "  front page = #$HOME_ID"
fi

# With a static front page and no posts page, WordPress has nowhere to show
# the blog index — home.php is simply never reached. This page is structure
# rather than content: it holds no copy, it only gives the archive a route.
say "Posts page"
if ! wp post list --post_type=page --name=blog --format=count 2>/dev/null | grep -q '^1$'; then
	wp post create --post_type=page --post_title='Blog' --post_name=blog --post_status=publish
fi
BLOG_ID="$(wp post list --post_type=page --name=blog --field=ID --format=csv | head -1)"
if [ -n "$BLOG_ID" ]; then
	wp option update page_for_posts "$BLOG_ID"
	echo "  posts page = #$BLOG_ID"
fi

say "Discourage search engines on local"
wp option update blog_public 0

say "Building ACF field groups from PHP"
wp lp acf:build || echo "  ! acf:build unavailable — is the theme active and ACF Pro on?"

say "Done"
echo "  Site:  $SITE_URL"
echo "  Admin: $SITE_URL/wp-admin  ($ADMIN_USER / $ADMIN_PASS)"
echo
echo "  Content is NOT seeded. For demo content and the QA page, run:"
echo "    bin/wp lp seed"
echo "  See bin/README.md for the two-developer database contract."
