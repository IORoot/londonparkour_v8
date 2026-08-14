<?php
/**
 * Custom post types and taxonomies.
 *
 * Classes are clasbpro_class (plugin CPT) — see app/setup/clasbpro.php. Theme-
 * owned CPTs below are coaches, locations, tutorials, testimonials. Session
 * expansion for boards lives in app/includes/clasbpro.php.
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
			'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt', 'page-attributes' ),
			'taxes'    => array( 'lp_series' ),
		),
		'lp_testimonial' => array(
			'singular'            => __( 'Testimonial', 'londonparkour_v8' ),
			'plural'              => __( 'Testimonials', 'londonparkour_v8' ),
			'slug'                => 'testimonials',
			'icon'                => 'dashicons-format-quote',
			'supports'            => array( 'title', 'editor' ),
			'taxes'               => array(),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'show_in_nav_menus'   => false,
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
			'post_types'   => array( 'clasbpro_class' ),
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
 * ACF still assigns a query var even when publicly_queryable is false.
 * Testimonials are admin-only — never front-end queryable.
 *
 * @param array  $args      register_post_type args.
 * @param string $post_type Post type name.
 * @return array
 */
function lp_testimonial_post_type_args( array $args, string $post_type ): array {
	if ( 'lp_testimonial' !== $post_type ) {
		return $args;
	}

	$args['public']              = false;
	$args['publicly_queryable']  = false;
	$args['exclude_from_search'] = true;
	$args['has_archive']         = false;
	$args['rewrite']             = false;
	$args['query_var']           = false;
	$args['show_in_nav_menus']   = false;
	$args['show_ui']             = true;
	$args['show_in_menu']        = true;

	return $args;
}
add_filter( 'register_post_type_args', 'lp_testimonial_post_type_args', 20, 2 );

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
