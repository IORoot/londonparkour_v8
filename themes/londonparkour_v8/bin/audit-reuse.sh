#!/usr/bin/env bash
#
# Reuse audit — enforces the one-file-per-piece-of-HTML rule.
#
# A design-system element's markup lives in exactly one file under parts/.
# Blocks, templates and components compose it via lp_part(); nothing else may
# retype it. This script fails if that rule is broken.
#
# Usage:  bin/audit-reuse.sh          (from the theme directory)
# Exit:   0 clean, 1 violations found

set -uo pipefail
cd "$(dirname "$0")/.." || exit 1

fail=0

# ── Markup that must come from parts/elements/, never be hand-written ────────
# Each rule: <label>|<regex>|<the part that owns it>|<file exempt from it, optional>
# The exemption exists because media-photo.php is itself under parts/components,
# the directory this audit scans — it DEFINES <img>, so only that one file may.
# parts/site/nav.php is exempt from the <button> rule alone, and only that
# rule: its three buttons cannot be button.php. Two are Tailwind Plus Elements
# invokers (`popovertarget`, `command`/`commandfor`) which only work on a real
# <button>, and the third is a 60px icon-only bar cell — button.php's `icon`
# variant is `btn btn-primary btn-square`, the exact chrome these must not have.
# It is NOT exempt from the <svg>/<use>/<img>/btn-class rules.
rules=(
  'raw <button>|<button[[:space:]>]|parts/elements/button.php|parts/site/nav.php'
  'daisyUI btn class|class="[^"]*\bbtn\b|parts/elements/button.php|'
  'hand-rolled separator|role="separator"|parts/elements/rule.php|'
  'inline <svg>|<svg[[:space:]>]|lp_icon() in app/includes/html.php|'
  'raw <use> sprite ref|<use[[:space:]]|lp_icon() in app/includes/html.php|'
  'raw <img>|<img[[:space:]>]|parts/components/media-photo.php|parts/components/media-photo.php'
)

# Directories that compose, and must therefore never define.
targets=(blocks template-parts)
for t in blocks parts/components parts/site; do [ -d "$t" ] && targets+=("$t"); done
# parts/components compose primitives too, but parts/elements and parts/brand
# are where markup is allowed to be defined.
scan=$(printf '%s\n' "${targets[@]}" | sort -u | tr '\n' ' ')

for rule in "${rules[@]}"; do
  IFS='|' read -r label regex owner exempt <<< "$rule"
  hits=$(grep -rnE "$regex" $scan --include='*.php' 2>/dev/null \
         | grep -v 'parts/elements/' | grep -v 'parts/brand/' || true)
  # Drop docblock lines. A part's docblock names the very markup it forbids
  # ("never emit a raw <svg>"), and a rule that punishes accurate documentation
  # gets documentation written around it instead. Comments emit nothing.
  hits=$(printf '%s' "$hits" | grep -vE '^[^:]+:[0-9]+:[[:space:]]*\*' || true)
  [ -n "$exempt" ] && hits=$(printf '%s' "$hits" | grep -v "$exempt" || true)
  if [ -n "$hits" ]; then
    fail=1
    echo "✗ $label — belongs in $owner"
    echo "$hits" | sed 's/^/    /'
    echo
  fi
done

# ── Tailwind literal audit ──────────────────────────────────────────────────
# Tailwind v4 text-scans source, so a class NAME assembled from fragments is
# never seen by the scanner and compiles to nothing.
#
# Echoing a whole class string from a lookup is correct and must not be flagged
# — that is the pattern every part here uses. What is broken is a literal
# fragment glued to a PHP expression:
#
#   class="<?php echo lp_classes( 'px-6', $spacing ); ?>"   ← fine
#   class="px-6 <?php echo $tone; ?>"                        ← fine
#   class="btn-<?php echo $variant; ?>"                      ← BROKEN
#   $c = 'text-' . $tone;                                    ← BROKEN
#
# So: flag a `<?php` inside a class attribute only when the character before it
# is neither a quote nor a space, i.e. it is welded to a partial class name.
glued=$(grep -rnE 'class="[^"]*[^ "]<\?(php|=)' blocks parts template-parts --include='*.php' 2>/dev/null || true)

# And flag string concatenation that starts a Tailwind-shaped prefix.
concat=$(grep -rnE "'(text|bg|border|fill|stroke|ring|from|to|via|p|m|px|py|pt|pb|pl|pr|mx|my|w|h|min-w|max-w|gap|grid-cols|col-span|flex|items|justify|rounded|shadow|opacity|scale|translate)-'[[:space:]]*\." \
         blocks parts template-parts --include='*.php' 2>/dev/null || true)

if [ -n "$glued$concat" ]; then
  fail=1
  echo "✗ Tailwind class name built from fragments — will compile to nothing"
  echo "  (echo a WHOLE literal string from a lookup array instead)"
  [ -n "$glued" ]  && echo "$glued"  | sed 's/^/    /'
  [ -n "$concat" ] && echo "$concat" | sed 's/^/    /'
  echo
fi

if [ "$fail" -eq 0 ]; then
  echo "✓ reuse audit clean — no element markup duplicated outside parts/"
fi

exit "$fail"
