---
name: google-analytics
description: >-
  Read London Parkour GA4 property 377511335 via the Analytics Data API REST
  (no MCP). Use when the user mentions GA4, Google Analytics, sessions,
  purchase events, key events, traffic sources, realtime, or G-98XR7R92LT.
  Do not use for GTM container edits, Google Ads mutates, or Search Console.
---

# Google Analytics Data API (London Parkour)

There is **no GA4 MCP**. Do not invent one. Do not use `gcloud` user ADC for
these calls. Do not assume a project `.env` or `scripts/ga_client.py` exists.

Connection IDs live in `.cursor/rules/londonparkour-google-stack.mdc`.
Reads are allowed. Never invent performance numbers if the API call failed.

## Account

| Item | Value |
|---|---|
| Property | `377511335` |
| Measurement ID | `G-98XR7R92LT` |
| API | Data API v1beta REST |
| Auth | Same SA as Ads/GSC: `~/.config/google-ads/service-account.json` |
| Scope | `https://www.googleapis.com/auth/analytics.readonly` |
| Interpreter | `~/.config/google-ads/venv/bin/python` |

The service account must be a user on the GA4 property. `gcloud` was only
used to enable `analyticsdata.googleapis.com` on Cloud project `cursor-to-gtm`.

## Endpoints

POST JSON, `Authorization: Bearer <token>`:

- `https://analyticsdata.googleapis.com/v1beta/properties/377511335:runReport`
- `https://analyticsdata.googleapis.com/v1beta/properties/377511335:runRealtimeReport`

## Token + report

```python
import json, os, urllib.request
from google.oauth2 import service_account
from google.auth.transport.requests import Request

creds = service_account.Credentials.from_service_account_file(
    os.path.expanduser("~/.config/google-ads/service-account.json"),
    scopes=["https://www.googleapis.com/auth/analytics.readonly"],
)
creds.refresh(Request())

body = {
    "dateRanges": [{"startDate": "28daysAgo", "endDate": "yesterday"}],
    "dimensions": [{"name": "eventName"}],
    "metrics": [{"name": "eventCount"}],
    "dimensionFilter": {
        "filter": {
            "fieldName": "eventName",
            "inListFilter": {
                "values": ["purchase", "generate_lead", "begin_checkout"]
            },
        }
    },
}
req = urllib.request.Request(
    "https://analyticsdata.googleapis.com/v1beta/properties/377511335:runReport",
    data=json.dumps(body).encode(),
    headers={
        "Authorization": f"Bearer {creds.token}",
        "Content-Type": "application/json",
    },
    method="POST",
)
print(json.load(urllib.request.urlopen(req)))
```

Equal **complete** days only (`yesterday`, not `today`) unless the user asked
for realtime.

## Interpretation

- Outcome that matters: paid class bookings. `purchase` with value > 0 is the
  Ads conversion. Coupon redemptions can be `purchase` at £0 — those are not
  Ads conversions.
- V7 live HTML does not fire V8 `purchase`. Do not treat GA4 `purchase` as
  live Ads proof until V8 is public.
- Staging and live share this property. Split tests by hostname or
  `item_category` (`class` / `workshop` / `private` / `coupon`).
- Do not sum Ads conversions and GA4 `purchase` as one number.

## Errors

| Symptom | Fix |
|---|---|
| 403 | SA is not a user on property `377511335` |
| 401 | Token from the Ads key file, not `gcloud` ADC |
| Empty rows | Date range, event name spelling, or no hits yet |
| Wrong ID | Property is `377511335`, not measurement ID `G-98XR7R92LT` |

## Related skills

- Key events, streams, custom dimensions: `.cursor/skills/google-analytics-admin/`
- GTM that sends the events: `.cursor/skills/google-tagmanager/`
- Ads conversion actions: `.cursor/skills/google-ads/`
