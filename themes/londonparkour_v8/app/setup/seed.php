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
		$slug = sanitize_title( pathinfo( $name, PATHINFO_FILENAME ) );

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'name'           => $slug,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
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
			wp_delete_file( $tmp );
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
			update_field( $name, $value, $id );
		}

		WP_CLI::log( "  + {$post_type} {$slug} (#{$id})" );
	}

	return $ids;
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
 * Delete every seed-owned post and attachment.
 */
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
		// `post` is the native blog type, which home.php and single.php read;
		// it references nothing, so its position here is not load-bearing.
		foreach ( array( 'lp_location', 'lp_coach', 'lp_class', 'lp_tutorial', 'post' ) as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				WP_CLI::warning( "Post type {$post_type} is not registered — skipping." );
				continue;
			}
			WP_CLI::log( $post_type );
			lp_seed_posts( $post_type, $media );
		}

		WP_CLI::log( 'Menus' );
		lp_seed_menus();

		WP_CLI::log( 'Blocks QA page' );
		lp_seed_page( $media );

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
