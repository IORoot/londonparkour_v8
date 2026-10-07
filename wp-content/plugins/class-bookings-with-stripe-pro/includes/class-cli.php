<?php
/**
 * WP-CLI: wp clasbpro export | import
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

class Cli {

	public static function init(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		\WP_CLI::add_command( 'clasbpro', self::class );
	}

	/**
	 * Export a Clasbpro snapshot zip.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : Destination zip path. Defaults to clasbpro-export-{timestamp}.zip in the current directory.
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args
	 * @param array<string, string> $assoc
	 */
	public function export( $args, $assoc ): void {
		unset( $args );
		$file = isset( $assoc['file'] ) ? (string) $assoc['file'] : 'clasbpro-export-' . gmdate( 'Y-m-d-His' ) . '.zip';
		$path = Snapshot::export_to_path( $file );
		if ( is_wp_error( $path ) ) {
			\WP_CLI::error( $path->get_error_message() );
		}
		\WP_CLI::success( sprintf( 'Exported to %s', $path ) );
	}

	/**
	 * Replace this site’s Clasbpro data from a snapshot zip.
	 *
	 * Writes a backup zip first. Does not send queued emails during import.
	 * Secrets in the destination are kept.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Path to a clasbpro export zip.
	 *
	 * [--yes]
	 * : Confirm replace. Required.
	 *
	 * @when after_wp_load
	 *
	 * @param array<int, string>    $args
	 * @param array<string, string> $assoc
	 */
	public function import( $args, $assoc ): void {
		unset( $args );
		if ( empty( $assoc['yes'] ) ) {
			\WP_CLI::error( 'Pass --yes to replace Clasbpro data on this site.' );
		}
		$file = (string) ( $assoc['file'] ?? '' );
		if ( '' === $file ) {
			\WP_CLI::error( 'Pass --file=<path> to a snapshot zip.' );
		}

		$inspect = Snapshot::inspect( $file );
		if ( is_wp_error( $inspect ) ) {
			\WP_CLI::error( $inspect->get_error_message() );
		}

		foreach ( (array) ( $inspect['warnings'] ?? [] ) as $warning ) {
			\WP_CLI::warning( (string) $warning );
		}
		$rewrite = (array) ( $inspect['url_rewrite'] ?? [] );
		\WP_CLI::log(
			sprintf(
				'URL rewrite: %s → %s',
				(string) ( $rewrite['from'] ?? '' ),
				(string) ( $rewrite['to'] ?? '' )
			)
		);

		$author_id = (int) get_current_user_id();
		if ( $author_id <= 0 ) {
			$admin = get_user_by( 'login', 'admin' );
			$author_id = ( $admin instanceof \WP_User ) ? (int) $admin->ID : 1;
		}

		$result = Snapshot::import_from_zip(
			$file,
			[
				'author_id'    => $author_id,
				'force_backup' => true,
				'confirm'      => true,
			]
		);
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		$backup = (string) ( $result['backup'] ?? '' );
		\WP_CLI::success( $backup ? sprintf( 'Imported. Backup: %s', $backup ) : 'Imported.' );
	}
}
