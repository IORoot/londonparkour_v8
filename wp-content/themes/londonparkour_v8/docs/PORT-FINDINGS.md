# Port findings

Issues found during the Storybook → WordPress port.

The default rule is transcribe faithfully and report: where the Storybook's
markup is the signed-off design, a discrepancy is a decision for the
design-system owner, not something to patch here. Those entries stay open.

Entries marked **(fixed)** or **(RESOLVED in port)** are the exception — bugs in
the WordPress port's own plumbing, with no Storybook counterpart to defer to.
§13 is the largest of these.

## 1. `Status` on the `page` surface may fail contrast in the light themes

**Where:** `parts/elements/status.php`, `page` surface — faithful to
`src/stories/Elements/Status/Status.js:46-47`.

```
page.signal = 'text-[10px] font-semibold tracking-[1.1px] text-primary'
page.spaces = 'text-[10px] font-semibold tracking-[0.8px] text-primary'
```

`Status.js` line 21 justifies this as "`text-primary`, which is
ground-independent". But the canonical surface-axis contract — and
`GlyphLabel`'s own `page` map, which uses `text-accent` — say the opposite:

> `text-primary` on the light page ground measures **1.54:1** (yellow) /
> **1.27:1** (lime). On the page ground the signal role is `text-accent`.

`GlyphLabel` and `Status` therefore disagree about the same surface. One of
these is wrong upstream:

- if `Status` is wrong, `page.signal`/`page.spaces` should be `text-accent`;
- if it is fine, then the `page` surface of `Status` is only ever used inside a
  dark theme, and that constraint is undocumented.

**Impact:** invisible in both dark themes, near-illegible in both light ones —
exactly the failure mode the design system's own docs call its most repeated bug.

**Action:** confirm with the design-system owner before launch, then fix
upstream in the Storybook and re-port. Do not patch only the WordPress copy.

---

## 2. `TrainInPerson`'s hand-built hairline is stale (RESOLVED in port)

`Blocks/TrainInPerson/TrainInPerson.js` hand-builds its own accent hairline and
its JSDoc explains that `Elements/Rule` has no `accent` tone. `Rule` has since
gained one, and the markup is byte-identical apart from `data-component="rule"`.

The port uses `lp_part( 'elements/rule', array( 'tone' => 'accent' ) )`.
Worth removing the duplication upstream too.

---

## 3. Deferred consolidation: the text-link CTA

`TrainInPerson`, `Classes`, `Coaches` and `Locations` each hand-roll the same
"uppercase text link with a hover fade" at different sizes and surfaces
(11px/`accent-content` vs 12px/`primary`, etc.).

Deliberately left duplicated during the port — promoting an abstraction off two
samples risks the wrong one. Revisit in the Phase 7 consolidation pass with all
ten blocks in hand, and promote to `parts/elements/text-link.php` with a
surface × size lookup if the shape holds.

---

## 4. Pages hand-roll form markup that must go through `parts/forms/`

The consolidation scan covered `Components/`, `Blocks/` and `Site/` only — it did
**not** read `Pages/`. Re-checked directly. Forms are genuinely used, and some of
it is raw markup written inside page compositions rather than composed from
`Forms/`:

| File | Raw markup |
|---|---|
| `Pages/Contact/Contact.js:83, :272` | `<form data-form aria-label="Contact enquiry form">`, `<label for>` |
| `Pages/NotFound/NotFound.js:163-167` | `<form role="search">`, `<label class="sr-only">`, `<input type="search">` |
| `Search/SearchResults/SearchResults.js:148-176` | `<form role="search">`, `<label class="sr-only">`, `<input type="search">`, `<select>` |

Only three `Forms/*` components are imported anywhere in the whole system:
`Field` (2), `Select` (2), `TextArea` (1) — by `Contact`, `NotFound`,
`FilterGrid`, `FilterRail` and `BookingDrawer`. All three are already ported.

**Confirmed NOT needed:** `Checkbox`, `Radio`, `Toggle`, `Range`, `Rating`,
`FileInput`, `Validator`, `Fieldset`, `Label` — no page or component reaches
them. They stay out of scope.

**Action for Phase 5 (templates):** the search form and the contact form must be
built from `parts/forms/field.php` / `select.php` / `text-area.php`, NOT by
transcribing the page's raw markup. The search form additionally needs to become
a real WordPress search form (`action="<?php echo esc_url( home_url( '/' ) ); ?>"`,
input `name="s"`), not a decorative one with `name="q"`. Keep `role="search"` and
the `sr-only` label.

---

## 5. Licensing, flagged before launch

- **Scope Trial** (`assets/fonts/ScopeTrial-Variable.ttf`) is a TRIAL licence.
  Needs a production licence before the site is public.
- **`@tailwindplus/elements`** is a paid Tailwind Plus package; the WordPress
  build needs its own licence entitlement.
- The vendored **ACF Pro** is a cracked build
  (`plugins/advanced-custom-fields-pro/acf-pro-crack.php`) — no update channel,
  no licence key. Worth resolving before it becomes load-bearing.

---

## 6. The three hand-rolled status dots do NOT match `status.php` (Phase 3)

`docs/CONSOLIDATION.md` §2d recommends routing `AsidePanel`'s spots dot,
`VideoStage`'s now-playing dot and `SiteNav`'s status-rail dot through the
already-ported `elements/status.php`, on the grounds that it is "literally the
shape `status.php` already covers". Measured against the source, it is not:

| | dot | gap | label |
|---|---|---|---|
| `status.php` | `status status-sm` — daisyUI, **8px** | `gap-2` (8px) | `text-[10px]`, tone fixed per variant |
| `AsidePanel` | `w-[6px] h-[6px]` | `gap-[8px]` | `text-[10px] font-semibold tracking-[0.8px]`, tone = surface **ink** |
| `VideoStage` | `w-[6px] h-[6px]` | `gap-[9px]` | `text-[12px] font-semibold tracking-[1px]` |

So the dot is 8px vs 6px in all three cases, `VideoStage` differs on gap and on
label size, and `AsidePanel`'s label is `ink`-toned where `status.php`'s
`spaces` variant is hard-coded `text-primary`.

Routing them through `status.php` as-is would silently resize three dots and
recolour one label. Port Brief rule 1 makes class strings signed-off design
decisions and says to report rather than substitute, so **both Phase 3 dots are
ported byte-exactly** and `status.php` is untouched. `BoardShell`'s live stamp
is a genuine `status.php` instance and does use it.

To actually close §2d, `status.php` needs a geometry axis (a 6px dot, a
per-variant gap, an `ink` label tone) — a deliberate change to a shipped
element, affecting `SiteNav` in Phase 5 too. Flagged, not taken.

## 7. Two element gaps closed during Phase 3, one atom promoted

- **`button.php` gained `band`** — the flush, full-width `justify-between`
  label+icon control. This is the gap §1b recorded; `AsidePanel`'s CTA and both
  `SiteNav` CTAs each hand-rolled it. Carries no `btn`: daisyUI's padding and
  height are exactly what it must not inherit. Phase 5 should use it for the two
  `SiteNav` CTAs rather than re-rolling them.
- **`view-tab.php` gained `rich`** — `ViewRail`'s dark two-line tab. A real
  `<button>`, so it cannot live in a component file.
- **`chevron.php` gained `media_card_static`** — `MediaCard`/`VideoCard` write
  their hover classes behind an `isLink` ternary, so a static card is a
  different literal string, not the `media_card` one.
- **`elements/avatar-initial.php` is new.** §4b asked `BlogCard` to compose
  `Byline` for its avatar, but `Byline` always renders the person's NAME beside
  it, and `BlogCard` renders that name in its own deliberately different voice
  (`font-body`, not `font-heading`) — composing it printed the name twice. The
  avatar alone is promoted; both callers stay byte-exact. `BlogCard`'s avatar is
  not `aria-hidden` and `Byline`'s is, per each source, so that is a parameter.

## 8. Phase 4 (last four blocks): three near-misses left un-shared, one gap

All four are cases where an existing part looks like the right call and is not.
Each was measured against the source, not eyeballed.

- **`Locations`' site row is not `components/list-row.php`.** ListRow is a
  *recessed board* row — `bg-secondary`, hover `bg-neutral` — sitting on top of
  its ground. The site rows carry no fill at all: they sit directly on the
  section's `bg-accent`, separated only by a hairline, and they need a leading
  glyph and a trailing category label (INDOOR/OUTDOOR) that ListRow has no slot
  for. Ported inline, exactly as the source does, and the source's own docblock
  argues the same case at length. Only the chevron is shared — Phase 3 had
  already added `chevron.php` variant `accent_band` for this row.

- **`PrivateCoaching`'s fact rail is not `components/fact-row.php`.** Same idea,
  different type: FactRow's label is `font-label text-[10px] font-normal
  tracking-[0.9px] … /65` over a `font-body` 15px value with `mt-[7px]`; the
  fact rail's key is `font-semibold tracking-[1.1px] … /60` over a
  `font-heading` 15px value that truncates, in a `gap-[7px]` flex column. Four
  differences, none of them a parameter FactRow exposes. Ported inline.

- **`Classes`' column head is not a field and not a part.** It carries layout
  classes as well as labels and is hand-coupled to `board-row.php`'s own column
  geometry, which that part does not export. The plan is explicit that it stays
  a hardcoded array; the block's docblock lists the four widths it must track.

- **The source control's taxonomy filter is only wired on `Classes`.**
  `lp_field_source()` takes a `taxonomy` option and `lp_source_taxonomy_for()`
  maps `lp_class` → `lp_level`, but until now no block passed it, so the
  "Latest → filter by level" branch the plan specifies did not exist anywhere.
  `Classes` passes it (the timetable board is the block a level filter is for).
  **`Hero` and `CTA` are the other two `lp_class` blocks and still omit it** —
  a coordinator call, not one to make per block: either they gain it too, or
  `Classes` is the deliberate exception. Flagged, not taken.

## 9. Two deliberate simplifications in Phase 4

Neither loses a capability the source's own data uses.

- **`Pricing` derives each tier's button variant from `highlight`.** The source
  gives every tier an independent `cta.variant`, but its data only ever pairs
  the highlighted column with `primary` and the other two with `ghost`. The CTA
  is a plain Link field and the variant follows the highlight, so the two
  controls cannot disagree. If a non-highlighted tier ever needs a solid button,
  that is a `style` button_group away.

- **`Pricing` derives each tier's `data-tier` id from its label.** The source
  hand-writes `id: 'drop-in'`; `sanitize_title( 'DROP-IN' )` produces the same
  slug for all three tiers without an extra field. QA greps still match.

## 10. Phase 5 (SiteNav + SiteFooter): the three carried-forward items, resolved

HANDOFF listed three compliance fixes for Phase 5. One was right, one was
stale, and one could not be done as written — the sprite was missing the
symbols. All three are settled below.

- **The desktop CTA does NOT become `button.php` variant `band`.** Measured,
  they are two different controls that happen to share a label span:

  | | `band` | SiteNav desktop CTA |
  |---|---|---|
  | display | `flex` `w-full` `justify-between` | `inline-flex` |
  | height | `h-[60px]` fixed | the BAR's height — `h-[76px]` / `h-[58px]` |
  | padding | `px-[22px]` | `px-[30px]` |
  | hover | `bg-neutral` + `text-primary` | `bg-primary/85` |

  Only the inner label (`font-label text-[12px] font-semibold uppercase
  tracking-[1px]`) and the 14px arrow match, which is exactly why `band` looks
  like the answer. `band` is AsidePanel's shape. The source argues the same
  case from the .pen: the Book Block is 181×76 at x:1259 on a 1440 bar with no
  right padding, i.e. a full-bar-height block flush to the viewport edge, which
  a fixed-height padded control cannot be. Ported inline. This is the third
  CONSOLIDATION recommendation that did not survive measurement (§6, §8, §10).

- **"Both SiteNav CTAs bypass `button.php`" was stale.** The current source
  mounts `Button` for both MOBILE CTAs (bar and drawer) and only the desktop one
  is inline. The port matches: two `lp_part( 'elements/button' )` calls, one
  inline block.

- **The status-rail dot stays hand-rolled**, confirming §6 at the call site: 6px
  `rounded-full`, not `status.php`'s daisyUI `status-sm`.

- **The three brand `<svg>`s are RESOLVED, by fixing the cause.** The sprite
  genuinely had no brand marks — 0 hits for instagram/youtube/facebook across
  `icons.svg` (328 symbols) and `glyphs.svg` (67) — so "must become `lp_icon()`"
  was not achievable as written. Rather than exempt the footer from the
  no-raw-`<svg>` rule, the three symbols were added to `assets/img/icons.svg`
  from the same .pen-extracted path geometry the source vendors (script-copied,
  not retyped), taking it to 331. `footer.php` now calls
  `lp_icon( 'icon-instagram', 'w-[18px] h-[18px]' )` like every other mark.
  One consequence worth recording: the source sizes these with `width="18"
  height="18"` ATTRIBUTES; through `lp_icon()` the size is a class instead.
  Same rendered size, different mechanism — the only class in either Site file
  that is not verbatim from its source.

- **`nav-link.php`'s `icon_id` slot is NOT the SiteNav glyph.** nav-link puts a
  `w-3.5 h-3.5` glyph INSIDE the anchor, coloured by `hover:`. This design needs
  a `w-[13px] h-[13px] flex-none` glyph OUTSIDE it, coloured by the wrapper's
  `group-hover:`. The glyph is rendered by `nav.php`, exactly as the source
  does, and nav-link is called without `icon_id`.

## 11. The reuse audit did not cover `parts/site/` (fixed)

`bin/audit-reuse.sh` scanned `blocks`, `template-parts` and `parts/components`
for its element-markup rules — but not `parts/site/`, the directory Phase 5
creates and the exact place the three fixes above had to land. A raw `<svg>` in
the footer would have passed silently.

`parts/site` is now in the audit's targets, verified by injection (a raw `<svg>`
appended to `nav.php` fails it; removing it passes). `parts/site/nav.php` is
exempt from the raw-`<button>` rule ALONE: two of its buttons are Tailwind Plus
Elements invokers (`popovertarget`, `command`/`commandfor`) which only function
on a real `<button>`, and the third is a 60px icon-only bar cell that must not
inherit `btn btn-primary btn-square`. It is not exempt from the
`<svg>`/`<use>`/`<img>`/btn-class rules.

Note the Tailwind *literal* rules (glued class fragments, prefix concatenation)
already scanned all of `parts` and were never affected.

## 12. Phase 5b: `forms/field.php` and `button.php` have no dark-band variant

`404.php` hand-builds its search input, its two ghost buttons and its submit
button rather than calling the shipped parts. This is not a shortcut — it is
what the source does, and for reasons that still hold here:

- **`forms/field.php`** hardcodes the `base-content` family. On this section's
  fixed `bg-neutral` band that is invisible in both light themes (surface-axis
  trap 2, the same one `status.php` needed its `surface` prop for).
- **`button.php` variant `ghost`** is `btn btn-outline`, also page-ground only,
  and its geometry is daisyUI's, not the `px-6 py-[15px]` bordered block the
  design uses.
- **`button.php` emits `type="button"` with no override**, so it cannot produce
  a submit control at all. A search form needs one.

The real fix is a `surface` axis on both parts — the same axis `status.php`,
`glyph-label.php` and `fact-row.php` already carry. That is a change to shipped
elements with existing callers, so it is flagged rather than taken mid-port.
Whoever does it should expect the same measuring discipline §6/§8/§10 needed:
`ghost`'s dark-band form is NOT `btn-outline` with a colour swapped.

Until then, every remaining dark-band template will hand-build these three
shapes. That is three files repeating a shape — the threshold at which
PORT-BRIEF rule 3a says promote it. Worth doing before Contact and DocsFaq,
which are the form-heaviest pages left.

### A second audit-scope gap, deliberately NOT fixed yet

`bin/audit-reuse.sh` scans `blocks`, `template-parts`, `parts/components` and
(since §11) `parts/site`. It does NOT scan theme-root templates, so `404.php`'s
hand-built `<button>` passes unremarked. Extending the audit now would mean one
exemption per template as each lands, and only one of the fourteen exists — so
the right moment is Phase 7's consolidation pass, once the shapes above have
been promoted and the true exemption list is knowable. Recorded here so it is a
decision, not an oversight.

## 13. The CPT source path had never executed (fixed)

Found while planning the Blocks QA page. Every block was verified with
`bin/wp lp render` against its `example.json`, and every `example.json` is
`"source": "manual"` — so `lp_resolve_source()`'s `latest` and `choose` branches
had never run for any block. Seeding demo records was the first time.

Five of the six source-backed blocks were wrong in CPT mode. Two root causes:

1. **Post-object fields return IDs.** `location` on `lp_class` and `lp_coach` is
   `return_format => 'id'`; blocks do `(string) $item['location']` and rendered a
   bare number. Fixed by `lp_flatten_references()` in `acf-fields.php`, driven by
   `lp_source_reference_fields()` — a map shaped like the `lp_source_taxonomy_for()`
   that sits beside it. The Classes board's `level` comes from the `lp_level`
   taxonomy and is attached in the same place, for the same reason: it is not a
   field on the record.
2. **Sessions live one level down.** `lp_class` holds a `sessions` repeater;
   Hero, Classes and CTA read `time` and `spaces` flat off the class. Fixed by
   `lp_expand_sessions()` behind `lp_resolve_source()`'s `'expand' => 'sessions'`
   option, which `cpt.php` had already nominated as the seam. **`source_limit` on
   those three blocks counts sessions, not classes** — the Hero board has a fixed
   slot count, so four rows may come from three classes. Proven on the QA page:
   Beginners Parkour carries two sessions and produces two rows.

Four smaller items in the same pass:

3. **Field-name drift between a CPT and its consumer.** `locations` read `type`
   while `lp_location` stored `site_type`; `classes` read `date_label` while
   `lp_class` stored `day_label`. `acf-groups.php`'s own header settles it —
   "field shapes are taken from the Storybook components that consume them" — so
   the CPT was renamed in both cases. Done before any content existed, when
   changing a derived `field_` key is still free. **After seeding it would have
   orphaned stored meta.**
4. **The `style` control on every CTA was dead.** `lp_field_action()` emitted a
   `solid|ghost|text` button_group and `lp_action()` returned it, but none of the
   nine `lp_action()` call sites read it — every variant is hardcoded, and
   `lp_action()`'s `'solid'` default is not even a valid `button.php` variant
   (`primary|inverse|ghost|destructive|icon|band`). The Storybook offers no
   per-CTA style choice; Phase 4 diffed the class strings byte-for-byte. Removed
   rather than wired up. Add one back only when a design offers the choice.
5. **`book_label` had no CPT source.** A sold-out class rendered "BOOK" where the
   manual row said "WAITLIST", because `book_label` is a manual-only subfield and
   `classes.php` fell back to a flat `'BOOK'`. Fixed by deriving it from
   `sold_out`, which is what the source component's own defaults do. A field an
   editor must keep in step with `sold_out` is a field that drifts.

**The lesson for the remaining phases.** `lp render` against `example.json`
proves markup, not data flow. Any block or template reading a CPT needs a render
against seeded records too. `/blocks-qa/` now runs that check continuously for
all six, and `bin/wp lp render <layout> --args='{"source":"latest"}'` is the
one-off form — but note that `--args` REPLACES the fixture rather than merging
into it, so a block rendered that way legitimately loses its copy and its CTAs.
Compare on the page, not on the command line.

### 6. The CPT list listed the entity it had already featured (fixed)

`Locations` shows a flagship site in its own panel and then a list of sites; in
CPT mode the list was "latest 5 locations", which INCLUDED the flagship, so
Vauxhall — The Arches appeared twice. `Coaches` has the same shape: a lead coach
with a portrait and quote, then a roster that included the lead.

The manual fixtures avoid this by hand — the author simply did not retype the
flagship into the list — and a query cannot infer that.

Fixed with an `'exclude_flag'` option on `lp_resolve_source()`, naming a
`true_false` field whose set records are dropped from the query. `lp_location`
already had `is_flagship`; `lp_coach` gained `is_lead` to mirror it exactly.

This is a RESOLVE-time option, not a field: `lp_field_source()` is untouched, so
the source control an editor sees is still identical in all six blocks and
`lp_assert_field_consistency()` still passes. Only the query changes.

The flag is also the honest data model. "Which coach leads" and "which site is
the flagship" are facts about the records, not about the block placing them, so
they belong on the CPT — and an editor who promotes a new head coach now does it
in one place.

### Accepted divergences on the QA page

Not bugs; do not "fix" them.

- **Coaches location case.** Manual rows say `PECKHAM`; CPT rows resolve to the
  post title `Peckham Rye`. The source component uppercases a short form the CPT
  does not store.
- **TrainInPerson vs Locations meta casing.** The two Storybook components format
  the same site differently — "SW8 1SR · 4 min from Vauxhall · Open 07:00–22:00"
  in one, uppercase in the other, and "8 min walk" versus "8 min". One CPT record
  cannot satisfy both; the demo data uses the Locations wording.
- **Coaches roster faces repeat.** Six committed demo images cannot give five
  coaches a unique portrait.

## 14. The Phase 5b page table was missing a page: `search.php` HAS a source

`docs/HANDOFF.md` listed `search.php`, `archive.php` and `index.php` together as
"no Storybook source, judgement call", and the B3 row said the same. That is
wrong for one of the three.

`src/stories/Search/SearchResults/SearchResults.js` is a fully designed 293-line
page — `Lc4uQ` "Search (Concourse)" plus five section masters in `vWQRz`. It was
missed because the Phase 5b table was built from `src/stories/Pages/`, and this
page is the only one that lives outside that directory. **There are 15 designed
pages, not 14.**

Two knock-on corrections to claims the handoff made:

- "Everything each page imports is already ported — the dependency closure was
  re-checked and is closed" held only for the pages that were enumerated. It was
  true for this one too, by luck: `SearchResultRow`, `BreadcrumbRail` and
  `ViewTab` were all already here.
- `src/stories/Components/Navigation/` (Breadcrumb, Dock, Link, Menu, Navbar,
  Pagination, Step, Tab) is Storybook scaffolding, not this design system.
  `SearchResults` imports none of it and hand-rolls its own pagination. Do not
  port that directory.

The lesson for B4–B6: enumerate from `src/stories/`, not `src/stories/Pages/`.

## 15. Search: five departures from `SearchResults.js`, and why

All five are in `search.php`'s own docblock; this records the reasoning once.

1. **Filter tabs are links, not `ViewTab` buttons.** The source's `onClick` is a
   Storybook callback that `view-tab.php`'s docblock already says does not come
   across. A post-type filter on WordPress is a URL. `view-tab.php` gained an
   `href` form — same class strings, `aria-current="page"` instead of
   `role="tab"`/`aria-selected`, because an `<a href>` is a link and must not
   claim tab semantics it cannot honour (Port Brief a11y rule). This mirrors
   `button.php`'s existing `<a>` vs `<button>` split; it is not a new shape.

2. **The SORT select is visual, not dropped.** `wsypA` draws it (`SORT` +
   200×42, one option "Most relevant") and the source ports it byte-for-byte.
   Extra options or onchange JS would be invented; WordPress search is already
   relevance-ordered. **The `<select>` markup is NOT `forms/select.php`** — the
   source is `w-[200px] bg-transparent` with an external `aria-labelledby`
   span; the part is `w-full`, daisyUI-chromed and stacks its own `<label>`.
   Routing one through the other would silently change the design.

3. **`24 RESULTS · 0.04s` loses its timing.** WordPress exposes no search timing;
   `timer_stop()` measures page generation. A wrong number is worse than a
   missing one.

4. **The rail is the design's four kinds, not every public type.** ARTICLES is
   the `blog` CPT (v7 import), not native `post`. Coaches, locations, support
   and notifications stay out of the main search query so ALL equals the sum of
   the four tabs and the query-bar hint stays true. Zero-count tabs still
   render — `wsypA` always draws all four.

5. **`CLEAR SEARCH ✕` and `CLEAR ✕` link home.** The source's breadcrumb
   action has no href and the query-bar control is `<button type="reset">`,
   which on a submitted query restores the submitted value. Home is the honest
   empty-search destination.

**No zero-results state**, per the source's own explicit note. With no results
the query bar reports `0 RESULTS` and the filter rail still renders (tabs stay
operable). The results band and pagination do not.

Row content maps to the design's own vocabulary — `category` is its singular
word per kind (`LESSON` for a tutorial, `ARTICLE` for a post), `meta` its plural
section word (`BLOG` for posts) plus the one real detail that kind carries: a
class's `price` field, a post's month. **The design's `TUTORIALS · FREE` is not
reproduced** — no field backs it, and claiming a price is a claim.

## 16. Pagination promoted; `button.php` gained `band_text`

Two shared-element changes in B3, both with real callers on the day.

**`parts/components/pagination.php`** is the source's inline `initPagination`
(`l6bk8` wrapping `bWhir`), promoted because three templates need it — search
plus the archive body that `archive.php` and `index.php` both include. It is the
design system's only pagination shape. `data-component` is `pagination`, not the
source's `search-pagination`; that is its one DOM departure.

Two things the design does not specify, resolved without inventing a visual:

- **No disabled prev/next.** On page one the previous slot renders as an empty
  `<span>` so the `justify-between` row keeps its alignment. Nothing undesigned
  is drawn.
- **No ellipsis.** Every page number renders. Marked `ponytail:` in
  `lp_pagination_args()` with the ceiling — window it when a real query runs past
  ~10 pages *and* the design gains a truncated state to window it with.

**`button.php` gained `band_text`** — the query bar's `CLEAR ✕` (`A1PesB`). It is
a `button` variant rather than a `text-link` variant because every `text-link`
variant renders an `<a href>` and a form reset has no href.

This is the one place B3 departs from Session 2 correction 2's "a second caller
is the moment it becomes a variant". The alternative was a third file-level
exemption in `bin/audit-reuse.sh`, which would have switched the `<button>` rule
off for the whole of `search.php` — a file being newly filled with markup. One
array entry keeps the gate on and costs a line. The rule's purpose is to stop
*speculative* variants; this one had its caller in the same commit. Note it is
**not** the dark-band bordered block from `NotFound.js:123` that correction 2 is
about — that shape still has exactly one caller and still should not be promoted.

## 17. B4, part 1: the ClassesHeaderCluster promotion and what it forced

`parts/components/classes-header-cluster.php` is the coordinated promotion the
handoff reserved — three pages open with it, and it holds the three view tabs
and three filter cells so they are defined once rather than three times. It
owns no markup beyond a wrapper; it is four already-ported parts in order.

It does **not** render nav. The source says the same, and says why: bundling
nav made the masthead's `<h1>` fall outside `<main>` on two of the three pages.
ClassesListings' own docblock records that it could not fix that from the
directory it owned. Here the fix is free — each template calls `get_header()`
and puts the cluster inside its own `<main>`.

Three things the promotion forced, each with callers on the day:

- **`view-tab.php`'s `rich` variant gained the `href` form** it already had for
  the plain variant. The Classes view rail navigates between three separate
  pages; the source exposes that as an `onTabSelect` callback precisely because
  it has no opinion on routing, and on WordPress the answer is a URL.
- **`view-rail.php` drops `role="tablist"` when its tabs are links.** Three
  links to three pages are navigation. Keeping the role would promise arrow-key
  behaviour that does not exist.
- **Tab metas are counted, not fixed.** `18 SESSIONS` / `13 CLASS TYPES` /
  `6 SITES` are literals in the source and would be wrong the first time an
  editor added a class. `lp_classes_view_tabs()` counts them. It reads the
  `sessions` repeater per class — marked `ponytail:` with the ceiling.

## 18. The filter grid is a real form, and how the missing submit is handled

The repo owner chose a GET form with a small auto-submit behaviour over a REST
endpoint plus a client-side search library. The reasoning is worth keeping,
because the question will come back:

- All four CPTs register `'show_in_rest' => false` (`app/setup/cpt.php`),
  deliberately — "Classic editor only". "Use the WP API" is therefore not a
  wiring job but a reversal of that decision, which also re-opens the block
  editor's REST surface.
- The dataset is ~13 class types over three filter cells with no facet counts,
  no ranking and no refinement UI. An InstantSearch-style library earns its keep
  on the opposite shape; here most of the work would be custom widgets
  suppressing what the library provides, plus an adapter for WP's response.
- A GET form gives shareable, bookmarkable, crawlable URLs and works with JS
  off — and it is the same mechanism ClassesAgenda's `?week=` will use.

**If site-wide search ever outgrows `WP_Query`, that is the case for an index
(Typesense/Meilisearch/Algolia) behind `search.php`** — and Classes can ride the
same index later. A bespoke REST endpoint now would be thrown away.

Mechanically: `filter-grid.php` gains an optional `action`, and with it wraps
itself in `<form method="get">`. The design draws NO submit control, so two
invisible things carry that job — a `sr-only` submit (the only mechanism with
JS off, and reachable by keyboard and screen reader), and `data-filter-form`,
which `assets/js/elements/FilterForm.js` reads to submit on a **select** change.
Text inputs are not auto-submitted; that would reload the page mid-word.
Without `action` the grid renders exactly as it did before.

The parameters are the theme's own — `class_search`, `class_level`,
`class_site` — not `s` and the `lp_level` taxonomy var. `?s=` on any URL makes
`is_search()` true at parse_query time, so `/classes/?s=foo` would route to
`search.php` and the Classes template would never run. `app/setup/queries.php`
sets `s` from `pre_get_posts` instead, which filters without touching the
conditional flags. No nonce: these are public read-only navigation parameters
on published content, and a nonce on a shareable URL would break the sharing.

## 19. ClassesListings: the featured class, and the one spec the model cannot carry

**`is_featured` is new on lp_class**, mirroring `is_lead` on Coach and
`is_flagship` on Location for the reason §13 records — the page shows one class
above the grid and the rest below, so the grid query must know which is already
on show. No ordering picks it; the design's featured class is neither first nor
newest.

It heroes the UNFILTERED page only. The design draws no filtered state, so this
is a judgement call: the alternative leads a filtered page with a class that
does not match what was asked for.

**`SITES` does not fit the data model.** The design's featured strip reads
PRICE `£15` / SITES `6` / DURATION `60 min` / RUNS `Tue + Thu`. Three come from
real fields. `SITES` cannot: a class here has ONE `location` (an ACF
post_object), so the honest count is 1, and 1 is what renders.

Do not paper over this with a literal `6`. The fix is a `locations`
relationship field in place of the single `location` — a data-model change for
the repo owner to approve, not a port decision. There are two readings of the
design's `6` (this class runs at six sites / the org has six sites) and
resolving that is part of the same decision.

Two smaller derivations, both from a field's own documented format:

- **DURATION** is the first ` · ` segment of `subtitle`, whose ACF instructions
  give exactly that format (`e.g. "60 min · all kit provided"`).
- **RUNS** joins the session `date_label`s, so it reads `TODAY + THU` where the
  design reads `Tue + Thu`. The field holds a board label, not a weekday.

The seven seeded classes gained `excerpt` values **transcribed from the design**
— `CLASS_CARDS`' per-card notes (`YV0EG/*`) and the featured lead note
(`xWBih/CDivu`) in ClassesListings.js. None is authored. Without them the cards
render note-less, which is not what the design shows.

The featured photo's figure gains `relative` over the source's class string.
The source draws an empty box because the Storybook has no media library; with
a real image, media-photo's `fill` layout needs a positioned ancestor. Nothing
else about the box changes, and with no thumbnail the bare source figure renders
unaltered.

## 20. The sessions repeater had no date, so the Agenda was unbuildable

ClassesAgenda is a dated week board — seven named days, 18 sessions, week
controls reading "Week 29 · 13th – 20th July 2026". None of that was derivable.

The `sessions` repeater carried `date_label`, `time`, `spaces`, `sold_out`.
`date_label` is the BOARD label a departure row prints ("TODAY", "THU"), not a
date, and every seeded session used one of those two strings. Nothing could be
compared to a week window, and mapping "TODAY" onto a weekday would have been
fabricating the timetable — the failure the Port Brief names.

**Resolved by the repo owner: a `date` sub-field was added**, an ACF date
picker returning `Y-m-d`. Both fields now exist with distinct jobs, and their
ACF instructions say so, because the pair invites exactly the confusion that
caused this. The other option weighed was a `weekday` recurrence field
generating dates rather than storing them — truer to how a timetable works, and
worth revisiting if per-week data entry becomes a chore.

**Demo sessions are seeded with `@MON`…`@SUN` tokens.** `lp_seed_weekdays()`
resolves them against the week of the seed run, so the board is never empty and
committed demo content never names a week that has already passed. Twelve
sessions are seeded — the rows of ClassesAgenda's own `WEEK` constant whose
titles have a class record here; the other six name classes this site does not
have.

The knock-on: `date_label` values changed from `TODAY`/`THU` to weekday
abbreviations, which the Hero, Classes and CTA boards also print. That is demo
data, not design, and the blocks still render.

## 21. Classes view pages live at `/classes-agenda`, not `/classes/agenda`

`/classes/` is the lp_class post-type archive, so a page under it needs a parent
page whose slug collides with that archive. The two view pages are therefore
`classes-agenda` and `classes-map` at the top level.

A URL is routing, not design — the source's `/classes/agenda` is a Storybook
href literal, not a signed-off `.pen` decision — so this is recorded rather than
worked around with rewrite rules, which would also have introduced a second URL
resolving to the same page. `lp_classes_page_url()` resolves the hrefs from the
seeded pages and falls back to the source's own path when a page is missing, so
the view rail always points somewhere.

`lp_seed_template_pages()` is new and closes half of the Phase 6 item HANDOFF
lists as "pages + their templates" — it seeds Legal and Classes — Agenda with
`_wp_page_template` set. Add Map, Contact and DocsFaq to its map as those land.
A page template is unverifiable without a page that uses it; `lp render` cannot
reach one and greps prove nothing (§13).

## 22. What the Agenda computes rather than assumes

Everything on the page except the design's own fixed copy is counted or read
from the clock:

- **`3 RUNNING NOW`** — a session is running when now sits between its start
  (its `date` + `time`) and its end (start + the duration in the class's
  `subtitle`). A row with no parseable duration is not counted rather than
  assumed. The stamp is omitted entirely when nothing is running.
- Week session count, per-day band counts, the board foot's site/day/session
  tally, and the `LIVE · UPDATED` time.
- Prev/next week labels are the neighbouring weeks' real labels, not the
  design's fixed "Week 28"/"Week 30".

Two small departures from the design's own label format:

- The week runs **Monday to Sunday**. The design's "13th – 20th July 2026" ends
  on the following Monday; this ends on the Sunday, because that is the week
  the board actually shows.
- A week straddling two months names both months. The design's example week
  sits inside one, so it names the month once — which would render
  "27th – 2nd August" and read as though the 27th were in August.

No element change was needed for the week controls: the source's prev/next
chevrons are Button `variant="icon"`, and button.php already renders an `<a>`
as soon as it is given a href.

## 23. B4 closed: what ClassDetail and ClassesMap added, and the two gaps left

Both remaining B4 pages are ported — `single-lp_class.php` and
`templates/classes-map.php`. Four fields were added first, all additive and
none ambiguous: `lp_location.{meeting_point,transport_rail,transport_bus}`
(the three `site-panel.php` already took as args but had nowhere to store),
`lp_class.what_to_expect` (a repeater, because ChecklistItem's numerals are
meaningful order — the same reasoning §20 used for the sessions `date`), and
`lp_coach.bio` (a different field from `quote`, which is the Coaches block's
pull-quote in the coach's own voice).

**`lp_location` already carried `latitude`/`longitude`.** ClassesMap therefore
projects real coordinates into the placeholder rectangle rather than porting
the source's six `x`/`y` percentages — which the source's own JSDoc admits are
invented, because the `.pen` has no pixel-space contract to transcribe. A site
added tomorrow lands in the right place with nobody editing a percentage.
Verified against real geography, not by eye: Wembley Park computes to
`left 8.00% / top 8.00%` (westernmost and northernmost), Stratford East to
`left 92.00%` (easternmost), Peckham Rye to `top 92.00%` (southernmost).

Two text fields, not ACF's `google_map` type: that needs a Google Maps JS API
key on `acf/init`, which this project has never configured and which would put
an external service and a billing account behind the admin screen. If a key is
ever added, `google_map` is the upgrade and the projection is unaffected.

### The two gaps, both data-model and neither closable by content

**`lp_class` cannot express a recurrence.** ClassDetail's WHEN fact reads
`Saturdays · 10:30–12:00` and ClassesMap's index meta reads
`STRATFORD EAST · WEDNESDAYS 18:00 · WITH ANDY`. Both assert a weekly pattern
and the first also asserts a time RANGE. The model has neither: `sessions`
holds dated occurrences with a single `time`, and duration is buried inside the
`subtitle` string (`"60 min · all kit provided"`). This is §19's `SITES` and
§20's `date_label` a third time.

Handled differently on the two pages, deliberately. ClassDetail **omits** the
WHEN row — it is a labelled fact, and a labelled fact that is wrong is worse
than an absent one. ClassesMap **names the next real session** instead
(`PECKHAM RYE · MON 07:00 · WITH KIE PICCIO`), because that claims an
occurrence rather than a pattern, and dropping the whole meta line would gut
the index. Closing it needs a recurrence field (weekday + time range), which is
the repo owner's decision.

**There is no audience field.** ClassDetail's WHO fact reads `Adults (14+)`.
Nothing stores it; the seeded classes carry audience only as prose inside
`subtitle` (`"women and non-binary only"`, `"ages 5+ with an adult"`), so
nothing can filter or display it structurally. Two readings — an age policy
(`14+`) or an audience label (`Adults` / `Kids` / `Women's session`). The
seeded set varies by audience rather than by a numeric gate, so the label
reading looks right, but that is a recommendation and not a decision. The row
is omitted rather than invented.

Also recorded, not actioned: `lp_class` has no video field, so ClassDetail's
"WATCH THE CLASS" control renders without a URL. `lp_tutorial` has `video_url`;
mirroring it is the obvious shape, but a class's promo clip and a how-to
tutorial are different assets and that is a modelling call, not a port one.

### A defect the gates could not see

`single-lp_class.php` first rendered its About group unconditionally while
`WHAT TO EXPECT` beside it was guarded, so every class showed an
"ABOUT THIS CLASS" label over nothing — no `lp_class` record seeds
`post_content`. Every deterministic gate passed; only curling the page and
reading it found this. §13's lesson holds: markup passing is not data flow
passing. Both templates now guard every group whose label would otherwise
render over an empty value, verified by draft-ing all six locations and
re-fetching `/classes-map/` (200, zero pins, meeting-points section absent,
no PHP notices).

The About copy stays raw rather than `the_content`-filtered: the design puts it
inside ONE styled `<p>`, and the filter would wrap it in `<p>` tags of its own.
A class body that grows past one paragraph is the moment to drop the design's
`<p>` and let the filter own the markup.

### Class-string fidelity

ClassesMap carried **10 of 10** source literals byte-for-byte. ClassDetail
carried **20 of 22**; the two absent are the caption-chip position (the chip
asserts a day and a running time with no backing data, so it was dropped) and
`w-full h-full object-cover` (the Storybook's bare `<img>`, which PORT-BRIEF
rule 3b replaces with `media-photo.php`).

## 24. B5 part 1: TutorialsIndex, and the one thing the model cannot hold

`archive-lp_tutorial.php` is ported and needed **no new ACF fields** — checked
before dispatch, not discovered during. Everything the design's cards want
already exists: `duration` is a field, the card note is the post EXCERPT, the
`01 ·` sequence is `menu_order`, and the VAULTING → STEP-VAULT hierarchy is
`lp_series`, which `app/setup/cpt.php:76` already registers
`hierarchical => true`. Kicker reads the term's PARENT, meta reads the term.

All three seeded `lp_series` terms are currently flat, so every card renders an
empty kicker and `01 · VAULTS`. That is correct against the real data — the
kicker appears the moment an editor nests a term under a category — and is
recorded here so it is not later mistaken for a bug.

**`RESUME 2:10` cannot be built.** Card 4 carries it in the design and it is
per-user watch progress: this theme has no user model, no auth and no progress
store. No card renders it. Unlike §23's two gaps, this is NOT a one-field fix —
it implies accounts and a progress store, so it is a product decision, not a
schema one. The sibling `NEW` flag IS derivable and is implemented, on a 30-day
window from `get_post_time()` (the design carries no numeric value to match, so
the window is a documented judgement call).

**Counts are counted.** `840 videos`, `12 series`, `10 VIDEOS` and
`Search 840 tutorials…` are literals in the source that would be wrong the first
time an editor added a tutorial. All four are computed, per §17's precedent, and
render as 3 against the seed. One documented departure: the masthead note's
count renders as a digit where the source spells it out ("Eight hundred and
forty"), because the value is now computed.

**The tutorial filter mirrors the Classes filter rather than forking it.**
`lp_filter_tutorial_archive()` + `lp_tutorial_filter_values()` in
`app/setup/queries.php` take `tutorial_search` / `tutorial_category` /
`tutorial_move` / `tutorial_sort` — theme-owned params, not `s` and not the
public taxonomy var, for the reason §18 records. Category lists parent
`lp_series` terms, Move lists children of the selected category, cascading on a
normal reload with no new JS. Sanitisation is identical to the Classes path
(`sanitize_text_field` + `wp_unslash`, `sanitize_title`).

"03 The Board" is deliberately NOT a `BoardShell` — both the source's JSDoc and
`BoardShell.js`'s own exclude this node. It is `meta-row` → `rule` → a
`video-card` grid → a bare `page-onward` rail, and that inline rail is a
different instance from the page-level one lower down.
