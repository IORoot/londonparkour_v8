# London Parkour V8 — full SEO audit

**Target:** https://staging.londonparkour.com/ (HTTP basic auth)  
**Live comparison:** https://londonparkour.com/ (V7, public)  
**Date:** 2026-09-11 (recheck: `/docs/` 200; Sample Page 404; LCP pass 14:33 BST)  
**Business type:** Hybrid local service (3 outdoor class sites in London) + publisher (609 tutorials) + light booking commerce.

This is a **pre-launch** audit of the V8 theme on staging. Ranking, GSC, GA4, and CrUX numbers below are **LIVE V7** unless labelled staging lab.

---

## Executive summary

**SEO Health Score: 54 / 100**

The V8 site is a well-typed local class business with server-rendered titles, unique meeting-point copy, Course/SportsEvent/Offer JSON-LD, and a slimmer tutorial hub than live. Recheck **2026-09-11**: staging **indexation leftovers are cleared**. V7→V8 301 map is live. `/book/` → `/classes/`. `/sample-page/` is 404. Clasbpro preview is `noindex`. **`/docs/` is 200** (`Parkour FAQ & Docs | London Parkour`, `index, follow`); `/docs` (no slash) 301s to **HTTPS** `/docs/`; FAQ 301s into a working hub.

**Not yet launch-complete:** set Site Address at cutover; lab LCP still Poor (**7.8 s** `/`, **6.6 s** `/classes/` this run); live GSC still has `dev.` / `http://` / `www.` sitemaps; NAP/schema (JOHN DOE, 42 vs 43, SW8 1SR vs 1SS). Canonicals follow `home_url()`. Inlined fold CSS removed render-blocking stylesheets; simulated LCP did not fall under 4 s.

**Canonicals are dynamic.** A 2026-09-11 check of every WordPress page plus 631 other 200s found **no ACF `seo_canonical` pointing at another host or path.** Staging host in the tags follows `home_url()`. It will become `londonparkour.com` when Site Address / `WP_HOME` does. See `findings/canonical-overrides.md`.

Highest-leverage constraint left is **cutover**: Site Address → `https://londonparkour.com`, then GSC sitemap hygiene. Live UK search already concentrates on the homepage (183 of 545 UK clicks in 90 days). `/classes/` is indexed and commercially invisible (1 click, position 52.5).

### Top 5 critical issues

1. Lab LCP still Poor on `/` (**7.8 s**, overlay text) and `/classes/` (**6.6 s** this run). Render-blocking CSS is gone; fail check “under 4 s” still fails.
2. Thin tutorial spokes (unique body one sentence) at new `/tutorials/{slug}/` URLs — 301s from `/tutorial/` are in place; Google still has to recrawl.
3. Live GSC sitemap hygiene: `dev.`, `http://`, and `http://www.` indexes still submitted.
4. At cutover, set Site Address so canonicals become `londonparkour.com`.
5. `/clasbpro-theme-preview/` is 200 `noindex` (out of sitemap). Harmless if it stays unpublished; delete when convenient.

### Top 5 quick wins

1. In live GSC, delete `dev.`, `http://`, and `http://www.` sitemaps; submit one HTTPS index after cutover.
2. Replace “JOHN DOE”; sync review count 42 vs 43; fix Vauxhall `SW8 1SR` vs `SW8 1SS`.
3. Add `/tutorials/` hub to the sitemap.
4. Finish lab LCP: Scope Trial woff2 / keep that TTF off the homepage critical CSS (`/` still 7.8 s). ClasbPro CSS already waits for the drawer.
5. Set Site Address / `WP_HOME` to `https://londonparkour.com` at DNS cutover.

---

## Synthesis (PERCEIVE → ANALYZE → VALIDATE → ACT)

### Perceive

- Staging: 752 sitemap URLs, 609 tutorials, Cloudflare AI-crawler blocks, no HSTS/CSP on HTML, lab LCP 7.8 s `/` / 6.6 s `/classes/` (FCP `/` 2.3 s this run).
- Live GSC (`sc-domain:londonparkour.com`, 90d UK): 545 clicks, 19,352 impressions, 2.8% CTR, pos 8.3. Sitemap: **988 submitted / 19 indexed (2%)**. Tutorial sitemaps: ~841 submitted / **0 indexed**.
- Live GA4 90d: 1,101 organic search sessions; 227 AI Assistant sessions. No `purchase` events (V7 does not fire V8 ecommerce).
- Demand is brand + local class intent (`parkour london`, `parkour classes london`, kids/seniors/near me). The tutorial library is not the traffic engine (5 UK clicks across top tutorial URLs).

### Analyze (first principles)

- Page types: homepage = brand/nav; `/classes/` should be the commercial service page; location pages = local; tutorials = how-to video; they currently mix jobs.
- Eligibility floor: live is indexed, but coverage is ~2%. Staging auth hides V8 from Google until cutover. The V7→V8 301 map is **live on staging**. `/docs/` is 200. Canonicals follow `home_url()`. Sample Page is 404.
- Lateral: thin tutorial HTML × 0% tutorial indexation on live × `/tutorial/` → `/tutorials/` path change = shipping 609 new URLs that Google has already refused to index, at new addresses.
- System: staging indexation leftovers are cleared; cutover is Site Address + GSC sitemap hygiene; LCP is parallel.

### Validate

- Do **not** keyword-stuff slogan H1s against Concourse brand voice. Title tags already carry “Parkour Classes in London”.
- Do **not** build indoor-gym or ninja pages. `parkour gym` SERP is indoor facilities; LP is outdoor. Spell that out; do not fake a gym.
- Do **not** add FAQPage or HowTo for Google (FAQ rich results retired 7 May 2026; HowTo retired Sep 2023).
- Do **not** delete the army/military blog without a redirect plan — it ranks at pos 1.6. Do not promote it.
- Operator capacity: a 2-person business should ship the cutover map + junk cleanup this week, not 55 unique category intros.

### Act

See `ACTION-PLAN.md`. Leading indicators after launch: GSC indexed count on the HTTPS sitemap (from 19 toward class/location/hub URLs), UK clicks on `/classes/`, lab LCP under 4 s then 2.5 s.

---

## Category scores

| Category | Score | Weight | Weighted |
|---|---:|---:|---:|
| Technical SEO | 70 | 22% | 15.4 |
| Content Quality | 56 | 23% | 12.9 |
| On-Page SEO | 51 | 20% | 10.2 |
| Schema / Structured Data | 44 | 10% | 4.4 |
| Performance (CWV, lab) | 40 | 10% | 4.0 |
| AI Search Readiness | 41 | 10% | 4.1 |
| Images | 68 | 5% | 3.4 |
| **Health** | **54** | 100% | **54.4 → 54** |

---

## Technical SEO — 70

**What works:** HTTPS, Googlebot allowed, sitemap declared, viewport, titles/H1/JSON-LD in first HTML (not an SPA), trailing-slash 301s on marketing URLs, checkout utilities correctly `noindex`. **Canonical / `og:url` / JSON-LD `@id` are `home_url()`-based.** V7→V8 301s are live. `/docs/` is **200** (`index, follow`); `/docs` 301s to HTTPS. `/clasbpro-theme-preview/` is `noindex, nofollow` and omitted from the page sitemap. `/sample-page/` is **404 `noindex`**. `/book/` 301s to `/classes/`.

**Fails**

| Sev | Finding |
|---|---|
| Info | `/docs/` resolved: 200 `index, follow`; no-slash 301s to HTTPS |
| High | Lab LCP 7.8 s `/`, 6.6 s `/classes/` this run (`performance.md`) |
| Info | `/sample-page/` resolved: 404 `noindex` |
| Info | `/clasbpro-theme-preview/` resolved: noindex + not in sitemap |
| Info | `/legal/` already **301**s via `lp_docs_redirects()` to `/docs/terms-of-service/` |
| Medium | No HSTS/CSP/X-CTO/X-FO/Referrer-Policy on HTML |
| Medium | `/tutorials/` hub missing from sitemap |
| Medium | `/tutorials-category/` 1.48 MB / 391 images |

---

## Content quality — 56

Homepage (1,384 main words), about (820), adult/kids class products, and the army blog (7.8k) pass volume. Location pages (258–283) and `/classes/` (296) sit under service/location floors. Tutorial unique body is **one sentence** plus a 609-item filter board that inflates word count.

E-E-A-T: named coaches with real quals (Andy: Parkour Coach L2, ADAPT L3, DBS). Trust dents: no `tel:`, `hello@` vs `contact@`, review 42 vs 43, “JOHN DOE”.

---

## On-page — 51

Titles on commercial URLs are unique and mostly 30–60 chars. H1s are slogans or place names (`the world is your playground.`, `This week's sessions.`, `Vauxhall`). Contact title is 22 chars. Internal nav is strong. Canonicals will be wrong at cutover until the host is rewritten.

---

## Schema — 44

Parse-valid `@graph` on every sampled page. Types are useful (`SportsClub`, `Course`, `SportsEvent`, `Offer`, `VideoObject`). Content is not:

- `streetAddress` is timetable text (Vauxhall trailing `"`).
- `logo` is whatever photo is on the page.
- `inLanguage` was `en-US` vs Course `en-GB` (11 Sep capture). **Fixed in source** (local 2026-09-12): `lang` / `og:locale` / WebPage `inLanguage` are `en-GB`. Staging not re-fetched.
- `aggregateRating` 4.9/42 on **every** URL including tutorials.
- Coach URL has no `Person`/`ProfilePage`.
- Location URL is not its own LocalBusiness.
- FAQPage present = **Info** (rich results retired). No HowTo (correct — do not add).

---

## Performance — 40 (lab)

Staging mobile Lighthouse 13.4.1, Slow 4G, HTTP auth, **14:33 BST**: homepage LCP **7.8 s** (was 7.7; other sample 6.9), FCP **2.3 s**. `/classes/` LCP **6.6 s** (was 4.4 with 1.2 s TTFB; first sample 8.8 discarded). CLS 0.003 / 0 (good), TBT 220 / 90 ms (good). Render-blocking CSS gone; Scope Trial TTF still on the critical path. No field INP/CrUX (no API key). Live V7 TTFB ~0.95 s.

---

## AI search — 41

Cloudflare `robots.txt` disallows GPTBot, ClaudeBot, Google-Extended (Gemini grounding opt-out). Googlebot / AI Overviews still allowed. `/llms.txt` 404 (Google ignores it). Passages are short. Staging auth blocks everyone until launch.

---

## Images — 68

Homepage: 44 `<img>`, **0 missing alt**. Lab LCP on `/` is overlay copy (`p.font-body`, Helvetica/Arial), not the hero photo. Ken Burns slides 1–3 are in `<template>` and not fetched on first paint. `/tutorials-category/` inlines 391 images. Organization `logo` must not be a JPEG of a person.

---

## Local — 38 (not in the 7-weight mix; informs on-page)

Three outdoor meeting points, unique copy, one `g.page` link. No phone. Vauxhall **SW8 1SR vs SW8 1SS**. Hours contradict (homepage Tue/Thu Vauxhall vs schema Sundays). GBP dashboard was not read.

---

## Live search (V7 context)

| | 28d worldwide | 90d UK |
|---|---|---|
| Clicks | 251 (−4.6%) | 545 |
| Impressions | 14,093 | 19,352 |
| CTR | 1.8% | 2.8% |
| Avg pos | 10 | 8.3 |

UK head terms: `parkour london`, `london parkour`, `parkour classes london`. Homepage 34% of UK clicks. `/classes/` 1 click / pos 52.5. Stale GSC sitemaps: `dev.londonparkour.com`, `http://londonparkour.com`, `http://www.londonparkour.com`.

---

## What this audit could not measure

- GBP category, hours, review velocity (no dashboard).
- Moz/Bing DA, referring-domain counts (Common Crawl only: in crawl, below rank threshold).
- CrUX/PSI field CWV (no API key).
- Staging GSC (auth wall).
- DataForSEO geo-grid / maps.

Next audit after cutover should re-inspect homepage, `/classes/`, one location, one tutorial on `londonparkour.com`, and count indexed URLs on the single HTTPS sitemap.

Specialist files: `findings/*.md`. Structured envelope: `audit-data.json`.
