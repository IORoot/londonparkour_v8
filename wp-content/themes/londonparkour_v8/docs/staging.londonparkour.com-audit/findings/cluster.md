# Cluster (hub-and-spoke, 609 tutorials)

**Scope:** staging sitemap `sitemap-urls.txt` (752 unique URLs, 2026-09-11) + LIVE GSC UK 90d queries/pages.  
Staging is **not ranked**. Cluster architecture is a launch design problem; traffic numbers are **LIVE V7**.

---

## Observed — sitemap architecture (STAGING V8)

| Bucket | Count | URL pattern |
|---|---:|---|
| Tutorial spokes | **609** | `/tutorials/{slug}/` |
| Tutorial category **terms** | 55 | `/tutorial-category/{slug}/` (singular) |
| Tutorial category **hub** | 1 | `/tutorials-category/` plus rewrite `/tutorials/category/` |
| Series **terms** | 13 | `/series/{slug}/` |
| Series **hub** | 1 | `/tutorials-series/` plus rewrite `/tutorials/series/` |
| Tutorial archive hub | 1 | `/tutorials/` |
| Classes hub + 6 products + 3 locations | 10 | `/classes/…` |
| Docs | 15 | `/docs/…` including `/docs/gift-cards/` |
| Blog posts | 10 | `/blog/…` |
| Other | rest | coaches, tags, sample-page, clasbpro preview |

Nav copy on staging gift-docs HTML: **“By category 11 CATEGORIES · By series 13 SERIES · By tutorial 609 VIDEOS”**. The 11 are parent movement families; the sitemap still lists **55** term archives (parents + children).

Parent-level category slugs (from sitemap): balancing, climbing, crawling, flowing, jumping, passing, rolling, spinning, strengthening, swinging, vaulting, mounting. Child examples: `step-vault`, `speed-vault`, `qm-basics`, `precisions`, `lache`.

Series terms: `2025-tutorial-library` (nav claims 198 episodes), `2024-precisions`, `2022-step-vaults`, `2022-qm`, plus 2019–2020 named series.

Fetched (staging, 200):

- `/tutorials/` — title `Parkour Tutorials | London Parkour`, H1 `Tutorials.`
- `/tutorials/category/` and `/tutorials-category/` — both 200, title `Parkour Tutorial Categories`
- `/tutorial-category/vaulting/` — 200, H1 `Tutorial Category Archives` (generic WP archive phrasing)
- `/series/2022-step-vaults/` — 200, H1 `2022 Step Vault Series.`
- `/tutorials/vaults/` — **404** (not a spoke slug; vaulting lives under `/tutorial-category/vaulting/`)

`/tutorials-category/` HTML is **1.48 MB / 391 images** (see `performance.md`). That is a hub that inlines the spokes.

---

## Observed — LIVE path vs staging path

| Role | LIVE V7 (indexed) | STAGING V8 |
|---|---|---|
| Tutorial spoke | `/tutorial/{slug}/` | `/tutorials/{slug}/` |
| Tutorial hub | `/tutorials/` (already plural; 2.67 MB HTML) | `/tutorials/` (323 KB) |
| Class example | `/classes/outdoor-class-old-street-5/` | `/classes/adult-beginners-outdoor/` |
| Kids class | `/classes/kids-class-west-6-9s/` (same slug) | `/classes/kids-class-west-6-9s/` |
| Teens class | `/classes/teens-class-west-10-14s/` | `/classes/youth-class-west-10-14s/` |
| Gift cards | `/giftcards/` | `/docs/gift-cards/` (`/gift-cards/` 301s here; `/giftcards/` 404) |
| Bookings | `/bookings/` | no `/bookings/` (404); `/book/` 301s to cancelled |

GSC URL Inspection: live `/tutorial/deadhang/` and `/tutorial/step-vault-technical-details/` are **Submitted and indexed**. Staging spoke `https://staging.londonparkour.com/tutorials/deadhang/` is 200 with VideoObject JSON-LD.

LIVE UK 90d tutorial **pages** (top 30 `/tutorial/`): **5 clicks, 688 impressions**. Highest impression spokes: `deadhang` (156), `crouch-walk` (144, 0 clicks), `how-to-step-vault-turning` (56), `basic-jump-roll` (51).

LIVE UK 90d queries that landed on `/tutorial/` (page filter): `step vault` (88 impr, 1 click, pos 6.4), `cat leap` (75 impr, 0 clicks, pos 11.8), `cat pass` / `cat pass parkour`, `dead hang tutorial`, `how to land quietly` (pos 1).

LIVE `/tutorials/` hub: 1 UK click, 395 impressions, pos **38.1**.

---

## Hub-spoke design (as built, not as ranked)

```
/tutorials/                          ← pillar (609-item board)
   ├─ /tutorials/series/  ==  /tutorials-series/     ← duplicate hub URLs
   │     └─ /series/{year-topic}/                    ← 13 series spokes
   ├─ /tutorials/category/  ==  /tutorials-category/ ← duplicate hub URLs
   │     └─ /tutorial-category/{term}/               ← 55 term spokes
   └─ /tutorials/{slug}/                             ← 609 lesson spokes
```

Intended internal links (theme helpers `lp_tutorials_series_url` / `lp_tutorials_category_url`): spoke → series/category boards via `/tutorials/series/` and `/tutorials/category/`. Sitemap still advertises the **page permalinks** `/tutorials-series/` and `/tutorials-category/`. Both resolve 200 — two canonicals for the same view unless one 301s (curl of both returned 200, not 301).

Commercial cluster (separate from tutorials):

```
/classes/                            ← timetable hub (LIVE GSC pos 52.5)
   ├─ /classes/{product}/            ← 6 class URLs
   └─ /classes/locations/{site}/     ← Vauxhall, Old Street, Kilburn Park
```

---

## Interpretation

1. **609 spokes, almost no search traffic.** LIVE UK 90d: homepage 183 clicks vs five tutorial clicks. The library is a publisher asset, not the organic acquisition engine.
2. **Path change `/tutorial/` → `/tutorials/` is a launch blocker.** Live Inspection PASS on the singular path. Without a 1:1 301, Google will recrawl 600+ URLs as new and drop the few that do rank (`deadhang`, step-vault cluster, silent landing).
3. **Duplicate hubs.** `/tutorials-category/` vs `/tutorials/category/` (and the series pair) split crawl signals. Pick one canonical and 301 the other. Sitemap currently lists the hyphenated page slugs; in-template CTAs use the nested `/tutorials/category/` form.
4. **Category term H1** `Tutorial Category Archives` is a generic archive, not a pillar title (`Vaulting` is in the `<title>` only). Weak spoke-to-hub semantics.
5. **Cannibalization risk inside the library:** `step vault` already has multiple LIVE URLs (`step-vault-technical-details`, `how-to-step-vault-turning`, plus series `2022-step-vaults` / `2020-step-vaults`). Staging keeps that split. SERP for `step vault parkour tutorial` already shows two LP URLs in the top results — overlap 7+ would argue for one canonical lesson + series hub, not competing spokes.
6. **Do not build new tutorial spokes to chase GSC.** The gap is redirects, canonical hubs, and linking the library **into** `/classes/` for commercial queries — not another 50 videos.

---

## Issues

| Severity | Finding |
|---|---|
| Launch blocker | `/tutorial/{slug}/` → `/tutorials/{slug}/` 301 map for all indexed spokes |
| Launch blocker | Class slug changes (`outdoor-class-old-street-5` → `adult-beginners-outdoor`; `teens-class-west-10-14s` → `youth-class-west-10-14s`). `kids-class-west-6-9s` is stable. |
| High | Two 200 OK hubs for categories and two for series |
| High | `/tutorials-category/` 1.48 MB / 391 images |
| Medium | Generic H1 on category terms |
| Medium | Step-vault / cat-leap / cat-pass keyword cannibalization across many spokes |
| Info | 609-video board is correctly a hub; GSC shows it does not need to be the money page |

---

## Recommendations

1. Ship a redirect spreadsheet: every live `/tutorial/*` 200 → staging `/tutorials/{same-slug}/` (confirm slug equality per URL; do not assume).
2. Canonical: keep `/tutorials/` as the pillar; 301 `/tutorials-category/` → `/tutorials/category/` (or the reverse) and the series pair the same way. Sitemap should list only the winner.
3. Rewrite category term H1 to the movement name (`Vaulting`), and link every spoke to its parent category + series + the classes timetable where the move is taught.
4. For `step vault` (88 UK impressions on tutorial URLs): pick **one** canonical lesson, point series hubs at it, 301 or rel-canonical near-duplicates.
5. Do not noindex the library. It earns specialist queries (`how to land quietly` pos 1). Just stop treating it as 609 competing landing pages.
