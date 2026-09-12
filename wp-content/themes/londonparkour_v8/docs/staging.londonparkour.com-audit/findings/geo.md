# GEO / AI-search findings — staging.londonparkour.com

**Frame:** Google’s 2026 AI-optimization guide treats this as SEO applied to AI surfaces, not a separate discipline. `llms.txt` is reported for completeness; **Google Search ignores it** (Search Central, 2026-06). Staging HTTP auth blocks all crawlers including Googlebot and AI bots until launch.

Fetched 2026-09-11: `robots.txt`, `/llms.txt`, homepage, `/tutorials/`, `/tutorials/vault-landing/`, `/contact/`.

---

## GEO readiness: 41 / 100

| Dimension | Weight | Score | Why |
|---|---|---|---|
| Citability | 25 | 8 | Slogan H1; short lead paras; tutorial intros truncated; few 134–167 word self-contained answers. |
| Structural readability | 20 | 12 | Clean H1→H2, lists, FAQ on contact. Few question headings. Docs gift-card H1 is the generic “Questions, answered.” |
| Multi-modal | 15 | 11 | Real photos, YouTube, `VideoObject` on tutorials. Maps are JS Leaflet (OSM), not in the initial HTML. |
| Authority / brand | 20 | 6 | `sameAs` IG/YT/FB. No Person/author. `inLanguage` was `en-US` (11 Sep); **fixed in source** to `en-GB`. Logo in schema is a photo. Wikipedia/Reddit not evidenced on-page. |
| Technical accessibility | 20 | 4 | **GPTBot + ClaudeBot + Google-Extended disallowed.** SSR is good. `/llms.txt` 404. |

**Platform (relative, not live citation measurements — staging is auth-gated):**

| Surface | Readiness | Note |
|---|---|---|
| Google AI Overviews | Medium-low | Uses Googlebot (allowed). Ranking + passage quality still weak. `nosnippet` not set. |
| Google AI Mode / Gemini apps | Low | `Google-Extended` is **Disallow** — opt-out of Gemini/Vertex grounding & training. |
| ChatGPT search | Low | `GPTBot` Disallow. `OAI-SearchBot` is not named, so it inherits `User-agent: * Allow: /`. Split OpenAI policy. |
| Perplexity | Medium | `PerplexityBot` not in the Cloudflare block list → allowed via `*`. |
| Bing Copilot | Unknown | `Bingbot` not blocked. Not tested. |

---

## AI crawler access (staging `robots.txt`)

Cloudflare managed blocklist (also present on **live** `londonparkour.com/robots.txt`):

| Crawler | Rule | Effect |
|---|---|---|
| GPTBot | `Disallow: /` | ChatGPT training **and** the GPTBot fetch path for search. |
| ClaudeBot | `Disallow: /` | Claude web features that honour robots. |
| Google-Extended | `Disallow: /` | Gemini / Vertex training & grounding opt-out. **Does not** block Googlebot / classic Search / AI Overviews. |
| CCBot | `Disallow: /` | Common Crawl / training. Reasonable to keep. |
| Bytespider | `Disallow: /` | ByteDance training. Reasonable to keep. |
| Applebot-Extended | `Disallow: /` | Apple AI training opt-out. Applebot (Search) not listed. |
| Amazonbot / meta-externalagent | `Disallow: /` | Training / indexing for those vendors. |
| CloudflareBrowserRenderingCrawler | `Disallow: /` | CF internal. |

Also:

```
User-agent: *
Content-Signal: search=yes,ai-train=no,use=reference
Allow: /
Sitemap: https://staging.londonparkour.com/wp-sitemap.xml
```

**Not listed (therefore allowed by `*`):** Googlebot, OAI-SearchBot, PerplexityBot, ChatGPT-User, anthropic-ai, Google-CloudVertexBot.

User-triggered fetchers (`ChatGPT-User`, `Google-Agent`, `Google-NotebookLM`) **ignore robots.txt**. Auth on staging will still block them; on live they can fetch.

WordPress: `Disallow: /wp-admin/` + `Allow: /wp-admin/admin-ajax.php`. Live robots also disallows `/wp-includes/`, plugins, themes; staging does not — launch should not leave `wp-content/themes/` crawlable if that is unintended.

### Google-Extended tradeoff

**Fact:** Google documents `Google-Extended` as the opt-out for Gemini / Vertex **training and grounding**, distinct from Googlebot.

**Interpretation:** Keeping the disallow protects 609 tutorials from being used as Gemini training/grounding stock. The cost is weaker presence *inside Gemini apps* that rely on Extended, not inside classic Search or AI Overviews. For a publisher whose tutorials are the differentiator, the disallow is a coherent rights choice — but it should be an explicit decision, not an inherited Cloudflare default. `GPTBot` / `ClaudeBot` blocks are the ones that hurt ChatGPT / Claude *search citations*.

Recommendation if AI-search visibility is wanted: allow **GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot**; keep **CCBot, Bytespider, Google-Extended** (or drop Extended if Gemini-app citations matter more than training opt-out). Do not expect Content-Signal lines to change Google ranking (Google does not use them for Search).

---

## llms.txt

| Path | Status |
|---|---|
| `/llms.txt` | **404** |
| `/llms-full.txt` | **404** |
| RSL 1.0 | Not present |

Google Search ignores these files. For a class business they are optional. If added for non-Google agents, a short file pointing at `/classes/`, three location URLs, `/tutorials/`, `/docs/pricing/`, and `/contact/` is enough. Do **not** treat shipping `llms.txt` as a citation lever.

---

## Passage-level citability

**Homepage lead (~40 words):** “Practical movement is the practice of getting where you want to go. We teach it across three London sites, to every age and every body. No experience needed.”

- Quotable definition pattern (“X is…”), but it defines *practical movement*, not parkour classes in London.
- Far below the 134–167 word citation band; also shorter than the 40–60 word “direct answer” target if the query is “parkour classes london”.
- H1 slogan is not extractable as a local-service answer.

**Tutorial example `/tutorials/vault-landing/`:** masthead lede is one truncated sentence; body is video + “Two demonstrations.” No HowTo steps, no word-count block in the 134–167 range. `VideoObject` is present (`embedUrl` YouTube, `duration` PT95S, `uploadDate` 2021-11-26). Good for multi-modal; weak as a citable text passage. Meta description ends with an ellipsis.

**Contact FAQ** uses `<details>` questions — better structure for AI extraction than the homepage, but below the fold and not FAQ schema.

No Person byline, no visible published/updated date on the homepage. Tutorial `uploadDate` is in JSON-LD only.

---

## Server-side rendering

WordPress PHP emits H1, body, prices, and JSON-LD in the first HTML. Booking drawer and Leaflet maps are JS. AI crawlers that do not execute JS still see the class value proposition and tutorial ledes. They will **not** see OSM pins or drawer contents.

Cloudflare email obfuscation replaces `hello@londonparkour.com` in HTML with `/cdn-cgi/l/email-protection` — AI crawlers that only read HTML will not extract a usable email from contact.

---

## Schema relevant to AI parsing

Present on every sampled page: `SportsClub` + `LocalBusiness`, `WebSite`, `WebPage`, `Offer` (packs) on homepage, `VideoObject` on tutorials, `BreadcrumbList`.

Gaps:

- `inLanguage` was `en-US` on a UK business (11 Sep). **Fixed in source** (local 2026-09-12): `en-GB`.
- `logo` / `image` often a class photo (`alfredo-strides.jpg`), not a mark.
- `streetAddress` is schedule text (see `local.md`) — poison for extraction.
- No `HowTo` / `Article` on tutorials.
- Same organization graph repeated on tutorial URLs, so a vault tutorial asserts it *is* the sports club.

---

## Brand mentions (on-page only)

`sameAs`: Instagram `london_parkour`, YouTube `@londonparkour`, Facebook `ldnpk`. Homepage testimonials + `https://g.page/r/CaEUXmf0e4IHEBM` (and `/review`). No Wikipedia, Wikidata, or Reddit links on-page. Off-site mention volume was **not** crawled; do not infer citation share.

---

## Top 5 changes

1. **Allow GPTBot + ClaudeBot + OAI-SearchBot** in Cloudflare robots if ChatGPT/Claude search citations are desired. Decide Google-Extended explicitly.
2. Add a 120–160 word, self-contained “Parkour classes in London” answer near the top of `/` and `/classes/` (sites, price, who it’s for, no membership).
3. On tutorials: un-truncate the lede; add a 3–5 step HowTo block in HTML (not only a video).
4. Fix `inLanguage`, logo, and `streetAddress` so extractors do not quote “SUNDAYS 09:00–12:15"” as a street.
5. Optional: `/llms.txt` as a map of canonical URLs for non-Google agents — zero Google ranking weight.

---

## Limitations

No live AI Overview / ChatGPT scraper was run (auth wall; no DataForSEO in this pass). Scores are page-signal estimates, not observed citation counts.
