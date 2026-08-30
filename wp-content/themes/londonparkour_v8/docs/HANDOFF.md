# Handoff — Storybook → WordPress port

State: Phases 0–4 complete. **B4 is closed** — Phase 5 has the site chrome,
`front-page.php`, `404.php`, `page.php`, `templates/legal.php`, `home.php`,
`single.php`, `search.php`, `archive.php`, `index.php`, `archive-lp_class.php`,
`templates/classes-agenda.php`, `templates/classes-map.php` and
`single-lp_class.php` done — **four page templates left**: TutorialsSeries,
TutorialDetail (B5), Contact, DocsFaq (B6)
(see "Phase 5b" and "The remaining batches"). Most of Phase 6 is done too,
pulled forward: `wp lp seed`, `bin/README.md` and the theme `README.md` all
exist, and `lp_seed_template_pages()` now seeds pages with `_wp_page_template`
set. Everything needed to continue is on disk; this document is the map.

**The work is now committed.** It was uncommitted for the whole port; there is
now a baseline commit of phases 0–5a and one commit per piece of work after it.
Read `git log` before starting — the commit messages carry the reasoning for
every decision below, and several correct this document's older claims.

**Read "Session 2 corrections" below before trusting §12, §8 or the button
gap.** Three items this document and `PORT-FINDINGS.md` list as open were
measured and found stale or wrong.

**Look at the site before you write anything.** `page.php` now renders
`page_sections`, and `bin/wp lp seed` builds `/blocks-qa/` — sixteen rows, every
block once from its `example.json` and the six source-backed blocks a second
time reading real CPT records. That page found five genuine bugs on its first
run (PORT-FINDINGS §13); use it to eyeball any block while porting a template.

    bin/bootstrap.sh && bin/wp lp seed     # from a clean database

The database is disposable and is never shared between developers — the content
definition is code, in `bin/demo-content/`, `bin/demo-media/` and each block's
`example.json`. `bin/README.md` is the contract; read it before touching content.

## Session 2 corrections — read before acting on the older sections

Five findings that change what is left to do. Each has a commit with the
evidence; none is a guess.

**1. PORT-FINDINGS §12 is mostly stale.** It says `forms/field.php` and
`button.php` have no dark-band variant and that `button.php` "emits
`type="button"` with no override, so it cannot produce a submit control at all."
Against the code: `field.php:30` and `text-area.php:31` already carry
`surface: page|board`, `fact-row.php:18` already has `page`, and `button.php`
already reads `$args['type'] ?? 'button'`. Only the dark-band button shape was
ever missing.

**2. That last gap was deliberately NOT closed, and should not be.** A grep of
the whole Storybook finds the dark-band bordered block at
`src/stories/Pages/NotFound/NotFound.js:123` and **nowhere else**, and every
`Button` variant the remaining pages ask for is `primary`, `inverse` or `icon` —
all shipped. Contact's submit is `variant:'primary'` plus `type=submit`, which
works today. Promoting a one-file shape is building ahead. `404.php` keeps its
hand-built controls and `bin/audit-reuse.sh` exempts it from the `<button>` rule
alone, exactly as it already exempts `parts/site/nav.php`. **A second caller is
the moment it becomes a variant** — until then, do not add it.

**3. The reuse audit now covers the theme root, `inc/` and `templates/`.** It
did not before, which is how `404.php`'s hand-built controls passed unremarked;
every page template you add from here is gated on raw `<button>`/`<svg>`/`<img>`,
`btn` classes and built class names. Verified by injection. The root `*.php`
glob expands at scan time, so new templates are covered without editing it.

**4. The blog could not render at all.** `show_on_front` was `page` with
`page_for_posts` left at `0`, so WordPress never reached `home.php` and `/blog/`
404'd. `bin/bootstrap.sh` now creates a Blog page and sets `page_for_posts`.
Menus were also empty — created and assigned but never filled — so both site
partials had been silently falling back to their ported defaults and no template
had ever exercised a real menu. `wp lp seed` now fills both menus and creates the
four demo posts.

**5. The Storybook's blog prose is truncated at source.** `BlogIndex`'s Version 7
excerpt and every string in `BlogDetail`'s `DEFAULT_BODY` end in `…`. The design
proves layout, not copy. Those strings are seeded verbatim so a visual diff
matches. **Do not complete them** — inventing copy is the failure the Port Brief
names, and real article text is a client dependency.

Two things checked and deliberately left alone:

- **`md:px-16` in `blocks/{cta,locations,pricing}`** is byte-identical to the
  Storybook source, so rule 1 protects it despite the layout contract's
  `lg:px-16`. It is a design-system inconsistency to report upstream, not a port
  defect. `page.php` was WP-authored, so its `md:px-16` and `py-[64px]` were
  fixed.
- **`404.php`'s aside only looks like `aside-panel.php`.** Measured: different
  ground (`bg-secondary` vs `bg-neutral`), different header and row border
  opacities, different CTA hover (`hover:bg-primary/85` vs `hover:bg-neutral
  hover:text-primary`). Routing it through the part would silently change the
  design — the same trap §6, §8 and §10 each fell into.

**§8 is closed.** `Hero` and `CTA` now pass `lp_field_source()`'s `taxonomy`
option, so all three `lp_class` blocks expose one identical control. No resolver
work was needed — `lp_resolve_source()` already reads `source_terms` generically
via `lp_source_taxonomy_for()`. Proven against seeded records, not by field
presence: `hero` filtered to the advanced term returns `Advanced Movement` alone.

## Session 3 correction — this document undercounted the pages

**There are 15 designed pages, not 14, and `search.php` is a real port.** The
Phase 5b table below was built from `src/stories/Pages/`, and
`src/stories/Search/SearchResults/SearchResults.js` is the one designed page
that lives outside it — 293 lines, `Lc4uQ` "Search (Concourse)" plus five
section masters. B3's "no Storybook source" was right about `archive.php` and
`index.php` and wrong about search. It is now ported. PORT-FINDINGS §14.

**Enumerate B4–B6 from `src/stories/`, not `src/stories/Pages/`.** The same
oversight would hide anything else filed by function rather than by kind.
`src/stories/Components/Navigation/` (Breadcrumb, Dock, Link, Menu, Navbar,
Pagination, Step, Tab) is the opposite case: Storybook scaffolding, not this
design system. No page imports it. Do not port it.

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
| **5** | **4 page templates left** (B5 ×2, B6 ×2) — see "Phase 5b" below. Chrome, `front-page.php`, `404.php`, `page.php`, `templates/legal.php`, `home.php`, `single.php`, `search.php`, `archive.php`, `index.php` and all four B4 pages are done |
| **6** | **Mostly done.** `wp lp seed` (in `app/setup/seed.php`, not `bin/seed.php` — it needs WP bootstrapped), `bin/README.md` and `README.md` all written and run. It now also seeds native posts and both menus. Left: (a) **pages + their templates** — Legal, Contact, DocsFaq and the two Classes view pages each need a seeded page with `_wp_page_template` set, which is what makes a page template verifiable; (b) **homepage rows**, still deferred — the nine-row order is recorded in `front-page.php`'s docblock and seed can gain a `--homepage` flag without redesign |
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
| Legal | 314 | `templates/legal.php` | **done** — the theme's first page template |
| BlogIndex | 274 | `home.php` | **done** |
| BlogDetail | 314 | `single.php` | **done** — the worked example for native-post templates |
| ClassesListings | 243 | `archive-lp_class.php` | **done** — the worked example for a filtered CPT archive |
| ClassesAgenda | 306 (+122 `ClassesHeaderCluster.js`) | `templates/classes-agenda.php` | **done** — cluster is now `parts/components/classes-header-cluster.php` |
| ClassesMap | 381 | `templates/classes-map.php` | **done** — pins project real lat/long, PORT-FINDINGS §23 |
| ClassDetail | 391 | `single-lp_class.php` | **done** — the worked example for a single CPT template |
| TutorialsIndex | 216 | `archive-lp_tutorial.php` | **done** — no new fields needed; PORT-FINDINGS §24 |
| TutorialsSeries | 275 | `taxonomy-lp_series.php` | missing |
| TutorialDetail | 384 | `single-lp_tutorial.php` | missing |
| Contact | 452 | page template, Flexible Content | missing |
| DocsFaq | 375 | page template, Flexible Content | missing |
| SearchResults | 293 | `search.php` | **done** — in `src/stories/Search/`, not `Pages/` |
| — | — | `archive.php`, `index.php` | **done** — no source page; shared body |

Suggested order — cheapest first, and each one earns something the next reuses:

1. ~~`404.php`~~ — **done.** It is the worked example for the rest: source
   defaults as `$lp_*` arrays at the top, `get_header()`/`get_footer()` for the
   chrome, one `<main>`, shared parts via `lp_part()`. It also surfaced the
   dark-band form gap now recorded in PORT-FINDINGS §12 — read that before
   starting Contact or DocsFaq.
2. ~~**Legal**~~ — **DONE**, at `templates/legal.php`. It is the worked example
   for every page template that needs its own ACF data: read it before Contact,
   DocsFaq or the Classes pages. The analysis below is kept because it records
   *why* the shape is what it is; everything in it was implemented as written.
   The page-template machinery it needed now exists — `templates/` is created,
   Tailwind scans it (`main.css` gained the `@source` glob), `bin/audit-reuse.sh`
   gates it, and WordPress registers a `Template Name:` one level deep with the
   key `templates/<file>.php`, which is what `_wp_page_template` stores and what
   an ACF `page_template` location rule must match. All verified by probe.

   Original analysis, implemented:

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
3. ~~**`home.php` + `single.php`**~~ — **DONE.** Native `post`, one ACF group
   (`group_lp_post`) holding only what WordPress has no field for. **Two
   precedents set here that the remaining templates should follow:**

   - **Structured body, not `the_content()`.** BlogDetail's sticky TOC is keyed
     to per-section ids, so the body is a repeater and the ids and TOC are
     *derived* from it with `sanitize_title()` rather than stored twice. This
     reproduced the source's four hand-written ids exactly. Legal made the same
     call; expect TutorialDetail and DocsFaq to need it too.
   - **Native data stays native.** Title, excerpt, date, featured image,
     category and author name come from core functions. Only add an ACF field
     where WordPress genuinely has no home for the value.

   A crop gap to watch: the 1440×540 hero reuses `lp_wide_lg` (16:9) with
   `object-cover`, because a lone orphan size yields an EMPTY srcset — adding
   the ratio means adding a matched family of three widths. If Classes or
   Tutorials want the same ratio, add the family then.

   `bootstrap.sh` now drafts core's `hello-world` post: WordPress dates it at
   install time, so it was always the newest post and took the index's featured
   slot and the head of the prev/next chain.
4. **The `lp_class` family**, then **`lp_tutorial`**. These carry the real query
   work and the `lp_level`/`lp_series` taxonomies.
5. **Contact / DocsFaq** — Flexible Content page templates. Largest, and they may
   want new blocks; check against `blocks/` before writing markup.

6. ~~**B3**~~ — **DONE.** Three things it left behind that B4–B6 should use
   rather than rebuild:

   - **`parts/components/pagination.php`** — the design system's ONLY pagination
     shape, promoted from SearchResults' inline `l6bk8`. Build its args with
     `lp_pagination_args()` (`app/includes/content.php`); pass a `noun` for the
     count line. It renders every page number, deliberately — PORT-FINDINGS §16
     has the ceiling and the upgrade path.
   - **`elements/view-tab.php` takes an `href`** and then renders an `<a>` with
     `aria-current="page"` instead of `<button role="tab">`. Any filter rail
     that navigates (ClassesListings, TutorialsIndex) wants this form, not the
     button. Class strings are identical.
   - **`lp_post_card_args( WP_Post )`** projects a post into blog-card's shape.
     `home.php` and `archive-list.php` both call it.

   Also: `template-parts/content/` no longer holds the `_tw` scaffold parts —
   `content{,-excerpt,-none,-page,-single}.php` were deleted when their last
   callers were rewritten. `comments.php` is now unreferenced too (no template
   calls `comments_template()`); left in place, not audited.

Watch for: `ClassesHeaderCluster.js` is shared by the Classes pages — decide
early whether it becomes `parts/components/` (coordinated promotion, PORT-BRIEF
rule 3a) rather than letting two templates each hand-roll it. `search.php`,
`archive.php` and `index.php` have no Storybook page at all, so they are a
judgement call: closest match is BlogIndex's list treatment.

## The remaining batches, in order

Sized from the sources. Everything each page imports is **already ported** — the
dependency closure was re-checked and is closed, so nothing new comes from the
Storybook to start any of these.

| Batch | Pages | Notes |
|---|---|---|
| ~~**B3**~~ | ~~`archive.php`, `search.php`, `index.php`~~ | **DONE.** `search.php` turned out to have a real source (§14) and is a full port. `archive.php` and `index.php` share `template-parts/content/archive-list.php`, which composes BlogIndex's Recent grid under a breadcrumb + masthead. Earned three things B4–B6 reuse: `parts/components/pagination.php`, `view-tab.php`'s link form, and `lp_post_card_args()` |
| ~~**B4**~~ | ~~ClassesListings, ClassesAgenda, ClassesMap, ClassDetail~~ | **DONE — all four.** `ClassesHeaderCluster` is promoted (`parts/components/classes-header-cluster.php`) and `ClassDetail` deliberately does not use it (no ViewRail/FilterGrid). Agenda uses `?week=±n` and forced a `date` sub-field onto the sessions repeater (§20). ClassesMap projects real `latitude`/`longitude` into the placeholder rather than porting the source's invented x/y percentages, and seeds itself via `lp_seed_template_pages()`. `single-lp_class.php` is the worked example for a single-CPT template. Filtering pattern is §17; the featured band was removed (§19); the two gaps left — no recurrence field, no audience field — are §23 and are the repo owner's call, not port decisions. |
| **B5** | ~~TutorialsIndex~~ **done**; TutorialsSeries → `taxonomy-lp_series.php`, TutorialDetail → `single-lp_tutorial.php` left | Both index pages embed `Blocks/TrainInPerson`, which is already a WP block — call the block partial, do not re-port the section |
| **B6** | Contact, DocsFaq → page templates | Largest, and last so earlier ports settle the patterns. **Repo owner chose Flexible Content**, so their bespoke sections become new blocks: enquiry form + reach panel, other-ways fact strip, FAQ group, section directory, passenger-enquiries strip |

**`ClassesMap` needs no map library.** Greps for Leaflet, Mapbox and
`google.maps` across `src/stories/Pages` return nothing — it is `MapPin` +
`SitePanel` + `ListRow` on a static ground, all ported. No Three.js, Swiper or
DOMPurify on any page either.

Two open items in `docs/phase7/contact-inventory.md` to resolve rather than
invent when B6 lands: **Finding B**, a literal `#FFFFFF` fill on section `L5CYk`
that matches no token (report it — the Port Brief forbids inventing a colour),
and **Finding A**, the "02" section that does not exist on the page (do not
renumber to close the gap).

## How this was run, and why it worked

The main loop is the coordinator and QA gate; a Sonnet subagent does each page
port, handed `docs/PORT-BRIEF.md` verbatim. Keep that seam. What it caught:

- Agents report shapes, they never promote them. Two agents each inventing a
  different `classes-header-cluster` is worse than the duplication it replaces.
- **Never accept an agent's "done" on its word.** Re-run the gates yourself and
  render the page. On the Legal port the QA pass independently re-checked the
  landmark count, every arbitrary y value against the source, that the shared
  element edit was purely additive, and that no test rows were left in the
  database — all of which held, but only because they were checked.
- A template that only passes greps is unproven. `lp render` against an
  `example.json` proves markup, not data flow (PORT-FINDINGS §13). Every
  template needs a real page, seeded, curled, and its rendered output inspected.

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

- **Three.js and DOMPurify** are out of scope for this pass.
- **The dark-band button variant** — see Session 2 correction 2. Not an
  oversight; adding it needs a second caller first.
- **`Forms/Select` has no `surface` axis.** No page in the remaining scope puts
  a Select on a dark band (Contact uses `Field` ×3 + `TextArea`), so it would be
  building ahead. Recorded, not actioned.
- **`status.php`'s dot geometry** (§6) stays as it is. Three byte-exact
  hand-rolled dots are correct under rule 1; re-opening a shipped element's
  geometry to save nine lines is the wrong trade.

Superseded: the old "no content seeded" and "no commits" entries. Both were
instructions for session 1 and no longer hold — content seeds from
`bin/demo-content/` and the work is committed.
