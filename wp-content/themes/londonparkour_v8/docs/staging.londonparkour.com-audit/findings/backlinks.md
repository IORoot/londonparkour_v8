# Backlinks (LIVE londonparkour.com only)

**Target:** `londonparkour.com` (V7 public). Staging was not crawled for links.  
**Moz API:** not configured. **Bing Webmaster:** not configured. **DataForSEO:** not used.  
**Source this round:** Common Crawl web graph + GSC URL Inspection referring URLs + live HTTP checks.

**Do not invent Domain Authority.** No DA/PA/spam score is available.

---

## Backlink Health Score: INSUFFICIENT DATA (1/7 factors)

Only Common Crawl rank/presence ran. Skill rule: fewer than 4 scored factors → **no numeric 0–100 score**.

| Factor | Status | Source |
|---|---|---|
| Referring domain count | No count | Moz/Bing/DataForSEO missing |
| Domain quality distribution | No data | — |
| Anchor text | No data | — |
| Toxic ratio | No data | — |
| Link velocity | No data | DataForSEO-only |
| Follow/nofollow | No data | — |
| Geographic relevance | No data | — |
| CC presence | **In crawl, below rank threshold** | Common Crawl `cc-main-2026-jan-feb-mar` |

---

## Observed — Common Crawl (`commoncrawl_graph.py`, 2026-09-11)

```
domain: londonparkour.com
release: cc-main-2026-jan-feb-mar
in_crawl: true
in_rankings: false
pagerank: null
harmonic_centrality: null
n_hosts: null
note: Domain found in CC crawl but below ranking threshold (too small/new for PageRank rankings).
```

Confidence: low (CC domain graph ~0.50). **Below ranking threshold ≠ zero backlinks.** It means the domain did not make the published ranks file. Do not translate this into “DA is low”.

---

## Observed — GSC Inspection referring URLs (homepage, LIVE)

Google’s URL Inspection for `https://londonparkour.com/` listed referring URLs:

| URL | Live fetch 2026-09-11 | londonparkour mention in HTML? |
|---|---|---|
| `https://mantaw.com/google-marketing-strategy/` | **403** | unverifiable (blocked) |
| `http://parkourlabs.com/` | 200, **114 bytes**, no `<title>` | **0** mentions of londonparkour / london parkour |
| `http://londonparkour.com/` | — | self / protocol variant, not an external link |
| `https://londonparkour.com/support/equity-policy/` | — | internal |

`parkourlabs.com` does **not** currently contain a detectable link in the raw HTML (empty/parked-sized body). Do not report it as a live backlink. Label: **not verified / likely removed or JS-only** (114 bytes is not a JS app).

These four strings are **Google’s crawl citations**, not a referring-domain list.

---

## Observed — not measured

- Referring domain total  
- Anchor distribution  
- New/lost links (30/60/90d)  
- Competitor gap vs parkourgenerationslondon.com / londonparkourschool.com  
- Disavow candidates  

Press URLs guessed from the site’s own blog (`/blog/the-guardian/`, `/blog/sky/`, `/blog/imperial-college-london/`) were **not** verified as inbound links. Sample Guardian lifeandstyle parkour URLs returned **404**. Do not count them.

---

## Interpretation

1. The live site is small in the CC graph. That matches a local service + YouTube-heavy publisher more than a domain-rank play. It does **not** justify buying links or inventing DA.
2. GSC already shows the commercial problem is **page type and `/classes/` ranking**, not a missing 100-referring-domain profile (`google.md`, `sxo.md`).
3. The only “link” Google cited that we could fetch is dead or empty (`parkourlabs.com`). Worth a reclaim check, not a disavow.

---

## Issues

| Severity | Finding |
|---|---|
| Info | Moz/Bing unset — no DA, no verified referring domains |
| Info | CC: in crawl, not in ranks file |
| Low | Inspection-cited `parkourlabs.com` no longer contains a visible link |
| Low | `mantaw.com` 403 — cannot verify |

---

## Recommendations

1. Configure Moz (free) or Bing Webmaster if a scored profile is required before launch. Re-run `/seo backlinks`.
2. After V8 go-live, keep the **same host** `londonparkour.com` so existing (unknown) equity is not split. Redirect `/tutorial/` and class slugs so equity lands on V8 URLs (`cluster.md`).
3. Optionally email/reclaim `parkourlabs.com` if it was a real partner; otherwise ignore.
4. Do not build a ninja/military linkbait strategy off the army blog just because it ranks (`sxo.md`).

---

*Moz and Bing remain the next free data tier. Common Crawl alone cannot support a health score or DA number.*
