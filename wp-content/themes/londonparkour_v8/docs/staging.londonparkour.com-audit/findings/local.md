# Local SEO findings — staging.londonparkour.com

**Business type (from page signals):** Hybrid outdoor **service at three physical meeting points** in London (not a storefront gym). Brick-and-mortar checks apply to the three sites; there is no indoor address.

**Vertical:** Sports / fitness instruction. `SportsClub` + `LocalBusiness` is a reasonable type. Per-location `SportsActivityLocation` (or `SportsClub` with unique `@id`) would be more precise.

**Locations (3):** Vauxhall, Old Street, Kilburn Park — dedicated URLs under `/classes/locations/{slug}/`.

**Not assessed (and not invented):** live GBP dashboard, primary category, review velocity, owner responses, geo-grid ranks, Bing Places, Apple Business, Yelp/BBB listings. Schema `aggregateRating` and on-page `g.page` links are reported as **page claims only**.

---

## Local SEO score: 38 / 100

| Dimension | Weight | Score | Evidence |
|---|---|---|---|
| GBP signals | 25 | 8 | `g.page/r/CaEUXmf0e4IHEBM` + `/review` on homepage. No Maps embed of a GBP place, no Place ID in HTML. Category/hours/photos on GBP not visible from the site. |
| Reviews | 20 | 6 | Schema 4.9 / **42**. Homepage hero shows 4.9 **(43)**. Testimonials on-page (includes “JOHN DOE”). No `tel:` review ask. Velocity unknown. |
| On-page local | 20 | 10 | Three location URLs with unique meeting-point copy. Titles include city. H1s are place-name only. No phone. Classes H1 has no city. |
| NAP / citations | 15 | 4 | **NAP conflicts** (below). No `telephone`. Email splits `hello@` vs `contact@`. Cloudflare obfuscation. Tier-1 directories not verified off-site. |
| Local schema | 10 | 5 | Graph present on all pages, but one org node for three sites; `streetAddress` is polluted; no `openingHoursSpecification`; logo is a photo. |
| Local links | 10 | 5 | `sameAs` IG/YT/FB. Press-style blog posts exist historically. No chamber/BBB on-page. |

---

## GBP checklist (what the website shows)

| Signal | Detected? |
|---|---|
| `g.page` profile URL | Yes — `https://g.page/r/CaEUXmf0e4IHEBM` |
| Review deep link | Yes — `…/review` (“SEE ALL” / outline button) |
| Google Maps iframe / Place embed | **No** |
| OSM/Leaflet meeting-point map | Yes — JS-mounted (`data-component="class-detail-osm"`). Not in first HTML. External “OPEN IN MAPS” / Street View links use lat/lng, not a Place ID. |
| Primary / secondary GBP categories | **Not visible** — do not infer |
| GBP posts, Q&A, photos, verified badge | **Not visible** |

One `g.page` ID on the homepage implies a **single** profile, not three. That may be correct (one SAB-style profile covering London) or a gap (three outdoor sites). Cannot tell from the site alone — confirm in GBP before launch.

---

## Review snapshot (page claims only)

| Source | ratingValue | reviewCount |
|---|---|---|
| JSON-LD `aggregateRating` (all sampled pages) | 4.9 | **42** |
| Homepage hero footer (desktop screenshot) | 4.9 ★ | **(43)** |

Do not treat 4.9/42 as live Google review stats. They were not read from GBP. Recency, replies, and 18-day velocity are unknown. On-page quote board is useful socially; one attribution is “JOHN DOE”, which reads as a placeholder and should be removed if it is not a real review.

---

## NAP consistency

**Name:** “London Parkour” — consistent in title, schema `name`, nav.

**Phone:** **Missing.** No `tel:` and no `telephone` in JSON-LD on homepage, contact, classes, or the three location pages.

**Email:**

| Source | Value |
|---|---|
| JSON-LD | `contact@londonparkour.com` |
| Contact page aside (rendered; Playwright) | `hello@londonparkour.com` |
| Curl HTML | Cloudflare `data-cfemail` obfuscation, not a visible mailto |

**Address / hours by location**

| | Visible on location page | Schema `PostalAddress` | Other on-site |
|---|---|---|---|
| **Vauxhall** | Meeting: “Outside Tube station exit 2, next to metal pillars…”. Fact row: `SW8 1SS · VAUXHALL TUBE STATION · SUNDAYS 09:00–12:15"` (trailing `"`). Geo 51.48585, −0.12275 (5 decimals). | `postalCode` SW8 1SS. **`streetAddress` = the whole schedule string including the stray quote.** | Homepage location strip: **`SW8 1SR`** on the flagship card vs **`SW8 1SS`** on the list row. Closing CTA: beginners **Tue/Thu 18:30 Vauxhall** — contradicts Sunday schema and the Vauxhall class board (youth/kids Sundays). |
| **Old Street** | “Outside main tube station exit. By big open area with benches.” Fact: `OLD STREET ST - London EC1Y 1BE`. Geo 51.52586666844253, −0.0879811565357742 (**14 decimals**). | `postalCode` EC1Y 1BE. `streetAddress` “OLD STREET ST - London EC1Y 1BE” (not a street). | Contact page lists postcode only. |
| **Kilburn Park** | “Outside Kilburn Park Tube Station”. Fact: `NW6 5AD · SUNDAYS · 11:00-12:30`. Geo 51.535135, −0.193966. | `postalCode` NW6 5AD. `streetAddress` again mix of postcode + Sunday hours. | Matches the Adult Beginners North 11:00 Sunday row. |

**Contact page visible postcodes:** “Vauxhall — SW8 1SS / Old Street — EC1Y 1BE / Kilburn Park — NW6 5AD” — postcode-only, no street, no `tel:`.

**Hours conflict (do not pick a winner without ops):**

- Contact aside: “Wed, Sat, Sun · 09:00–20:00” (labelled hours, not “office hours”).
- Vauxhall schema/fact: Sundays 09:00–12:15.
- Homepage CTA band: Tue/Thu 18:30 Vauxhall.
- Classes agenda (screenshot): Wed 18:30 Old Street; Saturday beginners on the homepage board.

---

## Local schema status

Emitted as a sitewide `@graph` on every sampled URL, `@id` `https://staging.londonparkour.com/#organization`.

**Present:** `@type` SportsClub + LocalBusiness; `areaServed` City London; `sameAs` three socials; `location[]` of three `Place` nodes with geo; `aggregateRating`; homepage `Offer` packs (£15 / £65 / £120).

**Missing / malformed:**

- No `telephone`.
- No `openingHoursSpecification`.
- No `priceRange`.
- `logo` is a photograph, not a logo file.
- `streetAddress` is not a street — it concatenates postcode, station, and hours, plus a stray `"` on Vauxhall.
- Location pages do **not** get their own LocalBusiness `@id`; they reuse the org graph and list all three Places. No `branchOf`.
- Tutorial/class pages still emit the full local graph (logo swapped to the page image). A vault tutorial should not claim to be the sports club’s logo.
- `inLanguage` / `og:locale`: `en-US`.

Valid JSON-LD parse (no syntax error on homepage). Content of `streetAddress` will fail Google’s PostalAddress expectations even if the JSON is well-formed.

---

## Location page quality

Three pages, subdirectory pattern `/classes/locations/{city}/`. Titles: “Parkour Classes at {Place} \| London”. H1s: “Vauxhall” / “Old Street” / “Kilburn Park” (place only).

Unique content that **passes a swap test:** meeting-point sentences, transport lines, bus numbers, different class boards (Vauxhall youth/kids; Old Street evening + beginners; Kilburn adult Sunday). This is not doorway spam.

Gaps: no `tel:`, map is JS, NAP string is the polluted schedule blob, H1 has no “parkour classes” phrase (title does). OSM map is a geographic signal for users, not a GBP embed.

Store locator: `/classes-map/` exists and is in the sitemap.

---

## Citations (Tier 1)

| Directory | On-page evidence |
|---|---|
| Google | `g.page` link only |
| Facebook | `sameAs` + footer icon `facebook.com/ldnpk` |
| Instagram / YouTube | `sameAs` |
| Yelp / BBB / Bing Places / Apple | **Not evidenced. Not searched off-site in this pass.** |

---

## Top actions

1. **Pick one email and one phone.** Put `tel:` in footer + contact fold. Align schema `email` / `telephone` with the visible strings. Disable CF obfuscation on the public address or also put it in schema (schema already has `contact@` in plaintext).
2. **Rewrite `streetAddress`** to a real thoroughfare or omit it and keep `postalCode` + `addressLocality`. Move hours into `openingHoursSpecification` per Place. Strip the trailing `"` on Vauxhall.
3. Resolve **SW8 1SR vs SW8 1SS** and the **Tue/Thu vs Sunday** Vauxhall timetable before Google (or customers) quotes the wrong one.
4. Give each location page its **own** `SportsClub`/`SportsActivityLocation` `@id`, `branchOf` the org, unique geo already present.
5. Confirm whether GBP is one London profile or three; do not add a second `g.page` without that decision. Embed or at least Place-ID the Maps links.
6. Align reviewCount 42 vs 43 with whatever GBP actually shows — or drop `aggregateRating` until it is sourced.
7. Remove or replace the “JOHN DOE” testimonial.
8. Add city + service to location H1s if design allows (“Parkour classes at Old Street”).

---

## Limitations

No GBP API, no geo-grid, no citation crawl, no review-site scrape. ChatGPT local conversion stats from the skill pack are industry context, not this account. Full AI-search treatment is in `geo.md`.
