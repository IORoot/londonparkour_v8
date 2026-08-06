# Consolidation analysis — Storybook `Components/`, `Blocks/`, `Site/` → PHP partials

Source read in full: `ldnpark2601/src/stories/{Components,Blocks,Site}/**/*.js` (main files only,
36 files). Already-ported shared parts (assumed available, NOT re-proposed): `button`, `rule`,
`badge`, `chip`, `status`, `glyph-label`, `nav-link`, `view-tab`, `logo`, `glyph`, forms
`field`/`select`/`text-area`.

**Scope correction applied:** this pass targets concrete, self-contained UI atoms — button-shaped
things, icon usage, form controls, small repeated atoms — in that priority order. Layout-only
findings from an earlier pass (section shells, gutter wrappers, "head row" compositions) are kept
at the bottom, clearly marked **NOT RECOMMENDED**, for the record.

---

## 1. Button-shaped things

### 1a. `cta-pair` — Button + adjacent link/Button cluster
**Occurrences: 3** — `Blocks/Hero.js`, `Blocks/CTA.js`, `Blocks/PrivateCoaching.js`.

- `Hero.js`: `<div class="flex items-center gap-[28px] flex-wrap"><span data-mount="primary-cta"></span>{secondary text link}</div>`
- `CTA.js`: `<div class="flex flex-wrap items-center gap-[28px]"><span data-mount="primary-cta"></span>{altCta text link}</div>` —
  same class set as Hero's, only token order differs (`flex-wrap` position) — literal drift, not a
  deliberate variant.
- `PrivateCoaching.js`: `<div class="mt-[30px] flex items-center gap-[14px] flex-wrap"><span data-mount="primary-cta"></span><span data-mount="ghost-cta"></span></div>` — same shape, but a **Button+Button** pair (not Button+link) at a tighter `gap-[14px]`.

**VARIES** by pairing type (button+link vs button+button) and gap (`28px` vs `14px`). Both CTAs in
every case are already correctly mounted via `Button.init` — the only duplicated thing is the
wrapper `flex items-center gap-[X] flex-wrap` row around them.

**Signature:** `cta-pair(gap: '28'|'14')` → literal wrapper class from a 2-entry lookup; children
stay `button.php` calls, never re-declared.

### 1b. Raw `<a>`/`<button>` markup that bypasses `button.php` (compliance flags, 1 occurrence each — still worth flagging)
- **`Components/AsidePanel.js`** — `CTA_CLASSES` (`flex items-center justify-between gap-[12px] w-full h-[60px] px-[22px] bg-primary text-primary-content hover:bg-neutral hover:text-primary transition-colors duration-150 focus-visible:outline …`) renders a full-width label+icon CTA as raw `<button>`/`<a>` markup. The file's own JSDoc says this is deliberate because `button.php`'s variants are "label-only or icon-only, fixed 15px/24px padding" — none support a full-width `justify-between` band. **Recommendation:** this is a real gap in `button.php`, not a second component — add a `full-width`/`band` variant to `button.php` rather than leaving this hand-rolled.
- **`Site/SiteNav.js`** — the desktop "FIND A CLASS" CTA (`${barHeight} inline-flex items-center gap-[12px] px-[30px] bg-primary text-primary-content font-label text-[12px] font-semibold uppercase tracking-[1px] hover:bg-primary/85 …`) is explicitly documented as NOT `button.php` because it must be full-bar-height with no padding constraint. Same underlying gap as AsidePanel's — both want a "flush, fills its container" button variant.
- **`Site/SiteNav.js`** promo-card CTA (`inline-flex items-center gap-[9px] pt-[8px] font-label text-[11px] font-semibold uppercase tracking-[1px] text-primary-content …` + trailing `icon-arrow-right`) is a plain hand-rolled link+icon inside the Classes popover promo card — no documented reason it can't be `button.php` (`variant="inverse"`-shaped, on-primary). Flag as a straightforward bypass to fix at port time.

---

## 2. Icon usage

**Blanket rule:** every `createIcon({...})` call and every raw inline `<svg>` in these 36 files
must become `lp_icon()` calls at port time — that's every icon in every Component/Block/Site file
read. Not enumerated individually below; the two patterns below are the ones with a *repeating
wrapper* worth turning into a shared atom, per the brief.

### 2a. `icon-circle` — icon centred in a solid rounded-full colour disc
**Occurrences: 2** — `Components/VideoCard.js` (`variant="full"` play glyph, decorative) and
`Components/VideoStage.js` (Play button, interactive). **VARIES.**

- `VideoCard.js`: `w-[34px] h-[34px] rounded-full bg-primary text-primary-content grid place-items-center shrink-0` — a non-interactive `<span>`, `aria-hidden`.
- `VideoStage.js` `PLAY_BUTTON`: `group relative w-[78px] h-[78px] shrink-0 rounded-full bg-primary text-primary-content grid place-items-center transition-colors duration-150 hover:bg-neutral hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary` — a real `<button>` with hover-inversion and a focus ring.

Same visual atom (icon-in-a-disc), two sizes, two interactivity levels. **Signature:**
`icon-circle(size: '34'|'78', interactive: bool)` → literal class per combination, never
concatenated (the interactive variant is a strict superset of classes, not a computed diff).

### 2b. `chevron-affordance` — trailing chevron that brightens on row hover
**Occurrences: 6 files** (7 instances counting `ListRow`'s two internal surface variants) —
`Components/BoardRow.js`, `Components/ListRow.js` (×2, `board`/`page` surfaces),
`Components/MediaCard.js`, `Components/VideoCard.js` (`variant="compact"`),
`Components/SearchResultRow.js`, `Blocks/Locations.js` (site row). **Confidence: high. VARIES**
by rest-opacity and hover colour, tracking each row's surface.

Literal strings (icon is always `icon-chevron-right`, `aria-hidden="true"`):
- `BoardRow.js`: `hidden sm:inline-flex text-neutral-content/50 group-hover:text-primary transition-colors duration-150 shrink-0` (icon `w-3.5 h-3.5`)
- `ListRow.js` `board`: `text-neutral-content/30 group-hover:text-primary` (+ `transition-colors duration-150 shrink-0` added at the call site; icon `w-3.5 h-3.5`)
- `ListRow.js` `page`: `text-base-content/30 group-hover:text-accent`
- `MediaCard.js`: `text-base-content ${isLink ? 'group-hover:text-accent transition-colors duration-150' : ''}` (icon `w-3.5 h-3.5`)
- `VideoCard.js` compact: **byte-identical** to `MediaCard.js`'s string above (icon `w-3.5 h-3.5`)
- `SearchResultRow.js`: `shrink-0 text-base-content/65 group-hover:text-accent transition-colors duration-150` (icon `w-[13px] h-[13px]` — the one outlier size, 13px vs everyone else's `w-3.5 h-3.5`/14px)
- `Locations.js` site row: `text-accent-content/70 group-hover:text-accent-content transition-colors duration-150 shrink-0` (icon `w-3.5 h-3.5`)

**Signature:** a `surface`-keyed lookup (`board`, `page`, `accentBand`, …) of whole literal wrapper
strings, icon fixed at `w-3.5 h-3.5` except the one documented `SearchResultRow` outlier at
`w-[13px] h-[13px]`.

### 2c. `Site/SiteFooter.js` hardcodes brand-mark `<svg>`s outside `createIcon()`
Instagram/YouTube/Facebook path data is vendored directly as raw `<svg viewBox="0 0 24 24" ...>`
markup (`SOCIAL_ICONS` map), not routed through `createIcon()`/the icon sprite at all — the file's
own JSDoc says this is because "no icon set already carries brand marks." **Flag, don't force**:
these need `lp_icon()` treatment too (or an equivalent brand-icon registration), but they're a
different case from the sprite-icon findings above — 3 icons, 1 file, no cross-file duplication,
just a compliance gap to close at port time.

### 2d. Status dot — should migrate to the already-ported `status.php`, not stay hand-rolled
**Occurrences: 3** — `Components/AsidePanel.js` (`spotsLeft` dot), `Site/SiteNav.js` (status-rail
dot), `Components/VideoStage.js` (now-playing dot). All three are a small solid
`rounded-full bg-{primary|accent}` dot paired with a label — literally the shape `status.php`
already covers:
- `AsidePanel.js`: `inline-block w-[6px] h-[6px] rounded-full ${surf.dot}`
- `SiteNav.js`: `w-[6px] h-[6px] rounded-full bg-primary flex-none`
- `VideoStage.js`: `inline-block w-[6px] h-[6px] rounded-full bg-primary`

All three hand-roll this instead of using `status.php` for a *historical* reason — `AsidePanel.js`'s
own JSDoc says `Status` used to be page-ground-only and would go invisible on these fixed dark
bands. That's since been fixed (`BoardRow.js`'s JSDoc confirms: "`Status` has since gained
[a surface prop] — `surface: 'board'` — so it IS now safe here"). **Recommendation: not a new
part** — route all three through the existing `status.php` (`surface: 'board'`) at port time; the
gap that justified hand-rolling them no longer exists.

---

## 3. Form controls

No checkbox, radio, toggle, or file-input shaped markup appears anywhere in these 36 files.
`Components/FilterGrid.js` is the only form-adjacent file and it already composes the existing
`Forms/Field`/`Forms/Select` correctly (no bypass to flag). **None of the not-yet-ported form
control types (checkbox/radio/toggle/file) are needed by any component in this batch** — nothing
to flag here.

---

## 4. Small repeated atoms

### 4a. `text-link-cta` — uppercase text link, opacity-fade hover
**Occurrences: 5** — `Components/BoardShell.js` (`FOOT_CTA`), `Blocks/Classes.js` (foot CTA),
`Blocks/TrainInPerson.js` (`ctaLabel`), `Blocks/Coaches.js` (`linkLabel`),
`Components/BreadcrumbRail.js` (`action`). **VARIES** by size/tracking/surface; two are byte-identical.

- `BoardShell.js` `FOOT_CTA`: `font-label text-[12px] font-semibold uppercase tracking-[0.9px] text-primary hover:text-primary/70 transition-colors duration-150`
- `Classes.js` foot CTA: **byte-identical** to the string above.
- `TrainInPerson.js`: `font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-accent-content hover:text-accent-content/70 transition-colors duration-150 whitespace-nowrap`
- `BreadcrumbRail.js`: `font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary hover:text-primary/70 transition-colors duration-150 whitespace-nowrap`
- `Coaches.js` (voice fragment, positional utilities `mt-[28px] inline-block self-start` stripped): `font-label text-step--2 font-semibold tracking-[0.5px] text-accent hover:text-accent/70 transition-colors duration-150`

**Signature:** a variant-keyed lookup (`board` 12px / `boardCompact` 10px / `accentBand` 11px /
`pageAccent` step--2), whole literal strings, no concatenation. `whitespace-nowrap` stays a
call-site modifier, not baked into the variant.

### 4b. `avatar-initial-chip` — square initial avatar (recommend reuse, not a new part)
**Occurrences: 2**, both in `Components/BlogCard.js` (`grid` and `lead` variants), and both are
**byte-identical** to what `Components/Byline.js`'s `sm` size already produces:
- `BlogCard.js` grid: `bg-neutral-content text-neutral w-[26px] h-[26px] flex items-center justify-center` = `Byline.js` `SURFACE.board` + `SIZE.sm`, combined verbatim.
- `BlogCard.js` lead: `bg-accent-content text-accent w-[26px] h-[26px] flex items-center justify-center` = `Byline.js` `SURFACE.accent` + `SIZE.sm`, combined verbatim.

The surrounding name/date text does **not** match (`BlogCard` uses `font-body`, `Byline` uses
`font-heading`/`font-label`) — a real, deliberate difference in each file's own voice, not
accidental drift. **Recommendation: don't build a new atom.** Once `Byline` is ported, have
`BlogCard` compose `byline(size: 'sm', surface: 'board'|'accent')` for its author row instead of
hand-rolling the avatar box — one-line fix at the call site, not a new shared primitive.

### 4c. Media photo treatments (lower priority, real but not button/icon/form)
Two small, genuinely-distinct media atoms, kept for completeness since they satisfy the "atom" bar
(self-contained, own semantics, 2+ files, whole-literal-string lookup):
- **`photo-scrim-media`** (absolute `<img>` + dark scrim overlay) — 5 occurrences
  (`PageMasthead.js`, `Hero.js`, `VideoCard.js` full, `VideoStage.js`, `Locations.js` flagship).
  `<img>` is **byte-identical** everywhere: `absolute inset-0 w-full h-full object-cover`. Scrim
  value is a different literal every time (`bg-gradient-to-b from-neutral/95 to-neutral/62` /
  `bg-neutral/90` / `bg-neutral/65` / `bg-secondary/45` / `bg-neutral/35`) — needs a named lookup,
  never invented.
- **`photo-fill-plain`** (absolute `<img>`, no scrim) — 2 occurrences (`PrivateCoaching.js`,
  `Coaches.js` lead portrait), **byte-identical**: `absolute inset-0 h-full w-full object-cover`
  (note the `h-full w-full` order — consistently swapped vs. the scrim family's `w-full h-full`,
  marking these as a genuinely separate lineage, not the same atom with a size difference).

---

## NOT RECOMMENDED — layout only, do not factor

- **`section-head`** (eyebrow + H2 + note wrapper) — `Classes.js`, `Coaches.js`, `Pricing.js`,
  `Locations.js`, `PrivateCoaching.js`. This is a real, well-documented drift (see
  `SectionHead.js`'s own JSDoc), but it's a page-section head composition, not a self-contained
  atom — out of scope per the corrected brief. *(Still worth doing eventually — flagged here for
  the record, not proposed as an atom.)*
- **`board-head`** (title + live-stamp row, `BoardShell.js`/`Classes.js`) — layout row, not an atom.
- **`column-head-row`** (hairline column-label strip, `BoardShell.js`/`Classes.js`) — layout row.
- **`board-foot-row`** (CTA + note wrapper, `BoardShell.js`/`Classes.js`) — the wrapper itself is a
  layout row; the CTA link inside it is already captured as atom **4a**.
- **Section gutter / shell** (`px-6 lg:px-16`, full-bleed background wrappers) — real repeated
  string across several files, but background + vertical rhythm genuinely differ per section (some
  Blocks are deliberately mid-migration per the Phase-7 layout contract, per their own JSDoc) — a
  layout concern, not a UI atom.
- **`eyebrow + stamp` differing-weight row** (`TrainInPerson.js` only) — single instance,
  explicitly documented as intentionally hand-built rather than forced into `MetaRow` — not a
  repeat, not a part, and it's a layout row anyway.

---

## Summary table

| Rank | Atom | Occurrences | Identical / Varies | Category |
|---|---|---|---|---|
| 1 | `chevron-affordance` | 6 files / 7 instances | Varies (2 byte-identical) | Icon usage |
| 2 | `text-link-cta` | 5 | Varies (2 byte-identical) | Small atom |
| 3 | `photo-scrim-media` | 5 | Img identical, scrim varies | Small atom |
| 4 | `cta-pair` | 3 | Varies | Button-shaped |
| 5 | `icon-circle` | 2 | Varies (size/interactivity) | Icon usage |
| 6 | `photo-fill-plain` | 2 | Identical | Small atom |
| 7 | status dot → migrate to `status.php` | 3 | Near-identical | Icon usage (compliance) |
| 8 | `avatar-initial-chip` → reuse `byline` | 2 | Identical (avatar only) | Small atom (compliance) |
| — | `button.php` bypasses (AsidePanel CTA, SiteNav desktop CTA, SiteNav promo CTA) | 1 each | N/A — compliance flags | Button-shaped |
| — | `SiteFooter.js` raw brand `<svg>`s | 3 icons / 1 file | N/A — compliance flag | Icon usage |
