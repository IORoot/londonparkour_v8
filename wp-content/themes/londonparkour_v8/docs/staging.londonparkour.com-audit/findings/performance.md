# Performance (Core Web Vitals)

**INP, not FID.** Lab Lighthouse does not emit INP; TBT is the lab proxy. Do not cite `maxPotentialFID`.

**PSI cannot reach HTTP-auth staging.** CrUX/PSI API key is **missing** (`claude-seo google_auth` not configured). CrUX 403 without identity; PSI anonymous quota 0. **No LIVE field LCP/INP/CLS in this audit.**

Live CrUX, if collected later, is **V7** until DNS cutover.

---

## Observed — staging V8 lab (Lighthouse 13.4.1, 2026-09-11)

Method: `npx lighthouse` against `https://staging.londonparkour.com/` with HTTP basic auth, `--form-factor=mobile`, simulated Slow 4G, headless Chrome. Auth header was used for the run and is **not** stored in this file.

| | Homepage `/` | `/classes/` | Threshold |
|---|---|---|---|
| Lighthouse Performance | **0.62** | **0.67** | 0.90 good |
| **LCP** | **7.6 s** Poor | **7.7 s** Poor | ≤ 2.5 s |
| **INP** | not emitted | not emitted | ≤ 200 ms |
| TBT (INP proxy) | 170 ms Good | 90 ms Good | ≤ 200 ms TBT |
| **CLS** | **0.001** Good | **0.041** Good | ≤ 0.1 |
| FCP | 3.6 s Poor | 3.5 s Poor | ≤ 1.8 s |
| Speed Index | 5.9 s | 4.0 s | — |
| TTI | 7.6 s | 7.7 s | — |
| Document TTFB (LH `server-response-time`) | 130 ms | 80 ms | ≤ 800 ms |
| Transfer | 1.18 MB / 47 req | 876 KB / 42 req | — |
| Fetch time | 2026-09-11T08:20:37Z | 2026-09-11T08:20:56Z | — |

CLS is fine. LCP/FCP are not. TBT suggests **INP is unlikely to be the primary lab problem**; the page is slow to paint, not mainly main-thread blocked.

Curl TTFB (unthrottled, same UA, 2026-09-11): staging `/` **210 ms** / 262 KB HTML; `/classes/` **208 ms** / 155 KB HTML. Cloudflare edge is fast. The LCP failure is **render path** (CSS/fonts/JS), not origin wait.

Homepage unused JS (LH): ~223 KiB — theme `app-XQEc0UBB.js` ~92 KB wasted, `gtm.js` ~71 KB, `gtag.js` ~65 KB.

---

## Observed — HTML weight (staging vs live)

| URL | HTML bytes | `<img>` | Notes |
|---|---:|---:|---|
| STAGING `/` | 261,595 | 44 | |
| STAGING `/classes/` | 154,926 | 5 | |
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

`wp-content/themes/londonparkour_v8/docs/web-perf-homepage.md` (2026-09-08, **localhost:8102**, Lighthouse 13.4.1 mobile Slow 4G): LCP **6.9 s** Poor, CLS 0.002, TBT 50 ms, FCP 3.8 s, Performance 64. Same shape as staging today (LCP ~7 s, CLS good, TBT good). That run is Docker PHP, not Cloudflare; TTFB there was 442 ms vs staging 130 ms.

That doc’s diagnosis (render-blocking CSS including ClasbPro + Google Fonts `@import` chain, Ken Burns vs LCP, Leaflet in the global bundle) was **not re-traced** in this Lighthouse pass (LCP element audit returned empty items). Treat it as a prior local finding, not a new staging measurement.

---

## Live field data (V7)

**Missing.** No CrUX origin or URL record this round. When a key exists, query `https://londonparkour.com` as origin and label results **LIVE V7**.

Curl-only live homepage: TTFB ~0.95 s (Needs improvement vs 800 ms TTFB guidance), HTML 327 KB. That is **not** LCP.

---

## Interpretation

1. Staging **passes TTFB and CLS**, **fails LCP** on both the homepage and the commercial timetable. Launching V8 will not magically get field LCP under 2.5 s.
2. `/tutorials-category/` at 1.5 MB / 391 images is a separate performance **and** crawl issue from the homepage LCP.
3. Lab TBT is Good, so do not prioritize INP work until field INP exists. Do prioritize LCP (CSS/font/hero).
4. Live V7 `/tutorials/` at 2.67 MB is worse than staging’s hub — keep the slimmer hub; do not paste the live dump back.

---

## Issues

| Severity | Finding | Evidence |
|---|---|---|
| High | Homepage LCP 7.6 s (lab, mobile) | Lighthouse 2026-09-11 |
| High | `/classes/` LCP 7.7 s | Same run |
| High | `/tutorials-category/` 1.48 MB HTML, 391 imgs | curl 2026-09-11 |
| Medium | No field INP/LCP/CLS | Missing PSI/CrUX key; PSI cannot auth staging |
| Medium | Theme + GTM unused JS ~223 KiB on homepage | LH unused-javascript |
| Info | Live `/tutorials/` 2.67 MB HTML | curl live |

---

## Recommendations

1. Fix LCP on `/` and `/classes/` before treating V8 as launch-ready (font/CSS load, hero, ClasbPro CSS on pages that do not need the calendar).
2. Paginate or lazy-render `/tutorials-category/` — do not ship 391 images in the initial HTML.
3. After go-live (public, no basic auth), run CrUX origin + PSI mobile and compare to this lab floor. Until then, do not claim “Good CWV”.
4. Measure INP in the field only; do not revive FID.
