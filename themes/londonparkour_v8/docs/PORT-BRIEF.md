# Port brief — Storybook → WordPress

Hand this to every porting agent verbatim.

## What you are doing

Porting a component from the `ldnpark2601` Storybook
(`/Users/wearebold/Sites/Storybook/ldnpark2601`) into this WordPress theme
(`/Users/wearebold/Sites/WordPress/londonparkour_v8/themes/londonparkour_v8`).

Source components are vanilla JS objects that write a template literal to
`innerHTML`. They become **PHP partials that take `$args`**. The JS is a
*rendering* layer only — it does not come across. Behaviour (motion, dialogs)
already lives in `assets/js/` and is driven by `data-*` attributes in markup.

## The reference implementation

Read these three before writing anything. They are the pattern:

- `parts/elements/button.php` — variant lookup map, `<a>` vs `<button>`, a11y
- `parts/elements/rule.php` — the simplest tone lookup
- `blocks/train-in-person/train-in-person.php` + `fields.php` — a full block

## Hard rules

1. **Copy Tailwind class strings byte-for-byte from the source.** Do not
   "improve", reorder, or tidy them. They are signed-off design decisions.

2. **A class name is never built from fragments.** Tailwind v4 scans source
   text; anything assembled from pieces compiles to nothing and fails silently.
   ```php
   class="<?php echo lp_classes( 'px-6 lg:px-16', $spacing ); ?>"  // correct
   class="btn-<?php echo $variant; ?>"                             // BROKEN
   $c = 'text-' . $tone;                                           // BROKEN
   ```
   Put whole literal strings in a PHP lookup array, keyed by variant/tone.

3. **One file per piece of HTML.** If the markup you need already exists under
   `parts/`, call it — never retype it:
   ```php
   lp_part( 'elements/button', array( 'label' => $label, 'href' => $href ) );
   ```
   Never emit a `<button>`, a `btn`-classed `<a>`, a `role="separator"`, or a
   raw `<svg>` outside `parts/elements/`. Icons come from `lp_icon( $id, $classes )`.

3a. **Check `parts/` before you write any markup.** Run `ls -R parts/` first.
   If a shape already exists there, call it. The shared parts are the point of
   this port — a component that retypes an existing part's markup is a defect,
   not a style choice.

   **Do NOT invent a new shared part on your own.** You see one component; the
   person coordinating sees all of them. If you spot a shape that looks
   reusable, port it inline as the source has it and **report it** — say which
   shape, and what you'd call it. Promotion is a coordinated step, because two
   agents each inventing `parts/components/media-box.php` with different props
   is worse than the duplication it was meant to remove.

3b. **Images go through `parts/components/media-photo.php`.** Never emit a raw
   `<img>` for a content image. The Storybook emits a bare `<img src>` because
   it has no media library; on WordPress that throws away `srcset` for free.

   ```php
   lp_part( 'components/media-photo', array(
       'image_id' => $item['thumb'],      // an attachment ID — enables srcset
       'alt'      => $item['title'],
       'scrim'    => 'hero',              // or 'none'
       'size'     => 'lp_wide',           // lp_wide | lp_portrait | lp_thumb
       'sizes'    => '100vw',             // these photos are full-bleed
   ) );
   ```

   **Every aspect is a parameter** — nothing is implicit, so a block can ask for
   exactly what it needs:

   | Arg | Values |
   |---|---|
   | `element` | `auto` (default: `<picture>` if `sources`/`formats`, else `<img>`), `img`, `picture`, `figure` |
   | `layout` | `fill` (default — absolute cover, NEEDS a positioned ancestor), `plain` (normal flow), `contain` |
   | `sources` | art direction → real `<picture>`: `array( 'media' => '(max-width: 640px)', 'image_id' => 12, 'size' => 'lp_portrait', 'sizes' => '100vw', 'type' => '' )` |
   | `alt` | **omit** to inherit the attachment's own alt; pass `''` only for a genuinely decorative image |
   | `caption` | rendered in `<figcaption>` when `element` is `figure` |
   | `class` / `wrapper_class` | extra classes on the `<img>` / on the `<picture>`/`<figure>` |
   | `loading` / `fetchpriority` / `decoding` | per-instance overrides |
   | `attrs` | arbitrary extra attributes, e.g. `data-motion-parallax` |

   `masthead` and `hero` scrims default to eager + `fetchpriority="high"`
   because they are the LCP element; everything else defaults to lazy.
   Use `layout => 'plain'` whenever the image is NOT inside a fixed-ratio
   positioned box — `fill` will collapse otherwise.

   Crops are registered in `app/setup/theme.php` in **ratio-matched families**
   (`lp_wide_sm|lp_wide|lp_wide_lg`) — a lone hard crop has no ratio siblings
   and produces an empty srcset. If you need a new ratio, add three widths and
   say so in your report; do not add a single orphan size.

4. **Escape everything.** `esc()` → `esc_html()`, `safeUrl()` → `esc_url()`,
   attributes → `esc_attr()`, rich text → `wp_kses_post()`. The JS `esc()` /
   `safeUrl()` helpers do NOT come across.

5. **`data-mount` seams disappear.** Where the source mounts a child component
   into a placeholder and calls `Child.init()`, the PHP calls `lp_part()`
   inline at that spot. Keep `data-component="…"` attributes — QA greps them.

6. **`data-motion-*` attributes carry over verbatim.** The motion layer reads
   the DOM, so they work unchanged.

7. **Keep the a11y decisions.** They were settled with the repo owner:
   - an `<a href>` is a link and must NEVER carry `role="button"`
   - real heading elements, never `role="heading"`
   - `role="list"` on a `list-style:none` list is deliberate — leave it
   - a disabled anchor gets `aria-disabled` + `tabindex="-1"` + `btn-disabled`

## Colour: the traps that have cost the most time

`docs/phase7/surface-axis.md` in the Storybook is canonical. Do not re-derive it.

- Tokens are **roles**: `primary` = ground-independent FILL; `accent` =
  ground-adaptive TEXT; `neutral` = the fixed dark band (identical in all four
  themes); `secondary` = the recessed board surface.
- In both LIGHT themes `base-content` and `neutral` are the same value —
  `text-base-content` on `bg-neutral` is invisible there and looks perfect in
  both dark themes. On a dark band always use the `neutral-content` family.
- `text-primary` on the light page ground measures **1.54:1** (yellow) /
  **1.27:1** (lime). On the page ground the signal role is `text-accent`.
  `bg-primary` as a fill is fine everywhere.
- `#45523E` is the light-theme **`accent`** — not `primary`, not `neutral`.
- **No arbitrary colour utilities.** `text-[#8A897F]`, `bg-[color-mix(...)]`
  break theming. If no token fits, report the gap rather than inventing a value.

## Copy

Default values come from the component's own `DEFAULT_*` constants and its
`init()` destructuring defaults. **Never** take copy from
`docs/phase7/*-inventory.md` — those strings are truncated and have repeatedly
been "completed" by invention.

## File layout

| Source | Destination |
|---|---|
| `src/stories/Elements/Foo/Foo.js` | `parts/elements/foo.php` |
| `src/stories/Components/Foo/Foo.js` | `parts/components/foo.php` |
| `src/stories/Forms/Foo/Foo.js` | `parts/forms/foo.php` |
| `src/stories/Brand/Foo/Foo.js` | `parts/brand/foo.php` |
| `src/stories/Blocks/Foo/Foo.js` | `blocks/foo/foo.php` + `blocks/foo/fields.php` |

Filenames are kebab-case. Every file starts with a docblock naming its source
path and any deliberate departure.

## Args

Props become `$args` keys in **snake_case** (`iconId` → `icon_id`,
`ariaLabel` → `aria_label`). Always default:
```php
$lp_label = (string) ( $args['label'] ?? 'Default from the source' );
```
Prefix local variables `$lp_` — partials share scope with their caller.

## Blocks only: fields.php

- Build from the shared helpers in `app/setup/acf-fields.php`. Never invent a
  field label that a helper already provides.
- Tabs in the canonical order, omitting any with no fields:
  **Content → Items → Actions → Settings**.
- Never set `'key'` — `wp lp acf:build` derives keys from the field path.
- Cross-field references use `'lp_conditional'` naming a **sibling field name**.
- Any list of business entities uses `lp_field_source()` and resolves via
  `lp_resolve_source( $args, 'lp_class' )`. Project the result locally — do not
  try to make one shape fit every block.

## Verify before you report done

From the theme directory:

```bash
php -l <each file you touched>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # blocks only; must print Success
bin/wp lp render <layout_name>   # blocks only; prints the markup
npm run build                    # must succeed
```

Then **diff your markup against the source's class strings**. A port is correct
when the class attributes match the Storybook exactly. Report any class you
could not carry over and why — do not silently drop or substitute one.
