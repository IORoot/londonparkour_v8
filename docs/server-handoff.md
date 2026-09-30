# Server-side performance — handoff for Andy

London Parkour (WordPress on Cloudways), 2026-09-30. This lists the performance and Lighthouse/audit findings we did **not** fix in the theme because they sit in hosting, caching layers or HTTP headers. Theme-side fixes (render-blocking CSS, fonts, JS size, images, Clasbpro CSS deferral) are tracked separately and are not for you. Numbers come from two runs on different targets, so they are not directly comparable: a squirrelscan crawl of **staging** (`docs/audit-squirrel.md`, 2026-09-22, 25 pages) and a Lighthouse run on a **local Docker** copy (`web-perf-homepage.md`, 2026-09-08). Nothing here has been measured on live.

## Do first (ordered by impact)

### 1. Slow server response (TTFB) on staging

- **What's wrong:** squirrelscan flagged `perf/ttfb` as "Very slow server response", 24 readings from **3,239 ms to 12,846 ms** (two above 12 s: 12,354 and 12,846; most 4-9 s). Example pages listed: `/about/`, `/blog/`, `/classes-map/`, `/classes/`, `/contact/`. Rule threshold: good < 600 ms, poor > 1000 ms. Source: `docs/audit-squirrel.md` line 328 (`perf/ttfb`); same data in `docs/staging.londonparkour.com-report.html` (perf/ttfb block).
- **For contrast:** local Docker TTFB was 442 ms (uncached PHP), so the theme itself is not the cause of multi-second responses. Source: `web-perf-homepage.md` "Core Web Vitals summary" (line 24). The doc says that number is "not production Varnish".
- **Uncertain:** we do not know whether Varnish/Redis are currently on for the staging app, or whether the crawl hit cold cache. The audit does not say. Start by confirming the state below.
- **Fix on Cloudways** (per `theme/docs/cloudways-caching.md` §1-3, do it on **each** application, staging and live):
  1. Servers -> Manage Services: **Varnish On**, **Redis On**, **Memcached Stop**. On 2GB+ servers Object Cache Pro activates with Redis (Settings -> Object Cache should say connected). Do not also activate the free "Redis Object Cache" plugin.
  2. Application Settings -> Varnish Settings: leave **Ignore Query String off**. Add rule URL / Exclude `\/wp-json\/clasbpro` and rule Cookie / Exclude `clasbpro_pack`. Keep Cloudways defaults (`wp-admin`, `wp-login.php`, `admin-ajax.php`, `wordpress_logged_in`).
  3. Breeze: keep plugin installed, **Cache System Disable**, Auto Purge Varnish **Enable**, Purge Cache After **240** minutes (keep at 4 h so the `wp_rest` nonce does not go stale; nonces tick every 12 h). Breeze JS/CSS/HTML minify and JS delay/defer stay **off**.
  4. Save, then Servers -> Manage Services -> Varnish -> **Purge**.
  5. No WP Rocket / LiteSpeed / other cache plugins. Cloudflare "Cache Everything" off unless `/wp-json/clasbpro/` is excluded.
- **Verify:** second request should be cached (private window, logged out):
  `curl -sI https://staging.londonparkour.com/ | grep -i x-cache` (expect HIT on the second hit; per `cloudways-caching.md` §7).
  `curl -sI 'https://staging.londonparkour.com/wp-json/clasbpro/v1/availability?class_id=1' | grep -i x-cache` (must NOT be a HIT).
  Timing: `curl -s -o /dev/null -w '%{time_starttransfer}\n' https://staging.londonparkour.com/` (run twice; the theme audit target is TTFB <= 0.8 s per `web-perf-homepage.md` line 9).
- **Note:** staging is behind HTTP basic auth (`cloudways-caching.md` §6). That may affect how crawlers and Varnish behave there; not confirmed by any source as a cause of the slow TTFB.

### 2. No Cache-Control / caching headers on any page or static asset

- **What's wrong:** squirrelscan `perf/cache-headers`: "No caching headers found", 25 of 25 pages (line 400). `perf/bad-caching`: "25/25 pages set no caching policy (no freshness lifetime and no validator)" (line 359). Source: `docs/audit-squirrel.md`; HTML report sections "Cache Headers" and "Weak Caching (site-wide)".
- Local Lighthouse (Docker Apache, not Cloudways) also found static files with only `ETag` / `Last-Modified`: cache insight **602 KB**, **750 ms FCP / 1,200 ms LCP** savings on repeat views. The source says "Do not chase this on localhost", so this is only a pointer to what to check on the real server. Source: `web-perf-homepage.md` §6 (line 93-95).
- **Fix** (targets stated in `web-perf-homepage.md` "Production cache headers", line 142-144, and the squirrelscan solution text):
  - Hashed Vite assets under `assets/dist`: `Cache-Control: public, max-age=31536000, immutable`.
  - `/uploads/`: a long `max-age` plus revalidation.
  - HTML: short max-age, or no-cache with revalidation (squirrelscan guidance).
  - Breeze "Browser Cache: Enable" (`cloudways-caching.md` §3 Basic Options) is the documented switch. Whether it produces the values above on this stack is **not confirmed**. If it does not, the header rule has to go in nginx/Apache config or via Cloudways support; no source gives the exact snippet, so none is invented here.
- **Verify:**
  `curl -sI https://<site>/wp-content/themes/londonparkour_v8/assets/dist/<hashed-file>.css | grep -i cache-control` (expect `max-age=31536000, immutable`; get the real filename from the page source).
  `curl -sI https://<site>/ | grep -i -E 'cache-control|etag|last-modified'`.

### 3. Compression (gzip / Brotli)

- **What's wrong:** nothing measured as broken. Local Docker Apache gzips HTML/CSS/JS (HTML 42.6 KB gzipped vs 255 KB raw). Source: `web-perf-homepage.md` lines 100 and 157. The squirrelscan `perf/bad-caching` rule text says to enable gzip/Brotli, but reports no compression failure for staging.
- **Action:** confirm on the Cloudways stack only. Breeze "Gzip Compression: Enable" is the documented setting (`cloudways-caching.md` §3). Brotli is mentioned only in squirrelscan's generic advice; we do not know if it is available.
- **Verify:** `curl -sI -H 'Accept-Encoding: br, gzip' https://<site>/ | grep -i content-encoding`.

## Then

### 4. Security headers missing (HSTS, X-Frame-Options, CSP)

Lower priority than speed; squirrelscan Security group score 85, 4 warnings. Source: `docs/audit-squirrel.md` lines 171-179.

- `security/hsts`: "Missing Strict-Transport-Security header". Suggested by the rule: `Strict-Transport-Security: max-age=31536000; includeSubDomains`, start with a short max-age (1 day) then raise to 1 year.
- `security/x-frame-options`: "No clickjacking protection". Rule suggests `X-Frame-Options: SAMEORIGIN` (or DENY), with CSP `frame-ancestors 'self'` preferred in modern browsers.
- `security/csp`: "No Content-Security-Policy header". Rule suggests starting with **report-only**. Careful: the site uses Stripe Checkout, Google Tag Manager and Leaflet, so a strict CSP can break booking; test on staging first. (That caution is ours, not from a source.)
- Verify: `curl -sI https://<site>/ | grep -i -E 'strict-transport|x-frame|content-security'`.
- Where to set them (nginx, Cloudways config, or a plugin) is not specified in any source.

### 5. Redirect and 4xx noticed by the crawler (probably content, confirm)

- `/legal/` 301s to `/docs/terms-of-service/` (`links/redirect-chains`, `crawl/canonical-chain`, line 207 / 138). Single hop; fix is to point internal links at the final URL, which is theme/content work. Listed only so you know the 301 is expected.
- `/docs/` returns **403** but is listed in the sitemap (`crawl/sitemap-4xx`, line 131). Unknown whether this is a server rule or WordPress. Please check; if the 403 is a server rule, it is yours.
- HTTP/2 / HTTP/3: only appears as generic advice in the squirrelscan TTFB solution text (report line ~1577). No source shows it is off. Just confirm: `curl -sI --http2 https://<site>/ | head -1`.

### 6. WP-Cron (not a Lighthouse issue, but server-side and open)

Source: `cloudways-caching.md` §6. Varnish-cached pages never reach PHP, so WP-Cron will not fire from visitors; on staging `wp-cron.php` returns 401 behind basic auth, leaving `clasbpro_expire_holds` (every 5 min) overdue.
- Add to crontab on staging **and** live (live `public_html` path differs):
  `*/5 * * * * cd /home/master/applications/rswhxpawjz/public_html && /usr/local/bin/wp cron event run --due-now >/dev/null 2>&1` (that path is staging's).
- In `wp-config.php`: `define( 'DISABLE_WP_CRON', true );`.
- Also WordPress Settings -> General -> Timezone must be Europe/London (WordPress setting, not server).

## Already handled in theme / not for you

Render-blocking CSS/fonts, unused JS (`app-*.js` 335 KB raw / 102 KB gzip), image sizing/format (WebP/AVIF), Clasbpro calendar CSS deferral, inline SVG, unminified inline CSS: all theme-side, see `web-perf-homepage.md`. WebP/AVIF output depends on WordPress image support ("if the host allows AVIF", `web-perf-homepage.md` line 136); if the Cloudways PHP image library lacks AVIF, tell us. `theme/docs/HANDOFF.md` contains no server or hosting perf items.

## Source conflicts and caveats

- The staging audit (crawl, no cookies) and the local Lighthouse run measure different things; the local run was **uncached PHP on Docker**, so its 442 ms TTFB does not say anything about Cloudways.
- `web-perf-homepage.md` line 9 uses TTFB <= 0.8 s (web.dev); squirrelscan's rule uses good < 600 ms / poor > 1000 ms. Aim for the stricter figure.
- `cloudways-caching.md` says Breeze Cache System **Off** (Varnish is the page cache) but Breeze Browser Cache and Gzip **On**. It does not state the resulting `Cache-Control` values; item 2 must be verified with `curl`, not assumed.
- The crawl date (2026-09-22) predates this handoff; the caching setup may have changed since. Re-check state before changing anything.

## Verify after changes

- [ ] Varnish On, Redis On, Memcached Stopped, on both staging and live apps; Varnish purged.
- [ ] `curl -sI https://<site>/ | grep -i x-cache` gives HIT on the second request.
- [ ] `/wp-json/clasbpro/...` is not a HIT.
- [ ] `/classes/?week=1` differs from `?week=0` (Ignore Query String is off).
- [ ] Hashed asset returns `Cache-Control: public, max-age=31536000, immutable`.
- [ ] `content-encoding: gzip` (or `br`) on HTML/CSS/JS.
- [ ] HSTS / X-Frame-Options present (and CSP if added, report-only first).
- [ ] TTFB re-measured: `curl -s -o /dev/null -w '%{time_starttransfer}\n' <url>` on `/`, `/classes/`, `/about/`, `/blog/`, `/contact/`.
- [ ] Re-run Lighthouse (mobile) and squirrelscan against staging; compare with `perf/ttfb`, `perf/cache-headers`, `perf/bad-caching` above.
- [ ] Booking still works after cache changes: drawer loads, seats update, coupon CTA switches after purchase, no 403 after a tab is left open ~5 h.

## Sources

- `/Users/wearebold/Sites/WordPress/londonparkour_v8/wp-content/themes/londonparkour_v8/docs/web-perf-homepage.md` (lines 9, 17-32, 93-100, 136, 142-158)
- `/Users/wearebold/Sites/WordPress/londonparkour_v8/wp-content/themes/londonparkour_v8/docs/cloudways-caching.md` (§1-3, 6-9)
- `/Users/wearebold/Sites/WordPress/londonparkour_v8/docs/audit-squirrel.md` (lines 131, 138, 171-179, 207, 328, 359, 400)
- `/Users/wearebold/Sites/WordPress/londonparkour_v8/docs/staging.londonparkour.com-report.html` (perf/ttfb, perf/bad-caching, perf/cache-headers, security/* sections)
- `/Users/wearebold/Sites/WordPress/londonparkour_v8/wp-content/themes/londonparkour_v8/docs/HANDOFF.md` (checked; no server items)
