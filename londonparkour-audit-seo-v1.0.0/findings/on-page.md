# On-Page SEO — findings

Owner: coordinator. Verified by curl + WP-CLI against http://localhost:8102 on
2026-09-08. 16 pages crawled and parsed; title/description checked across all
663 public posts and 13 taxonomy terms.

Raw data: `../onpage.txt`, `../onpage.json`, `../broken.txt`.

---

## Critical

### C1 — 486 of 609 tutorials are unreachable: archive pagination 404s

`/tutorials/` renders 123 tutorial links and pagination links to pages 2–6.
Every one of those pagination URLs returns **HTTP 404**:

```
200  /tutorials/
404  /tutorials/page/2/
404  /tutorials/page/3/
404  /tutorials/page/4/
404  /tutorials/page/5/
404  /tutorials/page/6/
```

`app/setup/queries.php:199` sets `posts_per_page` to 120 for this archive, and
`archive-lp_tutorial.php:241-242` reads `posts_per_page` / `max_num_pages` to
build the pagination control. The links are generated correctly; the paged
request does not resolve.

Consequence: 609 − 123 = **486 tutorial URLs have no internal link path at all**
and cannot be discovered by a crawler. There is also no sitemap (see technical
findings), so those 486 URLs are undiscoverable by any route.

Also emits 5 broken internal links on the single highest-link page on the site.

**Fix:** in `app/setup/queries.php`, the tutorials archive query. Diagnose why
the paged query 404s — likely `posts_per_page` being set without the query also
honouring `paged`, or a `pre_get_posts` guard that only matches the unpaged
request. Confirm `$wp_query->max_num_pages` and `get_query_var('paged')` agree.

**Falsifiable check:** `curl -o /dev/null -w '%{http_code}'` on
`/tutorials/page/2/` through `/page/6/` must all return 200, and the union of
tutorial detail links across pages 1–6 must equal 609 unique URLs.

---

## High

### H1 — `/about` is linked 17 times site-wide and returns 404

Every page's footer links to `/about`, which does not exist. 17 occurrences
across the 16-page crawl (it is in the global footer, so it is on every URL on
the site).

Two independent sources:
- `parts/site/footer.php:103` — hardcoded `'href' => '/about'`
- `bin/demo-content/menus.json:30` — `{ "label": "About", "url": "/about" }`,
  which seeds it into the footer nav menu

There is no About page among the 18 published pages. This is both a broken-link
problem and an entity problem — see the GEO findings on the cost of having no
About page for entity understanding.

**Fix:** either create the About page (a copy decision — the design file is the
only source of truth, so do not author it here), or remove the link from both
`footer.php` and `menus.json`. Do not fix only one; the seeder will reintroduce
it.

**Falsifiable check:** `/about` returns 200, or zero pages contain
`href="/about"`. Re-run `bin/wp lp seed --fresh` and confirm the result holds.

### H2 — Meta description equals the title on 26 URLs

`lp_seo_description()` in `app/includes/seo.php` ends its fallback chain at
`wp_get_document_title()`. Page bodies live in the ACF `page_sections`
Flexible Content field, so `post_content` is empty on template pages and the
chain runs all the way to the end.

Affected — 13 pages:

| URL | description served |
|---|---|
| `/` | (has one, 150 chars — OK) |
| `/classes/` | `Classes \| London Parkour` |
| `/coupons/` | `Coupons \| London Parkour` |
| `/private-coaching/` | `Private 1:1 \| London Parkour` |
| `/workshops/` | `Workshops \| London Parkour` |
| `/docs/` | (87 chars — OK, pulled from FAQ copy) |
| `/contact/` | (76 chars — OK) |
| `/blog/` | `Blog \| London Parkour` |
| `/classes-map/` | `Classes — Map \| London Parkour` |
| `/legal/` | (pulls T&Cs body) |
| `/tutorials-category/` | `Tutorials — Category \| London Parkour` |
| `/tutorials-series/` | `Tutorials — Series \| London Parkour` |
| `/blocks-qa/` | (noindexed — ignore) |

Plus all **13 `lp_series` taxonomy terms** (every term has an empty
`description`), e.g. `/tutorials/series/2019-balancing-series/` serves
`2019 Balancing Series | London Parkour`.

Net: **~20 indexable URLs** with a description that is a duplicate of the title
and carries no information. Google will usually rewrite these, so the practical
cost is a lost chance to control the snippet on the commercial pages
(`/classes/`, `/coupons/`, `/private-coaching/`, `/workshops/`).

**Fix:** two layers.
1. Populate the `seo_description` ACF field (added today) on the ~8 commercial
   and hub pages. This is content entry, not code.
2. Make `lp_seo_description()` fall back to block copy before it falls back to
   the title. `lp_seo_row_text()` already exists and already reads
   eyebrow/heading/body from an ACF row — the chain calls it, but only when
   `$desc` is still empty after the excerpt and `post_content` checks, and it
   only reads the **first** section row. Widen it, and add a term-description
   branch for taxonomy archives.

**Falsifiable check:** no indexable URL has
`meta[name=description] == <title>`. Script it across the sitemap once a
sitemap exists.

### H3 — Raw Markdown syntax leaks into meta descriptions on 20 URLs

10 blog posts and 10 `support` docs store **Markdown** in `post_content`.
`lp_seo_plain()` strips HTML tags but not Markdown, so heading markers and link
syntax reach the `<meta name="description">` verbatim.

Live examples:

```
/docs/youth-class/
  "## Our ethos In today’s society, we believe that our youth need an honest,
   enjoyable, professional and disciplined way of learning how to move. We do not…"

/docs/terms-of-service/
  "## London Parkour Terms of Service ### 1. Terms By accessing the website at
   [https://localhost:8443](https://localhost:8443/), you are agreeing to be…"
```

Affected: `/blog/imperial-college-london/`, `/blog/version-7/`, `/blog/sky/`,
`/blog/the-guardian/`, `/blog/sandringham-flower-show/`, `/blog/the-army/`,
`/blog/stranglers/`, `/blog/definitive-guide-to-army-military-parkour-training/`,
`/blog/parkour-faq/`, `/blog/33x-home-pressup-pushup-variations/`,
`/docs/frequently-asked-questions/`, `/docs/youth-class/`,
`/docs/terms-of-service/`, `/waiver/`, `/docs/pricing/`,
`/docs/personal-training-pt/`, `/docs/hiring-us/`, `/docs/equality-policy/`,
`/docs/contacting-us/`, `/docs/beginners-class/`.

**Fix:** strip Markdown in `lp_seo_plain()` (or a new `lp_seo_demarkdown()`
applied before clipping): ATX headings (`^#{1,6}\s`), link/image syntax
(`[text](url)` → `text`), emphasis markers, list bullets, blockquote `>`, and
code fences. Order matters — de-Markdown before `lp_seo_clip()`, or the clip
will count markup characters against the 155 budget.

**Falsifiable check:** no `<meta name="description">` on the site matches
`/(^|\s)#{1,6}\s|\]\(https?:|(\*\*|__)/`.

### H4 — `/tutorials/` ships 19.8 MB of images on one URL

| page | images | image bytes | over 200 KB |
|---|---|---|---|
| `/tutorials/` | 120 | **19.82 MB** | 57 |
| `/classes/` | 6 | 3.74 MB | 4 |
| `/` | 32 | 3.18 MB | 7 |
| `/blog/` | 9 | 1.47 MB | 2 |
| `/private-coaching/` | 5 | 1.02 MB | 1 |
| `/coaches/` | 4 | 0.90 MB | 2 |
| `/classes/locations/vauxhall/` | 3 | 0.45 MB | 1 |

`/tutorials/` is also 545 KB of HTML. `posts_per_page` is 120 for that archive
(`app/setup/queries.php:199`). Even fully lazy-loaded this is an
extreme page weight, and it is the site's main content hub.

**Fix:** reduce the archive page size (24–36 is normal for a card grid), which
also depends on fixing the pagination in C1. Serve archive thumbnails from
`lp_wide_sm` (640×360) or `lp_thumb_lg` rather than full-size derivatives.

**Falsifiable check:** total image transfer for `/tutorials/` under 1.5 MB and
HTML under 150 KB.

---

## Medium

### M1 — A 2.9 MB unprocessed camera JPEG is the LCP image on two templates

`/classes/` and `/classes-map/` both serve:

```html
<img src="http://localhost:8102/wp-content/themes/londonparkour_v8/bin/demo-media/DSC01072.jpeg"
     alt="" class="absolute inset-0 w-full h-full object-cover"
     decoding="async" data-component="media-photo" fetchpriority="high" />
```

**2,918,849 bytes.** It carries `fetchpriority="high"`, so it is deliberately
the LCP candidate. It is served straight from the theme directory, so it has no
`srcset`, no `sizes`, and no generated derivatives — it bypasses
`media-photo.php`'s whole point.

Root cause: `templates/classes-agenda.php:66-67` and
`templates/classes-map.php:71` do

```php
$lp_mast_media = lp_demo_media_id( 'DSC01072.jpeg' );
$lp_mast_url   = $lp_mast_media ? '' : (string) get_theme_file_uri( 'bin/demo-media/DSC01072.jpeg' );
```

`lp_demo_media_id()` is returning 0 — the demo image is not in the media
library under that name — so both templates take the raw-file fallback.

This is a development fallback that must never reach production. It is also the
reason `/classes/` carries 3.74 MB of images.

**Fix:** decide what the real masthead image for the Classes and Classes-Map
templates is (an editor/ACF choice — these templates have no `page_sections`,
so it likely wants a `seo_image`-style ACF field or a featured image), and make
the raw-file branch fail loudly in production rather than silently serving a
theme file. At minimum, run `bin/wp lp seed` so `lp_demo_media_id()` resolves,
and gate the fallback behind `WP_DEBUG`.

**Falsifiable check:** no page on the site references
`/wp-content/themes/`…`/bin/demo-media/`, and `/classes/` total image bytes
under 800 KB.

### M2 — Every nav and footer link 301-redirects

All internal navigation URLs are stored without a trailing slash, while
canonicals use one. Every one is a 301:

```
/classes          301 -> /classes/
/tutorials        301 -> /tutorials/
/docs             301 -> /docs/
/contact          301 -> /contact/
/blog             301 -> /blog/
/private-coaching 301 -> /private-coaching/
/classes-map      301 -> /classes-map/
/coaches          301 -> /coaches/
/gift-cards       301 -> /docs/gift-cards/
/tutorials/series 301 -> /tutorials/series/
```

The primary menu has 4 such links and the footer menu 12, so **every page on
the site emits ~16 redirecting internal links**. Minor individually; wasteful at
crawl scale and trivially avoidable.

`/gift-cards` is a different case — it redirects across to `/docs/gift-cards/`,
so the footer is advertising a URL that is not the canonical one.

**Fix:** add trailing slashes in `bin/demo-content/menus.json` and any
hardcoded `href` in `parts/site/footer.php` / `parts/site/nav.php`. Point
Gift Cards at `/docs/gift-cards/`.

**Falsifiable check:** zero internal `<a href>` on the site returns a 3xx.

### M3 — Non-home titles are 21–31 characters, under the 30–60 target

The homepage was fixed today (54 chars). Every other page inherits
`Page name | London Parkour`, which is short and drops the descriptive and
local terms:

| URL | title | len |
|---|---|---|
| `/blog/` | `Blog \| London Parkour` | 21 |
| `/docs/` | `Docs \| London Parkour` | 21 |
| `/classes/` | `Classes \| London Parkour` | 24 |
| `/coaches/` | `Coaches \| London Parkour` | 24 |
| `/contact/` | `Contact \| London Parkour` | 24 |
| `/coupons/` | `Coupons \| London Parkour` | 24 |
| `/tutorials/` | `Tutorials \| London Parkour` | 26 |
| `/workshops/` | `Workshops \| London Parkour` | 26 |
| `/private-coaching/` | `Private 1:1 \| London Parkour` | 28 |

These are the commercial pages. `Classes | London Parkour` spends 24 characters
without saying *parkour classes*, *London*, or anything about who the classes
are for.

**Fix:** populate the per-entry `seo_title` ACF field (added today) on these 9
pages. This is the cheapest high-value action in the whole audit and requires
no code and no change to any signed-off on-page copy — `seo_title` is
independent of the H1.

**Falsifiable check:** every indexable page's `<title>` is 30–60 characters and
unique.

### M4 — Empty `alt` on the masthead image of three templates

`alt=""` is correct for decorative images, but these are content photographs
used as page mastheads, and one is the LCP element.

Hardcoded:
- `templates/classes-agenda.php:97` — `'media_alt' => ''`
- `templates/classes-agenda.php:283` — `'media_alt' => ''` (session card thumbs)
- `templates/classes-map.php:101` — `'media_alt' => ''`
- `parts/components/byline.php:119` — `'alt' => ''`

Measured empty-alt counts: `/` 5 of 44 images, `/classes/` 6 of 6,
`/classes/locations/vauxhall/` 3 of 3, `/blog/` 1 of 10.

No image on the site is missing the `alt` **attribute** — the markup is
well-formed. This is about the value being empty.

`blocks/coaches/coaches.php:309` shows the pattern to copy: it falls back to
the coach's name when the attachment has no alt text.

**Fix:** pass the attachment's `_wp_attachment_image_alt` through, with a
sensible fallback (page title / location name / session title), the way
`coaches.php` already does. Note `/classes/` is 6-of-6 empty because its only
images are the demo-media masthead and session thumbs, both hardcoded.

**Falsifiable check:** fewer than 10% of content images site-wide have an empty
alt value, and no LCP image has one.

### M5 — `/docs/frequently-asked-questions/` is byte-identical to `/docs/`

Both URLs return exactly 159,164 bytes, the same `<title>` (`Docs | London
Parkour`), the same H1 (`Questions, answered.`) and the same 739 words.

`/docs/frequently-asked-questions/` does set `<link rel="canonical">` to
`/docs/`, so the duplicate is declared and Google will consolidate. The residual
problems are that the duplicate still consumes crawl budget, and the support
post's own title never appears.

**Fix:** decide which URL is the FAQ. If `/docs/` is the hub, the support post
should either redirect to it or render its own distinct content. The
`seo_noindex` ACF toggle is available if you want to keep the post addressable
but out of the index.

**Falsifiable check:** no two indexable URLs return identical HTML bodies.

### M6 — `localhost:8443` is baked into published content on 6 URLs

The v7 content import carried a dev hostname into `post_content`:

```
/docs/terms-of-service/
/docs/privacy-policy/
/docs/personal-training-pt/
/docs/contacting-us/
/blog/definitive-guide-to-army-military-parkour-training/
/blog/33x-home-pressup-pushup-variations/
```

On `/docs/terms-of-service/` it is in the first sentence and therefore in the
meta description: *"By accessing the website at
[https://localhost:8443](https://localhost:8443/)…"*. Terms of Service pointing
at a dev URL is a legal-copy problem as well as an SEO one.

**Fix:** a content search-and-replace on the production hostname, run as part of
the launch checklist. This is content, not code — `bin/demo-content/` is the
source of truth for seeded content, so fix it there too or it returns on the
next seed.

**Falsifiable check:** `grep -r 'localhost:8443'` over rendered pages and
`bin/demo-content/` returns nothing.

### M7 — `/legal/` and `/docs/terms-of-service/` both serve the Terms

Both return `Terms of service | London Parkour` with the same 150-char
description. `/legal/` is a template page (`templates/legal.php`) that renders
the T&Cs support post.

Unlike M5 these do **not** appear to cross-canonicalise. Two URLs competing for
the same content.

**Fix:** pick one canonical Terms URL and canonical or redirect the other.

**Falsifiable check:** one URL returns the Terms with a self-referential
canonical; the other 301s to it or carries a canonical pointing at it.

---

## Low

### L1 — `lang="en-US"` and `og:locale="en_US"` on a London business

All 16 crawled pages carry `<html lang="en-US">`, and Open Graph declares
`en_US`. WordPress `WPLANG` is empty so `get_locale()` returns `en_US`.

The business is London-only, prices are GBP, and copy uses British spelling.
This does not block anything, but it is a wrong regional signal and it is a
one-setting fix.

**Fix:** set the site language to English (UK) so `get_locale()` returns
`en_GB`. Check nothing in the theme string-matches `en-US`.

**Falsifiable check:** `<html lang="en-GB">` and
`og:locale` `en_GB` site-wide.

### L2 — Two published template stub pages are indexable and empty

`/tutorials-category/` and `/tutorials-series/` are published pages whose only
purpose is to attach a template. Both have 0 words, a title-as-description, and
are **not** noindexed — `lp_seo_is_noindex()` only covers `blocks-qa`,
`booking-error`, `booking-cancelled`, `booking-confirmed`.

Same category: `/sample-page/` (the WordPress default page, still published,
154 words of Lorem-style boilerplate: *"This is an example page. It's different
from a blog post because…"*) and `/clasbpro-theme-preview/` (3 words, contents
are the literal shortcode `[clasbpro_theme_preview]`).

**Fix:** delete `/sample-page/`. Add `clasbpro-theme-preview`,
`tutorials-category`, `tutorials-series` to the noindex slug list in
`lp_seo_is_noindex()`, or set `seo_noindex` on each via the new ACF toggle. The
sitemap findings cover the same ground from the inventory side.

**Falsifiable check:** `/sample-page/` returns 404, and the three stubs return
`noindex` in `<meta name="robots">`.

### L3 — `/classes/locations/` CPT archive 404s but is linked

Linked once in the crawl; returns 404. The three `site` location pages resolve
fine individually. Minor, but it is a broken link and a missing hub — the
location pages have no archive to be listed on. Related to the much larger
`spot` 404 problem in the technical findings.

---

## What works

Worth stating, because these are the things that usually go wrong and here do not:

- **One `<main>` and exactly one `<h1>` on all 16 pages crawled.** No
  `role="heading"` abuse. The landmark contract is enforced by a test suite.
- **No image is missing its `alt` attribute** — 174 unique images, zero absent
  attributes. Only empty values (M4).
- **Heading order is sane** — no skipped levels on the pages checked (e.g.
  homepage `1234442222322333322222`, no `h1 → h3` jump).
- **Canonicals are self-referential and correct**, and the theme strips
  `utm_*`, `mc_*`, `fbclid`, `gclid`, `_ga` from them.
- **404s return a real HTTP 404** with `noindex, nofollow` — no soft 404s found.
- **Server-rendered HTML.** All body copy is in the raw response; nothing
  depends on JS to be indexed.
- **Content is substantial where it matters most**: homepage 1416 words,
  `/waiver/` 1623, `/docs/` 739, `/coupons/` 564, `/private-coaching/` 536.
- **Coach and blog pages have real, unique 150-char descriptions** pulled from
  their excerpts — the description machinery works correctly when
  `post_content` or an excerpt exists. The H2 failure is specifically about
  ACF-block pages.
- **`max-image-preview:large`, `max-snippet:-1`, `max-video-preview:-1`** are
  set — correct for a photo- and video-led site.
- **One JSON-LD block per page** now that Rank Math is removed. No leftover
  plugin markup.
