# Google Ads writes

Interpreter: `~/.config/google-ads/venv/bin/python`

Load the client from the YAML (service-account JSON, no developer token):

```python
import os
from google.ads.googleads.client import GoogleAdsClient
from google.ads.googleads.errors import GoogleAdsException

CLIENT = GoogleAdsClient.load_from_storage(
    os.path.expanduser("~/.config/google-ads/google-ads.yaml")
)
CUSTOMER_ID = "8689582919"
```

If `load_from_storage` complains about a missing developer token, construct
explicitly:

```python
import os
from google.oauth2 import service_account
from google.ads.googleads.client import GoogleAdsClient

creds = service_account.Credentials.from_service_account_file(
    os.path.expanduser("~/.config/google-ads/service-account.json"),
    scopes=["https://www.googleapis.com/auth/adwords"],
)
CLIENT = GoogleAdsClient(
    credentials=creds,
    use_proto_plus=True,
    login_customer_id="8689582919",
)
```

Print `GoogleAdsException` `request_id` and each `error.message` on failure.

## GAQL search (fallback if MCP is down)

```python
ga = CLIENT.get_service("GoogleAdsService")
query = """
    SELECT conversion_action.id, conversion_action.name,
           conversion_action.status, conversion_action.type,
           conversion_action.category, conversion_action.tag_snippets
    FROM conversion_action
    WHERE conversion_action.status != 'REMOVED'
"""
for batch in ga.search_stream(customer_id=CUSTOMER_ID, query=query):
    for row in batch.results:
        print(row.conversion_action)
```

`tag_snippets` holds the conversion **label** used in GTM (`AW-810152772/LABEL`).

## Create “Website purchase”

Primary conversion, website, purchase category. Counting type is
`ONE_PER_CLICK` (v25 also has `MANY_PER_CLICK`). GTM sends `value`, so do
not force a default value. Always `validate_only=True` on the first call.

```python
ca_service = CLIENT.get_service("ConversionActionService")
ca_op = CLIENT.get_type("ConversionActionOperation")
ca = ca_op.create
ca.name = "Website purchase"
ca.type_ = CLIENT.enums.ConversionActionTypeEnum.WEBPAGE
ca.category = CLIENT.enums.ConversionActionCategoryEnum.PURCHASE
ca.status = CLIENT.enums.ConversionActionStatusEnum.ENABLED
ca.view_through_lookback_window_days = 1
ca.value_settings.always_use_default_value = False
# v25: ONE_PER_CLICK | MANY_PER_CLICK
ca.counting_type = CLIENT.enums.ConversionActionCountingTypeEnum.ONE_PER_CLICK

request = CLIENT.get_type("MutateConversionActionsRequest")
request.customer_id = CUSTOMER_ID
request.operations = [ca_op]
request.validate_only = True  # flip to False after a clean validate

response = ca_service.mutate_conversion_actions(request=request)
```

After the real mutate, search again and extract the label from `tag_snippets`.
Report: resource name, ID, label, status. Do not create a GTM tag unless the
user asked — that is a GTM-MCP job using Conversion ID `810152772` and the
new label.

## Mutate traps (this account)

- Do **not** set `target_spend_micros` on Maximize Clicks (`TARGET_SPEND`).
  That field overrides the daily budget.
- An empty TargetSpend oneof is a no-op. To change the CPC ceiling, set
  `target_spend.cpc_bid_ceiling_micros`.
- RSA `final_urls` / headlines / path fields: `AdService.mutate_ads`, not
  `AdGroupAdService` UPDATE.
- Some conversion actions cannot be paused or removed via API. Use the Ads UI.

## Safety

- Do not pause or remove ENABLED campaigns without an explicit ask.
- Do not attach a new conversion to bidding until the user says so.
- Do not reuse existing V7 conversion labels even if a similarly named action exists.
- `validate_only` does not persist; a 200 on validate is not “created”.
- Do not raise daily budget unless the user named the new amount.
