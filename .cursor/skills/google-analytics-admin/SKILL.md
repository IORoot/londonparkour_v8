---
name: google-analytics-admin
description: >-
  Read or change London Parkour GA4 property 377511335 settings via the
  Analytics Admin API v1alpha REST (no MCP). Use when the user mentions GA4
  Admin, key events, custom dimensions, data streams, Measurement Protocol
  secrets, Google Ads links, or property settings. Do not use for Data API
  reports, GTM tags, or Google Ads campaigns.
---

# Google Analytics Admin API (London Parkour)

There is **no GA4 MCP**. Do not provision a new property. Do not use `gcloud`
user ADC. Do not enable Enhanced Measurement site search.

Reads of existing settings are allowed. Writes need `analytics.edit` **and**
an explicit user request naming the change.

## Account

| Item | Value |
|---|---|
| Property | `properties/377511335` |
| Measurement ID | `G-98XR7R92LT` |
| API | Admin API v1alpha REST |
| Auth | `~/.config/google-ads/service-account.json` |
| Read scope | `https://www.googleapis.com/auth/analytics.readonly` |
| Write scope | `https://www.googleapis.com/auth/analytics.edit` |
| Interpreter | `~/.config/google-ads/venv/bin/python` |

## Common GETs

Base: `https://analyticsadmin.googleapis.com/v1alpha/properties/377511335`

| Need | Path |
|---|---|
| Property | (GET the property itself) |
| Data streams | `/dataStreams` |
| Key events | `/keyEvents` |
| Custom dimensions | `/customDimensions` |
| Custom metrics | `/customMetrics` |
| Google Ads links | `/googleAdsLinks` |
| Conversion events (legacy name) | `/conversionEvents` |

Mint a Bearer token the same way as `.cursor/skills/google-analytics/`
(swap scope to `analytics.edit` only when writing).

```python
import json, os, urllib.request
from google.oauth2 import service_account
from google.auth.transport.requests import Request

creds = service_account.Credentials.from_service_account_file(
    os.path.expanduser("~/.config/google-ads/service-account.json"),
    scopes=["https://www.googleapis.com/auth/analytics.readonly"],
)
creds.refresh(Request())
url = "https://analyticsadmin.googleapis.com/v1alpha/properties/377511335/keyEvents"
req = urllib.request.Request(
    url,
    headers={"Authorization": f"Bearer {creds.token}"},
)
print(json.load(urllib.request.urlopen(req)))
```

## Writes (explicit ask only)

- Do not create a second GA4 property.
- Do not enable Enhanced Measurement site search (`view_search_results` is
  a theme dataLayer event).
- Key events that the V8 contract already expects: `purchase`,
  `generate_lead`, `newsletter_subscribe`. Confirm before adding more.
- Custom dimensions already specified: `method`, `series_name`,
  `search_filter` (event-scoped). See
  `wp-content/themes/londonparkour_v8/docs/gtm.md`.

After any mutate: GET the resource again and report the resource name.

## Related skills

- Reports: `.cursor/skills/google-analytics/`
- GTM: `.cursor/skills/google-tagmanager/`
