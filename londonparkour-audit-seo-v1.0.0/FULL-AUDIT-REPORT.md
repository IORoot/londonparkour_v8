# London Parkour — Full SEO Audit

**Target:** http://localhost:8102 (local dev)
**Date:** 2026-09-08
**Method:** 8 specialist audits + coordinator verification. Every claim below was
re-checked with `curl` or WP-CLI against the running site by the coordinator
before being included. Claims that could not be verified are labelled
**[unverified]**.

## SEO Health Score: 48 / 100

| Category | Score | Weight | Why |
|---|---|---|---|
| Performance | 34 | 15% | No caching; a 2.9 MB LCP image; 19.4 MB of images on one page |
| Architecture & indexability | 35 | 13% | No sitemap; 486 tutorials orphaned; 3 dead nav/footer links |
| Technical | 42 | 17% | No sitemap, pagination 404s, 301 published-but-404 posts, username disclosure |
| Content & E-E-A-T | 48 | 13% | No About page, 229 tutorials with no transcript, 181 more hidden |
| GEO / AI search | 52 | 9% | Good schema base, but the citable text is unrendered |
| On-page | 55 | 13% | Homepage now fixed; 26 URLs still serve title-as-description |
| Local | 58 | 8% | Real Google reviews and geo data, but no phone number |
| Schema | 72 | 12% | Genuinely strong — the best-built part of the site |

**The one-line diagnosis:** this site's *markup* is in better shape than most
production sites — the JSON-LD is well-built and the reviews are real. It is
crippled by **discoverability and weight**, not quality. Roughly 73% of its
content cannot be reached by a crawler, there is no sitemap to compensate, and
the pages that *are* reachable are heavy enough to fail Core Web Vitals on a
real connection.

### The single most valuable line in the codebase

```
app/setup/queries.php:199   $lp_query->set( 'posts_per_page', 120 );
```

This one line sits at the intersection of **two separate criticals** found by two
different specialists who each saw only half of it:

- It is why `/tutorials/` ships **544 KB of HTML and 19.4 MB of images** in a
  single response (C4).
- It is why pages 2–6 exist to be 404'd, orphaning 486 URLs (C2).

Dropping it to 24 shrinks the page ~5× *and* makes the pagination fix meaningful.
Change it in the same commit as C2.

---

## Critical

### C1 — No XML sitemap exists anywhere — **FIXED 2026-09-08**

```
/sitemap.xml        404
/sitemap_index.xml  404
/wp-sitemap.xml     404
```

`robots.txt` returns 200 but contains no `Sitemap:` directive. WordPress core
ships `/wp-sitemap.xml` by default, so something is actively disabling it —
most likely a leftover from the Rank Math removal.

This is critical *because of C2*: with 486 URLs having no internal link path, a
sitemap is currently the only route by which they could be discovered. Right now
neither route exists.

**Shipped.** `/wp-sitemap.xml` returns 200 (`application/xml`), `/sitemap.xml`
301s to it, and `robots.txt` advertises `Sitemap: http://localhost:8102/wp-sitemap.xml`.
The index lists posts + taxonomies, no users provider. Tutorial sitemap contains
609 URLs. WordPress 7.1 was returning 404 on a rendered sitemap; `pre_handle_404`
in `seo.php` forces 200.

### C2 — 486 of 609 tutorials are unreachable (pagination 404s) — **FIXED 2026-09-08**

`/tutorials/` renders 123 tutorial links plus pagination to pages 2–6. Every
paginated URL 404s (verified `/tutorials/page/2/` → 404).

**Root cause** (found by the technical specialist, and it is a satisfying one):
rewrite-rule ordering. The singular tutorial rule
`tutorials/([^/]+)/page/([0-9]+)/` is registered before the archive pagination
rule, so `page` is captured as a tutorial slug and the lookup fails. The
pagination *links* are generated correctly — `app/setup/queries.php:199` sets
`posts_per_page` to 120 and the template reads `max_num_pages` properly. Nothing
is wrong with the query.

**Fix:** register the archive pagination rule with `add_rewrite_rule( ..., 'top' )`
so it is matched first, then flush rewrites. This is a handful of lines, and it
recovers 486 URLs — by far the highest-leverage change on this list.

**Shipped 2026-09-08 at 48 per page.** All 13 archive pages return 200 and the
609 crawled tutorial URLs are set-identical to the 609 published permalinks in
the database. See ACTION-PLAN item 3 for the corrected root cause — it was the
singular *permalink* rule that swallowed the request, not the singular
pagination rule.

### C3 — 301 published posts deliberately return 404 — **FIXED 2026-09-08**

`lp_location` has 304 published posts. Only **3** are indexable:

| kind | count | front-end |
|---|---|---|
| `spot` | 301 | 404 by design (`app/setup/locations.php:140-145`) |
| `site` | 3 | 200, indexable |

The 404 is intentional — spots exist to feed the map, not as pages — and there
are **zero internal links** to them, so today this costs nothing. It is listed
as critical because it is a **primed landmine**: the moment C1 is fixed, any
sitemap generator will enumerate all 304 published locations and hand Google 301
soft-404s. It also currently leaks them via the REST API.

**Fix before fixing C1:** set these to a non-public status, or exclude
`kind=spot` from the sitemap and add `noindex`. Whichever route, C3 must land
*with or before* C1.

**Shipped.** Spots are `private` (`lp_location_spots_private_v1`), not `publish`.
Live counts: 3 published `site`, 301 private `spot`. Location sitemap lists
exactly Old Street, Vauxhall, Kilburn Park. Sample spot pretty URLs 404;
the three sites return 200. A `save_post` hook keeps spots private if someone
hits Publish.

### C4 — `/tutorials/` ships 544 KB of HTML and ~19.4 MB of images — **FIXED 2026-09-08**

Measured, not estimated:

| | measured |
|---|---|
| HTML | 544,411 bytes |
| `<article>` cards | 120 |
| Tags (DOM proxy) | 3,982 |
| `<img>` elements | 120 |
| Avg image weight (40 sampled) | 166 KB |
| **Extrapolated image payload** | **~19.4 MB** |

The image half has a root cause worth stating precisely, because it is *not*
simply "oversized images":

- **74 of the 120 `<img>` have a `sizes` attribute but no `srcset`.** A `sizes`
  attribute without `srcset` is inert — the browser ignores it entirely and
  downloads whatever is in `src`.
- **66 point at a full-size upload for which no intermediate sizes were ever
  generated.** `wp-content/uploads/2024/10/` contains `lyMhhmFrvJU.jpg` and
  nothing else — no `-300x169`, no `-768x432`. These are YouTube-derived
  thumbnails that were sideloaded without WordPress generating its size family.

So there is nothing to *build* a `srcset` from. Each card downloads a full
1280×720 JPEG (212 KB for the one sampled) into a slot that `sizes` says is
25vw. Registering the size families is not enough — the existing attachments
need their thumbnails regenerated.

**Status after pagination + thumbnail regen (2026-09-08):** 48 cards per page,
all 48 `<img>` emit a 5-candidate `srcset` (300w / 640w / 768w / 1024w / 1280w),
and `src` is the 640px `lp_wide_sm` file. Fallback `src` payload is 2.00 MB
(from 8.0 MB after pagination, from ~19.4 MB originally). Browsers honouring
`sizes` (`25vw` on `lg`) will pick 300w or 640w, so the bytes on the wire are
lower still. See ACTION-PLAN item 5.

### C5 — A 2.9 MB raw DSLR photo is the LCP element on two pages

`/classes/` and `/classes-map/` load:

```
http://localhost:8102/wp-content/themes/londonparkour_v8/bin/demo-media/DSC01072.jpeg
served: 2,918,849 bytes
```

Note the path: it is served **out of the theme's `bin/demo-media/` source
directory**, not `wp-content/uploads/`. The seeder passes it as a raw `image_url`
rather than importing it, so it bypasses `wp_get_attachment_image()` entirely —
no `srcset`, no resizing, no WebP, and it is committed to the repository.

For scale: `/classes/` is 152 KB of HTML, so this single image is **19× the
entire rest of the page**.

**Fix:** import via `lp_sideload_image_once()` and store the attachment ID, as
the rest of the seeder already does.

---

## High

### H1 — Three dead links in global navigation and footer

| URL | status | linked from |
|---|---|---|
| `/about/` | 404 | `parts/site/footer.php:103` **and** `bin/demo-content/menus.json:30` |
| `/studio/` | 404 | footer |
| `/classes/map/` | 404 | main navigation |

Because they are global, `/about` alone appears on all ~666 indexable URLs.
`/classes/map/` is the worst of the three commercially — it breaks the entire
"parkour near me" path, which is the highest-intent local query class.

Note `/about` has two sources. Fixing only `footer.php` leaves `menus.json` to
reintroduce it on the next `bin/wp lp seed`.

### H2 — Meta description equals the page title on 26 URLs

`lp_seo_description()` ends its fallback chain at `wp_get_document_title()`.
Page bodies live in the ACF `page_sections` Flexible Content field, so
`post_content` is empty on template pages and the chain runs to the end.

Hit list includes the commercial pages: `/classes/`, `/coupons/`,
`/private-coaching/`, `/workshops/`, plus all 13 `lp_series` taxonomy terms.

Now cheap to fix: the `seo_description` ACF field added today overrides this per
page. ~8 pages of content entry closes the commercial exposure; widening the
code fallback closes the rest.

### H3 — 181 tutorials have transcript text that is never rendered

This is the site's single cheapest content win, and no specialist framed it
correctly, so here are the real numbers:

| | count |
|---|---|
| Published tutorials | 609 |
| Have transcript **data** (`video_transcript_srt` / `_json` / `_chatgpt`) | 380 |
| Have **no** transcript data at all | 229 |
| **Have data but every render flag is off** | **181** |
| Actually render it (`display_transcript`) | 199 |

Average prose transcript is 1,715 characters (~280 words) of unique,
topically-perfect text per tutorial. So ~310,000 characters of ideal indexable
content is sitting in `postmeta`, already written, invisible to crawlers and to
AI answer engines.

**Fix:** flip `display_transcript` on the 181. No writing required. Combined with
C2 this transforms the tutorial library from a liability into the site's
strongest asset.

### H4 — No phone number anywhere on the site

Verified across all six key pages and `/contact/`. The only contact route is a
form plus email, with a stated 36-hour reply time. For a local service business
taking class bookings, this is a direct conversion loss and a missing local
ranking signal.

### H5 — Username disclosure and open `xmlrpc.php`

```
/wp-json/wp/v2/users  →  [{"id":1,"name":"admin", ... "link":".../author/admin/"}]
POST /xmlrpc.php       →  200
```

The admin username is `admin` and it is publicly enumerable. Not strictly SEO,
but it travels with the audit and is a two-line fix.

### H6 — Blog posts emit no `Article` schema

10 published `blog` posts. A representative one
(`/blog/imperial-college-london/`) emits only:

```
['SportsClub','LocalBusiness'], WebSite, WebPage, BreadcrumbList
```

No `Article` / `BlogPosting`, so no author, no `datePublished`, no headline —
the properties that drive both article rich results and AI citation attribution.

### H7 — No page caching; TTFB 329–493 ms on localhost

On localhost, TTFB should be tens of milliseconds. 329–493 ms means real work on
every request — the homepage queries the live timetable uncached. On a real
connection this is the difference between a passing and failing LCP, and it sets
a floor no amount of asset optimisation can get under.

**All the performance figures in this report are lab floors on localhost.**
Localhost LCP of 420–644 ms translates to roughly **2.5–5 s in the field**
without caching.

### H8 — 67 KB of booking JavaScript on every page, including pages that cannot book

Measured on `/contact/`, which has no booking form:

| file | bytes |
|---|---|
| `cbfs-booking.js` | 29,725 |
| `cbfs-appointment-calendar.js` | 15,895 |
| `cbfs-calendar-core.js` | 10,024 |
| `cbfs-class-date-calendar.js` | 9,311 |
| `cbfs-packs.js` | 3,223 |
| **total** | **68,178 (67 KB)** |

Also present on `/tutorials/` and `/blog/`. The plugin enqueues globally so its
AJAX forms work anywhere; the fix is to conditionally dequeue on templates with
no booking surface rather than to modify the plugin.

### H9 — `/classes-map/` generates 1.07 MB of HTML

Measured 1,071,926 bytes, by rendering all 304 location spots server-side into
the document. Those are the same spots covered by C3. Move them to a fetched
JSON endpoint the map consumes on demand.

### H10 — No `rel="preload"` for the hero image on any page

Verified: zero `rel="preload"` on the homepage. `fetchpriority="high"` is
correctly set (one occurrence), which helps once the image is discovered, but a
preload hint would let discovery start earlier.

---

## Medium

- **Oversized images.** Confirmed separately; several hero assets are served far
  larger than their display size. Detail pending the performance specialist.
- **Empty `alt` on all 6 images on `/classes/`.**
- **6 of 42 testimonials have an empty review body** — rating only, no text.
- **`VideoObject` missing `uploadDate` and `duration`** on class pages. Both are
  required for video rich results, so the markup currently cannot earn one.
- **Teacher Training declares `timeRequired: PT1M`** — one minute for a
  multi-hour course. Almost certainly a units bug.
- **H1s contain no instance of "parkour" or "London".** The brand voice is
  signed-off design copy and must not be rewritten here; the `seo_title` field is
  the legitimate bypass, and it is now available.
- **Page-type mismatch on `/classes/`.** It is a live weekly agenda
  ("This week's sessions."), not a service page — the wrong page type for
  "parkour classes london". Scored 38/100 by the SXO specialist.
- **Pricing lives at two slugs.** `/coupons/` returns 200, and `/pricing/`
  301s to `/docs/pricing/`. Content is good; the commercial slug is the one
  nobody searches.
- **No `About`, no safeguarding statement, no rendered coach bios** — the parent
  persona scored worst of all personas at 41/100, and safeguarding is close to a
  hard requirement for youth sport in the UK.
- **29 tap targets below the 44 px minimum** — coordinate text and "MORE DETAILS"
  links render at 15–17 px height. **[unverified]** — reported by the performance
  specialist; not independently re-measured.
- **36 images lack explicit `width`/`height`.** Currently harmless because they
  sit in absolutely-positioned containers (hence CLS = 0), but it makes the zero
  CLS incidental rather than guaranteed — any future layout change to those
  containers reintroduces shift.

---

## What is genuinely good

Worth stating plainly, because the score above undersells it:

- **The JSON-LD is well-architected.** A correct `@graph` with stable `@id`
  cross-references, `SportsClub` + `LocalBusiness` dual-typing, `Course`,
  `FAQPage`, `BreadcrumbList`, `VideoObject`, geo coordinates and opening hours.
- **`aggregateRating` is legitimate.** All 42 testimonials carry a real Google
  `review_id` *and* an `author_profile_url` pointing at `google.com/maps/contrib/`.
  One reviewer's Google display name is literally "John Doe" — a content
  specialist flagged this as placeholder demo data, but it is a real review from
  a reviewer who chose that display name. No fabricated review markup exists.
- **Booking UX on `/classes/` is solid.** Its problem is discoverability, not
  the funnel.
- **Canonicals, clean URLs, and `robots` directives are correct** where present.
- **CLS is 0** on the homepage.
- **`fetchpriority="high"` is correctly applied** to hero images via
  `media-photo.php`, and `font-display: swap` is active.
- **Mobile layout is correct** — no horizontal overflow on any page tested, and
  both the hamburger nav and the Leaflet map work at mobile widths.

---

## Corrections to specialist findings

Recorded because the raw specialist reports are on file and disagree with this
synthesis in three places. In each case the coordinator's figure is the measured
one.

1. **"All 609 tutorials render unpaginated."** No — `/tutorials/` renders **120**
   cards (`posts_per_page = 120`). The 544 KB HTML figure was right; the
   attribution was wrong. Both this and the pagination-404 finding are true and
   share one root cause, which is what produced the `queries.php:199` insight.
2. **"Image size families are correctly registered for srcset."** True as a
   statement about `add_image_size()`, but misleading in effect: 74 of 120
   tutorial images emit no `srcset`, and 66 have no intermediate files on disk to
   populate one. Registration is not the blocker; thumbnail regeneration is.
3. **"'John Doe' is placeholder demo data in `aggregateRating`."** No — it is a
   real Google review with a valid `review_id` and a `maps/contrib` author URL.
   All 42 testimonials carry both. Do not touch the review markup.

The booking-plugin global-enqueue finding, by contrast, is **confirmed** — an
initial coordinator check missed it only because it grepped for the wrong asset
prefix (`ioroot` rather than `cbfs`).

---

## Fixed during this audit

| Item | Where |
|---|---|
| Homepage `title` and `og:image` were empty | ACF `seo_title` / `seo_image`, seeded |
| `SportsActivityLocation` → `SportsClub` | `lp_seo_org_types()` |
| `eventStatus` ternary returned `EventScheduled` on both branches | `seo.php:1351` → `EventSoldOut` |
| `HowTo` offered in the extra-schema allowlist (Google deprecated it in Sept 2023) | removed from `acf-groups.php` + `seo.php` |

The `eventStatus` fix is **latent**: `lp_class_upcoming_sessions()` currently
returns empty for every class (Teacher Training's only session is past-dated), so
no `Event` node is emitted anywhere on the site today. The fix is correct but
unexercised — re-verify once future-dated sessions exist.

## Known gate state

`bash bin/audit-reuse.sh` fails on `blocks/hero/hero.php:364` (raw `<button>`).
Verified **pre-existing** — it fails identically on HEAD with this audit's edits
stashed. Not introduced here, but it means the gate is currently red for
everyone.
