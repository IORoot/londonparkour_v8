<?php
/**
 * Resolves theme overrides and plugin default templates / component assets.
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Template_Loader {

	public const THEME_DIR = 'class-bookings-with-stripe';

	/**
	 * Locate a layout template: theme override first, then plugin default.
	 */
	public static function locate_layout( string $layout, string $context = '' ): string {
		$relative = ltrim( $layout, '/' );
		if ( ! str_ends_with( $relative, '.php' ) ) {
			$relative .= '.php';
		}

		$theme = locate_template(
			[
				self::THEME_DIR . '/' . $relative,
			],
			false,
			false
		);
		$path  = $theme ?: CLASBOWPRO_DIR . 'templates/' . $relative;

		return (string) apply_filters( 'clasbpro_template_path', $path, $relative, $context );
	}

	/**
	 * Locate a component partial within a layout directory.
	 */
	public static function locate_component( string $layout, string $slug ): string {
		$relative = $layout . '/' . $slug . '.php';

		$theme = locate_template(
			[
				self::THEME_DIR . '/' . $relative,
			],
			false,
			false
		);
		$path  = $theme ?: CLASBOWPRO_DIR . 'templates/' . $relative;

		return (string) apply_filters( 'clasbpro_component_path', $path, $layout, $slug );
	}

	/**
	 * Enqueue optional component CSS when present in theme or plugin assets.
	 */
	public static function enqueue_component_style( string $layout, string $slug, string $style_handle_prefix, string $plugin_url ): void {
		$filename = $slug . '.css';
		$subdir   = 'assets/components/' . $layout . '/' . $filename;

		$theme_file = get_stylesheet_directory() . '/' . self::THEME_DIR . '/' . $subdir;
		if ( is_readable( $theme_file ) ) {
			$theme_uri = get_stylesheet_directory_uri() . '/' . self::THEME_DIR . '/' . $subdir;
			wp_enqueue_style(
				$style_handle_prefix . '-' . $slug,
				$theme_uri,
				[],
				(string) filemtime( $theme_file )
			);
			return;
		}

		$plugin_file = CLASBOWPRO_DIR . $subdir;
		if ( is_readable( $plugin_file ) ) {
			wp_enqueue_style(
				$style_handle_prefix . '-' . $slug,
				trailingslashit( $plugin_url ) . $subdir,
				[],
				(string) filemtime( $plugin_file )
			);
		}
	}
}
