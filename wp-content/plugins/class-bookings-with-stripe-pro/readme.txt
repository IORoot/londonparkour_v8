=== Class Bookings with Stripe Pro ===
Contributors: lonetraceur1
Tags: stripe, booking, classes, checkout, elementor
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stripe Checkout booking for classes: capacity-aware dates, soft holds, webhooks, emails, reports, Elementor widget, and shortcode.

== Description ==

https://www.youtube.com/watch?v=8B6TxXcDt2E

**Class Bookings with Stripe Pro** helps studios, instructors, and venues sell places on scheduled classes using **Stripe Checkout** (Stripe’s hosted payment page). Customers pick a date, choose seats, and pay securely without WooCommerce or a custom cart.

The plugin uses **Advanced Custom Fields (ACF) Free** (built in). Each bookable class has schedule, price, capacity, and optional extras. Embed the form with a shortcode or Elementor widget. When a customer clicks **Book & pay with Stripe**, the plugin creates a soft-hold, opens a Stripe Checkout Session, and redirects to Stripe. A signed webhook marks the booking paid and can send customer and admin emails.

= Video tutorials — get started =

**New to the plugin?** Watch these first:

* [Quick start: install, API keys & first booking](https://youtu.be/8B6TxXcDt2E)
* [Full installation & setup guide](https://youtu.be/7HBBGPZfZL0)

= Features =

* **Classes** — Weekly recurring classes, one-off events, or external booking links.
* **Capacity** — Per class and date; counts paid bookings and active soft-holds.
* **Cancelled dates** — Block individual dates without disabling the class.
* **Bookings** — Pending, paid, and expired statuses with Stripe session references.
* **Reports** — Yearly charts, upcoming attendance, and guest lists.
* **Form extras** — Waiver, Mailchimp opt-in, and custom ACF fields.
* **Emails** — Customer and admin templates with merge tags via `wp_mail()`.
* **Result pages** — Booking Confirmed, Cancelled, and Error pages on activation.
* **Config-file Stripe keys** — Optional environment variables / `wp-config.php` constants so secrets are not stored in the database.

= Stripe Checkout =

The plugin uses the bundled **stripe-php** SDK. It does not embed card fields on your site. Checkout Sessions use inline `price_data` (GBP), session metadata, and a 30-minute soft-hold aligned with session expiry. Configure webhooks for `checkout.session.completed`, `checkout.session.expired`, and `checkout.session.async_payment_failed`.

= Shortcodes =

* `[clasbpro_booking class_id="123"]`
* `[clasbpro_booking_status type="success"]` (or `cancelled`, `error`)

= Requirements =

* WordPress 6.0+
* Stripe account (keys and webhook signing secret)
* ACF Free or Pro (bundled ACF Free loads if ACF is not active)
* Elementor (optional, for the Elementor widget)

= Video tutorials — full series =

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

== External services ==

This plugin connects to third-party services to process payments and, optionally, add customers to a mailing list. You must configure your own accounts and API keys for these services in **Class Bookings with Stripe → Settings**.

= Stripe =

This plugin uses [Stripe](https://stripe.com/) Checkout to collect payments. Stripe hosts the payment page; card details are entered on Stripe’s site, not in WordPress.

**When data is sent**

* When a customer submits the booking form, your WordPress site calls the Stripe API to create a Checkout Session, then redirects the browser to Stripe’s hosted checkout page.
* When payment completes, fails, or the session expires, Stripe sends signed webhook events to your site (`/wp-json/clasbpro/v1/stripe-webhook`).
* If webhooks are delayed, the plugin may call the Stripe API again to retrieve the Checkout Session status when the customer returns to your success page.

**What data is sent**

* To Stripe’s API (server-side): your Stripe secret key (authentication), customer name and email, class and booking details (class name, date, time, location, seats, booking ID), line-item title and price (GBP), and success/cancel return URLs.
* On Stripe Checkout (customer-facing): payment and billing details the customer enters on Stripe’s hosted page.
* From Stripe to your site (webhooks): event payloads about the Checkout Session and payment status (no card numbers are stored in WordPress).

This service is provided by Stripe, Inc.: [Terms of Service](https://stripe.com/legal/ssa), [Privacy Policy](https://stripe.com/privacy).

= Mailchimp =

If enabled in plugin settings, this plugin can subscribe customers to a Mailchimp audience when they opt in on the booking form.

**When data is sent**

* After a booking is marked paid (via Stripe webhook), and only when all of the following are true: Mailchimp opt-in is enabled in settings, you have saved a Mailchimp API key and Audience ID, the customer checked the opt-in box on the form, and the booking has not already been synced.

**What data is sent**

* To the Mailchimp Marketing API: the customer’s email address, first name, last name (parsed from the booking name), and subscription status (`pending` if double opt-in is enabled, otherwise `subscribed`). Your Mailchimp API key is sent in the request for authentication.

This service is provided by Intuit Mailchimp: [Terms of Use](https://mailchimp.com/legal/terms/), [Privacy Policy](https://mailchimp.com/legal/privacy/).

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install via **Plugins → Add New**.
2. Activate **Class Bookings with Stripe**.
3. Open **Class Bookings with Stripe → Settings** in the admin menu.
4. Enter Stripe publishable and secret keys (test or live), **or** define them in `wp-config.php` / the environment (see FAQ). Leave a settings field empty to use the config value; a pasted key overrides it.
5. In Stripe Dashboard → **Developers → Webhooks**, add an endpoint: `https://yoursite.com/wp-json/clasbpro/v1/stripe-webhook` with events `checkout.session.completed`, `checkout.session.expired`, and `checkout.session.async_payment_failed`.
6. Paste the webhook signing secret into plugin settings, or set `CLASBPRO_STRIPE_WEBHOOK_SECRET`.
7. Add classes under **Class Bookings with Stripe → Classes**.
8. Place `[clasbpro_booking class_id="123"]` on a page or add the **Class Booking with Stripe** Elementor widget.

== Frequently Asked Questions ==

= Can I set Stripe keys in wp-config.php? =

Yes. This is the recommended way on hosts that replace the database on deploy (for example Cloudways staging). Keys are not written to the database.

Resolution order for each key:

1. Environment variable (`getenv`, `$_ENV`, `$_SERVER`)
2. Matching PHP `define()` in `wp-config.php`
3. A value pasted into **Class Bookings with Stripe → Settings** (overrides 1 and 2)

Leave the settings fields empty to keep using the environment / wp-config values. Secret keys saved in settings are stored encrypted; if they cannot be decrypted after a database import, the plugin falls back to the environment / wp-config value.

Define these constants in `wp-config.php` (same names as environment variables):

`CLASBPRO_STRIPE_SECRET_TEST` — Stripe secret key (`sk_test_…`)
`CLASBPRO_STRIPE_SECRET_LIVE` — Stripe secret key (`sk_live_…`)
`CLASBPRO_STRIPE_WEBHOOK_SECRET` — Webhook signing secret (`whsec_…`)
`CLASBPRO_STRIPE_PUB_TEST` — Publishable key (`pk_test_…`)
`CLASBPRO_STRIPE_PUB_LIVE` — Publishable key (`pk_live_…`)

Example:

`define( 'CLASBPRO_STRIPE_SECRET_LIVE', 'sk_live_…' );`

Optional: `CLASBPRO_STRIPE_ENCRYPTION_KEY` is a dedicated passphrase for encrypting keys that *are* saved in the database, so they survive WordPress salt rotation.

= Does this plugin store credit card numbers? =

No. Card data is handled on Stripe Checkout. WordPress stores booking metadata only.

= Do I need WooCommerce? =

No.

= Do I need ACF Pro? =

No. ACF Free or Pro works; bundled ACF Free loads if ACF is not already active.

= Do I need Stripe Products? =

No. Checkout uses inline `price_data` from each class price.

= Which webhook events are required? =

`checkout.session.completed`, `checkout.session.expired`, and `checkout.session.async_payment_failed`.

= How long is a seat held? =

30 minutes, matching the Checkout Session expiry. Stale holds are cleared by webhook and cron.

= Which currency is supported? =

Choose your currency under Class Bookings with Stripe → Settings → Stripe. Supported currencies include GBP, USD, EUR, AUD, CAD, NZD, CHF, SEK, NOK, DKK, JPY, and KRW. Default is GBP.

= Does it work with Elementor? =

Yes. Use the **Class Booking with Stripe** widget or the shortcode.

== Screenshots ==

1. Booking form on the front end.
2. Class Bookings with Stripe settings.
3. Reports dashboard.

== Changelog ==

= 1.0.0 =

* Initial public release.
* Class and booking custom post types with ACF-driven fields.
* Stripe Checkout Sessions via bundled stripe-php SDK.
* REST checkout, signed webhooks, status polling, and availability API.
* Soft-holds, capacity limits, and cron expiry.
* Shortcodes, Elementor widget, result pages, and email templates.
* Reports, waiver, Mailchimp opt-in, and custom form fields.
* Bundled ACF Free when ACF is not active.
* Stripe keys from environment variables or wp-config.php, with settings fields as an override.

== Upgrade Notice ==

= 1.0.0 =

Initial release.
