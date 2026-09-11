---
name: google-ads
description: >-
  Query and mutate the London Parkour Google Ads account via the official
  google-ads MCP (read) and the google-ads Python client (write). Use when the
  user mentions Google Ads, Ads conversions, AW- labels, GAQL, campaigns, bids,
  conversion actions, Explorer/Basic access, or wiring GTM Ads tags. Do not use
  for GA4 Admin, GTM container edits, or Search Console.
---

# Google Ads (London Parkour)

This machine is already authenticated. Do not run `google-ads-api-quickstart` or
`google-ads-api-mcp-setup` unless credentials are broken.

Connection IDs live in `.cursor/rules/londonparkour-google-stack.mdc`. This
skill is the **how**. Reads are allowed. Writes need an explicit user request.

## Account

| Item | Value |
|---|---|
| Ads customer ID | `8689582919` (digits only; UI shows `868-958-2919`) |
| Conversion ID (gtag / GTM) | `810152772` (`AW-810152772`) — **not** the customer ID |
| Cloud project | `cursor-to-gtm` |
| Service account | `cursor-to-gtm@cursor-to-gtm.iam.gserviceaccount.com` |
| Ads UI role | **Standard** (write: campaigns, conversions, ads; not user-admin) |
| API access | **Explorer** on the Cloud project (production OK) |
| Currency | GBP |

Python config (no developer token; ADC / service-account JSON):

- Key: `~/.config/google-ads/service-account.json`
- YAML: `~/.config/google-ads/google-ads.yaml`
- Venv: `~/.config/google-ads/venv` (`google-ads` ≥ 31.2, API v25+)

Never print the private key. Never commit these files.

## Read vs write

| Need | Tool |
|---|---|
| List accounts, GAQL-shaped reads, field metadata | MCP namespace `user-google-ads` |
| Create / update conversion actions, campaigns, ads, budgets | Python client in [write.md](write.md) |

The official MCP is **strictly read-only**. Do not install third-party write MCPs.
Do not guess that MCP `search` accepts a raw GAQL `query` string — this server
does not.

### MCP tools (exact names)

Call `GetDynamicTools` for `user-google-ads` before the first invocation.

1. `customers_list_accessible_customers` — no args. Call first if the customer ID is unknown.
2. `metadata_get_resource_metadata` — required arg `resource_name` (e.g. `campaign`, `conversion_action`). Call before constructing a search; cache the result.
3. `search_search` — required: `customer_id`, `fields` (array), `resource`. Optional: `conditions`, `orderings`, `limit`.

Example: campaigns named and enabled:

```
search_search
  customer_id: "8689582919"
  resource: "campaign"
  fields: ["campaign.id", "campaign.name", "campaign.status"]
  conditions: ["campaign.status = ENABLED"]
```

If MCP is missing or errors, fall back to the Python search snippet in
[write.md](write.md) (**search only** unless the user asked for a mutate).

## Writes

Read [write.md](write.md) before any mutate. Rules:

1. Confirm the change with the user unless they already named the exact mutate.
2. Prefer `validate_only=True` first, then the real mutate.
3. Re-read the resource after success and report IDs / resource names.
4. Resolve the API version from the installed client (`google.ads.googleads.vXX`);
   do not hardcode `v24`. Current install: **v25**.

Explorer **cannot** call: `CustomerService.CreateCustomerClient`, user-access
services, Keyword Plan / Reach Plan / Audience Insights, or billing / payments /
invoices. Conversion actions and campaign mutates are allowed.

Some conversion actions are **API-immutable** (hide/remove in the Ads UI).

## Conversion contract

From `wp-content/themes/londonparkour_v8/docs/gtm.md`:

- Primary **Website purchase** `7757844393`, label `EARlCKmfnfMcEMTmp4ID`
  (`AW-810152772/EARlCKmfnfMcEMTmp4ID`). `ONE_PER_CLICK`. Value from dataLayer.
  Do **not** reuse V7 Ads labels.
- GTM tag `V8-Google Ads Purchase`: Conversion ID `810152772`, that label,
  value `{{V8-DLV - value}}`, transaction ID `{{V8-DLV - transaction_id}}`,
  trigger `V8-CE purchase value gt 0`.
- Do not fire Ads on `select_item` or `begin_checkout`.
- Do not split class / private / coupon / workshop into four Ads labels;
  product type stays in GA4 `item_category`.

## Errors

| Error | Fix |
|---|---|
| `CLOUD_PROJECT_NOT_APPROVED_FOR_PRODUCTION` | Project still Test; apply Explorer+ at [Ads API overview](https://console.cloud.google.com/apis/api/googleads.googleapis.com/overview?project=cursor-to-gtm) |
| `USER_PERMISSION_DENIED` | Set `login_customer_id` to `8689582919` (no hyphens). SA must be on that account. |
| `DEVELOPER_TOKEN_NOT_APPROVED` | Ignore — this setup does not use a developer token |
| MCP tools missing | Restart Cursor; server is `pipx run google-ads-mcp` in `~/.cursor/mcp.json` |

Auth uses `GOOGLE_APPLICATION_CREDENTIALS` + `GOOGLE_PROJECT_ID=cursor-to-gtm`.
Do not invent OAuth client / refresh-token flows unless ADC is broken.

## Related skills

- Writes: [write.md](write.md)
- GTM tags that fire Ads: `.cursor/skills/google-tagmanager/`
- GA4 events / key events: `.cursor/skills/google-analytics/` and `.cursor/skills/google-analytics-admin/`
- Read-only audits: `.cursor/skills/audit-google-ads/`
