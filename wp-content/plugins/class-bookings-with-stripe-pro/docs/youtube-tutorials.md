# Class Bookings with Stripe — YouTube tutorial copy

Use the titles and descriptions below when uploading your nine tutorial videos. Replace placeholders in **square brackets** before publishing.

**Title format (all videos):** `Class Bookings with Stripe | [Topic]`

**Suggested playlist order** (quick start → deep dives):

1. Quick start (install + first booking)
2. Full installation and setup
3. Stripe webhook setup
4. Settings: Result pages, Developer & Help tabs
5. Creating a repeating (weekly) class
6. One-off events
7. Extra fields & ACF on the booking form
8. Email setup
9. Class bookings list & Reports

---

## 1. Stripe webhook setup

https://youtu.be/54MQBsW8qWA

### Title
**Class Bookings with Stripe | Stripe Webhook Setup**

*Alternate:* **Class Bookings with Stripe | Stripe Webhooks**

### Description
```
Stripe webhooks are how WordPress knows a customer actually paid. Without them, bookings can stay stuck on “pending” even after a successful Checkout.

In this tutorial I walk through setting up webhooks for the Class Bookings with Stripe WordPress plugin — from the Stripe Dashboard to pasting the signing secret in your site.

What you’ll learn:
• Why webhooks matter (payment confirmed, session expired, async payment failed)
• The exact endpoint URL for your site
• Which events to subscribe to: checkout.session.completed, checkout.session.expired, checkout.session.async_payment_failed
• How to copy the webhook signing secret into the plugin Stripe tab
• Quick troubleshooting (404/HTML responses, wrong site URL, test vs live)

Webhook endpoint (replace with your domain):
https://[YOUR-SITE]/wp-json/clasbowi/v1/stripe-webhook

#WordPress #Stripe #Webhooks #ClassBooking #StripeCheckout
```

---

## 2. Creating a repeating (weekly) class

https://youtu.be/k5dlDzCyvoA

### Title
**Class Bookings with Stripe | Creating a Weekly Repeating Class**

*Alternate:* **Class Bookings with Stripe | Weekly Classes**

### Description
```
This video shows how to add a weekly repeating class in Class Bookings with Stripe — the schedule customers see in the date dropdown, plus price, capacity, and the booking form on your site.

What you’ll learn:
• Add a new Class under Stripe Class → Classes
• Choose “Weekly class” as the booking type
• Set day of week, start time, price (GBP), and capacity per session
• Optional: location, description, cancelled dates, pause bookings
• Publish and embed with shortcode [clasbowi_booking class_id="123"] or the Elementor widget

Perfect for yoga studios, fitness classes, workshops that run every week.

#WordPress #YogaBooking #FitnessClass #Stripe #RecurringClasses
```

---

## 3. Quick start — install, Stripe API keys & first booking form

https://youtu.be/8B6TxXcDt2E

### Title
**Class Bookings with Stripe | Quick Start: Install, API Keys & First Booking**

*Alternate:* **Class Bookings with Stripe | Quick Start**

### Description
```
New to Class Bookings with Stripe? This is the fastest path from zero to a working booking form on your WordPress site.

In about 10 minutes we cover:
• Install and activate the plugin
• Enter Stripe publishable + secret keys (test mode)
• Add your first Class with a simple schedule
• Place the booking shortcode or Elementor widget on a page
• Complete a test payment on Stripe Checkout

You’ll see the full flow: customer picks a date → soft-hold → redirect to Stripe → return to your Booking Confirmed page.

Requirements: WordPress 6.0+, Stripe account, PHP 7.4+. ACF is bundled if you don’t already use Advanced Custom Fields.

Shortcode: [clasbowi_booking class_id="YOUR_CLASS_ID"]

#WordPress #StripeCheckout #QuickStart #ClassBooking #Tutorial
```

---

## 4. Result pages, Developer & Help tabs

https://youtu.be/8mMCkKxIH-s

### Title
**Class Bookings with Stripe | Result Pages, Developer & Help Tabs**

*Alternate:* **Class Bookings with Stripe | Settings Tour**

### Description
```
A guided tour of the advanced settings tabs in Class Bookings with Stripe — everything beyond Stripe keys and emails.

Result pages tab
• Booking Confirmed, Booking Cancelled, and Booking Error pages
• Auto-created on activation; how to change which pages customers land on after Checkout
• Shortcode [clasbowi_booking_status] for success / cancelled / error states

Developer tab
• How webhooks update booking status (paid, expired)
• REST API routes (/checkout, /stripe-webhook, availability)
• Template overrides in your theme
• Hooks for custom integrations

Help tab
• Built-in documentation: Stripe API keys, webhooks, email delivery tips
• Copy-paste webhook URL and event list
• Local testing notes (Stripe CLI, site URL)

#WordPress #PluginSettings #Stripe #Developer #Webhooks
```

---

## 5. One-off events

https://youtu.be/gzN3yzXWajo

### Title
**Class Bookings with Stripe | One-Off Events & Workshops**

*Alternate:* **Class Bookings with Stripe | One-Off Events**

### Description
```
Not every class repeats weekly. This tutorial covers one-off events — workshops, pop-ups, retreats, or any class that runs only between specific start and end dates.

What you’ll learn:
• Select “One-off event” as the booking type
• Set event start date, end date, and time
• How the date dropdown shows only valid occurrences in that range
• Capacity and pricing for a single run or multi-day window
• Difference between weekly classes and one-off events in the admin

Same Stripe Checkout flow: customers book seats, pay on Stripe, and webhooks confirm the booking.

#WordPress #Workshop #EventBooking #Stripe #OneOffEvent
```

---

## 6. Full installation and setup guide

https://youtu.be/7HBBGPZfZL0

### Title
**Class Bookings with Stripe | Full Installation & Setup Guide**

*Alternate:* **Class Bookings with Stripe | Complete Setup**

### Description
```
The comprehensive setup guide for Class Bookings with Stripe — everything you need for a production-ready class booking site.

We cover:
1. Requirements (WordPress, PHP, Stripe account, optional Elementor)
2. Install plugin (upload zip or Plugins → Add New)
3. Stripe Dashboard: API keys (test and live)
4. Plugin settings: mode, keys, webhook signing secret
5. Stripe webhook endpoint and required events
6. Result pages (confirmed / cancelled / error)
7. Create classes (weekly, one-off, or external booking link)
8. Form extras, emails, and reports (overview)
9. Shortcode & Elementor widget
10. Go live checklist (live keys, live webhook, SMTP for email)

Webhook URL:
https://[YOUR-SITE]/wp-json/clasbowi/v1/stripe-webhook

Events: checkout.session.completed, checkout.session.expired, checkout.session.async_payment_failed

Currency: GBP (pence) by default. Card data stays on Stripe — not stored in WordPress.

#WordPress #Stripe #FullTutorial #ClassBooking #SetupGuide
```

---

## 7. Extra fields & ACF on the booking form

https://youtu.be/BivPyMuCGbQ

### Title
**Class Bookings with Stripe | Extra Fields & ACF**

*Alternate:* **Class Bookings with Stripe | Form Extras & ACF**

### Description
```
Collect more than name and email on your class booking form. This video covers the Form extras tab and Advanced Custom Fields (ACF) integration.

Built-in form extras:
• Waiver checkbox (optional required text)
• Mailchimp newsletter opt-in (API key, audience, tags)
• Form layout options

Custom ACF fields:
• Create an ACF Field Group in WordPress
• Location rule: “Class Bookings with Stripe → Booking form class ID”
• Assign fields to a specific class ID
• Supported types: text, email, number, textarea, select, radio, true/false
• Show custom answers in emails with merge tags: {acf:field_xxxxx}, {field_xxxxx}, {extra_fields}

ACF Free or Pro works; the plugin bundles ACF Free if ACF isn’t already installed.

#WordPress #ACF #AdvancedCustomFields #Mailchimp #BookingForm
```

---

## 8. Email setup

### Title
**Class Bookings with Stripe | Email Setup**

https://youtu.be/dqg_DweIVAo

*Alternate:* **Class Bookings with Stripe | Email Templates**

### Description
```
Configure what customers and you receive after a successful Stripe payment.

Topics covered:
• Emails tab in plugin settings
• Customer confirmation email (subject + HTML body)
• Admin notification email and recipient address
• Merge tags: customer name, class title, date, time, seats, amount, booking ID, Stripe session link, and ACF extras
• How emails are sent (WordPress wp_mail — use an SMTP plugin for reliable delivery)
• When emails fire (after checkout.session.completed webhook)

Tip: Test with a real booking in Stripe test mode and check spam folders until SMTP is configured.

#WordPress #EmailMarketing #BookingConfirmation #Stripe #wp_mail
```

---

## 9. Class bookings list & Reports

https://youtu.be/D2UpGlkhJWs

### Title
**Class Bookings with Stripe | Bookings List & Reports**

*Alternate:* **Class Bookings with Stripe | Reports Dashboard**

### Description
```
Day-to-day operations: view every booking attempt and use the Reports screen for attendance and trends.

Bookings (custom post type)
• Pending, paid, and expired statuses
• Customer details and class date
• Stripe Checkout session ID and payment metadata
• Soft-holds (30-minute window while customer pays)

Reports dashboard (Stripe Class → Reports)
• Yearly booking charts — filter by year
• Upcoming attendance — who’s booked for future dates
• Guest lists by class — export-friendly view for check-in
• How capacity and paid bookings feed the numbers

Great for studio owners tracking revenue patterns and preparing for each session.

#WordPress #Reports #ClassManagement #Attendance #Stripe
```

---

## Channel-wide defaults (optional)

Paste at the end of every description, or save as a YouTube upload default:

```
---
Class Bookings with Stripe — WordPress plugin for selling class places with Stripe Checkout (no WooCommerce).

Subscribe for more WordPress and Stripe tutorials.
```

### Suggested tags (pick 5–8 per video)
`WordPress`, `Stripe`, `Stripe Checkout`, `class booking`, `booking plugin`, `yoga booking`, `fitness studio`, `ACF`, `Elementor`, `webhook`, `IORoot`

### Thumbnail text ideas
| Video | Short text on thumb |
|-------|---------------------|
| Webhooks | **Stripe Webhooks** |
| Weekly class | **Weekly Classes** |
| Quick start | **10 Min Setup** |
| Settings tabs | **Settings Tour** |
| One-off | **One-Off Events** |
| Full setup | **Full Setup** |
| ACF extras | **Custom Fields** |
| Emails | **Email Setup** |
| Reports | **Bookings & Reports** |
