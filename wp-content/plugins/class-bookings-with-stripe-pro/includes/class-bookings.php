<?php
/**
 * Booking lifecycle: capacity calculation, soft-hold creation, status transitions, cron cleanup.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Bookings {

	public const STATUS_PENDING  = 'pending';
	public const STATUS_PAID     = 'paid';
	public const STATUS_EXPIRED  = 'expired';
	public const STATUS_REFUNDED = 'refunded';

	public const CRON_HOOK = 'clasbpro_expire_holds';

	public static function init(): void {
		add_filter( 'cron_schedules', [ self::class, 'register_cron_interval' ] );
		add_action( self::CRON_HOOK, [ self::class, 'expire_stale_holds' ] );
		add_action( 'init', [ self::class, 'maybe_schedule_cron' ] );
	}

	public static function register_cron_interval( array $schedules ): array {
		if ( ! isset( $schedules['clasbpro_five_minutes'] ) ) {
			$schedules['clasbpro_five_minutes'] = [
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 5 minutes (Class Bookings with Stripe)', 'class-bookings-with-stripe-pro' ),
			];
		}
		return $schedules;
	}

	public static function maybe_schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'clasbpro_five_minutes', self::CRON_HOOK );
		}
	}

	public static function on_activate(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'clasbpro_five_minutes', self::CRON_HOOK );
		}
	}

	public static function on_deactivate(): void {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Whether an appointment slot already has a paid or held booking.
	 */
	public static function appointment_slot_taken( int $class_id, string $rule_id, string $class_date ): bool {
		if ( '' === $rule_id ) {
			return false;
		}

		$now_gmt = current_time( 'mysql', true );

		$query = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => '_clasbpro_class_id',
					'value' => $class_id,
				],
				[
					'key'   => '_clasbpro_class_date',
					'value' => $class_date,
				],
				[
					'key'   => '_clasbpro_slot_rule_id',
					'value' => $rule_id,
				],
				[
					'relation' => 'OR',
					[
						'key'   => '_clasbpro_status',
						'value' => self::STATUS_PAID,
					],
					[
						'relation' => 'AND',
						[
							'key'   => '_clasbpro_status',
							'value' => self::STATUS_PENDING,
						],
						[
							'key'     => '_clasbpro_expires_at',
							'value'   => $now_gmt,
							'compare' => '>',
							'type'    => 'DATETIME',
						],
					],
				],
			],
		] );

		return ! empty( $query->posts );
	}

	/**
	 * Map of taken appointment slots in a date range: "Y-m-d|rule_id" => true.
	 *
	 * @return array<string, true>
	 */
	public static function appointment_taken_slots_lookup( int $class_id, string $date_from, string $date_to ): array {
		if ( $class_id <= 0 || '' === $date_from || '' === $date_to ) {
			return [];
		}

		$now_gmt = current_time( 'mysql', true );

		$query = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => '_clasbpro_class_id',
					'value' => $class_id,
				],
				[
					'key'     => '_clasbpro_class_date',
					'value'   => [ $date_from, $date_to ],
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				],
				[
					'relation' => 'OR',
					[
						'key'   => '_clasbpro_status',
						'value' => self::STATUS_PAID,
					],
					[
						'relation' => 'AND',
						[
							'key'   => '_clasbpro_status',
							'value' => self::STATUS_PENDING,
						],
						[
							'key'     => '_clasbpro_expires_at',
							'value'   => $now_gmt,
							'compare' => '>',
							'type'    => 'DATETIME',
						],
					],
				],
			],
		] );

		if ( empty( $query->posts ) ) {
			return [];
		}

		update_postmeta_cache( $query->posts );

		$lookup = [];
		foreach ( $query->posts as $post_id ) {
			$date = (string) get_post_meta( (int) $post_id, '_clasbpro_class_date', true );
			$rule = (string) get_post_meta( (int) $post_id, '_clasbpro_slot_rule_id', true );
			if ( '' !== $date && '' !== $rule ) {
				$lookup[ $date . '|' . $rule ] = true;
			}
		}

		return $lookup;
	}

	/**
	 * Count seats currently taken for (class, date) — paid + active soft-holds.
	 */
	public static function seats_taken( int $class_id, string $class_date, string $slot_rule_id = '' ): int {
		$now_gmt = current_time( 'mysql', true );

		$meta_query = [
			'relation' => 'AND',
			[
				'key'   => '_clasbpro_class_id',
				'value' => $class_id,
			],
			[
				'key'   => '_clasbpro_class_date',
				'value' => $class_date,
			],
		];
		if ( '' !== $slot_rule_id ) {
			$meta_query[] = [
				'key'   => '_clasbpro_slot_rule_id',
				'value' => $slot_rule_id,
			];
		}
		$meta_query[] = [
			'relation' => 'OR',
			[
				'key'   => '_clasbpro_status',
				'value' => self::STATUS_PAID,
			],
			[
				'relation' => 'AND',
				[
					'key'   => '_clasbpro_status',
					'value' => self::STATUS_PENDING,
				],
				[
					'key'     => '_clasbpro_expires_at',
					'value'   => $now_gmt,
					'compare' => '>',
					'type'    => 'DATETIME',
				],
			],
		];

		$query = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => $meta_query,
		] );

		$total = 0;
		foreach ( $query->posts as $post_id ) {
			$total += (int) get_post_meta( (int) $post_id, '_clasbpro_seats', true );
		}
		return $total;
	}

	/**
	 * @return int Remaining capacity for (class, date), clamped at 0.
	 */
	public static function seats_remaining( array $class_data, string $class_date, string $slot_rule_id = '' ): int {
		$capacity = max( 0, (int) ( $class_data['capacity'] ?? 0 ) );
		if ( ! empty( $class_data['is_appointments'] ) && '' !== $slot_rule_id ) {
			return self::appointment_slot_taken( (int) $class_data['id'], $slot_rule_id, $class_date ) ? 0 : $capacity;
		}
		$taken = self::seats_taken( (int) $class_data['id'], $class_date, $slot_rule_id );
		return max( 0, $capacity - $taken );
	}

	/**
	 * Validate that a date is bookable for the given class:
	 *  - class is active
	 *  - the date matches the class's day-of-week
	 *  - the date is not in cancelled_dates
	 *  - the date is in the future (or today before start_time)
	 *
	 * @return string '' if valid, otherwise an error reason code.
	 */
	public static function validate_date( array $class_data, string $class_date, string $slot_rule_id = '' ): string {
		if ( ! empty( $class_data['is_appointments'] ) ) {
			return Slot_Rules::validate_slot( $class_data, $slot_rule_id, $class_date );
		}
		if ( empty( $class_data['class_active'] ) ) {
			return 'class_inactive';
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $class_date ) ) {
			return 'date_invalid';
		}

		try {
			$tz   = wp_timezone();
			$dt   = new \DateTimeImmutable( $class_date, $tz );
			$now  = new \DateTimeImmutable( 'now', $tz );
		} catch ( \Exception $e ) {
			return 'date_invalid';
		}

		if ( ! empty( $class_data['is_one_off_event'] ) ) {
			$start_date = Helpers::normalise_date_string( (string) ( $class_data['start_date'] ?? '' ) );
			$end_date   = Helpers::normalise_date_string( (string) ( $class_data['end_date'] ?? '' ) );
			if ( '' === $start_date ) {
				return 'date_invalid';
			}
			if ( '' === $end_date ) {
				$end_date = $start_date;
			}
			try {
				$range_start = new \DateTimeImmutable( $start_date, $tz );
				$range_end   = new \DateTimeImmutable( $end_date, $tz );
			} catch ( \Exception $e ) {
				return 'date_invalid';
			}
			if ( $range_end < $range_start || $dt < $range_start || $dt > $range_end ) {
				return 'date_invalid';
			}
		} else {
			// Day of week match.
			$weekday = strtolower( $class_data['day_of_week'] ?? '' );
			$want    = strtolower( $dt->format( 'l' ) );
			if ( $weekday !== $want ) {
				return 'date_invalid';
			}
			if ( ! Helpers::date_in_class_run_window( $class_data, $class_date ) ) {
				return 'date_invalid';
			}
		}

		// Cancelled.
		if ( in_array( $class_date, (array) ( $class_data['cancelled_dates'] ?? [] ), true ) ) {
			return 'date_invalid';
		}

		// Past date — allow today before start_time.
		$class_start = $dt->modify( $class_data['start_time'] ?: '00:00' );
		if ( ! $class_start ) {
			return 'date_invalid';
		}
		if ( $class_start <= $now ) {
			return 'date_invalid';
		}

		return '';
	}

	/**
	 * Whether a recurring class has at least one bookable date in the calendar window.
	 *
	 * @param array<string, mixed> $class_data
	 */
	public static function has_bookable_recurring_dates_in_calendar_window( array $class_data ): bool {
		if ( empty( $class_data['class_active'] ) || empty( $class_data['start_time'] ) ) {
			return false;
		}
		$weekday = strtolower( (string) ( $class_data['day_of_week'] ?? '' ) );
		if ( '' === $weekday ) {
			return false;
		}

		try {
			$tz     = wp_timezone();
			$cursor = ( new \DateTimeImmutable( 'now', $tz ) )->modify( 'first day of this month' );
			$months = max( 1, min( 12, (int) ( $class_data['calendar_months_ahead'] ?? 3 ) ) );
			$end    = $cursor->modify( '+' . $months . ' months' );
		} catch ( \Exception $e ) {
			return false;
		}

		while ( $cursor <= $end ) {
			$days = self::recurring_month_calendar_days(
				$class_data,
				(int) $cursor->format( 'Y' ),
				(int) $cursor->format( 'n' )
			);
			foreach ( $days as $day ) {
				if ( ! empty( $day['selectable'] ) ) {
					return true;
				}
			}
			$cursor = $cursor->modify( 'first day of next month' );
		}

		return false;
	}

	/**
	 * Class-date calendar payload for one month (weekly recurring classes).
	 *
	 * @param array<string, mixed> $class_data
	 * @return array<string, array{has_class: bool, selectable: bool, cancelled: bool, full: bool, remaining: int, time_label: string, label: string}>
	 */
	public static function recurring_month_calendar_days( array $class_data, int $year, int $month ): array {
		if ( empty( $class_data['class_active'] ) || empty( $class_data['start_time'] ) ) {
			return [];
		}

		$weekday = strtolower( (string) ( $class_data['day_of_week'] ?? '' ) );
		if ( '' === $weekday ) {
			return [];
		}

		try {
			$tz          = wp_timezone();
			$now         = new \DateTimeImmutable( 'now', $tz );
			$month_start = new \DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), $tz );
			$month_end   = $month_start->modify( 'last day of this month' );
		} catch ( \Exception $e ) {
			return [];
		}

		$cancelled = (array) ( $class_data['cancelled_dates'] ?? [] );
		$mode      = Helpers::cancelled_dates_display( $class_data );
		$days      = [];
		$walk      = $month_start;

		while ( $walk <= $month_end ) {
			$date = $walk->format( 'Y-m-d' );

			if ( strtolower( $walk->format( 'l' ) ) !== $weekday ) {
				$walk = $walk->modify( '+1 day' );
				continue;
			}

			$walk = $walk->modify( '+1 day' );

			if ( ! Helpers::date_in_class_run_window( $class_data, $date ) ) {
				continue;
			}

			try {
				$dt          = new \DateTimeImmutable( $date, $tz );
				$class_start = $dt->modify( (string) $class_data['start_time'] );
			} catch ( \Exception $e ) {
				continue;
			}

			if ( ! $class_start || $class_start <= $now ) {
				continue;
			}

			$is_cancelled = in_array( $date, $cancelled, true );
			if ( $is_cancelled && 'show' !== $mode ) {
				// hide: omit so the day looks like a normal unavailable calendar day.
				continue;
			}

			$remaining  = $is_cancelled ? 0 : self::seats_remaining( $class_data, $date );
			$selectable = ! $is_cancelled && $remaining > 0;
			$time_label = Helpers::format_time( (string) $class_data['start_time'] );

			$days[ $date ] = [
				'has_class'  => true,
				'selectable' => $selectable,
				'cancelled'  => $is_cancelled,
				'full'       => ! $is_cancelled && $remaining <= 0,
				'remaining'  => $remaining,
				'time_label' => $time_label,
				'label'      => Helpers::format_date( $date ) . ' · ' . $time_label,
			];
		}

		return $days;
	}

	/**
	 * Get the next $count upcoming dates with availability metadata.
	 *
	 * @return array<int, array{date: string, label: string, remaining: int, cancelled: bool, selectable: bool}>
	 */
	public static function next_available_dates( array $class_data, int $count = 3 ): array {
		if ( empty( $class_data['class_active'] ) ) {
			return [];
		}
		if ( empty( $class_data['start_time'] ) ) {
			return [];
		}

		$is_one_off = ! empty( $class_data['is_one_off_event'] );
		$weekday    = strtolower( $class_data['day_of_week'] ?? '' );
		if ( ! $is_one_off && '' === $weekday ) {
			return [];
		}
		if ( $is_one_off && empty( $class_data['start_date'] ) ) {
			return [];
		}

		$results   = [];
		$attempt   = 0;
		$batch     = $count;
		$cancelled = (array) ( $class_data['cancelled_dates'] ?? [] );
		$mode      = Helpers::cancelled_dates_display( $class_data );
		$from      = Helpers::normalise_date_string( (string) ( $class_data['start_date'] ?? '' ) );
		$to        = Helpers::normalise_date_string( (string) ( $class_data['end_date'] ?? '' ) );

		// Pull a wider window so that fully-booked dates can be skipped while still returning $count items.
		while ( count( $results ) < $count && $attempt < 6 ) {
			$batch += $count;
			$dates = $is_one_off
				? Helpers::date_range_occurrences( (string) $class_data['start_date'], (string) $class_data['end_date'], (string) $class_data['start_time'], $batch, [] )
				: Helpers::next_weekday_occurrences( $weekday, $class_data['start_time'], $batch, [], $from, $to );
			$results = [];
			foreach ( $dates as $date ) {
				$is_cancelled = in_array( $date, $cancelled, true );
				if ( $is_cancelled && 'show' !== $mode ) {
					// hide: do not list cancelled dates in the dropdown.
					continue;
				}
				$remaining     = $is_cancelled ? 0 : self::seats_remaining( $class_data, $date );
				$is_selectable = ( ! $is_cancelled && $remaining > 0 );

				// Keep labelled cancelled dates visible when mode is "show"; hide fully-booked dates.
				if ( ! $is_cancelled && $remaining <= 0 ) {
					continue;
				}

				$results[] = [
					'date'       => $date,
					'label'      => Helpers::format_date( $date ) . ' · ' . Helpers::format_time( $class_data['start_time'] ),
					'remaining'  => $remaining,
					'cancelled'  => $is_cancelled,
					'selectable' => $is_selectable,
				];
				if ( count( $results ) >= $count ) {
					break;
				}
			}
			$attempt++;
		}

		return $results;
	}

	/**
	 * Create a pending booking with a 30-minute soft-hold.
	 *
	 * @return int|\WP_Error Booking post ID, or WP_Error.
	 */
	public static function create_pending_booking( array $params ) {
		$class_id     = (int) $params['class_id'];
		$class_date   = (string) $params['class_date'];
		$slot_rule_id = sanitize_key( (string) ( $params['slot_rule_id'] ?? '' ) );
		$seats        = (int) $params['seats'];
		$slot_snapshot = is_array( $params['slot_snapshot'] ?? null ) ? (array) $params['slot_snapshot'] : [];
		$name        = sanitize_text_field( (string) ( $params['customer_name'] ?? '' ) );
		$email       = sanitize_email( (string) ( $params['customer_email'] ?? '' ) );
		$amount_pence = (int) $params['amount_pence'];
		$waiver_accepted = ! empty( $params['waiver_accepted'] ) ? 1 : 0;
		$mailchimp_opt_in = ! empty( $params['mailchimp_opt_in'] ) ? 1 : 0;
		$extra_fields = is_array( $params['extra_fields'] ?? null ) ? (array) $params['extra_fields'] : [];

		$post_id = wp_insert_post( [
			'post_type'   => CPT::BOOKING_PT,
			'post_status' => 'publish',
			'post_title'  => sprintf(
				/* translators: 1: customer name, 2: class title, 3: date */
				__( '%1$s · %2$s · %3$s', 'class-bookings-with-stripe-pro' ),
				$name ?: __( 'Pending', 'class-bookings-with-stripe-pro' ),
				get_the_title( $class_id ),
				Helpers::format_date( $class_date )
			),
		], true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + CLASBOWPRO_HOLD_SECONDS );

		update_post_meta( $post_id, '_clasbpro_class_id', $class_id );
		update_post_meta( $post_id, '_clasbpro_class_date', $class_date );
		if ( '' !== $slot_rule_id ) {
			update_post_meta( $post_id, '_clasbpro_slot_rule_id', $slot_rule_id );
		}
		if ( ! empty( $slot_snapshot ) ) {
			update_post_meta( $post_id, '_clasbpro_slot_snapshot', wp_json_encode( $slot_snapshot ) );
		}
		update_post_meta( $post_id, '_clasbpro_seats', $seats );
		update_post_meta( $post_id, '_clasbpro_customer_name', $name );
		update_post_meta( $post_id, '_clasbpro_customer_email', $email );
		update_post_meta( $post_id, '_clasbpro_amount_total', $amount_pence );
		update_post_meta( $post_id, '_clasbpro_waiver_accepted', $waiver_accepted );
		update_post_meta( $post_id, '_clasbpro_mailchimp_opt_in', $mailchimp_opt_in );
		update_post_meta( $post_id, '_clasbpro_extra_fields', wp_json_encode( $extra_fields ) );
		update_post_meta( $post_id, '_clasbpro_status', self::STATUS_PENDING );
		update_post_meta( $post_id, '_clasbpro_expires_at', $expires_at );
		update_post_meta( $post_id, '_clasbpro_created_gmt', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $post_id, '_clasbpro_status_token', wp_generate_password( 32, false, false ) );

		return (int) $post_id;
	}

	public static function verify_status_token( int $booking_id, string $token ): bool {
		if ( '' === $token ) {
			return false;
		}
		$stored = (string) get_post_meta( $booking_id, '_clasbpro_status_token', true );
		return '' !== $stored && hash_equals( $stored, $token );
	}

	public static function attach_stripe_session( int $booking_id, string $session_id ): void {
		update_post_meta( $booking_id, '_clasbpro_stripe_session_id', $session_id );
	}

	public static function find_by_stripe_session( string $session_id ): ?int {
		if ( '' === $session_id ) {
			return null;
		}
		$query = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				[
					'key'   => '_clasbpro_stripe_session_id',
					'value' => $session_id,
				],
			],
		] );
		if ( empty( $query->posts ) ) {
			return null;
		}
		return (int) $query->posts[0];
	}

	public static function set_status( int $booking_id, string $status ): void {
		update_post_meta( $booking_id, '_clasbpro_status', $status );
		update_post_meta( $booking_id, '_clasbpro_status_updated_gmt', gmdate( 'Y-m-d H:i:s' ) );

		Reports::invalidate_cache();

		if ( in_array( $status, [ self::STATUS_REFUNDED, self::STATUS_EXPIRED ], true ) ) {
			Scheduled_Emails::cancel_for_booking( $booking_id );
		}
	}

	public static function get_status( int $booking_id ): string {
		return (string) get_post_meta( $booking_id, '_clasbpro_status', true );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_slot_snapshot( int $booking_id ): ?array {
		$raw = (string) get_post_meta( $booking_id, '_clasbpro_slot_snapshot', true );
		if ( '' === $raw ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Resolved display context for emails and admin (snapshot-first).
	 *
	 * @return array{start_time: string, location: string, duration: int, label: string, price: float}
	 */
	public static function get_booking_display_context( int $booking_id, ?array $class_data = null ): array {
		$meta     = self::get_meta( $booking_id );
		$snapshot = self::get_slot_snapshot( $booking_id );
		if ( $snapshot ) {
			return [
				'start_time' => (string) ( $snapshot['start_time'] ?? '' ),
				'location'   => (string) ( $snapshot['location'] ?? '' ),
				'duration'   => (int) ( $snapshot['duration_minutes'] ?? 0 ),
				'label'      => (string) ( $snapshot['label'] ?? '' ),
				'price'      => (float) ( $snapshot['price_gbp'] ?? 0 ),
			];
		}
		if ( null === $class_data ) {
			$class_data = Helpers::get_class_data( (int) $meta['class_id'] );
		}
		return [
			'start_time' => (string) ( $class_data['start_time'] ?? '' ),
			'location'   => (string) ( $class_data['location'] ?? '' ),
			'duration'   => (int) ( $class_data['duration'] ?? 0 ),
			'label'      => '',
			'price'      => (float) ( $class_data['price'] ?? 0 ),
		];
	}

	public static function get_meta( int $booking_id ): array {
		return [
			'class_id'           => (int) get_post_meta( $booking_id, '_clasbpro_class_id', true ),
			'class_date'         => (string) get_post_meta( $booking_id, '_clasbpro_class_date', true ),
			'slot_rule_id'       => (string) get_post_meta( $booking_id, '_clasbpro_slot_rule_id', true ),
			'seats'              => (int) get_post_meta( $booking_id, '_clasbpro_seats', true ),
			'customer_name'      => (string) get_post_meta( $booking_id, '_clasbpro_customer_name', true ),
			'customer_email'     => (string) get_post_meta( $booking_id, '_clasbpro_customer_email', true ),
			'amount_total_pence' => (int) get_post_meta( $booking_id, '_clasbpro_amount_total', true ),
			'waiver_accepted'    => (int) get_post_meta( $booking_id, '_clasbpro_waiver_accepted', true ),
			'mailchimp_opt_in'   => (int) get_post_meta( $booking_id, '_clasbpro_mailchimp_opt_in', true ),
			'extra_fields_json'  => (string) get_post_meta( $booking_id, '_clasbpro_extra_fields', true ),
			'status'             => (string) get_post_meta( $booking_id, '_clasbpro_status', true ),
			'stripe_session_id'  => (string) get_post_meta( $booking_id, '_clasbpro_stripe_session_id', true ),
			'stripe_payment_intent' => (string) get_post_meta( $booking_id, '_clasbpro_stripe_payment_intent', true ),
		];
	}

	/**
	 * Cron callback — mark stale pending bookings as expired so seats free up promptly.
	 */
	public static function expire_stale_holds(): void {
		$now_gmt = current_time( 'mysql', true );

		$query = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => '_clasbpro_status',
					'value' => self::STATUS_PENDING,
				],
				[
					'key'     => '_clasbpro_expires_at',
					'value'   => $now_gmt,
					'compare' => '<',
					'type'    => 'DATETIME',
				],
			],
		] );

		foreach ( $query->posts as $post_id ) {
			self::set_status( (int) $post_id, self::STATUS_EXPIRED );
		}
	}
}
