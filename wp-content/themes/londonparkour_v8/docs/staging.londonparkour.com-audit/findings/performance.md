# Performance (Core Web Vitals)

**INP, not FID.** Lab Lighthouse does not emit INP; TBT is the lab proxy. Do not cite `maxPotentialFID`.

**PSI cannot reach HTTP-auth staging.** CrUX/PSI API key is **missing** (`claude-seo google_auth` not configured). CrUX 403 without identity; PSI anonymous quota 0. **No LIVE field LCP/INP/CLS in this audit.**

Live CrUX, if collected later, is **V7** until DNS cutover.

---

## Observed — staging V8 lab (Lighthouse 13.4.1, 2026-09-11 13:47 BST)

Method: `npx lighthouse` against `https://staging.londonparkour.com/` with HTTP basic auth, `--form-factor=mobile`, simulated Slow 4G, headless Chrome. Auth header was used for the run and is **not** stored in this file. JSON: `lh-home.lighthouse.json`, `lh-classes.lighthouse.json`.

| | Homepage `/` morning | Homepage `/` now | `/classes/` morning | `/classes/` now | Threshold |
|---|---|---|---|---|---|
| Lighthouse Performance | 0.62 | **0.68** | 0.67 | **0.78** | 0.90 good |
| **LCP** | **7.6 s** Poor | **7.2 s** Poor | **7.7 s** Poor | **5.0 s** Poor | ≤ 2.5 s |
| **INP** | not emitted | not emitted | not emitted | not emitted | ≤ 200 ms |
| TBT (INP proxy) | 170 ms Good | 180 ms Good | 90 ms Good | 50 ms Good | ≤ 200 ms TBT |
| **CLS** | **0.001** Good | **0.002** Good | **0.041** Good | **0.001** Good | ≤ 0.1 |
| FCP | 3.6 s Poor | **1.8 s** | 3.5 s Poor | **2.6 s** | ≤ 1.8 s |
| Speed Index | 5.9 s | 5.6 s | 4.0 s | 3.1 s | — |
| TTI | 7.6 s | 7.2 s | 7.7 s | 6.9 s | — |
| Document TTFB (LH) | 130 ms | 130 ms | 80 ms | **1.3 s** (outlier) | ≤ 800 ms |
| Transfer | 1.18 MB / 47 req | **858 KiB / 38 req** | 876 KB / 42 req | 879 KiB / 36 req | — |
| Fetch time | 2026-09-11T08:20:37Z | 2026-09-11T12:47:42Z | 2026-09-11T08:20:56Z | 2026-09-11T12:47:56Z | — |

CLS is fine. TBT is fine. **Homepage LCP is still Poor.** FCP on `/` is now at the Good threshold (1.8 s). `/classes/` LCP 5.0 s includes a **1.3 s lab TTFB** this run (curl TTFB earlier today was ~90–200 ms). Do not treat 5.0 s as a new floor without another run; even subtracting that TTFB spike it is still above 2.5 s.

**Fail check for “LCP under 4 s” still fails on `/`.**

Curl TTFB (unthrottled, this afternoon): staging `/` **~1.2 s** this fetch / 262 KB HTML. Cloudflare `cf-cache-status: DYNAMIC`. Origin wait is not the homepage LCP story — Lighthouse’s simulated LCP is still **element render delay** on overlay text.

Homepage unused JS (LH now): ~186 KiB — `gtm.js` ~71 KB, `gtag.js` ~65 KB, theme `app-DYsXYAld.js` ~50 KB. Leaflet is gone from the homepage network.

---

## Shipped on staging (2026-09-11)

Verified on `https://staging.londonparkour.com/` this afternoon (`app-DYsXYAld.js`).

| Change | Evidence |
|---|---|
| Self-hosted Inter/Archivo woff2 + `faces.css` before `main.css`; latin files preloaded | No `fonts.googleapis.com`. Network: `inter-latin.woff2`, `archivo-latin.woff2`. |
| Leaflet CSS/JS off `/` and `/classes/` | Not in first HTML. Still loads on `/classes-map/` (correct). |
| ClasbPro calendar CSS deferred until booking drawer | Not in first-paint stylesheet ids. URLs remain in `lpBooking.calendarStyles`. |
| Ken Burns: only slide 0 in the document | Live `<img>` = `alfredo-strides.jpg`. Slides 1–3 in `<template>`. LH network this run fetched **only** that one hero JPEG. Image-delivery estimate **271 KiB → 56 KiB**. |

LCP element is still **text**, not the photo:

- `/` — overlay `p.font-body` (“Practical movement is the practice…”). Breakdown: TTFB 152 ms + element render delay 2.1 s (observed); simulated LCP 7.2 s.
- `/classes/` — H1 “This week's sessions.” (`font-display` / Scope Trial).

Render-blocking estimate on `/`: **~2.1 s this morning → 250 ms now**. Remaining: `main.css` (48 KB), `faces.css` (1 KB extra request), ClasbPro `booking` / `packs` / theme-pack CSS.

---

## Observed — HTML weight (staging vs live)

| URL | HTML bytes | `<img>` | Notes |
|---|---:|---:|---|
| STAGING `/` | 261,741 | 44 | 3 of the hero slides are inside `<template>` (not fetched) |
| STAGING `/classes/` | 154,926 | 5 | Morning crawl; not re-weighed this afternoon |
| STAGING `/tutorials/` | 323,175 | 48 | Hub |
| STAGING `/tutorials-category/` | **1,476,594** | **391** | Category board dumps the library |
| STAGING class singular | 205,682 | 2 | |
| STAGING tutorial singular | 212,578 | 3 | |
| LIVE `/` | 326,560 | — | TTFB **0.95 s** |
| LIVE `/classes/` | 334,426 | 51 | TTFB **0.89 s** |
| LIVE `/tutorials/` | 2,674,342 | — | Full library dump on live hub |
| LIVE `/tutorial/deadhang/` | 263,262 | 26 | |

Staging already improved the tutorial **hub** vs live (323 KB vs 2.67 MB). The **category overview** (`/tutorials-category/`) reintroduces a ~1.5 MB HTML bomb (391 images). That will dominate crawl budget and mobile LCP if it ships.

---

## Observed — local lab (not staging, corroboration only)

`wp-content/themes/londonparkour_v8/docs/web-perf-homepage.md` (2026-09-08, **localhost:8102**, Lighthouse 13.4.1 mobile Slow 4G): LCP **6.9 s** Poor, CLS 0.002, TBT 50 ms, FCP 3.8 s, Performance 64. That run is Docker PHP, not Cloudflare.

That diagnosis (Google Fonts `@import`, Leaflet in the global bundle, random first Ken Burns slide, calendar CSS on first paint) has been **implemented** on staging. Remaining lab LCP is overlay/H1 text waiting on `main.css` + ClasbPro CSS/JS + Scope Trial TTF.

---

## Live field data (V7)

**Missing.** No CrUX origin or URL record this round. When a key exists, query `https://londonparkour.com` as origin and label results **LIVE V7**.

Curl-only live homepage: TTFB ~0.95 s (Needs improvement vs 800 ms TTFB guidance), HTML 327 KB. That is **not** LCP.

---

## Interpretation

1. Staging **passes TTFB (typical), CLS, and now homepage FCP**. It still **fails LCP** on `/` (7.2 s). `/classes/` improved to 5.0 s in this run with a bad TTFB sample.
2. Ken Burns no longer downloads three extra 1920px JPEGs on first paint. That was the last hero-bandwidth bug; it did not make the photo the LCP element (50% scrim + overlay copy).
3. `/tutorials-category/` at 1.5 MB / 391 images is a separate performance **and** crawl issue from the homepage LCP.
4. Lab TBT is Good. Do not start an INP project. Next LCP work is ClasbPro CSS/JS on drawer-open, inlining `faces.css`, and Scope Trial as woff2 / delayed H1 decode.

---

## Issues

| Severity | Finding | Evidence |
|---|---|---|
| High | Homepage LCP 7.2 s (lab, mobile) — overlay text | Lighthouse 2026-09-11 13:47 BST |
| High | `/classes/` LCP 5.0 s this run (was 7.7 s); still Poor | Same run; TTFB 1.3 s outlier |
| High | `/tutorials-category/` 1.48 MB HTML, 391 imgs | curl 2026-09-11 |
| Medium | ClasbPro booking/packs/theme CSS + calendar JS still first paint | LH render-blocking on `/classes/` ~990 ms |
| Medium | `faces.css` is a 1 KB extra blocking request; Scope Trial is a 17 KB TTF from `main.css` | LH + network |
| Medium | No field INP/LCP/CLS | Missing PSI/CrUX key; PSI cannot auth staging |
| Medium | Theme + GTM unused JS ~186 KiB on homepage | LH unused-javascript (Leaflet gone) |
| Info | Live `/tutorials/` 2.67 MB HTML | curl live |

---

## Recommendations

1. Next LCP slice: load ClasbPro CSS/JS when the drawer opens (same pattern as calendar CSS). Inline `faces.css`. Convert/preload Scope Trial woff2; do not run H1 decode before first paint.
2. Paginate or lazy-render `/tutorials-category/` — do not ship 391 images in the initial HTML.
3. After go-live (public, no basic auth), run CrUX origin + PSI mobile and compare to this lab floor. Until then, do not claim “Good CWV”.
4. Measure INP in the field only; do not revive FID.
