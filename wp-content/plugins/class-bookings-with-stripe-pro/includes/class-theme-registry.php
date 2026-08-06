<?php
/**
 * Discovers and describes bundled form theme packs.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Theme_Registry {

	public const PACKS_DIR = 'themes';

	/**
	 * @var array<string, array<string, mixed>>|null
	 */
	private static ?array $cache = null;

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		self::$cache = [];
		$root        = self::packs_root();

		if ( ! is_dir( $root ) ) {
			return self::$cache;
		}

		$dirs = glob( $root . '/*', GLOB_ONLYDIR );
		if ( ! is_array( $dirs ) ) {
			return self::$cache;
		}

		foreach ( $dirs as $dir ) {
			$slug = basename( $dir );
			$meta = self::read_manifest( $dir );
			if ( empty( $meta ) ) {
				continue;
			}
			$meta['slug'] = $slug;
			$meta['dir']  = $dir;
			self::$cache[ $slug ] = $meta;
		}

		uasort(
			self::$cache,
			static function ( array $a, array $b ): int {
				return strcasecmp( (string) ( $a['name'] ?? $a['slug'] ), (string) ( $b['name'] ?? $b['slug'] ) );
			}
		);

		return self::$cache;
	}

	public static function packs_root(): string {
		return trailingslashit( CLASBOWPRO_DIR . self::PACKS_DIR );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get( string $slug ): ?array {
		$all = self::all();
		return $all[ $slug ] ?? null;
	}

	public static function exists( string $slug ): bool {
		return null !== self::get( $slug );
	}

	public static function pack_dir( string $slug ): string {
		return trailingslashit( self::packs_root() . $slug );
	}

	public static function pack_url( string $slug ): string {
		return trailingslashit( CLASBOWPRO_URL . self::PACKS_DIR . '/' . $slug );
	}

	public static function templates_dir( string $slug ): string {
		return self::pack_dir( $slug ) . Template_Loader::THEME_DIR . '/';
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function read_manifest( string $dir ): array {
		$file = trailingslashit( $dir ) . 'theme.json';
		if ( ! is_readable( $file ) ) {
			return [];
		}

		$raw = file_get_contents( $file );
		if ( false === $raw ) {
			return [];
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return [];
		}

		if ( empty( $data['provides'] ) || ! is_array( $data['provides'] ) ) {
			$data['provides'] = [];
		}

		$data['tags'] = self::normalise_tags( $data['tags'] ?? [] );

		return $data;
	}

	/**
	 * @param mixed $raw
	 * @return list<string>
	 */
	public static function normalise_tags( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$tags = [];
		foreach ( $raw as $tag ) {
			$tag = sanitize_key( (string) $tag );
			if ( '' !== $tag ) {
				$tags[ $tag ] = $tag;
			}
		}

		sort( $tags );

		return array_values( $tags );
	}

	/**
	 * Unique tags across all packs, sorted alphabetically.
	 *
	 * @return list<string>
	 */
	public static function all_tags(): array {
		$tags = [];

		foreach ( self::all() as $theme ) {
			foreach ( (array) ( $theme['tags'] ?? [] ) as $tag ) {
				$tags[ $tag ] = $tag;
			}
		}

		sort( $tags );

		return array_values( $tags );
	}

	/**
	 * Human-readable label for a tag slug.
	 */
	public static function tag_label( string $tag ): string {
		$labels = [
			'tutorial' => __( 'Tutorial', 'class-bookings-with-stripe-pro' ),
			'premium'  => __( 'Premium', 'class-bookings-with-stripe-pro' ),
			'basic'    => __( 'Basic', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $tag ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $tag ) );
	}

	public static function screenshot_url( string $slug ): string {
		$theme = self::get( $slug );
		if ( ! $theme ) {
			return '';
		}

		$screenshot = (string) ( $theme['screenshot'] ?? 'screenshot.svg' );
		$path       = self::pack_dir( $slug ) . $screenshot;

		if ( is_readable( $path ) ) {
			return self::pack_url( $slug ) . $screenshot;
		}

		return '';
	}

	/**
	 * Relative paths inside the pack (for code viewer and zip).
	 *
	 * @return list<string>
	 */
	public static function list_files( string $slug ): array {
		$pack_dir = self::pack_dir( $slug );
		if ( ! is_dir( $pack_dir ) ) {
			return [];
		}

		$files   = [];
		$exclude = [ '.', '..', '.DS_Store' ];

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $pack_dir, \FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
				continue;
			}

			$basename = $file->getFilename();
			if ( in_array( $basename, $exclude, true ) ) {
				continue;
			}

			$relative = ltrim( str_replace( $pack_dir, '', $file->getPathname() ), '/' );
			$files[]  = str_replace( '\\', '/', $relative );
		}

		sort( $files );

		return $files;
	}

	public static function read_file( string $slug, string $relative ): string {
		$path = self::safe_pack_path( $slug, $relative );
		if ( '' === $path || ! is_readable( $path ) ) {
			return '';
		}

		$content = file_get_contents( $path );
		return false === $content ? '' : $content;
	}

	public static function safe_pack_path( string $slug, string $relative ): string {
		if ( ! self::exists( $slug ) ) {
			return '';
		}

		$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
		if ( '' === $relative || str_contains( $relative, '..' ) ) {
			return '';
		}

		$path = self::pack_dir( $slug ) . $relative;
		$real = realpath( $path );
		$root = realpath( self::pack_dir( $slug ) );

		if ( false === $real || false === $root || ! str_starts_with( $real, $root ) ) {
			return '';
		}

		return $real;
	}

	/**
	 * Files that install copies into the active theme.
	 *
	 * @return list<array{source: string, dest: string}>
	 */
	public static function install_map( string $slug ): array {
		$map      = [];
		$pack_dir = self::pack_dir( $slug );
		$tpl_dir  = Template_Loader::THEME_DIR;

		foreach ( self::list_files( $slug ) as $relative ) {
			if ( 'theme.json' === $relative || str_starts_with( $relative, 'screenshot.' ) ) {
				continue;
			}

			if ( 'bootstrap.php' === $relative ) {
				$map[] = [
					'source' => $pack_dir . $relative,
					'dest'   => 'bootstrap.php',
				];
				continue;
			}

			if ( str_starts_with( $relative, $tpl_dir . '/' ) ) {
				$map[] = [
					'source' => $pack_dir . $relative,
					'dest'   => substr( $relative, strlen( $tpl_dir ) + 1 ),
				];
			}
		}

		return $map;
	}

	public static function provides_layout( string $slug, string $layout ): bool {
		$theme = self::get( $slug );
		if ( ! $theme ) {
			return false;
		}

		$provides = $theme['provides'] ?? [];
		if ( ! is_array( $provides ) ) {
			return false;
		}

		if ( in_array( $layout, $provides, true ) ) {
			return true;
		}

		$file = self::templates_dir( $slug ) . $layout . '.php';
		return is_readable( $file );
	}
}
