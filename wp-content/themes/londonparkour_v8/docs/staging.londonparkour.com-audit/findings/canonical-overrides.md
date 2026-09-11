# Canonical / ACF override check — 2026-09-11 (follow-up)

**Question:** is ACF `seo_canonical` filled with a frozen URL (especially `staging.londonparkour.com`) that would survive cutover?

## How the field works

`lp_seo_canonical_url()` in `seo.php` uses `get_field('seo_canonical')` **only if it is a non-empty string**. Otherwise it uses `home_url()` / `get_permalink()` / `wp_get_canonical_url()`. `og:url` and JSON-LD `@id`/`url` call the same helper (plus `home_url('/') . '#organization'`).

An empty field is dynamic. A filled field is a literal URL.

## Staging HTML (source of truth for what Google would see)

Checked **756 URLs** (752 sitemap + booking utilities + `/blocks-qa/`). Head scan 2026-09-11.

| Result | Count |
|---|---:|
| HTTP 200, canonical **equals** request URL on `staging.londonparkour.com` | **631** |
| HTTP 200, canonical on another host (`londonparkour.com`, etc.) | **0** |
| HTTP 200, canonical path ≠ request path | **0** |
| `og:url` ≠ canonical | **0** |
| HTTP 301 (PHP, not ACF) | **3** |
| `/docs/` nginx 403 | **1** |
| Incomplete head fetch (no status parsed) | 121 |

Every **WordPress page** (19, from REST) was re-fetched in full (not a head scan):

| Page | HTTP | Canonical | Notes |
|---|---|---|---|
| `/` `/about/` `/blog/` `/classes/` `/classes-map/` `/contact/` `/coupons/` `/private-coaching/` `/workshops/` `/tutorials-series/` `/tutorials-category/` `/sample-page/` `/clasbpro-theme-preview/` | 200 | self, staging host | ACF field empty or equal to current permalink |
| `/booking-confirmed/` `/booking-cancelled/` `/booking-error/` `/blocks-qa/` | 200 | self, staging | already `noindex, nofollow` via slug list |
| `/legal/` | **301** | — | `lp_docs_redirects()` → `/docs/terms-of-service/`. **Not** an ACF canonical. Earlier audit “200 with canonical to terms” was curl following the redirect. |
| `/docs/` | 403 | none | nginx, still a launch bug |

`/clasbpro-theme-preview/` is still `index, follow` on staging — the theme slug noindex is in git, not deployed yet.

## Local Docker DB (not staging)

`seo_canonical` meta exists on 5 rows (`about`, `clasbpro-theme-preview`, 3 revisions). **All values are empty strings.** `database/backup.sql` matches: the three seeded `seo_canonical` rows are `''`.

Staging REST does not expose ACF (`show_in_rest` is off), so postmeta on Cloudways was not queried. HTML is the production signal.

## Verdict

**No page is emitting a frozen canonical to another URL.** None point at `https://londonparkour.com/…` while the site is on staging. None point at a different path except the three PHP 301s.

That means the ACF field is empty (or set to the current permalink, which behaves the same until someone edits it). Changing **Settings → General → Site Address** (and `WP_HOME` / `WP_SITEURL`) updates canonical, `og:url`, and JSON-LD `@id`s. A Cloudways `search-replace` of the old host in postmeta is still wise in case any absolute URL was saved later.

**Retract:** “rewrite every canonical to londonparkour.com in the theme before launch.” The theme already does that from `home_url()`.
