# Web performance audit — homepage

**Target:** http://localhost:8102/
**Date:** 2026-09-08
**Method:** Cloudflare `web-perf` skill. Lighthouse 13.4.1 (mobile, simulated Slow 4G) plus Chrome DevTools Protocol resource timing. Chrome DevTools MCP traces (`performance_start_trace`) were not available.

**Not measured:** field CrUX, real INP (lab uses TBT as the proxy). The Cursor browser session was logged-in (admin bar); Lighthouse ran without cookies and is the public-page result.

Thresholds from [web.dev Core Web Vitals](https://web.dev/articles/vitals) and [TTFB](https://web.dev/articles/ttfb): LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1, TTFB ≤ 0.8s. Lighthouse Performance weights (v10+): LCP 25%, TBT 30%, CLS 25%, FCP 10%, Speed Index 10% — see [Lighthouse performance scoring](https://developer.chrome.com/docs/lighthouse/performance/performance-scoring).

Related: [Cloudways caching](cloudways-caching.md) (production Varnish/Redis; this audit is local, uncached).

---

## Core Web Vitals summary

Lab floors on localhost. TTFB here is PHP on Docker, not production Varnish.

| Metric | Value | Rating |
|---|---|---|
| **LCP** | **6.9s** | Poor (> 4s) |
| **INP** | not measured (TBT **50ms**) | TBT is Good (< 200ms); treat INP as likely fine |
| **CLS** | **0.002** | Good (< 0.1) |
| TTFB | 442ms | Good (< 800ms) |
| FCP | 3.8s | Poor (> 3s) |
| TBT | 50ms | Good |
| Speed Index | 6.1s | Poor (> 5.8s) |
| TTI | 7.0s | — |
| Lighthouse Performance | **64** | Needs improvement (50–89) |
| Total transfer | 1,150 KiB / 30 requests | Fine |

LCP element is **not** the hero photo. It is the overlay copy `p.font-body` (“Practical movement is the practice…”). Breakdown: **TTFB 444ms + element render delay 2410ms**. No LCP resource-load subpart — the text waited on CSS/fonts, and the Ken Burns stack never became LCP.

Unthrottled desktop CDP (logged-in): FCP ~1.6s, CLS 0. That session includes the admin bar and is **not** the public page.

---

## Top issues

### 1. High — render-blocking CSS, ~2100ms FCP/LCP

Nine stylesheets in `<head>`. Lighthouse `render-blocking-insight` estimated savings:

| File | Transfer | Est. waste |
|---|---|---|
| `assets/dist/main-Cqel--mv.css` | 40.6 KB gzip | **1055ms** |
| Google Fonts CSS (`@import` from main.css) | 1.2 KB | **775ms** |
| `assets/dist/app-CIGW-MKW.css` (Leaflet) | 6.8 KB | **455ms** |
| `cbfs-appointment-calendar.css` | 3.5 KB | 305ms |
| `cbfs-form-select.css` | 0.6 KB | 305ms |
| `class-bookings-with-stripe/style.css` | 4.7 KB | 305ms |
| `cbfs-packs.css` | 1.8 KB | 305ms |
| `cbfs-status-themes.css` | 0.8 KB | 155ms |
| `cbfs-booking.css` | 4.0 KB | 155ms |

Unused-CSS audit is **0 bytes** — Tailwind’s content scan is working. The cost is how CSS is loaded, not dead utilities.

### 2. High — font request chain

Document → `main.css` → `fonts.googleapis.com/css2?family=Archivo…&family=Inter…` → `fonts.gstatic.com` woff2 (Inter 48 KB + Archivo 35 KB). Longest chain **589ms** unthrottled; much worse on Slow 4G. No `preconnect`. Scope Trial is a local **TTF** (17 KB) discovered from that same CSS. `font-display: swap` is already set on Scope Trial.

Source: first rule of `assets/css/main.css`.

### 3. High — Ken Burns fights LCP

`assets/js/motion/effects/kenBurns.js` picks a **random** first slide (`Math.floor(Math.random() * slides.length)`) and sets the others to `opacity: 0`. `blocks/hero/hero.php` only marks slide **0** as `eager` + `fetchpriority="high"`. If JS picks slide 2–4, the prioritized image is hidden and a `loading="lazy"` full-bleed JPEG becomes visible. Overlay text wins LCP.

Lighthouse also downloaded `andy_jumps_landscape.jpg` at **208 KB** (`lp_wide_lg` / 1920w) on a **412px** viewport because lazy slides use `sizes="auto, 100vw"`.

### 4. Medium — unused JS, ~300ms LCP / 79 KB

`app-edXity_X.js` is **335 KB** raw / **102 KB** gzip. Lighthouse marks **77% unused** (265 KB of 343 KB decoded).

Cause: `assets/js/app.js` statically imports `SiteNetworkMap.js` and `ClassDetailOsmMap.js`, so Rollup puts **Leaflet + `leaflet.css`** in the global bundle. The map mount (`[data-component="site-network-map"]`) only exists on `templates/classes-map.php`.

Clasbpro calendar JS is ~93% unused until the drawer opens (`cbfs-booking.js` 28 KB unused of 30 KB decoded, plus calendar-core / appointment / class-date).

`swiper` is in `package.json` but not imported — unused npm dependency, not in the bundle. `@tailwindplus/elements` is required on the homepage for `el-dialog`; keep it.

### 5. Medium — hero JPEGs, 269 KB (Lighthouse: 150ms LCP)

No WebP/AVIF. Measured JPEG sizes:

| Asset | Bytes |
|---|---|
| `2048x2048/emilia-wall-run.jpg` | 289 KB |
| `2048x2048/alfredo-strides.jpg` | 270 KB |
| `2048x2048/alfredo-climbs.jpg` | 261 KB |
| `lp_wide_lg/andy_jumps_landscape.jpg` | 208 KB |

Hero srcset tops out at `2048x2048`. Slide 0 is wired correctly (`fetchpriority="high"`, `sizes="100vw"`); slides 1–3 still compete on the network.

### 6. Low on this Docker box — Cache-Control missing

Lighthouse cache insight: **602 KB**, **750ms FCP / 1200ms LCP** on repeat views. Static files have `ETag` / `Last-Modified` only. This is Apache in Docker, not theme PHP. Production should set hashed Vite assets to `immutable` (see Cloudways caching doc). Do not chase this on localhost.

### Skipped (0ms or already fine)

- Unused CSS: 0ms
- HTML gzip: document is **42.6 KB** gzipped (255 KB raw)
- Minification: already on via Vite
- CLS: 0.002 from the decode `<h1>` — leave it
- Preconnect-only to `fonts.googleapis.com`: 59ms — do not do this in isolation; fix the `@import` instead

---

## Recommendations

### 1. Break the font chain (biggest first-paint win)

Stop discovering Inter/Archivo from inside CSS. Self-host the two woff2 files, or print `<link rel="preconnect">` plus a render-blocking `<link rel="stylesheet">` in `wp_head` **before** `main.css`. A CSS `@import` cannot start until `main.css` has downloaded.

### 2. Don’t load Leaflet on the homepage

Dynamic-import the map modules so Rollup emits a separate chunk:

```javascript
siteNetworkMap: {
  init: async () => {
    const { initSiteNetworkMap } = await import('./elements/SiteNetworkMap.js');
    return initSiteNetworkMap();
  },
  selector: '[data-component="site-network-map"]',
  lazy: true,
},
```

Same for `classDetailOsmMap`. That should drop most of the 81 KB unused JS and remove render-blocking `app-CIGW-MKW.css` from pages without a map.

### 3. Make Ken Burns LCP-safe

In `kenBurns.js`, start at slide **0** on first paint (randomize only after the first hold). Keep slide 0 eager + `fetchpriority="high"`. Do not set `opacity: 0` on slide 0 before first paint. For lazy slides, use an explicit `sizes="100vw"` (not `auto`) or don’t attach them until the first crossfade.

### 4. Serve hero photos as AVIF/WebP

Enable WordPress `image/webp` (and AVIF if the host allows) so `parts/components/media-photo.php` emits a `<picture>`. Compress `lp_wide_lg` so a 412px phone is not offered a 1920w JPEG.

### 5. Defer Clasbpro calendar CSS

Homepage booking is real (`lp_clasbpro_needs_booking_assets()` is true because of the drawer), but `cbfs-appointment-calendar.css`, `cbfs-form-select.css`, and `cbfs-status-themes.css` do not need to block first paint. Load them when the drawer opens.

### 6. Production cache headers

For hashed Vite assets: `Cache-Control: public, max-age=31536000, immutable`. For `/uploads/`: a long `max-age` plus revalidation. Skip this on localhost.

---

## Codebase findings

| | |
|---|---|
| Stack | Classic WordPress theme, **Vite 6** + Tailwind v4 + daisyUI. `target: 'es2022'`, tree-shaking on, no production source maps in `assets/dist`. |
| Entries | `assets/css/main.css` → `main-Cqel--mv.css` (266 KB / 40 KB gzip). `assets/js/app.js` → `app-edXity_X.js` (335 KB / 102 KB gzip) + sibling `app-CIGW-MKW.css` (Leaflet). |
| CSS | Tailwind content scan is working. Do not run a PurgeCSS pass. |
| JS | Single `app` entry. Leaflet should be a dynamic `import()`. `@tailwindplus/elements` stays. |
| Fonts | Inter + Archivo via Google; Scope Trial local TTF with `font-display: swap`. |
| Compression | HTML/CSS/JS gzip on this Apache. Images uncompressed JPEG. |
| HTML | Public homepage **255 KB** raw / **43 KB** gzip. No admin bar. |

---

## Accessibility (high-level, from the a11y tree)

- Skip link, one `<main>`, booking buttons have names.
- **12 client-logo links have no accessible name** (empty `alt` on the GIFs under `wp-content/uploads/Logos/`).
- Decode animation on the `<h1>` exposes scrambled characters to the a11y tree until it settles. CLS from that is 0.002 — not a visual issue, but AT hears garbage.
- Unnamed textbox on the homepage looks like a honeypot — confirm it is `aria-hidden` or `tabindex="-1"`.

---

## Resource summary (Lighthouse mobile)

| Type | Requests | Transfer |
|---|---|---|
| Total | 30 | 1,150 KB |
| Image | 7 | 397 KB |
| Script | 7 | 127 KB |
| Font | 3 | 98 KB |
| Stylesheet | 9 | 62 KB |
| Document | 1 | 42 KB |
| Third-party (Google Fonts) | 3 | 83 KB |
