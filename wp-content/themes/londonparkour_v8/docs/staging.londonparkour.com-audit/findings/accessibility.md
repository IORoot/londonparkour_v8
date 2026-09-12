# Accessibility findings — staging.londonparkour.com

**Target:** https://staging.londonparkour.com/ (HTTP basic auth; credentials not recorded)  
**Date:** 2026-09-11  
**Standard:** WCAG 2.2 Level AA  
**Method:** Lighthouse 13.4.1 accessibility (mobile), rendered accessibility-tree snapshots, keyboard on the booking drawer. VoiceOver/NVDA was **not** used. A Lighthouse score of 100 is not conformance.

**Host:** staging V8 theme. Not live V7.

---

## Executive summary

**Not yet WCAG 2.2 AA.** Worst Lighthouse accessibility score this run: **0.96** (`/classes/adult-beginners-outdoor/`). Three pages scored **1.00**. Automated tools did not catch the homepage decode headline, which the accessibility tree reads as individual letters.

The landmark contract is in good shape: skip link, one `<main id="main">`, real headings, named primary/footer nav, booking `<dialog>` labelled “Booking panel”. Forms that take a booking are labelled and support autocomplete. Tutorials ship captions and a transcript. `prefers-reduced-motion` is wired in the motion layer.

The AA blockers are contrast on “What to expect” numerals, prohibited `aria-label` on testimonial star rows, and a Label-in-Name mismatch on the homepage next-class board.

### Lighthouse accessibility (mobile)

| Page | Score | Failed audits |
|---|---:|---|
| `/` | 0.97 | `aria-prohibited-attr`, `label-content-name-mismatch` |
| `/classes/` | 1.00 | — |
| `/classes/adult-beginners-outdoor/` | 0.96 | `color-contrast` (5 nodes) |
| `/contact/` | 1.00 | — |
| `/tutorials/deadhang/` | 0.99 | `heading-order` |
| `/classes-map/` | 1.00 | — |

JSON (headers stripped): `../a11y/*.lighthouse.json`.

---

## What already meets the bar

- Skip link “Skip to content” → `#main` on every sampled page.
- Exactly one `<main>`; site nav and footer are siblings outside it; page `<h1>` is inside it.
- Images sampled had `alt`. Overlay “hit area” links on timetable rows are `aria-hidden` + `tabindex="-1"` with a named “MORE DETAILS” control.
- Contact fields NAME / EMAIL / SUBJECT / MESSAGE are programmatically labelled. Booking drawer: Your name (`autocomplete="name"`), Email (`autocomplete="email"`), How many people?, calendar days with names like “Saturday 12 September, available”.
- Escape closes the booking drawer and returns focus to the triggering “BOOK YOUR FIRST CLASS — £15” button.
- Tutorial Deadhang: Play control named “Play: Deadhang”; English SRT captions and a transcript JSON are in the page (1.2.2 path exists, not only YouTube).
- Classes map: three named site links sit beside the Leaflet canvas, so the map is not the only way to the locations.
- Motion layer respects `prefers-reduced-motion` (`assets/js/motion/reducedMotion.js`); decode effect paints the final string immediately when reduced.

---

## Findings

### Critical

None that block keyboard use or hide the booking form. Contrast below is Serious, not a keyboard trap.

### Serious

#### 1. “What to expect” numerals fail contrast (1.4.3)

- **URL:** `/classes/adult-beginners-outdoor/`
- **WCAG:** 1.4.3 Contrast (Minimum) AA
- **Evidence:** Lighthouse `color-contrast` on five `<span class="shrink-0 w-[44px] font-heading text-[24px] …">` nodes labelled `01`–`05`. Foreground `#979691` on `#efede7` = **2.53:1**. Large text needs **3:1**.
- **Source:** `parts/components/checklist-item.php` — `$lp_numeral_class` uses `text-base-content/40`. Same string in Storybook `src/stories/Components/ChecklistItem/ChecklistItem.js`.
- **Contract:** `docs/phase7/surface-axis.md` — muted on `page` is `text-base-content/65` (≈5.8:1). `/50` already fails (3.38:1). `/40` is below the documented floor.
- **Fix:** Use `text-base-content/65` (page muted). Do not invent a hex. Port the same literal class in Storybook so the next theme port does not restore `/40`.

#### 2. Star rating `aria-label` is dropped (4.1.2)

- **URL:** `/` (testimonials)
- **WCAG:** 4.1.2 Name, Role, Value A
- **Evidence:** Lighthouse `aria-prohibited-attr`, three nodes: `<span class="flex items-center gap-0.5" aria-label="5 out of 5 stars">`. Axe: `aria-label` cannot be used on a `span` with no role. Browsers ignore the name.
- **Source:** `blocks/testimonials/testimonials.php` (visible rows and the `<template>`). Same pattern in Storybook `src/stories/Blocks/Testimonials/Testimonials.js`. Tests currently assert the invalid markup (`Testimonials.test.js`).
- **Fix:** `role="img"` on the span, or visually hidden text and `aria-hidden="true"` on the decorative SVGs. Keep the visible stars.

#### 3. Homepage next-class board fails Label in Name (2.5.3)

- **URL:** `/`
- **WCAG:** 2.5.3 Label in Name AA
- **Evidence:** Lighthouse `label-content-name-mismatch` on `<button data-slot="next-class-board" aria-label="Reserve a place — Adult Beginners East — 10:30 — SAT - 12th">`. Visible text includes “NEXT CLASS”, location, “20 LEFT”, duration, etc. Those strings are not in the accessible name. Voice-control users who say the visible words will miss.
- **Source:** `blocks/hero/hero.php` (`$lp_next_aria` from `foot_label` + name + time + when).
- **Fix:** Include the visible board title and location in the accessible name, or drop `aria-label` and let the button’s visible text be the name (then trim what AT hears with visually hidden extras if needed).

### Moderate

#### 4. Decode headline is spelled letter-by-letter (1.3.2)

- **URL:** `/`
- **WCAG:** 1.3.2 Meaningful Sequence A (and 4.1.2 while scrambling)
- **Evidence:** After the animation, the accessibility tree name for the `<h1>` is `t h e w o r l d i s y o u r p l a y g r o u n d .` — one character per `data-decode-char` span. During the ~1.6s scramble the name is random glyphs. `textContent` is the full sentence; the tree is not. No `aria-label` on the `h1`.
- **Source:** `blocks/hero/hero.php` `data-motion-decode` + `assets/js/motion/effects/decode.js` `buildDecodeNodes()`.
- **Fix:** Set `aria-label` (or a visually hidden copy) to the final sentence and `aria-hidden="true"` on the glyph spans. Reduced-motion already skips scramble; it does not merge the spans.

#### 5. Tutorial board heading skips a level (1.3.1 / best practice)

- **URL:** `/tutorials/deadhang/`
- **WCAG:** 1.3.1 Info and Relationships (Lighthouse `heading-order`; not a hard AA criterion by itself)
- **Evidence:** `h1` “Deadhang”, then `h3` “LACHE — LESSON BOARD”, then `h2` “Two demonstrations.”
- **Source:** `parts/components/board-shell.php` always emits `<h3>` for `board_title`. Caller: `single-lp_tutorial.php`.
- **Fix:** Promote to `h2` when it is the next outline step, or insert a real `h2` for “The series / category” instead of a meta-row. Do not use `role="heading"`.

#### 6. Contact name/email missing `autocomplete` (1.3.5)

- **URL:** `/contact/`
- **WCAG:** 1.3.5 Identify Input Purpose AA
- **Evidence:** Name and email inputs have associated labels but empty `autocomplete`. The booking drawer already sets `name` / `email`.
- **Source:** contact field partial (rendered as `field-1` / `field-2`).
- **Fix:** `autocomplete="name"` and `autocomplete="email"` on those inputs.

#### 7. Contact honeypot is still in the accessibility tree

- **URL:** `/contact/`
- **WCAG:** 4.1.2 (extra unnamed-or-odd field)
- **Evidence:** Snapshot exposes a textbox named “Company”. DOM: `#lp-company`, `class="sr-only"`, `tabindex="-1"`, labelled “Company”, **not** `aria-hidden`. Dispatch honeypot wraps the field in `sr-only` **and** `aria-hidden="true"` (`blocks/dispatch/dispatch.php`).
- **Fix:** Match dispatch: hide the wrapper from AT (`aria-hidden="true"`).

### Low

- `html lang="en-US"` on a UK site. 3.1.1 passes because a language is set; `en-GB` would be more accurate.
- Leaflet `.leaflet-container` has no `role` / `aria-label`. Zoom controls are named. Three site links duplicate the pins. Not a Lighthouse fail.
- Browser snapshot lists a second `navigation` named “Primary” (mobile `<dialog id="site-nav-mobile-menu">` while `open=false`). Confirm with VoiceOver that the closed dialog is ignored; native `<dialog>` should be.
- Opening the booking drawer focused the backdrop “Close panel” control first, not “CLOSE” or the first field. Escape and labelled fields still work.

---

## Keyboard / landmarks

| Check | Result |
|---|---|
| Skip link present | Yes, first in the tree |
| One `main` | Yes |
| Booking dialog name | `aria-label="Booking panel"` |
| Escape from booking | Closes; focus returns to the opener |
| Keyboard trap | None found on the sampled flow |
| Focus visible | `focus-visible:outline` on the next-class board; not a full visual pass of every control |
| Target size 24×24 | Not measured per control; BOOK / Find a class look large enough |

Skip-link click from automation hit the sticky nav because the link is `sr-only` until focused. That is not a user-Tab failure by itself; a Safari Tab pass is still required.

---

## Pages not sampled

`/docs/`, `/about/`, `/workshops/`, kids class, coupon checkout, Stripe payment step, mobile nav drawer Tab order, 200% zoom, Windows High Contrast, VoiceOver/NVDA.

---

## Highest-priority fixes

1. `text-base-content/65` on checklist numerals (Storybook + theme).
2. Valid name for testimonial stars (`role="img"` or hidden text).
3. Next-class board accessible name includes visible title/location.
4. Decode `h1`: `aria-label` with the final sentence.

No changes were made.
