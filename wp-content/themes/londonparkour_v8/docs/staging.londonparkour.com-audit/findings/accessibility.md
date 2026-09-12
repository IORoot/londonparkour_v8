# Accessibility findings — staging.londonparkour.com

**Target:** https://staging.londonparkour.com/ (HTTP basic auth; credentials not recorded)  
**Date:** 2026-09-11 (theme recode + local recheck 2026-09-12)  
**Standard:** WCAG 2.2 Level AA  
**Method:** Lighthouse 13.4.1 accessibility (mobile), rendered accessibility-tree snapshots, keyboard on the booking drawer. VoiceOver/NVDA was **not** used. A Lighthouse score of 100 is not conformance.

**Host:** staging V8 theme. Not live V7. Source fixes below were verified on local WP (`http://localhost:8102`), not re-run on staging.

---

## Executive summary

**Not yet WCAG 2.2 AA.** The remaining AA blocker is a Label-in-Name mismatch on the homepage next-class board.

The landmark contract is in good shape: skip link, one `<main id="main">`, real headings, named primary/footer nav, booking `<dialog>` labelled “Booking panel”. Forms that take a booking or a contact enquiry are labelled and support autocomplete. Tutorials ship captions and a transcript. `prefers-reduced-motion` is wired in the motion layer. Decode `<h1>`, `html lang`, and the classes-map Leaflet region were closed in source on 2026-09-12.

### Lighthouse accessibility (mobile)

Original 2026-09-11 run (JSON on disk: `../a11y/*.lighthouse.json`). Theme recode has closed the audits marked **fixed in source**; staging has not been re-scored.

| Page | Score (11 Sep) | Failed audits | Status |
|---|---:|---|---|
| `/` | 0.97 | `aria-prohibited-attr`, `label-content-name-mismatch` | Stars **fixed in source**. Decode `<h1>` **fixed in source** (not a Lighthouse fail). Label-in-Name **open**. |
| `/classes/` | 1.00 | — | — |
| `/classes/adult-beginners-outdoor/` | 0.96 | `color-contrast` (5 nodes) | Numerals **fixed in source**. |
| `/contact/` | 1.00 | — | Autocomplete + honeypot **fixed in source** (Lighthouse did not flag them). |
| `/tutorials/deadhang/` | 0.99 | `heading-order` | Lesson board **fixed in source**. |
| `/classes-map/` | 1.00 | — | Leaflet region **fixed in source** (not a Lighthouse fail). |

---

## What already meets the bar

- Skip link “Skip to content” → `#main` on every sampled page.
- Exactly one `<main>`; site nav and footer are siblings outside it; page `<h1>` is inside it.
- Images sampled had `alt`. Overlay “hit area” links on timetable rows are `aria-hidden` + `tabindex="-1"` with a named “MORE DETAILS” control.
- Contact fields NAME (`autocomplete="name"`) / EMAIL (`autocomplete="email"`) / SUBJECT / MESSAGE are programmatically labelled. The Company honeypot is wrapped `sr-only` + `aria-hidden="true"` and is not in the accessibility tree. Booking drawer: Your name (`autocomplete="name"`), Email (`autocomplete="email"`), How many people?, calendar days with names like “Saturday 12 September, available”.
- Escape closes the booking drawer and returns focus to the triggering “BOOK YOUR FIRST CLASS — £15” button.
- Tutorial Deadhang: Play control named “Play: Deadhang”; English SRT captions and a transcript JSON are in the page (1.2.2 path exists, not only YouTube). Lesson board title is `<h2>` after the page `<h1>`.
- Class “What to expect” numerals use page-muted `text-base-content/65` (Storybook + theme).
- Testimonial star rows expose `role="img"` + `aria-label="5 out of 5 stars"`.
- Classes map: three named site links sit beside the Leaflet canvas, so the map is not the only way to the locations. `.leaflet-container` is `role="region"` named “Map of class locations”; zoom in/out stay named; pins are pointer-only so they do not duplicate the list as tab stops. Meeting-point maps use `aria-label="Map of {site}"`.
- `html lang="en-GB"` (and `og:locale` `en_GB` / schema `inLanguage` `en-GB`) on local WP. Theme maps empty/`en_US` locale to `en_GB`.
- Motion layer respects `prefers-reduced-motion` (`assets/js/motion/reducedMotion.js`); decode effect paints the final string immediately when reduced. Decode host `aria-label` is the final sentence; glyph spans are `aria-hidden`. Local `/` tree name for the `<h1>` is `the world is your playground.`

---

## Findings

### Critical

None that block keyboard use or hide the booking form.

### Serious

#### 1. Homepage next-class board fails Label in Name (2.5.3)

- **URL:** `/`
- **WCAG:** 2.5.3 Label in Name AA
- **Evidence:** Lighthouse `label-content-name-mismatch` on `<button data-slot="next-class-board" aria-label="Reserve a place — Adult Beginners East — 10:30 — SAT - 12th">`. Visible text includes “NEXT CLASS”, location, “20 LEFT”, duration, etc. Those strings are not in the accessible name. Voice-control users who say the visible words will miss.
- **Source:** `blocks/hero/hero.php` (`$lp_next_aria` from `foot_label` + name + time + when).
- **Fix:** Include the visible board title and location in the accessible name, or drop `aria-label` and let the button’s visible text be the name (then trim what AT hears with visually hidden extras if needed).

### Moderate

#### 2. Decode headline — **fixed in source** (local 2026-09-12)

- **URL:** `/`
- **WCAG:** 1.3.2 Meaningful Sequence A (and 4.1.2 while scrambling)
- **Was:** After the animation, the accessibility tree name for the `<h1>` was `t h e w o r l d i s y o u r p l a y g r o u n d .` — one character per `data-decode-char` span. During the ~1.6s scramble the name was random glyphs. No `aria-label` on the `h1`.
- **Now:** `buildDecodeNodes()` sets `aria-label` to the final sentence (newlines collapsed) and `aria-hidden="true"` on glyph/line spans. Hero PHP also emits the label before JS. Local snapshot heading name is `the world is your playground.` All 29 decode glyphs hidden from AT. Staging not re-snapshotted.
- **Source:** `blocks/hero/hero.php` + `assets/js/motion/effects/decode.js` `buildDecodeNodes()`.

### Low

- `html lang` — **fixed in source** (local 2026-09-12). Was `en-US`; now `en-GB`. `lp_british_locale` maps empty/`en_US` → `en_GB`. Local `/`: `lang="en-GB"`, `og:locale` `en_GB`, JSON-LD `inLanguage` `en-GB`. Staging still had `en-US` in the 11 Sep capture.
- Leaflet `.leaflet-container` — **fixed in source** (local 2026-09-12). Was unlabelled. Local `/classes-map/` tree has a region named “Map of class locations”. Zoom controls still named. Three site links remain the keyboard path; pins `keyboard: false`.
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

## Highest-priority remaining

1. Next-class board accessible name includes visible title/location.
