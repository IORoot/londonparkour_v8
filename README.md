# London Parkour V8

WordPress site for [londonparkour.com](https://londonparkour.com) — classes, tutorials, bookings, and the Concourse design system.

This repo is `wp-content`-shaped (`themes/`, `plugins/`, Docker). The real work is the classic theme `wp-content/themes/londonparkour_v8/` (ACF Pro, PHP templates, Vite + Tailwind v4 + daisyUI). It is not a block theme.

Local site: **http://localhost:8102** · admin `admin` / `admin`.

## How it was built

Three layers, in order. Fidelity to the design is the product — class strings in the theme are copied from Storybook, not rewritten.

1. **[Pen.dev](https://pen.dev)** — layout, type, colour, and copy live in the Pencil file (`london_parkour_V7.pen`). That file is the source of truth for screens and wording.
2. **Storybook** — the Concourse design system (`londonparkour_v8_storybook_v2`) is the HTML + Tailwind implementation of those screens. Components, tokens, and four themes (dark/light × yellow/green) are signed off there first.
3. **WordPress on Cloudways** — this repo ports that Storybook into a classic theme and hosts it on Cloudways (staging + live).

Elliott at **[We Are Bold](http://www.wearebold.co.uk/)** built the Design-system in Pen.dev / Storybook.

Theme internals and the porting contract: [`wp-content/themes/londonparkour_v8/README.md`](wp-content/themes/londonparkour_v8/README.md).

## Local

```bash
docker compose up -d
wp-content/themes/londonparkour_v8/bin/bootstrap.sh
wp-content/themes/londonparkour_v8/bin/wp lp seed
cd wp-content/themes/londonparkour_v8 && npm install && npm run build
```

Commit `assets/dist` — Cloudways does not run `npm`.

## Deploy to Cloudways staging

Staging is a **separate Cloudways application**. Git Pull runs on staging only, never live.

1. Push to `master`.
2. Open the Cloudways dashboard for **staging** (`londonparkour_staging`).
3. **Deployment via Git** → **Pull**.
4. The database updates every minute via cron (`database/cloudways_load.sh`). No SSH import needed.
5. Application Settings → General → Purge site cache

If the page still looks stale after Pull: **Servers → Manage Services → Varnish → Purge**. `uploads/` is gitignored — media 404s on staging until copied.

Caching rules: [`docs/cloudways-caching.md`](docs/cloudways-caching.md). Staging import design: [`docs/superpowers/specs/2026-08-24-cloudways-staging-db-import-design.md`](docs/superpowers/specs/2026-08-24-cloudways-staging-db-import-design.md).
