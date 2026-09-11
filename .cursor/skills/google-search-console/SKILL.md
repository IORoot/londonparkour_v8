---
name: google-search-console
description: >-
  Query London Parkour Google Search Console via MCP (read-only): search
  analytics, URL inspection, sitemaps, and property listing. Use when the user
  mentions GSC, Search Console, clicks, impressions, CTR, average position,
  queries, indexing, URL inspection, or sitemaps. Do not use for Google Ads,
  GTM, or GA4 Admin.
---

# Google Search Console (London Parkour)

This machine is already authenticated. Do not create a new Cloud project or
service-account key unless credentials are broken.

Connection IDs live in `.cursor/rules/londonparkour-google-stack.mdc`. This
skill is the **how**. Write scope is off.

## Account

| Item | Value |
|---|---|
| Property | `sc-domain:londonparkour.com` |
| Cloud project | `cursor-to-gtm` |
| Service account | `cursor-to-gtm@cursor-to-gtm.iam.gserviceaccount.com` |
| GSC permission | Full user (API used read-only) |
| API | `searchconsole.googleapis.com` (enabled) |
| Key file | `~/.config/google-ads/service-account.json` |
| MCP package | `mcp-server-google-search-console` |
| MCP namespace | `user-google-search-console` |

Never print the private key. Never commit the key file.

## Read vs write

The MCP is registered with `webmasters.readonly`. Sitemap submit/delete tools
are not available. Do not raise `GSC_SCOPES` to write without an explicit user
request.

## MCP tools (exact names)

Call `GetDynamicTools` for `user-google-search-console` before the first
invocation in a session.

1. `list_sites` — no args. Confirms which properties the service account can see.
2. `get_site` — required `site_url`. Permission level for one property.
3. `get_performance_summary` — required `site_url`. Optional `period`
   (`7d`/`28d`/`90d`, default `28d`) and `search_type` (default `web`). Current
   vs previous period plus top 10 queries.
4. `get_search_analytics` — required `site_url`. Pass `dimensions` for
   breakdowns (`query`, `page`, `date`, `country`, `device`,
   `searchAppearance`). Convenience filters: `filter_query`, `filter_page`,
   `filter_device`, `filter_country` (ISO 3166-1 alpha-3 lowercase, e.g. `gbr`).
   Optional `compare_period`: `previous_period` or `year_over_year`.
5. `inspect_url` / `batch_inspect_urls` — index status for one URL or up to 20.
6. `list_sitemaps` — submitted sitemaps and health summary.

Default `site_url` to `sc-domain:londonparkour.com` unless the user names
another property.

Example: last 28 days of queries:

```
get_search_analytics
  site_url: "sc-domain:londonparkour.com"
  dimensions: ["query"]
  row_limit: 25
```

## Data caveats

- Search Analytics is typically 2–3 days behind; default `data_state` is `final`.
- Query-dimension rows omit anonymized low-volume traffic. Site totals must
  come from a dimensionless (or non-query) aggregate, not from summing query
  rows.
- Country codes are ISO 3166-1 alpha-3 (`gbr`, not `gb` / `uk`).
- Impressions, CTR, and average position spanning 2025-05-13 to 2026-04-27 are
  unreliable (GSC logging error; clicks unaffected; no backfill).
- AI Overviews / AI Mode impressions are not a separate search type in the
  standard Performance report.

## If MCP is missing or 403s

1. Confirm `~/.cursor/mcp.json` has a `google-search-console` server pointing
   `GSC_SERVICE_ACCOUNT_KEY_FILE` at the Ads key file.
2. Confirm the Search Console API is enabled:
   `gcloud services list --enabled --project=cursor-to-gtm --filter=searchconsole`
3. Confirm the service account is still a user on
   `sc-domain:londonparkour.com` in Search Console → Settings → Users.
