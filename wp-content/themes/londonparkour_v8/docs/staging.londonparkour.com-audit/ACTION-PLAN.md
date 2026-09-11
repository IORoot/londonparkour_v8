# Action plan — London Parkour V8 SEO

Priority buckets are the **output of validation**, not a punch list of every finding. Each item names the first principle, what it unblocks, how we would know it failed, and a leading indicator.

Do not start content rewrites or AI-crawler policy changes until Phase 1 is done.

---

## Phase 1 — Launch blockers (before public DNS)

**Timeframe:** this week, in this order.

### 1. Confirm Site Address at cutover (canonicals are already dynamic)

ACF `seo_canonical` is **not filled** with a foreign URL on any checked page. Canonical, `og:url`, and JSON-LD `@id`s follow `home_url()`. Set **Settings → General → Site Address** (and `WP_HOME` / `WP_SITEURL`) to `https://londonparkour.com` and run `search-replace` for the old host in postmeta as usual.

- **THINK:** Empty ACF field = permalink. A later absolute URL in that field would freeze the host.
- **Fail check:** After cutover, view-source homepage canonical is still `staging.londonparkour.com`.
- **Indicator:** GSC Inspection “user-declared canonical” = `https://londonparkour.com/`.

### 2. Redirect map (live V7 → V8)

Must 301, not 302:

| Live (indexed) | Staging V8 |
|---|---|
| `/tutorial/{slug}/` | `/tutorials/{slug}/` |
| `/classes/outdoor-class-old-street-5/` (and dated clones) | `/classes/adult-beginners-outdoor/` (and the matching product) |
| `/classes/teens-class-west-10-14s/` | `/classes/youth-class-west-10-14s/` |
| `/bookings/` | real booking URL (not `/booking-cancelled/`) |
| `/giftcards/` | `/docs/gift-cards/` (or a real gift-card page) |

Kids slug `/classes/kids-class-west-6-9s/` can stay; still 301 the `-2/-4/-5` clones.

- **Fail check:** Inspection of `/tutorial/deadhang/` after launch is “not found” with no destination.
- **Indicator:** UK impressions on those URLs do not fall to zero in the first 28 days.

### 3. Delete or noindex junk

`/sample-page/`: `noindex` + drop from sitemap, or delete. `/clasbpro-theme-preview/` is already in the theme slug noindex list (git); **deploy that** so staging/live stop emitting `index, follow`.

- **Fail check:** they still appear in `wp-sitemap-posts-page-1.xml` or robots is still `index, follow`.

### 4. Fix `/docs/`

200 on `/docs/`, or remove from sitemap. FAQ doc must not 301 into a 403. `/docs` (no slash) must 301 to **HTTPS** `/docs/`.

- **Fail check:** curl `/docs/` is still nginx 403.

### 5. Fix `/book/`

Hero uses the booking drawer (correct). Closing CTA `href="/book/"` 301s to `/booking-cancelled/`. Point it at a working booking entry, or remove the href.

- **Fail check:** curl `-I /book/` still lands on cancelled + `noindex`.

**Parallel with Phase 1 (does not block DNS):** Cloudflare HSTS on HTML; `noindex` thin taxonomies (blog-tag, tutorial-tag, level, support-category).

---

## Phase 2 — High impact (week of launch + week after)

### 6. Live GSC sitemap hygiene

Delete `https://dev.londonparkour.com/sitemap_index.xml`, `http://londonparkour.com/sitemap_index.xml`, `http://www.londonparkour.com/sitemap_index.xml`. Submit **one** HTTPS index (`wp-sitemap.xml` or equivalent). Add `/tutorials/` hub to that index.

- **Fail check:** `list_sitemaps` still shows four properties.
- **Indicator:** indexed count on the remaining sitemap moves off 19.

### 7. NAP / schema truth

- Real `streetAddress` or omit it; hours in `openingHoursSpecification`.
- One Vauxhall postcode (1SR vs 1SS — ops must pick).
- Stable logo file on Organization (not the page photo).
- `en-GB` on `lang` / `inLanguage` / `og:locale`.
- One review count (42 vs 43), only on the org/homepage — not on tutorials.
- `Person` + `ProfilePage` on `/coaches/andy-pearson/`.
- Align Event capacity with on-page “capped at twelve”.

- **Fail check:** Rich Results Test still shows timetable text in `streetAddress`.

### 8. `/classes/` as the commercial URL

Keep the timetable. Add a short outdoor-not-a-gym lede already implied by the product. Do not chase `parkour gym`. Title already matches `parkour classes london`.

- **Indicator:** UK clicks on `/classes/` leave “1”; position leaves ~52.

### 9. Lab LCP

Homepage and `/classes/` are 7.6–7.7 s (Poor). Prior local diagnosis: render-blocking CSS + Google Fonts `@import` + hero. CLS/TBT are already good — do not start an INP project.

- **Fail check:** restaged Lighthouse mobile LCP still > 4 s after the CSS/font pass.
- **Indicator:** Lighthouse LCP; then CrUX once a PSI key exists.

### 10. `/tutorials-category/` weight

1.48 MB / 391 images. Do not inline the library. Keep the slimmer `/tutorials/` hub (323 KB vs live 2.67 MB).

---

## Phase 3 — Content and architecture (month 2)

11. Tutorial spokes: keep the video; finish the truncated standard as visible HTML. Do **not** add HowTo schema. Do **not** expect 609 spokes to index — live already submitted ~841 with 0 indexed. Prioritise hubs + the few GSC-visible slugs (`deadhang`, `crouch-walk`, `step-vault-*`).
12. Location pages: keep unique meeting sentences; H1 can include “Parkour classes” if design allows. Below 500-word floor is a coverage heuristic, not a penalty.
13. Kids: one hub only if 6–9 + 10–14 is a real combined product. Collapse dated live clones via 301.
14. Seniors: **do not** imply a 50+ programme. Either a real elders class or stay out of that snippet.
15. Gift cards: one canonical commerce URL with an Offer, not only a docs H1 “Questions, answered.”
16. Replace “JOHN DOE”. Add a `tel:` if the business wants calls (contact copy currently prefers email).
17. Army/military blog: leave indexed unless the business accepts losing pos 1.6; do not feature it in nav or ads.

---

## Phase 4 — Monitor (ongoing)

- Capture `/seo drift baseline` on `https://londonparkour.com/` after cutover.
- Watch: GSC indexed/submitted on the single sitemap; UK clicks on `/` vs `/classes/`; Inspection of 10 redirected URLs; lab LCP; GA4 organic landings (property 377511335).
- Do not compare 90d impressions to the previous 90d (GSC logging error overlap through 2026-04-27).
- Optional: PSI/CrUX API key; Moz if a backlink score is required.
- Re-audit 30 days after DNS: indexation, redirect coverage, `/book/` destination, `/classes/` position.

**Could not measure this round:** GBP internals, referring-domain counts, field CWV, staging in GSC.
