# Sitemap findings — staging.londonparkour.com

**Scope:** Pre-launch V8. Staging is behind HTTP basic auth, so Google cannot crawl this host today. Findings are about what *will* ship if `wp-sitemap.xml` is submitted on live. Do not treat staging indexation as live indexation.

**Sources:** `sitemap-urls.txt` (752 unique), `https://staging.londonparkour.com/wp-sitemap.xml` and child files (curl 2026-09-11), live GSC `sc-domain:londonparkour.com` via MCP (same day).

---

## Score / verdict

Valid XML, well under the 50k / 50MB caps, HTTPS-only, referenced from `robots.txt`. **Quality is poor:** junk pages are indexable and listed, the tutorials hub is missing, taxonomy archives are bloated, and live GSC already shows this URL mix barely indexes.

---

## Observed composition (752 unique)

| Source | Count | In sitemap? |
|---|---|---|
| `lp_tutorial` (`/tutorials/{slug}/`) | 609 | Yes |
| Tutorial categories (`/tutorial-category/`) | 55 | Yes — **no `<lastmod>`** |
| Support/docs | 15 | Yes |
| Series tax (`/series/`) | 13 | Yes |
| Pages | 15 | Yes — includes junk |
| Blog posts + `/blog/` | 11 | Yes |
| Blog tags | 10 | Yes |
| Classes (6) + location pages (3) + `/classes/` | 10 | Yes |
| Coaches | 4 | Yes |
| Blog categories / levels / support-category / tutorial-tag | 3 each (12) | Yes |
| Other hubs | rest | Mixed |

Index file: 14 child sitemaps (pages, blog, coaches, locations, support, tutorials, classes, 7 taxonomies). Core WP format. No `<priority>` / `<changefreq>` (good — Google ignores them).

---

## Issues

### Critical — junk URLs are indexable and in the page sitemap

| URL | Status | robots | Notes |
|---|---|---|---|
| `/sample-page/` | 200 | `index, follow` | Default WP copy: “This is an example page…” Canonical self. First URL in `wp-sitemap-posts-page-1.xml`. |
| `/clasbpro-theme-preview/` | 200 | `index, follow` | Title “Booking Form Theme Preview”. Meta description is the shortcode `[clasbpro_theme_preview]`. Body: “Select a theme from the Themes screen and open Live preview.” |

**Fix before launch:** delete or `noindex` + remove from sitemap. Do not ship either URL on the public domain.

### High — important URLs missing or broken

| URL | HTTP | In sitemap? | What happened |
|---|---|---|---|
| `/tutorials/` (hub) | **200**, `index, follow`, canonical self, title “Parkour Tutorials” | **No** | CPT archive is not in core sitemaps. Nav, breadcrumbs, and “BY TUTORIAL” all point here. 609 children are listed; the hub is not. |
| `/gift-cards/` (footer label) | **301 → `/docs/gift-cards/`** | Alias no; destination **yes** | Commerce URL is a docs article whose H1 is the generic docs heading “Questions, answered.” |
| `/book/` | **301 → `/booking-cancelled/`** (`noindex, nofollow`) | No | Homepage closing CTA `href="/book/"`. This is not a missing sitemap row — it is a **broken booking URL**. Do not add `/book/` until it resolves to a real booking page. |

`/tutorials/series/` and `/tutorials/category/` return 200 but canonicalize to `/tutorials-series/` and `/tutorials-category/`, which **are** in the page sitemap. Duplicate paths, not missing hubs.

### High — taxonomy bloat (~90 archive URLs)

These are all `index, follow`, listed, and thin:

- 55 `/tutorial-category/{slug}/`
- 13 `/series/{slug}/`
- 10 `/blog-tag/` including `advert`, `army`, `military`, `music-video`
- 3 `/blog-category/`, 3 `/level/`, 3 `/support-category/`, 3 `/tutorial-tag/` (`tutorial`, `demonstration`, `challenge`)

Spot checks:

- `/blog-tag/advert/` — H1 “Blog Tag Archives”, title `advert | London Parkour`
- `/tutorial-tag/tutorial/` — H1 “Tutorial Tag Archives”
- `/level/beginner/` — H1 “Level Archives”

Live GSC on the current Yoast index already shows the same pattern: **988 submitted / 19 indexed (2%)**. Tutorial sitemaps 1–5: 841 URLs submitted, **0 indexed**. `tutorial_category-sitemap.xml`: 55 submitted, **0 indexed**. `blog-sitemap.xml`: 11 / 0.

**Interpretation:** submitting another 90 thin tax URLs plus 609 tutorials on V8 will repeat the live indexation failure unless archives are `noindex` (or omitted) and the hub + class/location pages are the ones Google is asked to crawl.

### Medium — `<lastmod>` quality

- Page sitemap: present; several marketing pages share `2026-09-08T16:35:29+01:00` (template save, not content change).
- Tutorial posts: 609 `<lastmod>` values but only **24 unique**; clusters of 86 / 78 / 50 URLs on `2026-08-14` timestamps (bulk import). Google ignores lastmod when it is not verifiably the last significant content change.
- Taxonomy sitemaps: **zero** `<lastmod>`.
- Index file: **no** `<lastmod>` on child sitemap loc entries.

### Medium — live GSC sitemap hygiene (launch blocker for the *live* property)

Queried 2026-09-11 against `sc-domain:londonparkour.com`:

| Submitted sitemap | Submitted | Indexed | Last downloaded | Notes |
|---|---|---|---|---|
| `https://londonparkour.com/sitemap_index.xml` | 988 | 19 | 2026-09-07 | Current Yoast index. 2% index rate. `page-sitemap.xml` alone has **561 warnings**. |
| `http://londonparkour.com/sitemap_index.xml` | 988 | 19 | 2026-09-08 | Duplicate HTTP property. Stale. |
| `https://dev.londonparkour.com/sitemap_index.xml` | 59 | 19 | 2024-12-18 | Dev host. 1 error, 4 warnings. |
| `http://www.londonparkour.com/sitemap_index.xml` | 0 | 0 | 2021-09-19 | Dead; 1 error. Last fetched 2021. |

Totals across the four: **2035 submitted / 57 indexed (3%)**.

Live `robots.txt` still declares `Sitemap: https://londonparkour.com/wp-sitemap.xml`, which **301s**. GSC is on Yoast `sitemap_index.xml`. Staging V8 declares `wp-sitemap.xml` (core). At cutover, pick **one** HTTPS sitemap, submit only that, and delete the other three GSC entries.

### Info — format / robots

- Staging `robots.txt` → `Sitemap: https://staging.londonparkour.com/wp-sitemap.xml` (200, valid index).
- All listed URLs are HTTPS on the staging host.
- No image/video/news sitemap extensions.
- Path change vs live: V7 tutorials are `/tutorial/` (singular); V8 is `/tutorials/`. Class slugs also changed. Redirect map is a launch blocker (out of sitemap scope, but it determines whether these 609 new locs inherit equity).

---

## Recommendations (priority)

1. **Remove or noindex** `/sample-page/` and `/clasbpro-theme-preview/` and drop them from the page sitemap.
2. **Include the tutorials archive** `/tutorials/` (or convert it to a real page that core sitemaps emit).
3. **Fix `/book/`** so the homepage CTA does not 301 to a noindexed cancelled state. Only then consider listing the canonical booking URL.
4. Keep gift-card content at one canonical (`/docs/gift-cards/` or a real `/gift-cards/` page). Do not list both.
5. **`noindex` thin taxonomies** (blog tags, tutorial tags, levels, support categories) and omit them from the sitemap. Revisit tutorial categories / series only if they have unique intro copy.
6. On live GSC at launch: delete `dev.`, `http://`, and `http://www.` sitemaps; submit a single HTTPS index; do not carry 988 stale V7 locs into V8 without a redirect map.

---

## What this pass did not check

HTTP 200 for all 752 locs (sampled junk, hubs, taxonomies, locations, one tutorial). Full 752 HEAD crawl not run. Staging auth means GSC URL Inspection of these URLs is not possible until the host is public.
