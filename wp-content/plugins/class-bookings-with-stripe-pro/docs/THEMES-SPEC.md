# Form Themes Gallery — Technical Specification

**Plugin:** Class Bookings with Stripe Pro (`clasbpro`)  
**Version:** 1.0.0  
**Status:** Implemented

## Overview

Pro-only admin feature that provides a **template gallery** for booking form appearance. Users can switch form look, layout, and style with one click, copy theme files into their active WordPress theme, browse source code, download packs as ZIP, and preview themes on the front end.

The free plugin (`clasbowi`) does not include this feature.

## Goals

1. **One-click enable** — switch live form styling without editing theme files.
2. **Install to theme** — copy pack files into `themes/{child}/class-bookings-with-stripe/` for full ownership.
3. **View code** — browse and copy individual files from the admin.
4. **Download ZIP** — export a complete pack for offline use.
5. **Preview** — see a working form with a theme before enabling it.

## Architecture

### Theme source modes

Stored in option `clasbpro_theme_settings`:

| Key | Values | Description |
|-----|--------|-------------|
| `theme_source` | `default` \| `gallery` \| `theme` | Which resolution path is active |
| `active_gallery_theme` | string (slug) | Selected gallery pack when source is `gallery` |

| Mode | Runtime behaviour |
|------|-------------------|
| **Plugin default** | Core plugin templates + `cbfs-booking.css` only. Theme folder overrides ignored. |
| **Gallery theme** | Active pack loaded from `plugins/.../themes/{slug}/` via filters. Theme folder ignored. |
| **Theme files** | Standard `locate_template()` resolution under `get_stylesheet_directory()/class-bookings-with-stripe/`. Plugin auto-loads `bootstrap.php` if present. |

### Hybrid workflows

| Action | Effect |
|--------|--------|
| **Enable** | Sets `theme_source=gallery` and `active_gallery_theme={slug}`. Live site updates immediately. |
| **Install to theme** | Copies pack files to active stylesheet directory. Does not change `theme_source` unless user accepts post-install prompt. |
| **View code** | Expandable panel: file list, syntax-highlighted content, per-file copy. |
| **Download ZIP** | Streams full pack archive. |
| **Live preview** | Opens front-end preview page with nonce; renders form with preview theme for that request only. |

### Pack directory layout

Each bundled pack mirrors the install layout:

```
class-bookings-with-stripe-pro/themes/{slug}/
├── theme.json
├── bootstrap.php              # optional — filters, extra enqueues
├── screenshot.svg             # card thumbnail
└── class-bookings-with-stripe/
    ├── booking-form.php       # optional layout override
    ├── style.css              # auto-enqueued when present (hybrid)
    ├── credit-card.svg.php    # optional partials
    └── assets/                # images, etc.
```

### `theme.json` manifest

```json
{
  "slug": "yoga-split",
  "name": "Yoga Split Screen",
  "description": "Split-screen layout with hero image and studio styling.",
  "version": "1.0.0",
  "provides": ["booking-form"],
  "screenshot": "screenshot.svg"
}
```

- **`provides`** — tiered list of what the pack overrides (`booking-form`, `booking-status`, component slugs). Loader only replaces templates the pack ships.
- Future packs may add `booking-status` without requiring all packs to be complete.

### Asset loading (hybrid)

1. **Plugin auto-enqueues** `class-bookings-with-stripe/style.css` when present (after base `clasbpro` handle), for both gallery and theme-file modes.
2. **`bootstrap.php`** handles non-standard assets (images, component CSS, label filters, etc.).

Helpers:

- `Theme_Loader::asset_url( $relative )` — base URL for pack/theme assets.
- `Theme_Loader::asset_path( $relative )` — base filesystem path.

### Template resolution

`Theme_Loader` hooks `clasbpro_template_path` and `clasbpro_component_path` at priority 20:

- **`default`** — forces plugin `templates/` paths.
- **`gallery`** — resolves from active pack when `provides` includes the requested template.
- **`theme`** — passes through `Template_Loader` result (theme overrides first).

`Template_Loader::THEME_DIR` remains `class-bookings-with-stripe`.

### Bootstrap loading

| Mode | When loaded |
|------|-------------|
| Gallery | `init` priority 20 — `{pack}/bootstrap.php` |
| Theme files | `init` priority 20 — `{stylesheet}/class-bookings-with-stripe/bootstrap.php` |

Only plugin-bundled packs are loaded in gallery mode (trusted source).

### Install to theme

**Destination:** `get_stylesheet_directory()/class-bookings-with-stripe/` (+ `bootstrap.php` at theme subfolder root’s parent — i.e. sibling to `class-bookings-with-stripe/`).

Actually per spec: bootstrap.php installs to pack root level alongside class-bookings-with-stripe:

```
themes/{child}/
├── class-bookings-with-stripe/   # templates, css, assets
└── (bootstrap.php is inside pack root — copied to themes/{child}/class-bookings-with-stripe/../)
```

Pack layout has `bootstrap.php` at pack root; install copies:

- `{pack}/class-bookings-with-stripe/*` → `{stylesheet}/class-bookings-with-stripe/`
- `{pack}/bootstrap.php` → `{stylesheet}/class-bookings-with-stripe/bootstrap.php` **OR** `{stylesheet}/class-bookings-with-stripe/../bootstrap.php`

Per agreed spec, bootstrap lives at pack root. Install copies to:

- `{stylesheet}/class-bookings-with-stripe/` (directory contents)
- `{stylesheet}/class-bookings-with-stripe/bootstrap.php` if we nest it there for auto-load

**Implementation:** `bootstrap.php` is stored at `{pack}/bootstrap.php` and installed to `{stylesheet}/class-bookings-with-stripe/bootstrap.php` so the plugin auto-loader finds it in theme-file mode.

**Conflict handling:** merge with confirmation; backup existing folder to `class-bookings-with-stripe.backup-{Y-m-d-His}/` before write.

**Post-install:** optional notice — “Switch to Theme files?”

### Preview

- Hidden page slug: `clasbpro-theme-preview`
- Shortcode: `[clasbpro_theme_preview]`
- URL: `{preview_page}?clasbpro_theme_preview={slug}&_wpnonce={nonce}`
- Uses first published `clasbpro_class` with bookable dates
- `Theme_Loader` applies preview slug for that request only (does not persist settings)

### Admin UI

- **Menu:** Stripe Class → **Themes** (`clasbpro-themes`)
- **Capability:** `manage_options` (filterable via `clasbpro_themes_capability`)
- **Banner:** three-way `theme_source` control + status for theme-files path
- **Grid:** cards with screenshot, name, description, provides badges, actions
- **Card actions:** Enable, Install, Live preview, View files, Download ZIP

### v1 gallery packs

Migrated from `twentytwentyfive` examples:

| Slug | Origin | Provides |
|------|--------|----------|
| `custom-labels` | Example 1 | labels via bootstrap |
| `styled-button` | Example 2 | CSS only |
| `single-column` | Example 3 | `booking-form` |
| `secure-checkout` | Example 4 | `booking-form` |
| `yoga-split` | Example 5 | `booking-form` |

### PHP classes

| Class | File | Responsibility |
|-------|------|----------------|
| `Theme_Registry` | `includes/class-theme-registry.php` | Scan packs, read manifests, list files |
| `Theme_Loader` | `includes/class-theme-loader.php` | Runtime resolution, CSS, bootstrap |
| `Theme_Installer` | `includes/class-theme-installer.php` | Merge install, backup, ZIP export |
| `Theme_Preview` | `includes/class-theme-preview.php` | Preview page, URL, class resolution |
| `Themes` | `includes/class-themes.php` | Admin menu, UI, form handlers |

### Security

- All admin actions require `manage_options` and nonces.
- Preview requires valid nonce and `clasbpro_theme_preview` query arg.
- File viewer only exposes files listed in pack manifest scan (no path traversal).
- ZIP generation only from registered pack directories.

### Hooks

| Hook | Purpose |
|------|---------|
| `clasbpro_themes_capability` | Filter admin capability (default `manage_options`) |
| `clasbpro_gallery_theme_url` | Filter base URL for gallery pack assets |
| `clasbpro_gallery_theme_path` | Filter base path for gallery pack files |

### Out of scope (v1)

- Remote theme marketplace / updates
- `booking-status` pack overrides (schema supports; no v1 packs)
- Custom capability roles beyond filter
- Block editor / Elementor-specific theme previews

## Data flow

```
Admin Enable → clasbpro_theme_settings → Theme_Loader (gallery filters + bootstrap + CSS)
Front-end Shortcode → Template_Loader → Theme_Loader filters → templates
Install → Theme_Installer → stylesheet/class-bookings-with-stripe/
Preview → query arg → Theme_Loader (temporary gallery slug)
```
