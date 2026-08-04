<?php

namespace EasyWPSMTP\WPCLI;

use WP_CLI;

/**
 * Registers Easy WP SMTP commands with WP-CLI.
 *
 * @since 2.15.0
 */
class Bootstrap {

	/**
	 * Register the `easy-wp-smtp` namespace and its subcommands with WP-CLI.
	 *
	 * @since 2.15.0
	 *
	 * @return void
	 */
	public function register() {

		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'easy-wp-smtp', Commands\Manage::class );
		WP_CLI::add_command( 'easy-wp-smtp test', Commands\Test::class );

		$setup  = [ 'shortdesc' => Commands\Setup::shortdesc() ];
		$option = [ 'shortdesc' => Commands\Option::shortdesc() ];

		// longdesc is only displayed for help / our-namespace invocations, but
		// building it walks the full arg registry; skip that work otherwise.
		if ( $this->needs_longdesc() ) {
			$registry = new Options\Registry();

			$setup['longdesc']  = Commands\Setup::help( $registry );
			$option['longdesc'] = Commands\Option::help( $registry );
		}

		WP_CLI::add_command( 'easy-wp-smtp setup', Commands\Setup::class, $setup );
		WP_CLI::add_command( 'easy-wp-smtp option', Commands\Option::class, $option );
	}

	/**
	 * Whether this invocation will display a command longdesc: a
	 * `wp easy-wp-smtp ...` command (including `--help`) or a
	 * `wp help easy-wp-smtp ...` lookup.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function needs_longdesc() {

		$args = WP_CLI::get_runner()->arguments;

		return ! empty( $args ) && ( $args[0] === 'easy-wp-smtp' || $args[0] === 'help' );
	}
}
