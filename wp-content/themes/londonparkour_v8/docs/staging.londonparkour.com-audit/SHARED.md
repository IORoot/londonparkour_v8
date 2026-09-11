# Staging SEO audit — shared context

**Target:** https://staging.londonparkour.com/ (HTTP basic auth; do not print the password in findings)
**Live (V7, still public):** https://londonparkour.com/
**This is a pre-launch V8 WordPress theme audit.** Do not treat staging rankings as live rankings.

## Fetching

```bash
export PATH="/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin:/opt/homebrew/bin:$HOME/.local/bin:$PATH"
AUTH='staging:Parkour1'
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
/usr/bin/curl -sS -u "$AUTH" -A "$UA" --max-time 30 "$URL"
```

- `claude-seo run render_page.py` **rejects URL userinfo**. Do not pass `https://user:pass@host`.
- Python `urllib` may get Cloudflare 403. Prefer curl with the UA above.
- Homepage HTML: `homepage.html` in this folder
- Full sitemap URL list (752): `sitemap-urls.txt`

## Business type

Hybrid **local service** (3 outdoor class sites in London) + **publisher** (609 tutorials, blog, docs) + light **booking commerce** (`/book/`, coupons, gift cards).

## Homepage facts (verified)

- Title: `London Parkour | Practical Movement Training & Classes`
- Meta robots: `index, follow` (not noindex)
- Canonical / og:url: `https://staging.londonparkour.com/` (follows `home_url()`; ACF `seo_canonical` not overriding — `findings/canonical-overrides.md`)
- H1: "the world is your playground."
- JSON-LD `@graph`: SportsClub+LocalBusiness, WebSite, WebPage
- Schema `inLanguage`: `en-US` (UK business)
- `streetAddress` fields mixed with schedule text
- `logo` is a photo (`alfredo-strides.jpg`)
- robots.txt: Cloudflare AI crawler blocks (GPTBot, Google-Extended, ClaudeBot, CCBot…) + WP disallow `/wp-admin/` + Sitemap `wp-sitemap.xml`
- Response headers: Cloudflare, **no HSTS, no CSP, no X-Robots-Tag, no X-Content-Type-Options**
- `/llms.txt` 404

## Sitemap composition (752 unique)

| Source | Count | Notes |
|---|---|---|
| lp_tutorial | 609 | `/tutorials/{slug}/` — live V7 uses `/tutorial/` singular |
| pages | 13 in sitemap | sample-page 404; clasbpro preview omitted from sitemap |
| support/docs | 15 | |
| series tax | 13 | |
| blog posts | 10 | |
| tutorial categories | 55 | |
| classes | 6 | URL slugs changed vs live |
| coaches | 4 | |
| locations | 3 | Vauxhall, Old Street, Kilburn Park |
| other tax | rest | |

Pages in sitemap (13): `/` `/about/` `/blog/` `/legal/` `/classes/` `/classes-map/` `/contact/` `/docs/` `/tutorials-series/` `/tutorials-category/` `/workshops/` `/private-coaching/` `/coupons/`  
Not in sitemap: `/sample-page/` (**404** `noindex`); `/clasbpro-theme-preview/` (200 `noindex, nofollow`).

## Live GSC (sc-domain:londonparkour.com) — context only

28d web: 251 clicks (−4.6%), 14,093 impressions (−20.7%), CTR 1.8%, avg pos 10.

Sitemaps: 2035 submitted, **57 indexed (3%)**. Live `https://londonparkour.com/sitemap_index.xml`: 988 submitted, **19 indexed (2%)**. Stale properties still submitted: `dev.londonparkour.com`, `http://londonparkour.com`, `http://www.londonparkour.com`.

UK top queries: parkour london, london parkour, parkour classes london, london parkour school, parkour classes near me, parkour for seniors near me, parkour for kids london.

Live tutorial path is `/tutorial/`; staging is `/tutorials/`. Class slugs also changed. Redirect map is a launch blocker.

Write findings to `findings/<your-name>.md`. Do not invent metrics. Separate observed facts vs interpretation. Never dump secrets.
