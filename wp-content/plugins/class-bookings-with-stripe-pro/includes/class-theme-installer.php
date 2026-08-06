<?php
/**
 * Installs theme packs into the active stylesheet directory.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Theme_Installer {

	/**
	 * @return array{add: list<string>, overwrite: list<string>, unchanged: list<string>}
	 */
	public static function analyze( string $slug ): array {
		$result = [
			'add'        => [],
			'overwrite'  => [],
			'unchanged'  => [],
		];

		$dest_root = self::destination_root();

		foreach ( Theme_Registry::install_map( $slug ) as $item ) {
			$dest_abs = $dest_root . $item['dest'];
			$label    = $item['dest'];

			if ( ! is_readable( $item['source'] ) ) {
				continue;
			}

			if ( ! file_exists( $dest_abs ) ) {
				$result['add'][] = $label;
				continue;
			}

			$source_hash = md5_file( $item['source'] );
			$dest_hash   = is_readable( $dest_abs ) ? md5_file( $dest_abs ) : '';

			if ( false !== $source_hash && false !== $dest_hash && $source_hash === $dest_hash ) {
				$result['unchanged'][] = $label;
			} else {
				$result['overwrite'][] = $label;
			}
		}

		return $result;
	}

	public static function destination_root(): string {
		return trailingslashit( get_stylesheet_directory() ) . Template_Loader::THEME_DIR . '/';
	}

	/**
	 * @return array{success: bool, message: string, backup?: string}
	 */
	public static function install( string $slug, bool $backup = true ): array {
		if ( ! Theme_Registry::exists( $slug ) ) {
			return [
				'success' => false,
				'message' => __( 'Theme pack not found.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( ! wp_is_writable( get_stylesheet_directory() ) ) {
			return [
				'success' => false,
				'message' => __( 'Your theme directory is not writable.', 'class-bookings-with-stripe-pro' ),
			];
		}

		$dest_root = self::destination_root();
		$backup_path = '';

		if ( $backup && is_dir( $dest_root ) ) {
			$backup_path = self::backup_existing();
			if ( '' === $backup_path ) {
				return [
					'success' => false,
					'message' => __( 'Could not back up existing theme files.', 'class-bookings-with-stripe-pro' ),
				];
			}
		}

		if ( ! is_dir( $dest_root ) ) {
			wp_mkdir_p( $dest_root );
		}

		foreach ( Theme_Registry::install_map( $slug ) as $item ) {
			if ( ! is_readable( $item['source'] ) ) {
				continue;
			}

			$dest_abs = $dest_root . $item['dest'];
			$dest_dir = dirname( $dest_abs );

			if ( ! is_dir( $dest_dir ) ) {
				wp_mkdir_p( $dest_dir );
			}

			if ( ! copy( $item['source'], $dest_abs ) ) {
				return [
					'success' => false,
					'message' => sprintf(
						/* translators: %s: file path */
						__( 'Failed to copy %s.', 'class-bookings-with-stripe-pro' ),
						$item['dest']
					),
					'backup'  => $backup_path,
				];
			}
		}

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %s: theme directory path */
				__( 'Theme files installed to %s.', 'class-bookings-with-stripe-pro' ),
				$dest_root
			),
			'backup'  => $backup_path,
			'path'    => $dest_root,
		];
	}

	private static function backup_existing(): string {
		$source = self::destination_root();
		if ( ! is_dir( $source ) ) {
			return '';
		}

		$parent = trailingslashit( get_stylesheet_directory() );
		$name   = Template_Loader::THEME_DIR . '.backup-' . gmdate( 'Y-m-d-His' );
		$target = $parent . $name;

		return self::copy_dir( $source, $target ) ? $target : '';
	}

	private static function copy_dir( string $source, string $target ): bool {
		if ( ! is_dir( $source ) ) {
			return false;
		}

		if ( ! is_dir( $target ) && ! wp_mkdir_p( $target ) ) {
			return false;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $source, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $item ) {
			if ( ! $item instanceof \SplFileInfo ) {
				continue;
			}

			$sub_path = substr( $item->getPathname(), strlen( $source ) );
			$dest     = $target . $sub_path;

			if ( $item->isDir() ) {
				if ( ! is_dir( $dest ) && ! wp_mkdir_p( $dest ) ) {
					return false;
				}
			} elseif ( ! copy( $item->getPathname(), $dest ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string|false Temp file path or false on failure.
	 */
	public static function build_zip( string $slug ) {
		if ( ! Theme_Registry::exists( $slug ) ) {
			return false;
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$zip  = new \ZipArchive();
		$tmp  = wp_tempnam( 'clasbpro-theme-' . $slug );
		if ( false === $tmp ) {
			return false;
		}

		if ( true !== $zip->open( $tmp, \ZipArchive::OVERWRITE ) ) {
			return false;
		}

		$pack_dir = Theme_Registry::pack_dir( $slug );
		foreach ( Theme_Registry::list_files( $slug ) as $relative ) {
			$abs = $pack_dir . $relative;
			if ( is_readable( $abs ) ) {
				$zip->addFile( $abs, $slug . '/' . $relative );
			}
		}

		$zip->close();

		return $tmp;
	}
}
