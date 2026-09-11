# SXO (page-type mismatch)

SXO question: does the page **type** match what Google already ranks for the query? Technical scores do not fix a gym query landing on an outdoor timetable.

Traffic and SERP evidence is **LIVE V7**. Page markup cited for V8 is **staging**. Brand rule from `AGENTS.md`: professional, skill-focused; **not ninja, not military**.

---

## 1. Outdoor classes vs “parkour gym”

**Query (LIVE UK 90d GSC):** `parkour gym` — 54 impressions, 1 click, pos **12.4**. Related: `indoor parkour gym` / `indoor parkour gym near me` / `london parkour gym` / `free running gym` (low volume, positions ~6–14). Combined gym-stem: 2 clicks, 114 impressions.

**SERP consensus (WebSearch, 2026-09-11):** dominant type is **indoor facility / gym landing** — Parkour Generations “London Parkour Gym” (Republic, E14), memberships, open gym hours, showers. Secondary: London Parkour Project indoor adult timetable. Local/facility pages, not outdoor park listings.

**Target page type (STAGING):** `/classes/` is a **weekly outdoor timetable** (title `Parkour Classes in London | Weekly Timetable`, H1 `This week's sessions.`, meta: coach-led, cap 12, £15 drop-in). JSON-LD: SportsClub + LocalBusiness + places. **No gym, no indoor, no open-gym hours.** Class singular `Adult Beginners East` is Old Street outdoor `SportsEvent` + `Course`.

**Mismatch: CRITICAL.** SERP wants an indoor gym page. LP offers outdoor classes in three parks. Ranking at pos 12 with 1.9% CTR is expected: the result does not answer “gym”.

**Do not** add fake indoor facilities. **Do** make the outdoor difference explicit in title/H1/first paragraph (`Outdoor parkour classes in London — not a gym`) so gym searchers self-select and `parkour classes london` (pos 5.1, 16.7% CTR) stays aligned.

Persona: adult in London who wants a dedicated indoor space, mats, membership. LP page answers “when is this week’s outdoor session?” — wrong job.

SXO gap (gym query → `/classes/`): **~25/100** (page type 0/15).

---

## 2. Kids

**Queries (LIVE UK 90d):** `parkour for kids london` 2/7/28.6%/3.7 · `parkour for kids` 1/72/1.4%/15.2 · `parkour london kids` 1/32/3.1%/4.8 · `kids parkour near me` 1/13/7.7%/10.7 · `parkour classes for kids near me` 1/14/7.1%/7.4. Kids-stem total in the pulled set: **9 clicks, 302 impressions**.

**SERP consensus:** dedicated **youth academy / age-banded class** pages — London Parkour School (mini/junior/youth, memberships), London Parkour Project (SE London, ages 4–17), Foucan / Westway. Service pages with ages, prices, locations. Not blog posts.

**LIVE pages:** `/classes/kids-class-west-6-9s/` indexed but **0 clicks, 1 impression, pos 75** in the UK page table; sibling dated slugs (`-2`, `-4`, `-5`) share ~82 impressions at pos ~31. `/classes/teens-class-west-10-14s/` 1 click, 26 impr, pos 64.6. Kids demand is **not landing on the kids URL**.

**STAGING:** `/classes/kids-class-west-6-9s/` title `Kids Class West (6-9s) | Parkour in London`, Course + SportsEvent + Offer £15, FAQPage, indexable. Youth class slug changed to `/classes/youth-class-west-10-14s/` (live `teens-class-…` must 301).

**Mismatch: HIGH on live URL hygiene** (dated class clones at pos 30–75); **MEDIUM on page type** (staging kids page *is* the right type, but one Vauxhall 6–9 class cannot own “parkour for kids london” against multi-site academies).

Do not invent a kids gym. Keep age in the title. Add a thin **kids hub** only if it is a real product (6–9 + 10–14 + locations), not a blog.

SXO gap (kids query → live dated class URLs): **~40/100**. Staging singular kids page is closer (**~60/100**) if redirects collapse the `-2/-4/-5` clones.

---

## 3. Seniors

**Queries (LIVE UK 90d):** `parkour for seniors near me` **3 clicks, 68 impr, pos 8.4** · `parkour for seniors` 1/3/33%/7.3 · other senior stems 0 clicks. Total **4 clicks, 87 impressions**.

**SERP consensus:** **dedicated elders programme** pages — West Coast Parkour “London Elders” (50+, Stratford, fall-prevention copy), historic Parkour Dance “Forever Young”. Not a mixed adult outdoor class.

**Target:** no seniors class in the staging sitemap (6 products: adult beginners, evening intermediate, adult north, kids 6–9, youth 10–14, teacher training). Adult classes are **14+** on live copy. LP is visible for the query (pos ~8) without a matching page — likely homepage/classes bleed.

**Mismatch: HIGH.** Impression without a product is a **trust risk** (searcher expects a 50+ class). Either build a real elders session or stay out of the snippet with copy that does not imply a seniors programme.

SXO gap: **~20/100**. Brand fit (welcoming, not extreme) is good *if* the product exists; it does not.

---

## 4. Informational tutorials vs commercial classes

**Commercial (LIVE UK 90d):** `parkour classes london` 9 clicks, pos 5.1 — aligned with **service/timetable**. `parkour london` / `london parkour` are navigational/brand → homepage (183 of 545 UK clicks). `/classes/` itself: **1 click, pos 52.5**.

**Informational:** `step vault` (tutorial URLs: 88 impr, 1 click, pos 6.4); SERP (WebSearch) is **how-to / tutorial** — LP `/tutorial/step-vault-basics/` and `/tutorial/step-vault-01/` already occupy that type, plus Trickipedia/wikiHow. Staging spokes keep `VideoObject`. **Page type ALIGNED.**

**Mismatch is not type — it is mixing jobs on one URL.** Homepage title `Practical Movement Training & Classes` tries to rank for both library and booking. Tutorials do not convert to bookings; classes timetable does not rank. GSC + GA4: organic landings for how-tos stay on `/tutorial/…`; booking intent still dumps on `/`.

Do not add “BOOK NOW” as the H1 of a step-vault lesson. Do add a single contextual link: this move is taught at Old Street / Kilburn — `/classes/…`.

SXO gap (how-to query → tutorial spoke): **~75/100** (type aligned; depth/cannibalization subtracts).  
SXO gap (classes query → homepage instead of `/classes/`): **~45/100**.

---

## 5. Brand: not ninja / not military

**Ninja (LIVE UK 90d):** 11 queries, **0 clicks, 14 impressions** (`ninja parkour near me` pos 22.7, `ninja warrior gym near me`, etc.). LP is not winning these and should not chase them. Staging titles/H1s sampled (`Practical Movement`, `Adult Beginners East`, `Kids Class West`) do not say ninja. **Keep it that way.**

**Military:** LIVE `/blog/definitive-guide-to-army-military-parkour-training/` is **indexed**, UK page table **130 impressions, pos 1.6**, 1 click. Staging sitemap still includes that post. Brand doctrine says avoid military positioning. The URL is a **high-ranking off-brand magnet** (also GA4 organic landing, 24 sessions). Interpretation: do not delete without a redirect plan; do not promote it in nav or ads; consider `noindex` only if the business accepts losing that query cluster.

---

## Issues

| Severity | Query / page | SERP expects | LP serves |
|---|---|---|---|
| Critical | `parkour gym` | Indoor gym landing | Outdoor timetable |
| High | `parkour for seniors near me` | Elders programme | No product |
| High | Kids class URLs on live | One stable kids service page | Dated slug clones, pos 30–75 |
| High | `parkour classes london` | Timetable/service | Homepage eats clicks; `/classes/` pos 52 |
| Medium | Army blog | — | Off-brand ranking URL |
| Aligned | `step vault` / how-tos | Tutorial | Tutorial + VideoObject |
| Aligned | ninja queries | Ninja gyms | LP correctly absent (0 clicks) |

---

## Recommendations

1. `/classes/` title/H1: outdoor + London + drop-in £15. One sentence: not an indoor gym. Leave gym queries to competitors unless the business opens a gym.
2. 301 live kids/teens dated clones to the two staging class URLs. Make `/classes/` filterable by age so “for kids” has a crawlable hub.
3. Seniors: product decision first (new class vs stay silent). Do not write seniors landing copy without a session on the board.
4. Point informational spokes at the timetable with one sentence + link; do not convert tutorials into landing pages.
5. Leave ninja language off. Decide explicitly what to do with the army blog (keep, noindex, or retitle) — it is currently a top indexed URL.
