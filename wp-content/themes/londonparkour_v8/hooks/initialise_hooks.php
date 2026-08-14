<?php
/**
 * Theme WordPress hooks bootstrap.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_hooks = array(
	'hooks/filter_tutorial_listing_columns.php',
	'hooks/filter_testimonial_listing_columns.php',
	'hooks/filter_svg_featured_image.php',
);

foreach ( $lp_hooks as $lp_hook_file ) {
	$lp_hook_path = get_theme_file_path( $lp_hook_file );
	if ( is_readable( $lp_hook_path ) ) {
		require_once $lp_hook_path;
	}
}
unset( $lp_hooks, $lp_hook_file, $lp_hook_path );
