<?php
/**
 * Training spots are retired. This command used to import Google Takeout
 * pins as lp_location kind=spot; class sites are the only location records now.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * @param array $args Positional args.
 * @param array $assoc_args Flags.
 */
function lp_cli_import_spots( $args, $assoc_args ) {
	unset( $args, $assoc_args );
	WP_CLI::error( 'Training spots have been retired. Only class sites are stored as lp_location.' );
}

WP_CLI::add_command( 'lp import-spots', 'lp_cli_import_spots' );
