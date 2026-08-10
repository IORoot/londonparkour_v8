<?php
/**
 * Theme supports, menus and image sizes.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme setup.
 */
function lp_setup(): void {
	load_theme_textdomain( 'londonparkour_v8', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// This theme is not block-based. See app/setup/editor.php.
	remove_theme_support( 'block-templates' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'londonparkour_v8' ),
			'footer'  => __( 'Footer', 'londonparkour_v8' ),
		)
	);
}
add_action( 'after_setup_theme', 'lp_setup' );

/**
 * Image sizes matching the design system's fixed aspect ratios.
 *
 * Sourced from the Storybook's own media boxes: the Locations flagship card and
 * the Coaches lead portrait are 556x600; MediaCard, VideoCard and VideoStage
 * are 16:9.
 *
 * Each ratio is registered at THREE widths on purpose. WordPress builds a
 * srcset only from images sharing the reference image's aspect ratio, so a lone
 * hard crop produces an EMPTY srcset — the responsive behaviour in
 * parts/components/media-photo.php would silently do nothing. A ratio needs
 * ratio-matched siblings to be responsive at all.
 *
 * Adding a size only affects uploads made afterwards; run
 * `bin/wp media regenerate --yes` after changing anything here.
 */
function lp_image_sizes(): void {
	// 16:9 — mastheads, hero, video stills.
	add_image_size( 'lp_wide_sm', 640, 360, true );
	add_image_size( 'lp_wide', 1280, 720, true );
	add_image_size( 'lp_wide_lg', 1920, 1080, true );

	// 556:600 — the portrait media box.
	add_image_size( 'lp_portrait_sm', 278, 300, true );
	add_image_size( 'lp_portrait', 556, 600, true );
	add_image_size( 'lp_portrait_lg', 1112, 1200, true );

	// 1:1 — avatars and roster thumbnails.
	add_image_size( 'lp_thumb', 160, 160, true );
	add_image_size( 'lp_thumb_lg', 320, 320, true );
}
add_action( 'after_setup_theme', 'lp_image_sizes' );

/**
 * Expose the design-system crops in the media library's size picker, so an
 * editor inserting an image can actually choose them.
 *
 * @param array $sizes Size name => label.
 * @return array
 */
function lp_selectable_image_sizes( array $sizes ): array {
	return array_merge(
		$sizes,
		array(
			'lp_wide'     => __( 'Wide (16:9)', 'londonparkour_v8' ),
			'lp_portrait' => __( 'Portrait (556x600)', 'londonparkour_v8' ),
			'lp_thumb'    => __( 'Thumbnail (square)', 'londonparkour_v8' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'lp_selectable_image_sizes' );

/**
 * The prose class set, from one constant.
 *
 * @param string $extra Additional classes to append.
 * @return string
 */
function lp_content_class( string $extra = '' ): string {
	return trim( LONDONPARKOUR_V8_TYPOGRAPHY_CLASSES . ' ' . $extra );
}

/**
 * Resolve the site's active daisyUI theme name.
 *
 * Four themes ship — two grounds x two signals — composed from an options-page
 * pair exactly as the Storybook toolbar composes them.
 *
 * @return string e.g. 'parkour-dark-yellow'
 */
function lp_theme_name(): string {
	// Match Storybook light ground so page comps read correctly: Pricing
	// `bg-base-200` is white, Clients/Locations `bg-accent` is the light
	// amber/olive band — not dark-theme signal yellow. Signal stays yellow
	// so Marquee / CTA / Private Coaching `bg-primary` remain yellow.
	$ground = 'light';
	$signal = 'yellow';

	if ( function_exists( 'get_field' ) ) {
		$ground = get_field( 'theme_ground', 'option' ) ?: $ground;
		$signal = get_field( 'theme_signal', 'option' ) ?: $signal;
	}

	// Whitelist — this value lands in an HTML attribute and picks a CSS block.
	$ground = in_array( $ground, array( 'dark', 'light' ), true ) ? $ground : 'light';
	$signal = in_array( $signal, array( 'yellow', 'green' ), true ) ? $signal : 'yellow';

	return "parkour-{$ground}-{$signal}";
}

/**
 * Put the active theme on <html>, which is all theming is.
 *
 * @param string $output The language attributes string.
 * @return string
 */
function lp_html_theme_attr( string $output ): string {
	return $output . ' data-theme="' . esc_attr( lp_theme_name() ) . '"';
}
add_filter( 'language_attributes', 'lp_html_theme_attr' );
