<?php
/**
 * Demo content seeding — `wp lp seed`.
 *
 * Seeds tutorials, blog posts, menus, template pages, and Flexible Content
 * fixtures. Does NOT create, update, or delete `lp_location`, `lp_coach`, or
 * `clasbpro_class` — those are editor-owned. Never mass-wipe clasbpro classes.
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
 * Which post type each reference field points at.
 *
 * Kept beside the seeder rather than derived from ACF: it is demo-content
 * knowledge, not field knowledge, and ACF cannot tell us what a JSON string
 * was meant to mean. Location/coach/class CPTs are not seeded — do not add
 * refs that invent those records.
 */
const LP_SEED_REFS = array(
	// Tutorials may still name coaches by slug when those posts already exist.
	'coaches' => 'lp_coach',
);

/**
 * Which source-backed blocks get a second, CPT-backed row, and how many items.
 *
 * The only per-block knowledge seed holds. It is coordination data — how big a
 * demo list should be — not block data, which lives in fields.php. Keyed by
 * FOLDER slug; the layout name is the underscored form.
 *
 * CTA shows one session, so it asks for one.
 */
const LP_SEED_CPT_ROWS = array(
	'hero'            => 4,
	'classes'         => 7,
	'coaches'         => 5,
	'locations'       => 5,
	'train-in-person' => 3,
	'cta'             => 1,
	'tutorials'       => 4,
);

/**
 * Homepage Flexible Content order — Storybook Homepage V8 (`Homepage.js`).
 * Front-page.php records the same sequence; seed writes it every run.
 */
const LP_SEED_HOMEPAGE_ORDER = array(
	'hero',
	'marquee',
	'classes',
	'pricing',
	'private-coaching',
	'statement',
	'workshop',
	'clients',
	'tutorials',
	'testimonials',
	'locations',
	'coaches',
	'cta',
);

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
			'post_type'      => $post_type,
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
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

		$existing = lp_attachment_id_for_file( $file );
		if ( $existing ) {
			$map[ $name ] = $existing;
			continue;
		}

		$id = lp_sideload_image_once(
			$file,
			array(
				'post_title' => pathinfo( $name, PATHINFO_FILENAME ),
			)
		);

		if ( is_wp_error( $id ) ) {
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
 * Create the demo taxonomy terms (and refresh term meta on re-seed).
 */
function lp_seed_terms(): void {
	foreach ( lp_seed_read_json( 'bin/demo-content/terms.json' ) as $taxonomy => $terms ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			WP_CLI::warning( "Taxonomy {$taxonomy} is not registered — skipping its terms." );
			continue;
		}

		foreach ( $terms as $term ) {
			$existing = term_exists( $term['slug'], $taxonomy );
			$term_id  = 0;

			if ( $existing ) {
				$term_id = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
			} else {
				$result = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
				if ( is_wp_error( $result ) ) {
					WP_CLI::warning( "Could not create {$taxonomy}/{$term['slug']}: " . $result->get_error_message() );
					continue;
				}
				$term_id = (int) $result['term_id'];
				WP_CLI::log( "  + term {$taxonomy}/{$term['slug']}" );
			}

			$fields = $term['fields'] ?? null;
			if ( ! is_array( $fields ) || ! $term_id ) {
				continue;
			}

			foreach ( $fields as $name => $value ) {
				update_field( (string) $name, $value, 'term_' . $term_id );
			}
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
 * Create or update the demo records of one post type.
 *
 * @param string            $post_type Post type.
 * @param array<string,int> $media     Filename => attachment ID.
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

		/*
		 * Native posts carry their body, excerpt and date in the post row
		 * rather than in ACF, which the CPTs do not — a class is all fields.
		 * All three are optional, so this stays generic: any record may set
		 * them and the CPT files that do not are unaffected. `date` is worth
		 * seeding explicitly because a blog index orders by it and a byline
		 * prints it, so leaving every post at "now" hides both.
		 */
		foreach ( array( 'content' => 'post_content', 'excerpt' => 'post_excerpt', 'date' => 'post_date' ) as $lp_key => $lp_field ) {
			if ( isset( $record[ $lp_key ] ) ) {
				$postarr[ $lp_field ] = (string) $record[ $lp_key ];
			}
		}

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
			update_field( $name, lp_seed_weekdays( $value ), $id );
		}

		WP_CLI::log( "  + {$post_type} {$slug} (#{$id})" );
	}

	return $ids;
}

/**
 * Expand `@MON`…`@SUN` weekday tokens in a seed value to real dates.
 *
 * A timetable has to sit in the week you are looking at, and the demo content
 * is committed code that would otherwise name a fixed week and go stale the
 * day after it was written. The token resolves against the week containing the
 * seed run, so `bin/wp lp seed` always produces a board with sessions on it.
 *
 * Emits ACF's own `Ymd` storage format, so the `date` sub-field's
 * `return_format` governs what `get_field()` hands back.
 *
 * Recurses, because sub-field values arrive inside repeater rows.
 *
 * @param mixed $value A seed field value.
 * @return mixed The value with any weekday tokens replaced.
 */
function lp_seed_weekdays( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'lp_seed_weekdays', $value );
	}

	$days = array( 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN' );

	if ( ! is_string( $value ) || ! preg_match( '/^@(' . implode( '|', $days ) . ')$/', $value, $match ) ) {
		return $value;
	}

	// PHP's "this week" is ISO — Monday-start — including on a Sunday.
	$monday = new DateTimeImmutable( 'monday this week' );
	$offset = (int) array_search( $match[1], $days, true );

	return $monday->modify( sprintf( '+%d days', $offset ) )->format( 'Ymd' );
}

/**
 * Apply an optional example.refs.json map (dot-path => "post_type:slug").
 *
 * Parallel to example.media.json. Used so Flexible Content rows can name
 * editor-owned clasbpro packs/classes without baking numeric IDs into git.
 *
 * @param array  $data Data, by reference.
 * @param string $dir  Block directory path.
 */
function lp_seed_apply_refs( array &$data, string $dir ): void {
	$map_file = $dir . '/example.refs.json';
	if ( ! is_readable( $map_file ) ) {
		return;
	}

	$map = json_decode( (string) file_get_contents( $map_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! is_array( $map ) ) {
		WP_CLI::warning( 'Invalid example.refs.json in ' . basename( $dir ) );
		return;
	}

	foreach ( $map as $path => $spec ) {
		$spec = (string) $spec;
		if ( ! str_contains( $spec, ':' ) ) {
			WP_CLI::warning( "Bad ref '{$spec}' at {$path} — expected post_type:slug." );
			continue;
		}
		[ $post_type, $slug ] = explode( ':', $spec, 2 );
		$id                   = lp_seed_ref( $post_type, $slug );
		if ( $id ) {
			lp_seed_set_path( $data, (string) $path, $id );
		}
	}
}

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

		lp_seed_apply_refs( $data, $dir );

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

/**
 * Load one block's example.json (and optional example.media.json) as an FC row.
 *
 * @param string            $slug  Folder slug under blocks/.
 * @param array<string,int> $media Filename => attachment ID.
 * @return array|null
 */
function lp_seed_block_row( string $slug, array $media ): ?array {
	$dir     = get_theme_file_path( 'blocks/' . $slug );
	$layout  = str_replace( '-', '_', $slug );
	$example = $dir . '/example.json';

	if ( ! is_readable( $example ) ) {
		WP_CLI::warning( "blocks/{$slug} has no example.json — skipping homepage row." );
		return null;
	}

	$data = json_decode( (string) file_get_contents( $example ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( ! is_array( $data ) ) {
		WP_CLI::warning( "blocks/{$slug}/example.json is not valid JSON — skipping homepage row." );
		return null;
	}

	$map_file = $dir . '/example.media.json';
	if ( is_readable( $map_file ) ) {
		$map = json_decode( (string) file_get_contents( $map_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		foreach ( (array) $map as $path => $file ) {
			if ( isset( $media[ $file ] ) ) {
				lp_seed_set_path( $data, (string) $path, $media[ $file ] );
			}
		}
	}

	lp_seed_apply_refs( $data, $dir );

	return array_merge( array( 'acf_fc_layout' => $layout ), $data );
}

/**
 * Seed the front page with the Homepage V8 Flexible Content stack.
 *
 * @param array<string,int> $media Filename => attachment ID.
 */
function lp_seed_homepage( array $media ): void {
	$front_id = (int) get_option( 'page_on_front' );

	if ( ! $front_id ) {
		$front_id = lp_seed_find( 'page', 'home' );
	}

	if ( ! $front_id ) {
		WP_CLI::warning( 'No front page found — run bin/bootstrap.sh before seeding homepage rows.' );
		return;
	}

	$rows = array();
	foreach ( LP_SEED_HOMEPAGE_ORDER as $slug ) {
		$row = lp_seed_block_row( $slug, $media );
		if ( ! $row ) {
			continue;
		}
		// Homepage boards should hit real clasbpro rows so BOOK opens the drawer.
		if ( isset( LP_SEED_CPT_ROWS[ $slug ] ) ) {
			unset( $row['source_manual'] );
			$row['source']       = 'latest';
			$row['source_limit'] = LP_SEED_CPT_ROWS[ $slug ];
		}
		$rows[] = $row;
	}

	update_field( 'page_sections', $rows, $front_id );
	WP_CLI::log( sprintf( '  + homepage (#%d) with %d row(s)', $front_id, count( $rows ) ) );
}

/**
 * Seed one page per page template, with `_wp_page_template` set.
 *
 * A page template is unverifiable without a page that uses it — `lp render`
 * cannot reach one, and greps prove nothing (PORT-FINDINGS §13). This is the
 * Phase 6 item docs/HANDOFF.md lists as "pages + their templates".
 *
 * Keyed by slug. The template key is what `_wp_page_template` stores and what
 * an ACF `page_template` location rule must match: `templates/<file>.php`.
 *
 * Agenda is the default Classes page at slug `classes` (template still
 * `templates/classes-agenda.php`). Map is `classes-map`. The CPT listings
 * archive lives at `/all-classes/` so it does not collide. See
 * lp_classes_page_url() in app/includes/content.php.
 */
function lp_seed_template_pages(): void {
	$pages = array(
		'legal'            => array( 'Legal', 'templates/legal.php' ),
		'classes'          => array( 'Classes', 'templates/classes-agenda.php' ),
		'classes-map'      => array( 'Classes — Map', 'templates/classes-map.php' ),
		'workshops'        => array( 'Workshops', 'templates/workshops-overview.php' ),
		'private-coaching' => array( 'Private 1:1', 'templates/private-coaching.php' ),
		'contact'          => array( 'Contact', 'templates/contact.php' ),
		'docs'             => array( 'Docs', 'templates/docs-faq.php' ),
		'tutorials-series'   => array( 'Tutorials — Series', 'templates/tutorials-series.php' ),
		'tutorials-category' => array( 'Tutorials — Category', 'templates/tutorials-category.php' ),
	);

	$sections = array(
		'contact'  => array( 'enquiries', 'other-ways', 'faq' ),
	);

	foreach ( $pages as $slug => $page ) {
		list( $title, $template ) = $page;

		if ( ! is_readable( get_theme_file_path( $template ) ) ) {
			WP_CLI::warning( "No template file {$template} — skipping page '{$slug}'." );
			continue;
		}

		$existing = lp_seed_find( 'page', $slug );

		// Migrate the pre-rename Agenda slug so re-seed does not leave a duplicate.
		if ( ! $existing && 'classes' === $slug ) {
			$existing = lp_seed_find( 'page', 'classes-agenda' );
		}

		if ( ! $existing && 'docs' === $slug ) {
			$existing = lp_seed_find( 'page', 'docs-faq' );
		}

		if ( $existing && ! lp_seed_is_ours( $existing ) ) {
			WP_CLI::warning( "A page at '{$slug}' exists and was not created by seed — skipping it." );
			continue;
		}

		$postarr = array(
			'post_type'   => 'page',
			'post_title'  => $title,
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
			WP_CLI::warning( "Could not write page '{$slug}': " . $id->get_error_message() );
			continue;
		}

		$id = (int) $id;
		update_post_meta( $id, LP_SEED_MARKER, 1 );
		update_post_meta( $id, '_wp_page_template', $template );

		if ( isset( $sections[ $slug ] ) ) {
			$rows = array();
			foreach ( $sections[ $slug ] as $spec ) {
				$parts = explode( ':', $spec );
				$block = $parts[0];
				$variant = $parts[1] ?? '';
				$example = 'groups' === $variant
					? get_theme_file_path( "blocks/{$block}/example.groups.json" )
					: get_theme_file_path( "blocks/{$block}/example.json" );

				if ( ! is_readable( $example ) ) {
					WP_CLI::warning( "Missing {$example} for page '{$slug}'." );
					continue;
				}

				$data = json_decode( (string) file_get_contents( $example ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( ! is_array( $data ) ) {
					continue;
				}

				$rows[] = array_merge(
					array( 'acf_fc_layout' => str_replace( '-', '_', $block ) ),
					$data
				);
			}
			update_field( 'page_sections', $rows, $id );
			WP_CLI::log( sprintf( '  + page %s (#%d) -> %s (%d sections)', $slug, $id, $template, count( $rows ) ) );
		} else {
			WP_CLI::log( sprintf( '  + page %s (#%d) -> %s', $slug, $id, $template ) );
		}

		if ( 'private-coaching' === $slug && function_exists( 'update_field' ) ) {
			$appt = lp_seed_find( 'clasbpro_class', 'private-sessions' );
			if ( $appt ) {
				update_field( 'appointment_class', $appt, $id );
			}
		}
	}
}

/**
 * Insert one level of menu items, then recurse into their children.
 *
 * @param int   $menu_id Menu term id.
 * @param array $items   Records from menus.json.
 * @param int   $parent  Parent menu item id, 0 at the top level.
 * @return int Number of items written.
 */
function lp_seed_menu_items( int $menu_id, array $items, int $parent = 0 ): int {
	$written  = 0;
	$position = 0;

	foreach ( $items as $item ) {
		++$position;

		/*
		 * A footer column heading is a label with no destination. WordPress has
		 * no "label only" item type, so it is a custom item with an empty url —
		 * which is what lp_menu_columns() already reads as a heading.
		 */
		$url = (string) ( $item['url'] ?? '' );

		$id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => (string) $item['label'],
				'menu-item-url'       => '' === $url ? '' : home_url( $url ),
				'menu-item-type'      => 'custom',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
				'menu-item-position'  => $position,
				// Core explodes this on spaces, so it must be a string.
				'menu-item-classes'   => (string) ( $item['classes'] ?? '' ),
			)
		);

		if ( is_wp_error( $id ) || ! $id ) {
			WP_CLI::warning( "Could not write menu item '{$item['label']}'." );
			continue;
		}

		$id = (int) $id;
		update_post_meta( $id, LP_SEED_MARKER, 1 );
		++$written;

		if ( ! empty( $item['children'] ) ) {
			$written += lp_seed_menu_items( $menu_id, (array) $item['children'], $id );
		}
	}

	return $written;
}

/**
 * Fill the registered nav menus from menus.json.
 *
 * bootstrap.sh creates the menus and assigns them to their locations; this
 * fills them, because an assigned-but-empty menu makes both site partials fall
 * back to their ported defaults and no template ever exercises a real menu.
 */
function lp_seed_menus(): void {
	$locations = get_nav_menu_locations();

	foreach ( lp_seed_read_json( 'bin/demo-content/menus.json' ) as $location => $items ) {
		$menu = wp_get_nav_menu_object( $locations[ $location ] ?? 0 );

		if ( ! $menu ) {
			WP_CLI::warning( "No menu assigned to the '{$location}' location — run bin/bootstrap.sh." );
			continue;
		}

		// Re-seeding replaces our own items and leaves hand-added ones alone,
		// the same contract every other record type here follows.
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $existing ) {
			if ( lp_seed_is_ours( (int) $existing->ID ) ) {
				wp_delete_post( (int) $existing->ID, true );
			}
		}

		$count = lp_seed_menu_items( (int) $menu->term_id, (array) $items );
		WP_CLI::log( "  + {$location} menu, {$count} item(s)" );
	}
}

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

	$removed = 0;
	foreach ( $ids as $id ) {
		$id = (int) $id;
		/*
		 * Never purge locations, coaches, or clasbpro classes — even if an
		 * older seed run tagged them. Those CPTs are editor-owned.
		 */
		$type = get_post_type( $id );
		if ( in_array( $type, array( 'lp_location', 'lp_coach', 'clasbpro_class' ), true ) ) {
			continue;
		}
		wp_delete_post( $id, true );
		++$removed;
	}

	WP_CLI::log( sprintf( '  - removed %d seeded record(s)', $removed ) );
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

		WP_CLI::log( 'Site settings' );
		// Light ground + yellow signal: Pricing white, Clients/Locations accent
		// band, Marquee/CTA/Private Coaching stay yellow (not lime).
		update_field( 'theme_ground', 'light', 'option' );
		update_field( 'theme_signal', 'yellow', 'option' );
		WP_CLI::log( '  + theme = parkour-light-yellow' );

		WP_CLI::log( 'Media' );
		$media = lp_seed_media();

		WP_CLI::log( 'Terms' );
		lp_seed_terms();

		/*
		 * Do NOT seed lp_location, lp_coach, or clasbpro_class — those are
		 * editor-owned. A prior wipe of clasbpro_class destroyed real classes;
		 * never mass-delete or re-create them from demo JSON.
		 */
		foreach ( array( 'lp_tutorial', 'post' ) as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				WP_CLI::warning( "Post type {$post_type} is not registered — skipping." );
				continue;
			}
			WP_CLI::log( $post_type );
			lp_seed_posts( $post_type, $media );
		}

		WP_CLI::log( 'Menus' );
		lp_seed_menus();

		WP_CLI::log( 'Template pages' );
		lp_seed_template_pages();

		if ( post_type_exists( 'support' ) ) {
			WP_CLI::log( 'Support docs fields' );
			$lp_n = lp_docs_seed_support_fields();
			WP_CLI::log( sprintf( '  updated %d support post(s)', $lp_n ) );
		}

		WP_CLI::log( 'Homepage' );
		lp_seed_homepage( $media );

		WP_CLI::log( 'Blocks QA page' );
		lp_seed_page( $media );

		WP_CLI::success( 'Seeded.' );
	},
	array(
		'shortdesc' => 'Seed non-class demo content for QA. Never creates or deletes locations, coaches, or clasbpro classes.',
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
