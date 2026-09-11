# Schema / JSON-LD findings — staging.londonparkour.com

**Scope:** Pre-launch V8. JSON-LD is in the first HTML (good; December 2025 JS-SEO guidance). `@id` / `url` values follow `home_url()` — they will become `https://londonparkour.com/` when Site Address does. Not a theme rewrite.

**Fetched:** 2026-09-11 curl + HTTP basic auth. Pages: `/`, `/classes/adult-beginners-outdoor/`, `/classes/locations/vauxhall/`, `/tutorials/deadhang/`, `/coaches/andy-pearson/`, plus `/classes/kids-class-west-6-9s/`, `/contact/`, `/blog/parkour-faq/` for type coverage.

**Also used:** `SHARED.md`, `local.md`, `ecommerce.md`, `geo.md`. Deprecated-types reference: FAQ rich results retired **7 May 2026**; HowTo rich results retired **September 2023**.

Do **not** recommend HowTo. Do **not** recommend a new FAQPage for Google SERP.

No Microdata (`itemscope` = 0 on sampled pages). No RDFa. Format is JSON-LD `@graph` only.

---

## Schema score: 44/100

Valid parse on every sampled page (no trailing-comma / `@context` failures). Types used are mostly **active**. The graph’s *content* fails PostalAddress, Organization.logo, language, review, and production-host rules.

| Page | Types (excerpt) | Status | Issues |
|---|---|---|---|
| `/` | SportsClub+LocalBusiness, WebSite, WebPage, Offer ×3, Place ×3 | ⚠️ | Staging `@id`. `logo` = photo `alfredo-strides.jpg`. `streetAddress` polluted. `inLanguage` `en-US`. `aggregateRating` 4.9/42. No `telephone`. SearchAction `?s={search_term_string}`. |
| Adult class | + Course, SportsEvent ×8, Offer, VideoObject, FAQPage, BreadcrumbList | ⚠️ | Same org pollution. Course `inLanguage` `en-GB` vs WebPage `en-US`. No `hasCourseInstance`. Event capacity 20 / remaining 19 vs on-page “capped at twelve”. FAQPage **Info**. VideoObject missing `duration`. |
| Kids class | same pattern as adult | ⚠️ | `logo` swapped to `zak-traverse.jpg`. Events inherit Vauxhall polluted `streetAddress`. FAQPage **Info**. |
| Location Vauxhall | SportsClub+LocalBusiness (sitewide), WebPage, BreadcrumbList | ❌ | **Not** a per-location LocalBusiness/`SportsActivityLocation`. `logo` = `location_Vauxhall-Large.jpeg` (photo) on the **organization** node. Still lists all three Places. |
| Tutorial deadhang | SportsClub+LocalBusiness, WebPage, BreadcrumbList, VideoObject | ❌ | Vault/deadhang URL asserts the sports club. `logo` = tutorial still `Tutorial-swinging-lache-1-deadhang_16_9.jpg`. VideoObject otherwise usable (`duration` PT179S, YouTube embed). No HowTo (correct). |
| Coach Andy | SportsClub+LocalBusiness, WebPage, BreadcrumbList | ❌ | **No `Person` / `ProfilePage`.** `logo` = `profile-upscaled2.png`. Strong on-page quals, none in schema. |
| Contact | + FAQPage | ⚠️ Info | Real questions in `mainEntity`. FAQ rich results retired May 2026. |
| Blog FAQ | + BlogPosting, Person (author only) | ⚠️ | Author `Person` name only (no `@id` / url to `/coaches/andy-pearson/`). Org `logo` = `parkour_faq.jpg`. Not FAQPage (fine). |

---

## Detection

Every sampled URL emits one `<script type="application/ld+json">` `@graph`.

Sitewide node (homepage, matches `SHARED.md` / `local.md`):

```json
"@type": ["SportsClub", "LocalBusiness"],
"@id": "https://staging.londonparkour.com/#organization",
"name": "London Parkour",
"url": "https://staging.londonparkour.com/",
"email": "contact@londonparkour.com",
"aggregateRating": { "ratingValue": 4.9, "reviewCount": 42 },
"logo": "https://staging.londonparkour.com/wp-content/uploads/2026/08/lp_wide_lg/alfredo-strides.jpg"
```

`SportsClub` + `LocalBusiness` is a reasonable type for outdoor classes (`local.md`). `sameAs`: Instagram `london_parkour`, YouTube `@londonparkour`, Facebook `ldnpk`. `areaServed` City London. `addressCountry` GB.

**Zero** `@id` / `url` values on sampled graphs use `londonparkour.com` without `staging.`. Counts on homepage: 21 staging identifiers, 0 production.

---

## Validation by issue

### 1. `streetAddress` pollution — High

Observed values (all sampled pages; same three Place nodes):

| Place | `postalCode` | `streetAddress` (verbatim) |
|---|---|---|
| Vauxhall | SW8 1SS | `SW8 1SS · VAUXHALL TUBE STATION · SUNDAYS 09:00–12:15"` |
| Old Street | EC1Y 1BE | `OLD STREET ST - London EC1Y 1BE` |
| Kilburn Park | NW6 5AD | `NW6 5AD · SUNDAYS · 11:00-12:30` |

Vauxhall includes a stray `"`. Hours and station names are not a street. Google PostalAddress expects a thoroughfare (or omit `streetAddress` and keep `postalCode` + `addressLocality`). Matches `SHARED.md` and `local.md`.

SportsEvent on the adult class repeats Old Street’s polluted address eight times. Kids events repeat the Vauxhall blob.

### 2. `logo` is a photo — High

Google Organization `logo` is a mark, not a hero photograph.

| Page | `logo` file |
|---|---|
| Homepage / contact / adult class | `alfredo-strides.jpg` |
| Location Vauxhall | `location_Vauxhall-Large.jpeg` **on the org node** |
| Tutorial deadhang | `Tutorial-swinging-lache-1-deadhang_16_9.jpg` **on the org node** |
| Coach | `profile-upscaled2.png` **on the org node** |
| Blog FAQ | `parkour_faq.jpg` **on the org node** |

The generator copies the page image onto `SportsClub.logo`. A deadhang still image must not be the business logo. `image` may be the page photo; `logo` must be the identity mark and stable across URLs.

### 3. `inLanguage: en-US` — Medium

UK operator, `Europe/London` in `/wp-json/`. WebSite/WebPage `inLanguage` is `en-US`; `og:locale` `en_US`; `<html lang="en-US">`. Adult `Course` is `en-GB` — the only GB language tag found — so the graph disagrees with itself. Use `en-GB` (or `en`) consistently. Matches `SHARED.md`.

### 4. `aggregateRating` 4.9 / 42 — High (accuracy)

Present on the org node on **every** sampled URL, including tutorials and sample-page.

- Schema: `ratingValue` 4.9, `reviewCount` **42**, `bestRating` 5, `worstRating` 1.
- Homepage hero: 4.9 ★ **(43)** (`local.md`, `visual.md`).

Do not invent a GBP count. Either source the number from the live review profile and keep it in sync, or drop `aggregateRating` until it is. Self-serving review markup with a mismatched count is a trust defect. These are page claims, not a GBP API read.

### 5. Staging host in `@id` / `url` — Critical at cutover

All `@id`s are `https://staging.londonparkour.com/…`. After DNS cutover, Google should see `https://londonparkour.com/#organization` and page `@id`s on that host. Leaving staging identifiers in production HTML conflicts with canonicals and with any live V7 graph.

### 6. FAQPage — Info (not Critical)

Present on adult class, kids class, and contact.

Class `Question.name` values are **labels**, not questions: “Beginners Welcome”, “Class Contents”, “Weather”, “Age limits”, “Clothing”, “Water”, “Prejudice”, “Lateness”, “Non-Competitive”, “Outdoors”, “Secure”, “Roots.”, “Community.”

Contact questions *are* questions: “Do I need any experience?”, “How much is a first class?” (answer on page: first class is £15 for 90 minutes).

**FAQ rich results retired 7 May 2026 for all sites.** Flag as Info. Do not add more FAQPage for Google. Do not rip out accurate Q&A HTML. Do not use FAQPage on `/blog/parkour-faq/` to chase stars.

### 7. HowTo — not present (do not add)

Tutorials have `VideoObject` only. **Do not recommend HowTo** (rich results removed September 2023). Visible step headings in HTML are a content issue (`content.md`), not a schema opportunity.

### 8. Course / SportsEvent / Offer — useful, with data bugs

Adult Course: name `Adult Beginners East`, `offers` £15 GBP, `provider` org, `timeRequired` present, **no** `hasCourseInstance`. `Course` rich cards still exist; Course *Info carousel* does not (June 2025). Missing `hasCourseInstance` is a completeness gap, not a retired type.

SportsEvent ×8: `EventScheduled`, `OfflineEventAttendanceMode`, `startDate`/`endDate` with `+01:00`, `maximumAttendeeCapacity` 20, `remainingAttendeeCapacity` 19. `/classes/` copy says sessions are “capped at twelve”. Schema vs page conflict (`ecommerce.md` noted capacity 20).

Offers on homepage: Ten Pack £120, Five-Pack £65 (third pack also present in graph). `availability` InStock, currency GBP.

### 9. VideoObject — tutorials better than class

Deadhang: `name` Deadhang, `duration` PT179S, `uploadDate` 2021-12-20, `embedUrl` YouTube `YHpCqey3_3w`, `thumbnailUrl` the tutorial still, description = the on-page lede. Usable for Video rich results.

Adult class VideoObject: YouTube `8UEP-8yX7E0`, `uploadDate` 2026-08-10, **no `duration`**, thumbnail is `alfredo-strides.jpg`.

### 10. Person / ProfilePage missing on the coach URL

`/coaches/andy-pearson/` has no `Person` node. BlogPosting author is `Person` `name: Andy Pearson`, `jobTitle: HEAD COACH`, `worksFor` org — **no** `url` to the coach permalink. That is the highest-value missing type: ProfilePage + Person with `url`, `jobTitle`, `worksFor`, `sameAs` if they exist.

### 11. Location pages are not location entities

Vauxhall URL reuses `https://staging.londonparkour.com/#organization` and lists all three Places. There is a Place `@id` `…/vauxhall/#place` but the page is not `about` that Place as a LocalBusiness/`SportsActivityLocation` with `branchOf`. Confirmed `local.md`.

### 12. SearchAction

`urlTemplate`: `https://staging.londonparkour.com/?s={search_term_string}`. Sitelinks search box needs a working on-site search and a production host. Staging URL must not ship.

---

## What not to do

- Do not add **HowTo** for tutorials or classes.
- Do not add a **new FAQPage** to chase Google FAQ rich results (retired May 2026). Existing FAQPage = Info.
- Do not keep staging host identifiers on live — change Site Address / `WP_HOME`, do not hard-code the production host in the theme.
- Do not put `aggregateRating` on tutorial/sample URLs if the rating is the club’s Google reviews — and do not publish 42 if the page shows 43.
- Do not map `#45523E`-style design tokens here; this file is schema only.

---

## Recommendations (production identifiers = londonparkour.com)

1. **Confirm Site Address at cutover** so `home_url()` emits `https://londonparkour.com/…` on every `@id`, `url`, SearchAction template, and Place `@id`. Keep the same path suffixes (`#organization`, `/classes/locations/vauxhall/#place`). Do not hard-code the host in the theme.
2. **Fix `streetAddress`:** real thoroughfare or omit; move hours to `openingHoursSpecification` on each Place. Strip the Vauxhall trailing `"`.
3. **Stable logo file** (the mark) on Organization. Page photos stay on `image` / `primaryImageOfPage` / VideoObject `thumbnailUrl` only.
4. **`inLanguage` / `lang` / `og:locale` → `en-GB`.** Align Course and WebPage.
5. **Source or remove `aggregateRating`.** One count, only on the org (and maybe homepage), not on every tutorial.
6. **Coach URL:** `ProfilePage` + `Person` with `url` `https://londonparkour.com/coaches/andy-pearson/`, `jobTitle`, `worksFor`. Point BlogPosting `author` at that `@id`.
7. **Location URL:** `SportsActivityLocation` or `SportsClub` with its own `@id`, `branchOf` the org, `geo` already present.
8. Align Event `maximumAttendeeCapacity` with the on-page cap (twelve vs 20).
9. Leave FAQPage as Info; optionally rename class FAQ `name`s to actual questions for non-Google consumers — not for SERP stars.
10. Add `duration` on class VideoObject. Optional: `hasCourseInstance` on Course pointing at the SportsEvents.

---

## Limitations

Rich Results Test was not run (auth wall). Counts and strings are from parsed JSON-LD, not the Google validator. Review numbers were not read from GBP. No generated-schema.json shipped — production snippets must use `londonparkour.com` and verified NAP, not staging.
