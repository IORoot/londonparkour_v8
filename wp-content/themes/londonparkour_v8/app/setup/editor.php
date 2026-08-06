<?php
/**
 * Classic editor enforcement.
 *
 * Page structure is built from the ACF Flexible Content field, not blocks. The
 * Classic Editor plugin does the heavy lifting (installed by bin/bootstrap.sh);
 * this file makes the theme correct on its own if the plugin is ever absent.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable the block editor for every post type.
 *
 * @return bool
 */
function lp_disable_block_editor(): bool {
	return false;
}
add_filter( 'use_block_editor_for_post_type', 'lp_disable_block_editor', 100 );

/**
 * Drop the block library's front-end stylesheets — nothing here uses them.
 */
function lp_dequeue_block_styles(): void {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'lp_dequeue_block_styles', 100 );

/**
 * Give TinyMCE the same prose classes the front end uses.
 *
 * @param array $settings TinyMCE settings.
 * @return array
 */
function lp_tinymce_body_class( array $settings ): array {
	$settings['body_class'] = LONDONPARKOUR_V8_TYPOGRAPHY_CLASSES;
	return $settings;
}
add_filter( 'tiny_mce_before_init', 'lp_tinymce_body_class' );

/**
 * Limit heading levels to those Tailwind Typography styles.
 *
 * The page <h1> belongs to the masthead, not the content field.
 *
 * @param array $settings TinyMCE settings.
 * @return array
 */
function lp_tinymce_block_formats( array $settings ): array {
	$settings['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4';
	return $settings;
}
add_filter( 'tiny_mce_before_init', 'lp_tinymce_block_formats' );
