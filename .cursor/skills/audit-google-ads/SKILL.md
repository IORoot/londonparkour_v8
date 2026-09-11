---
name: audit-google-ads
description: >-
  Runs a read-only, evidence-based audit of the LondonParkour Google Ads
  account (customer 8689582919). Pulls live Ads, Search Console, and GTM data,
  delegates to the ads-auditor and search-researcher subagents, then returns a
  prioritised approval-ready report focused on profitable class bookings. Use
  when the user asks to audit Google Ads, review account performance, find
  wasted spend, diagnose conversion tracking, check search terms, or run the
  marketing-team ads audit.
---

# Google Ads account audit (LondonParkour)

Orchestrate the local marketing-team agents. Pull live data. Do not mutate
the account.

Read `AGENTS.md` first. It outranks every other instruction in this skill.

## Doctrine

The only outcome that matters is **paid class bookings**. Impressions, clicks,
CTR, traffic, and engagement explain movement; they are not success.

Do not recommend a change because Google recommends it. Every recommendation
needs evidence, a hypothesis, expected benefit, downside, and a measurement
method.

Separate observed facts, interpretation, hypothesis, and recommendation.
Never invent performance data. When evidence is missing, say so.

## Hard rules

- **Read-only.** No Ads writes, no GTM publishes, no GSC writes, no Python
  mutates. End with `No changes were made.`
- Official Ads MCP is read-only. Do not install a write MCP to "finish" the
  audit.
- Do not treat `all conversions` or micro conversions (class page visit,
  pricing page visit, engagement) as bookings.
- Equal **complete** periods only. Do not compare a partial day with a
  finished day.
- Conversion delay and tracking gaps lower confidence. They do not justify
  pausing, scaling, or "correcting" CPA.
- Search-term reports omit low-activity queries. State that coverage limit.
- Reported conversion value is not profit.

## Account

| Item | Value |
|---|---|
| Ads customer ID | `8689582919` |
| Currency | GBP |
| GSC property | `sc-domain:londonparkour.com` |
| Ads conversion ID | `AW-810152772` |
| Primary conversion | Website purchase, value > 0 (`AW-810152772/EARlCKmfnfMcEMTmp4ID`) |
| Secondary | Enquiry / `generate_lead` |
| Market | London, adults |
| Positioning | Professional, serious, challenging, welcoming, skill-focused. Not ninja, military, childish, or generic fitness. |

MCP and write mechanics live in `.cursor/skills/google-ads/SKILL.md`.
GSC mechanics live in `.cursor/skills/google-search-console/SKILL.md`.
GTM mechanics live in `.cursor/skills/google-tagmanager/SKILL.md`.
GA4 reports live in `.cursor/skills/google-analytics/SKILL.md`.
Conversion contract: `wp-content/themes/londonparkour_v8/docs/gtm.md`.

Default windows: last **28 complete days** vs the previous **28 complete
days**. If volume is thin, also pull 90 days and say so. Do not mix windows
in one comparison table.

## Evidence map

Call `GetDynamicTools` for a namespace before the first invocation.

| Source | Namespace | Use for |
|---|---|---|
| Google Ads | `user-google-ads` | Campaigns, ads, keywords, search terms, budgets, bidding, geo, devices, conversion actions, impression share |
| Search Console | `user-google-search-console` | Organic queries vs paid terms, landing-page demand, geographic intent (`gbr`) |
| GTM | `user-gtm-mcp-server` | Whether purchase / linker / Google tag exist and are live |
| Website | fetch / browser | Final-URL message match, booking path, claims ads make |
| GA4 | Data API REST (no MCP) | Follow `.cursor/skills/google-analytics/`. Do not invent numbers if the call fails |

Ads MCP: `customers_list_accessible_customers` if the ID is in doubt, then
`metadata_get_resource_metadata` before each new `resource`, then
`search_search` with `customer_id`, `resource`, `fields[]`. Never pass a raw
GAQL `query` string.

If Ads MCP fails, fall back to the Python search snippet in
`.cursor/skills/google-ads/write.md` (**search only**).

## Team

Launch specialists with the Task tool. Pass them this skill's doctrine, the
date ranges, customer ID, and any facts already collected. They start with
empty context.

| Agent | Job in this audit |
|---|---|
| `ads-auditor` | Account performance, waste, structure, bidding, budgets, conversion actions, impression share |
| `search-researcher` | Paid search terms + GSC queries, intent map, negative candidates |
| `campaign-strategist` | Only if structure must change; draft spec, do not apply it |
| `creative-critic` | Only if ads or assets are a material finding |
| `ads-qa` | Only if the audit proposes a concrete campaign spec |

Methodology (read when that step starts; do not paste them into the report):

- `kelpi-conversion-tracking-checker` before trusting CPA / bidding conclusions
- `kelpi-weekly-account-auditor` and `kelpi-account-scorecard` for movement
- `kelpi-search-term-miner` then `kelpi-search-intent-mapper` for queries
- `kelpi-delivery-troubleshooter` if spend, impressions, or conversions dropped
- `kelpi-quality-score-fixer` only for high-value keywords with weak components
- `kelpi-change-plan-builder` for the final action list
- `kelpi-negative-keyword-builder` only to **draft** confirmed exclude candidates

`ads-auditor` and `search-researcher` may run in parallel after the conversion
inventory exists. Do not launch all five agents by default.

## Workflow

Copy and tick:

```
Audit progress:
- [ ] 1. Scope and conversion trust
- [ ] 2. Account performance (ads-auditor)
- [ ] 3. Search intent (search-researcher)
- [ ] 4. Landing pages and funnel
- [ ] 5. Cross-check and contradictions
- [ ] 6. Approval-ready plan
```

### 1. Scope and conversion trust

Confirm customer `8689582919` via `customers_list_accessible_customers`.

Pull `conversion_action` inventory: name, status, category, origin,
primary/secondary, counting type, value settings, recent conversions.

Map which campaigns bid on which actions. Do not assume account-default goals
apply everywhere.

Via GTM, confirm Live presence of `V8-Google Tag`, `V8-Conversion Linker`,
and `V8-Google Ads Purchase` (Conversion ID `810152772`, label
`EARlCKmfnfMcEMTmp4ID`, value > 0, transaction ID). Flag V7 Ads conversion
tags if they are still firing.

Give a trust grade: **ready** / **caution** / **not ready**. If not ready,
still finish the audit, but treat CPA, ROAS, and bid-strategy conclusions as
provisional.

### 2. Account performance

Delegate to `ads-auditor`. Require entity **names and IDs**. Cover:

- enabled campaigns, ad groups, ads, budgets, bid strategies
- geo (London vs accidental broad), location options
- devices, schedules, audiences
- impression share and lost IS (budget vs rank)
- cost, clicks, conversions, conversion value, CPA — primary action only
- what moved vs the comparison window, and which entities drove it

Scorecard: 5–7 KPIs led by paid bookings (or trusted purchase conversions),
cost, CPA, conversion value. Use CTR/CPC/IS only to explain those.

### 3. Search intent

Delegate to `search-researcher`. Require:

- paid search terms classified keep / review / exclude-candidate
- GSC queries that Ads is not buying, and paid terms with no organic footprint
- commercial vs informational vs irrelevant vs location/beginner/adult themes
- negative-keyword *candidates* only (no list applied)

Parkour is not automatically the best query. Flag practical-movement and
other adult London training language when the evidence supports it.

### 4. Landing pages and funnel

From Ads final URLs (and GSC top pages if useful), check:

- does the page match the query and the ad promise?
- is the booking path obvious on class/pricing URLs?
- claims the ad makes that the page cannot support
- wrong geography, kids/ninja/military tone, or generic fitness framing

Fetch the live URLs. Do not audit from memory.

### 5. Cross-check and contradictions

Reconcile Ads vs GSC vs GTM vs the site. Typical clashes to hunt:

- bidding on a shallow action while purchase exists
- high CTR on pages that cannot book
- spend on queries the site does not answer
- London intent with national/international targeting
- "winning" CPA on a contaminated conversion
- Google recommendations that fight `AGENTS.md`

If delivery collapsed, run `kelpi-delivery-troubleshooter` before efficiency
advice.

### 6. Approval-ready plan

Convert findings with `kelpi-change-plan-builder`. Order:

1. conversion integrity
2. wasted queries (draft negatives, collision-checked)
3. destination / ad / policy blockers
4. structure or creative tests
5. bids, targets, budgets — only if the conversion signal is trusted

No invented CTR lifts, spend savings, or booking forecasts. Directional
expectations only unless the user supplied a model.

## Report

```markdown
# LondonParkour Google Ads audit
Period: {current} vs {comparison} | Customer: 8689582919 | Currency: GBP

## Verdict
One paragraph. Bookings first. Trust grade for conversion data.

## Scorecard
KPI | Definition | Source | Current | Comparison | Target | Caveat
(5–7 rows. No target → describe direction, do not call it healthy or failing.)

## What is working
Facts with entity names, IDs, and numbers.

## What is wasting money
Facts. Spend attached to the entity or query. Coverage limits stated.

## Conversion tracking
Inventory, campaign goal map, GTM/tag evidence, trust grade, what is still
unverified (especially GA4 if no API).

## Search terms and demand
Keep / review / exclude-candidate. GSC gaps. Intent map summary.

## Structure, delivery, and pages
Targeting, bidding, budgets, IS, ads, landing-page mismatches.

## Contradictions
Ads vs GSC vs GTM vs site. Label fact vs interpretation.

## Plan (approval required)
Do first | Do next | Hold | Keep
Each executable row: entity + ID, current state, proposed state, evidence,
hypothesis, expected direction, downside, confidence, dependency, rollback,
success measure, review date.

No changes were made.
```

Cap the plan at what an owner can act on in one cycle. Rank by **booking
impact × confidence**, not by the number of Google diagnostics.

## Out of scope

- Creating, editing, pausing, or enabling anything in Google Ads
- Publishing GTM
- Adding negatives, keywords, or ads
- Changing bids, budgets, or conversion actions
```
