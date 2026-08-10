<?php
/**
 * londonparkour_v8 functions and definitions.
 *
 * Classic template-hierarchy theme. Page structure comes from a single ACF
 * Flexible Content field whose layouts are the folders under blocks/; Gutenberg
 * is disabled entirely. See README.md for the build and bootstrap steps.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LONDONPARKOUR_V8_VERSION' ) ) {
	define( 'LONDONPARKOUR_V8_VERSION', '1.0.0' );
}

if ( ! defined( 'LONDONPARKOUR_V8_TYPOGRAPHY_CLASSES' ) ) {
	/*
	 * Single source of truth for prose classes — used by lp_content_class() on
	 * the front end and by the TinyMCE body_class filter, so editor and site
	 * agree without the value being typed twice.
	 */
	define(
		'LONDONPARKOUR_V8_TYPOGRAPHY_CLASSES',
		'prose prose-neutral max-w-none prose-a:text-primary'
	);
}

$lp_includes = array(
	// Infrastructure.
	'app/includes/html.php',
	'app/includes/menus.php',
	'app/includes/modules.php',
	'app/includes/clasbpro.php',
	'app/includes/content.php',
	'app/includes/contact.php',
	// Setup.
	'app/setup/theme.php',
	'app/setup/editor.php',
	'app/setup/cpt.php',
	'app/setup/clasbpro.php',
	'app/setup/queries.php',
	'app/setup/acf.php',
	'app/setup/acf-fields.php',
	// acf-groups.php is NOT listed — it returns an array and is required by acf-build.php.
	'app/setup/acf-build.php',
	'app/setup/seed.php',
	// Admin hooks.
	'hooks/initialise_hooks.php',
	// Legacy _tw template helpers.
	'inc/template-tags.php',
	'inc/template-functions.php',
);

foreach ( $lp_includes as $lp_file ) {
	$lp_path = get_theme_file_path( $lp_file );
	if ( is_readable( $lp_path ) ) {
		require_once $lp_path;
	}
}
unset( $lp_includes, $lp_file, $lp_path );
