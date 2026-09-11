---
name: google-tagmanager
description: >-
  Read and edit the London Parkour GTM container GTM-P5T257F via MCP
  user-gtm-mcp-server. Use when the user mentions GTM, tags, triggers,
  variables, dataLayer, container versions, Preview, publish to Live, AW-
  conversion tags, or V8- prefixed entities. Do not use for Google Ads
  campaign mutates, GA4 Admin property settings, or Search Console.
---

# Google Tag Manager (London Parkour)

Connection IDs live in `.cursor/rules/londonparkour-google-stack.mdc`. Event
and tag **contract** lives in `wp-content/themes/londonparkour_v8/docs/gtm.md`
— read it before creating or renaming anything.

Reads are allowed. Workspace edits, versions, and Live publishes need an
explicit user request. Do not invent a second container.

## Account

| Item | Value |
|---|---|
| Public ID | `GTM-P5T257F` |
| Account ID | `2847440974` |
| Container ID | `8738637` |
| MCP | `user-gtm-mcp-server` |
| Folder | **V8** for all new entities. V7 entities stay prefixed `V7-` |
| GA4 | `G-98XR7R92LT` |
| Ads | `AW-810152772` |

Staging and live share this snippet. Publishing Live is what makes staging
fire without Preview.

## MCP workflow

Call `GetDynamicTools` for `user-gtm-mcp-server` before the first invocation.

Every entity tool takes `accountId` + `containerId`. Workspace-scoped tools
also need `workspaceId` — list workspaces first; do not hardcode an old ID.

| Tool | Typical actions |
|---|---|
| `gtm_workspace` | `list`, `get`, `getStatus`, `createVersion`, `sync`, `quickPreview` |
| `gtm_tag` | `list`, `get`, `create`, `update`, `remove` |
| `gtm_trigger` | `list`, `get`, `create`, `update`, `remove` |
| `gtm_variable` | `list`, `get`, `create`, `update`, `remove` |
| `gtm_folder` | `list`, `get` |
| `gtm_version_header` | `list`, `latest` |
| `gtm_version` | `live`, `get`, `publish` |
| `gtm_container` | `get`, `snippet` |
| `gtm_built_in_variable` | `list`, `create` |

`update` replaces the resource. Always `get` first and send the complete
object with your change applied.

## Read vs write

| Need | How |
|---|---|
| Inventory Live tags | `gtm_version` `action: live` (paginate with `resourceType` if truncated) |
| Inventory workspace | `gtm_tag` / `gtm_trigger` / `gtm_variable` `action: list` |
| Edit | mutate workspace entities, then `gtm_workspace` `createVersion` |
| Go live | `gtm_version` `action: publish` — **only when asked** |
| Confirm | re-read Live version; fetch published `gtm.js` if needed |

Workspace edits are not live. Do not treat `create` as published.

Check the Live version via MCP. Do not trust a version number written in a doc.

## Conversion contract (do not freelance)

- Ads tag: `V8-Google Ads Purchase`, Conversion ID `810152772`, label
  `EARlCKmfnfMcEMTmp4ID`, `{{V8-DLV - value}}`,
  `{{V8-DLV - transaction_id}}`, trigger `V8-CE purchase value gt 0`.
- Do not reuse V7 Ads labels. Do not fire Ads on `select_item` or
  `begin_checkout`.
- Do not split class / private / coupon / workshop into four Ads labels.
- Do not import `docs/gtm-ecommerce-import.json`.
- Pause V7 Ads conversion tags only when asked; do not unpause them to “fix”
  tracking.

Site events are pushed from `assets/js/utils/analytics.js`. Theme snippet:
`app/includes/gtm.php` via Site Settings `gtm_container_id`.

## Safety

- Do not `remove` V7 entities unless asked; pause is the usual path.
- Do not enable Enhanced Measurement site search from GTM or GA4.
- Custom JS variables must be ES5. GTM regex is RE2 (no lookahead).
- Never print service-account JSON from `~/.cursor/mcp.json`.

## Related skills

- Ads conversion actions: `.cursor/skills/google-ads/`
- GA4 reports: `.cursor/skills/google-analytics/`
- GA4 key events / streams: `.cursor/skills/google-analytics-admin/`
