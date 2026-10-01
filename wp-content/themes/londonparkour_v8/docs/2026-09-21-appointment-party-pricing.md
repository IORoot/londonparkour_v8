# Appointment Party-Size Pricing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (inline; user asked to implement in this session). Steps use checkbox (`- [ ]`) syntax for tracking. Do not commit unless the user asks.

**Goal:** Appointment classes charge a per-person rate that depends on party size (1 = £65, 2 = £45 each, 3 = £40 each), with listings showing `from` the cheapest session total.

**Architecture:** A `Party_Prices` domain class owns sanitise/lookup/totals. Class meta `_clasbpro_party_prices` is the store. Checkout and the booking form look up `party_prices[seats]`. Weekly classes keep unit × seats. Theme `lp_class_price_display` prefixes appointments with `from`.

**Tech Stack:** WordPress classic plugin (PHP 8), ACF Free-safe custom admin UI, vanilla JS booking form, Stripe Checkout `price_data` + quantity.

## Global Constraints

- Appointments only; weekly / one-off / external-link pricing unchanged.
- Per-person amounts in admin; session total is derived (`seats × rate`).
- Rows are `1…capacity`; empty is missing (invalid for that size); `0` is a valid free rate.
- Hide class Price and slot price override on appointments.
- `class['price']` is the 1-person rate.
- Listings: `from` + cheapest session total (not cheapest per-person).
- Stripe: quantity = people, unit_amount = per-person rate for that size.
- Packs stay 1 seat and match the 1-person rate.
- Plugin tests via `themes/londonparkour_v8/bin/wp eval-file`.
- Do not commit unless asked.

---

### Task 1: Party_Prices domain

**Files:**
- Create: `wp-content/plugins/class-bookings-with-stripe-pro/includes/class-party-prices.php`
- Modify: `wp-content/plugins/class-bookings-with-stripe-pro/class-bookings-with-stripe-pro.php` (require)
- Modify: `wp-content/plugins/class-bookings-with-stripe-pro/includes/helpers.php` (`get_class_data`)
- Modify: `wp-content/plugins/class-bookings-with-stripe-pro/includes/class-rest.php` (checkout unit)
- Test: `wp-content/plugins/class-bookings-with-stripe-pro/tests/party-prices.test.php`

**Produces:**
- `Party_Prices::META_KEY = '_clasbpro_party_prices'`
- `sanitize_map( $raw, int $capacity ): array<int, float>`
- `unit_price_for_seats( array $map, int $seats ): ?float`
- `total_pence_for_seats( array $map, int $seats ): ?int`
- `cheapest_session_total( array $map ): ?float`
- `one_person_rate( array $map ): float`
- `is_complete( array $map, int $capacity ): bool`

- [ ] Write failing tests then the class, then wire `get_class_data` + checkout.

### Task 2: Admin UI

**Files:**
- Modify: `includes/class-acf-fields.php` (hide Price on appointments; message field for table)
- Modify: `includes/class-appointment-admin.php` (render/save/notice; hide slot price)
- Modify: `includes/class-plugin.php` if Party_Prices::init is used
- Modify: `assets/cbfs-appointment-admin.js` / `.css`

### Task 3: Booking form

**Files:**
- Modify: `templates/booking-form/total-row.php`
- Modify: `assets/cbfs-booking.js` `updateTotal`

### Task 4: Theme display

**Files:**
- Modify: `wp-content/themes/londonparkour_v8/app/includes/clasbpro.php` `lp_class_price_display`

### Task 5: Verify

- `php -l` on touched PHP
- `bin/wp eval-file tests/party-prices.test.php`
- Local appointment form if Docker is up
