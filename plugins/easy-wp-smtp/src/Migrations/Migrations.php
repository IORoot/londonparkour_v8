<?php

namespace EasyWPSMTP\Migrations;

use EasyWPSMTP\Admin\DebugEvents\Migration as DebugEventsMigration;
use EasyWPSMTP\Admin\Recommendations\Migration as RecommendationsMigration;
use EasyWPSMTP\Queue\Migration as QueueMigration;
use EasyWPSMTP\WP;
use WP_Upgrader;

/**
 * Class Migrations.
 *
 * @since 2.0.0
 */
class Migrations {

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public function hooks() {

		// Initialize migrations during request in the admin panel only.
		add_action( 'admin_init', [ $this, 'init_migrations_on_request' ] );

		// Run deprecated options migration manually via GET param.
		add_action( 'admin_init', [ $this, 'maybe_run_deprecated_options_migration' ] );

		// Initialize migrations after plugin update.
		add_action( 'upgrader_process_complete', [ $this, 'init_migrations_after_upgrade' ], PHP_INT_MAX, 2 );
		add_action(
			'wp_ajax_nopriv_easy_wp_smtp_init_migrations',
			[ $this, 'init_migrations_ajax_handler' ]
		);
	}

	/**
	 * Initialize DB migrations during request.
	 *
	 * @since 2.3.0
	 */
	public function init_migrations_on_request() {

		// Do not initialize migrations during AJAX and cron requests.
		if ( WP::is_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$this->init_migrations();
	}

	/**
	 * Initialize DB migrations.
	 *
	 * @since 2.3.0
	 */
	private function init_migrations() {

		$migrations = $this->get_migrations();

		foreach ( $migrations as $migration ) {
			if ( is_subclass_of( $migration, MigrationAbstract::class ) && $migration::is_enabled() ) {
				( new $migration() )->init();
			}
		}
	}

	/**
	 * Get migrations classes.
	 *
	 * @since 2.3.0
	 *
	 * @return array Migrations classes.
	 */
	private function get_migrations() {

		$migrations = [
			DeprecatedOptionsMigration::class,
			GeneralMigration::class,
			DebugEventsMigration::class,
			QueueMigration::class,
			RecommendationsMigration::class,
		];

		/**
		 * Filters DB migrations classes.
		 *
		 * @deprecated 2.3.0
		 *
		 * @since 2.0.0
		 *
		 * @param array $migrations Migrations classes.
		 */
		$migrations = apply_filters_deprecated(
			'easy_wp_smtp_migrations_init',
			[ $migrations ],
			'2.3.0',
			'easy_wp_smtp_migrations_get_migrations'
		);

		/**
		 * Filters DB migrations classes.
		 *
		 * @since 2.3.0
		 *
		 * @param array $migrations Migrations classes.
		 */
		return apply_filters( 'easy_wp_smtp_migrations_get_migrations', $migrations );
	}

	/**
	 * Initialize DB migrations after plugin update.
	 * Initiate ajax call to perform the migration with the new plugin version code.
	 *
	 * @since 2.3.0
	 *
	 * @param WP_Upgrader $upgrader WP_Upgrader instance.
	 * @param array       $options  Array of update data.
	 */
	public function init_migrations_after_upgrade( $upgrader, $options ) {

		if (
			// Skip if in admin panel.
			( is_admin() && ! wp_doing_ajax() ) ||
			// Skip if it's update from plugins list page.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			( wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'update-plugin' )
		) {
			return;
		}

		$plugins = [];

		if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			$plugins = $options['plugins'];
		} elseif ( isset( $options['plugin'] ) && is_string( $options['plugin'] ) ) {
			$plugins = [ $options['plugin'] ];
		}

		if (
			! in_array( 'easy-wp-smtp/easy-wp-smtp.php', $plugins, true ) &&
			! in_array( 'easy-wp-smtp-pro/easy-wp-smtp.php', $plugins, true )
		) {
			return;
		}

		$secret = wp_hash( 'easy_wp_smtp_init_migrations' . wp_salt() );

		$url = add_query_arg(
			[
				'action' => 'easy_wp_smtp_init_migrations',
			],
			admin_url( 'admin-ajax.php' )
		);

		$timeout = (int) ini_get( 'max_execution_time' );

		$args = [
			'sslverify' => false,
			'timeout'   => $timeout ? $timeout : 30,
			'body'      => [
				'secret' => $secret,
			],
		];

		wp_remote_post( $url, $args );
	}

	/**
	 * Initialize migrations via AJAX request.
	 *
	 * @since 2.3.0
	 */
	public function init_migrations_ajax_handler() {

		$secret   = ! empty( $_POST['secret'] ) ? sanitize_text_field( wp_unslash( $_POST['secret'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$expected = wp_hash( 'easy_wp_smtp_init_migrations' . wp_salt() );

		if ( ! hash_equals( $expected, $secret ) ) {
			wp_send_json_error();
		}

		$this->init_migrations();

		wp_send_json_success();
	}

	/**
	 * Run deprecated options migration manually via GET parameter.
	 *
	 * @since 2.0.0
	 */
	public function maybe_run_deprecated_options_migration() {

		if (
			current_user_can( easy_wp_smtp()->get_capability_manage_options() ) &&
			isset( $_GET['page'] ) && $_GET['page'] === 'easy-wp-smtp' &&
			isset( $_GET['easy-wp-smtp-migrate-deprecated-options'] )
		) {
			if ( empty( get_option( 'swpsmtp_options' ) ) ) {
				WP::add_admin_notice( esc_html__( 'Deprecated options were already removed from DB and can\'t be migrated.', 'easy-wp-smtp' ), WP::ADMIN_NOTICE_ERROR );

				return;
			}

			( new DeprecatedOptionsMigration() )->migrate_to_1( true );

			wp_safe_redirect( easy_wp_smtp()->get_admin()->get_admin_page_url() );
			exit();
		}
	}

	/**
	 * Initialize DB migrations.
	 *
	 * @deprecated 2.3.0
	 *
	 * @since 2.0.0
	 */
	public function init() {

		_deprecated_function( __METHOD__, '2.3.0', self::class . '::init_migrations_on_request' );

		$this->init_migrations_on_request();
	}
}
