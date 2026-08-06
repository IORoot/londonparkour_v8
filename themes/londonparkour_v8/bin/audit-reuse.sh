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
#
# 404.php is exempt from the <button> rule on the same terms, for a reason that
# was measured rather than assumed. Its search submit is the ONLY instance of
# that shape in the whole design — a grep of the Storybook finds the dark-band
# bordered block at src/stories/Pages/NotFound/NotFound.js:123 and nowhere else,
# and every Button the remaining pages ask for is `primary`, `inverse` or
# `icon`, all of which button.php already ships. Promoting a one-page shape
# would be building ahead, so the shape stays here and the rule steps around it.
# If a second file ever needs it, that is the moment it becomes a variant.
rules=(
  'raw <button>|<button[[:space:]>]|parts/elements/button.php|parts/site/nav.php|404\.php'
  'daisyUI btn class|class="[^"]*\bbtn\b|parts/elements/button.php|'
  'hand-rolled separator|role="separator"|parts/elements/rule.php|'
  'inline <svg>|<svg[[:space:]>]|lp_icon() in app/includes/html.php|'
  'raw <use> sprite ref|<use[[:space:]]|lp_icon() in app/includes/html.php|'
  'raw <img>|<img[[:space:]>]|parts/components/media-photo.php|parts/components/media-photo.php'
)

# Directories that compose, and must therefore never define.
targets=(blocks template-parts)
for t in blocks parts/components parts/site inc templates; do [ -d "$t" ] && targets+=("$t"); done
# The theme-root templates compose too, and until now went unscanned — which is
# how 404.php's hand-built controls passed unremarked. The glob expands here, so
# every page template added later is covered without touching this line.
targets+=(*.php)
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
  # -E, not -v's default BRE: a rule may exempt more than one file, and it
  # separates them with the same `|` the rule fields use, so `read` hands the
  # whole alternation over as the final field.
  [ -n "$exempt" ] && hits=$(printf '%s' "$hits" | grep -vE "$exempt" || true)
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
# Same reach as the markup rules above, plus parts/elements and parts/brand:
# defining markup there is allowed, building a class name from fragments is not.
lit_scan="$scan parts"

# `parts` overlaps the `parts/components` already in $scan, so the same hit can
# come back twice; sort -u makes a failure report list each line once.
glued=$(grep -rnE 'class="[^"]*[^ "]<\?(php|=)' $lit_scan --include='*.php' 2>/dev/null | sort -u || true)

# And flag string concatenation that starts a Tailwind-shaped prefix.
concat=$(grep -rnE "'(text|bg|border|fill|stroke|ring|from|to|via|p|m|px|py|pt|pb|pl|pr|mx|my|w|h|min-w|max-w|gap|grid-cols|col-span|flex|items|justify|rounded|shadow|opacity|scale|translate)-'[[:space:]]*\." \
         $lit_scan --include='*.php' 2>/dev/null | sort -u || true)

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
