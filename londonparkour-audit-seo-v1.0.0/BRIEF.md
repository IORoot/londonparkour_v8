# Audit brief — London Parkour (local WordPress)

## Target
http://localhost:8102/ — a LOCAL Docker WordPress site. Business: **London Parkour**,
a parkour / "practical movement" school running classes at three London sites
(Vauxhall, Old Street, Kilburn Park). Local service business (brick-and-mortar,
multi-location). Not yet launched publicly.

## CRITICAL TOOLING CONSTRAINT
`claude-seo run fetch_page.py` and friends **BLOCK localhost** (SSRF guard).
Do NOT try to fetch localhost through claude-seo. Instead:
- Use `curl -sL http://localhost:8102/<path>` in the Shell tool.
- Pre-crawled HTML is already saved at `/tmp/seo-audit-lp/pages/*.html`
  (filenames are the URL path with `/` replaced by `_`).
- `claude-seo run parse_html.py <file>` DOES work on a saved local file.
- For rendering / screenshots / Core Web Vitals, the `cursor-ide-browser`
  MCP namespace CAN reach localhost (browser_navigate, browser_take_screenshot,
  browser_cdp with Runtime.evaluate / Performance.getMetrics).

## WP-CLI
WP-CLI is NOT on the host. It runs in a Docker sidecar:
`/Users/andypearson/Sites/londonparkour_v8/wp-content/themes/londonparkour_v8/bin/wp <args>`
`wp db query` fails (MariaDB ssl plugin) — use `bin/wp eval '<php>'` or `bin/wp post meta`.

## Theme / architecture facts (do not re-derive)
- Classic PHP theme at `wp-content/themes/londonparkour_v8`.
- ALL SEO output is theme-owned in `app/includes/seo.php`. **Rank Math was
  removed today.** There is no SEO plugin. The theme prints title,
  meta description, canonical, Open Graph, Twitter, robots and a single
  JSON-LD `@graph`.
- Editor overrides were just added via ACF: per-entry field group `group_lp_seo`
  (fields `seo_title`, `seo_description`, `seo_image`, `seo_canonical`,
  `seo_noindex`, `seo_webpage_type`, `seo_disable_faq`, `seo_disable_offers`,
  `seo_disable_breadcrumbs`, `seo_schema_nodes`) plus a SEO tab on the
  Site Settings options page (`seo_title`, `seo_description`, `seo_image`,
  `seo_org_types`, `seo_org_name`, `seo_org_email`, `seo_org_phone`,
  `seo_same_as`). So "add a field for X" is usually already possible —
  prefer recommending we POPULATE these fields over building new machinery.
- Page bodies come from an ACF Flexible Content field `page_sections`, so
  `post_content` is EMPTY on nearly every page. That is why the automatic
  meta description falls through.
- Tailwind class strings in `parts/`, `blocks/` and page templates are copied
  byte-for-byte from a signed-off Storybook and MUST NOT be "improved".
- **Copy must never be invented.** The design file
  (`london_parkour_V7.pen`, encrypted) is the only source of truth for copy.
  If a headline is weak for SEO, REPORT IT as a copy decision for the owner —
  do not write replacement marketing copy as if it were fact. You may propose
  candidate wording clearly labelled as a proposal.

## Already-established facts (verified, reuse, don't re-verify)
- HTTP 200 homepage. `Server: Apache/2.4.68`, `X-Powered-By: PHP/8.3.32`.
- Response headers carry **no** HSTS, CSP, X-Content-Type-Options,
  Referrer-Policy or X-Frame-Options. Site is plain HTTP (localhost).
- `blog_public = 0` (WordPress "Discourage search engines" is ON).
  Consequence: `wp-sitemap.xml`, `/sitemap.xml`, `/sitemap_index.xml` all 404,
  and robots.txt advertises no sitemap.
- robots.txt = `User-agent: *` / `Disallow: /wp-admin/` /
  `Allow: /wp-admin/admin-ajax.php`. No Sitemap line.
- `/llms.txt` 404.
- Meta robots on public pages is forced to `index, follow,
  max-image-preview:large, max-snippet:-1, max-video-preview:-1` by the theme,
  which CONTRADICTS `blog_public = 0`.
- Published, publicly-queryable counts:
  page 18, blog 10, clasbpro_class 7, lp_tutorial **609**,
  lp_coach 4, lp_location **304**, support 15. ~949 total.
- `lp_location` splits 301 `spot` + 3 `site`. All 301 `spot` permalinks
  return **404** even though `get_permalink()` generates them.
  The 3 `site` ones (vauxhall, old-street, kilburn-park) return 200.
- All 609 tutorials are **under 300 words** (typically 60–90 words), each with
  a video. 13 `lp_series` terms.
- 7 classes, 22–117 words each, 2 of 7 have a featured image.
- Homepage: title 54 chars, description 150 chars, 1416 words, one `<main>`,
  one H1 "the world is your playground.", 44 images (5 with empty alt).
- Most other pages have meta description == the title string
  (e.g. `/blog/` desc is "Blog | London Parkour", 21 chars).
- Most non-home titles are 21–31 chars — under the 30–60 target.
- `/docs/frequently-asked-questions/` serves byte-identical HTML to `/docs/`
  and canonicalises to `/docs/`.
- Orphan/empty published pages: `/sample-page/` (WP default, 154 words),
  `/tutorials-category/`, `/tutorials-series/`, `/legal/`,
  `/clasbpro-theme-preview/` — all near-zero words and NOT noindexed.
  Only `blocks-qa`, `booking-error`, `booking-cancelled`, `booking-confirmed`
  are noindexed by the theme.
- Homepage JSON-LD `@graph`: `["SportsClub","LocalBusiness"]` org node
  (with aggregateRating 4.9/42 reviews and 3 Place children), `WebSite` with
  SearchAction, `WebPage`, and three `Offer` nodes. One script tag only.
- Full on-page extract for 16 pages: `/tmp/seo-audit-lp/onpage.txt` and
  `onpage.json`.

## What to return
A tight markdown findings report. For every finding give:
- Severity: Critical / High / Medium / Low / Info
- The evidence (a number, a URL, a header, a code line — not a vibe)
- The specific fix, naming the file or ACF field where it lands
- How we would know the fix failed (a falsifiable check)
Flag anything you could NOT verify because of the localhost constraint.
Do not invent copy. Do not recommend HowTo schema. Treat FAQPage as Info only
(Google retired FAQ rich results May 2026) — do not recommend removing it and
do not claim an AI-citation benefit.
