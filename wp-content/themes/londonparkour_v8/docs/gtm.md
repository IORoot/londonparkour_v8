# Google Tag Manager — conversion funnel

Site Settings field `gtm_container_id` prints the official snippets
(`app/includes/gtm.php`). Ecommerce events are pushed from
`assets/js/utils/analytics.js`. This document is the GTM-side mapping for
container `GTM-P5T257F` (GA4 `G-98XR7R92LT`, Google Ads `AW-810152772`).

The theme cannot publish tags into the GTM UI. Prefer creating the
pieces below by hand — GTM’s importer is brittle with recipe files
(first `customEvent` enum, then a `Not Found` if the export wrapper is
incomplete). Import is optional: `docs/gtm-ecommerce-import.json`,
Admin → Import Container → **Merge** into the current workspace. Do
not **Overwrite**.

## Funnel (dataLayer)

| User action | Event | `item_category` |
|---|---|---|
| Click Book / Buy | `select_item` | `class` / `workshop` / `private` / `coupon` |
| Drawer form loaded | `begin_checkout` | same |
| Click CONFIRM AND PAY | `add_payment_info` | same (`payment_type`: `stripe` or `coupon`) |
| Land on `/booking-confirmed/` | `purchase` | same |

Each ecommerce push is preceded by `{ ecommerce: null }` so GA4 does not
merge items across steps.

`item_id` is `class:{id}`, `workshop:{id}`, `private:{id}`, or `pack:{id}`.
Currency is `GBP`.

## Live container today (version 26)

These tags still fire on the **old** markup. They will go silent on this
theme — pause them after the new funnel is live, do not delete until GA4
and Ads reports look right.

| Current tag | What it listens for | Why it misses |
|---|---|---|
| `book_class_button_click` | Click text = `BOOK NOW` | Buttons now say `BOOK` / `CONFIRM AND PAY` |
| `stripe_button_click` | Click ID contains `submit-btn` | Pay button is `.cbfs-form__button` |
| `book_pt_button_press` | Click text = `BOOK PT` | 1:1 CTA is `BOOK` |
| `adult_outdoor_class_*_purchase`, `youth_class_*_purchase`, `private_booking_purchase`, `gift_card_*_purchase` | Window Loaded + `#thankyou_Purchase` contains a product name | Confirmation page has no that ID |
| Ads conversions `r7xb…`, `rs_Q…`, `5dsU…`, `lCVY…`, `Zkk-…` | Same `#thankyou_Purchase` text | Same |

## Enable built-in variables

Variables → Configure → enable **Event**.

## Data layer variables

Create one Data Layer Variable per row (Data Layer Version 2):

| Variable name | Data Layer Variable Name |
|---|---|
| DLV - ecommerce | `ecommerce` |
| DLV - transaction_id | `ecommerce.transaction_id` |
| DLV - value | `ecommerce.value` |
| DLV - currency | `ecommerce.currency` |
| DLV - item_category | `ecommerce.items.0.item_category` |
| DLV - payment_type | `ecommerce.payment_type` |

## Triggers

One Custom Event trigger, “Some Custom Events”, regex
(RE2 — no lookahead):

| Trigger name | Event name |
|---|---|
| LP - CE ecommerce funnel | `^(select_item\|begin_checkout\|add_payment_info\|purchase)$` |

## GA4 Event tag

You already have a Google Tag (`G-98XR7R92LT`) on All Pages. Leave that.

| Field | Value |
|---|---|
| Tag type | Google Analytics: GA4 Event |
| Tag name | LP - GA4 Ecommerce |
| Measurement ID | `G-98XR7R92LT` (or inherit from the Google Tag) |
| Event name | `{{Event}}` |
| Send ecommerce data | Data Layer |
| Extra parameter | `payment_type` = `{{DLV - payment_type}}` |
| Trigger | LP - CE ecommerce funnel |

That one tag forwards all four recommended events with items, value,
currency, and `transaction_id` on `purchase`.

In GA4: Admin → Events → mark `purchase` as a **key event**. Optionally
register `item_category` as an event-scoped custom dimension
(`class` / `workshop` / `private` / `coupon`).

## Google Ads

Old Ads tags (`AW-810152772` + five conversion labels) are per-product
and keyed off `#thankyou_Purchase`. Replace with one conversion action
in Google Ads named **Website purchase**, then in GTM:

| Field | Value |
|---|---|
| Tag type | Google Ads Conversion Tracking |
| Conversion ID | `810152772` |
| Conversion Label | the new action’s label |
| Conversion value | `{{DLV - value}}` |
| Currency | `GBP` (or `{{DLV - currency}}`) |
| Transaction ID | `{{DLV - transaction_id}}` |
| Trigger | Custom Event `purchase` |

Do not fire Ads conversions on `select_item` or `begin_checkout`.
Optional secondary: `add_payment_info` where `{{DLV - payment_type}}`
equals `stripe` (exclude coupon-redeemed class bookings).

To keep reporting split by product type, either:

- four Ads conversion actions filtered on `{{DLV - item_category}}`, or
- one purchase conversion and segment in GA4 by `item_category`.

## Preview checks

1. Preview the container against `http://localhost:8102/` (or production).
2. Homepage → Book a class: `select_item` then `begin_checkout`
   (`item_category: class`).
3. Confirm and Pay: `add_payment_info` with `payment_type: stripe`.
4. Complete Stripe test pay → `/booking-confirmed/` fires `purchase`
   once (`transaction_id` is the Stripe session id). Refresh must **not**
   send a second purchase (sessionStorage guard).
5. Repeat for a coupon buy (`coupon` / `pack:{id}`), a workshop date
   (`workshop`), and a 1:1 (`private`).

In GA4 DebugView the four events should appear under the same device.
