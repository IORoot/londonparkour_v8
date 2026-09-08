# London Parkour — Prioritised SEO Action Plan

Ordered by leverage per unit of effort, not by severity. Each item has a
falsifiable check — no item is "done" until its check passes.

Effort: **S** < 1h · **M** a few hours · **L** a day or more · **©** copy
decision, needs the design file and owner sign-off

---

## Do first — ordered, because two of these are coupled

### 1. ~~Gate the spot locations *before* adding a sitemap~~ · **DONE 2026-09-08**

Spots are `private`. Check passed: 3 sitemap-eligible locations, not 304.

### 2. ~~Restore the sitemap~~ · **DONE 2026-09-08**

`/wp-sitemap.xml` returns 200; `robots.txt` contains `Sitemap:`; location
sitemap has the 3 sites and no spots. `/sitemap.xml` 301s to `/wp-sitemap.xml`.

### 3. ~~Fix tutorial pagination and reduce `posts_per_page`~~ · **DONE 2026-09-08**

Shipped at 48 per page (13 pages). Two files:

- `app/includes/tutorials.php` — added
  `add_rewrite_rule( '^tutorials/page/([0-9]{1,})/?$', 'index.php?post_type=lp_tutorial&paged=$matches[1]', 'top' )`
  to `lp_tutorials_series_rewrite()`, and bumped the flush flag to
  `lp_tutorials_view_rewrite_v2` so it actually regenerates.
- `app/setup/queries.php:199` — `posts_per_page` 120 → 48.

**Root cause, corrected.** The audit brief attributed this to the singular
*pagination* rule `tutorials/([^/]+)/page/([0-9]+)/`. It was actually the
singular *permalink* rule. Because the CPT registers `pages => true`, WordPress
generates:

```
[149]  tutorials/([^/]+)(?:/([0-9]+))?/?$   => lp_tutorial=$1&page=$2
[195]  tutorials/page/([0-9]{1,})/?$        => post_type=lp_tutorial&paged=$1
```

`/tutorials/page/2/` matched **149** first and resolved to
`lp_tutorial=page&page=2`. No tutorial has the slug `page`, so it 404'd. The
correct rule sat 46 positions too late. Post-fix the archive rules are at index
4 and 5.

**Checks — all passing:**

| check | result |
|---|---|
| `/tutorials/page/1..13/` | all HTTP 200 |
| `/tutorials/page/14/` | 404 (correct: 609 ÷ 48 = 13 pages) |
| `<article>` cards across 13 pages | 609 |
| Unique tutorial URLs crawled | 609, **set-identical** to the 609 DB permalinks |
| Single tutorial permalinks | 200 (3 sampled) |
| `/tutorials/series/`, `/tutorials/category/` | 200 — no regression |
| Pagination control | sliding window correct; no `next` on 13, no `prev` on 1 |
| `php -l`, `acf:build --check`, `npm run build` | pass |

**Measured effect:**

| | before | after |
|---|---|---|
| Reachable tutorials | 123 | **609** |
| HTML per page | 544,411 B | 295,621 B |
| DOM tags | 3,982 | 1,967 |
| Image payload per page | ~19.4 MB | ~8.0 MB |

Two notes for the record. The plan predicted "under 120 KB" — that assumed 24
per page; at 48 the figure is ~296 KB. And the HTML only halved while cards fell
2.5×, which implies roughly **131 KB of fixed per-page chrome** independent of
card count — part of that is item 10. The page is materially lighter but is
**not yet fast**: at 8 MB of images it still needs item 5, which is now the
binding constraint here.

### 4. ~~Stop serving the 2.9 MB DSLR photo~~ · **DONE 2026-09-08**

No `<img>` points at `bin/demo-media/`. `/classes/` LCP is 123 KB with srcset.

### 5. ~~Regenerate tutorial thumbnails~~ · **DONE 2026-09-08**

Ran `wp_update_image_subsizes()` across all 606 unique tutorial featured
images. 603 generated, 3 already had sizes from the probe, 0 failures.
WordPress now has ratio-matched siblings (`lp_wide_sm` 640w, `medium` 300w,
`medium_large` 768w, `large` 1024w, original 1280w), so
`wp_get_attachment_image()` emits a real `srcset`.

Card `src` also switched from `lp_wide` (1280) to `lp_wide_sm` (640) in
`parts/components/video-card.php` — the 4-column board's `sizes` is `25vw`,
so the fallback file should not be the 1280px original. Larger candidates
stay in `srcset` for retina.

**Checks — passing on `/tutorials/`:**

| check | result |
|---|---|
| `<img>` with `srcset` | 48 / 48 |
| `src` under `/lp_wide_sm/` | 48 / 48 |
| `src` payload | 2.00 MB (was 8.0 MB; was ~19.4 MB at 120 cards) |
| `lp_wide_sm` file | ~42 KB vs ~166 KB original |

Four attachments cannot grow a 16:9 `lp_wide_sm`: three SVG posters (vector,
`srcset` does not apply) and one 480×360 JPEG WordPress will not upscale.
Those are 4 of 609 tutorials, none on page 1.

This is a **data** change (attachment metadata + size files on disk). It will
need re-running after `bin/wp lp seed --fresh` unless the seeder starts
calling `wp_update_image_subsizes()`.

### 6. Reveal the 181 hidden transcripts · S
Set `display_transcript` on the 181 tutorials that have transcript data with all
render flags off. Pure data change, no writing.

**Check:** `display_transcript` count goes 199 → 380; spot-check three tutorial
URLs for transcript prose in the HTML.

*~310,000 characters of unique, already-written, topically-ideal indexable text.*

### 7. Resolve the three dead nav/footer links · S (or © if creating)
`/about/`, `/studio/`, `/classes/map/`.

Remove the link, or build the page. `/about` must be fixed in **both**
`parts/site/footer.php:103` and `bin/demo-content/menus.json:30` — patching one
leaves the seeder to reintroduce it.

**Check:** each returns 200, or zero pages contain the `href`. Re-run
`bin/wp lp seed --fresh` and confirm it holds.

### 8. Close the username disclosure · S
Restrict `/wp-json/wp/v2/users`, disable `xmlrpc.php`, and rename the `admin`
account.

**Check:** the users endpoint 401s; `POST /xmlrpc.php` does not return 200.

---

## Next — real wins, slightly more work

### 9. Add page caching · M — the highest-ROI performance fix
TTFB is 329–493 ms **on localhost**, which means real per-request work; the
homepage queries the live timetable uncached. Every other performance item is
capped by this one.

Cache the timetable query in a transient with a short TTL, then add full-page
caching with sensible exclusions for the booking flow.

**Check:** TTFB on localhost under 100 ms for a cached page; the timetable still
reflects a new session within its TTL.

### 10. ~~Stop loading booking assets where nothing can be booked~~ · **DONE 2026-09-08**

Theme dequeue in `app/setup/clasbpro.php` (`lp_clasbpro_needs_booking_assets()`).
Plugin untouched. Assets stay on homepage, `/classes/`, class pages, `/coupons/`,
`/private-coaching/`, `/workshops/`, and pages with a Hero, Pricing, or Classes
block or a clasbpro shortcode.

**Checks — passing:**

| check | result |
|---|---|
| `/contact/`, `/tutorials/`, `/blog/`, `/about/`, `/docs/`, `/classes-map/` | zero `cbfs-*` files; no `#lp-booking-drawer` |
| `/`, `/classes/`, `/coupons/`, `/private-coaching/`, `/workshops/`, a class URL | 5 `cbfs-*` JS files + drawer |
| BOOK on `/classes/` Evening Intermediate Outdoor | drawer loads name / date / seats / coupon form |

### 11. Move `/classes-map/` spots to a JSON endpoint · M
The page renders all 304 spots server-side into 1.07 MB of HTML. Serve them from
a REST endpoint the map fetches on demand. Pairs naturally with item 1.

**Check:** `/classes-map/` HTML under 150 KB; all map pins still render.

### 12. Preload the hero image · S
No `rel="preload"` exists on any page. Add one for the LCP image, after item 4
so the preload points at a reasonably-sized asset.

**Check:** `rel="preload"` present with a matching `imagesrcset`; LCP improves
against the pre-change baseline.

### 13. ~~Populate `seo_description` on the commercial pages~~ · **DONE 2026-09-08**

ACF `seo_title` and `seo_description` filled on the commercial pages, docs,
classes, sites, coaches, blog posts, and series terms. Tutorials archive uses
Site Settings fields (`seo_tutorials_title` / `seo_tutorials_description`).

**Check:** sampled indexable URLs have distinct title and description; none
equal.

### 14. ~~Add `seo_title` to the seven pages whose H1s carry no keywords~~ · **DONE 2026-09-08**

Same pass as item 13. `/classes/` title is now `Parkour Classes in London | Weekly Timetable`; H1 remains `This week's sessions.`

### 15. Add `Article` schema to the 10 blog posts · M
Emit `BlogPosting` with `headline`, `datePublished`, `dateModified`, `author`
and `image`.

**Check:** each blog URL's `@graph` contains a `BlogPosting` node and validates.

### 16. Complete `VideoObject` · S
Add `uploadDate` and `duration` — both required, so the markup cannot currently
earn a video rich result. Also fix Teacher Training's `timeRequired: PT1M`.

**Check:** Rich Results Test passes for video on a class page.

### 17. Add a phone number · S ©
Publish it on `/contact/` and in the footer, and add `telephone` to the
`LocalBusiness` node.

**Check:** `telephone` present in JSON-LD and visible in the rendered footer.

### 18. Alt text for the 6 images on `/classes/` · S ©
**Check:** zero `<img>` with empty `alt` on the page.

### 19. Enlarge the 29 sub-44 px tap targets · S
Coordinate text and "MORE DETAILS" links render at 15–17 px height. Not
independently verified by the coordinator.

**Check:** every interactive element has a ≥44 px hit area at mobile widths.

---

## Then — structural, needs owner decisions

### 20. Split `/classes/` into an agenda and a service page · L ©
The page is a live weekly timetable, which is the wrong page type for "parkour
classes london". The timetable is genuinely good and should stay; it needs a
service page above it that can rank.

### 21. Move pricing to `/pricing/` · M
It currently 301s to `/docs/pricing/`, with more at `/coupons/`. Make
`/pricing/` the canonical destination and redirect the others into it.

### 22. Build the trust pages · L ©
`/about/` (the E-E-A-T anchor and entity home), a safeguarding statement, and
rendered coach bios. Safeguarding is close to a hard requirement for youth sport
in the UK, and the parent persona is the site's worst-performing at 41/100.

### 23. Consider dedicated service pages · L ©
`/classes/beginners/`, `/classes/youth/`, and a hire/organisations page. Both
beginner and youth queries are currently served by `/docs/` wiki articles —
right content, wrong page type, wrong URL depth.

---

## Explicitly not recommended

- **Do not rewrite the H1s or any signed-off design copy.** Use `seo_title` and
  `seo_description`. This repo's governing rule is that class strings and copy
  are design decisions; the ACF SEO layer exists precisely so search needs do
  not require touching them.
- **Do not add `HowTo` schema.** Google deprecated its rich results in September
  2023. It has been removed from the allowlist.
- **Do not "fix" the spot-location 404s into 200s.** They are intentional and
  correctly have zero internal links. Gate them from the sitemap instead.
- **Do not touch the review markup.** All 42 testimonials are real Google
  reviews with verifiable `review_id`s. The "John Doe" reviewer is a genuine
  review, not demo data.
- **`llms.txt` is optional at best.** Google ignores it. The far better AI-search
  investment is item 6 — rendering the transcripts you already have.
- **Do not modify the booking plugin** to fix its global enqueue. Dequeue from
  the theme (item 10); a patched plugin is lost on its next update.
- **Do not treat CLS = 0 as solved.** It holds only because 36 images without
  `width`/`height` happen to sit in absolutely-positioned containers. It is
  incidental, not designed — worth adding the attributes when those components
  are next touched.

---

## Sequencing note

**Items 1 → 8 are the whole ballgame, and every one of them is `S`.** None
requires a copy decision, a design change, or owner sign-off. Together they:

- take the crawlable surface from ~180 URLs to ~666 (items 1–3)
- cut the heaviest page on the site by roughly 5× and its image payload from
  ~19.4 MB to ~1 MB (items 3, 5)
- remove 2.9 MB from the LCP path on two commercial pages (item 4)
- add ~310,000 characters of unique, already-written indexable prose (item 6)
- clear every broken link in global navigation (item 7)

Item 9 (caching) is the one **M** worth pulling forward, because TTFB caps
everything else in the performance category.

Two ordering constraints that are not negotiable:

- **1 before 2.** Adding a sitemap while 301 published locations still 404 hands
  Google 301 soft-404s.
- **4 before 12.** Preloading a 2.9 MB image makes things worse, not better.

A note on measurement: all performance figures here are **localhost lab floors**.
Real-world LCP will be roughly 2.5–5× worse until item 9 lands, so do not read
the 420–644 ms localhost LCP as a pass.
