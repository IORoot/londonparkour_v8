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

2. **The SORT select is dropped.** A GET `<select>` with no submit control needs
   JS this theme does not have, and the design draws no submit beside it.
   Shipping it inert or inventing a submit are both worse than omitting it.
   Build it when sorting is designed. **The `<select>` markup in the source is
   NOT `forms/select.php`** — the source is `w-[200px] bg-transparent` with an
   external `aria-labelledby` span; the part is `w-full`, daisyUI-chromed and
   stacks its own `<label>`. Routing one through the other would silently change
   the design. Recorded so nobody "fixes" it later.

3. **`24 RESULTS · 0.04s` loses its timing.** WordPress exposes no search timing;
   `timer_stop()` measures page generation. A wrong number is worse than a
   missing one.

4. **Two tabs the design predates.** The design has four kinds; this theme
   registers six public post types, so coaches and locations are searchable and
   would have appeared in results with no tab and no place in the ALL total.
   Their labels are the registered CPT labels uppercased. The design's own four
   words are used verbatim. Zero-count tabs are not rendered.

5. **`CLEAR ✕` stays `<button type="reset">`, as drawn — and is semantically
   wrong on WordPress.** On a submitted query a reset restores the submitted
   value, so it clears the user's *edits*, not the search. Reported rather than
   redesigned: the honest fixes are a link to an empty-query state (which the
   design does not have) or JS (which this theme does not load for forms).

**No zero-results state**, per the source's own explicit note. With no results
the query bar reports `0 RESULTS` and the filter rail and results band are not
rendered at all.

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
