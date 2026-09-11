# Content quality findings — staging.londonparkour.com

**Scope:** Pre-launch V8 HTML. Word counts are from visible text in `<main>` (preferred) vs full document (nav + footer add ~200 words). Copy quoted below is **on the page**; nothing was invented to fill gaps.

**Fetched:** 2026-09-11 curl + HTTP basic auth. Auth credentials are not recorded here.

**Gates:** `seo-content` skill + `quality-gates.md`. Word-count floors are coverage heuristics, not Google ranking factors.

**Also used:** `SHARED.md`, `local.md`, `sxo.md`, `cluster.md`, `geo.md`. FAQ rich results retired **7 May 2026** — existing FAQ *content* is judged as content, not as a SERP feature.

---

## Content Quality Score: 56/100

### E-E-A-T Breakdown

| Factor | Score | Key signals |
|---|---:|---|
| Experience | 13/20 | Real outdoor photos, YouTube class/tutorial video, coach bios with years-on-street (Andy: “since he started in 2005”). Location pages have meeting-point sentences that pass a swap test (`local.md`). Tutorial *unique* text is one lede sentence. |
| Expertise | 17/25 | Named coaches, qualifications listed on Andy’s page (Parkour Coach L2, ADAPT L3, DBS, first aid). Class pages explain outdoor, scaled sessions. Army/military blog is long first-party material (and clashes with the brand rule “not military” in `AGENTS.md` — that is positioning, not thinness). |
| Authoritativeness | 12/25 | `sameAs` IG/YT/FB. Homepage client-logo row. Live GSC brand queries exist (`google.md`) but that is V7. No Person schema on the coach **URL**. Blog byline “Andy Pearson / HEAD COACH”. |
| Trustworthiness | 16/30 | HTTPS. `/docs/privacy-policy/` and terms exist (200). No `tel:`. Email split `contact@` (schema) vs obfuscated `hello@` on contact (`local.md`). Review count **42** in schema vs **43** in the hero (`local.md`). “JOHN DOE” testimonial on the homepage. Junk WP pages indexable. |

Weights follow the skill (Trust 30 / Expertise 25 / Authoritativeness 25 / Experience 20). Google does not publish numeric E-E-A-T weights.

### AI Citation Readiness: 44/100

Quotable homepage definition is ~40 words and defines *practical movement*, not “parkour classes in London” (`geo.md`). Tutorial ledes are one truncated sentence. Contact `<details>` + FAQPage Q&A is the strongest extractable block. `en-US` on a UK operator. Staging auth blocks AI crawlers until launch; robots already `Disallow` GPTBot/ClaudeBot/Google-Extended (`geo.md`).

---

## Word counts, titles, H1s

`<main>` counts include chrome inside main (filter boards, agendas). Unique-body notes call out when the number is inflated.

| URL | HTTP | Title (chars) | H1 | Main words | Full words | Floor | Verdict |
|---|---|---|---|---:|---:|---|---|
| `/` | 200 | London Parkour \| Practical Movement Training & Classes (54) | the world is your playground. | 1384 | 1592 | Homepage 500 | Pass volume. H1 is a slogan — no “parkour”, “classes”, or “London”. Meta 130 chars: “Practical movement is the practice of getting where you want to go. Taught across three London sites, to every age and every body.” |
| `/classes/` | 200 | Parkour Classes in London \| Weekly Timetable (44) | This week's sessions. | 296 | 504 | Service 800 | **Below floor.** Title is the query match; H1 is a timetable label. Unique intro is the 30-word meta/lede. Rest is Week 37 agenda (7–13 Sep 2026). |
| `/classes/adult-beginners-outdoor/` | 200 | Adult Beginners East \| Parkour in London (40) | Adult Beginners East | 1155 | 1363 | Service 800 | Pass volume. H1 = product name, not “parkour classes Old Street”. Lede (on page): “You don’t need to be fit, strong or experienced. Outdoor parkour for adults of every ability.” |
| `/classes/kids-class-west-6-9s/` | 200 | Kids Class West (6-9s) \| Parkour in London (42) | Kids Class West (6-9s) | 1534 | 1744 | Service 800 | Pass volume. Age is in title and H1. Lede: “Parkour for 6–9s. Real obstacles, small enough to start.” |
| `/classes/locations/vauxhall/` | 200 | Parkour Classes at Vauxhall \| London (36) | Vauxhall | 283 | 485 | Location 500–600 | **Below floor.** Unique meeting copy is real (“Outside Tube station exit 2, next to metal pillars and open area.”) — not a city-swap doorway. H1 is place-name only. |
| `/classes/locations/old-street/` | 200 | Parkour Classes at Old Street \| London (38) | Old Street | 277 | 480 | Location 500–600 | **Below floor.** Unique: “Outside main tube station exit. By big open area with benches.” |
| `/classes/locations/kilburn-park/` | 200 | Parkour Classes at Kilburn Park \| London (41) | Kilburn Park | 258 | 461 | Location 500–600 | **Below floor.** Unique: “Outside Kilburn Park Tube Station”. |
| `/about/` | 200 | About London Parkour \| Outdoor Classes Since 2018 (49) | London Parkour | 820 | 1024 | About 400 | Pass. On-page: “High-quality, well-taught, reasonably priced, accessible and fun coaching across London. That was the brief Andy set when he founded LondonParkour in 2018 — and it still is.” |
| `/contact/` | 200 | Contact London Parkour (22) | Let's talk movement. | 273 | 473 | — | **Title under 30 chars.** Form + FAQ. Meta: “Tell us what you're training for… Email is faster than the phone — coaches are on the floor during sessions.” No visible `tel:`. |
| `/tutorials/` | 200 | Parkour Tutorials \| London Parkour (34) | Tutorials. | 1833 | 2034 | Category 400 | Volume is the 609-video board. Hub is **not in the sitemap**. Lede: “609 coached videos, filed by movement.” |
| `/tutorials/deadhang/` | 200 | Deadhang \| London Parkour (25) | Deadhang | 1991 | 2191 | — | **Title under 30.** Unique body is the lede: “Demonstrate a full-stretch dead-hang on a bar that is scaffolding-width or greater for thirty seconds.” Then “Two demonstrations.” + shared 609-item filter. Unique copy is thin. |
| `/tutorials/vault-landing/` | 200 | Vault Landing \| London Parkour (31) | Vault Landing | 1383 | 1584 | — | Unique lede: “Perform any vault (Cat-Pass / slide / step / side / turn / etc… ) on a hip-height obstacle, but the landing must be a speed-step…” Same shared filter. |
| `/tutorials/crouch-walk/` | 200 | Crouch walk \| London Parkour (30) | Crouch walk | 1535 | 1736 | — | Unique lede: “Staying in a crouched position, with the hips below the knees, walk along the bar as you would normally.” |
| `/docs/` | **403** | 403 Forbidden | 403 Forbidden | 5 | 5 | — | Hub in sitemap; nginx 403. Cannot audit docs-hub copy. |
| `/docs/frequently-asked-questions/` | 301→403 | — | — | — | — | FAQ 800 | Redirects to `/docs/` then 403. In sitemap. |
| `/docs/gift-cards/` (proxy for docs template) | 200 | Parkour Gift Cards \| London Parkour (35) | **Questions, answered.** | — | — | — | Title is specific; H1 is the generic docs masthead. |
| `/blog/definitive-guide-to-army-military-parkour-training/` | 200 | Definitive Guide to Army & Military Parkour Training (52) | same as title | 7839 | 8043 | Blog 1500 | Pass volume. Byline present. Dates in schema. Brand doctrine: avoid military positioning — the URL and H1 are explicitly military. |
| `/blog/parkour-faq/` | 200 | Parkour FAQ \| London Parkour (28) | Parkour FAQ | 1281 | 1482 | Blog 1500 | **Below blog floor.** Schema is `BlogPosting`, not FAQPage. Meta truncates with “…”. |
| `/coaches/andy-pearson/` | 200 | Andy Pearson \| Parkour Coach, London (36) | Andy Pearson | 213 | 415 | — | Short but specific: Head Coach, Old Street, Precision & balance, qualifications list. Unique body ~ four paragraphs + quals. No Person JSON-LD on this URL. |
| `/sample-page/` | 200 | Sample Page \| London Parkour (28) | *(none)* | 159 | 360 | — | Default WP: “This is an example page… I live in Los Angeles, have a great dog…” |
| `/clasbpro-theme-preview/` | 200 | Booking Form Theme Preview \| London Parkour (43) | *(none)* | 11 | 214 | — | Body: “Select a theme from the Themes screen and open Live preview.” Meta description is the shortcode `[clasbpro_theme_preview]`. |

Three locations only — doorway 30+/50+ gates do **not** apply.

---

## Who / How / Why (helpful-content heuristic)

| | Homepage | Class product | Tutorial spoke | About / coach |
|---|---|---|---|---|
| **Who** | Brand, not a byline. Coaches appear later as “The people who teach the practice.” | Class pages include a coach byline component. | No author on the tutorial URL. | Andy named; quals on the coach URL. |
| **How** | Outdoor, three sites, £15, no contract — stated. | Session list + video. | Video + one-sentence standard. No written steps. | First-hand coaching history. |
| **Why** | Help people book a first class. Slogan H1 is brand, not query-bait. | Matches “when is the class”. | Library of movement standards — people-first if the video loads; thin as text. | Origin story. |

When the tutorial unique text is a single sentence, the page exists to host a video, not to answer the query in HTML. That is a coverage gap for crawlers that do not play YouTube, not proof of scaled AI spam.

---

## Thin / duplicate

**Thin (unique body):** location pages (~260–280 words in main, much of it shared “Three sites. One network.” + footer); `/classes/` intro; tutorial spokes (one lede); coach (~213); contact; sample-page; theme-preview.

**Duplicate / templated:**

- Location H2s repeat the place name twice, then the same “Three sites. One network.”
- Tutorial filter board (609 titles) is copy-pasted onto every spoke — inflates word count without unique coverage.
- Docs articles share H1 “Questions, answered.” (`/docs/gift-cards/` title says Gift Cards; H1 does not).
- Homepage “JOHN DOE” quote is placeholder-shaped (`local.md`).

**Not duplicate:** the three location meeting-point sentences differ. Adult vs kids class ledes differ. Do not treat locations as doorway pages.

**Junk that must not ship:** `/sample-page/` (Los Angeles bike-messenger WP boilerplate) and `/clasbpro-theme-preview/`.

---

## Keyword / heading notes (observed, not rewritten)

Live UK head terms (`google.md` / `SHARED.md`): parkour london, london parkour, parkour classes london, parkour for kids london.

- Homepage **title** includes Practical Movement + Classes. **H1** does not say parkour or London.
- `/classes/` title is the commercial query. H1 “This week's sessions.” does not.
- Location **titles** include “Parkour Classes at {Place} \| London”. **H1s** are “Vauxhall” / “Old Street” / “Kilburn Park”.
- Kids title/H1 include 6–9s — aligned with `parkour for kids london` (`sxo.md`).
- Deadhang title 25 chars — below the 30-char gate.
- Contact title 22 chars.

No keyword-stuffing observed in titles.

---

## FAQ content vs Google FAQ rich results

**Info — not Critical.** Google retired FAQ rich results for **all** sites on **7 May 2026**. Do not add a new `FAQPage` for SERP benefit. Do not treat missing FAQ stars as a ranking hole.

On-page FAQ **copy** still helps humans and non-Google extractors:

- Contact page: real questions (“Do I need any experience?”, “How much is a first class?” — first class £15 / 90 minutes, on the page).
- Class pages: FAQ labels are topics, not questions (“Beginners Welcome”, “Weather”, “Age limits”).
- `/blog/parkour-faq/` is a 2023 `BlogPosting` (datePublished `2023-12-07`), 1281 main words.

Keep the contact Q&A as HTML. Do not build a new FAQPage “for Google”.

Do **not** recommend HowTo schema for tutorials (retired Sep 2023). If tutorials need more text, write visible step headings in HTML — that is content, not a rich-result play.

---

## Issues found

### Critical

1. **Indexable junk:** `/sample-page/` (WP default, “Los Angeles”) and `/clasbpro-theme-preview/` (plugin live-preview).
2. **Docs hub 403** + FAQ doc 301→403 — cannot serve the docs index Google is asked to crawl.

### High

3. **Tutorial unique copy is one sentence** on all three sampled spokes, while 609 URLs will be submitted. Live tutorial sitemaps already show ~0 indexed (`sitemap.md`). Shipping the same thin HTML at `/tutorials/{slug}/` (live was `/tutorial/`) repeats that failure.
4. **Location pages below 500-word floor** with place-only H1s. Unique meeting copy is good; it is still a short page for “parkour classes {area}”.
5. **`/classes/` is a timetable, not a service explainer** (296 main words). Live GSC: `/classes/` 1 UK click / pos ~52 (`google.md`). Staging H1 still does not say outdoor parkour classes in London.

### Medium

6. Homepage H1 slogan vs title/query.
7. Contact title too short; no phone; Cloudflare email obfuscation.
8. Coach page is strong E-E-A-T *text* but only 213 main words and no Person schema on the profile URL.
9. Army/military guide is the longest blog (7.8k words) and ranks on live (pos 1.6) — it also contradicts the “not military” brand rule. Do not rewrite it in this audit; flag the conflict.
10. Blog FAQ below 1,500-word blog floor; last modified = published 2023-12-07 (no refresh signal).
11. “JOHN DOE” testimonial.
12. Docs H1 “Questions, answered.” on a gift-card URL.

### Low / Info

13. FAQPage on class + contact pages: **Info**. No Google FAQ rich result. Question *names* on class FAQs are not questions.
14. Truncated meta descriptions (ellipsis) on class, location, tutorials, blog FAQ — WP excerpt cut, not a separate ranking system.

---

## Recommendations (content, not invented copy)

1. Delete or `noindex` sample-page and clasbpro preview.
2. Restore `/docs/` (200) or remove it from the sitemap; stop 301ing the FAQ doc into a 403.
3. On tutorial spokes: keep the video; add the rest of the standard as visible HTML (the lede is already truncated on-page). Do not add HowTo JSON-LD.
4. On `/classes/` and the three location URLs: keep the existing unique sentences; put “parkour classes” + place in the H1 if design allows (location titles already have it).
5. Replace “JOHN DOE” or remove that quote.
6. Do not add FAQPage for Google. Leave contact Q&A in HTML.
7. Un-generic the docs masthead H1 so gift-cards (and siblings) use the page title, not “Questions, answered.”

---

## Limitations

Word counts include in-`<main>` filter/agenda chrome. Unique-body judgements are from the visible lede/H1, not a stripped-chrome tokenizer. `/docs/` copy was not readable (403). No rater-style E-E-A-T panel; scores are heuristic. Live traffic cited from `google.md` is V7.
