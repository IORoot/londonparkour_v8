# Google Tag Manager — V8

Container `GTM-P5T257F`. GA4 property `G-98XR7R92LT`. Google Ads `AW-810152772`.
Site Settings field `gtm_container_id` prints the snippets (`app/includes/gtm.php`).
Events are pushed from `assets/js/utils/analytics.js`. CMP is not in this pass
— the theme always pushes; GTM tags currently have consent `notSet`.

V7 entities are prefixed `V7-`. Everything below is `V8-`, in a **V8** folder.
Do not import `docs/gtm-ecommerce-import.json` — create tags via the API / UI.

## Locked decisions

| Topic | Decision |
|---|---|
| Config tag | One `V8-Google Tag` on All Pages. Pause `V7-Google Tag` and `V7-Universal Google Tag` on publish |
| Linker | `V8-Conversion Linker` on All Pages |
| GA4 property | Keep `G-98XR7R92LT` (do not split a new property) |
| Payments | Ecommerce funnel. `v8_purchase` is a key event (incl. £0 coupon redemptions). Always send `value`, `currency`, `items[].price` |
| Ads (this publish) | **Deferred.** New “Website purchase” conversion (value > 0 only) in a second pass when a fresh Ads label exists. Leave V7 Ads tags running for live V7 |
| Contact | `v8_generate_lead` (key event), theme dataLayer, sessionStorage |
| Newsletter | `v8_newsletter_subscribe` (key event). `method`: `dispatch` \| `booking_drawer` |
| Dispatch | Fire on `?dispatch=sent` (Mailchimp thank-you), not a footer form |
| Booking newsletter | Fire on `/booking-confirmed/` only if `_clasbpro_mailchimp_opt_in` |
| Search | Custom `v8_view_search_results`. Do **not** enable GA4 Enhanced Measurement site search |
| Video | Tutorial dialogs only: `v8_video_start` + `v8_video_progress` at 25 / 50 / 75 |
| Series | `v8_select_content` on PLAY SERIES, series lesson cards, tutorial sibling board |
| Product interest | `v8_view_item` on class / workshop / private / coupon **detail** load only |
| Event names | Every V8 dataLayer event is prefixed `v8_` so it is distinct from live V7 events in the shared GA4 property |
| Out of scope | Map pins, timetable filters, outbound, 404, class/workshop/thank-you films |
| GTM tags | Two GA4 Event tags: ecommerce vs leads/content |
| Custom dimensions | `method`, `series_name`, `search_filter` (event-scoped) |
| Publish | Publish to Live so Cloudways staging (same snippet) can be tested without Preview. V7 click/thank-you/Ads tags stay unpaused |

## Data layer — commerce

Each ecommerce push is preceded by `{ ecommerce: null }`.

| User action | Event | `item_category` |
|---|---|---|
| Product detail load | `v8_view_item` | `class` / `workshop` / `private` / `coupon` |
| Click Book / Buy | `v8_select_item` | same |
| Drawer form loaded | `v8_begin_checkout` | same |
| Click CONFIRM AND PAY | `v8_add_payment_info` | same (`payment_type`: `stripe` or `coupon`) |
| Land on `/booking-confirmed/` | `v8_purchase` | same |

`item_id` is `class:{id}`, `workshop:{id}`, `private:{id}`, or `pack:{id}`.
Currency is `GBP`. Every commerce event includes `value` and `items[].price`.
`v8_purchase` uses the Stripe session id as `transaction_id`
(sessionStorage so refresh does not send a second hit).

GA4 Monetization → Ecommerce purchases only counts the recommended event
`purchase`. V8 revenue lives on the `v8_purchase` **key event** (`value` +
`currency`) and on the event parameters / items array. Do not also fire a
bare `purchase` from V8 — that would mix with V7.

## Data layer — leads and content

No PII (no email, no name).

| User action | Event | Parameters |
|---|---|---|
| Contact `?contact=sent` | `v8_generate_lead` | — |
| Dispatch `?dispatch=sent` | `v8_newsletter_subscribe` | `method`: `dispatch` |
| Booking confirmed + Mailchimp opt-in | `v8_newsletter_subscribe` | `method`: `booking_drawer` |
| Search results (`/?s=`) | `v8_view_search_results` | `search_term`, `result_count`, `search_filter` (`all` or post type) |
| Tutorial video actually plays | `v8_video_start` | `video_title`, `video_provider`: `youtube`, `series_name` |
| Tutorial watch 25 / 50 / 75% | `v8_video_progress` | `video_percent`, plus the start params |
| PLAY SERIES / lesson card / sibling row | `v8_select_content` | `content_type`, `content_id`, `content_name`, `series_name` |

Lead and search events use sessionStorage so a thank-you refresh is not a
second conversion. `v8_select_content` fires on every click.

## GTM workspace (V8 folder)

### Variables (built-in)

Enable **Event** if it is not already on. Leave other built-ins unprefixed.

### Variables (user-defined, Data Layer v2)

| Name | Data Layer Variable Name |
|---|---|
| `V8-DLV - ecommerce` | `ecommerce` |
| `V8-DLV - transaction_id` | `ecommerce.transaction_id` |
| `V8-DLV - value` | `ecommerce.value` |
| `V8-DLV - currency` | `ecommerce.currency` |
| `V8-DLV - item_category` | `ecommerce.items.0.item_category` |
| `V8-DLV - item_id` | `ecommerce.items.0.item_id` |
| `V8-DLV - item_name` | `ecommerce.items.0.item_name` |
| `V8-DLV - item_price` | `ecommerce.items.0.price` |
| `V8-DLV - payment_type` | `ecommerce.payment_type` |
| `V8-DLV - method` | `method` |
| `V8-DLV - series_name` | `series_name` |
| `V8-DLV - search_filter` | `search_filter` |
| `V8-DLV - search_term` | `search_term` |
| `V8-DLV - result_count` | `result_count` |
| `V8-DLV - video_percent` | `video_percent` |
| `V8-DLV - video_title` | `video_title` |
| `V8-DLV - content_type` | `content_type` |
| `V8-DLV - content_id` | `content_id` |
| `V8-DLV - content_name` | `content_name` |
| `V8-GTM Tag ID` | Constant `G-98XR7R92LT` |

### Triggers

| Name | Type | Fire on |
|---|---|---|
| `V8-CE ecommerce` | Custom Event, regex | `^(v8_view_item\|v8_select_item\|v8_begin_checkout\|v8_add_payment_info\|v8_purchase)$` |
| `V8-CE leads and content` | Custom Event, regex | `^(v8_generate_lead\|v8_newsletter_subscribe\|v8_view_search_results\|v8_video_start\|v8_video_progress\|v8_select_content)$` |
| `V8-All Pages` | Page View — All Pages | (Initialization / All Pages as required by tag type) |

### Tags

| Name | Type | Notes |
|---|---|---|
| `V8-Google Tag` | Google Tag | Tag ID `{{V8-GTM Tag ID}}`. All Pages. Pause both V7 googtags |
| `V8-Conversion Linker` | Conversion Linker | All Pages |
| `V8-GA4 Ecommerce` | GA4 Event | Event name `{{Event}}`. Send ecommerce data from dataLayer. Extra params: `value`, `currency`, `transaction_id`, `item_category`, `item_id`, `item_name`, `price`, `payment_type`. Trigger `V8-CE ecommerce` |
| `V8-GA4 Leads and Content` | GA4 Event | Event name `{{Event}}`. No ecommerce. Params: `method`, `series_name`, `search_filter`, `search_term`, `result_count`, `video_percent`, `video_title`, `content_type`, `content_id`, `content_name`. Trigger `V8-CE leads and content` |

Event tags inherit the Google Tag measurement ID (or override with `{{V8-GTM Tag ID}}`).

## GA4 Admin

1. Mark as **key events**: `v8_purchase`, `v8_generate_lead`, `v8_newsletter_subscribe`.
2. Custom dimensions (event-scoped): `method`, `series_name`, `search_filter`, `item_category`.
3. Do not enable Enhanced Measurement “site search” (would double-count `v8_view_search_results`).

## Google Ads (not this publish)

Create a conversion action named **Website purchase**, then a GTM tag:

| Field | Value |
|---|---|
| Tag | `V8-Google Ads Purchase` |
| Conversion ID | `810152772` |
| Conversion Label | the new action’s label |
| Value | `{{V8-DLV - value}}` |
| Currency | GBP |
| Transaction ID | `{{V8-DLV - transaction_id}}` |
| Trigger | Custom Event `v8_purchase` **and** value greater than 0 |

Do not fire Ads on `select_item` or `begin_checkout`. Split product type in
GA4 via ecommerce `item_category`, not as four Ads labels.

## Staging vs live

Cloudways staging and live share `GTM-P5T257F`. Publishing the workspace is
what makes tags fire on staging without Tag Assistant. Live V7 keeps its
click / thank-you / Ads tags until the V8 theme is the public site.

**Published:** container version **31** (`v8_` event prefix + purchase value)
is Live. After that publish GTM opened workspace **35**.

`V8-Google Tag` fires on the built-in **Initialization** trigger.
`V8-Conversion Linker` fires on `V8-All Pages`.

## Preview checks (staging, after publish)

1. Homepage → class detail: `v8_view_item`. Book: `v8_select_item` then `v8_begin_checkout`.
2. Confirm and Pay: `v8_add_payment_info` (`payment_type: stripe`).
3. Stripe test pay → `/booking-confirmed/` `v8_purchase` once with `value` / `currency` / item `price`. Refresh must not repeat.
4. Repeat for coupon pack, workshop date, 1:1 (`private`).
5. Contact form → `v8_generate_lead`. Refresh must not repeat.
6. Dispatch join → Mailchimp return `?dispatch=sent` → `v8_newsletter_subscribe` / `dispatch`.
7. Booking with newsletter ticked → `v8_newsletter_subscribe` / `booking_drawer` on confirmation.
8. Search: `v8_view_search_results` with term, count, filter tab.
9. Tutorial play: `v8_video_start`, then progress 25/50/75. Class/workshop films stay silent.
10. PLAY SERIES / lesson card / sibling row: `v8_select_content` with `series_name`.

In GA4 DebugView the events should appear under the same device.
