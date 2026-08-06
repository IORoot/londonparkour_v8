# Design — Blocks QA page, demo seeding, and the two-dev database contract

Date: 2026-08-05
Status: approved, not yet implemented

## Why

Two problems, one solution.

**Nothing can be reviewed in a browser.** Phases 0–5a produced 10 blocks, 24
components and 18 elements, all verified through `bin/wp lp render` and
`bin/wp lp part` — markup checks against the Storybook, one piece at a time. No
page on the site has ever shown a block. Composition bugs (spacing between
sections, theme bleed, a scrim that only fails against the next block's
background) are invisible to the current toolchain.

**A second developer cannot get a working site.** `bootstrap.sh` produces an
empty install. Everything that makes the site look like the design is content,
and content currently lives only in whoever's local database.

## Decisions

Settled before design; recorded so they are not relitigated.

1. **No SQL dump in the repository.** The database is disposable and is never
   shared. The *content definition* is code.
2. **The QA page covers both source modes** — manual rows and CPT-backed rows —
   which requires seeding demo CPT records.
3. **Demo images are committed** to the repository.
4. **Seed does not touch the Home page.** Blocks QA page and demo CPTs only.
5. **Six demo photos at 1920×1280**, roughly 2–2.5 MB, accepted as a permanent
   one-time repository cost.

Decision 1 is the load-bearing one. This repository already made the same bet in
Phase 1: ACF field groups are generated from PHP by `wp lp acf:build`
specifically so that the database is never authoritative. A committed `.sql`
reverses that, and it is the worst possible file to hold in git — unmergeable
because of auto-increment IDs and serialized data, large opaque diffs, and it
forces an out-of-band protocol for who is allowed to edit the database when.

The reframe: two developers do not need the same database. They need the same
content definition, expressed in files that merge.

## The CPT source path has never run

Discovered while gathering signatures for the implementation plan, and it widens
this work's scope.

Every block was verified with `bin/wp lp render` against its `example.json`, and
every `example.json` is `"source": "manual"`. `lp_resolve_source()`'s `latest`
and `choose` branches have therefore never executed for any block. Seeding demo
CPT records is the first time they will.

Cross-referencing each block's projection against what its CPT actually stores:

| Block | CPT | State |
|---|---|---|
| `train-in-person` | `lp_location` | works — `tag`/`title`/`meta` align exactly |
| `locations` | `lp_location` | type badge blank — projection reads `type`, field is `site_type` |
| `coaches` | `lp_coach` | location renders a post ID — field is `return_format => 'id'` |
| `hero` | `lp_class` | time/spaces/sold_out blank, location renders a post ID |
| `classes` | `lp_class` | seven of eleven columns blank or wrong |
| `cta` | `lp_class` | every panel field falls back to its hardcoded default |

Two root causes, not six bugs:

1. **Post-object fields return IDs.** `location` on `lp_class` and `lp_coach` is
   `return_format => 'id'`; blocks cast it to string and get a number.
2. **Sessions live one level down.** `lp_class` holds a `sessions` repeater
   (`day_label`/`time`/`spaces`/`sold_out`) — deliberately, per `cpt.php`:
   *"a session is a recurring time-slot of it, held as an ACF repeater on the
   class rather than a fifth post type."* Hero, Classes and CTA read `time` and
   `spaces` flat off the class record. Nothing flattens them.

Plus two smaller items found in the same pass:

3. **Field-name drift between a CPT and its consumer.** `locations` reads `type`
   while `lp_location` stores `site_type`; `classes` reads `date_label` while
   `lp_class` stores `day_label`. `acf-groups.php`'s own header states the rule
   that settles both — *"field shapes are taken from the Storybook components
   that consume them"* — so the CPT is what drifted, and the CPT gets renamed.
4. **The `style` control on every CTA is dead.** `lp_field_action()` emits a
   `solid|ghost|text` button_group and `lp_action()` returns it, but **no block
   reads it**. Every button variant is hardcoded (`'variant' => 'primary'`), and
   `lp_action()`'s `'solid'` default is not a valid `button.php` variant anyway.
   The Storybook has no per-CTA style choice — Phase 4 diffed the class strings
   byte-for-byte — so the control is invention with no consumer. It is removed,
   not wired up. Add it back if a design ever calls for it.

`cpt.php` already names the fix location for 1 and 2: *"`lp_resolve_source()` in
acf-fields.php is the seam to change — not the blocks."* All fixes land there and
in `acf-groups.php`; no block projection changes.

**`source_limit` on Hero and Classes counts sessions, not classes.** The Hero
board is titled NEXT SESSIONS and has a fixed visual slot count, so
`source_limit: 4` must yield four session rows even if they come from two
classes. `lp_resolve_source()` gains a session-expansion mode for this.

**Timing matters.** Renaming ACF fields changes the derived `field_` keys and
orphans any stored meta. No content exists yet, so the renames are free right
now and expensive after seeding. They happen before seed runs, not after.

## Sequencing

`page.php` is currently the untouched `_tw` scaffold — it calls
`get_template_part( 'template-parts/content/content', 'page' )` and never
touches `page_sections`. A Blocks page cannot render until that changes.

Order of work:

1. `page.php` renders sections (this is Phase 5b item 2 regardless)
2. Rename the drifted CPT fields — free only while no content exists
3. Demo media, committed
4. `wp lp seed` for media, terms and CPT records — no page yet
5. Fix `lp_resolve_source()`, now that `wp lp render hero --args='{"source":"latest"}'`
   can reproduce each failure against real records
6. `wp lp seed` builds the `blocks-qa` page
7. Documentation
8. Resume Phase 5b with a site that has content in it

Steps 4 and 5 are in that order deliberately: seeding the CPT records first is
what makes the resolver fixes testable. Each fix starts by running a render that
produces visibly wrong output and ends by running the same render again. That is
the red-green cycle, using the tooling this repository already has, rather than
introducing a test framework it does not use.

Every remaining page template is easier to verify against a populated site, so
pulling this Phase 6 work forward pays for itself across the next twelve
templates — and Phase 5b step 4, the whole `lp_class` page family, depends on the
CPT path working at all.

## Architecture

### `page.php`

Renders `page_sections` when the field has rows, then `the_content` prose when
there is any. One template serves both shapes with no page-template picker:

- **Blocks QA** — sections only, no post content
- **Legal** (the actual Phase 5b port) — content only, no sections
- A page with both renders sections first, then prose

`lp_render_sections()` already returns early when `get_field()` yields a
non-array, so it is safe to call unconditionally. The rows check exists only to
decide whether to emit the prose wrapper.

### `wp lp seed` — location and name

The approved plan named this `bin/seed.php`. **Deviation:** it becomes
`app/setup/seed.php`, registering the WP-CLI command `lp seed`.

Reason: it needs WordPress bootstrapped, and every other tool in this theme is a
`wp lp *` command (`acf:build`, `render`, `part`). `bin/` holds shell scripts and
the `wp` wrapper only. A PHP file requiring the WordPress runtime does not belong
there.

`app/setup/acf-build.php` is 537 lines and already carries three unrelated CLI
commands. Seed does not grow it further; it gets its own file, added to the
`$lp_includes` list in `functions.php`.

### Safety model

Every post seed creates carries `_lp_seed = 1` post meta.

Seed reads, updates and deletes **only** posts carrying that marker. A post an
editor authored by hand can never be modified or removed by seed, whatever its
slug. This is the single invariant that makes the command safe to run against a
database that has real work in it.

Get-or-create is by `post_name`, so re-running is idempotent: same slugs, same
posts, fields overwritten. `--fresh` deletes every marked post (and its
attachments) before recreating, for a clean rebuild without touching the rest of
the install.

### The QA page is generated, not authored

The `blocks-qa` page's `page_sections` value is built by iterating `blocks/*/`:

- **10 manual rows** — each row is `{ acf_fc_layout: <name> }` merged with that
  block's `example.json`, verbatim. There is no second copy of demo content
  anywhere: the fixture `bin/wp lp render` already verifies is the same fixture
  the page renders.
- **6 CPT rows** — a duplicate row for each source-backed block (Hero, Classes,
  Coaches, Locations, TrainInPerson, CTA) with `source` set to `latest`, a
  `source_limit`, and `source_manual` dropped.

`source` takes `latest | choose | manual` — it is not the post type. Which CPT a
block draws from is fixed in its own `fields.php` via `lp_field_source()`, not
chosen per row.

The two row families sit adjacent on the page. Because the demo CPT records are
derived from the same `source_manual` data — TrainInPerson's manual rows *are*
the location records, "Vauxhall — The Arches", "SW8 1SR · 4 min from Vauxhall" —
the manual row and the CPT row should render near-identically. Any visible
divergence is a `lp_resolve_source()` or projection bug, caught by eye rather
than by reading code. The QA page is a differential test, not just a gallery.

The `source_limit` per block is the one piece of coordination data seed holds
directly, because it is not block data.

### Data files

| Path | Owns | Read by |
|---|---|---|
| `blocks/*/example.json` | manual-mode content | `lp render`, `lp seed` |
| `blocks/*/example.media.json` | **new, optional** — maps a field path to a demo image filename | `lp seed` only |
| `bin/demo-content/lp_class.json` | demo classes, with sessions | `lp seed` |
| `bin/demo-content/lp_coach.json` | demo coaches | `lp seed` |
| `bin/demo-content/lp_location.json` | demo locations | `lp seed` |
| `bin/demo-content/lp_tutorial.json` | demo tutorials | `lp seed` |
| `bin/demo-content/terms.json` | `lp_level` and `lp_series` terms | `lp seed` |
| `bin/demo-media/*.jpeg` | 6 demo photographs | `lp seed` |

`example.media.json` is a sibling file rather than a map inside `seed.php`
deliberately. `lp_render_sections()`'s own docblock states the rule — "there is
no registry to edit" — and adding block #11 must not mean editing seed. It is
kept separate from `example.json` so that `lp render` continues to receive pure
field values and never sees a filename where it expects an attachment ID.

Demo CPT records are JSON rather than PHP so that adding a demo class is a data
edit both developers can make and merge without touching code.

### Demo media

Six photographs sourced from the Storybook repository's own `DSC*.jpeg` files
(currently 2.6–5.8 MB each), downscaled to **1920×1280 at q78** — roughly
2–2.5 MB total, committed once and never changed.

1920 wide is not arbitrary. `lp_wide_lg` is 1920×1080 and `lp_portrait_lg` is
1112×1200; a source narrower than 1920 causes WordPress to skip the largest crop,
which yields a thinner srcset and leaves the Phase 3 responsive-image work
half-tested. 1280 tall covers the portrait family from the same file.

Seed imports them with `wp media import` keyed on filename, so re-running does
not duplicate the library, and each import is marked `_lp_seed` like any other
post. After import, seed triggers crop regeneration so the ratio-matched families
described in `app/setup/theme.php` exist.

## Data flow

```
blocks/*/example.json ─────┐
blocks/*/example.media.json ├──> lp seed ──> page "blocks-qa" (page_sections)
bin/demo-content/*.json ───┤              ──> lp_class / lp_coach /
bin/demo-media/*.jpeg ─────┘                  lp_location / lp_tutorial posts
                                           ──> lp_level / lp_series terms
                                           ──> media library attachments
```

Seed runs in dependency order, because later steps reference earlier IDs:

1. Media import — filename → attachment ID map
2. Terms — `lp_level`, `lp_series`
3. CPT records — fields resolved, images attached, terms assigned, cross-
   references (a class's location and coaches) resolved by slug
4. The `blocks-qa` page — manual rows from `example.json` with image fields
   filled from the media map, then the CPT-mode rows

## Error handling

- **ACF absent** — hard `WP_CLI::error`. Every write goes through
  `update_field()`; there is no partial-success mode worth having.
- **Malformed JSON** — named file and `json_last_error_msg()`, then abort before
  any write. Seed never half-populates.
- **Missing demo image** — a filename in `example.media.json` with no matching
  file in `bin/demo-media/` is a `WP_CLI::warning` and that field is left unset.
  The block still renders; `media-photo.php` already returns early without an
  `image_id`. A missing photograph must not block seeding everything else.
- **Unresolved cross-reference** — a class naming a location slug that no demo
  location defines is a warning, field left unset. Same reasoning.
- **Marker check** — if an existing post at a seed slug lacks `_lp_seed`, seed
  skips it with a warning rather than overwriting. Someone made that page by
  hand.

## Verification

Existing gates still apply and must pass unchanged:

```bash
php -l <file>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # must print Success
npm run build
```

New to this work:

```bash
bin/wp lp seed --fresh           # from a bootstrapped install
bin/wp lp seed                   # again — must produce no duplicates
```

Idempotency is verified by counting posts before and after the second run, not
by inspection. The safety invariant is verified by injection, the way
`audit-reuse.sh` was: create a page by hand at the `blocks-qa` slug without the
marker, run seed, confirm it is skipped and not overwritten.

Visual verification is the point of the whole exercise: `/blocks-qa/` renders 16
rows, and each of the six source-backed blocks reads the same as its manual twin.

## The two-dev contract

Documented in `bin/README.md`. The rule at the top:

> The database is disposable. Never share it. The content definition is code.

| Task | Command |
|---|---|
| From zero | `bin/bootstrap.sh && bin/wp lp seed` |
| Reset demo content | `bin/wp lp seed --fresh` |
| Nuke and rebuild | `docker compose down -v`, then the two above |
| Field group change | edit PHP → `bin/wp lp acf:build` → commit `acf-json/` |

Two standing rules for the pair:

- Anything authored in wp-admin that matters moves into `bin/demo-content/`, or
  it does not exist for the other developer.
- Conflicts resolve as ordinary JSON and PHP merges, because that is all they
  are.

There is no dump to restore. `docker compose down -v` followed by bootstrap and
seed *is* the recovery path, and it is fast enough that it is also the routine
way to get back to a known state.

## Out of scope

- **Homepage seeding.** Decided against. The nine-row order stays recorded in
  `front-page.php`'s docblock for whenever it is wanted; seed can gain a
  `--homepage` flag later without redesign.
- **Variant coverage rows** — extra rows exercising each block's style, spacing
  and direction controls. Considered and dropped; the 16 rows cover every
  block's markup and both source paths, which is what the page is for.
- **Production or client content.** Seed produces demo content for QA. Real
  content is an editor's job and does not belong in the repository.
