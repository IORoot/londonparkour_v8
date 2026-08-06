<?php

namespace EasyWPSMTP\Admin\DebugEvents;

use EasyWPSMTP\Admin\Area;
use EasyWPSMTP\Options;
use EasyWPSMTP\Tasks\DebugEventsCleanupTask;
use EasyWPSMTP\WP;
use WP_Error;

/**
 * Debug Events class.
 *
 * @since 2.0.0
 */
class DebugEvents {

	/**
	 * Transient name for the error debug events.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	const ERROR_DEBUG_EVENTS_TRANSIENT = 'easy_wp_smtp_error_debug_events_transient';

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public function hooks() {

		// Process AJAX requests.
		add_action( 'wp_ajax_easy_wp_smtp_debug_event_preview', [ $this, 'process_ajax_debug_event_preview' ] );
		add_action( 'wp_ajax_easy_wp_smtp_delete_all_debug_events', [ $this, 'process_ajax_delete_all_debug_events' ] );

		// Initialize screen options for the Debug Events page.
		add_action( 'load-' . easy_wp_smtp()->get_admin()->get_admin_page_hook( 'tools' ), [ $this, 'screen_options' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_options' ], 10, 3 );
		add_filter( 'set_screen_option_easy_wp_smtp_debug_events_per_page', [ $this, 'set_screen_options' ], 10, 3 );

		// Cancel previous debug events cleanup task if retention period option was changed.
		add_filter( 'easy_wp_smtp_options_set', [ $this, 'maybe_cancel_debug_events_cleanup_task' ] );

		// Detect debug events log retention period constant change.
		if ( Options::init()->is_const_defined( 'debug_events', 'retention_period' ) ) {
			add_action( 'admin_init', [ $this, 'detect_debug_events_retention_period_constant_change' ] );
		}
	}

	/**
	 * Detect debug events retention period constant change.
	 *
	 * @since 2.0.0
	 */
	public function detect_debug_events_retention_period_constant_change() {

		if ( ! WP::in_wp_admin() ) {
			return;
		}

		if ( Options::init()->is_const_changed( 'debug_events', 'retention_period' ) ) {
			( new DebugEventsCleanupTask() )->cancel();
		}
	}

	/**
	 * Cancel previous debug events cleanup task if retention period option was changed.
	 *
	 * @since 2.0.0
	 *
	 * @param array $options Currently processed options passed to a filter hook.
	 *
	 * @return array
	 */
	public function maybe_cancel_debug_events_cleanup_task( $options ) {

		if ( isset( $options['debug_events']['retention_period'] ) ) {
			// If this option has changed, cancel the recurring cleanup task and init again.
			if ( Options::init()->is_option_changed( $options['debug_events']['retention_period'], 'debug_events', 'retention_period' ) ) {
				( new DebugEventsCleanupTask() )->cancel();
			}
		}

		return $options;
	}

	/**
	 * Process AJAX request for deleting all debug event entries.
	 *
	 * @since 2.0.0
	 */
	public function process_ajax_delete_all_debug_events() {

		if (
			empty( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'easy_wp_smtp_debug_events' )
		) {
			wp_send_json_error( esc_html__( 'Access rejected.', 'easy-wp-smtp' ) );
		}

		if ( ! current_user_can( easy_wp_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have the capability to perform this action.', 'easy-wp-smtp' ) );
		}

		if ( ! self::is_valid_db() ) {
			wp_send_json_error( esc_html__( 'For some reason the database table was not installed correctly. Please contact plugin support team to diagnose and fix the issue.', 'easy-wp-smtp' ) );
		}

		global $wpdb;

		$table = self::get_table_name();

		$sql = "TRUNCATE TABLE `$table`;";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $sql );

		if ( $result !== false ) {
			wp_send_json_success( esc_html__( 'All debug event entries were deleted successfully.', 'easy-wp-smtp' ) );
		}

		wp_send_json_error(
			sprintf( /* translators: %s - WPDB error message. */
				esc_html__( 'There was an issue while trying to delete all debug event entries. Error message: %s', 'easy-wp-smtp' ),
				$wpdb->last_error
			)
		);
	}

	/**
	 * Process AJAX request for debug event preview.
	 *
	 * @since 2.0.0
	 */
	public function process_ajax_debug_event_preview() {

		if (
			empty( $_POST['nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'easy_wp_smtp_debug_events' )
		) {
			wp_send_json_error( esc_html__( 'Access rejected.', 'easy-wp-smtp' ) );
		}

		if ( ! current_user_can( easy_wp_smtp()->get_capability_manage_options() ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have the capability to perform this action.', 'easy-wp-smtp' ) );
		}

		if ( ! self::is_valid_db() ) {
			wp_send_json_error( esc_html__( 'For some reason the database table was not installed correctly. Please contact plugin support team to diagnose and fix the issue.', 'easy-wp-smtp' ) );
		}

		$event_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : false;

		if ( empty( $event_id ) ) {
			wp_send_json_error( esc_html__( 'No Debug Event ID provided!', 'easy-wp-smtp' ) );
		}

		$event = new Event( $event_id );

		wp_send_json_success(
			[
				'title'   => $event->get_title(),
				'content' => $event->get_details_html(),
			]
		);
	}

	/**
	 * Add the debug event to the DB.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message The event's message.
	 * @param int    $type    The event's type.
	 *
	 * @return bool|int
	 */
	public static function add( $message = '', $type = 0 ) {

		if ( ! self::is_valid_db() ) {
			return false;
		}

		if ( ! in_array( $type, array_keys( Event::get_types() ), true ) ) {
			return false;
		}

		if ( $type === Event::TYPE_DEBUG && ! self::is_debug_enabled() ) {
			return false;
		}

		try {
			$event = new Event();
			$event->set_type( $type );
			$event->set_content( $message );
			$event->set_initiator();

			return $event->save()->get_id();
		} catch ( \Exception $exception ) {
			return false;
		}
	}

	/**
	 * Save the debug message.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message The debug message.
	 *
	 * @return bool|int
	 */
	public static function add_debug( $message = '' ) {

		return self::add( $message, Event::TYPE_DEBUG );
	}

	/**
	 * Add a debug event subject to a throttle window — only logs if no other event
	 * with the same throttle key has been logged within the TTL.
	 *
	 * Useful for background errors that can fire on every page load (e.g. expired
	 * OAuth token refresh failures) to avoid flooding the events table.
	 *
	 * @since 2.15.0
	 *
	 * @param string $message      The event's message.
	 * @param string $throttle_key Unique key identifying this event's throttle bucket.
	 *                             Automatically prefixed with `easy_wp_smtp_` before
	 *                             being used as a transient key.
	 * @param int    $ttl          Throttle window in seconds. Default 5 minutes.
	 * @param int    $type         The event's type. Default Event::TYPE_ERROR (0).
	 *
	 * @return bool|int Event ID on success, false if throttled or save failed.
	 */
	public static function add_throttled( $message, $throttle_key, $ttl = 5 * MINUTE_IN_SECONDS, $type = 0 ) {

		$transient_key = 'easy_wp_smtp_' . $throttle_key;

		if ( get_transient( $transient_key ) ) {
			return false;
		}

		$event_id = self::add( $message, $type );

		if ( $event_id !== false ) {
			set_transient( $transient_key, time(), $ttl );
		}

		return $event_id;
	}

	/**
	 * Get the debug message from the provided debug event IDs.
	 *
	 * @since 2.0.0
	 *
	 * @param array|string|int $ids A single or a list of debug event IDs.
	 *
	 * @return array
	 */
	public static function get_debug_messages( $ids ) {

		global $wpdb;

		if ( empty( $ids ) ) {
			return [];
		}

		if ( ! self::is_valid_db() ) {
			return [];
		}

		// Convert to a string.
		if ( is_array( $ids ) ) {
			$ids = implode( ',', $ids );
		}

		$ids          = explode( ',', (string) $ids );
		$ids          = array_map( 'intval', $ids );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		$table = self::get_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$events_data = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, content, initiator, event_type, created_at  FROM {$table} WHERE id IN ( {$placeholders} )", $ids )
		);
		// phpcs:enable

		if ( empty( $events_data ) ) {
			return [];
		}

		return array_map(
			function ( $event_item ) {
				$event = new Event( $event_item );

				return $event->get_short_details();
			},
			$events_data
		);
	}

	/**
	 * Returns the number of error debug events in a given time span.
	 *
	 * By default it returns the number of error debug events in the last 30 days.
	 *
	 * @since 2.4.0
	 *
	 * @param string $span_of_time The time span to count the events for. Default '-30 days'.
	 *
	 * @return int|WP_Error The number of error debug events or WP_Error on failure.
	 */
	public static function get_error_debug_events_count( $span_of_time = '-30 days' ) {

		$timestamp = strtotime( $span_of_time );

		if ( ! $timestamp || $timestamp > time() ) {
			return new WP_Error( 'easy_wp_smtp_admin_debug_events_get_error_debug_events_count_invalid_time', 'Invalid time span.' );
		}

		$transient_key             = self::ERROR_DEBUG_EVENTS_TRANSIENT . '_' . sanitize_title_with_dashes( $span_of_time );
		$cached_error_events_count = get_transient( $transient_key );

		if ( $cached_error_events_count !== false ) {
			return (int) $cached_error_events_count;
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
		$sql = $wpdb->prepare(
			'SELECT COUNT(*) FROM `%1$s` WHERE event_type = %2$d AND created_at >= "%3$s"',
			self::get_table_name(),
			Event::TYPE_ERROR,
			gmdate( WP::datetime_mysql_format(), $timestamp )
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$error_events_count = (int) $wpdb->get_var( $sql );

		set_transient( $transient_key, $error_events_count, HOUR_IN_SECONDS );

		return $error_events_count;
	}

	/**
	 * Register the screen options for the debug events page.
	 *
	 * @since 2.0.0
	 */
	public function screen_options() {

		$screen = get_current_screen();

		if (
			! is_object( $screen ) ||
			strpos( $screen->id, easy_wp_smtp()->get_admin()->get_admin_page_hook( 'tools' ) ) === false ||
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! isset( $_GET['tab'] ) || $_GET['tab'] !== 'debug-events'
		) {
			return;
		}

		add_screen_option(
			'per_page',
			[
				'label'   => esc_html__( 'Number of events per page:', 'easy-wp-smtp' ),
				'option'  => 'easy_wp_smtp_debug_events_per_page',
				'default' => EventsCollection::PER_PAGE,
			]
		);
	}

	/**
	 * Set the screen options for the debug events page.
	 *
	 * @since 2.0.0
	 *
	 * @param bool   $keep   Whether to save or skip saving the screen option value.
	 * @param string $option The option name.
	 * @param int    $value  The number of items to use.
	 *
	 * @return bool|int
	 */
	public function set_screen_options( $keep, $option, $value ) {

		if ( 'easy_wp_smtp_debug_events_per_page' === $option ) {
			return (int) $value;
		}

		return $keep;
	}

	/**
	 * Whether the email debug for debug events is enabled or not.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_debug_enabled() {

		return (bool) Options::init()->get( 'debug_events', 'email_debug' );
	}

	/**
	 * Get the debug events page URL.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_page_url() {

		return add_query_arg(
			[
				'tab' => 'debug-events',
			],
			easy_wp_smtp()->get_admin()->get_admin_page_url( Area::SLUG . '-tools' )
		);
	}

	/**
	 * Get the DB table name.
	 *
	 * @since 2.0.0
	 *
	 * @return string Table name, prefixed.
	 */
	public static function get_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'easywpsmtp_debug_events';
	}

	/**
	 * Whether the DB table exists.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_valid_db() {

		global $wpdb;

		static $is_valid = null;

		// Return cached value only if table already exists.
		if ( $is_valid === true ) {
			return true;
		}

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$is_valid = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $table ) );

		return $is_valid;
	}
}
