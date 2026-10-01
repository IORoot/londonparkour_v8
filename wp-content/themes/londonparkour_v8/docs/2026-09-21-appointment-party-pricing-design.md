# Appointment party-size pricing

**Date:** 2026-09-21
**Status:** approved for implementation planning
**Scope:** Class Bookings with Stripe Pro appointment classes, plus London Parkour V8 theme price display.

## Problem

Appointments charge one unit price × number of people. A 1-person session at £65 becomes £130 for two people. London Parkour needs a rate card:

| People | Per person | Session total |
|--------|------------|---------------|
| 1      | £65        | £65           |
| 2      | £45        | £90           |
| 3      | £40        | £120          |

Weekly classes and one-off events stay on the existing single unit price.

## Decisions

- Rate card lives **once on the appointment class**. Every availability slot uses it.
- Admin types **per-person** amounts. The editor shows the **session total** beside each row.
- One required row per size from **1 through Capacity**.
- On appointments, hide the class **Price** field and the per-slot **price override**.
- `class['price']` remains the **1-person** per-person rate (emails, admin columns, coupon pack unit-price matching).
- Listings show **from £X** using the **cheapest session total** (`people × per-person` for each row). With the table above that is **from £65**.
- Stripe line item: `quantity` = people, `unit_amount` = that size’s per-person rate.
- Coupon packs stay **1 person** and must still match the 1-person rate. Group bookings cannot use a pack.
- A booked appointment slot is **fully taken**. Capacity is the max party size for that one booking, not leftover seats.

## Data

Store on the class post:

```
_clasbpro_party_prices => [ 1 => 65.0, 2 => 45.0, 3 => 40.0 ]
```

Keys are integers `1…capacity`. Values are per-person amounts in the site currency (same number type as `price_gbp`). Empty keys are invalid. `0` is allowed (free at that size).

`Helpers::get_class_data()` for appointments:

- `price` — 1-person per-person rate (from the table, not ACF `price_gbp`)
- `party_prices` — the full map
- keep existing `capacity`

If Capacity increases, new keys are absent until filled; checkout for those sizes is rejected. If Capacity decreases, higher keys are dropped on save.

Do not rewrite historical bookings when the table changes. Paid amounts stay whatever Stripe charged.

## Admin

When `schedule_type` is `appointments`:

1. Hide ACF `price_gbp`.
2. Hide the slot-rule **Price override** input. Slot rules keep storing `price_gbp` as null; `Slot_Rules::rule_price_gbp()` is not used for appointment checkout totals.
3. Render a rate table under Capacity (custom UI, same pattern as slot rules — plugin still supports ACF without repeaters).
4. Rows `1…capacity`. Each row: people (label), per-person number input, live session total.
5. Saving the class sanitises and writes `_clasbpro_party_prices`.
6. After save, show an admin warning if any size in `1…capacity` is missing (empty, not zero). WordPress still saves the post; checkout rejects those sizes until the row is filled.

Weekly / one-off / external-link classes: unchanged Price field and no table.

## Checkout and booking form

Server is the source of truth.

On `POST /checkout` for appointments:

1. Resolve `seats` (packs force `seats = 1`).
2. Look up `party_prices[seats]`. If missing, 422.
3. `unit_pence = to_pence(that rate)`; `amount_total = unit_pence * seats`.
4. Do not multiply the old class/slot `price` by seats.

Pass the party-price map to the booking form (JSON on the form, same way unit price is passed today). `updateTotal()`:

- Looks up per-person rate for the selected seats.
- Sets total to rate × seats.
- Shows per-person next to total, e.g. `£45 each · £90`.

Appointment slot `dataset.unitPrice` is no longer the checkout unit for every party size. Either remove it for appointments or keep it as the 1-person rate only; JS must use the map.

## Public display (theme)

`lp_class_price_display( $class_id )` for appointments:

- Compute session total for each size: `seats * party_prices[seats]`.
- Format the **minimum** of those totals.
- Prefix with `from ` (e.g. `from £65`).
- If the table is empty/invalid, return `''` (same as a missing price today).

Weekly classes still return a single `£15` with no `from`.

SEO (`lp_seo_gbp_amount`) must still parse a number out of `from £65`.

Booking form and emails are exact, not `from`:

- `{price}` — per-person rate charged for that booking
- `{seats}` / `{quantity}` — party size
- `{amount_total}` — amount paid

## Out of scope

- Party pricing on weekly classes, one-off events, or coupon pack purchases.
- Per-slot rate cards.
- Changing Stripe product names / invoice copy beyond quantity × unit.
- Redesigning the DROP-IN price label on fact rails.
- Backfilling ACF `price_gbp` from the table (hidden on appointments; unused for checkout).

## Testing

PHP tests (plugin `eval-file` style, matching existing coupon/rate-limit tests):

- 1 / 2 / 3 people → per-person 65 / 45 / 40; totals 65 / 90 / 120 in pence.
- Missing size (capacity 4, table only to 3) → lookup fails.
- Seats below 1 or above capacity → rejected.
- `price` on class data equals the 1-person rate.
- Cheapest session total for `from` is min(seats × rate), not min(per-person).
- Weekly class checkout remains unit × seats and ignores `party_prices`.
- Pack checkout still forces 1 seat and matches the 1-person rate.

Manual:

- Appointment class editor: Price hidden, table tracks Capacity.
- Slot editor: price override hidden.
- Booking form: change people, total and “each” update; Stripe shows `2 × £45`.
- Theme class archive / single: `from £65` for the example table.
