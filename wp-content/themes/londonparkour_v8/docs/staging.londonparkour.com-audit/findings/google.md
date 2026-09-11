# Google (GSC + GA4 + URL Inspection)

**Property:** `sc-domain:londonparkour.com`  
**Country filter:** `gbr` unless labelled worldwide  
**Label:** every number below is **LIVE V7** (`londonparkour.com`). Staging V8 is not in GSC or CrUX.  
**Fetched:** 2026-09-11 via MCP `user-google-search-console` and GA4 Data API property `377511335`.  
**`claude-seo google_auth`:** not configured (`~/.config/claude-seo/google-api.json` missing). PSI/CrUX API key absent.

Search Analytics has a 2–3 day lag. Query-dimension rows omit anonymized traffic — do not sum query rows to get site totals.

The confirmed GSC impressions/CTR/position logging error runs **2025-05-13 to 2026-04-27** (clicks OK, no backfill). The **current 90d window (2026-06-13 → 2026-09-11) is after that window**. The previous 90d (2026-03-14 → 2026-06-12) **overlaps it**, so impression/CTR/position deltas vs previous 90d are not trustworthy.

---

## Observed — 90-day Search Console (extends SHARED 28d)

SHARED already has 28d worldwide web: 251 clicks (−4.6%), 14,093 impressions (−20.7%), CTR 1.8%, avg pos 10.

### Worldwide web, 90d (2026-06-13 → 2026-09-11)

| | Current | Previous 90d | Delta |
|---|---|---|---|
| Clicks | **783** | 673 | **+16.3%** (clicks are reliable) |
| Impressions | 50,545 | 66,991 | −24.5% — **unreliable** (previous period overlaps the logging error) |
| CTR | 1.55% | 1.0% | — |
| Avg position | 9.1 | 7.3 | — |

Top worldwide queries by clicks: `parkour london` (70), `london parkour` (69), `parkour classes london` (9), `parkour in london` (8), `step vault` (7), `london parkour school` (5), `gorilla run` (4), `parkour classes near me` (4), `parkour for seniors near me` (3).

### UK (`gbr`) web, 90d (2026-06-13 → 2026-09-10)

**Site totals (no query dimension):** 545 clicks, 19,352 impressions, **2.8% CTR**, avg pos **8.3**.

UK is ~70% of worldwide clicks in this window (545 / 783).

#### UK top queries (query dimension; rows are incomplete vs totals)

| Query | Clicks | Impr. | CTR | Pos |
|---|---:|---:|---:|---:|
| parkour london | 62 | 825 | 7.5% | 3.8 |
| london parkour | 52 | 167 | 31.1% | 3.2 |
| parkour classes london | 9 | 54 | 16.7% | 5.1 |
| parkour in london | 7 | 35 | 20.0% | 5.5 |
| london parkour school | 5 | 221 | 2.3% | 6.0 |
| parkour classes near me | 4 | 144 | 2.8% | 7.8 |
| parkour for seniors near me | 3 | 68 | 4.4% | 8.4 |
| parkour for kids london | 2 | 7 | 28.6% | 3.7 |
| parkour canary wharf | 2 | 113 | 1.8% | 2.1 |
| parkour course | 2 | 154 | 1.3% | 20.0 |
| parkour gym | 1 | 54 | 1.9% | 12.4 |
| london parkour academy | 1 | 158 | 0.6% | 4.6 |
| parkour (head term) | 1 | 793 | 0.13% | 4.0 |

`london parkour school` / `london parkour academy` / `parkour canary wharf` earn impressions against competitor names. CTR is low — users are looking for those brands, not this one.

#### UK top pages

| Page | Clicks | Impr. | CTR | Pos |
|---|---:|---:|---:|---:|
| `https://londonparkour.com/` | **183** | 5,321 | 3.4% | 9.1 |
| `/pulse/we-ranked-every-country-at-sports-heres-the-winner/` | 4 | 72 | 5.6% | 9.0 |
| `/blog/stranglers/` | 3 | 151 | 2.0% | 7.1 |
| `/blog/definitive-guide-to-army-military-parkour-training/` | 1 | 130 | 0.8% | **1.6** |
| `/classes/` | **1** | **486** | **0.21%** | **52.5** |
| `/tutorials/` | 1 | 395 | 0.25% | 38.1 |
| `/tutorial/deadhang/` | 1 | 156 | 0.64% | 9.2 |
| `/giftcards/` | 1 | 15 | 6.7% | 11.1 |
| `/bookings/` | 0 | 1 | 0 | 83 |

Homepage takes **183 / 545 (34%) of UK clicks**. The commercial timetable `/classes/` is almost unused in search (1 click, position ~page 5–6).

`/tutorial/*` (singular, LIVE path): 5 UK clicks and 688 impressions across the top 30 tutorial URLs in this window. Highest-impression tutorials: `deadhang` (156), `crouch-walk` (144, 0 clicks), `how-to-step-vault-turning` (56).

---

## Observed — URL Inspection (LIVE V7)

All inspected URLs: `PASS`, `Submitted and indexed`, `robotsTxtState: ALLOWED`, crawled as **MOBILE**. Mobile usability verdict is `VERDICT_UNSPECIFIED` (API returned no details).

| URL | Last crawl | Google canonical matches user? |
|---|---|---|
| `https://londonparkour.com/` | 2026-09-11 | Yes |
| `/classes/` | 2026-09-07 | Yes |
| `/classes/outdoor-class-old-street-5/` | 2026-09-08 | Yes |
| `/classes/kids-class-west-6-9s/` | 2026-09-03 | Yes |
| `/tutorial/deadhang/` | 2026-09-07 | Yes |
| `/tutorial/step-vault-technical-details/` | 2026-08-18 | Yes |
| `/tutorials/` (hub, already plural on live) | 2026-09-08 | Yes |
| `/giftcards/` | 2026-08-14 | Yes |
| `/bookings/` | 2026-09-04 | Yes |
| `/blog/definitive-guide-to-army-military-parkour-training/` | 2026-08-20 | Yes |

Homepage referring URLs reported by Inspection: `https://mantaw.com/google-marketing-strategy/`, `http://parkourlabs.com/`, `http://londonparkour.com/`, `https://londonparkour.com/support/equity-policy/`. These are **Inspection citations, not a backlink inventory** — see `backlinks.md`.

---

## Observed — sitemaps (LIVE, unchanged shape from SHARED)

4 submitted sitemaps, **2,035 URLs submitted, 57 indexed (3%)**.

| Sitemap | Submitted | Indexed | Notes |
|---|---:|---:|---|
| `https://londonparkour.com/sitemap_index.xml` | 988 | 19 (2%) | Last downloaded 2026-09-07 |
| `http://londonparkour.com/sitemap_index.xml` | 988 | 19 (2%) | Duplicate http property |
| `https://dev.londonparkour.com/sitemap_index.xml` | 59 | 19 (32%) | Stale **dev** host, last downloaded **2024-12-18**, 1 error |
| `http://www.londonparkour.com/sitemap_index.xml` | 0 | 0 | Last downloaded **2021-09-19**, 1 error |

Stale properties still submitted: `dev.londonparkour.com`, `http://londonparkour.com`, `http://www.londonparkour.com`.

---

## Observed — GA4 property `377511335` (LIVE V7, 90d → yesterday)

Staging and live share this property. Hostnames in the window: **`londonparkour.com` 10,594 sessions**, `localhost` 4. **No `staging.londonparkour.com` rows.** Treat all GA4 figures as live V7.

| | 90d |
|---|---|
| Sessions | 10,596 |
| Users | 9,814 |
| Pageviews | 13,934 |
| Engagement rate | 25.3% |

Channel group (sessions): Direct 8,633 · **Organic Search 1,101** (745 users, 60.4% engagement) · Paid Search 299 · Organic Shopping 236 · **AI Assistant 227** · Display 225 · Organic Social 51 · Referral 13.

Organic Search 1,101 sessions vs GSC worldwide 783 clicks: different definitions (GA4 sessions can include more than one landing; GSC is web search clicks; neither should be forced equal).

Top **Organic Search** landing pages (sessions): `/` 451 · `/classes/` 68 · `(not set)` 66 · `/pulse/we-ranked-every-country-at-sports-heres-the-winner/` 32 · `/tutorials/` 30 · army-military blog 24 · `/map/` 24 · `/tutorial/how-to-land-silently/` 24 · `/tutorial/step-vault-01/` 17.

Key ecommerce events `purchase` / `generate_lead` / `begin_checkout` / `select_item` / `view_item`: **no rows**. V7 live HTML does not fire V8 `purchase`. Do not treat this as “zero bookings”.

---

## CrUX / PageSpeed Insights

**Missing API key.** `claude-seo google_auth` is not configured.

- CrUX API (`chromeuxreport.googleapis.com`) → **403** `Method doesn't allow unregistered callers`.
- PSI v5 without a key → **429** quota 0 on anonymous project `583797351490`.

No LIVE field LCP/INP/CLS for this audit. Do not infer CWV from GSC. Lab numbers for staging are in `performance.md`.

---

## Interpretation

1. Search demand is **brand + local class intent**, not the 609-tutorial library. Homepage captures most UK clicks; `/classes/` does not.
2. `london parkour school` / `academy` / `canary wharf` impressions are **competitor-brand leakage**, not proof LP ranks as those businesses.
3. Indexation of the live sitemap (19 of 988) is a **live V7 fact**. Staging’s 752-URL `wp-sitemap.xml` is not in GSC yet. Launch will re-open coverage, especially the `/tutorial/` → `/tutorials/` move.
4. `/bookings/` and `/giftcards/` are indexed on live. Staging 301s them to `/classes/` and `/docs/gift-cards/` (`ecommerce.md`, `cluster.md`).
5. GA4 “AI Assistant” (227 sessions) is a real channel on this property; it is not GSC AI Overviews.

---

## Issues

| Severity | Finding | Evidence |
|---|---|---|
| Launch blocker | `/tutorial/` (indexed) vs staging `/tutorials/`; class slugs also changed | Inspection PASS on live singular URLs; staging sitemap uses plural |
| High | `/classes/` is indexed but commercially invisible in UK search | 1 click / 486 impr / pos 52.5 |
| High | Stale GSC sitemaps (`dev`, `http://`, `www`) still submitted | MCP `list_sitemaps` |
| Medium | Army/military blog is a top indexed URL at pos ~1.6 | Conflicts with brand “not military” — see `sxo.md` |
| Medium | No CrUX/PSI field data this round | Missing API key |
| Info | Do not compare 90d impressions to previous 90d | Logging-error overlap |

---

## Recommendations

1. Before go-live: 301 map every indexed `/tutorial/{slug}/` to `/tutorials/{slug}/`, and every live class slug that changed (Inspection shows `outdoor-class-old-street-5` still canonical).
2. After launch, submit **one** `https://londonparkour.com/wp-sitemap.xml` (or index) and remove `dev` / `http` / `www` sitemaps in GSC.
3. Do not import staging rankings. Re-inspect homepage, `/classes/`, and a tutorial on the live host after DNS cutover.
4. Optional: add a PSI/CrUX API key to `claude-seo google_auth` if field CWV is required before launch.
