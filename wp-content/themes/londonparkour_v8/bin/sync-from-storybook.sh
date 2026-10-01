#!/usr/bin/env bash
#
# Token sync — copies the design tokens from the Storybook book into the theme.
#
# Storybook (ldnpark2601) owns the tokens: src/assets/styles/{themes,_vars}.
# This mirrors those two directories into assets/css/. Tokens only — markup
# still moves through the port workflow and docs/PORT-BRIEF.md.
#
# WP-owned overrides are excluded and never overwritten:
#   _vars/font.css — body/label use system fonts so LCP text doesn't wait on
#                    a webfont (51e56bd8, bea56c6a). Storybook still says Inter.
#
# Usage:  bin/sync-from-storybook.sh [--check]
#   --check   report what would change, write nothing; exit 1 if out of sync
# Env:    STORYBOOK=/abs/path  (default /Users/wearebold/Sites/Storybook/ldnpark2601)

set -euo pipefail
cd "$(dirname "$0")/.." || exit 1

src="${STORYBOOK:-/Users/wearebold/Sites/Storybook/ldnpark2601}/src/assets/styles"
[ -d "$src/themes" ] && [ -d "$src/_vars" ] || { echo "Storybook styles not found at $src" >&2; exit 2; }

dry=""
[ "${1:-}" = "--check" ] && dry="--dry-run"

changes=""
for dir in themes _vars; do
  # -c compares content; lines starting "." are attribute-only (mtime), not changes
  out=$(rsync -rc --delete --itemize-changes $dry \
    --exclude 'font.css' \
    "$src/$dir/" "assets/css/$dir/" | grep -v '^\.' || true)
  [ -n "$out" ] && changes+="$(sed "s|^|$dir: |" <<<"$out")"$'\n'
done

if [ -z "$changes" ]; then
  echo "✓ tokens in sync with $src"
  exit 0
fi

printf '%s' "$changes"
if [ -n "$dry" ]; then
  echo "✗ out of sync (run without --check to apply)"
  exit 1
fi
echo "✓ synced — run npm run build"
