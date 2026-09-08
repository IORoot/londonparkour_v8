# London Parkour — Full SEO Audit

**Target:** http://localhost:8102 (local dev)
**Date:** 2026-09-08 (open items only — completed findings removed)
**Method:** 8 specialist audits + coordinator verification. Every remaining
claim below was re-checked with `curl` or WP-CLI against the running site.

Shipped since the original pass (sitemap, tutorial pagination, spot 404s,
tutorial image weight, the 2.9 MB LCP photo, meta descriptions, dead
nav/footer links, hidden transcripts, REST users / xmlrpc, `BlogPosting`,
map HTML weight, `/classes/` alt text, class `VideoObject` `uploadDate` /
`duration`, `seo_title` on pages whose H1s omit “parkour” / “London”,
conditional booking JS, and hero `rel="preload"`) are out of this document.
The original write-up is in `FULL-AUDIT-REPORT-original.md`.

## Remaining score pressure

The original health score was **48 / 100**. The items that drove the
architecture, technical, and on-page floors have shipped. What is still
holding the site back:

| Category | Still open |
|---|---|
| Performance | No page caching; TTFB 329–493 ms on localhost; oversized images |
| Content & E-E-A-T | 229 tutorials have no transcript data at all; no safeguarding statement; coach bios not rendered |
| Local | Real Google reviews and geo data, but no phone number |
| On-page / SXO | `/classes/` is a weekly agenda, not a service page; pricing lives at `/coupons/` and `/docs/pricing/` |
| Schema | Teacher Training `timeRequired: PT1M`; 6 of 42 testimonials have a rating and no body |

**The one-line diagnosis now:** markup, crawl paths, and titles are in decent
shape. The remaining work is **speed, a phone number, and page-type / trust
gaps** — not discoverability.

---

## High

### H7 — No page caching; TTFB 329–493 ms on localhost

On localhost, TTFB should be tens of milliseconds. 329–493 ms means real work on
every request — the homepage queries the live timetable uncached. On a real
connection this is the difference between a passing and failing LCP, and it sets
a floor no amount of asset optimisation can get under.

**All the performance figures in this report are lab floors on localhost.**
Localhost LCP of 420–644 ms translates to roughly **2.5–5 s in the field**
without caching.

---

## Medium

- **Oversized images.** Confirmed separately; several hero assets are served far
  larger than their display size. Detail pending the performance specialist.
- **6 of 42 testimonials have an empty review body** — rating only, no text.
- **Teacher Training declares `timeRequired: PT1M`** — one minute for a
  multi-hour course. Almost certainly a units bug.
- **Page-type mismatch on `/classes/`.** It is a live weekly agenda
  ("This week's sessions."), not a service page — the wrong page type for
  "parkour classes london". Scored 38/100 by the SXO specialist.
- **Pricing lives at two slugs.** `/coupons/` returns 200, and `/pricing/`
  301s to `/docs/pricing/`. Content is good; the commercial slug is the one
  nobody searches.
- **No safeguarding statement, no rendered coach bios** — `/about/` now
  exists, but the parent persona still scored worst of all personas at 41/100,
  and safeguarding is close to a hard requirement for youth sport in the UK.
- **229 tutorials have no transcript data at all.** The 181 that had data but
  were hidden now render. These 229 still have nothing to show.
- **29 tap targets below the 44 px minimum** — coordinate text and "MORE DETAILS"
  links render at 15–17 px height. **[unverified]** — reported by the performance
  specialist; not independently re-measured.
- **36 images lack explicit `width`/`height`.** Currently harmless because they
  sit in absolutely-positioned containers (hence CLS = 0), but it makes the zero
  CLS incidental rather than guaranteed — any future layout change to those
  containers reintroduces shift.

---

## What is genuinely good

Worth stating plainly, because the remaining list undersells it:

- **The JSON-LD is well-architected.** A correct `@graph` with stable `@id`
  cross-references, `SportsClub` + `LocalBusiness` dual-typing, `Course`,
  `FAQPage`, `BreadcrumbList`, `VideoObject` (now with `uploadDate` and
  `duration` on class pages), `BlogPosting`, geo coordinates and opening hours.
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
  `media-photo.php`, and a matching `rel="preload"` now early-discovers the LCP
  photo. `font-display: swap` is active.
- **Mobile layout is correct** — no horizontal overflow on any page tested, and
  both the hamburger nav and the Leaflet map work at mobile widths.

---

## Corrections to specialist findings

Recorded because the raw specialist reports are on file. The coordinator's
figure is the measured one.

**"'John Doe' is placeholder demo data in `aggregateRating`."** No — it is a
real Google review with a valid `review_id` and a `maps/contrib` author URL.
All 42 testimonials carry both. Do not touch the review markup.

The booking-plugin global-enqueue finding was **confirmed**, then **fixed
2026-09-08** in the theme (`app/setup/clasbpro.php`): assets dequeue on pages
with no booking surface. Plugin untouched. An initial coordinator check had
missed it only because it grepped for the wrong asset prefix (`ioroot` rather
than `cbfs`).

---

## Known gate state

`bash bin/audit-reuse.sh` fails on `blocks/hero/hero.php:364` (raw `<button>`).
Verified **pre-existing** — it fails identically on HEAD with this audit's edits
stashed. Not introduced here, but it means the gate is currently red for
everyone.
