<?php
/**
 * Helper functions: date math, money formatting, ACF option getters.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Helpers {

	private const DAYS = [
		'monday'    => 1,
		'tuesday'   => 2,
		'wednesday' => 3,
		'thursday'  => 4,
		'friday'    => 5,
		'saturday'  => 6,
		'sunday'    => 7,
	];

	/**
	 * Get the next $count occurrences of $weekday (e.g. 'sunday') as Y-m-d strings,
	 * starting from "now" in the site timezone. Skips today if its $start_time has already passed.
	 *
	 * @param string             $weekday    e.g. 'sunday'
	 * @param string             $start_time HH:MM (24h)
	 * @param int                $count      How many occurrences
	 * @param array<int, string> $skip_dates Y-m-d strings to skip (cancelled)
	 * @param string             $from_date  Optional inclusive lower bound (Y-m-d).
	 * @param string             $to_date    Optional inclusive upper bound (Y-m-d).
	 * @return array<int, string>            Y-m-d strings
	 */
	public static function next_weekday_occurrences( string $weekday, string $start_time, int $count, array $skip_dates = [], string $from_date = '', string $to_date = '' ): array {
		$weekday = strtolower( trim( $weekday ) );
		if ( ! isset( self::DAYS[ $weekday ] ) ) {
			return [];
		}

		$from_date = self::normalise_date_string( $from_date );
		$to_date   = self::normalise_date_string( $to_date );

		try {
			$tz   = wp_timezone();
			$now  = new \DateTimeImmutable( 'now', $tz );
			$walk = $now->setTime( 0, 0, 0 );
			if ( '' !== $from_date ) {
				$from = new \DateTimeImmutable( $from_date, $tz );
				if ( $walk < $from ) {
					$walk = $from;
				}
			}
		} catch ( \Exception $e ) {
			return [];
		}

		$results        = [];
		$todays_weekday = (int) $now->format( 'N' );
		$target_weekday = self::DAYS[ $weekday ];

		$include_candidate = static function ( string $candidate ) use ( $skip_dates, $from_date, $to_date ): bool {
			if ( in_array( $candidate, $skip_dates, true ) ) {
				return false;
			}
			if ( '' !== $from_date && $candidate < $from_date ) {
				return false;
			}
			if ( '' !== $to_date && $candidate > $to_date ) {
				return false;
			}
			return true;
		};

		// If today matches the weekday, only include it if the class hasn't started yet.
		$today_is_target = ( $todays_weekday === $target_weekday ) && $walk->format( 'Y-m-d' ) === $now->format( 'Y-m-d' );
		if ( $today_is_target ) {
			$today_class_start = $now->modify( $start_time );
			if ( $today_class_start && $now < $today_class_start ) {
				$candidate = $walk->format( 'Y-m-d' );
				if ( $include_candidate( $candidate ) ) {
					$results[] = $candidate;
				}
			}
			$walk = $walk->modify( '+1 day' );
		}

		$max_iterations = 730;
		while ( count( $results ) < $count && $max_iterations-- > 0 ) {
			if ( '' !== $to_date && $walk->format( 'Y-m-d' ) > $to_date ) {
				break;
			}
			$walk_weekday = (int) $walk->format( 'N' );
			if ( $walk_weekday === $target_weekday ) {
				$candidate = $walk->format( 'Y-m-d' );
				if ( $include_candidate( $candidate ) ) {
					$results[] = $candidate;
				}
				$walk = $walk->modify( '+7 days' );
			} else {
				$diff = ( $target_weekday - $walk_weekday + 7 ) % 7;
				$walk = $walk->modify( "+{$diff} days" );
			}
		}

		return $results;
	}

	/**
	 * Whether a Y-m-d date falls within the class run window (start_date / end_date).
	 * Empty bounds mean unbounded on that side. One-off classes still require start_date.
	 *
	 * @param array<string, mixed> $class_data
	 */
	public static function date_in_class_run_window( array $class_data, string $date ): bool {
		$date = self::normalise_date_string( $date );
		if ( '' === $date ) {
			return false;
		}
		$start = self::normalise_date_string( (string) ( $class_data['start_date'] ?? '' ) );
		$end   = self::normalise_date_string( (string) ( $class_data['end_date'] ?? '' ) );
		if ( ! empty( $class_data['is_one_off_event'] ) ) {
			if ( '' === $start ) {
				return false;
			}
			if ( '' === $end ) {
				$end = $start;
			}
		}
		if ( '' !== $start && $date < $start ) {
			return false;
		}
		if ( '' !== $end && $date > $end ) {
			return false;
		}
		return true;
	}

	/**
	 * How cancelled dates should appear: show | hide.
	 * Legacy value "unbookable" is treated as "hide".
	 *
	 * @param array<string, mixed> $class_data
	 */
	public static function cancelled_dates_display( array $class_data ): string {
		$mode = sanitize_key( (string) ( $class_data['cancelled_dates_display'] ?? 'show' ) );
		if ( 'unbookable' === $mode ) {
			return 'hide';
		}
		if ( ! in_array( $mode, [ 'show', 'hide' ], true ) ) {
			return 'show';
		}
		return $mode;
	}

	/**
	 * Get upcoming dates inside a one-off event date range.
	 *
	 * @param array<int, string> $skip_dates Y-m-d strings to skip.
	 * @return array<int, string>
	 */
	public static function date_range_occurrences( string $start_date, string $end_date, string $start_time, int $count, array $skip_dates = [] ): array {
		$start_date = self::normalise_date_string( $start_date );
		$end_date   = self::normalise_date_string( $end_date );
		if ( '' === $start_date ) {
			return [];
		}
		if ( '' === $end_date ) {
			$end_date = $start_date;
		}

		try {
			$tz    = wp_timezone();
			$now   = new \DateTimeImmutable( 'now', $tz );
			$walk  = new \DateTimeImmutable( $start_date, $tz );
			$end   = new \DateTimeImmutable( $end_date, $tz );
		} catch ( \Exception $e ) {
			return [];
		}

		if ( $end < $walk ) {
			return [];
		}

		$results        = [];
		$max_iterations = 366;
		while ( count( $results ) < $count && $walk <= $end && $max_iterations-- > 0 ) {
			$candidate       = $walk->format( 'Y-m-d' );
			$candidate_start = $walk->modify( $start_time ?: '00:00' );
			if ( $candidate_start && $candidate_start > $now && ! in_array( $candidate, $skip_dates, true ) ) {
				$results[] = $candidate;
			}
			$walk = $walk->modify( '+1 day' );
		}

		return $results;
	}

	/**
	 * Format Y-m-d as e.g. "Sun 17 May 2026".
	 */
	public static function format_date( string $ymd ): string {
		try {
			$dt = new \DateTimeImmutable( $ymd, wp_timezone() );
			return wp_date( 'D j M Y', $dt->getTimestamp() );
		} catch ( \Exception $e ) {
			return $ymd;
		}
	}

	/**
	 * Format a one-off event date range.
	 */
	public static function format_date_range( string $start_date, string $end_date = '' ): string {
		$start_date = self::normalise_date_string( $start_date );
		$end_date   = self::normalise_date_string( $end_date );
		if ( '' === $start_date ) {
			return '';
		}
		if ( '' === $end_date || $end_date === $start_date ) {
			return self::format_date( $start_date );
		}

		return self::format_date( $start_date ) . ' - ' . self::format_date( $end_date );
	}

	/**
	 * Format HH:MM as 12-hour (e.g. "10:15 AM"). Falls back to input.
	 */
	public static function format_time( string $hhmm ): string {
		$ts = strtotime( $hhmm );
		if ( false === $ts ) {
			return $hhmm;
		}
		return wp_date( 'g:i A', $ts );
	}

	/**
	 * Sanitize HTML for the waiver checkbox label (post-like tags, including links).
	 */
	public static function waiver_label_kses( string $html ): string {
		$out = wp_kses_post( $html );
		return apply_filters( 'clasbpro_waiver_label_kses', $out, $html );
	}

	/**
	 * Curated Stripe currencies supported by this plugin.
	 *
	 * @return array<string, array{label: string, symbol: string, decimals: int, position: string}>
	 */
	public static function stripe_currencies(): array {
		$currencies = [
			'gbp' => [
				'label'    => __( 'British Pound (£)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => '£',
				'decimals' => 2,
				'position' => 'before',
			],
			'usd' => [
				'label'    => __( 'US Dollar ($)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => '$',
				'decimals' => 2,
				'position' => 'before',
			],
			'eur' => [
				'label'    => __( 'Euro (€)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => '€',
				'decimals' => 2,
				'position' => 'before',
			],
			'aud' => [
				'label'    => __( 'Australian Dollar (A$)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'A$',
				'decimals' => 2,
				'position' => 'before',
			],
			'cad' => [
				'label'    => __( 'Canadian Dollar (C$)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'C$',
				'decimals' => 2,
				'position' => 'before',
			],
			'nzd' => [
				'label'    => __( 'New Zealand Dollar (NZ$)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'NZ$',
				'decimals' => 2,
				'position' => 'before',
			],
			'chf' => [
				'label'    => __( 'Swiss Franc (CHF)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'CHF ',
				'decimals' => 2,
				'position' => 'before',
			],
			'sek' => [
				'label'    => __( 'Swedish Krona (kr)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'kr',
				'decimals' => 2,
				'position' => 'after',
			],
			'nok' => [
				'label'    => __( 'Norwegian Krone (kr)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'kr',
				'decimals' => 2,
				'position' => 'after',
			],
			'dkk' => [
				'label'    => __( 'Danish Krone (kr)', 'class-bookings-with-stripe-pro' ),
				'symbol'   => 'kr',
				'decimals' => 2,
				'position' => 'after',
			],
		];

		/**
		 * Filter the curated Stripe currency list.
		 *
		 * @param array<string, array{label: string, symbol: string, decimals: int, position: string}> $currencies
		 */
		return apply_filters( 'clasbpro_stripe_currencies', $currencies );
	}

	/**
	 * Active Stripe currency code (lowercase ISO 4217).
	 */
	public static function currency(): string {
		$code = strtolower( trim( (string) get_option( Constants::STRIPE_CURRENCY_OPTION, '' ) ) );
		if ( '' === $code ) {
			$code = strtolower( trim( (string) self::get_option( 'stripe_currency', '' ) ) );
		}
		if ( '' === $code ) {
			$code = 'gbp';
		}
		$currencies = self::stripe_currencies();
		return array_key_exists( $code, $currencies ) ? $code : 'gbp';
	}

	/**
	 * Save Stripe currency to the canonical WP option only.
	 */
	public static function save_stripe_currency( string $code ): bool {
		$code = strtolower( sanitize_key( $code ) );
		if ( ! array_key_exists( $code, self::stripe_currencies() ) ) {
			return false;
		}

		update_option( Constants::STRIPE_CURRENCY_OPTION, $code, false );
		return true;
	}

	/**
	 * Persist the site Stripe currency (WP option + ACF when available).
	 */
	public static function set_stripe_currency( string $code ): bool {
		if ( ! self::save_stripe_currency( $code ) ) {
			return false;
		}

		if ( function_exists( 'update_field' ) ) {
			update_field( 'stripe_currency', strtolower( sanitize_key( $code ) ), Constants::OPTIONS_POST_ID );
		}

		return true;
	}

	/**
	 * @return array{label: string, symbol: string, decimals: int, position: string}
	 */
	public static function currency_config( ?string $currency = null ): array {
		$code       = $currency ? strtolower( $currency ) : self::currency();
		$currencies = self::stripe_currencies();
		return $currencies[ $code ] ?? $currencies['gbp'];
	}

	public static function currency_minor_unit_multiplier( ?string $currency = null ): int {
		$decimals = (int) ( self::currency_config( $currency )['decimals'] ?? 2 );
		return (int) ( 10 ** max( 0, $decimals ) );
	}

	/**
	 * Label for class/slot price fields in admin.
	 */
	public static function price_field_label(): string {
		$symbol = trim( (string) ( self::currency_config()['symbol'] ?? '£' ) );
		return sprintf(
			/* translators: %s: currency symbol or code */
			__( 'Price (%s)', 'class-bookings-with-stripe-pro' ),
			$symbol
		);
	}

	public static function price_input_step( ?string $currency = null ): string {
		return 0 === (int) ( self::currency_config( $currency )['decimals'] ?? 2 ) ? '1' : '0.01';
	}

	/**
	 * Currency formatting config for frontend JavaScript.
	 *
	 * @return array{code: string, symbol: string, decimals: int, position: string}
	 */
	public static function currency_format_config( ?string $currency = null ): array {
		$config = self::currency_config( $currency );
		return [
			'code'     => $currency ? strtolower( $currency ) : self::currency(),
			'symbol'   => (string) $config['symbol'],
			'decimals' => (int) $config['decimals'],
			'position' => (string) ( $config['position'] ?? 'before' ),
		];
	}

	/**
	 * Format a major-unit amount for display (e.g. 15.00 → "£15.00", 1500 → "¥1500").
	 *
	 * @param float|int|string $amount
	 */
	public static function format_price( $amount, ?string $currency = null ): string {
		$config   = self::currency_config( $currency );
		$decimals = (int) $config['decimals'];
		$formatted = number_format( (float) $amount, $decimals );
		if ( 'after' === ( $config['position'] ?? 'before' ) ) {
			return $formatted . ' ' . $config['symbol'];
		}
		return $config['symbol'] . $formatted;
	}

	/**
	 * Format a Stripe minor-unit amount for display.
	 */
	public static function format_stripe_amount( int $amount, ?string $currency = null ): string {
		return self::format_price( self::from_stripe_amount( $amount, $currency ), $currency );
	}

	/**
	 * Convert a major-unit amount to Stripe minor units (pence, cents, whole yen, etc.).
	 *
	 * @param float|int|string $amount
	 */
	public static function to_pence( $amount, ?string $currency = null ): int {
		return self::to_stripe_amount( $amount, $currency );
	}

	/**
	 * @param float|int|string $amount
	 */
	public static function to_stripe_amount( $amount, ?string $currency = null ): int {
		$multiplier = self::currency_minor_unit_multiplier( $currency );
		return (int) round( ( (float) $amount ) * $multiplier );
	}

	/**
	 * Convert Stripe minor units back to a major-unit float.
	 */
	public static function from_stripe_amount( int $amount, ?string $currency = null ): float {
		$multiplier = self::currency_minor_unit_multiplier( $currency );
		if ( $multiplier <= 0 ) {
			return (float) $amount;
		}
		return (float) $amount / $multiplier;
	}

	/**
	 * Get an ACF option, falling back if ACF isn't available.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public static function get_option( string $key, $default = '' ) {
		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $key, 'clasbpro_options' );
			if ( null !== $value && '' !== $value ) {
				return $value;
			}
		}
		return $default;
	}

	/**
	 * Get the active Stripe secret key based on the configured mode.
	 */
	public static function stripe_secret_key(): string {
		$mode = self::get_option( 'stripe_mode', 'test' );
		$field = 'live' === $mode ? 'stripe_secret_key_live' : 'stripe_secret_key_test';
		return trim( Secrets::get( $field ) );
	}

	/**
	 * Get the active Stripe publishable key.
	 */
	public static function stripe_publishable_key(): string {
		$mode = self::get_option( 'stripe_mode', 'test' );
		$key  = 'live' === $mode
			? self::get_option( 'stripe_pub_key_live', '' )
			: self::get_option( 'stripe_pub_key_test', '' );
		return is_string( $key ) ? trim( $key ) : '';
	}

	public static function stripe_webhook_secret(): string {
		return trim( Secrets::get( 'stripe_webhook_secret' ) );
	}

	/**
	 * Number of upcoming dates shown in the booking form dropdown for a class.
	 *
	 * @param array<string, mixed> $class_data Shape from {@see get_class_data()}.
	 */
	public static function class_upcoming_dates_count( array $class_data ): int {
		if ( ! empty( $class_data['is_appointments'] ) ) {
			return 1;
		}
		if ( ! empty( $class_data['is_one_off_event'] ) ) {
			return 1;
		}
		$n = isset( $class_data['upcoming_dates_count'] ) ? (int) $class_data['upcoming_dates_count'] : 3;
		return max( 1, min( 12, $n ) );
	}

	/**
	 * Read class fields off a clasbpro_class post in a uniform shape.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_class_data( int $class_id ): ?array {
		$post = get_post( $class_id );
		if ( ! $post || 'clasbpro_class' !== $post->post_type ) {
			return null;
		}

		$cancelled_dates = self::read_cancelled_dates( $class_id );

		$start_time = (string) ( function_exists( 'get_field' ) ? get_field( 'start_time', $class_id ) : '' );
		// ACF time field saves as H:i:s — keep first 5 chars.
		if ( $start_time && strlen( $start_time ) > 5 ) {
			$start_time = substr( $start_time, 0, 5 );
		}

		$schedule_type   = function_exists( 'get_field' ) ? (string) get_field( 'schedule_type', $class_id ) : 'recurring';
		$legacy_external = function_exists( 'get_field' ) ? (bool) get_field( 'use_external_link', $class_id ) : false;
		if ( 'external_link' === $schedule_type || $legacy_external ) {
			$schedule_type     = 'external_link';
			$use_external_link = true;
		} else {
			$use_external_link = false;
			if ( ! in_array( $schedule_type, [ 'one_off', 'appointments' ], true ) ) {
				$schedule_type = 'recurring';
			}
		}

		$start_date = function_exists( 'get_field' ) ? self::normalise_date_string( (string) get_field( 'start_date', $class_id ) ) : '';
		$end_date   = function_exists( 'get_field' ) ? self::normalise_date_string( (string) get_field( 'end_date', $class_id ) ) : '';
		if ( 'one_off' === $schedule_type && '' === $end_date ) {
			$end_date = $start_date;
		}

		$cancelled_display = function_exists( 'get_field' ) ? sanitize_key( (string) get_field( 'cancelled_dates_display', $class_id ) ) : 'show';
		if ( 'unbookable' === $cancelled_display ) {
			$cancelled_display = 'hide';
		}
		if ( ! in_array( $cancelled_display, [ 'show', 'hide' ], true ) ) {
			$cancelled_display = 'show';
		}

		$class_image = function_exists( 'get_field' ) ? get_field( 'class_image', $class_id ) : null;
		$image_id    = 0;
		if ( is_numeric( $class_image ) ) {
			$image_id = (int) $class_image;
		} elseif ( is_array( $class_image ) && ! empty( $class_image['ID'] ) ) {
			$image_id = (int) $class_image['ID'];
		}

		$upcoming_raw = function_exists( 'get_field' ) ? get_field( 'upcoming_dates_count', $class_id ) : null;
		$upcoming_n   = ( null !== $upcoming_raw && '' !== $upcoming_raw && false !== $upcoming_raw ) ? (int) $upcoming_raw : 3;
		$upcoming_n   = max( 1, min( 12, $upcoming_n ) );

		$is_appointments = 'appointments' === $schedule_type;
		$calendar_months = function_exists( 'get_field' ) ? (int) get_field( 'calendar_months_ahead', $class_id ) : 3;
		$calendar_months = max( 1, min( 12, $calendar_months ?: 3 ) );
		$lead_hours      = function_exists( 'get_field' ) ? (int) get_field( 'minimum_lead_time_hours', $class_id ) : 0;
		$lead_hours      = max( 0, $lead_hours );
		$slot_rules      = $is_appointments ? Slot_Rules::get_rules( $class_id ) : [];
		$date_selection  = function_exists( 'get_field' ) ? (string) get_field( 'date_selection_mode', $class_id ) : 'dropdown';
		if ( ! in_array( $date_selection, [ 'dropdown', 'calendar' ], true ) ) {
			$date_selection = 'dropdown';
		}
		$uses_date_calendar = 'recurring' === $schedule_type && 'calendar' === $date_selection;

		$class_name = (string) $post->post_title;

		return [
			'id'              => $class_id,
			'name'            => $class_name,
			'description'     => function_exists( 'get_field' ) ? (string) get_field( 'description', $class_id ) : '',
			'image_id'        => $image_id,
			'use_external_link' => $use_external_link,
			'external_link_url' => function_exists( 'get_field' ) ? esc_url_raw( (string) get_field( 'external_link_url', $class_id ) ) : '',
			'location'        => function_exists( 'get_field' ) ? (string) get_field( 'location', $class_id ) : '',
			'schedule_type'   => $schedule_type,
			'is_one_off_event' => 'one_off' === $schedule_type,
			'is_appointments' => $is_appointments,
			'day_of_week'     => function_exists( 'get_field' ) ? (string) get_field( 'day_of_week', $class_id ) : '',
			'start_date'      => $start_date,
			'end_date'        => $end_date,
			'start_time'      => $start_time,
			'duration'        => function_exists( 'get_field' ) ? (int) get_field( 'duration_minutes', $class_id ) : 0,
			'price'           => function_exists( 'get_field' ) ? (float) get_field( 'price_gbp', $class_id ) : 0.0,
			'capacity'        => function_exists( 'get_field' ) ? (int) get_field( 'capacity', $class_id ) : 0,
			'show_seats_remaining' => function_exists( 'get_field' ) ? (bool) get_field( 'show_seats_remaining', $class_id ) : true,
			'upcoming_dates_count' => $upcoming_n,
			'calendar_months_ahead' => $calendar_months,
			'minimum_lead_time_hours' => $lead_hours,
			'slot_rules'      => $slot_rules,
			'has_slot_rules'  => ! empty( $slot_rules ),
			'class_active'    => function_exists( 'get_field' ) ? (bool) get_field( 'class_active', $class_id ) : true,
			'cancelled_dates' => array_values( $cancelled_dates ),
			'cancelled_dates_display' => $cancelled_display,
			'date_selection_mode' => $date_selection,
			'uses_date_calendar'  => $uses_date_calendar,
			'calendar_color'      => function_exists( 'get_field' ) ? (string) get_field( 'calendar_color', $class_id ) : '',
			'calendar_icon'       => function_exists( 'get_field' )
				? self::sanitize_calendar_icon_svg( (string) get_field( 'calendar_icon', $class_id ) )
				: '',
			'calendar_show_image' => function_exists( 'get_field' ) ? (bool) get_field( 'calendar_show_image', $class_id ) : false,
		];
	}

	/**
	 * Read cancelled dates from either:
	 * - Pro repeater field "cancelled_dates"
	 * - Free fallback textarea field "cancelled_dates_fallback"
	 *
	 * @return array<int,string>
	 */
	private static function read_cancelled_dates( int $class_id ): array {
		if ( ! function_exists( 'get_field' ) ) {
			return [];
		}

		$cancelled_dates = [];

		$repeater_value = get_field( 'cancelled_dates', $class_id );
		if ( is_array( $repeater_value ) ) {
			foreach ( $repeater_value as $row ) {
				if ( is_array( $row ) && ! empty( $row['date'] ) ) {
					$cancelled_dates[] = self::normalise_date_string( (string) $row['date'] );
				}
			}
		}

		$fallback_value = get_field( 'cancelled_dates_fallback', $class_id );
		if ( is_string( $fallback_value ) && '' !== trim( $fallback_value ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', $fallback_value ) ?: [];
			foreach ( $lines as $line ) {
				$raw = trim( (string) $line );
				if ( '' === $raw ) {
					continue;
				}

				// Allow optional note after pipe, e.g. 2026-12-24|Holiday.
				$date_part = trim( explode( '|', $raw, 2 )[0] ?? '' );
				if ( '' === $date_part ) {
					continue;
				}

				$cancelled_dates[] = self::normalise_date_string( $date_part );
			}
		}

		$cancelled_dates = array_values( array_unique( array_filter( $cancelled_dates ) ) );
		sort( $cancelled_dates );

		return $cancelled_dates;
	}

	/**
	 * Coerce common date formats into Y-m-d.
	 */
	public static function normalise_date_string( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		// ACF stores as Ymd by default; tolerate other formats.
		if ( preg_match( '/^\d{8}$/', $value ) ) {
			return substr( $value, 0, 4 ) . '-' . substr( $value, 4, 2 ) . '-' . substr( $value, 6, 2 );
		}
		$ts = strtotime( $value );
		if ( false === $ts ) {
			return '';
		}
		return wp_date( 'Y-m-d', $ts );
	}

	/**
	 * @return array{offset: int, days: int, title: string}
	 */
	public static function calendar_month_shell( string $preset_date = '' ): array {
		$tz  = wp_timezone();
		$now = new \DateTimeImmutable( 'now', $tz );
		try {
			$focus = '' !== $preset_date ? new \DateTimeImmutable( $preset_date, $tz ) : $now;
		} catch ( \Exception $e ) {
			$focus = $now;
		}
		$month_start = $focus->modify( 'first day of this month' );
		$cal_start   = (int) $month_start->format( 'w' );

		return [
			'offset' => 0 === $cal_start ? 6 : $cal_start - 1,
			'days'   => (int) $focus->format( 't' ),
			'title'  => wp_date( 'F Y', $focus->getTimestamp() ),
		];
	}

	/**
	 * Sanitise a redirect target so we never bounce off-site.
	 */
	public static function sanitise_internal_url( string $url, string $fallback = '' ): string {
		$url = esc_url_raw( $url );
		if ( '' === $url ) {
			return $fallback;
		}
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );
		if ( $url_host && $home_host && strtolower( $url_host ) !== strtolower( $home_host ) ) {
			return $fallback;
		}
		return $url;
	}

	/**
	 * Write a diagnostic line when WP_DEBUG and WP_DEBUG_LOG are enabled.
	 *
	 * @param string $message Log message.
	 */
	public static function debug_log( string $message ): void {
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only when site owner enables debug logging.
		error_log( $message );
	}

	/**
	 * Public-facing class title (WordPress post title, same filter as booking forms).
	 *
	 * @param array<string, mixed> $class_data
	 */
	public static function class_display_name( array $class_data ): string {
		return (string) apply_filters(
			'clasbpro_booking_title',
			(string) ( $class_data['name'] ?? '' ),
			$class_data
		);
	}

	/**
	 * @return array<int, int>
	 */
	public static function schedule_class_ids(): array {
		return Schedule_Calendar::configured_class_ids();
	}

	/**
	 * @param mixed $raw
	 * @return array<int, int>
	 */
	public static function normalize_schedule_class_ids( $raw ): array {
		if ( ! is_array( $raw ) ) {
			if ( is_numeric( $raw ) ) {
				$raw = [ (int) $raw ];
			} else {
				return [];
			}
		}

		$ids = [];
		foreach ( $raw as $item ) {
			$id = is_object( $item ) ? (int) ( $item->ID ?? 0 ) : (int) $item;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	public static function schedule_weeks_ahead(): int {
		return Schedule_Calendar::weeks_ahead_cap();
	}

	public static function class_calendar_color( int $class_id ): string {
		return Schedule_Calendar::class_calendar_color( $class_id );
	}

	/**
	 * Allowed tags for inline calendar icon SVG markup.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function calendar_icon_allowed_html(): array {
		return [
			'svg'      => [
				'xmlns'           => true,
				'viewbox'         => true,
				'viewBox'         => true,
				'width'           => true,
				'height'          => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'class'           => true,
				'aria-hidden'     => true,
				'role'            => true,
				'focusable'       => true,
			],
			'g'        => [
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'transform'       => true,
				'class'           => true,
			],
			'path'     => [
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'class'           => true,
			],
			'circle'   => [
				'cx'              => true,
				'cy'              => true,
				'r'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'class'           => true,
			],
			'rect'     => [
				'x'               => true,
				'y'               => true,
				'width'           => true,
				'height'          => true,
				'rx'              => true,
				'ry'              => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'class'           => true,
			],
			'line'     => [
				'x1'              => true,
				'y1'              => true,
				'x2'              => true,
				'y2'              => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'class'           => true,
			],
			'polyline' => [
				'points'          => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'class'           => true,
			],
			'polygon'  => [
				'points'          => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linejoin' => true,
				'class'           => true,
			],
		];
	}

	/**
	 * Strip unsafe markup from calendar icon SVG.
	 */
	public static function sanitize_calendar_icon_svg( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		$svg = (string) wp_kses( $raw, self::calendar_icon_allowed_html() );
		if ( '' === $svg || ! preg_match( '/<svg\b/i', $svg ) ) {
			return '';
		}
		return $svg;
	}

	/**
	 * @param array<string, mixed> $class_data
	 */
	public static function class_calendar_icon( array $class_data ): string {
		return self::sanitize_calendar_icon_svg( (string) ( $class_data['calendar_icon'] ?? '' ) );
	}

	/**
	 * @param array<string, mixed> $class_data
	 */
	public static function class_calendar_image_url( array $class_data, string $size = 'medium' ): string {
		if ( empty( $class_data['calendar_show_image'] ) || empty( $class_data['image_id'] ) ) {
			return '';
		}
		$url = wp_get_attachment_image_url( (int) $class_data['image_id'], $size );
		return $url ? esc_url_raw( $url ) : '';
	}
}
