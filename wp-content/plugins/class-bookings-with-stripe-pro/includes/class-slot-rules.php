<?php
/**
 * Appointment slot rules: storage, occurrence generation, availability.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Slot_Rules {

	public const META_KEY = '_clasbpro_slot_rules';

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_rules( int $class_id ): array {
		$raw = get_post_meta( $class_id, self::META_KEY, true );
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$rules = [];
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$sanitized = self::sanitize_rule( $row );
			if ( null !== $sanitized ) {
				$rules[] = $sanitized;
			}
		}

		return $rules;
	}

	/**
	 * @param array<int, array<string, mixed>> $rules
	 */
	public static function save_rules( int $class_id, array $rules ): void {
		$clean = [];
		foreach ( $rules as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$sanitized = self::sanitize_rule( $row );
			if ( null !== $sanitized ) {
				$clean[] = $sanitized;
			}
		}
		update_post_meta( $class_id, self::META_KEY, $clean );
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>|null
	 */
	public static function sanitize_rule( array $row ): ?array {
		$id = isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '';
		if ( '' === $id ) {
			$id = self::generate_rule_id();
		}

		$type = isset( $row['type'] ) ? (string) $row['type'] : 'recurring';
		if ( ! in_array( $type, [ 'recurring', 'one_off' ], true ) ) {
			$type = 'recurring';
		}

		$start_time = self::normalise_time( (string) ( $row['start_time'] ?? '' ) );
		if ( '' === $start_time ) {
			return null;
		}

		$duration = max( 1, (int) ( $row['duration_minutes'] ?? 60 ) );
		$location = sanitize_text_field( (string) ( $row['location'] ?? '' ) );
		$label    = sanitize_text_field( (string) ( $row['label'] ?? '' ) );

		$price_raw = $row['price_gbp'] ?? '';
		$price_gbp = ( '' === $price_raw || null === $price_raw ) ? null : max( 0, (float) $price_raw );

		$skip_dates = self::parse_skip_dates( (string) ( $row['skip_dates'] ?? '' ) );

		$rule = [
			'id'               => $id,
			'type'             => $type,
			'start_time'       => $start_time,
			'duration_minutes' => $duration,
			'location'         => $location,
			'label'            => $label,
			'price_gbp'        => $price_gbp,
			'skip_dates'       => $skip_dates,
		];

		if ( 'one_off' === $type ) {
			$specific = Helpers::normalise_date_string( (string) ( $row['specific_date'] ?? '' ) );
			if ( '' === $specific ) {
				return null;
			}
			$rule['specific_date'] = $specific;
		} else {
			$day  = strtolower( sanitize_text_field( (string) ( $row['day_of_week'] ?? '' ) ) );
			$days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
			if ( ! in_array( $day, $days, true ) ) {
				return null;
			}
			$rule['day_of_week'] = $day;

			$rec_start = Helpers::normalise_date_string( (string) ( $row['recurring_start'] ?? '' ) );
			$rec_end   = Helpers::normalise_date_string( (string) ( $row['recurring_end'] ?? '' ) );
			if ( '' !== $rec_start ) {
				$rule['recurring_start'] = $rec_start;
			}
			if ( '' !== $rec_end ) {
				$rule['recurring_end'] = $rec_end;
			}
		}

		return $rule;
	}

	public static function generate_rule_id(): string {
		return 'rule_' . wp_generate_password( 10, false, false );
	}

	/**
	 * @return array<int, string>
	 */
	public static function parse_skip_dates( string $text ): array {
		if ( '' === trim( $text ) ) {
			return [];
		}
		$dates = [];
		$lines = preg_split( '/\r\n|\r|\n/', $text ) ?: [];
		foreach ( $lines as $line ) {
			$raw = trim( (string) $line );
			if ( '' === $raw ) {
				continue;
			}
			$date_part = trim( explode( '|', $raw, 2 )[0] ?? '' );
			$norm      = Helpers::normalise_date_string( $date_part );
			if ( '' !== $norm ) {
				$dates[] = $norm;
			}
		}
		return array_values( array_unique( $dates ) );
	}

	public static function normalise_time( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^(\d{1,2}):(\d{2})/', $value, $m ) ) {
			return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, mixed> $rule
	 */
	public static function rule_price_gbp( array $class_data, array $rule ): float {
		if ( isset( $rule['price_gbp'] ) && null !== $rule['price_gbp'] && '' !== $rule['price_gbp'] ) {
			return (float) $rule['price_gbp'];
		}
		return (float) ( $class_data['price'] ?? 0 );
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, mixed> $rule
	 * @return array<string, mixed>
	 */
	public static function build_snapshot( array $class_data, array $rule, string $date ): array {
		return [
			'date'             => $date,
			'start_time'       => (string) ( $rule['start_time'] ?? '' ),
			'duration_minutes' => (int) ( $rule['duration_minutes'] ?? 0 ),
			'location'         => (string) ( $rule['location'] ?? '' ),
			'label'            => (string) ( $rule['label'] ?? '' ),
			'price_gbp'        => self::rule_price_gbp( $class_data, $rule ),
			'rule_id'          => (string) ( $rule['id'] ?? '' ),
		];
	}

	/**
	 * @param array<string, mixed> $class_data
	 */
	public static function find_rule( array $class_data, string $rule_id ): ?array {
		foreach ( (array) ( $class_data['slot_rules'] ?? [] ) as $rule ) {
			if ( is_array( $rule ) && (string) ( $rule['id'] ?? '' ) === $rule_id ) {
				return $rule;
			}
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $class_data
	 */
	public static function calendar_window_end( array $class_data ): \DateTimeImmutable {
		$months = max( 1, min( 12, (int) ( $class_data['calendar_months_ahead'] ?? 3 ) ) );
		try {
			$tz  = wp_timezone();
			$now = new \DateTimeImmutable( 'now', $tz );
			return $now->modify( '+' . $months . ' months' )->setTime( 23, 59, 59 );
		} catch ( \Exception $e ) {
			return new \DateTimeImmutable( '+3 months', wp_timezone() );
		}
	}

	/**
	 * @param array<string, mixed> $class_data
	 */
	public static function slot_datetime( array $class_data, array $rule, string $date ): ?\DateTimeImmutable {
		try {
			$tz = wp_timezone();
			$dt = new \DateTimeImmutable( $date . ' ' . ( $rule['start_time'] ?? '00:00' ), $tz );
			return $dt;
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, mixed> $rule
	 */
	public static function is_occurrence_in_past_or_inside_lead( array $class_data, array $rule, string $date ): bool {
		$slot_start = self::slot_datetime( $class_data, $rule, $date );
		if ( ! $slot_start ) {
			return true;
		}
		try {
			$now      = new \DateTimeImmutable( 'now', wp_timezone() );
			$lead_hrs = max( 0, (int) ( $class_data['minimum_lead_time_hours'] ?? 0 ) );
			$earliest = $now->modify( '+' . $lead_hrs . ' hours' );
			return $slot_start <= $earliest;
		} catch ( \Exception $e ) {
			return true;
		}
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, mixed> $rule
	 */
	public static function is_date_cancelled_for_rule( array $class_data, array $rule, string $date ): bool {
		if ( in_array( $date, (array) ( $class_data['cancelled_dates'] ?? [] ), true ) ) {
			return true;
		}
		return in_array( $date, (array) ( $rule['skip_dates'] ?? [] ), true );
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, mixed> $rule
	 */
	public static function is_date_in_rule_range( array $class_data, array $rule, string $date ): bool {
		try {
			$tz = wp_timezone();
			$dt = new \DateTimeImmutable( $date, $tz );
		} catch ( \Exception $e ) {
			return false;
		}

		$window_end = self::calendar_window_end( $class_data );
		$now        = new \DateTimeImmutable( 'now', $tz );
		$today      = $now->setTime( 0, 0, 0 );

		if ( $dt < $today || $dt > $window_end ) {
			return false;
		}

		if ( 'one_off' === ( $rule['type'] ?? '' ) ) {
			return $date === (string) ( $rule['specific_date'] ?? '' );
		}

		$weekday = strtolower( $dt->format( 'l' ) );
		if ( $weekday !== (string) ( $rule['day_of_week'] ?? '' ) ) {
			return false;
		}

		if ( ! empty( $rule['recurring_start'] ) ) {
			$start = new \DateTimeImmutable( (string) $rule['recurring_start'], $tz );
			if ( $dt < $start ) {
				return false;
			}
		}
		if ( ! empty( $rule['recurring_end'] ) ) {
			$end = new \DateTimeImmutable( (string) $rule['recurring_end'], $tz );
			if ( $dt > $end ) {
				return false;
			}
		}

		return true;
	}

	public static function slot_is_booked( int $class_id, string $rule_id, string $date ): bool {
		return Bookings::appointment_slot_taken( $class_id, $rule_id, $date );
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @return array<string, array{has_any: bool, has_available: bool, slot_count: int, available_count: int}>
	 */
	public static function month_availability( array $class_data, int $year, int $month ): array {
		$days = [];
		if ( empty( $class_data['slot_rules'] ) ) {
			return $days;
		}

		try {
			$tz    = wp_timezone();
			$start = new \DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), $tz );
			$end   = $start->modify( 'last day of this month' );
		} catch ( \Exception $e ) {
			return $days;
		}

		$walk = $start;
		$class_id     = (int) ( $class_data['id'] ?? 0 );
		$taken_lookup = Bookings::appointment_taken_slots_lookup(
			$class_id,
			$start->format( 'Y-m-d' ),
			$end->format( 'Y-m-d' )
		);
		while ( $walk <= $end ) {
			$date   = $walk->format( 'Y-m-d' );
			$slots         = self::slots_for_date( $class_data, $date, $taken_lookup );
			$slot_count    = count( $slots );
			$avail_count   = 0;
			foreach ( $slots as $slot ) {
				if ( ! empty( $slot['selectable'] ) ) {
					++$avail_count;
				}
			}
			if ( $slot_count > 0 ) {
				$days[ $date ] = [
					'has_any'          => true,
					'has_available'    => $avail_count > 0,
					'slot_count'       => $slot_count,
					'available_count'  => $avail_count,
				];
			}
			$walk = $walk->modify( '+1 day' );
		}

		return $days;
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, true>|null $taken_lookup Optional preloaded taken slots for month views.
	 * @return array<int, array<string, mixed>>
	 */
	public static function slots_for_date( array $class_data, string $date, ?array $taken_lookup = null ): array {
		$date = Helpers::normalise_date_string( $date );
		if ( '' === $date || empty( $class_data['slot_rules'] ) ) {
			return [];
		}

		$class_id      = (int) ( $class_data['id'] ?? 0 );
		$default_price = (float) ( $class_data['price'] ?? 0 );
		$slots         = [];

		foreach ( (array) $class_data['slot_rules'] as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			if ( ! self::is_date_in_rule_range( $class_data, $rule, $date ) ) {
				continue;
			}
			if ( self::is_date_cancelled_for_rule( $class_data, $rule, $date ) ) {
				continue;
			}
			if ( self::is_occurrence_in_past_or_inside_lead( $class_data, $rule, $date ) ) {
				continue;
			}

			$rule_id = (string) ( $rule['id'] ?? '' );
			$booked  = null !== $taken_lookup
				? ! empty( $taken_lookup[ $date . '|' . $rule_id ] )
				: self::slot_is_booked( $class_id, $rule_id, $date );
			$price   = self::rule_price_gbp( $class_data, $rule );

			$slot = [
				'rule_id'          => $rule_id,
				'date'             => $date,
				'start_time'       => (string) ( $rule['start_time'] ?? '' ),
				'time_label'       => Helpers::format_time( (string) ( $rule['start_time'] ?? '' ) ),
				'location'         => (string) ( $rule['location'] ?? '' ),
				'duration_minutes' => (int) ( $rule['duration_minutes'] ?? 0 ),
				'label'            => (string) ( $rule['label'] ?? '' ),
				'price_gbp'        => $price,
				'show_price'       => abs( $price - $default_price ) > 0.001,
				'price_label'      => Helpers::format_price( $price ),
				'status'           => $booked ? 'booked' : 'available',
				'selectable'       => ! $booked,
				'capacity'         => max( 1, (int) ( $class_data['capacity'] ?? 1 ) ),
			];

			$slots[] = $slot;
		}

		usort(
			$slots,
			static function ( array $a, array $b ): int {
				$ta = (string) ( $a['start_time'] ?? '' );
				$tb = (string) ( $b['start_time'] ?? '' );
				if ( $ta === $tb ) {
					return strcmp( (string) ( $a['location'] ?? '' ), (string) ( $b['location'] ?? '' ) );
				}
				return strcmp( $ta, $tb );
			}
		);

		return $slots;
	}

	/**
	 * @param array<string, mixed> $class_data
	 */
	public static function has_any_bookable_slot( array $class_data ): bool {
		if ( empty( $class_data['slot_rules'] ) ) {
			return false;
		}

		try {
			$tz    = wp_timezone();
			$start = new \DateTimeImmutable( 'today', $tz );
			$end   = self::calendar_window_end( $class_data );
		} catch ( \Exception $e ) {
			return false;
		}

		$walk = $start;
		while ( $walk <= $end ) {
			$date = $walk->format( 'Y-m-d' );
			foreach ( self::slots_for_date( $class_data, $date ) as $slot ) {
				if ( ! empty( $slot['selectable'] ) ) {
					return true;
				}
			}
			$walk = $walk->modify( '+1 day' );
		}

		return false;
	}

	/**
	 * Validate slot for checkout.
	 *
	 * @param array<string, mixed> $class_data
	 * @return string Error code or '' if valid.
	 */
	public static function validate_slot( array $class_data, string $rule_id, string $date ): string {
		if ( empty( $class_data['class_active'] ) ) {
			return 'class_inactive';
		}
		if ( '' === $rule_id || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return 'date_invalid';
		}

		$rule = self::find_rule( $class_data, $rule_id );
		if ( ! $rule ) {
			return 'date_invalid';
		}
		if ( ! self::is_date_in_rule_range( $class_data, $rule, $date ) ) {
			return 'date_invalid';
		}
		if ( self::is_date_cancelled_for_rule( $class_data, $rule, $date ) ) {
			return 'date_invalid';
		}
		if ( self::is_occurrence_in_past_or_inside_lead( $class_data, $rule, $date ) ) {
			return 'date_invalid';
		}
		if ( self::slot_is_booked( (int) $class_data['id'], $rule_id, $date ) ) {
			return 'capacity_full';
		}

		return '';
	}
}
