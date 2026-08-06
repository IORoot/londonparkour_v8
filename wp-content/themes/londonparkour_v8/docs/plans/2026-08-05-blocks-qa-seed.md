# Blocks QA Page & Demo Seeding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a `/blocks-qa/` page rendering all ten blocks in both source modes, backed by a `wp lp seed` command whose demo content lives in git, and fix the CPT source path that has never executed.

**Architecture:** Content definition is code — `blocks/*/example.json` for manual rows, `bin/demo-content/*.json` for CPT records, `bin/demo-media/` for photographs. A new `wp lp seed` command composes them into a WordPress database that is treated as disposable. Along the way, `lp_resolve_source()` gains post-object resolution and `lp_class` session expansion, which is the seam `cpt.php` already nominates.

**Tech Stack:** WordPress (classic theme, no Gutenberg), ACF Pro, WP-CLI in a Docker `cli` sidecar, PHP 8, Vite + Tailwind v4.

## Global Constraints

Copied from the spec and the existing project rules. Every task's requirements implicitly include this section.

- **Never emit HTML outside a part.** `lp_part( 'elements/button', $args )` is the only way to emit an element; `bin/audit-reuse.sh` fails the build otherwise.
- **Never emit a raw `<svg>`** outside `parts/brand/`. Use `lp_icon( $id, $classes )`.
- **`lp_classes( ...$strings )` joins WHOLE literal class strings.** It never builds one — Tailwind v4 text-scans source.
- **Copy defaults live in the partial**, in the `$args['x'] ?? '…'` fallback. Control defaults (`source`, `source_limit`, `style`, `spacing_*`) live in `fields.php`.
- **ACF field groups are generated.** Never edit `acf-json/` by hand; edit PHP and run `bin/wp lp acf:build`.
- **WP-CLI is not on the host.** Always `bin/wp <args>`, never `wp <args>`.
- **All commands run from `themes/londonparkour_v8/`** unless stated otherwise.
- **`source` takes `latest | choose | manual`.** It is never a post type name.
- **No commits are made by this plan.** The project has deliberately committed nothing; every task ends with a verification step, not a `git commit`. If the standing decision changes, add commits at that point.
- **Demo images are 1920×1280 JPEG at q78**, committed to `bin/demo-media/`.
- Site is `http://localhost:8102`, admin `admin` / `admin`.

## File Structure

| Path | Responsibility | Task |
|---|---|---|
| `page.php` | Modify — render `page_sections`, then `the_content` | 1 |
| `app/setup/acf-groups.php` | Modify — rename `site_type`→`type`, `day_label`→`date_label` | 2 |
| `bin/demo-media/*.jpeg` | Create — six demo photographs | 3 |
| `blocks/*/example.media.json` | Create — field path → demo filename, five files | 3 |
| `bin/demo-content/*.json` | Create — CPT records and terms, five files | 4 |
| `app/setup/seed.php` | Create — the `wp lp seed` command, all of it | 4, 7 |
| `functions.php` | Modify — add `app/setup/seed.php` to `$lp_includes` | 4 |
| `app/setup/acf-fields.php` | Modify — `lp_resolve_source()` fixes; remove dead `style` | 5, 6, 8 |
| `blocks/*/fields.php` | Modify — pass expansion opts on Hero/Classes/CTA | 6 |
| `blocks/*/example.json` | Modify — drop dead `"style"` keys | 8 |
| `bin/README.md` | Create — the two-dev contract | 9 |
| `README.md` | Create — theme readme | 9 |
| `docs/PORT-FINDINGS.md` | Modify — §13, the CPT-path findings | 9 |
| `docs/HANDOFF.md` | Modify — state update | 9 |
| `bin/bootstrap.sh` | Modify — closing message names `wp lp seed` | 9 |

---

### Task 1: `page.php` renders sections

`page.php` is still the untouched `_tw` scaffold. Nothing can render a block on a page until this changes. This is Phase 5b item 2 regardless.

**Files:**
- Modify: `page.php` (whole file)

**Interfaces:**
- Consumes: `lp_render_sections( string $field = 'page_sections', $post_id = null ): void` from `app/includes/modules.php`, `lp_content_class()` from `inc/template-functions.php`
- Produces: nothing other tasks call. Tasks 7 and 8 depend on this file rendering `page_sections`.

- [ ] **Step 1: Confirm the current behaviour is wrong**

Run:
```bash
bin/wp post create --post_type=page --post_title='Section Probe' \
  --post_name=section-probe --post_status=publish --porcelain
```

Note the returned ID as `$ID`, then:

```bash
bin/wp eval 'update_field( "page_sections", array( array( "acf_fc_layout" => "statement", "quote" => "PROBE OK" ) ), '"$ID"' );'
curl -s http://localhost:8102/section-probe/ | grep -c 'PROBE OK'
```

Expected: `0`. The row is stored but `page.php` never reads it.

- [ ] **Step 2: Rewrite `page.php`**

```php
<?php
/**
 * Pages.
 *
 * Two shapes, one template. A page built from blocks has rows in the
 * `page_sections` Flexible Content field; a prose page (Legal, and anything an
 * editor writes long-form) has post content. Either may be empty, and a page
 * with both renders sections first, then prose.
 *
 * lp_render_sections() returns early when the field holds no array, so it is
 * safe to call unconditionally. The rows check below exists only to decide
 * whether the prose wrapper is emitted at all — an empty <div class="prose">
 * on a pure block page would introduce stray vertical rhythm.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lp_sections = function_exists( 'get_field' ) ? get_field( 'page_sections' ) : null;
$lp_has_sections = is_array( $lp_sections ) && $lp_sections;
?>

<main id="main">
	<?php
	if ( $lp_has_sections ) {
		lp_render_sections();
	}

	while ( have_posts() ) :
		the_post();

		$lp_content = trim( get_the_content() );

		if ( '' === $lp_content ) {
			continue;
		}
		?>
		<div class="mx-auto w-full max-w-[720px] px-6 md:px-16 py-[64px]">
			<div class="<?php echo esc_attr( lp_content_class() ); ?>">
				<?php the_content(); ?>
			</div>
		</div>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
```

- [ ] **Step 3: Verify the probe now renders**

```bash
php -l page.php
curl -s http://localhost:8102/section-probe/ | grep -c 'PROBE OK'
```

Expected: `No syntax errors detected`, then `1`.

- [ ] **Step 4: Verify a prose page still renders**

```bash
bin/wp post create --post_type=page --post_title='Prose Probe' \
  --post_name=prose-probe --post_status=publish \
  --post_content='<p>PROSE OK</p>' --porcelain
curl -s http://localhost:8102/prose-probe/ | grep -c 'PROSE OK'
```

Expected: `1`.

- [ ] **Step 5: Confirm `lp_content_class()` exists and is what was used**

```bash
grep -n 'function lp_content_class' inc/template-functions.php
```

Expected: one match. If there is none, replace `lp_content_class()` in Step 2 with the constant directly: `esc_attr( LONDONPARKOUR_V8_TYPOGRAPHY_CLASSES )`, which is defined in `functions.php` and is that helper's source of truth.

- [ ] **Step 6: Delete both probes**

```bash
bin/wp post delete $(bin/wp post list --post_type=page --name=section-probe --field=ID --format=csv) --force
bin/wp post delete $(bin/wp post list --post_type=page --name=prose-probe --field=ID --format=csv) --force
```

- [ ] **Step 7: Run the standing gates**

```bash
bash bin/audit-reuse.sh
bin/wp lp acf:build --check
```

Expected: `✓` and `Success`.

---

### Task 2: Rename the drifted CPT fields

Two field names diverge between a CPT and the block that consumes it. `acf-groups.php`'s own header states the rule that settles it: *"Field shapes are taken from the Storybook components that consume them."* The CPT is what drifted.

Renaming an ACF field changes its derived `field_` key and orphans stored meta. **No content exists yet**, so this is free now and expensive after Task 4. It must happen first.

**Files:**
- Modify: `app/setup/acf-groups.php:194` (`site_type` → `type`), `app/setup/acf-groups.php:77` (`day_label` → `date_label`)

**Interfaces:**
- Consumes: nothing.
- Produces: `lp_location` records expose `type`; `lp_class` session rows expose `date_label`. Tasks 4, 5 and 6 rely on both names.

- [ ] **Step 1: Confirm the drift**

```bash
grep -n "'site_type'\|'day_label'" app/setup/acf-groups.php
grep -n "'type'" blocks/locations/fields.php
grep -n "'date_label'" blocks/classes/fields.php
```

Expected: the CPT uses `site_type` and `day_label`; the two blocks use `type` and `date_label`.

- [ ] **Step 2: Rename `site_type` to `type`**

In `app/setup/acf-groups.php`, inside the Location group:

```php
			array(
				'name'          => 'type',
				'label'         => __( 'Type', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'INDOOR'  => __( 'Indoor', 'londonparkour_v8' ),
					'OUTDOOR' => __( 'Outdoor', 'londonparkour_v8' ),
				),
				'default_value' => 'INDOOR',
			),
```

- [ ] **Step 3: Rename `day_label` to `date_label`**

In `app/setup/acf-groups.php`, inside the Class group's `sessions` repeater `sub_fields`:

```php
					array(
						'name'  => 'date_label',
						'label' => __( 'Date', 'londonparkour_v8' ),
						'type'  => 'text',
					),
```

- [ ] **Step 4: Confirm no other reference to the old names survives**

```bash
grep -rn "site_type\|day_label" app/ blocks/ parts/ bin/ *.php
```

Expected: no output. Any hit is a caller that must be renamed too.

- [ ] **Step 5: Rebuild and verify the field groups**

```bash
php -l app/setup/acf-groups.php
bin/wp lp acf:build
bin/wp lp acf:build --check
```

Expected: `No syntax errors detected`, a `Wrote N field group(s)` success, then `Success` from the consistency check.

- [ ] **Step 6: Confirm the generated JSON carries the new names**

```bash
grep -l 'group_lp_location\|group_lp_class' acf-json/*.json | xargs grep -o '"name": "\(type\|date_label\|site_type\|day_label\)"' | sort -u
```

Expected: `"name": "type"` and `"name": "date_label"` only. No `site_type`, no `day_label`.

---

### Task 3: Demo media

Six photographs, downscaled from the Storybook repository's originals (2.6–5.8 MB each) to 1920×1280 q78. 1920 wide is required, not cosmetic: `lp_wide_lg` is 1920×1080 and `lp_portrait_lg` is 1112×1200, and a narrower source makes WordPress skip the largest crop, thinning the srcset and leaving the Phase 3 responsive-image work half-tested.

**Files:**
- Create: `bin/demo-media/hero-arches.jpeg`, `site-vauxhall.jpeg`, `site-southbank.jpeg`, `coach-andy.jpeg`, `coach-kie.jpeg`, `private-coaching.jpeg`
- Create: `blocks/hero/example.media.json`, `blocks/coaches/example.media.json`, `blocks/locations/example.media.json`, `blocks/private-coaching/example.media.json`

**Interfaces:**
- Consumes: nothing.
- Produces: `bin/demo-media/<name>.jpeg` files, and `example.media.json` as a flat JSON object mapping **dot-path into the block's row data** → **demo filename**, e.g. `{"lead_coach.image": "coach-andy.jpeg"}`. Task 8 walks these paths. Numeric segments index repeater rows: `source_manual.0.thumb`.

- [ ] **Step 1: Create the directory and downscale the originals**

The source images live in the Storybook repo at `/Users/wearebold/Sites/Storybook/ldnpark2601/`. Six are chosen; the mapping from original to demo name is arbitrary beyond wide-vs-portrait suitability, so verify each result looks like what its name claims before moving on.

```bash
mkdir -p bin/demo-media
SRC=/Users/wearebold/Sites/Storybook/ldnpark2601

sips -Z 1920 "$SRC/DSC01086.jpeg" --out bin/demo-media/hero-arches.jpeg
sips -Z 1920 "$SRC/DSC01033.jpeg" --out bin/demo-media/site-vauxhall.jpeg
sips -Z 1920 "$SRC/DSC01119.jpeg" --out bin/demo-media/site-southbank.jpeg
sips -Z 1920 "$SRC/DSC01057.jpeg" --out bin/demo-media/coach-andy.jpeg
sips -Z 1920 "$SRC/DSC01072.jpeg" --out bin/demo-media/coach-kie.jpeg
sips -Z 1920 "$SRC/DSC01091.jpeg" --out bin/demo-media/private-coaching.jpeg
```

`sips -Z` is macOS-native and fits the longest edge to 1920 while preserving aspect ratio.

- [ ] **Step 2: Verify dimensions and total weight**

```bash
sips -g pixelWidth -g pixelHeight bin/demo-media/*.jpeg | grep -E 'pixel|jpeg'
du -sh bin/demo-media
```

Expected: every image at least 1920 on its long edge, and a total under 4 MB. If the total exceeds 4 MB, re-run the failing files through `sips -s formatOptions 78` to recompress, and re-check.

- [ ] **Step 3: Confirm every image clears the largest crop**

`lp_wide_lg` needs 1920×1080 and `lp_portrait_lg` needs 1112×1200. An image that is 1920 wide but under 1080 tall cannot fill the wide family.

```bash
for f in bin/demo-media/*.jpeg; do
  w=$(sips -g pixelWidth "$f" | awk '/pixelWidth/{print $2}')
  h=$(sips -g pixelHeight "$f" | awk '/pixelHeight/{print $2}')
  [ "$w" -ge 1920 ] && [ "$h" -ge 1200 ] || echo "TOO SMALL: $f ${w}x${h}"
done
```

Expected: no output. Anything listed needs a different source image — pick another `DSC*.jpeg` and re-run Step 1 for that file only.

- [ ] **Step 4: Write `blocks/hero/example.media.json`**

```json
{
  "media": "hero-arches.jpeg"
}
```

- [ ] **Step 5: Write `blocks/coaches/example.media.json`**

The lead coach portrait, plus a thumbnail on each of the five manual roster rows. Andy and Kie's photographs are reused across the roster; six committed images cannot give five coaches a unique face each, and the QA page only needs to prove the thumb slot renders.

```json
{
  "lead_coach.image": "coach-andy.jpeg",
  "source_manual.0.thumb": "coach-kie.jpeg",
  "source_manual.1.thumb": "coach-andy.jpeg",
  "source_manual.2.thumb": "coach-kie.jpeg",
  "source_manual.3.thumb": "coach-andy.jpeg",
  "source_manual.4.thumb": "coach-kie.jpeg"
}
```

- [ ] **Step 6: Write `blocks/locations/example.media.json`**

```json
{
  "flagship.image": "site-vauxhall.jpeg"
}
```

- [ ] **Step 7: Write `blocks/private-coaching/example.media.json`**

```json
{
  "media": "private-coaching.jpeg"
}
```

- [ ] **Step 8: Verify every path in every media map is a real field**

Each key's first segment must exist as a field in that block's `fields.php`.

```bash
grep -n "lp_field_media" blocks/hero/fields.php blocks/coaches/fields.php \
  blocks/locations/fields.php blocks/private-coaching/fields.php
```

Expected: `hero` has a bare `lp_field_media()` (name `media`); `coaches` has one named `image` inside the `lead_coach` group and one named `thumb` inside the source repeater; `locations` has one named `image` inside the `flagship` group; `private-coaching` has a bare `lp_field_media()`. If any differs, correct the JSON key to match `fields.php` — `fields.php` is authoritative.

- [ ] **Step 9: Verify the JSON parses**

```bash
for f in blocks/*/example.media.json; do php -r 'json_decode(file_get_contents($argv[1]),true); echo $argv[1], ": ", json_last_error_msg(), PHP_EOL;' "$f"; done
```

Expected: `No error` for all four.

---

### Task 4: `wp lp seed` — media, terms and CPT records

The command, its safety model, and everything except the QA page. Seeding the CPT records before fixing the resolver is deliberate: it is what makes Tasks 5 and 6 testable against real data.

**Files:**
- Create: `app/setup/seed.php`
- Create: `bin/demo-content/terms.json`, `lp_location.json`, `lp_coach.json`, `lp_class.json`, `lp_tutorial.json`
- Modify: `functions.php` (`$lp_includes`)

**Interfaces:**
- Consumes: `bin/demo-media/*.jpeg` from Task 3; the renamed `type` and `date_label` fields from Task 2.
- Produces:
  - `lp_seed_media(): array` — filename (basename, e.g. `coach-andy.jpeg`) → attachment ID
  - `lp_seed_terms(): void`
  - `lp_seed_posts( string $post_type, array $media ): array` — demo slug → post ID
  - `lp_seed_is_ours( int $post_id ): bool`
  - `lp_seed_read_json( string $path ): array` — aborts via `WP_CLI::error` on unreadable or malformed input
  - Post meta marker `_lp_seed` = `1` on every created post and attachment
  - Task 7 adds `lp_seed_page()` to this same file.

- [ ] **Step 1: Write `bin/demo-content/terms.json`**

Level names are taken verbatim from `blocks/classes/example.json`'s `level` values so the CPT rows and manual rows read identically.

```json
{
  "lp_level": [
    { "slug": "beginner", "name": "Level 1 · Beginner" },
    { "slug": "improver", "name": "Level 2 · Improver" },
    { "slug": "advanced", "name": "Level 3 · Advanced" },
    { "slug": "all-levels", "name": "All levels" }
  ],
  "lp_series": [
    { "slug": "foundations", "name": "Foundations" },
    { "slug": "vaults", "name": "Vaults" },
    { "slug": "conditioning", "name": "Conditioning" }
  ]
}
```

- [ ] **Step 2: Write `bin/demo-content/lp_location.json`**

Six sites. The first is the flagship from `blocks/locations/example.json`; the rest are its `source_manual` rows verbatim, so a CPT row and a manual row must render the same.

```json
[
  {
    "slug": "vauxhall-the-arches",
    "title": "Vauxhall — The Arches",
    "thumbnail": "site-vauxhall.jpeg",
    "fields": {
      "tag": "FLAGSHIP INDOOR SITE",
      "meta": "SW8 1SR · 4 MIN FROM VAUXHALL · OPEN 07:00–22:00",
      "type": "INDOOR",
      "is_flagship": true,
      "latitude": "51.4861",
      "longitude": "-0.1253"
    }
  },
  {
    "slug": "peckham-rye",
    "title": "Peckham Rye",
    "fields": {
      "tag": "INDOOR SITE",
      "meta": "SE15 3UA · Rye Lane · 6 min",
      "type": "INDOOR",
      "is_flagship": false,
      "latitude": "51.4695",
      "longitude": "-0.0699"
    }
  },
  {
    "slug": "southbank-undercroft",
    "title": "Southbank Undercroft",
    "thumbnail": "site-southbank.jpeg",
    "fields": {
      "tag": "OUTDOOR SITE",
      "meta": "SE1 8XX · Waterloo · 8 min",
      "type": "OUTDOOR",
      "is_flagship": false,
      "latitude": "51.5060",
      "longitude": "-0.1160"
    }
  },
  {
    "slug": "stratford-east",
    "title": "Stratford East",
    "fields": {
      "tag": "INDOOR SITE",
      "meta": "E20 1EJ · Stratford · 5 min",
      "type": "INDOOR",
      "is_flagship": false,
      "latitude": "51.5434",
      "longitude": "-0.0086"
    }
  },
  {
    "slug": "hackney-marshes",
    "title": "Hackney Marshes",
    "fields": {
      "tag": "OUTDOOR SITE",
      "meta": "E9 5PF · Homerton · 10 min",
      "type": "OUTDOOR",
      "is_flagship": false,
      "latitude": "51.5533",
      "longitude": "-0.0342"
    }
  },
  {
    "slug": "wembley-park",
    "title": "Wembley Park",
    "fields": {
      "tag": "OUTDOOR SITE",
      "meta": "HA9 0FJ · Wembley Park · 7 min",
      "type": "OUTDOOR",
      "is_flagship": false,
      "latitude": "51.5633",
      "longitude": "-0.2795"
    }
  }
]
```

- [ ] **Step 3: Write `bin/demo-content/lp_coach.json`**

Andy Pearson is the lead from `blocks/coaches/example.json`'s `lead_coach`; the other five are its `source_manual` rows. `location` is a **slug reference** into `lp_location.json` — Task 4's seeder resolves it to a post ID.

```json
[
  {
    "slug": "andy-pearson",
    "title": "Andy Pearson",
    "thumbnail": "coach-andy.jpeg",
    "fields": {
      "role": "HEAD COACH / 11 YRS",
      "specialty": "Precision & balance",
      "location": "vauxhall-the-arches",
      "quote": "“The job isn't to make you brave. It's to break the thing you're scared of into six pieces small enough that you're not.”"
    }
  },
  {
    "slug": "kie-piccio",
    "title": "Kie Piccio",
    "thumbnail": "coach-kie.jpeg",
    "fields": {
      "role": "COACH / 6 YRS",
      "specialty": "Precision & balance",
      "location": "peckham-rye",
      "quote": ""
    }
  },
  {
    "slug": "leon-lawrence",
    "title": "Leon Lawrence",
    "thumbnail": "coach-andy.jpeg",
    "fields": {
      "role": "COACH / 4 YRS",
      "specialty": "Kids & families",
      "location": "vauxhall-the-arches",
      "quote": ""
    }
  },
  {
    "slug": "nirosh-ganeshalingam",
    "title": "Nirosh Ganeshalingam",
    "thumbnail": "coach-kie.jpeg",
    "fields": {
      "role": "COACH / 8 YRS",
      "specialty": "Strength & conditioning",
      "location": "southbank-undercroft",
      "quote": ""
    }
  },
  {
    "slug": "sofia-reyes",
    "title": "Sofia Reyes",
    "thumbnail": "coach-andy.jpeg",
    "fields": {
      "role": "COACH / 5 YRS",
      "specialty": "Women's sessions",
      "location": "vauxhall-the-arches",
      "quote": ""
    }
  },
  {
    "slug": "tomas-vrba",
    "title": "Tomas Vrba",
    "thumbnail": "coach-kie.jpeg",
    "fields": {
      "role": "COACH / 7 YRS",
      "specialty": "Competition & film",
      "location": "",
      "quote": ""
    }
  }
]
```

- [ ] **Step 4: Write `bin/demo-content/lp_class.json`**

Seven classes, taken from `blocks/classes/example.json`'s `source_manual` rows. **Beginners Parkour deliberately carries two sessions** — it is the record that proves session expansion in Task 6 produces two rows from one class. `terms` names an `lp_level` slug from `terms.json`; `location` and `coaches` are slug references.

```json
[
  {
    "slug": "sunrise-session",
    "title": "Sunrise Session",
    "menu_order": 1,
    "terms": { "lp_level": ["improver"] },
    "fields": {
      "subtitle": "60 min · outdoor, rain or shine",
      "location": "peckham-rye",
      "coaches": ["kie-piccio"],
      "price": "£15",
      "price_label": "DROP-IN",
      "primary_action": { "link": { "title": "WAITLIST", "url": "/book/sunrise/", "target": "" } },
      "sessions": [
        { "date_label": "TODAY", "time": "07:00", "spaces": "FULL", "sold_out": true }
      ]
    }
  },
  {
    "slug": "kids-parkour-5-11",
    "title": "Kids Parkour 5–11",
    "menu_order": 2,
    "terms": { "lp_level": ["all-levels"] },
    "fields": {
      "subtitle": "45 min · parents welcome to watch",
      "location": "hackney-marshes",
      "coaches": ["leon-lawrence"],
      "price": "£12",
      "price_label": "PER CHILD",
      "primary_action": { "link": { "title": "BOOK", "url": "/book/kids/", "target": "" } },
      "sessions": [
        { "date_label": "TODAY", "time": "10:00", "spaces": "2 LEFT", "sold_out": false }
      ]
    }
  },
  {
    "slug": "beginners-parkour",
    "title": "Beginners Parkour",
    "menu_order": 3,
    "terms": { "lp_level": ["beginner"] },
    "fields": {
      "subtitle": "60 min · all kit provided",
      "location": "vauxhall-the-arches",
      "coaches": ["andy-pearson", "leon-lawrence"],
      "price": "£15",
      "price_label": "DROP-IN",
      "primary_action": { "link": { "title": "BOOK", "url": "/book/beginners/", "target": "" } },
      "sessions": [
        { "date_label": "TODAY", "time": "18:30", "spaces": "4 LEFT", "sold_out": false },
        { "date_label": "THU", "time": "18:30", "spaces": "8 LEFT", "sold_out": false }
      ]
    }
  },
  {
    "slug": "open-gym",
    "title": "Open Gym",
    "menu_order": 4,
    "terms": { "lp_level": ["all-levels"] },
    "fields": {
      "subtitle": "90 min · unstructured, coach on floor",
      "location": "stratford-east",
      "coaches": ["nirosh-ganeshalingam"],
      "price": "£8",
      "price_label": "DROP-IN",
      "primary_action": { "link": { "title": "BOOK", "url": "/book/open-gym/", "target": "" } },
      "sessions": [
        { "date_label": "TODAY", "time": "19:45", "spaces": "11 LEFT", "sold_out": false }
      ]
    }
  },
  {
    "slug": "womens-session",
    "title": "Women's Session",
    "menu_order": 5,
    "terms": { "lp_level": ["all-levels"] },
    "fields": {
      "subtitle": "60 min · women and non-binary only",
      "location": "southbank-undercroft",
      "coaches": ["sofia-reyes"],
      "price": "£15",
      "price_label": "DROP-IN",
      "primary_action": { "link": { "title": "BOOK", "url": "/book/womens/", "target": "" } },
      "sessions": [
        { "date_label": "FRI", "time": "07:15", "spaces": "6 LEFT", "sold_out": false }
      ]
    }
  },
  {
    "slug": "advanced-movement",
    "title": "Advanced Movement",
    "menu_order": 6,
    "terms": { "lp_level": ["advanced"] },
    "fields": {
      "subtitle": "75 min · by coach invitation",
      "location": "vauxhall-the-arches",
      "coaches": ["tomas-vrba"],
      "price": "£15",
      "price_label": "DROP-IN",
      "primary_action": { "link": { "title": "BOOK", "url": "/book/advanced/", "target": "" } },
      "sessions": [
        { "date_label": "FRI", "time": "12:00", "spaces": "3 LEFT", "sold_out": false }
      ]
    }
  },
  {
    "slug": "family-session",
    "title": "Family Session",
    "menu_order": 7,
    "terms": { "lp_level": ["all-levels"] },
    "fields": {
      "subtitle": "60 min · ages 5+ with an adult",
      "location": "wembley-park",
      "coaches": ["leon-lawrence"],
      "price": "£24",
      "price_label": "2 PEOPLE",
      "primary_action": { "link": { "title": "BOOK", "url": "/book/family/", "target": "" } },
      "sessions": [
        { "date_label": "SAT", "time": "09:30", "spaces": "9 LEFT", "sold_out": false }
      ]
    }
  }
]
```

- [ ] **Step 5: Write `bin/demo-content/lp_tutorial.json`**

No block reads `lp_tutorial`. These exist for Phase 5b step 4, which ports `archive-lp_tutorial.php`, `taxonomy-lp_series.php` and `single-lp_tutorial.php`.

```json
[
  {
    "slug": "the-safety-vault",
    "title": "The Safety Vault",
    "thumbnail": "coach-kie.jpeg",
    "menu_order": 1,
    "terms": { "lp_series": ["vaults"], "lp_level": ["beginner"] },
    "fields": {
      "duration": "8:24",
      "video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "coaches": ["kie-piccio"]
    }
  },
  {
    "slug": "landing-without-noise",
    "title": "Landing Without Noise",
    "thumbnail": "coach-andy.jpeg",
    "menu_order": 2,
    "terms": { "lp_series": ["foundations"], "lp_level": ["beginner"] },
    "fields": {
      "duration": "6:11",
      "video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "coaches": ["andy-pearson"]
    }
  },
  {
    "slug": "grip-and-hang-strength",
    "title": "Grip & Hang Strength",
    "thumbnail": "coach-kie.jpeg",
    "menu_order": 3,
    "terms": { "lp_series": ["conditioning"], "lp_level": ["improver"] },
    "fields": {
      "duration": "12:03",
      "video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "coaches": ["nirosh-ganeshalingam"]
    }
  }
]
```

- [ ] **Step 6: Verify every demo JSON parses and every slug reference resolves**

```bash
for f in bin/demo-content/*.json; do php -r 'json_decode(file_get_contents($argv[1]),true); echo $argv[1], ": ", json_last_error_msg(), PHP_EOL;' "$f"; done
```

Expected: `No error` for all five.

Then check references by hand against the files above: every `location` value in `lp_coach.json` and `lp_class.json` must appear as a `slug` in `lp_location.json`; every `coaches` entry must appear as a `slug` in `lp_coach.json`; every `lp_level`/`lp_series` term must appear in `terms.json`. Tomas Vrba's empty `location` is intentional — the source row says "ALL SITES", which is not a site.

- [ ] **Step 7: Write `app/setup/seed.php`**

```php
<?php
/**
 * Demo content seeding — `wp lp seed`.
 *
 * The database is disposable; the content definition is code. This command
 * composes bin/demo-content/*.json, bin/demo-media/*.jpeg and each block's
 * example.json into a working site, and is the only supported way to get one.
 * There is no SQL dump: `docker compose down -v` followed by bootstrap.sh and
 * this command IS the recovery path. See bin/README.md.
 *
 * SAFETY. Every post and attachment this command creates carries `_lp_seed`
 * post meta. Nothing without that marker is ever updated or deleted, so a page
 * an editor wrote by hand cannot be touched however its slug collides.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/** Post meta key marking a record as seed-owned. */
const LP_SEED_MARKER = '_lp_seed';

/**
 * Read and decode a demo-content JSON file, or abort.
 *
 * Seed never half-populates: a malformed file stops the run before any write.
 *
 * @param string $path Theme-relative path, e.g. 'bin/demo-content/lp_class.json'.
 * @return array
 */
function lp_seed_read_json( string $path ): array {
	$full = get_theme_file_path( $path );

	if ( ! is_readable( $full ) ) {
		WP_CLI::error( "Cannot read {$path}" );
	}

	$data = json_decode( (string) file_get_contents( $full ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( ! is_array( $data ) ) {
		WP_CLI::error( "{$path} is not valid JSON: " . json_last_error_msg() );
	}

	return $data;
}

/**
 * Is this post one of ours?
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function lp_seed_is_ours( int $post_id ): bool {
	return (bool) get_post_meta( $post_id, LP_SEED_MARKER, true );
}

/**
 * Find an existing post by slug within a post type, whatever its status.
 *
 * @param string $post_type Post type.
 * @param string $slug      Post slug.
 * @return int Post ID, or 0.
 */
function lp_seed_find( string $post_type, string $slug ): int {
	$found = get_posts(
		array(
			'post_type'        => $post_type,
			'name'             => $slug,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);

	return (int) ( $found[0] ?? 0 );
}

/**
 * Import bin/demo-media/*.jpeg into the media library, once.
 *
 * Keyed on filename, so re-running does not duplicate the library.
 *
 * @return array<string,int> Filename => attachment ID.
 */
function lp_seed_media(): array {
	$dir = get_theme_file_path( 'bin/demo-media' );
	$map = array();

	if ( ! is_dir( $dir ) ) {
		WP_CLI::warning( 'bin/demo-media is missing — every image field will be left unset.' );
		return $map;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	foreach ( (array) glob( $dir . '/*.jpeg' ) as $file ) {
		$name = basename( $file );

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => LP_SEED_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'name'           => sanitize_title( pathinfo( $name, PATHINFO_FILENAME ) ),
			)
		);

		if ( ! empty( $existing[0] ) ) {
			$map[ $name ] = (int) $existing[0];
			continue;
		}

		// Copy to a temp path — media_handle_sideload MOVES the file it is given.
		$tmp = wp_tempnam( $name );
		copy( $file, $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy

		$id = media_handle_sideload(
			array(
				'name'     => $name,
				'tmp_name' => $tmp,
			),
			0
		);

		if ( is_wp_error( $id ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			WP_CLI::warning( "Could not import {$name}: " . $id->get_error_message() );
			continue;
		}

		update_post_meta( $id, LP_SEED_MARKER, 1 );
		$map[ $name ] = (int) $id;
		WP_CLI::log( "  + media {$name} (#{$id})" );
	}

	return $map;
}

/**
 * Create the demo taxonomy terms.
 */
function lp_seed_terms(): void {
	foreach ( lp_seed_read_json( 'bin/demo-content/terms.json' ) as $taxonomy => $terms ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			WP_CLI::warning( "Taxonomy {$taxonomy} is not registered — skipping its terms." );
			continue;
		}

		foreach ( $terms as $term ) {
			if ( term_exists( $term['slug'], $taxonomy ) ) {
				continue;
			}
			wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
			WP_CLI::log( "  + term {$taxonomy}/{$term['slug']}" );
		}
	}
}

/**
 * Resolve a demo slug reference to a post ID.
 *
 * A reference that does not resolve is a warning, not a failure: one missing
 * cross-reference must not stop the rest of the seed.
 *
 * @param string $post_type Referenced post type.
 * @param mixed  $value     Slug, or array of slugs, or ''.
 * @return int|array<int>|null
 */
function lp_seed_ref( string $post_type, $value ) {
	if ( is_array( $value ) ) {
		$ids = array();
		foreach ( $value as $slug ) {
			$id = lp_seed_ref( $post_type, $slug );
			if ( $id ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	$slug = (string) $value;

	if ( '' === $slug ) {
		return null;
	}

	$id = lp_seed_find( $post_type, $slug );

	if ( ! $id ) {
		WP_CLI::warning( "No {$post_type} with slug '{$slug}' — leaving the reference unset." );
		return null;
	}

	return $id;
}

/**
 * Which post type each reference field points at.
 *
 * Kept beside the seeder rather than derived from ACF: it is demo-content
 * knowledge, not field knowledge, and ACF cannot tell us what a JSON string
 * was meant to mean.
 */
const LP_SEED_REFS = array(
	'location' => 'lp_location',
	'coaches'  => 'lp_coach',
);

/**
 * Create or update the demo records of one post type.
 *
 * @param string             $post_type Post type.
 * @param array<string,int>  $media     Filename => attachment ID.
 * @return array<string,int> Slug => post ID.
 */
function lp_seed_posts( string $post_type, array $media ): array {
	$records = lp_seed_read_json( "bin/demo-content/{$post_type}.json" );
	$ids     = array();

	foreach ( $records as $record ) {
		$slug     = (string) $record['slug'];
		$existing = lp_seed_find( $post_type, $slug );

		if ( $existing && ! lp_seed_is_ours( $existing ) ) {
			WP_CLI::warning( "{$post_type} '{$slug}' exists and was not created by seed — skipping it." );
			continue;
		}

		$postarr = array(
			'post_type'   => $post_type,
			'post_title'  => (string) $record['title'],
			'post_name'   => $slug,
			'post_status' => 'publish',
			'menu_order'  => (int) ( $record['menu_order'] ?? 0 ),
		);

		if ( $existing ) {
			$postarr['ID'] = $existing;
			$id            = wp_update_post( $postarr, true );
		} else {
			$id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( "Could not write {$post_type} '{$slug}': " . $id->get_error_message() );
			continue;
		}

		$id = (int) $id;
		update_post_meta( $id, LP_SEED_MARKER, 1 );
		$ids[ $slug ] = $id;

		if ( ! empty( $record['thumbnail'] ) ) {
			$file = (string) $record['thumbnail'];
			if ( isset( $media[ $file ] ) ) {
				set_post_thumbnail( $id, $media[ $file ] );
			} else {
				WP_CLI::warning( "No demo image '{$file}' for {$post_type} '{$slug}'." );
			}
		}

		foreach ( (array) ( $record['terms'] ?? array() ) as $taxonomy => $slugs ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				wp_set_object_terms( $id, (array) $slugs, $taxonomy, false );
			}
		}

		foreach ( (array) ( $record['fields'] ?? array() ) as $name => $value ) {
			if ( isset( LP_SEED_REFS[ $name ] ) ) {
				$value = lp_seed_ref( LP_SEED_REFS[ $name ], $value );
			}
			update_field( $name, $value, $id );
		}

		WP_CLI::log( "  + {$post_type} {$slug} (#{$id})" );
	}

	return $ids;
}

/**
 * Delete every seed-owned post and attachment.
 */
function lp_seed_purge(): void {
	$ids = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => LP_SEED_MARKER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}

	WP_CLI::log( sprintf( '  - removed %d seeded record(s)', count( $ids ) ) );
}

WP_CLI::add_command(
	'lp seed',
	function ( $args, $assoc_args ) {
		if ( ! function_exists( 'update_field' ) ) {
			WP_CLI::error( 'ACF is not active — every write goes through update_field().' );
		}

		if ( ! empty( $assoc_args['fresh'] ) ) {
			WP_CLI::log( 'Purging previously seeded content' );
			lp_seed_purge();
		}

		WP_CLI::log( 'Media' );
		$media = lp_seed_media();

		WP_CLI::log( 'Terms' );
		lp_seed_terms();

		// Order matters: classes and coaches reference locations by slug.
		foreach ( array( 'lp_location', 'lp_coach', 'lp_class', 'lp_tutorial' ) as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				WP_CLI::warning( "Post type {$post_type} is not registered — skipping." );
				continue;
			}
			WP_CLI::log( $post_type );
			lp_seed_posts( $post_type, $media );
		}

		WP_CLI::success( 'Seeded.' );
	},
	array(
		'shortdesc' => 'Seed demo content for QA. Idempotent; --fresh purges first.',
		'synopsis'  => array(
			array(
				'type'        => 'flag',
				'name'        => 'fresh',
				'description' => 'Delete previously seeded content before seeding.',
				'optional'    => true,
			),
		),
	)
);
```

- [ ] **Step 8: Register the file in `functions.php`**

In the `$lp_includes` array, after `'app/setup/acf-build.php',`:

```php
	'app/setup/seed.php',
```

- [ ] **Step 9: Lint and confirm the command is registered**

```bash
php -l app/setup/seed.php
bin/wp lp seed --help
```

Expected: `No syntax errors detected`, then the command's synopsis showing the `--fresh` flag.

- [ ] **Step 10: Run the seed**

```bash
bin/wp lp seed
```

Expected: six media imports, seven terms, then 6 locations, 6 coaches, 7 classes, 3 tutorials. Finishes with `Success: Seeded.`

- [ ] **Step 11: Verify idempotency by counting**

```bash
for pt in lp_location lp_coach lp_class lp_tutorial; do
  printf '%s ' "$pt"; bin/wp post list --post_type=$pt --format=count
done
bin/wp lp seed
echo '--- after second run ---'
for pt in lp_location lp_coach lp_class lp_tutorial; do
  printf '%s ' "$pt"; bin/wp post list --post_type=$pt --format=count
done
bin/wp post list --post_type=attachment --format=count
```

Expected: `6 6 7 3` before and after, and 6 attachments. Any increase is a duplicate-creation bug in `lp_seed_find()` or `lp_seed_media()`.

- [ ] **Step 12: Verify the safety invariant by injection**

Create a post at a seed slug **without** the marker, and confirm seed refuses it.

```bash
bin/wp post create --post_type=lp_location --post_title='HAND WRITTEN' \
  --post_name=peckham-rye-probe --post_status=publish --porcelain
```

Then temporarily point a demo record at that slug: change `"slug": "peckham-rye"` to `"slug": "peckham-rye-probe"` in `bin/demo-content/lp_location.json`, run `bin/wp lp seed`, and confirm the output contains:

```
Warning: lp_location 'peckham-rye-probe' exists and was not created by seed — skipping it.
```

and that the post title is still `HAND WRITTEN`:

```bash
bin/wp post list --post_type=lp_location --name=peckham-rye-probe --field=title
```

Expected: `HAND WRITTEN`. **Then revert the JSON edit** and delete the probe:

```bash
bin/wp post delete $(bin/wp post list --post_type=lp_location --name=peckham-rye-probe --field=ID --format=csv) --force
bin/wp lp seed
```

- [ ] **Step 13: Verify `--fresh` purges and rebuilds**

```bash
bin/wp lp seed --fresh
for pt in lp_location lp_coach lp_class lp_tutorial; do
  printf '%s ' "$pt"; bin/wp post list --post_type=$pt --format=count
done
```

Expected: `6 6 7 3` again.

- [ ] **Step 14: Regenerate crops and confirm the srcset families exist**

```bash
bin/wp media regenerate --yes
bin/wp eval '$a = get_posts( array( "post_type" => "attachment", "posts_per_page" => 1, "fields" => "ids" ) ); print_r( array_keys( wp_get_attachment_metadata( $a[0] )["sizes"] ) );'
```

Expected: the list includes `lp_wide_sm`, `lp_wide`, `lp_wide_lg`, `lp_portrait_sm`, `lp_portrait`, `lp_portrait_lg`, `lp_thumb`, `lp_thumb_lg`. A missing `lp_wide_lg` means a demo image is under 1920×1080 — go back to Task 3 Step 3.

---

### Task 5: `lp_resolve_source()` resolves reference fields

With demo records in place, the CPT path can be run and its failures seen. This task fixes the first root cause: `location` on `lp_class` and `lp_coach` is `return_format => 'id'`, so blocks casting it to string render a post ID.

**Files:**
- Modify: `app/setup/acf-fields.php` — `lp_resolve_source()` and a new `lp_source_reference_fields()`

**Interfaces:**
- Consumes: seeded records from Task 4.
- Produces:
  - `lp_source_reference_fields( string $post_type ): array` — field name → `'title'` or `'titles'`
  - `lp_resolve_source()` items now carry `location` as a **title string**, and `lp_class` items additionally carry `level` (the first `lp_level` term name). Task 6 relies on both.

- [ ] **Step 1: Reproduce the failure**

```bash
bin/wp lp render coaches --args='{"source":"latest","source_limit":5}' | grep -o 'PECKHAM\|Peckham Rye\|>[0-9]\{1,4\}<' | head
```

Expected: numeric output such as `>14<` — a post ID where a place name belongs. If instead you see `Peckham Rye`, the bug is already fixed and this task is a no-op; verify against `hero` before concluding that.

- [ ] **Step 2: Add the reference map**

In `app/setup/acf-fields.php`, immediately after `lp_source_taxonomy_for()`:

```php
/**
 * Reference fields on a source record, and how to flatten them.
 *
 * A block projection does `(string) $item['location']`, so a post_object field
 * returning an ID renders as a number. These fields are replaced by their
 * post title(s) before the item reaches a block.
 *
 * This is a map rather than a lookup through get_field_object() because that
 * would cost a query per field per record, and because the set is small and
 * changes only when a CPT gains a reference field. It sits beside
 * lp_source_taxonomy_for(), which is the same shape for the same reason.
 *
 * @param string $post_type Post type name.
 * @return array<string,string> Field name => 'title' (one) or 'titles' (many).
 */
function lp_source_reference_fields( string $post_type ): array {
	$map = array(
		'lp_class'    => array( 'location' => 'title', 'coaches' => 'titles' ),
		'lp_coach'    => array( 'location' => 'title' ),
		'lp_tutorial' => array( 'coaches' => 'titles' ),
	);

	return $map[ $post_type ] ?? array();
}

/**
 * Replace reference IDs with titles on one resolved item.
 *
 * @param array  $fields    The record's ACF fields.
 * @param string $post_type Post type the record belongs to.
 * @return array
 */
function lp_flatten_references( array $fields, string $post_type ): array {
	foreach ( lp_source_reference_fields( $post_type ) as $name => $mode ) {
		if ( ! isset( $fields[ $name ] ) ) {
			continue;
		}

		$ids = array_filter( array_map( 'intval', (array) $fields[ $name ] ) );

		if ( ! $ids ) {
			$fields[ $name ] = '';
			continue;
		}

		$titles = array_map( 'get_the_title', $ids );

		$fields[ $name ] = 'titles' === $mode ? implode( ', ', $titles ) : (string) $titles[0];
	}

	return $fields;
}
```

- [ ] **Step 3: Call it from `lp_resolve_source()`, and attach the level term**

In `lp_resolve_source()`, replace the record loop's body. The existing loop reads:

```php
		$fields = function_exists( 'get_fields' ) ? get_fields( $id ) : array();
		$fields = is_array( $fields ) ? $fields : array();

		$items[] = array_merge(
```

Change it to:

```php
		$fields = function_exists( 'get_fields' ) ? get_fields( $id ) : array();
		$fields = is_array( $fields ) ? $fields : array();
		$fields = lp_flatten_references( $fields, $post_type );

		// The Classes board shows a level per row; it lives on the taxonomy,
		// not in a field, so it is attached here rather than in the block.
		$taxonomy = lp_source_taxonomy_for( $post_type );
		if ( $taxonomy && ! isset( $fields['level'] ) ) {
			$assigned         = get_the_terms( $id, $taxonomy );
			$fields['level'] = is_array( $assigned ) && $assigned ? $assigned[0]->name : '';
		}

		$items[] = array_merge(
```

- [ ] **Step 4: Verify coaches now shows a place name**

```bash
php -l app/setup/acf-fields.php
bin/wp lp render coaches --args='{"source":"latest","source_limit":5}' | grep -o 'Peckham Rye\|Southbank Undercroft\|Vauxhall — The Arches' | sort -u
```

Expected: the three location titles. No bare numbers.

- [ ] **Step 5: Verify the level term reaches the Classes projection**

```bash
bin/wp lp render classes --args='{"source":"latest","source_limit":7}' | grep -o 'Level 1 · Beginner\|Level 3 · Advanced\|All levels' | sort -u
```

Expected: at least those three level names.

- [ ] **Step 6: Confirm the manual path is unchanged**

Reference flattening must not touch manual rows, which already carry strings.

```bash
bin/wp lp render coaches | grep -o 'PECKHAM\|VAUXHALL\|SOUTHBANK' | sort -u
```

Expected: `PECKHAM`, `SOUTHBANK`, `VAUXHALL` — the uppercase manual values, untouched.

- [ ] **Step 7: Run the standing gates**

```bash
bash bin/audit-reuse.sh
bin/wp lp acf:build --check
```

Expected: `✓` and `Success`.

---

### Task 6: `lp_resolve_source()` expands class sessions

The second root cause. `lp_class` holds a `sessions` repeater; Hero, Classes and CTA read `time` and `spaces` flat off the class. `source_limit` on those blocks counts **sessions, not classes** — the Hero board is titled NEXT SESSIONS and has a fixed slot count.

**Files:**
- Modify: `app/setup/acf-fields.php` — `lp_resolve_source()` signature and body
- Modify: `blocks/hero/hero.php:91`, `blocks/classes/classes.php:187`, `blocks/cta/cta.php:36` — pass the expansion option

**Interfaces:**
- Consumes: `lp_flatten_references()` from Task 5; the `date_label` rename from Task 2.
- Produces: `lp_resolve_source( array $args, string $post_type, array $opts = array() ): array`. New option `'expand' => 'sessions'`. Each expanded item is the parent class's fields merged with one session row's subfields, session values winning, plus `id`/`title`/`url`/`thumb` from the parent.

- [ ] **Step 1: Reproduce the failure**

```bash
bin/wp lp render hero --args='{"source":"latest","source_limit":4}' | grep -c '18:30\|07:00\|19:45'
```

Expected: `0`. The times live in the `sessions` repeater and never reach the board.

- [ ] **Step 2: Add the expansion option to `lp_resolve_source()`**

Change the signature and docblock:

```php
/**
 * Resolve a block's source control to a flat list of items.
 *
 * Blocks call this and then project the result into their own shape — the same
 * entity appears differently in different places (a Hero session row shows four
 * fields, the Classes board eleven), so there is no single view-model.
 *
 * Every item is a flat associative array. Records contribute their ACF fields
 * plus `id`, `title`, `url` and `thumb`; manual rows contribute their subfields
 * with `id` null. A block reads $item['time'], $item['title'] either way.
 *
 * With `'expand' => 'sessions'`, each lp_class record is expanded into one item
 * per row of its `sessions` repeater — the class's own fields merged with that
 * session's, session values winning. `source_limit` then counts SESSIONS, not
 * classes, because the boards that ask for this show one row per time-slot and
 * have a fixed slot count. cpt.php names this function as the seam for exactly
 * this: a session is a time-slot of a class, not a fifth post type.
 *
 * @param array  $args      The block's field values (the whole $args array).
 * @param string $post_type CPT to query when source is 'latest' or 'choose'.
 * @param array  $opts      'expand' => 'sessions' to flatten class time-slots.
 * @return array<int, array>
 */
function lp_resolve_source( array $args, string $post_type, array $opts = array() ): array {
```

- [ ] **Step 3: Over-fetch classes when expanding**

Inside the `else` branch that builds `$query_args`, the `posts_per_page` line currently reads:

```php
				'posts_per_page'         => max( 1, (int) ( $args['source_limit'] ?? 4 ) ),
```

Replace it with:

```php
				// When expanding, the limit counts sessions, so we cannot know how
				// many classes to fetch — take them all and slice after expansion.
				// ponytail: 100 is a backstop, not a page size; raise it if a client
				// ever runs more than a hundred concurrently published classes.
				'posts_per_page'         => isset( $opts['expand'] )
					? 100
					: max( 1, (int) ( $args['source_limit'] ?? 4 ) ),
```

- [ ] **Step 4: Expand after the item loop**

At the end of `lp_resolve_source()`, replace:

```php
	return $items;
}
```

with:

```php
	if ( 'sessions' === ( $opts['expand'] ?? '' ) ) {
		$items = lp_expand_sessions( $items, max( 1, (int) ( $args['source_limit'] ?? 4 ) ) );
	}

	return $items;
}

/**
 * Expand class records into one item per session.
 *
 * A class with two sessions becomes two rows carrying the same title, price and
 * location. A class with none contributes nothing — an unscheduled class is not
 * a time-slot and does not belong on a timetable.
 *
 * @param array<int,array> $items Resolved class records.
 * @param int              $limit How many SESSIONS to return.
 * @return array<int,array>
 */
function lp_expand_sessions( array $items, int $limit ): array {
	$rows = array();

	foreach ( $items as $item ) {
		$sessions = $item['sessions'] ?? array();

		if ( ! is_array( $sessions ) || ! $sessions ) {
			continue;
		}

		unset( $item['sessions'] );

		foreach ( $sessions as $session ) {
			if ( ! is_array( $session ) ) {
				continue;
			}

			$rows[] = array_merge( $item, $session );

			if ( count( $rows ) >= $limit ) {
				return $rows;
			}
		}
	}

	return $rows;
}
```

- [ ] **Step 5: Pass the option from Hero**

`blocks/hero/hero.php` line 91 currently reads `lp_resolve_source( $args, 'lp_class' )`. Change to:

```php
	lp_resolve_source( $args, 'lp_class', array( 'expand' => 'sessions' ) )
```

- [ ] **Step 6: Pass the option from Classes**

`blocks/classes/classes.php` line 187, same change:

```php
	lp_resolve_source( $args, 'lp_class', array( 'expand' => 'sessions' ) )
```

- [ ] **Step 7: Pass the option from CTA**

`blocks/cta/cta.php` line 36 currently reads `$lp_rows = lp_resolve_source( $args, 'lp_class' );`. Change to:

```php
$lp_rows = lp_resolve_source( $args, 'lp_class', array( 'expand' => 'sessions' ) );
```

- [ ] **Step 8: Verify Hero renders four session rows**

```bash
php -l app/setup/acf-fields.php
php -l blocks/hero/hero.php && php -l blocks/classes/classes.php && php -l blocks/cta/cta.php
bin/wp lp render hero --args='{"source":"latest","source_limit":4}' | grep -o '07:00\|10:00\|18:30\|19:45' | sort -u
```

Expected: `07:00`, `10:00`, `18:30`, `19:45` — four times drawn from four classes in `menu_order`.

- [ ] **Step 9: Verify one class produces two rows**

Beginners Parkour carries two sessions. Asking for enough rows must show both.

```bash
bin/wp lp render hero --args='{"source":"latest","source_limit":8}' | grep -c 'Beginners Parkour'
```

Expected: `2`.

- [ ] **Step 10: Verify the limit counts sessions, not classes**

```bash
bin/wp lp render classes --args='{"source":"latest","source_limit":3}' | grep -o '07:00\|10:00\|18:30\|19:45\|07:15' | sort -u | wc -l
```

Expected: `3`. If it returns more, the slice is being applied to classes rather than to expanded rows.

- [ ] **Step 11: Verify CTA fills its panel from a real record**

```bash
bin/wp lp render cta --args='{"source":"latest","source_limit":1}' | grep -o 'Sunrise Session\|07:00\|Peckham Rye\|FULL'
```

Expected: `07:00`, `Peckham Rye` and `FULL` — the first session of the first class by `menu_order`, not CTA's hardcoded `Today, 18:30` defaults.

- [ ] **Step 12: Confirm the three unexpanded blocks are untouched**

```bash
bin/wp lp render locations --args='{"source":"latest","source_limit":5}' | grep -o 'INDOOR\|OUTDOOR' | sort -u
bin/wp lp render train-in-person --args='{"source":"latest","source_limit":3}' | grep -o 'FLAGSHIP INDOOR SITE\|OUTDOOR SITE' | sort -u
```

Expected: `INDOOR`/`OUTDOOR` from Locations (proving the Task 2 `type` rename landed), and the tags from TrainInPerson.

- [ ] **Step 13: Run the standing gates**

```bash
bash bin/audit-reuse.sh
bin/wp lp acf:build --check
```

Expected: `✓` and `Success`.

---

### Task 7: `wp lp seed` builds the Blocks QA page

The page is generated by iterating `blocks/*/`, so it can never drift from the fixtures. Sixteen rows: ten manual, six CPT-backed, each CPT row placed directly after its manual twin so the two can be compared without scrolling.

**Files:**
- Modify: `app/setup/seed.php` — add `lp_seed_page()`, `lp_seed_rows()`, `lp_seed_set_path()`, and call from the command

**Interfaces:**
- Consumes: `lp_seed_media()`, `lp_seed_find()`, `lp_seed_is_ours()` from Task 4; `page.php` from Task 1.
- Produces: a published page at `/blocks-qa/` titled "Blocks QA".

- [ ] **Step 1: Add the row builders to `app/setup/seed.php`**

Insert before the `WP_CLI::add_command( 'lp seed', ... )` call:

```php
/**
 * Which source-backed blocks get a second, CPT-backed row, and how many items.
 *
 * The only per-block knowledge seed holds. It is coordination data — how big a
 * demo list should be — not block data, which lives in fields.php.
 *
 * CTA shows one session, so it asks for one.
 */
const LP_SEED_CPT_ROWS = array(
	'hero'             => 4,
	'classes'          => 7,
	'coaches'          => 5,
	'locations'        => 5,
	'train-in-person'  => 3,
	'cta'              => 1,
);

/**
 * Set a value at a dot-path inside an array, creating levels as needed.
 *
 * Numeric segments index repeater rows: 'source_manual.0.thumb'.
 *
 * @param array  $data  Data, by reference.
 * @param string $path  Dot-path.
 * @param mixed  $value Value to set.
 */
function lp_seed_set_path( array &$data, string $path, $value ): void {
	$keys   = explode( '.', $path );
	$cursor = &$data;

	foreach ( $keys as $key ) {
		$key = ctype_digit( $key ) ? (int) $key : $key;

		if ( ! isset( $cursor[ $key ] ) || ! is_array( $cursor[ $key ] ) ) {
			$cursor[ $key ] = array();
		}

		$cursor = &$cursor[ $key ];
	}

	$cursor = $value;
	unset( $cursor );
}

/**
 * Build the QA page's Flexible Content rows from the blocks on disk.
 *
 * There is no list of blocks here: the folders under blocks/ ARE the list, the
 * same rule lp_render_sections() follows. A block added tomorrow appears on the
 * QA page without this file changing.
 *
 * @param array<string,int> $media Filename => attachment ID.
 * @return array<int,array>
 */
function lp_seed_rows( array $media ): array {
	$rows = array();

	foreach ( (array) glob( get_theme_file_path( 'blocks' ) . '/*', GLOB_ONLYDIR ) as $dir ) {
		$slug    = basename( $dir );
		$layout  = str_replace( '-', '_', $slug );
		$example = $dir . '/example.json';

		if ( ! is_readable( $example ) ) {
			WP_CLI::warning( "blocks/{$slug} has no example.json — skipping it." );
			continue;
		}

		$data = json_decode( (string) file_get_contents( $example ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! is_array( $data ) ) {
			WP_CLI::warning( "blocks/{$slug}/example.json is not valid JSON — skipping it." );
			continue;
		}

		// Attachment IDs for the fields this block's media map names.
		$map_file = $dir . '/example.media.json';

		if ( is_readable( $map_file ) ) {
			$map = json_decode( (string) file_get_contents( $map_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			foreach ( (array) $map as $path => $file ) {
				if ( isset( $media[ $file ] ) ) {
					lp_seed_set_path( $data, (string) $path, $media[ $file ] );
				} else {
					WP_CLI::warning( "blocks/{$slug}: no demo image '{$file}' — {$path} left unset." );
				}
			}
		}

		$rows[] = array_merge( array( 'acf_fc_layout' => $layout ), $data );

		// The CPT twin, immediately after its manual counterpart so the two can
		// be compared without scrolling. Any divergence is a projection bug.
		if ( isset( LP_SEED_CPT_ROWS[ $slug ] ) ) {
			$cpt = $data;
			unset( $cpt['source_manual'] );

			$cpt['source']       = 'latest';
			$cpt['source_limit'] = LP_SEED_CPT_ROWS[ $slug ];

			$rows[] = array_merge( array( 'acf_fc_layout' => $layout ), $cpt );
		}
	}

	return $rows;
}

/**
 * Create or update the Blocks QA page.
 *
 * @param array<string,int> $media Filename => attachment ID.
 */
function lp_seed_page( array $media ): void {
	$slug     = 'blocks-qa';
	$existing = lp_seed_find( 'page', $slug );

	if ( $existing && ! lp_seed_is_ours( $existing ) ) {
		WP_CLI::warning( "A page at '{$slug}' exists and was not created by seed — skipping it." );
		return;
	}

	$postarr = array(
		'post_type'   => 'page',
		'post_title'  => 'Blocks QA',
		'post_name'   => $slug,
		'post_status' => 'publish',
	);

	if ( $existing ) {
		$postarr['ID'] = $existing;
		$id            = wp_update_post( $postarr, true );
	} else {
		$id = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'Could not write the QA page: ' . $id->get_error_message() );
	}

	$id   = (int) $id;
	$rows = lp_seed_rows( $media );

	update_post_meta( $id, LP_SEED_MARKER, 1 );
	update_field( 'page_sections', $rows, $id );

	WP_CLI::log( sprintf( '  + page %s (#%d) with %d row(s)', $slug, $id, count( $rows ) ) );
}
```

- [ ] **Step 2: Call it from the command**

In the `lp seed` closure, immediately before `WP_CLI::success( 'Seeded.' );`:

```php
		WP_CLI::log( 'Blocks QA page' );
		lp_seed_page( $media );
```

- [ ] **Step 3: Lint and re-seed**

```bash
php -l app/setup/seed.php
bin/wp lp seed --fresh
```

Expected: the run ends with `+ page blocks-qa (#N) with 16 row(s)` then `Success: Seeded.`

- [ ] **Step 4: Verify the row count and ordering**

```bash
bin/wp eval '$p = get_page_by_path( "blocks-qa" ); foreach ( (array) get_field( "page_sections", $p->ID ) as $i => $r ) { printf( "%2d %-18s %s\n", $i, $r["acf_fc_layout"], $r["source"] ?? "-" ); }'
```

Expected: 16 lines. Every block appears once with `manual` (or `-` where it has no source control), and the six in `LP_SEED_CPT_ROWS` appear a second time with `latest` on the line immediately after.

- [ ] **Step 5: Verify the page renders every block**

```bash
curl -s http://localhost:8102/blocks-qa/ | grep -o 'data-component="[a-z-]*"' | sort | uniq -c
```

Expected: a `data-component` for all ten blocks, with the six source-backed ones appearing twice.

- [ ] **Step 6: Verify images reached the page**

```bash
curl -s http://localhost:8102/blocks-qa/ | grep -c 'srcset='
```

Expected: at least `4` — one per `example.media.json` target that is a top-level media field, plus the coach thumbs.

- [ ] **Step 7: Verify the differential — CPT rows match their manual twins**

This is the point of the whole page. Compare the two Locations rows:

```bash
curl -s http://localhost:8102/blocks-qa/ | grep -o 'Peckham Rye\|Southbank Undercroft\|Stratford East\|Hackney Marshes\|Wembley Park' | sort | uniq -c
```

Expected: each site name appears exactly **twice** — once from the manual row, once from the CPT row. A count of 1 means the CPT projection dropped it.

- [ ] **Step 8: Verify idempotency of the page**

```bash
bin/wp lp seed
bin/wp post list --post_type=page --name=blocks-qa --format=count
```

Expected: `1`. A second page means `lp_seed_find()` is not matching on re-run.

- [ ] **Step 9: Open it and look**

```bash
open http://localhost:8102/blocks-qa/
```

Read down the page. For each of the six paired blocks, the CPT row should be visually indistinguishable from its manual twin except where the demo data genuinely differs. **Two known, accepted divergences** — confirm these and nothing else:

1. **Coaches location case.** Manual rows say `PECKHAM`; CPT rows resolve to the real post title `Peckham Rye`. The source component uppercases short forms that the CPT does not store.
2. **Coaches roster faces repeat.** Six committed images cannot give five coaches a unique portrait.

Anything else is a bug — record it in `docs/PORT-FINDINGS.md` §13 during Task 9.

---

### Task 8: Remove the dead `style` control

`lp_field_action()` emits a `solid|ghost|text` button_group and `lp_action()` returns it, but **no block reads it** — every button variant is hardcoded. `lp_action()`'s `'solid'` default is not even a valid `button.php` variant (`primary|inverse|ghost|destructive|icon|band`). The Storybook has no per-CTA style choice; Phase 4 diffed the class strings byte-for-byte. The control is invention with no consumer, so it goes.

**Files:**
- Modify: `app/setup/acf-fields.php` — `lp_field_action()` and `lp_action()`
- Modify: `blocks/classes/example.json`, `coaches/example.json`, `cta/example.json`, `hero/example.json`, `private-coaching/example.json`, `train-in-person/example.json` — drop `"style"` keys

**Interfaces:**
- Consumes: nothing.
- Produces: `lp_action()` returns `array{label:string, href:string, target:string}|null` — the `style` key is gone. No caller reads it today; confirmed in Step 1.

- [ ] **Step 1: Prove no consumer exists before deleting anything**

```bash
grep -rn "\['style'\]" blocks/ parts/ inc/ app/ *.php
```

Expected: **no output**. If there is any hit, stop — a consumer exists and this task's premise is wrong. Report it and skip to Task 9.

- [ ] **Step 2: Reduce `lp_field_action()` to the link**

Replace the `sub_fields` array and update the docblock's last paragraph:

```php
/**
 * A call to action.
 *
 * Every CTA in this theme is an ACF **Link** field
 * (https://www.advancedcustomfields.com/resources/link/). The Link field already
 * carries title, url and target, so there is deliberately NO separate label
 * text field — the link's own title IS the button label. One control, one place
 * to edit, and internal links keep working when a permalink changes.
 *
 * There is deliberately no style control either. Button variants are fixed by
 * the design and hardcoded in each block, exactly as the Storybook has them; a
 * `style` button_group shipped here for one phase with no block reading it,
 * which is a control an editor can move that changes nothing. Add one back only
 * when a design actually offers the choice.
 *
 * @param string $name  Field name, e.g. 'primary_action'.
 * @param string $label Field label, e.g. 'Primary action'.
 * @param array  $overrides Group-level overrides.
 * @return array
 */
function lp_field_action( string $name = 'primary_action', string $label = '', array $overrides = array() ): array {
	return array_merge(
		array(
			'name'       => $name,
			'label'      => $label ?: __( 'Primary action', 'londonparkour_v8' ),
			'type'       => 'group',
			'layout'     => 'block',
			'sub_fields' => array(
				array(
					'name'          => 'link',
					'label'         => __( 'Link', 'londonparkour_v8' ),
					'type'          => 'link',
					'return_format' => 'array',
					'instructions'  => __( 'The link text is the button label.', 'londonparkour_v8' ),
				),
			),
		),
		$overrides
	);
}
```

- [ ] **Step 3: Drop `style` from `lp_action()`**

Change the return block and the docblock's `@return` line:

```php
 * @return array{label:string, href:string, target:string}|null
```

```php
	return array(
		'label'  => $label,
		'href'   => $href,
		'target' => (string) ( $link['target'] ?? '' ),
	);
```

- [ ] **Step 4: Drop the dead keys from the fixtures**

Six files carry `"style"` inside an action group. Remove the `"style"` line from each, and the trailing comma from the `"link"` line above it where it becomes the last entry:

- `blocks/classes/example.json` — `primary_action`
- `blocks/coaches/example.json` — `link_action`
- `blocks/cta/example.json` — `primary_action`, `alt_action`
- `blocks/hero/example.json` — `primary_action`, `secondary_action`
- `blocks/private-coaching/example.json` — `primary_action`, `secondary_action`
- `blocks/train-in-person/example.json` — `primary_action`

For example, `blocks/hero/example.json`'s two actions become:

```json
  "primary_action": {
    "link": { "title": "BOOK YOUR FIRST CLASS", "url": "/book/", "target": "" }
  },
  "secondary_action": {
    "link": { "title": "Watch a session ↗", "url": "/watch/", "target": "" }
  }
```

- [ ] **Step 5: Confirm no `style` key survives in any fixture**

```bash
grep -rn '"style"' blocks/*/example.json
```

Expected: no output.

- [ ] **Step 6: Verify the JSON still parses**

```bash
for f in blocks/*/example.json; do php -r 'json_decode(file_get_contents($argv[1]),true); echo $argv[1], ": ", json_last_error_msg(), PHP_EOL;' "$f"; done
```

Expected: `No error` for all ten.

- [ ] **Step 7: Rebuild the field groups and re-render every block**

```bash
php -l app/setup/acf-fields.php
bin/wp lp acf:build
bin/wp lp acf:build --check
for d in blocks/*/; do
  l=$(basename "$d" | tr '-' '_')
  bin/wp lp render "$l" >/dev/null || echo "FAIL $l"
done
```

Expected: `Success` from the check, and no `FAIL` lines.

- [ ] **Step 8: Confirm the buttons still render their labels**

```bash
bin/wp lp render hero | grep -o 'BOOK YOUR FIRST CLASS\|Watch a session'
```

Expected: both strings. The label comes from the Link title, which is untouched.

- [ ] **Step 9: Re-seed so the page picks up the new fixtures**

```bash
bin/wp lp seed --fresh
curl -s http://localhost:8102/blocks-qa/ | grep -c 'BOOK YOUR FIRST CLASS'
```

Expected: at least `1`.

- [ ] **Step 10: Run the standing gates**

```bash
bash bin/audit-reuse.sh
npm run build
```

Expected: `✓`, and a clean Vite build.

---

### Task 9: Documentation

The two-dev contract is the deliverable here, not a nicety — it is the whole answer to "how do we work across two machines".

**Files:**
- Create: `bin/README.md`, `README.md`
- Modify: `docs/PORT-FINDINGS.md` (append §13), `docs/HANDOFF.md`, `bin/bootstrap.sh:98-99`

**Interfaces:**
- Consumes: everything above.
- Produces: no code.

- [ ] **Step 1: Write `bin/README.md`**

````markdown
# bin/

Tooling. Everything here runs from `themes/londonparkour_v8/`.

## The rule

**The database is disposable. Never share it. The content definition is code.**

There is no SQL dump in this repository and there should never be one. `.sql`
files do not merge — auto-increment IDs and serialized data guarantee a conflict
no one can resolve by hand — and sharing a database forces an out-of-band
protocol about who is allowed to edit it. This project already made the opposite
bet in Phase 1: ACF field groups are generated from PHP so that the database is
never authoritative. Content follows the same rule.

Two developers do not need the same database. They need the same content
definition, in files that merge.

## Getting a working site

```bash
docker compose up -d          # from the repo root
bin/bootstrap.sh              # install, activate, build field groups
bin/wp lp seed                # demo content for QA
npm install && npm run build  # assets — assets/dist is gitignored
```

Site: http://localhost:8102 · admin `admin` / `admin` · QA page: `/blocks-qa/`

## Day to day

| Task | Command |
|---|---|
| Reset demo content | `bin/wp lp seed --fresh` |
| Nuke and rebuild | `docker compose down -v`, then the four commands above |
| Changed a field group | edit PHP → `bin/wp lp acf:build` → commit `acf-json/` |
| Changed a demo image | replace the file in `bin/demo-media/` → `bin/wp lp seed --fresh` → `bin/wp media regenerate --yes` |
| Verify a block in isolation | `bin/wp lp render <layout>` |
| Verify a partial | `bin/wp lp part components/<name>` |

`docker compose down -v` destroys the database. That is the supported recovery
path, not a disaster — bootstrap and seed rebuild it in under a minute.

## Where demo content lives

| Path | Owns |
|---|---|
| `blocks/*/example.json` | a block's manual-mode content |
| `blocks/*/example.media.json` | field dot-path → demo image filename |
| `bin/demo-content/*.json` | CPT records and taxonomy terms |
| `bin/demo-media/*.jpeg` | the photographs, 1920×1280 |

**Anything you author in wp-admin that matters moves into these files, or it
does not exist for the other developer.** That is the whole contract. If you
tweaked a demo class in the admin and want it kept, edit
`bin/demo-content/lp_class.json` to match and re-seed.

Conflicts resolve as ordinary JSON and PHP merges, because that is all they are.

## Safety

Every post `wp lp seed` creates carries `_lp_seed` post meta. Seed only ever
updates or deletes posts carrying that marker, so a page you wrote by hand
cannot be touched — even if its slug collides with a demo slug. In that case
seed warns and skips.

## The scripts

| File | What it does |
|---|---|
| `bootstrap.sh` | Idempotent install: core, theme, plugins, permalinks, menus, front page, field groups. Never touches content. |
| `wp` | WP-CLI wrapper. WP-CLI is not on the host; it runs in the `cli` sidecar. Always use this, never bare `wp`. |
| `audit-reuse.sh` | Fails the build on hand-rolled markup — a raw `<svg>`, a raw `<img>`, or a built class string. Verified by injection; trust it. |
| `demo-content/` | CPT records and terms, read by `wp lp seed`. |
| `demo-media/` | Demo photographs, read by `wp lp seed`. |

`wp lp seed` itself is not here — it needs WordPress bootstrapped, so it lives
in `app/setup/seed.php` like every other `wp lp *` command.
````

- [ ] **Step 2: Write the theme `README.md`**

````markdown
# londonparkour_v8

Classic-hierarchy WordPress theme, ported from the `ldnpark2601` Storybook
design system. Page structure comes from a single ACF Flexible Content field
whose layouts are the folders under `blocks/`; Gutenberg is disabled.

## Quick start

```bash
docker compose up -d          # from the repo root
bin/bootstrap.sh
bin/wp lp seed
npm install && npm run build
```

http://localhost:8102 · `admin` / `admin` · QA page at `/blocks-qa/`

**Read `bin/README.md` before touching content or the database.**

## Layout

| Path | Holds |
|---|---|
| `app/setup/` | CPTs, ACF field definitions and generation, seeding, theme supports |
| `app/includes/` | The Flexible Content dispatcher, menus, HTML helpers |
| `blocks/<slug>/` | One block: markup, `fields.php`, `example.json` |
| `parts/elements/` | One file per piece of HTML. The only way to emit markup. |
| `parts/components/` | Composed pieces built from elements |
| `parts/site/` | Nav and footer |
| `bin/` | Tooling and demo content — see its README |
| `docs/` | The port's contract, findings and history |

## Rules that are enforced

- **`lp_part( 'elements/button', $args )` is the only way to emit an element.**
  `bin/audit-reuse.sh` fails the build on hand-rolled markup.
- **Never a raw `<svg>`** outside `parts/brand/` — use `lp_icon( $id, $classes )`.
- **`lp_classes()` joins WHOLE literal class strings**, never builds one.
  Tailwind v4 text-scans source, so a built string is not compiled.
- **ACF field groups are generated.** Edit PHP, run `bin/wp lp acf:build`.
  Editing `acf-json/` by hand is overwritten.

## Verify anything

```bash
php -l <file>
bash bin/audit-reuse.sh          # must print ✓
bin/wp lp acf:build --check      # must print Success
bin/wp lp render <layout_name>   # one block, no database
bin/wp lp part <slug>            # one partial
npm run build
```

## Documentation

| File | What it owns |
|---|---|
| `docs/PORT-BRIEF.md` | The porting contract. Hand it verbatim to any agent. |
| `docs/PORT-FINDINGS.md` | Discrepancies found and deliberately not fixed |
| `docs/CONSOLIDATION.md` | The shared-atom analysis behind `parts/` |
| `docs/HANDOFF.md` | Current state and what remains |
| `docs/specs/`, `docs/plans/` | Design and implementation records |
````

- [ ] **Step 3: Append §13 to `docs/PORT-FINDINGS.md`**

Append, matching the file's existing heading style — open it first and copy the `##` level and numbering format used by §12.

```markdown
## 13. The CPT source path had never executed

Found while planning the Blocks QA page. Every block was verified with
`bin/wp lp render` against its `example.json`, and every `example.json` is
`"source": "manual"` — so `lp_resolve_source()`'s `latest` and `choose` branches
had never run for any block. Seeding demo records was the first time.

Five of the six source-backed blocks were wrong in CPT mode. Two root causes:

1. **Post-object fields return IDs.** `location` on `lp_class` and `lp_coach` is
   `return_format => 'id'`; blocks do `(string) $item['location']` and rendered a
   number. Fixed by `lp_flatten_references()` in `acf-fields.php`, driven by
   `lp_source_reference_fields()` — a map shaped like the `lp_source_taxonomy_for()`
   beside it.
2. **Sessions live one level down.** `lp_class` holds a `sessions` repeater;
   Hero, Classes and CTA read `time` and `spaces` flat off the class. Fixed by
   `lp_expand_sessions()` behind `lp_resolve_source()`'s `'expand' => 'sessions'`
   option, which `cpt.php` had already nominated as the seam. **`source_limit`
   on those three blocks counts sessions, not classes** — the Hero board has a
   fixed slot count.

Two smaller items in the same pass:

3. **Field-name drift between a CPT and its consumer.** `locations` read `type`
   while `lp_location` stored `site_type`; `classes` read `date_label` while
   `lp_class` stored `day_label`. `acf-groups.php`'s own header settles it —
   "field shapes are taken from the Storybook components that consume them" — so
   the CPT was renamed in both cases. Done before any content existed, when
   changing a derived `field_` key is still free.
4. **The `style` control on every CTA was dead.** `lp_field_action()` emitted a
   `solid|ghost|text` button_group and `lp_action()` returned it, but no block
   read it — every variant is hardcoded, and `lp_action()`'s `'solid'` default is
   not a valid `button.php` variant. The Storybook offers no per-CTA style
   choice, so the control was removed rather than wired up.

**The lesson for the remaining phases.** `lp render` against `example.json`
proves markup, not data flow. Any block or template reading a CPT needs a render
against seeded records too — `bin/wp lp render <layout> --args='{"source":"latest"}'`
is now that check, and `/blocks-qa/` runs it for all six continuously.

### Accepted divergences on the QA page

Not bugs; do not "fix" them.

- **Coaches location case.** Manual rows say `PECKHAM`; CPT rows resolve to the
  post title `Peckham Rye`. The source component uppercases a short form the CPT
  does not store.
- **Coaches roster faces repeat.** Six committed demo images cannot give five
  coaches a unique portrait.
```

- [ ] **Step 4: Update `bin/bootstrap.sh`'s closing message**

Lines 98–99 currently point at a `bin/README.md` section for content. Replace them with:

```bash
echo "  Content is NOT seeded. For demo content and the QA page, run:"
echo "    bin/wp lp seed"
echo "  See bin/README.md for the two-developer database contract."
```

- [ ] **Step 5: Verify bootstrap still runs clean**

```bash
bash -n bin/bootstrap.sh
bin/bootstrap.sh
```

Expected: no syntax error, and an idempotent run ending in `Done` with the new message.

- [ ] **Step 6: Update `docs/HANDOFF.md`**

Three edits:

1. In the **Remaining** table, change the Phase 6 row to note that `wp lp seed`,
   `bin/README.md` and `README.md` are **done**, and that what remains of Phase 6
   is homepage seeding (deliberately deferred — see the spec's Out of Scope).
2. In the header **State** paragraph, add that `page.php` now renders
   `page_sections` and a `/blocks-qa/` page exists for visual QA.
3. In **Phase 5b**, change the `page.php` (Legal) row from "scaffold only" to
   "renders sections + prose; the Legal prose treatment is still to port", and
   add a line under "Suggested order" pointing at `/blocks-qa/` as the way to
   eyeball any block while working on a template.

- [ ] **Step 7: Full end-to-end verification from a clean database**

The real test of the two-dev contract is that a fresh machine gets a working
site. Simulate it:

```bash
cd /Users/wearebold/Sites/WordPress/londonparkour_v8
docker compose down -v
docker compose up -d
themes/londonparkour_v8/bin/bootstrap.sh
cd themes/londonparkour_v8
bin/wp lp seed
npm run build
curl -s http://localhost:8102/blocks-qa/ | grep -o 'data-component="[a-z-]*"' | sort -u | wc -l
```

Expected: `10` distinct block components on the page, from nothing but a git
checkout and Docker. If this fails, the contract is broken and the docs are
lying — fix it before finishing.

- [ ] **Step 8: Run every standing gate one final time**

```bash
bash bin/audit-reuse.sh
bin/wp lp acf:build --check
for d in blocks/*/; do l=$(basename "$d" | tr '-' '_'); bin/wp lp render "$l" >/dev/null || echo "FAIL $l"; done
for f in parts/components/*.php; do bin/wp lp part "components/$(basename "$f" .php)" >/dev/null || echo "FAIL $f"; done
npm run build
```

Expected: `✓`, `Success`, no `FAIL` lines, clean build.

---

## Self-Review

**Spec coverage.** Every spec section maps to a task: `page.php` → 1; CPT-path
findings → 2, 5, 6, 8; demo media → 3; data files and `wp lp seed` safety model →
4; generated QA page → 7; two-dev contract → 9. The spec's "Out of scope" items
(homepage seeding, variant rows, production content) have no task, correctly.

**Placeholder scan.** No TBDs. Every code step carries the actual code; every
verification step carries the actual command and its expected output. The two
places that say "read the file first" (Task 9 Step 3's heading style, Task 9
Step 6's HANDOFF edits) are prose-editing steps where copying the surrounding
convention is the instruction, not a gap.

**Type consistency.** `lp_seed_media()` returns filename→ID and is passed to
`lp_seed_posts()` and `lp_seed_page()` as `$media` throughout. `lp_seed_find()`
returns `int` (0 for none) and every caller treats 0 as absent.
`lp_resolve_source()`'s third parameter is `array $opts` everywhere, with the
single key `'expand' => 'sessions'`. `LP_SEED_CPT_ROWS` is keyed by **folder
slug** (`train-in-person`) while `acf_fc_layout` uses the **underscored** name
(`train_in_person`); Task 7 Step 1 converts between them explicitly.

**Known risk.** Task 7's `update_field( 'page_sections', $rows, $id )` relies on
ACF resolving field *names* inside Flexible Content rows. If ACF stores the rows
but the page renders empty, the fix is to key each row's sub-values by field key
rather than name — read the keys from `acf-json/group_lp_page_sections.json`.
Task 7 Step 4 catches this before Step 5 would confuse it with a template bug.
