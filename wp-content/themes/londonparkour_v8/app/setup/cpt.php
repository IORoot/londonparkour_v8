<?php
/**
 * Custom post types and taxonomies.
 *
 * These are the business entities the design-system blocks read from. A class
 * is the entity ("Beginners Parkour"); a session is a recurring time-slot of
 * it, held as an ACF repeater on the class rather than a fifth post type. If
 * bookings ever need their own records, lp_resolve_source() in acf-fields.php
 * is the seam to change — not the blocks.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types this theme owns, keyed by post-type name.
 *
 * @return array<string, array>
 */
function lp_post_types(): array {
	return array(
		'lp_class'    => array(
			'singular' => __( 'Class', 'londonparkour_v8' ),
			'plural'   => __( 'Classes', 'londonparkour_v8' ),
			'slug'     => 'classes',
			'icon'     => 'dashicons-calendar-alt',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'taxes'    => array( 'lp_level' ),
		),
		'lp_coach'    => array(
			'singular' => __( 'Coach', 'londonparkour_v8' ),
			'plural'   => __( 'Coaches', 'londonparkour_v8' ),
			'slug'     => 'coaches',
			'icon'     => 'dashicons-groups',
			'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'taxes'    => array(),
		),
		'lp_location' => array(
			'singular' => __( 'Location', 'londonparkour_v8' ),
			'plural'   => __( 'Locations', 'londonparkour_v8' ),
			'slug'     => 'locations',
			'icon'     => 'dashicons-location',
			'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'taxes'    => array(),
		),
		'lp_tutorial' => array(
			'singular' => __( 'Tutorial', 'londonparkour_v8' ),
			'plural'   => __( 'Tutorials', 'londonparkour_v8' ),
			'slug'     => 'tutorials',
			'icon'     => 'dashicons-video-alt3',
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'taxes'    => array( 'lp_level', 'lp_series' ),
		),
	);
}

/**
 * Taxonomies this theme owns, keyed by taxonomy name.
 *
 * @return array<string, array>
 */
function lp_taxonomies(): array {
	return array(
		'lp_level'  => array(
			'singular'     => __( 'Level', 'londonparkour_v8' ),
			'plural'       => __( 'Levels', 'londonparkour_v8' ),
			'slug'         => 'level',
			'hierarchical' => true,
			'post_types'   => array( 'lp_class', 'lp_tutorial' ),
		),
		'lp_series' => array(
			'singular'     => __( 'Series', 'londonparkour_v8' ),
			'plural'       => __( 'Series', 'londonparkour_v8' ),
			'slug'         => 'series',
			'hierarchical' => true,
			'post_types'   => array( 'lp_tutorial' ),
		),
	);
}

/**
 * lp_* post types and taxonomies are registered by ACF (acf-json/post_type_lp*.json).
 * lp_post_types() / lp_taxonomies() are the source for JSON generation — see
 * `wp lp acf:build` in app/setup/acf-build.php.
 */

/**
 * Build a WordPress labels array from a singular and plural name.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array
 */
function lp_labels( string $singular, string $plural ): array {
	/* translators: %s: post type singular or plural name. */
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'menu_name'          => $plural,
		'all_items'          => sprintf( __( 'All %s', 'londonparkour_v8' ), $plural ),
		'add_new_item'       => sprintf( __( 'Add New %s', 'londonparkour_v8' ), $singular ),
		'edit_item'          => sprintf( __( 'Edit %s', 'londonparkour_v8' ), $singular ),
		'new_item'           => sprintf( __( 'New %s', 'londonparkour_v8' ), $singular ),
		'view_item'          => sprintf( __( 'View %s', 'londonparkour_v8' ), $singular ),
		'search_items'       => sprintf( __( 'Search %s', 'londonparkour_v8' ), $plural ),
		'not_found'          => sprintf( __( 'No %s found', 'londonparkour_v8' ), strtolower( $plural ) ),
		'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'londonparkour_v8' ), strtolower( $plural ) ),
	);
}
