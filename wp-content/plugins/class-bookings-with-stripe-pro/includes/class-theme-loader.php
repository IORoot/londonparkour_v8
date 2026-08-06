<?php
/**
 * Applies gallery, theme-file, and preview theme resolution at runtime.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Theme_Loader {

	private const OPTION_KEY = 'clasbpro_theme_settings';

	/**
	 * Preview theme slug for the current request only.
	 */
	private static string $preview_slug = '';

	public static function init(): void {
		add_filter( 'clasbpro_template_path', [ self::class, 'filter_template_path' ], 20, 3 );
		add_filter( 'clasbpro_component_path', [ self::class, 'filter_component_path' ], 20, 3 );
		add_action( 'init', [ self::class, 'load_bootstrap' ], 20 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_theme_style' ], 20 );
		add_action( 'wp_enqueue_scripts', [ self::class, 'dequeue_conflicting_theme_examples' ], 100 );
		add_action( 'init', [ self::class, 'capture_preview_slug' ], 5 );
	}

	public static function capture_preview_slug(): void {
		if ( empty( $_GET['clasbpro_theme_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$slug = sanitize_key( wp_unslash( (string) $_GET['clasbpro_theme_preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $slug || ! Theme_Registry::exists( $slug ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), 'clasbpro_theme_preview_' . $slug ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		self::$preview_slug = $slug;
	}

	public static function is_preview_active(): bool {
		return '' !== self::$preview_slug;
	}

	public static function get_preview_slug(): string {
		return self::$preview_slug;
	}

	/**
	 * @return array{theme_source: string, active_gallery_theme: string}
	 */
	public static function get_settings(): array {
		$defaults = [
			'theme_source'          => 'default',
			'active_gallery_theme'  => '',
		];

		$stored = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		return array_merge( $defaults, $stored );
	}

	/**
	 * @param array{theme_source?: string, active_gallery_theme?: string} $settings
	 */
	public static function update_settings( array $settings ): void {
		$current = self::get_settings();

		if ( isset( $settings['theme_source'] ) ) {
			$source = sanitize_key( (string) $settings['theme_source'] );
			if ( in_array( $source, [ 'default', 'gallery', 'theme' ], true ) ) {
				$current['theme_source'] = $source;
			}
		}

		if ( isset( $settings['active_gallery_theme'] ) ) {
			$slug = sanitize_key( (string) $settings['active_gallery_theme'] );
			if ( '' === $slug || Theme_Registry::exists( $slug ) ) {
				$current['active_gallery_theme'] = $slug;
			}
		}

		update_option( self::OPTION_KEY, $current, false );
	}

	public static function get_theme_source(): string {
		if ( self::is_preview_active() ) {
			return 'gallery';
		}

		return (string) self::get_settings()['theme_source'];
	}

	public static function get_active_gallery_slug(): string {
		if ( self::is_preview_active() ) {
			return self::$preview_slug;
		}

		return (string) self::get_settings()['active_gallery_theme'];
	}

	public static function theme_files_dir(): string {
		return trailingslashit( get_stylesheet_directory() ) . Template_Loader::THEME_DIR;
	}

	public static function theme_files_url(): string {
		return trailingslashit( get_stylesheet_directory_uri() ) . Template_Loader::THEME_DIR;
	}

	public static function asset_path( string $relative = '' ): string {
		$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
		$source   = self::get_theme_source();

		if ( 'gallery' === $source ) {
			$slug = self::get_active_gallery_slug();
			if ( '' !== $slug ) {
				$base = Theme_Registry::templates_dir( $slug );
				return $base . $relative;
			}
		}

		if ( 'theme' === $source ) {
			return self::theme_files_dir() . ( $relative ? '/' . $relative : '' );
		}

		return '';
	}

	public static function asset_url( string $relative = '' ): string {
		$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
		$source   = self::get_theme_source();

		if ( 'gallery' === $source ) {
			$slug = self::get_active_gallery_slug();
			if ( '' !== $slug ) {
				$base = trailingslashit( Theme_Registry::pack_url( $slug ) . Template_Loader::THEME_DIR );
				return $base . $relative;
			}
		}

		if ( 'theme' === $source ) {
			$base = trailingslashit( self::theme_files_url() );
			return $base . $relative;
		}

		return '';
	}

	public static function filter_template_path( string $path, string $relative, string $context ): string {
		$source = self::get_theme_source();

		if ( 'default' === $source ) {
			$plugin = CLASBOWPRO_DIR . 'templates/' . $relative;
			return is_readable( $plugin ) ? $plugin : $path;
		}

		if ( 'theme' === $source ) {
			$file = self::theme_files_dir() . '/' . $relative;
			return is_readable( $file ) ? $file : $path;
		}

		if ( 'gallery' !== $source ) {
			return $path;
		}

		$slug = self::get_active_gallery_slug();
		if ( '' === $slug ) {
			return $path;
		}

		$pack_file = Theme_Registry::templates_dir( $slug ) . $relative;
		if ( is_readable( $pack_file ) ) {
			return $pack_file;
		}

		$plugin = CLASBOWPRO_DIR . 'templates/' . $relative;
		return is_readable( $plugin ) ? $plugin : $path;
	}

	public static function filter_component_path( string $path, string $layout, string $slug ): string {
		$source = self::get_theme_source();

		if ( 'default' === $source ) {
			$plugin = CLASBOWPRO_DIR . 'templates/' . $layout . '/' . $slug . '.php';
			return is_readable( $plugin ) ? $plugin : $path;
		}

		if ( 'theme' === $source ) {
			$file = self::theme_files_dir() . '/' . $layout . '/' . $slug . '.php';
			return is_readable( $file ) ? $file : $path;
		}

		if ( 'gallery' !== $source ) {
			return $path;
		}

		$theme_slug = self::get_active_gallery_slug();
		if ( '' === $theme_slug ) {
			return $path;
		}

		$pack_file = Theme_Registry::templates_dir( $theme_slug ) . $layout . '/' . $slug . '.php';
		if ( is_readable( $pack_file ) ) {
			return $pack_file;
		}

		$plugin = CLASBOWPRO_DIR . 'templates/' . $layout . '/' . $slug . '.php';
		return is_readable( $plugin ) ? $plugin : $path;
	}

	public static function load_bootstrap(): void {
		if ( self::is_preview_active() || 'gallery' === self::get_theme_source() ) {
			$slug = self::get_active_gallery_slug();
			if ( '' !== $slug ) {
				$file = Theme_Registry::pack_dir( $slug ) . 'bootstrap.php';
				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
			return;
		}

		if ( 'theme' === self::get_theme_source() ) {
			$file = self::theme_files_dir() . '/bootstrap.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	public static function enqueue_theme_style(): void {
		$source = self::get_theme_source();
		if ( ! in_array( $source, [ 'gallery', 'theme' ], true ) ) {
			return;
		}

		$css_path = '';
		$css_url  = '';

		if ( 'gallery' === $source ) {
			$slug = self::get_active_gallery_slug();
			if ( '' === $slug ) {
				return;
			}
			$css_path = Theme_Registry::templates_dir( $slug ) . 'style.css';
			$css_url  = trailingslashit( Theme_Registry::pack_url( $slug ) . Template_Loader::THEME_DIR ) . 'style.css';
		} else {
			$css_path = self::theme_files_dir() . '/style.css';
			$css_url  = trailingslashit( self::theme_files_url() ) . 'style.css';
		}

		if ( ! is_readable( $css_path ) ) {
			return;
		}

		wp_enqueue_style(
			'clasbpro-theme-pack',
			$css_url,
			[ 'clasbpro' ],
			(string) filemtime( $css_path )
		);

		$status_themes_path = CLASBOWPRO_DIR . 'assets/cbfs-status-themes.css';
		if ( is_readable( $status_themes_path ) ) {
			wp_enqueue_style(
				'clasbpro-status-themes',
				CLASBOWPRO_URL . 'assets/cbfs-status-themes.css',
				[ 'clasbpro', 'clasbpro-theme-pack' ],
				(string) filemtime( $status_themes_path )
			);
		}
	}

	/**
	 * Dequeue styles registered by twentytwentyfive demo examples when gallery mode is active.
	 */
	public static function dequeue_conflicting_theme_examples(): void {
		if ( 'gallery' !== self::get_theme_source() && ! self::is_preview_active() ) {
			return;
		}

		$handles = [
			'twentytwentyfive-cbfs-example3',
			'twentytwentyfive-cbfs-example4',
			'twentytwentyfive-cbfs-example5',
			'example2-booking-style',
		];

		foreach ( $handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	}

	/**
	 * Path to shared status page fragments (title, details, actions).
	 */
	public static function status_content_path(): string {
		return CLASBOWPRO_DIR . 'templates/booking-status/_content.php';
	}

	/**
	 * CSS wrapper class for the active gallery / theme-files pack (e.g. cbfs-horizon).
	 */
	public static function get_wrapper_class(): string {
		$source = self::get_theme_source();
		if ( 'default' === $source ) {
			return '';
		}

		$form_path = '';
		if ( 'gallery' === $source || self::is_preview_active() ) {
			$slug = self::get_active_gallery_slug();
			if ( '' !== $slug ) {
				$form_path = Theme_Registry::templates_dir( $slug ) . 'booking-form.php';
			}
		} elseif ( 'theme' === $source ) {
			$form_path = self::theme_files_dir() . '/booking-form.php';
		}

		if ( '' !== $form_path && is_readable( $form_path ) ) {
			$content = (string) file_get_contents( $form_path );
			if ( preg_match( '/cbfs-form--layout-modern\s+((?:cbfs|twentytwentyfive)[^\s"]+)/', $content, $matches ) ) {
				return sanitize_html_class( $matches[1] );
			}
		}

		$slug = self::get_active_gallery_slug();
		return '' !== $slug ? 'cbfs-' . sanitize_html_class( $slug ) : '';
	}

	/**
	 * data-* attributes for success status polling.
	 */
	public static function status_session_attrs( string $type, string $session_id, string $status_token, string $kind = 'booking', int $purchase_id = 0 ): string {
		if ( 'success' !== $type ) {
			return '';
		}
		if ( '' === $session_id && $purchase_id <= 0 ) {
			return '';
		}

		$attrs = ' data-cbfs-kind="' . esc_attr( 'coupon' === $kind ? 'coupon' : 'booking' ) . '"';
		if ( '' !== $session_id ) {
			$attrs .= ' data-cbfs-session="' . esc_attr( $session_id ) . '"';
		}
		if ( '' !== $status_token ) {
			$attrs .= ' data-cbfs-token="' . esc_attr( $status_token ) . '"';
		}
		if ( $purchase_id > 0 ) {
			$attrs .= ' data-cbfs-purchase="' . esc_attr( (string) $purchase_id ) . '"';
		}

		return $attrs;
	}

	public static function theme_files_status(): array {
		$dir = self::theme_files_dir();
		if ( ! is_dir( $dir ) ) {
			return [
				'exists'     => false,
				'path'       => $dir,
				'file_count' => 0,
			];
		}

		$count = 0;
		$iter  = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iter as $file ) {
			if ( $file instanceof \SplFileInfo && $file->isFile() ) {
				++$count;
			}
		}

		return [
			'exists'     => true,
			'path'       => $dir,
			'file_count' => $count,
		];
	}
}
