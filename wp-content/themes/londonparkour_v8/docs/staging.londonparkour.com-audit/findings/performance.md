# Performance (Core Web Vitals)

**INP, not FID.** Lab Lighthouse does not emit INP; TBT is the lab proxy. Do not cite `maxPotentialFID`.

**PSI cannot reach HTTP-auth staging.** CrUX/PSI API key is **missing** (`claude-seo google_auth` not configured). CrUX 403 without identity; PSI anonymous quota 0. **No LIVE field LCP/INP/CLS in this audit.**

Live CrUX, if collected later, is **V7** until DNS cutover.

---

## Observed — staging V8 lab (Lighthouse 13.4.1, 2026-09-11 14:33 BST)

Method: `npx lighthouse` against `https://staging.londonparkour.com/` with HTTP basic auth, `--form-factor=mobile`, simulated Slow 4G, headless Chrome. Auth header was used for the run and is **not** stored in this file. JSON: `lh-home.lighthouse.json`, `lh-classes.lighthouse.json`.

Both URLs were run twice this pass. Homepage first sample (13:30 UTC) was FCP 1.7 s / LCP 6.9 s / Perf 0.63 / TBT 390 ms — kept as a note, not discarded. `/classes/` first sample was **FCP 2.8 s / LCP 8.8 s / Perf 0.69** with the same TTFB (~130 ms) and the same LCP node — discarded as lab noise. Numbers below are the rerun (13:33 UTC).

| | Homepage `/` 14:10 BST | Homepage `/` now | `/classes/` 14:10 BST | `/classes/` now | Threshold |
|---|---|---|---|---|---|
| Lighthouse Performance | 0.65 | **0.66** | 0.81 | **0.73** | 0.90 good |
| **LCP** | **7.7 s** Poor | **7.8 s** Poor | **4.4 s** Poor | **6.6 s** Poor | ≤ 2.5 s |
| **INP** | not emitted | not emitted | not emitted | not emitted | ≤ 200 ms |
| TBT (INP proxy) | 180 ms Good | **220 ms** Good | 40 ms Good | **90 ms** Good | ≤ 200 ms TBT |
| **CLS** | **0.002** Good | **0.003** Good | **0.001** Good | **0** Good | ≤ 0.1 |
| FCP | **2.6 s** | **2.3 s** | 2.5 s | **1.6 s** | ≤ 1.8 s |
| Speed Index | 5.6 s | **5.3 s** | 2.9 s | **4.6 s** | — |
| TTI | 7.7 s | **7.8 s** | 6.5 s | **6.6 s** | — |
| Document TTFB (LH) | 240 ms | **100 ms** | **1.2 s** (outlier) | **120 ms** | ≤ 800 ms |
| Transfer | 800 KiB / 34 req | **813 KiB / 33 req** | 819 KiB / 32 req | **830 KiB / 31 req** | — |
| Fetch time | 2026-09-11T13:10:05Z | 2026-09-11T13:33:08Z | 2026-09-11T13:09:14Z | 2026-09-11T13:33:23Z | — |

CLS is fine. TBT is fine. **Homepage LCP did not move** outside lab noise (7.7 s → 7.8 s; other sample 6.9 s). `/classes/` LCP **4.4 s → 6.6 s** with a clean TTFB this time (the 14:10 4.4 s run had a 1.2 s TTFB outlier). Observed (unthrottled) FCP and LCP are the **same timestamp** on both URLs (~2.3 s). The simulated LCP gap is Slow-4G modelling, mainly the Scope Trial TTF.

**Fail check for “LCP under 4 s” still fails on `/` and `/classes/`.**

LCP element is still **text**, not the photo:

- `/` — overlay `p.font-body` (“Practical movement is the practice…”), Helvetica/Arial. Breakdown: TTFB 127 ms + element render delay 2.2 s (observed); simulated LCP 7.8 s.
- `/classes/` — H1 “This week's sessions.” (`font-display` / Scope Trial TTF). Breakdown: TTFB 137 ms + element render delay 2.2 s (observed); simulated LCP 6.6 s.

Render-blocking CSS on `/` is **gone** (insight score 1, no savings). `faces.css` is no longer a request. `main.css` is `media="print"` + `onload`. `/classes/` render-blocking leftover is ClasbPro **JS** (~40 ms estimate this run).

Homepage unused JS: ~181 KiB — `gtm.js` ~69 KB, `gtag.js` ~64 KB, theme `app-BACpot98.js` ~49 KB. Leaflet and Inter are gone. ClasbPro JS still downloads in the footer (~21 KB across five files).

---

## Shipped on staging (2026-09-11 14:33 BST)

Verified on `https://staging.londonparkour.com/` this pass (`app-BACpot98.js`, `main-FGL1F6ZX.css`). First HTML: inlined `#londonparkour-critical` (Archivo faces + fold CSS); `londonparkour` stylesheet is async. No `faces.css` link.

| Change | Evidence |
|---|---|
| Body/label = Helvetica Neue / Helvetica / Arial | Overlay computed `font-family` is that stack. No `inter-latin.woff2`. |
| Archivo faces inlined + latin woff2 preloaded | `archivo-latin.woff2` (~35 KB). No extra `faces.css` round trip. |
| Fold CSS inlined; `main.css` async | Home render-blocking insight empty. HTML `/` **330 KB** (was 261 KB). |
| Leaflet CSS/JS off `/` and `/classes/` | Not in first HTML. Still loads on `/classes-map/` (correct). |
| ClasbPro CSS deferred until booking drawer | Not in first-paint stylesheet ids. Six URLs in `lpBooking.calendarStyles`. |
| Ken Burns: only slide 0 in the document | Three `<template>`s. |
| Scope Trial still a 17 KB TTF | Now discovered from **inlined** critical CSS (`ScopeTrial-Variable-Cdy3xehk.ttf`). Longest document-chain child on both URLs. LCP on `/classes/`. |

---

## Observed — HTML weight (staging vs live)

| URL | HTML bytes | `<img>` | Notes |
|---|---:|---:|---|
| STAGING `/` | **329,984** | 44 | +69 KB vs 14:10 (inlined critical CSS). 3 hero slides in `<template>` |
| STAGING `/classes/` | **223,403** | 5 | +~68 KB vs morning crawl |
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

1. Staging **passes TTFB (typical) and CLS**. It still **fails LCP** on `/` (7.8 s this run). Inlining faces + fold CSS **removed render-blocking stylesheets**. It did **not** pull simulated homepage LCP under 4 s. Unthrottled, overlay FCP and LCP are the same paint (~2.3 s).
2. `/classes/` LCP is the Scope Trial H1. Critical CSS made the TTF a first-party of the HTML (longest chain ~0.5–0.6 s unthrottled). Simulated LCP **got worse** (4.4 s → 6.6 s) once TTFB is honest. FCP on that URL **did** improve (2.5 s → 1.6 s).
3. ClasbPro **JS** still loads on first visit (footer). LH still lists those scripts in render-blocking savings on `/classes/` (~40 ms this run). Do not drop GTM/`gtag`.
4. `/tutorials-category/` at 1.5 MB / 391 images is a separate performance **and** crawl issue from the homepage LCP.
5. Lab TBT is Good. Do not start an INP project.

---

## Issues

| Severity | Finding | Evidence |
|---|---|---|
| High | Homepage LCP 7.8 s (lab, mobile) — overlay text | Lighthouse 2026-09-11 14:33 BST (other sample 6.9 s) |
| High | `/classes/` LCP 6.6 s this run (was 4.4 s with 1.2 s TTFB); still Poor | Same pass; first sample 8.8 s discarded |
| High | `/tutorials-category/` 1.48 MB HTML, 391 imgs | curl 2026-09-11 |
| Medium | Scope Trial is a 17 KB TTF discovered from inlined critical CSS | Network + longest document chain |
| Medium | ClasbPro JS still first-load (footer, ~21 KB) | Network + `/classes/` render-blocking insight |
| Medium | No field INP/LCP/CLS | Missing PSI/CrUX key; PSI cannot auth staging |
| Medium | Theme + GTM unused JS ~181 KiB on homepage | LH unused-javascript |
| Info | Live `/tutorials/` 2.67 MB HTML | curl live |
| Info | Render-blocking **CSS** on `/` is gone | LH render-blocking-insight score 1 |

---

## Recommendations

1. Next LCP slice: Scope Trial **woff2** + preload (or keep the TTF out of homepage critical CSS). Optionally load ClasbPro JS on drawer-open (CSS already waits).
2. Paginate or lazy-render `/tutorials-category/` — do not ship 391 images in the initial HTML.
3. After go-live (public, no basic auth), run CrUX origin + PSI mobile and compare to this lab floor. Until then, do not claim “Good CWV”.
4. Measure INP in the field only; do not revive FID.
