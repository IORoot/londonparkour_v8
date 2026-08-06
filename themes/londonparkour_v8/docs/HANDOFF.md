# Handoff — Storybook → WordPress port

State: Phases 0–4 complete. Phase 5 has the site chrome, `front-page.php`,
`404.php` and `page.php` done, with 11 page templates left — see "Phase 5b".
Most of Phase 6 is done too, pulled forward: `wp lp seed`, `bin/README.md` and
the theme `README.md` all exist. Everything needed to continue is on disk; this
document is the map.

**Look at the site before you write anything.** `page.php` now renders
`page_sections`, and `bin/wp lp seed` builds `/blocks-qa/` — sixteen rows, every
block once from its `example.json` and the six source-backed blocks a second
time reading real CPT records. That page found five genuine bugs on its first
run (PORT-FINDINGS §13); use it to eyeball any block while porting a template.

    bin/bootstrap.sh && bin/wp lp seed     # from a clean database

The database is disposable and is never shared between developers — the content
definition is code, in `bin/demo-content/`, `bin/demo-media/` and each block's
`example.json`. `bin/README.md` is the contract; read it before touching content.

## Read these first

| File | What it owns |
|---|---|
| `docs/PORT-BRIEF.md` | **The contract.** Hand it verbatim to every porting agent |
| `docs/PORT-FINDINGS.md` | Discrepancies found, deliberately not fixed. Includes a real contrast bug and the licensing items |
| `~/.claude/plans/we-need-to-port-replicated-backus.md` | The approved plan: scope, decisions, block→CPT matrix, verification list |

Source of truth for the design system:
`/Users/wearebold/Sites/Storybook/ldnpark2601` — clean at commit `9e0dffc`.
Its `CLAUDE.md` is the conventions doc; `docs/phase7/surface-axis.md` is
canonical for colour and must never be re-derived.

## Environment

Repo is `wp-content`-shaped: `docker-compose.yml` + `plugins/` + `themes/`.

```bash
docker compose up -d                       # from repo root
themes/londonparkour_v8/bin/bootstrap.sh   # idempotent, safe to re-run
```

- Site: **http://localhost:8102**, admin `admin` / `admin`
- WP-CLI is NOT on the host — it runs in the `cli` sidecar. Use `bin/wp <args>`
- ACF Pro + Classic Editor are active; theme is active; front page is set

`docker-compose.yml` was modified during Phase 0: WP core moved to a named
`wp_core` volume (so the CLI sidecar can see it) and a persistent `cli` service
was added. Done before install, so nothing was lost.

## Done

**Phase 0 — scaffold.** `_tw` classic skeleton, PostCSS/esbuild replaced by
Vite (`@tailwindcss/vite`, manifest, `assets/dist`, ES2022). Storybook CSS
copied wholesale — all four `parkour-*` themes compile; only the `@source`
globs changed. JS copied (`app.js`, `motion/`, `utils/`, `elements/`), Three.js
stripped, real `DOMContentLoaded` boot replaces the Storybook decorator.
Sprites extracted from the 425 KB `preview-head.html` to
`assets/img/icons.svg` (328 symbols) + `glyphs.svg` (67), served externally.

**Phase 1 — the gate.** 4 CPTs (`lp_class`, `lp_coach`, `lp_location`,
`lp_tutorial`), 2 taxonomies (`lp_level`, `lp_series`), the shared field
vocabulary in `app/setup/acf-fields.php`, `lp_resolve_source()`, and
`wp lp acf:build` with a UI-consistency assertion that runs on every build.

**Phase 2 — primitives.** 13 parts: elements badge, button, chip, glyph-label,
nav-link, rule, status, view-tab; forms field, select, text-area; brand logo, glyph.

**Phase 3a — shared atoms (GATE, done).** Factored from a scan of all 34 source
files (`docs/CONSOLIDATION.md`), 4 more parts:
`elements/text-link.php` (4 variants), `elements/chevron.php` (6 variants),
`elements/icon-circle.php` (2 variants), `components/media-photo.php` (5 scrims + none).
Deliberately NOT factored: `cta-pair` (a flex row around two things that are already
parts) and every layout shell — see the NOT RECOMMENDED section of CONSOLIDATION.md.

**Images are responsive.** `parts/components/media-photo.php` uses
`wp_get_attachment_image()` (srcset + sizes for free), supports `sizes`,
`<picture>` art direction via `sources`, and defaults masthead/hero to
eager + fetchpriority=high as the LCP element. Exercised end-to-end against a
real attachment before Phase 3; three defects fixed at that point — an explicit
`alt => ''` was silently replaced by the library alt, `fetchpriority="auto"` was
emitted on every image, and a `formats` arg advertised AVIF/WebP `<source>`s
whose srcset was the original JPEG. `formats` is gone: core generates no
alternates, and a conversion plugin rewrites URLs itself. Crops in `app/setup/theme.php`
are registered in RATIO-MATCHED FAMILIES (`lp_wide_sm|lp_wide|lp_wide_lg`,
`lp_portrait_*`, `lp_thumb*`) — a lone hard crop yields an EMPTY srcset, so
never add an orphan size. After changing sizes: `bin/wp media regenerate --yes`.

**Phase 3 — components (done).** All 24 under `parts/components/`. Ported
against the shared atoms: no component retypes a button, chevron, badge, chip,
status, glyph-label, text-link, icon-circle, avatar or `<img>` — `bin/audit-reuse.sh`
now enforces the `<img>` rule too. Four element changes were needed and are
recorded in `docs/PORT-FINDINGS.md` §7: `button.php` gained `band`,
`view-tab.php` gained `rich`, `chevron.php` gained `media_card_static`, and
`elements/avatar-initial.php` is new (shared by `byline` and `blog-card`).

`docs/CONSOLIDATION.md` §2d is **not** implemented — its premise is wrong and
the three hand-rolled status dots stay hand-rolled. PORT-FINDINGS §6 has the
measurements; this matters for `SiteNav` in Phase 5.

**Phase 4 — blocks (done, all 10).** `train-in-person`, `marquee`, `statement`,
`cta`, `hero`, `coaches`, `classes`, `locations`, `private-coaching`, `pricing`
— markup + `fields.php` + `example.json` each, every one verified with
`bin/wp lp render`. Every fully-literal class string in the four Phase 4 blocks
was diffed against its Storybook source and matches byte-for-byte; the only
rendered classes not present verbatim in a source file are the runtime
concatenations the source itself builds (the Classes column head, Pricing's
`h-[3px] ${bar}` and `shrink-0 ${wash}`) and the markup emitted by Phase 3
parts. `GiftCardUpsell` and `SiteFooter` exist in the Storybook's `Blocks/` but
are not in the block scope — SiteFooter is Phase 5, GiftCardUpsell is not in the
plan's ten.

Phase 4's own findings are PORT-FINDINGS §8 and §9. The one open coordinator
call: `Classes` is the only `lp_class` block passing `lp_field_source()`'s
`taxonomy` option, so `Hero` and `CTA` still have no level filter.

Building the second source-backed block exposed a real conflict in the gate:
`lp_field_source()` takes a `$label` precisely so an editor reads "Sites" on
TrainInPerson and "Session" on CTA, and its `multiple` option switches
`source_items` between `relationship` and `post_object` — but
`lp_assert_field_consistency()` compared both and failed. `source_items` and
`source_manual` are now exempt from that comparison and nothing else is;
`source`, `source_limit` and `source_terms` are still checked, so the control
itself cannot drift. Re-verified by injection: a relabelled `note` still fails.

`cta` is the one source-backed block using `multiple => false` — its panel shows
ONE session, so it reads `lp_resolve_source()` for row [0].

The gate then earned its keep: `coaches` first called its lead-coach group
`lead`, which `hero` already uses for its standfirst. Two different meanings
behind one name is exactly the drift the assertion exists to stop — renamed to
`lead_coach`. Expect more of these as the last four blocks land; the fix is
always to rename the newcomer, never to loosen the check.

**CTAs are ACF Link fields.** `lp_field_action()` emits one ACF `link` field plus a
style button_group — no separate label text field; the Link's own title IS the label.
`lp_action()` normalises it and also accepts a bare Link value.

## Key mechanisms

- **`lp_part( 'elements/button', $args )`** — the ONLY way to emit an element.
  One file per piece of HTML; `bin/audit-reuse.sh` fails the build otherwise.
- **`lp_icon( $id, $classes )`** — sprite icons. Never a raw `<svg>` outside
  `parts/brand/` (Logo and the 16 animatable glyphs are inline by design — the
  `glyph-assembly` motion effect animates their individual `<path>` nodes,
  which `<use>` cannot expose).
- **`lp_classes( ...$strings )`** — joins WHOLE literal class strings. Never
  builds one; Tailwind v4 text-scans source.
- **Copy defaults live in the partial, never in `fields.php`.** Each block
  defaults its copy once, in its `$args['x'] ?? '…'` fallback, taken from the
  source component's own `init()` defaults. An ACF `default_value` for the same
  string gives it two homes that drift, and pre-fills every new block with copy
  an editor then deletes. CONTROL defaults are the exception and do belong in
  `fields.php` — a `button_group`/`select`/`number` with none renders unset, so
  `source`, `source_limit`, `style`, `spacing_*` and Marquee's direction/speed
  keep theirs. Full note in `app/setup/acf-fields.php`'s header.
- **`lp_render_sections()`** — dispatches ACF Flexible Content rows to
  `blocks/{layout}/{layout}.php`. No registry to edit.
- **`bin/wp lp render <layout>`** — renders one block from
  `blocks/<slug>/example.json` without touching the database. This is how to
  verify a block port.
- **`bin/wp lp part <slug> [--args=<json>]`** — the same for any partial, which
  is how Phase 3 was verified (components have no ACF layout, so `lp render`
  cannot reach them). e.g.
  `bin/wp lp part components/byline --args='{"size":"sm","surface":"accent"}'`

## Reference implementations — copy these

- `parts/elements/button.php` — variant lookup map, `<a>` vs `<button>`, a11y
- `parts/elements/rule.php` — simplest tone lookup
- `blocks/train-in-person/` — a complete block: markup, `fields.php`,
  `example.json`

## Remaining

| Phase | Work |
|---|---|
| **5** | 11 page templates — see "Phase 5b" below. Chrome, `front-page.php`, `404.php` and `page.php` are done |
| **6** | **Mostly done.** `wp lp seed` (in `app/setup/seed.php`, not `bin/seed.php` — it needs WP bootstrapped), `bin/README.md` and `README.md` all written and run. Left: homepage seeding, deliberately deferred — the nine-row order is still recorded in `front-page.php`'s docblock and seed can gain a `--homepage` flag without redesign |
| **7** | Full verification list; consolidation pass |

All ten blocks are done. Six take the CPT source control (Hero, Classes,
Coaches, Locations, TrainInPerson, CTA); four are repeater-only (Marquee,
Statement, PrivateCoaching, Pricing); Statement has no list. The matrix is in
the plan.

Worked examples, if a later phase needs one: `blocks/hero/` is a source-backed
block whose rows are a ported component, `blocks/train-in-person/` projects its
rows inline, `blocks/statement/` is repeater-only. `blocks/cta/` is the only
`multiple => false`; `blocks/coaches/` and `blocks/locations/` each pair a
source list with a separate single-entity group; `blocks/pricing/` is the only
block with a nested repeater (each tier's cells, keyed back to the row rail).

## Verify anything

```bash
cd themes/londonparkour_v8
php -l <file>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # must print Success
bin/wp lp render <layout_name>
npm run build
```

`bin/audit-reuse.sh` is verified by injection — feeding it
`class="btn-<?php echo $v; ?>"`, `'text-' . $tone` or a raw `<img>` makes it
fail, and clean code passes. Trust it.

It also now ignores docblock lines: a part's own documentation names the markup
it forbids ("never emit a raw `<svg>`"), and a rule that punishes accurate
documentation just gets documentation written around it. Comments emit nothing.
Verified the same way — real markup one line below a mentioning comment still
fails the audit.

Render-sweep every partial at once (catches PHP notices no single render shows):

```bash
for f in parts/components/*.php; do
  bin/wp lp part "components/$(basename "$f" .php)" >/dev/null || echo "FAIL $f"
done
```

**Phase 5a — the site chrome (done).** `parts/site/nav.php` and
`parts/site/footer.php`, wired from `header.php`/`footer.php`. All three
compliance fixes this document carried forward are settled — see PORT-FINDINGS
§10; the short version is that `band` genuinely does not fit the desktop CTA,
only ONE SiteNav CTA was ever inline, and the missing brand marks were added to
the sprite (331 symbols now) rather than exempting the footer.

Menus: `primary` and `footer` locations were already registered in Phase 0.
`app/includes/menus.php` maps them to the shapes the two partials take —
`lp_menu_links()` (flat) and `lp_menu_columns()` (heading + children). Both
return an empty array when no menu is assigned, and each partial then falls back
to the Storybook's own copy, so an un-configured install renders the design
rather than an empty bar. Which nav item opens the Classes drop panel is a menu
item CSS class, `has-panel`, and only the first one flagged is used.

`bin/audit-reuse.sh` now also scans `parts/site` — it did not before, which
would have let a raw `<svg>` through in exactly the files this phase adds.
PORT-FINDINGS §11.

## Phase 5b — the page templates, not started

**`front-page.php` is DONE and needed no port.** Homepage.js is a pure
composition of Blocks that already exist here, so its only real content is the
section ORDER — which on WordPress is editor data, not markup. That order is
recorded in `front-page.php`'s docblock because `bin/seed.php` has to reproduce
it. Do not "port" Homepage.js into a template; it would hardcode what the
Flexible Content field exists to hold.

**Sizing the rest, so nobody starts this without knowing:** 6,511 lines of
source across the 14 page components. That is several sessions, not one. Read
`docs/PORT-BRIEF.md` first and treat each page as its own port.

| Storybook page | Lines | WordPress | Exists? |
|---|---|---|---|
| Homepage | 121 | `front-page.php` | **done** (composition only) |
| NotFound | 316 | `404.php` | **done** |
| Legal | 314 | `page.php` | renders sections + prose; the Legal prose treatment is still to port |
| BlogIndex | 274 | `home.php` | missing |
| BlogDetail | 314 | `single.php` | scaffold only |
| ClassesListings | 243 | `archive-lp_class.php` | missing |
| ClassesAgenda | 306 (+122 `ClassesHeaderCluster.js`) | page template | missing |
| ClassesMap | 381 | page template | missing |
| ClassDetail | 391 | `single-lp_class.php` | missing |
| TutorialsIndex | 216 | `archive-lp_tutorial.php` | missing |
| TutorialsSeries | 275 | `taxonomy-lp_series.php` | missing |
| TutorialDetail | 384 | `single-lp_tutorial.php` | missing |
| Contact | 452 | page template, Flexible Content | missing |
| DocsFaq | 375 | page template, Flexible Content | missing |
| — | — | `search.php`, `archive.php`, `index.php` | scaffold only, no source page |

Suggested order — cheapest first, and each one earns something the next reuses:

1. ~~`404.php`~~ — **done.** It is the worked example for the rest: source
   defaults as `$lp_*` arrays at the top, `get_header()`/`get_footer()` for the
   chrome, one `<main>`, shared parts via `lp_part()`. It also surfaced the
   dark-band form gap now recorded in PORT-FINDINGS §12 — read that before
   starting Contact or DocsFaq.
2. **Legal** — **analysed, not built.** `page.php` now renders `page_sections`
   when a page has rows and `the_content` prose when it has any, so both a block
   page and a prose page work off it. Legal needs more than that, and the shape
   was worked out before the session ended. Start here:

   **Legal does NOT belong on `page.php`.** It is
   breadcrumb → masthead → doc meta → body(index rail + clauses) → onward, and
   none of that furniture belongs on every static page. It needs the theme's
   **first WP page template** — there are none yet, and no `templates/` dir.
   That machinery is the real cost of this item, not the markup.

   All four components it composes are already ported and their signatures
   checked: `breadcrumb-rail` (crumbs, action), `page-masthead` (title, note,
   media_id), `fact-row` (label, value, surface — Legal uses `page`),
   `page-onward` (prev, next, surface, variant). Only two sections are inline in
   the source, and both are Legal-only, so they stay in the template rather than
   being promoted to `parts/` (PORT-BRIEF rule 3a).

   **The one real decision, already made: the ten clauses are an ACF repeater,
   not `the_content()`.** The design's index rail is keyed to clause NUMBERS and
   jumps to per-clause ids, so the numbers and titles have to be structured data.
   Deriving them by regexing `<h2>`s out of rendered TinyMCE output would be
   fragile and would still not produce the source's number-span + heading row.
   A repeater of `{ number, title, paragraphs }` makes the index trivial and the
   markup exact. Doc facts and doc actions are repeaters too; onward prev/next
   are Link fields.

   So: an ACF group located on `page_template ==` the new Legal template. Note
   `acf-groups.php` has no `page_template` location rule yet — `$lp_where()`
   takes any param, so this is a one-line addition, but it is new ground.

   **One element gap found:** Legal's doc-meta actions are
   `font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent
   hover:text-accent/70 transition-colors duration-150`. That is
   `text-link.php`'s `board_compact` with `accent` in place of `primary` — a
   fifth variant, not a re-roll. Add it (Phase 3 set the precedent in
   PORT-FINDINGS §7) rather than hand-rolling the `<a>`.

   **Do not invent the missing hrefs.** The source's pager points at Privacy and
   Cookie policies that exist in neither the design nor the repo, and its own
   docblock says so twice. Labels port, hrefs do not.
3. **`home.php` + `single.php`** (BlogIndex/BlogDetail) — native `post`, so no CPT
   or ACF work; `components/blog-card.php` and `components/byline.php` are ported
   and waiting.
4. **The `lp_class` family**, then **`lp_tutorial`**. These carry the real query
   work and the `lp_level`/`lp_series` taxonomies.
5. **Contact / DocsFaq** — Flexible Content page templates. Largest, and they may
   want new blocks; check against `blocks/` before writing markup.

Watch for: `ClassesHeaderCluster.js` is shared by the Classes pages — decide
early whether it becomes `parts/components/` (coordinated promotion, PORT-BRIEF
rule 3a) rather than letting two templates each hand-roll it. `search.php`,
`archive.php` and `index.php` have no Storybook page at all, so they are a
judgement call: closest match is BlogIndex's list treatment.

## Phase 5 carried three compliance fixes forward (all now closed — §10)

Phase 3 closed its own items from CONSOLIDATION.md. These are left, all in
`Site/` files that Phase 5 owns:

- Both `SiteNav` CTAs bypass `button.php` — route them through the new
  `band` variant (PORT-FINDINGS §7), do not re-roll.
- `SiteFooter` hardcodes 3 brand `<svg>`s — must become `lp_icon()`. Check the
  sprite actually carries brand marks first; the file's own note says it may not.
- `SiteNav`'s status-rail dot — read PORT-FINDINGS §6 BEFORE touching it.
  CONSOLIDATION §2d's instruction to route it through `status.php` rests on a
  measurement that does not hold.

Hand any porting agent `docs/PORT-BRIEF.md` verbatim (rule 3a forbids inventing
new shared parts — report shapes instead).

## Not done deliberately

- **No content seeded.** Per instruction. `bin/seed.php` is to be written and
  documented but not executed.
- **No commits.** Nothing has been committed; the working tree holds all work.
- **Three.js and DOMPurify** are out of scope for this pass.
