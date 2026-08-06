# Class Bookings with Stripe — WordPress.org submission copy

Use the sections below when submitting to the [WordPress Plugin Directory](https://wordpress.org/plugins/developers/add/). The **Short Description** field uses the first paragraph only. Paste the **Description**, **FAQ**, and **Changelog** blocks into the corresponding fields on the submission form (or into `readme.txt` if you add one later).

---

## Short Description (max ~150 characters)

Stripe Checkout booking for classes: capacity-aware dates, soft holds, webhooks, emails, reports, Elementor widget, and shortcode.

---

## Description

**Class Bookings with Stripe** is a WordPress plugin for studios, instructors, and venues that sell places on scheduled classes. It connects your site to **Stripe Checkout** (Stripe’s hosted payment page) so customers pick a date, choose how many seats they need, and pay securely without you building a custom cart or WooCommerce store.

The plugin is built around **Advanced Custom Fields (ACF)**. Each class is a **Class** in the admin: you set location, schedule, price, capacity, and optional extras. The booking form is embedded with a shortcode or Elementor widget. When someone clicks **Book & pay with Stripe**, the plugin creates a temporary reservation, opens a Stripe Checkout Session, and sends the customer to Stripe to complete payment. A signed webhook confirms payment, updates the booking, and can send customer and admin emails.

### Video tutorials — get started

**New to the plugin?** Watch these first:

* [Quick start: install, API keys & first booking](https://youtu.be/8B6TxXcDt2E)
* [Full installation & setup guide](https://youtu.be/7HBBGPZfZL0)

### What you can manage

* **Classes** — Weekly recurring classes, one-off events, or classes that link out to an external booking URL (ClassFor, Eventbrite, etc.) instead of the built-in form.
* **Capacity** — Per class and per date; remaining seats account for paid bookings and active soft-holds.
* **Cancelled dates** — Block individual dates via a repeater on the class; the form skips them automatically.
* **Pause bookings** — Toggle **Class is active (bookable)** off to stop new bookings without deleting the class.
* **Bookings list** — Every attempt is stored (pending, paid, expired) with customer details and Stripe session references.
* **Reports** — Yearly booking charts, upcoming attendance, and guest lists by class.
* **Form extras** — Optional waiver checkbox, Mailchimp opt-in, and custom ACF fields on the booking form.
* **Emails** — Customisable customer and admin templates with merge tags, sent via WordPress `wp_mail()`.
* **Result pages** — On activation, the plugin creates **Booking Confirmed**, **Booking Cancelled**, and **Booking Error** pages using `[clasbowi_booking_status]`.

### How Stripe Checkout integration works

Stripe Checkout is Stripe’s hosted, PCI-compliant payment flow. This plugin does **not** embed card fields on your WordPress page. Instead it uses the official **stripe-php** SDK (bundled in the plugin) to create a **Checkout Session** in `payment` mode and redirect the browser to Stripe.

**1. Customer submits the form**

The front-end form (shortcode `[clasbowi_booking class_id="123"]` or the **Class Booking with Stripe** Elementor widget) collects:

* Class date (from a capacity-aware dropdown of upcoming dates)
* Number of seats (default 1, up to seats remaining for the chosen date)
* Name and email (and optional waiver, Mailchimp opt-in, and custom fields)

Submitting calls the WordPress REST API: `POST /wp-json/clasbowi/v1/checkout`.

**2. Soft-hold and session creation**

The server validates the class, date, capacity, and form rules, then:

* Creates a **pending** booking post with a **30-minute soft-hold** so seats are reserved while the customer pays.
* Calls `Stripe\Checkout\Session::create()` with:
  * `mode: payment`
  * **Inline line items** via `price_data` (no pre-created Stripe Products required) — product name is built from your settings template and class metadata; currency is GBP.
  * `quantity` = number of seats; `unit_amount` = class price in pence.
  * `customer_email` when provided on the form.
  * `success_url` and `cancel_url` pointing at your result pages (with `{CHECKOUT_SESSION_ID}` on success for status polling).
  * `expires_at` aligned with the soft-hold window.
  * **Metadata** on the session and payment intent: `booking_id`, `class_id`, `class_date`, `seats`.

The API returns the Checkout Session URL; JavaScript redirects the customer to **checkout.stripe.com**.

**3. Customer pays on Stripe**

On Stripe’s page the customer completes payment (card, wallets, and other methods enabled in your Stripe Dashboard). Stripe may also collect or confirm billing details depending on your Checkout settings.

**4. Webhook confirms the booking**

Configure a webhook endpoint in Stripe Dashboard → Developers → Webhooks:

`https://yoursite.com/wp-json/clasbowi/v1/stripe-webhook`

Listen for:

* `checkout.session.completed` — marks the booking **paid**, stores customer details from the session, optionally subscribes to Mailchimp, and sends emails.
* `checkout.session.expired` — releases the soft-hold.
* `checkout.session.async_payment_failed` — treats the booking as expired.

The plugin verifies the **webhook signing secret** from settings before processing events. Processing is **idempotent** (duplicate `completed` events are ignored if already paid).

**5. Return URLs and status polling**

After payment, Stripe redirects to your **Booking Confirmed** page. The `[clasbowi_booking_status]` shortcode polls `GET /wp-json/clasbowi/v1/booking-status?session=cs_...` and shows confirmation details. If the webhook is delayed, the plugin can **reconcile** by fetching the session from Stripe directly.

If the customer abandons Checkout or the session expires, they land on **Booking Cancelled** or the hold is cleared via webhook/cron.

**6. Capacity and expiry**

Seats taken for a (class, date) = **paid** bookings + **pending** bookings whose `expires_at` is still in the future. A scheduled cron expires stale holds every five minutes; the webhook remains the authoritative path for paid and expired Checkout sessions.

### Front-end embedding

* **Shortcode:** `[clasbowi_booking class_id="123"]` — also accepts `class_slug`, `clasbowi_booking_id`, or legacy `clasbowi_booking` alias.
* **Status shortcode:** `[clasbowi_booking_status type="success"]` (or `cancelled`, `error`).
* **Elementor:** **Class Booking with Stripe** widget — pick a class by ID or read `clasbowi_booking_id` from the current post in loops/templates.

### Requirements and bundled dependencies

* WordPress 6.x recommended.
* **ACF** — If Advanced Custom Fields (Free or Pro) is already active, the plugin uses it. Otherwise the plugin loads a **bundled copy of ACF Free** from `vendor/acf/`.
* **Stripe account** — Publishable key, secret key, and webhook signing secret (separate test and live keys in settings).
* **Elementor** (optional) — Only needed for the Elementor widget; the shortcode works without it.
* **PHP 7.2+** (required by bundled stripe-php; PHP 7.4+ recommended).
* **stripe-php** SDK is bundled under `vendor/stripe/stripe-php/` — no Composer step on the server.

### Privacy and data

Bookings store customer name, email, class, date, seats, payment status, and optional waiver/Mailchimp/extra field data in WordPress post meta. Payment card data is handled entirely by Stripe, not stored in WordPress.

### Support

Documentation and setup notes are included in the plugin admin under **Classes → Settings**. For issues and feature requests, use the support forum on WordPress.org once published, or the contact URL on the plugin listing.

### Video tutorials — full series

Step-by-step guides on YouTube (IORoot):

* [Quick start: install, API keys & first booking](https://youtu.be/8B6TxXcDt2E)
* [Full installation & setup guide](https://youtu.be/7HBBGPZfZL0)
* [Stripe webhook setup](https://youtu.be/54MQBsW8qWA)
* [Result pages, Developer & Help tabs](https://youtu.be/8mMCkKxIH-s)
* [Creating a weekly repeating class](https://youtu.be/k5dlDzCyvoA)
* [One-off events & workshops](https://youtu.be/gzN3yzXWajo)
* [Extra fields & ACF on the booking form](https://youtu.be/BivPyMuCGbQ)
* [Email setup](https://youtu.be/dqg_DweIVAo)
* [Bookings list & reports](https://youtu.be/D2UpGlkhJWs)

---

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via **Plugins → Add New**.
2. Activate **Class Bookings with Stripe**.
3. Go to **Classes → Settings**.
4. Enter your Stripe **publishable** and **secret** keys (test or live mode).
5. In Stripe Dashboard → **Developers → Webhooks**, add an endpoint:
   `https://yoursite.com/wp-json/clasbowi/v1/stripe-webhook`
   Enable events: `checkout.session.completed`, `checkout.session.expired`, `checkout.session.async_payment_failed`.
6. Paste the **webhook signing secret** into plugin settings.
7. Create classes under **Classes → Add New**.
8. Place `[clasbowi_booking class_id="123"]` on a page, or add the **Class Booking with Stripe** Elementor widget.
9. Confirm the auto-created result pages (**Booking Confirmed**, **Booking Cancelled**, **Booking Error**) or assign your own in settings.

---

## Frequently Asked Questions

= Does this plugin store credit card numbers? =

No. Card entry and processing happen on **Stripe Checkout**. WordPress only stores booking metadata and Stripe session/payment intent IDs.

= Do I need WooCommerce? =

No. This plugin is a lightweight alternative for **single-class, date-based** bookings with Stripe Checkout.

= Do I need ACF Pro? =

No. The plugin works with **ACF Free or Pro** if already installed. If neither is active, it loads **bundled ACF Free** automatically.

= Do I need to create Products in Stripe? =

No. Each Checkout Session uses **inline `price_data`** built from the class price and your line-item title template in settings.

= Which Stripe events must the webhook listen for? =

`checkout.session.completed`, `checkout.session.expired`, and `checkout.session.async_payment_failed`.

= What happens if two people book the last seat at the same time? =

The first successful checkout creation gets a soft-hold. The second request receives a capacity error if no seats remain. Paid bookings and non-expired holds count toward capacity.

= How long is a seat held before payment? =

**30 minutes**, matching the Checkout Session `expires_at`. Expired holds are cleared by webhook and a five-minute cron job.

= Can I cancel a single date without disabling the whole class? =

Yes. On the Class edit screen, add the date to the **Cancelled dates** repeater.

= Can I send customers elsewhere instead of Stripe? =

Yes. Enable **Booking mode: use external link** on a class and set the external URL (e.g. Eventbrite).

= Does it work with Elementor? =

Yes. Register the **Class Booking with Stripe** widget when Elementor is active. You can also use the shortcode in any editor.

= Which currency is supported? =

Checkout sessions are created in **GBP** (pence). Changing currency would require a code customisation today.

= How are emails sent? =

Via WordPress **`wp_mail()`** using templates you edit under **Settings → Emails**, with merge tags such as `{customer_name}`, `{class_date}`, `{seats}`, `{amount_total}`, and others listed in the admin.

= Can I add a waiver or newsletter opt-in? =

Yes. **Form extras** in settings support a waiver checkbox (with optional policy page link), Mailchimp opt-in (API key and audience ID), and additional ACF fields shown on the form.

= What if my webhook does not fire on local development? =

Use the Stripe CLI to forward events, or a tunnel (ngrok, etc.) so Stripe can reach your REST webhook URL. The success page also polls Stripe as a fallback when webhooks are delayed.

= Are there legacy shortcode names? =

Yes. `[clasbowi_booking]` and `[clasbowi_booking_status]` remain as aliases for older sites.

---

## Changelog

= 1.0.0 =

* Initial public release.
* Class custom post type with ACF-driven fields: schedule, price, capacity, location, duration, cancelled dates, active flag, and external booking link mode.
* Booking custom post type with statuses: pending (soft-hold), paid, expired.
* Stripe Checkout Session creation via bundled stripe-php SDK with inline `price_data` line items and session metadata.
* REST API: checkout creation, signed Stripe webhook handler, booking status polling, and availability refresh.
* 30-minute soft-holds with capacity counting and cron-based hold expiry.
* Front-end booking form with date dropdown, seat quantity (1 up to remaining capacity), name, email, and optional form extras.
* Shortcodes: `[clasbowi_booking]`, `[clasbowi_booking_status]`, plus `yoga_*` aliases.
* Elementor **Class Booking with Stripe** widget with manual class ID or current-post field support.
* Auto-created result pages on activation: Booking Confirmed, Booking Cancelled, Booking Error.
* Customisable customer and admin email templates with merge tags via `wp_mail()`.
* Optional waiver acceptance and Mailchimp audience subscription after successful payment.
* Custom ACF fields on the booking form (Form extras).
* Admin reports: yearly booking chart, upcoming attendance, and guest lists.
* Bundled ACF Free bootstrap when ACF is not already active.
* Test and live Stripe keys, webhook signing secret, and configurable Stripe line-item title template.
* Stripe webhook reconciliation fallback when polling booking status after redirect.

---

## Suggested readme.txt header (optional)

If you add a `readme.txt` for WordPress.org SVN, start with:

```
=== Class Bookings with Stripe ===
Contributors: lonetraceur1
Tags: stripe, booking, classes, checkout, events, elementor, acf
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

Then paste the Short Description, Description, Installation, FAQ, and Changelog sections above into the same file using WordPress.org’s `== Section ==` headings.
