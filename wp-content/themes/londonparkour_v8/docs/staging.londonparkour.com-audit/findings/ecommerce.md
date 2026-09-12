# Ecommerce (booking, packs, gift cards, schema, indexability)

This is **not** a Google Shopping merchant. It is a local class business with ClasbPro/Stripe packs, drop-in Offers, and a gift-card doc. DataForSEO Merchant / Shopping APIs were not used.

**LIVE V7** still indexes `/bookings/` and `/giftcards/`. **STAGING V8** changes those paths and emits much more schema.

---

## Observed — URL map (staging curl 2026-09-11)

| URL | HTTP | Robots | Notes |
|---|---|---|---|
| `/book/` | **301** → `/classes/` | destination index, follow | Recheck 2026-09-11. WordPress `x-redirect-by`. Closing CTA is a safe hop. |
| `/booking-cancelled/` | 200 | noindex, nofollow | Correct utility |
| `/booking-confirmed/` | 200 | noindex, nofollow | Correct utility |
| `/bookings/` | **301** → `/classes/` | — | Live V7 URL is indexed; V8 hop is in place |
| `/checkout/` | **404** | | No Woo-style checkout |
| `/cart/` | **301** → `/tutorials/cartwheel/` | | Slug collision with a tutorial named cartwheel |
| `/coupons/` | 200 | **index, follow** | Pack shop |
| `/giftcards/` | **301** → `/docs/gift-cards/` | — | Live V7 URL is indexed; V8 hop is in place |
| `/gift-cards/` | 301 → `/docs/gift-cards/` | | |
| `/docs/gift-cards/` | 200 | index, follow | Docs/FAQ, H1 `Questions, answered.` — **no Offer/Product** |
| `/clasbpro-theme-preview/` | 200 | **noindex, nofollow** | Out of page sitemap |
| `/sample-page/` | **404** | noindex, nofollow | Gone |

Theme (`single-clasbpro_class.php`, `archive-clasbpro_class.php`): BOOK opens a **drawer**, not `/book/…` hrefs. Checkout is not a crawlable cart. `/book/` 301s to `/classes/`.

Live V7: `/bookings/` and `/giftcards/` Inspection **PASS / indexed** (last crawl 2026-09-04 and 2026-08-14). Both send `follow, index`. Live JSON-LD on those HTML files: **none parsed**.

---

## Observed — Offer / Event schema (STAGING)

### Class singular (e.g. `/classes/adult-beginners-outdoor/`)

JSON-LD `@graph` includes `Course`, **8× `SportsEvent`** (2026-09-12 → 2026-10-31), each with `offers` (GBP **15**), `EventScheduled`, `OfflineEventAttendanceMode`, `maximumAttendeeCapacity` 20, `remainingAttendeeCapacity` 19, location Place Old Street (`postalCode` EC1Y 1BE). Also FAQPage, VideoObject, SportsClub.

Kids class `/classes/kids-class-west-6-9s/`: same pattern, Course name `Kids Class West (6-9s)`, sessions from 2026-09-13 09:00, Offer £15.

`/classes/` **hub** has SportsClub + places, **no** ItemList of Offers/Events.

### Packs `/coupons/`

Three `Offer` nodes, `InStock`, GBP:

| Name | Price | `@id` |
|---|---:|---|
| Single Class | 15 | `#offer-pack-56654` |
| Five-Pack | 65 | `#offer-pack-56655` |
| Ten Pack | 120 | `#offer-pack-56656` |

`eligibleQuantity` QuantitativeValue unit `class`. Same three Offers also emit on the **homepage**. Visible copy matches (£15 / £65 / £120). Title: `Parkour Class Packs in London | No Contract`. H1: `No contract. Ever.`

Not `Product` + `Offer` (Merchant listings). Fine for a local service; will not create Shopping rich results.

### Gift cards

`/docs/gift-cards/`: WebPage + org graph only. **No Offer, no Product.** H1 is the docs pattern `Questions, answered.` Copy mentions gift cards; nav still points at coupons `FROM £15`. This is an FAQ, not a buy page. Live `/giftcards/` is the indexed commerce URL.

### Global schema defects (also on commerce pages)

- `streetAddress` values mix postcode, station, and **timetable text**, e.g. `SW8 1SS · VAUXHALL TUBE STATION · SUNDAYS 09:00–12:15`. Invalid PostalAddress.
- Org `logo` is a photo (`alfredo-strides.jpg` / class-specific stills), not a mark.
- `inLanguage` on Course nodes was already `en-GB`. Homepage WebPage/`lang` was `en-US` in the 11 Sep capture; **fixed in source** (local 2026-09-12) so WebPage, `lang`, and `og:locale` match Course (`en-GB`).

---

## Observed — indexability of checkout

| Surface | Should be indexed? | Staging fact |
|---|---|---|
| Class product + timetable | Yes | index, follow |
| `/coupons/` packs | Yes (commercial) | index, follow |
| Booking drawer / Stripe payment | **No** | No dedicated indexable checkout URL found |
| `/booking-confirmed/` `/booking-cancelled/` `/booking-error/` | **No** | noindex, nofollow; excluded from post sitemaps in `seo.php` |
| `/book/` | Must not resolve to cancelled | **301 to `/classes/`** — done |
| `/clasbpro-theme-preview/` | **No** | noindex, nofollow; out of sitemap |
| Live `/bookings/` | Historically a booking UI | Still indexed on V7; **301 → `/classes/`** on V8 |

404s are noindex via `lp_seo_is_noindex()`. Theme QA slugs `blocks-qa`, `booking-*`, `clasbpro-theme-preview` are excluded from sitemaps. `sample-page` 404s.

---

## Interpretation

1. V8 schema for **classes and packs is substantially ahead of V7** (V7 bookings/giftcards HTML had no JSON-LD). Course + SportsEvent + Offer £15 is the right type for drop-in sessions.
2. The booking **IA is drawer-based**, so there is no public checkout to noindex. `/book/` 301s to `/classes/`.
3. Gift cards **lost a product URL**. Live `/giftcards/` now 301s to `/docs/gift-cards/`. Docs-only will drop gift-card queries (`parkour gifts` 1 click / 15 impr / pos 11.1 on live `/giftcards/`) unless a buy surface is restored.
4. `/cart/` → `cartwheel` tutorial is a footgun if any plugin or user hits `/cart/`.
5. Homepage emitting the same three pack Offers as `/coupons/` can be OK (same prices) but Google may pick the homepage as the Offer URL (`offer.url` is the canonical of the page they sit on). Prefer `/coupons/` as the Offer URL.

---

## Issues

| Severity | Finding |
|---|---|
| Done | `/book/` 301 → `/classes/` |
| Done | Live `/bookings/` and `/giftcards/` 301 on staging |
| Info | `/sample-page/` 404 `noindex` |
| Info | `/clasbpro-theme-preview/` noindex + out of sitemap |
| High | `streetAddress` includes session times |
| Medium | Gift cards: no Offer/Product, wrong H1 for a commerce intent |
| Medium | `/cart/` 301 to unrelated tutorial |
| Medium | Class hub has no Event/Offer list (singular pages do) |
| OK | Booking confirmed/cancelled noindex |
| OK | Pack Offers £15/65/120 match on-page prices |
| OK | Not a Shopping feed — do not build Merchant Center for three packs |

---

## Recommendations

1. Keep `/bookings/` → `/classes/` and `/giftcards/` → `/docs/gift-cards/`. Do not regress `/book/` to cancelled.
2. Sample Page is 404. Clasbpro preview is already `noindex` and out of the sitemap. Confirm `blocks-qa` stays out.
3. Put `url` on pack Offers as `https://londonparkour.com/coupons/` even when also embedded on the homepage — or only emit packs on `/coupons/`.
4. Gift cards: if still sold, a Product/Offer page with price and availability; if not, 301 and a sentence on `/coupons/`.
5. Clean PostalAddress (`streetAddress` = street only; timetable belongs in SportsEvent `startDate`).
6. Do not index Stripe/ClasbPro query-string checkout if the plugin ever exposes one — add `noindex` if such URLs appear in GSC after launch.
