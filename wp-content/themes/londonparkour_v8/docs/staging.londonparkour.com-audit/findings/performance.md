# Performance (Core Web Vitals)

**INP, not FID.** Lab Lighthouse does not emit INP; TBT is the lab proxy. Do not cite `maxPotentialFID`.

**PSI cannot reach HTTP-auth staging.** CrUX/PSI API key is **missing** (`claude-seo google_auth` not configured). CrUX 403 without identity; PSI anonymous quota 0. **No LIVE field LCP/INP/CLS in this audit.**

Live CrUX, if collected later, is **V7** until DNS cutover.

---

## Observed — staging V8 lab (Lighthouse 13.4.1, 2026-09-11 14:10 BST)

Method: `npx lighthouse` against `https://staging.londonparkour.com/` with HTTP basic auth, `--form-factor=mobile`, simulated Slow 4G, headless Chrome. Auth header was used for the run and is **not** stored in this file. JSON: `lh-home.lighthouse.json`, `lh-classes.lighthouse.json`.

Homepage was run twice this pass. The first sample (13:09 UTC) was **FCP 5.5 s / LCP 8.5 s / Perf 0.57** with the same TTFB (~150 ms) and the same LCP node — discarded as lab noise. Numbers below are the rerun (13:10 UTC).

| | Homepage `/` 13:47 BST | Homepage `/` now | `/classes/` 13:47 BST | `/classes/` now | Threshold |
|---|---|---|---|---|---|
| Lighthouse Performance | 0.68 | **0.65** | 0.78 | **0.81** | 0.90 good |
| **LCP** | **7.2 s** Poor | **7.7 s** Poor | **5.0 s** Poor | **4.4 s** Poor | ≤ 2.5 s |
| **INP** | not emitted | not emitted | not emitted | not emitted | ≤ 200 ms |
| TBT (INP proxy) | 180 ms Good | 180 ms Good | 50 ms Good | **40 ms** Good | ≤ 200 ms TBT |
| **CLS** | **0.002** Good | **0.002** Good | **0.001** Good | **0.001** Good | ≤ 0.1 |
| FCP | **1.8 s** | **2.6 s** | 2.6 s | **2.5 s** | ≤ 1.8 s |
| Speed Index | 5.6 s | 5.6 s | 3.1 s | **2.9 s** | — |
| TTI | 7.2 s | 7.7 s | 6.9 s | **6.5 s** | — |
| Document TTFB (LH) | 130 ms | 240 ms | **1.3 s** (outlier) | **1.2 s** (outlier) | ≤ 800 ms |
| Transfer | 858 KiB / 38 req | **800 KiB / 34 req** | 879 KiB / 36 req | **819 KiB / 32 req** | — |
| Fetch time | 2026-09-11T12:47:42Z | 2026-09-11T13:10:05Z | 2026-09-11T12:47:56Z | 2026-09-11T13:09:14Z | — |

CLS is fine. TBT is fine. **Homepage LCP is still Poor** and did not improve outside lab noise (7.2 s → 7.7 s). `/classes/` LCP **5.0 s → 4.4 s**; this run’s TTFB is again ~1.2 s (curl is typically ~90–200 ms). Subtracting that spike still leaves LCP above 2.5 s.

**Fail check for “LCP under 4 s” still fails on `/`.** `/classes/` is 4.4 s this run and is not a clean pass.

LCP element is still **text**, not the photo:

- `/` — overlay `p.font-body` (“Practical movement is the practice…”), now Helvetica/Arial. Breakdown: TTFB 256 ms + element render delay 2.1 s (observed); simulated LCP 7.7 s.
- `/classes/` — H1 “This week's sessions.” (`font-display` / Scope Trial TTF).

Render-blocking estimate on `/`: **500 ms** this run (`faces.css` + `main.css` only). ClasbPro stylesheets are gone from first HTML.

Homepage unused JS: ~182 KiB — `gtm.js` ~71 KB, `gtag.js` ~65 KB, theme `app-BACpot98.js` ~50 KB. Leaflet and Inter are gone. ClasbPro JS still downloads in the footer (~21 KB across five files).

---

## Shipped on staging (2026-09-11)

Verified on `https://staging.londonparkour.com/` this pass (`app-BACpot98.js`, `main-FGL1F6ZX.css`). First HTML stylesheets: `londonparkour-fonts`, `londonparkour` only.

| Change | Evidence |
|---|---|
| Body/label = Helvetica Neue / Helvetica / Arial | Overlay computed `font-family` is that stack. No `inter-latin.woff2` preload or network. |
| Archivo still self-hosted + preloaded | `archivo-latin.woff2` (~35 KB). `faces.css` still a 1 KB extra blocking request. |
| Leaflet CSS/JS off `/` and `/classes/` | Not in first HTML. Still loads on `/classes-map/` (correct). |
| ClasbPro CSS (core, packs, calendar, theme-pack) deferred until booking drawer | Not in first-paint stylesheet ids. Six URLs in `lpBooking.calendarStyles`. |
| Ken Burns: only slide 0 in the document | Three `<template>`s. Inter never fetched. |
| Scope Trial still a 17 KB TTF from `main.css` | Network: `ScopeTrial-Variable-Cdy3xehk.ttf`. LCP on `/classes/`. |

---

## Observed — HTML weight (staging vs live)

| URL | HTML bytes | `<img>` | Notes |
|---|---:|---:|---|
| STAGING `/` | 261,325 | 44 | 3 of the hero slides are inside `<template>` (not fetched) |
| STAGING `/classes/` | 154,926 | 5 | Morning crawl; not re-weighed this pass |
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

---

## Live field data (V7)

**Missing.** No CrUX origin or URL record this round. When a key exists, query `https://londonparkour.com` as origin and label results **LIVE V7**.

Curl-only live homepage: TTFB ~0.95 s (Needs improvement vs 800 ms TTFB guidance), HTML 327 KB. That is **not** LCP.

---

## Interpretation

1. Staging **passes TTFB (typical) and CLS**. It still **fails LCP** on `/` (7.7 s this run). Helvetica/Arial + ClasbPro CSS off first paint **did not move homepage LCP** outside lab noise. The overlay still waits on `main.css` (48 KB) + `faces.css`.
2. `/classes/` LCP is the Scope Trial H1. CSS deferral helped a little (5.0 s → 4.4 s) but this run is still TTFB-heavy (~1.2 s). The remaining render path is `main.css` + Scope Trial TTF.
3. ClasbPro **JS** still loads on first visit (footer). LH still lists those scripts in render-blocking savings on `/classes/` (~940 ms estimate, mixed with CSS). Do not drop GTM/`gtag`.
4. `/tutorials-category/` at 1.5 MB / 391 images is a separate performance **and** crawl issue from the homepage LCP.
5. Lab TBT is Good. Do not start an INP project.

---

## Issues

| Severity | Finding | Evidence |
|---|---|---|
| High | Homepage LCP 7.7 s (lab, mobile) — overlay text | Lighthouse 2026-09-11 14:10 BST (rerun; first sample 8.5 s discarded) |
| High | `/classes/` LCP 4.4 s this run (was 5.0 s); still Poor | Same pass; TTFB 1.2 s outlier |
| High | `/tutorials-category/` 1.48 MB HTML, 391 imgs | curl 2026-09-11 |
| Medium | `faces.css` is a 1 KB extra blocking request; Scope Trial is a 17 KB TTF from `main.css` | LH render-blocking `/` 500 ms |
| Medium | ClasbPro JS still first-load (footer, ~21 KB) | Network + `/classes/` render-blocking insight |
| Medium | No field INP/LCP/CLS | Missing PSI/CrUX key; PSI cannot auth staging |
| Medium | Theme + GTM unused JS ~182 KiB on homepage | LH unused-javascript |
| Info | Live `/tutorials/` 2.67 MB HTML | curl live |

---

## Recommendations

1. Next LCP slice: inline `faces.css`. Convert/preload Scope Trial woff2; do not run H1 decode before first paint. Optionally load ClasbPro JS on drawer-open (CSS already waits).
2. Paginate or lazy-render `/tutorials-category/` — do not ship 391 images in the initial HTML.
3. After go-live (public, no basic auth), run CrUX origin + PSI mobile and compare to this lab floor. Until then, do not claim “Good CWV”.
4. Measure INP in the field only; do not revive FID.
