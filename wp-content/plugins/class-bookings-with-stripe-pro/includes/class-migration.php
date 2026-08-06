<?php
/**
 * One-time migration from legacy unprefixed / short-prefixed identifiers.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Migration {

	private const OPTION_VERSION = 'clasbpro_db_version';
	private const DB_VERSION     = 8;

	public static function maybe_run(): void {
		$current = (int) get_option( self::OPTION_VERSION, 0 );
		if ( $current >= self::DB_VERSION ) {
			return;
		}

		if ( $current < 2 ) {
			self::migrate_post_types();
			self::migrate_post_meta_keys();
			self::migrate_options();
			self::migrate_cron();
			self::migrate_page_meta();
		}

		if ( $current < 3 ) {
			Scheduled_Emails::create_table();
		}

		if ( $current < 4 ) {
			self::migrate_scheduled_email_rules();
		}

		if ( $current < 5 ) {
			Scheduled_Emails::create_table();
		}

		if ( $current < 6 ) {
			self::remove_feedback_url();
		}

		if ( $current < 7 ) {
			Scheduled_Emails::ensure_body_html_mode_column();
		}

		if ( $current < 8 ) {
			self::migrate_stripe_currency_option();
		}

		update_option( self::OPTION_VERSION, self::DB_VERSION, false );
	}

	private static function migrate_stripe_currency_option(): void {
		if ( '' !== get_option( Constants::STRIPE_CURRENCY_OPTION, '' ) ) {
			return;
		}

		$candidates = [
			(string) Helpers::get_option( 'stripe_currency', '' ),
			(string) get_option( 'clasbpro_options_stripe_currency', '' ),
			(string) get_option( 'options_stripe_currency', '' ),
		];

		foreach ( $candidates as $raw ) {
			$code = strtolower( trim( $raw ) );
			if ( '' !== $code && array_key_exists( $code, Helpers::stripe_currencies() ) ) {
				update_option( Constants::STRIPE_CURRENCY_OPTION, $code, false );
				return;
			}
		}
	}

	private static function migrate_post_types(): void {
		global $wpdb;

		$map = [
			'clasbpro_class'   => Constants::CPT_CLASS,
			'clasbpro_booking' => Constants::CPT_BOOKING,
		];

		foreach ( $map as $from => $to ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->posts,
				[ 'post_type' => $to ],
				[ 'post_type' => $from ],
				[ '%s' ],
				[ '%s' ]
			);
		}
	}

	private static function migrate_post_meta_keys(): void {
		global $wpdb;

		$from = '_clasbpro_';
		$to   = Constants::META_PREFIX;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, %s, %s) WHERE meta_key LIKE %s AND meta_key NOT LIKE %s",
				$from,
				$to,
				$wpdb->esc_like( $from ) . '%',
				$wpdb->esc_like( $to ) . '%'
			)
		);
	}

	private static function migrate_options(): void {
		global $wpdb;

		$legacy = 'clasbpro_options';
		$new    = Constants::OPTIONS_POST_ID;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_name = REPLACE(option_name, %s, %s) WHERE option_name LIKE %s",
				$legacy,
				$new,
				$wpdb->esc_like( $legacy ) . '%'
			)
		);
	}

	private static function migrate_cron(): void {
		$legacy_hook = 'clasbpro_expire_holds';
		$legacy_sched = 'clasbpro_five_minutes';

		while ( $ts = wp_next_scheduled( $legacy_hook ) ) {
			wp_unschedule_event( $ts, $legacy_hook );
		}

		if ( ! wp_next_scheduled( Constants::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, Constants::CRON_SCHEDULE, Constants::CRON_HOOK );
		}

		$schedules = wp_get_schedules();
		if ( isset( $schedules[ $legacy_sched ] ) && ! isset( $schedules[ Constants::CRON_SCHEDULE ] ) ) {
			// Schedules are filters; next init will register clasbpro_five_minutes.
		}
	}

	private static function migrate_page_meta(): void {
		global $wpdb;

		$map = [
			'_clasbpro_result_page_success' => Constants::META_PREFIX . 'result_page_success',
			'_clasbpro_result_page_cancel'  => Constants::META_PREFIX . 'result_page_cancel',
			'_clasbpro_result_page_error'   => Constants::META_PREFIX . 'result_page_error',
		];

		foreach ( $map as $from => $to ) {
			if ( $from === $to ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
					$to,
					$from
				)
			);
		}
	}

	private static function migrate_scheduled_email_rules(): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}

		$post_id = Constants::OPTIONS_POST_ID;

		self::migrate_legacy_rules_json(
			'reminder_email_rules_json',
			'reminder',
			Scheduled_Emails::TYPE_REMINDER,
			$post_id
		);
		self::migrate_legacy_rules_json(
			'post_class_email_rules_json',
			'post_class',
			Scheduled_Emails::TYPE_POST_CLASS,
			$post_id
		);
	}

	private static function migrate_legacy_rules_json( string $json_field, string $prefix, string $type, string $post_id ): void {
		if ( (int) Helpers::get_option( $prefix . '_offset_amount', 0 ) > 0 ) {
			return;
		}

		$raw  = Helpers::get_option( $json_field, '' );
		$rows = Scheduled_Emails::decode_rules_json( is_string( $raw ) ? $raw : '' );
		if ( empty( $rows ) || ! is_array( $rows[0] ) ) {
			return;
		}

		$row = $rows[0];

		update_field( $prefix . '_offset_amount', max( 1, (int) ( $row['offset_amount'] ?? ( Scheduled_Emails::TYPE_REMINDER === $type ? 24 : 3 ) ) ), $post_id );
		update_field( $prefix . '_offset_unit', (string) ( $row['offset_unit'] ?? 'hours' ), $post_id );
		update_field( $prefix . '_email_subject', (string) ( $row['subject'] ?? '' ), $post_id );
		update_field( $prefix . '_email_body', self::strip_feedback_merge_tags( (string) ( $row['body'] ?? '' ) ), $post_id );
		update_field( $prefix . '_admin_copy', ! empty( $row['admin_copy'] ) ? 1 : 0, $post_id );

		$uuid = trim( (string) ( $row['rule_uuid'] ?? $row['uuid'] ?? '' ) );
		if ( '' === $uuid ) {
			$uuid = wp_generate_uuid4();
		}
		update_field( $prefix . '_email_rule_uuid', $uuid, $post_id );
	}

	private static function remove_feedback_url(): void {
		Scheduled_Emails::clean_feedback_merge_tags_from_queue();
		Scheduled_Emails::drop_feedback_url_column();

		if ( ! function_exists( 'update_field' ) ) {
			return;
		}

		$post_id = Constants::OPTIONS_POST_ID;
		$fields  = [
			'admin_email_body',
			'customer_email_body',
			'reminder_email_body',
			'post_class_email_body',
			'reminder_email_rules_json',
			'post_class_email_rules_json',
		];

		foreach ( $fields as $field ) {
			$raw = Helpers::get_option( $field, '' );
			if ( ! is_string( $raw ) || '' === $raw || false === stripos( $raw, 'feedback' ) ) {
				continue;
			}

			update_field( $field, Scheduled_Emails::strip_feedback_merge_tags( $raw ), $post_id );
		}

		delete_field( 'default_feedback_url', $post_id );
	}
}
