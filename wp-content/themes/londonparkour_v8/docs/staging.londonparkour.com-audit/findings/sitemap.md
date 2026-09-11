# Sitemap findings — staging.londonparkour.com

**Scope:** Pre-launch V8. Staging is behind HTTP basic auth, so Google cannot crawl this host today. Findings are about what *will* ship if `wp-sitemap.xml` is submitted on live. Do not treat staging indexation as live indexation.

**Sources:** `sitemap-urls.txt` (752 unique), `https://staging.londonparkour.com/wp-sitemap.xml` and child files (curl 2026-09-11), live GSC `sc-domain:londonparkour.com` via MCP (same day).

---

## Score / verdict

Valid XML, well under the 50k / 50MB caps, HTTPS-only, referenced from `robots.txt`. Recheck 2026-09-11: `/docs/` is **200**; `/sample-page/` is **404**; clasbpro preview is **out** of the page sitemap (13 locs). The tutorials hub is missing, taxonomy archives are bloated, and live GSC already shows this URL mix barely indexes.

---

## Observed composition (752 unique)

| Source | Count | In sitemap? |
|---|---|---|
| `lp_tutorial` (`/tutorials/{slug}/`) | 609 | Yes |
| Tutorial categories (`/tutorial-category/`) | 55 | Yes — **no `<lastmod>`** |
| Support/docs | 15 | Yes |
| Series tax (`/series/`) | 13 | Yes |
| Pages | 13 | Recheck: sample-page 404; clasbpro preview omitted |
| Blog posts + `/blog/` | 11 | Yes |
| Blog tags | 10 | Yes |
| Classes (6) + location pages (3) + `/classes/` | 10 | Yes |
| Coaches | 4 | Yes |
| Blog categories / levels / support-category / tutorial-tag | 3 each (12) | Yes |
| Other hubs | rest | Mixed |

Index file: 14 child sitemaps (pages, blog, coaches, locations, support, tutorials, classes, 7 taxonomies). Core WP format. No `<priority>` / `<changefreq>` (good — Google ignores them).

---

## Issues

### Info — Sample Page gone; clasbpro preview noindex

| URL | Status | robots | Notes |
|---|---|---|---|
| `/sample-page/` | **404** | `noindex, nofollow` | Not in `wp-sitemap-posts-page-1.xml`. Resolved. |
| `/clasbpro-theme-preview/` | 200 | `noindex, nofollow` | Out of the page sitemap. Resolved. |

### High — important URLs missing or broken

| URL | HTTP | In sitemap? | What happened |
|---|---|---|---|
| `/tutorials/` (hub) | **200**, `index, follow`, canonical self, title “Parkour Tutorials” | **No** | CPT archive is not in core sitemaps. Nav, breadcrumbs, and “BY TUTORIAL” all point here. 609 children are listed; the hub is not. |
| `/gift-cards/` (footer label) | **301 → `/docs/gift-cards/`** | Alias no; destination **yes** | Commerce URL is a docs article whose H1 is the generic docs heading “Questions, answered.” |
| `/book/` | **301 → `/classes/`** | No | Homepage closing CTA `href="/book/"`. Safe hop. Do not add `/book/` to the sitemap. |

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
- Path change vs live: V7 tutorials are `/tutorial/` (singular); V8 is `/tutorials/`. Class slugs also changed. The V7→V8 301 map is **live on staging** (`redirects.php`).

---

## Recommendations (priority)

1. **Sample Page is 404.** Clasbpro preview is already `noindex` and out of the page sitemap.
2. **Include the tutorials archive** `/tutorials/` (or convert it to a real page that core sitemaps emit).
3. **`/book/` already 301s to `/classes/`.** Do not add it to the sitemap.
4. Keep gift-card content at one canonical (`/docs/gift-cards/` or a real `/gift-cards/` page). Do not list both.
5. **`noindex` thin taxonomies** (blog tags, tutorial tags, levels, support categories) and omit them from the sitemap. Revisit tutorial categories / series only if they have unique intro copy.
6. On live GSC at launch: delete `dev.`, `http://`, and `http://www.` sitemaps; submit a single HTTPS index. V7→V8 301s are already in `redirects.php`.

---

## What this pass did not check

HTTP 200 for all 752 locs (sampled junk, hubs, taxonomies, locations, one tutorial). Full 752 HEAD crawl not run. Staging auth means GSC URL Inspection of these URLs is not possible until the host is public.
