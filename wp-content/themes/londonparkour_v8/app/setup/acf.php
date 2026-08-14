<?php
/**
 * ACF wiring — local JSON sync points and the theme options page.
 *
 * Two field-group populations live side by side:
 *
 *   - group_page-sections.json is GENERATED from blocks/<name>/fields.php by
 *     `wp lp acf:build`. Its source of truth is PHP; editing it in wp-admin
 *     works but is overwritten on the next build. Schema is developer-owned.
 *   - Every other group (CPT fields, this options page) is admin-owned. ACF
 *     writes them to acf-json/ on save and they get committed as-is.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where ACF writes field groups when they are saved in wp-admin.
 *
 * @param string $path Default save path.
 * @return string
 */
function lp_acf_json_save_point( string $path ): string {
	return get_stylesheet_directory() . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'lp_acf_json_save_point' );

// ACF hides the native Custom Fields metabox for speed. Same restore as v7.
add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' );

/**
 * Where ACF looks for field groups on load.
 *
 * @param array $paths Default load paths.
 * @return array
 */
function lp_acf_json_load_point( array $paths ): array {
	unset( $paths[0] );
	$paths[] = get_stylesheet_directory() . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'lp_acf_json_load_point' );

/**
 * Theme options page — currently the ground/signal theme axis.
 */
function lp_acf_options_page(): void {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Site Settings', 'londonparkour_v8' ),
			'menu_title' => __( 'Site Settings', 'londonparkour_v8' ),
			'menu_slug'  => 'lp-site-settings',
			'capability' => 'manage_options',
			'redirect'   => false,
			'icon_url'   => 'dashicons-admin-customizer',
			'position'   => 59,
		)
	);
}
add_action( 'acf/init', 'lp_acf_options_page' );
