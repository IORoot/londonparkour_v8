# Cloudways caching — londonparkour_v8

How this site should be cached on Cloudways so marketing pages are fast and
booking stays live.

Do this on **each** application (staging and live). Varnish rules are
per-application, not per-server.

Related: [staging DB import](superpowers/specs/2026-08-24-cloudways-staging-db-import-design.md).

## Goal

Cache anonymous HTML. Do **not** cache Class Bookings With Stripe Pro JSON or
the coupon cookie.

| Layer | Switch | Why |
|---|---|---|
| Varnish | **On** | Page cache. This is the one that matters. |
| Redis | **On** | Object cache (database). Safe for clasbpro. |
| Object Cache Pro | **On** (2GB+ servers) | WordPress ↔ Redis. Cloudways installs it. |
| Memcached | **Off** | Do not run a second object cache. Redis wins; Memcached just wastes RAM. |
| Breeze plugin | **Keep installed** | Purges Varnish when you edit a page. |
| Breeze Cache System | **Off** | Varnish already stores HTML. Two page caches means two places to exclude. |
| Breeze JS/CSS minify | **Off** | Vite already builds `assets/dist`. Minify here can break the booking drawer. |
| WP Rocket / LiteSpeed / other cache plugins | **Do not install** | Fights Varnish and Breeze. |
| Cloudflare “Cache Everything” | **Off** unless you also exclude `/wp-json/clasbpro/` | Same REST risk as Varnish. |

Random header images, decode titles, and Google-review rotation happen in the
browser. They keep working with HTML cached. Seat counts and “Today / Tomorrow”
on the homepage **will** look stale until TTL or a purge — that is copy, not
checkout. Checkout re-reads seats from the database.

## 1. Server — Redis on, Memcached off, Varnish on

1. Cloudways → **Servers** → this site’s server.
2. Left menu → **Manage Services**.
3. **Varnish** → **Start** / leave running. Use **Purge** after the rules below.
4. **Redis** → **Start** / leave running.
5. **Memcached** → **Stop**.

On a 2GB+ server, Object Cache Pro activates when Redis is running. In
WordPress, **Settings → Object Cache** (or the Object Cache Pro admin notice)
should say it is connected. Do not also activate the free “Redis Object Cache”
plugin.

## 2. Application — Varnish rules

1. Cloudways → **Applications** → the WordPress app (repeat for staging and live).
2. **Application Settings** → **Varnish Settings**.
3. Leave **Ignore Query String** / **Ignore Query Strings** **off**.
   `/classes/?week=1` and Stripe return URLs (`session`, `clasbpro_pack_purchase`)
   must be distinct cache keys. If that toggle is on, every week of the agenda
   shows week 0 and payment-success pages can mix visitors.
4. **Add New Rule** for each row:

| Type | List / method | Value | Why |
|---|---|---|---|
| URL | Exclude | `\/wp-json\/clasbpro` | All booking GETs: calendars, slots, availability, `panel-form`, pack-status, booking-status |
| Cookie | Exclude | `clasbpro_pack` | Coupon already attached in this browser |

Cloudways URL fields are regex. `\/wp-json\/clasbpro` matches
`/wp-json/clasbpro/v1/…`.

Keep Cloudways’ defaults if they are already there (`wp-admin`, `wp-login.php`,
`admin-ajax.php`, `wordpress_logged_in`). Do not delete them.

5. Save. Then **Servers → Manage Services → Varnish → Purge**.

There is no per-rule TTL in the UI. HTML lives until Varnish expires it or
Breeze purges it. Keep Breeze’s purge interval at **4 hours** (below) so the
`wp_rest` nonce in the page does not go stale. WordPress nonces tick every 12
hours; a page cached longer than that 403s the booking drawer.

## 3. WordPress — Breeze

Breeze is preinstalled. Do not replace it.

**Settings → Breeze** (or **Breeze** in the admin bar).

### Basic Options

| Setting | Value |
|---|---|
| Cache System | **Disable** |
| Purge Cache After | **240** minutes (4 hours), if the field is still shown |
| Gzip Compression | Enable |
| Browser Cache | Enable |
| Cache Logged-in Users | Disable |
| Mobile Caching | Disable (same HTML for all) |

### File Optimization

Leave **HTML / CSS / JS minify** off. Same for **JS delay** / **defer**. The
theme already ships hashed Vite files; extra minify has broken booking JS on
similar stacks.

### Advanced Options

**Never Cache URL(s)** — belt and braces if you later turn Cache System on,
and useful if Cloudflare sits in front:

```
/wp-json/clasbpro/*
```

One URL per line. Include the full `https://…/wp-json/clasbpro/*` as well if
Breeze does not accept a path-only wildcard on your version.

### Varnish (inside Breeze)

| Setting | Value |
|---|---|
| Auto Purge Varnish | **Enable** |
| Varnish IP | leave the default Cloudways fills in |

Save, then **Purge** from the Breeze admin bar once.

## 4. What this does *not* freeze

These stay random / live because they run in the browser or hit uncached REST:

- Hero Ken Burns start image (`Math.random()` in JS; all slides are in HTML)
- Decode titles (`data-motion-decode`)
- Google reviews rotation (`quoteBoard.js`; posts are `lp_testimonial`, not a live Google call)
- Drawer booking / coupon / PT forms (`GET /wp-json/clasbpro/v1/panel-form`)
- Calendars, slots, remaining seats inside the drawer
- Stripe Checkout and the webhook (`POST` — Varnish does not store POST)

These **do** freeze until TTL or purge (cosmetic):

- Homepage “next class”, “Today / Tomorrow”, “N left”
- `/classes/?week=` agenda
- Class and workshop singles (next date, seat copy)
- Pricing “why” line (`array_rand()` in PHP)
- Contact form nonce (same 12-hour nonce window as booking)

A booking does **not** purge those HTML seat counts. Treat “N left” on a cached
page as approximate. Money is still correct: `POST /checkout` re-reads capacity
before creating a Stripe session.

## 5. After you change the site

| Change | What to purge |
|---|---|
| Edit a page / class in WP | Breeze auto-purge is enough |
| Git Pull of theme CSS/JS | Breeze **Purge** + **Servers → Varnish → Purge** |
| Import staging DB | `cloudways_load.sh` already flushes WP cache; still **Varnish → Purge** |
| Booking plugin / coupon change | Varnish purge so listings match |

## 6. Check it worked

From a private window (logged out, no `clasbpro_pack` cookie):

```bash
# Homepage should be cached after the second hit
curl -sI https://YOUR-DOMAIN/ | grep -i 'x-cache'

# Booking JSON must never be a Varnish HIT
curl -sI 'https://YOUR-DOMAIN/wp-json/clasbpro/v1/availability?class_id=1' | grep -i 'x-cache'
```

Expect `HIT` (or similar) on `/` the second time. Expect `MISS` / bypass /
no Varnish hit on `/wp-json/clasbpro/…`.

Then in the browser:

1. Open a class → Book. Calendar and seats should change if you book the last
   spot in another tab and reopen the drawer (without reloading the page cache).
2. Buy or attach a coupon. Reload the homepage — CTA should offer the pack, not
   pay-in-full. If it still says pay-in-full, the `clasbpro_pack` cookie exclude
   is missing.
3. `/classes/?week=1` must show a different week to `?week=0`.
4. Leave a tab open ~5 hours, then Book. Drawer must not 403. If it does, TTL
   is too long.

## 7. If something breaks

| Symptom | Likely cause |
|---|---|
| Drawer “Could not load form” / 403 | Stale `wp_rest` nonce → HTML TTL > 12h, or REST cached |
| Sold-out date still looks open in the calendar | `/wp-json/clasbpro/` not excluded |
| Pay-in-full after buying a coupon | `clasbpro_pack` cookie not excluded |
| Every agenda week looks the same | Ignore Query String is on |
| CSS missing after Pull | Not caching — `assets/dist` not deployed. See theme `bin/README.md` |
| Logged-in admin sees old pages | Unusual; confirm `wordpress_logged_in` is still a default exclude |

Fix: add the missing rule, **Purge Varnish**, hard-reload.

## 8. Do not

- Exclude the whole site from Varnish “until booking works”. Exclude JSON and
  the pack cookie instead.
- Turn on **Ignore Query String**.
- Run Redis and Memcached as two WordPress object caches.
- Put Cloudflare Cache Everything in front without the same `/wp-json/clasbpro/`
  bypass.
- Create `.staging-import-enabled` on **live** (that file is for DB import,
  not cache).
