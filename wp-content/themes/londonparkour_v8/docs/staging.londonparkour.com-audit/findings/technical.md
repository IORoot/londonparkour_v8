# Technical SEO findings — staging.londonparkour.com

**Scope:** Pre-launch V8. Staging is behind HTTP basic auth, so Google cannot crawl this host today. Findings describe what *will* ship if this theme/config is cut over. Do not treat staging indexation as live indexation.

**Fetched:** 2026-09-11 via curl (Chrome/128 UA + HTTP basic auth). Auth credentials are not recorded here.

**Also used:** `SHARED.md`, `sitemap.md`, `performance.md`, `geo.md`, `ecommerce.md`. Lab CWV is from `performance.md` (Lighthouse 13.4.1); no CrUX/PSI field data on staging.

Scores are this skill’s heuristics, not Google-internal signals.

---

## Technical Score: 54/100

### Category Breakdown

| Category | Status | Score | Why |
|---|---|---:|---|
| Crawlability | warn | 58 | `robots.txt` 200, sitemap declared, Googlebot not blocked. `/docs/` nginx **403**. `/tutorials/` hub missing from sitemap. Junk URLs crawlable. Auth wall today. |
| Indexability | warn | 52 | Canonical / `og:url` / JSON-LD `@id` follow `home_url()` — **not frozen**. Recheck 2026-09-11: no ACF `seo_canonical` to another host or path (`canonical-overrides.md`). `sample-page` and `clasbpro-theme-preview` still indexable on staging. `/legal/` is a PHP **301**, not a 200 with a foreign canonical. |
| Security | warn | 48 | HTTPS + HTTP→HTTPS 301. HTML responses have **no** HSTS, CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, or X-Robots-Tag. `/wp-json/` is 200 (JSON `noindex`). Security headers are a lightweight ranking signal — do not over-weight. |
| URL Structure | warn | 55 | Trailing-slash 301s work on `/about`, `/classes`, `/contact`, `/tutorials`. `/docs` (no slash) 301s to **http://**. `/book/` 301s to `/booking-cancelled/`. Live V7 paths (`/tutorial/`, `/bookings/`, `/giftcards/`) are a launch redirect map, not a staging 200. |
| Mobile | pass | 72 | Viewport present. Visual audit: no horizontal overflow; H1 in the fold (`visual.md`). Lab LCP still fails (see CWV). |
| Core Web Vitals | fail | 30 | Lab mobile LCP **7.6 s** `/`, **7.7 s** `/classes/` (`performance.md`). CLS good. No field INP. `/tutorials-category/` is 1.48 MB HTML. |
| Structured Data | fail | 40 | JSON-LD in first HTML (good). Content of the graph is polluted — see `schema.md`. |
| JS Rendering | pass | 85 | Titles, H1, copy, prices, JSON-LD are server-rendered. Booking drawer and OSM maps are JS. |
| IndexNow | fail | 10 | `/indexnow-key.txt` **404**. Optional for Google; relevant for Bing/Yandex only. |

---

## Observed — robots.txt

`https://staging.londonparkour.com/robots.txt` → **200**, `text/plain`.

- Cloudflare managed AI blocks: GPTBot, Google-Extended, ClaudeBot, CCBot, Bytespider, Amazonbot, Applebot-Extended, meta-externalagent, CloudflareBrowserRenderingCrawler — `Disallow: /`.
- `User-agent: *` `Allow: /` plus `Content-Signal: search=yes,ai-train=no,use=reference`.
- WordPress: `Disallow: /wp-admin/` + `Allow: /wp-admin/admin-ajax.php`.
- `Sitemap: https://staging.londonparkour.com/wp-sitemap.xml` (**200**).
- **Not listed (allowed by `*`):** Googlebot, OAI-SearchBot, PerplexityBot, ChatGPT-User.

**Interpretation:** Google Search is not blocked. Staging **HTTP auth** still stops Googlebot until launch. AI-crawler policy matches live V7 (`geo.md`); that is a product decision, not a crawl defect for classic Search.

`/llms.txt` **404** (Google Search ignores it).

---

## Observed — meta robots and canonicals

Homepage (matches `SHARED.md`):

- Title: `London Parkour | Practical Movement Training & Classes`
- Meta robots: `max-image-preview:large, index, follow, max-snippet:-1, max-video-preview:-1`
- Canonical / og:url: `https://staging.londonparkour.com/`
- `<html lang="en-US">`, `og:locale` `en_US`

**Sampled 200 pages** send `index, follow` and a **self-canonical on the current host** (`home_url()`). That is correct for staging; it is not a hard-coded `staging.londonparkour.com` string. `/legal/` is **not** a 200 — it 301s (PHP) to `/docs/terms-of-service/`. See `canonical-overrides.md`.

Utility page `/booking-cancelled/` (destination of `/book/`): `noindex, nofollow` — correct for that URL, wrong as a booking CTA target.

No HTML `X-Robots-Tag` header on page responses. `/wp-json/` sends `x-robots-tag: noindex` (correct for the API index).

---

## Observed — security headers (homepage HTML 200)

Present: `server: cloudflare`, `content-type: text/html; charset=utf-8`, `alt-svc: h3`, NEL/report-to.

**Absent on HTML:** `Strict-Transport-Security`, `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `X-Robots-Tag`.

Matches `SHARED.md`. `/wp-json/` *does* send `x-content-type-options: nosniff`. Cloudflare 520 on `/xmlrpc.php` sent `x-frame-options: SAMEORIGIN` and `referrer-policy: same-origin` — that is the error surface, not the theme HTML.

HTTPS: `http://staging.londonparkour.com/` → **301** `https://staging.londonparkour.com/`. `www.staging.londonparkour.com` does not resolve. Homepage HTML has **no** `http://` resource URLs (no mixed content in the document).

`/wp-json/` **200**, ~280 KB JSON, namespaces include `wp/v2` and `clasbpro/v1`. Discoverable via `Link: rel="https://api.w.org/"` on HTML. Not a ranking issue; it is an information-disclosure / crawl-noise issue if it stays open on live.

---

## Sample of 20 sitemap (and hub) URLs

Selected from `sitemap-urls.txt` plus `/tutorials/` (hub is **not** in the sitemap — `sitemap.md`).

| URL | HTTP | Canonical | Robots | Title |
|---|---|---|---|---|
| `/` | 200 | `https://staging.londonparkour.com/` | index, follow | London Parkour \| Practical Movement Training & Classes |
| `/sample-page/` | 200 | self, staging | index, follow | Sample Page \| London Parkour |
| `/clasbpro-theme-preview/` | 200 | self, staging | index, follow | Booking Form Theme Preview \| London Parkour |
| `/classes/` | 200 | self, staging | index, follow | Parkour Classes in London \| Weekly Timetable |
| `/about/` | 200 | self, staging | index, follow | About London Parkour \| Outdoor Classes Since 2018 |
| `/contact/` | 200 | self, staging | index, follow | Contact London Parkour |
| `/legal/` | **301** | — | — | PHP `lp_docs_redirects()` → `/docs/terms-of-service/` |
| `/workshops/` | 200 | self, staging | index, follow | Parkour Workshops in London |
| `/classes/adult-beginners-outdoor/` | 200 | self, staging | index, follow | Adult Beginners East \| Parkour in London |
| `/classes/kids-class-west-6-9s/` | 200 | self, staging | index, follow | Kids Class West (6-9s) \| Parkour in London |
| `/classes/locations/vauxhall/` | 200 | self, staging | index, follow | Parkour Classes at Vauxhall \| London |
| `/classes/locations/old-street/` | 200 | self, staging | index, follow | Parkour Classes at Old Street \| London |
| `/coaches/andy-pearson/` | 200 | self, staging | index, follow | Andy Pearson \| Parkour Coach, London |
| `/blog/definitive-guide-to-army-military-parkour-training/` | 200 | self, staging | index, follow | Definitive Guide to Army & Military Parkour Training |
| `/blog/parkour-faq/` | 200 | self, staging | index, follow | Parkour FAQ \| London Parkour |
| `/tutorials/deadhang/` | 200 | self, staging | index, follow | Deadhang \| London Parkour |
| `/tutorials/vault-landing/` | 200 | self, staging | index, follow | Vault Landing \| London Parkour |
| `/tutorials/crouch-walk/` | 200 | self, staging | index, follow | Crouch walk \| London Parkour |
| `/tutorials/` | 200 | self, staging | index, follow | Parkour Tutorials \| London Parkour |
| `/docs/gift-cards/` | 200 | self, staging | index, follow | Parkour Gift Cards \| London Parkour |

---

## Targeted checks

### `sample-page` and `clasbpro-theme-preview`

Both **200**, `index, follow`, self-canonical, **in the page sitemap**. Confirms `sitemap.md`. Default WP copy / `[clasbpro_theme_preview]` shortcode in the meta description. Must `noindex` + drop from sitemap, or delete, before public DNS.

### `/book/`

**301** `x-redirect-by: WordPress` → `/booking-cancelled/` (`noindex, nofollow`). Homepage closing CTA still points at `/book/` (`visual.md`, `ecommerce.md`). Do not add `/book/` to the sitemap until it is a real booking URL.

### Trailing slashes

| Request | Result |
|---|---|
| `/about`, `/classes`, `/contact`, `/tutorials` | **301** → HTTPS slash URL |
| Slash URL of those four | **200** |
| `/docs` (no slash) | **301** → `http://staging.londonparkour.com/docs/` (scheme downgrade) |
| `/docs/` | **403** nginx (`<center>nginx</center>`), 548 bytes — not WordPress HTML |

Primary nav Docs href is `/docs` (no slash). That path is a dead end.

### `/docs/` hub vs children

| URL | HTTP |
|---|---|
| `/docs/` | **403** (in sitemap) |
| `/docs/frequently-asked-questions/` | **301** → `/docs/` → **403** (also in sitemap) |
| `/docs/gift-cards/`, `/docs/pricing/`, `/docs/privacy-policy/`, `/docs/beginners-class/`, `/docs/contacting-us/` | **200** |

Nginx is blocking the hub; child guides still render.

### `/wp-json/`

**200** JSON. `x-robots-tag: noindex`. REST index names the site `London Parkour`, `timezone_string: Europe/London`. Fine to leave for the app; do not expect it in Search.

---

## JS / AMP / IndexNow / crawl budget

- Critical SEO tags are in the first HTML (canonical, robots, title, JSON-LD). No AMP.
- IndexNow key file 404.
- Site is 752 URLs, not >10k. Crawl-budget risk is **quality** (junk + 55 category archives + 1.48 MB `/tutorials-category/`), documented in `sitemap.md` / `performance.md`.
- HTML sizes sampled: `/` 262 KB; adult class 206 KB; `/tutorials/` hub  (prior) 323 KB — under Googlebot’s 2 MB HTML cap. `/tutorials-category/` at ~1.48 MB is the page that approaches the cap.

---

## Critical Issues (fix before public DNS)

1. **`/sample-page/` and `/clasbpro-theme-preview/` are 200 + index + sitemap** on staging. Default WP and plugin-preview junk. Theme slug noindex for the preview page is in git, not deployed yet.
2. **`/docs/` returns nginx 403** and is listed in `wp-sitemap.xml`. FAQ doc 301s into that 403. Nav “Docs” uses the no-slash URL that also 301s to **HTTP**.
3. **`/book/` → `/booking-cancelled/`.** Conversion URL is a noindex utility page.

## High Priority (before / in the first week of launch)

4. **Redirect map vs live V7** (`cluster.md` / `ecommerce.md`): `/tutorial/{slug}/` → `/tutorials/{slug}/`; `/bookings/` (404 here); `/giftcards/` (404 here); teens slug → `youth-class-west-10-14s`. Live GSC already indexes the old paths.
5. **Do not ship `index, follow` on a public staging hostname.** After cutover, staging should be `noindex` (or auth-only). Production HTML must stay `index, follow`.
6. **Lab LCP 7.6–7.7 s** on `/` and `/classes/` (`performance.md`). Field CWV does not exist for this host.

## Medium Priority (within 1 month)

7. Add HSTS (and the other missing headers) at Cloudflare. Ranking weight is small; they are still table-stakes for a booking site.
8. `/docs` no-slash **HTTP** Location — force HTTPS in the redirect.
9. `/tutorials/` hub is 200 and linked from nav but **absent from the sitemap**.
10. `/wp-json/` 200 with ClasbPro namespace — decide whether live should keep it public.
11. IndexNow only if Bing indexing speed matters.

## Low Priority (backlog)

12. `/llms.txt` 404 — Google ignores it.
13. `xmlrpc.php` Cloudflare 520 — not an SEO ranking issue.
14. RSS `/feed/` 200 (841 bytes in this fetch) — normal.
15. **Canonicals are dynamic** (`home_url()`). Not a theme rewrite. At cutover, set Site Address / `WP_HOME` to `https://londonparkour.com` and run `search-replace`. See `canonical-overrides.md`.
16. **`/legal/` already 301s** to `/docs/terms-of-service/` via `lp_docs_redirects()`. Earlier “200 with canonical to terms” was curl following redirects. No extra 301 needed.

---

## Limitations

No Googlebot fetch-as, no live GSC inspection of staging (auth), no PSI/CrUX on this host. Trailing-slash and `/docs/` checks are curl, not Chrome. CWV numbers are lab-only from `performance.md`.
