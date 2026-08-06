<?php
/**
 * Global week schedule: events for multiple classes on a time grid.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Schedule_Calendar {

	/** @var string[] */
	private const AUTO_COLORS = [
		'#e8f0fe',
		'#fce8f3',
		'#fdf0e7',
		'#e8f5e9',
		'#ede8ff',
		'#fef6e4',
		'#e4f7f4',
		'#f3e8ff',
	];

	/**
	 * @return array<int, int>
	 */
	public static function configured_class_ids(): array {
		return ACF_Fields::get_saved_schedule_class_ids();
	}

	public static function weeks_ahead_cap(): int {
		$n = function_exists( 'get_field' ) ? (int) get_field( 'schedule_weeks_ahead', Constants::OPTIONS_POST_ID ) : 8;
		return max( 1, min( 52, $n ?: 8 ) );
	}

	/**
	 * @param array<int, int>|null $override
	 * @return array<int, int>
	 */
	public static function resolve_class_ids( ?array $override = null ): array {
		if ( null !== $override && [] !== $override ) {
			return array_values( array_unique( array_filter( array_map( 'absint', $override ) ) ) );
		}
		return self::configured_class_ids();
	}

	public static function monday_of_week( string $date ): string {
		$date = Helpers::normalise_date_string( $date );
		if ( '' === $date ) {
			$date = wp_date( 'Y-m-d' );
		}
		try {
			$dt = new \DateTimeImmutable( $date, wp_timezone() );
		} catch ( \Exception $e ) {
			return wp_date( 'Y-m-d' );
		}
		$iso = (int) $dt->format( 'N' );
		if ( $iso > 1 ) {
			$dt = $dt->modify( '-' . ( $iso - 1 ) . ' days' );
		}
		return $dt->format( 'Y-m-d' );
	}

	/**
	 * @return array{monday: string, sunday: string, days: array<int, string>}
	 */
	public static function week_dates( string $monday ): array {
		$monday = self::monday_of_week( $monday );
		try {
			$start = new \DateTimeImmutable( $monday, wp_timezone() );
		} catch ( \Exception $e ) {
			$monday = wp_date( 'Y-m-d' );
			$start  = new \DateTimeImmutable( $monday, wp_timezone() );
		}
		$days = [];
		for ( $i = 0; $i < 7; $i++ ) {
			$days[] = $start->modify( '+' . $i . ' days' )->format( 'Y-m-d' );
		}
		return [
			'monday' => $days[0],
			'sunday' => $days[6],
			'days'   => $days,
		];
	}

	public static function is_week_in_range( string $monday ): bool {
		try {
			$tz     = wp_timezone();
			$now    = new \DateTimeImmutable( 'now', $tz );
			$cursor = $now->modify( 'monday this week' )->setTime( 0, 0, 0 );
			$end    = $cursor->modify( '+' . self::weeks_ahead_cap() . ' weeks' )->modify( 'sunday this week' );
			$view   = new \DateTimeImmutable( self::monday_of_week( $monday ), $tz );
		} catch ( \Exception $e ) {
			return false;
		}
		return $view >= $cursor && $view <= $end;
	}

	public static function class_calendar_color( int $class_id, ?array $class_data = null ): string {
		if ( is_array( $class_data ) && ! empty( $class_data['calendar_color'] ) ) {
			$custom = sanitize_hex_color( (string) $class_data['calendar_color'] );
			if ( $custom ) {
				return $custom;
			}
		}
		if ( function_exists( 'get_field' ) ) {
			$custom = (string) get_field( 'calendar_color', $class_id );
			$custom = sanitize_hex_color( $custom );
			if ( $custom ) {
				return $custom;
			}
		}
		$index = abs( $class_id ) % count( self::AUTO_COLORS );
		return self::AUTO_COLORS[ $index ];
	}

	/**
	 * @param array<int, int> $class_ids
	 * @return array{week: array{monday: string, sunday: string, days: array<int, string>}, events: array<int, array<string, mixed>>, classes: array<int, array<string, mixed>>, range: array{start_minutes: int, end_minutes: int}}
	 */
	public static function week_payload( array $class_ids, string $week ): array {
		$week_info = self::week_dates( $week );
		$events    = [];
		$classes   = [];

		foreach ( $class_ids as $class_id ) {
			$class_data = Helpers::get_class_data( $class_id );
			if ( ! $class_data || empty( $class_data['class_active'] ) ) {
				continue;
			}
			$classes[ $class_id ] = [
				'id'    => $class_id,
				'name'  => Helpers::class_display_name( $class_data ),
				'color' => self::class_calendar_color( $class_id, $class_data ),
			];
			foreach ( self::events_for_class_week( $class_data, $week_info['days'] ) as $event ) {
				$events[] = $event;
			}
		}

		usort(
			$events,
			static function ( array $a, array $b ): int {
				$cmp = strcmp( (string) $a['date'], (string) $b['date'] );
				if ( 0 !== $cmp ) {
					return $cmp;
				}
				$cmp = strcmp( (string) $a['start_time'], (string) $b['start_time'] );
				if ( 0 !== $cmp ) {
					return $cmp;
				}
				return strcmp( (string) $a['class_name'], (string) $b['class_name'] );
			}
		);

		return [
			'week'    => $week_info,
			'events'  => $events,
			'classes' => array_values( $classes ),
			'range'   => self::time_range_for_events( $events ),
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 * @return array{start_minutes: int, end_minutes: int}
	 */
	private static function time_range_for_events( array $events ): array {
		if ( empty( $events ) ) {
			return [
				'start_minutes' => 8 * 60,
				'end_minutes'   => 18 * 60,
			];
		}
		$min = 24 * 60;
		$max = 0;
		foreach ( $events as $event ) {
			$start = self::time_to_minutes( (string) ( $event['start_time'] ?? '00:00' ) );
			$end   = $start + max( 15, (int) ( $event['duration_minutes'] ?? 45 ) );
			$min   = min( $min, $start );
			$max   = max( $max, $end );
		}
		$pad = 30;
		return [
			'start_minutes' => max( 0, (int) ( floor( $min / 60 ) * 60 ) - $pad ),
			'end_minutes'   => min( 24 * 60, (int) ( ceil( $max / 60 ) * 60 ) + $pad ),
		];
	}

	private static function time_to_minutes( string $hhmm ): int {
		$parts = explode( ':', $hhmm );
		if ( count( $parts ) < 2 ) {
			return 0;
		}
		return max( 0, (int) $parts[0] ) * 60 + max( 0, (int) $parts[1] );
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<int, string>   $week_days
	 * @return array<int, array<string, mixed>>
	 */
	private static function events_for_class_week( array $class_data, array $week_days ): array {
		if ( ! empty( $class_data['use_external_link'] ) || 'external_link' === ( $class_data['schedule_type'] ?? '' ) ) {
			return self::external_events_for_week( $class_data, $week_days );
		}
		if ( ! empty( $class_data['is_appointments'] ) ) {
			return self::appointment_events_for_week( $class_data, $week_days );
		}
		if ( ! empty( $class_data['is_one_off_event'] ) ) {
			return self::one_off_events_for_week( $class_data, $week_days );
		}
		return self::recurring_events_for_week( $class_data, $week_days );
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<int, string>   $week_days
	 * @return array<int, array<string, mixed>>
	 */
	private static function recurring_events_for_week( array $class_data, array $week_days ): array {
		$weekday = strtolower( (string) ( $class_data['day_of_week'] ?? '' ) );
		if ( '' === $weekday || empty( $class_data['start_time'] ) ) {
			return [];
		}

		$events = [];
		foreach ( $week_days as $date ) {
			try {
				$dt = new \DateTimeImmutable( $date, wp_timezone() );
			} catch ( \Exception $e ) {
				continue;
			}
			if ( strtolower( $dt->format( 'l' ) ) !== $weekday ) {
				continue;
			}
			if ( ! Helpers::date_in_class_run_window( $class_data, $date ) ) {
				continue;
			}
			$event = self::build_standard_event( $class_data, $date );
			if ( $event ) {
				$events[] = $event;
			}
		}
		return $events;
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<int, string>   $week_days
	 * @return array<int, array<string, mixed>>
	 */
	private static function one_off_events_for_week( array $class_data, array $week_days ): array {
		$start = Helpers::normalise_date_string( (string) ( $class_data['start_date'] ?? '' ) );
		$end   = Helpers::normalise_date_string( (string) ( $class_data['end_date'] ?? '' ) );
		if ( '' === $start ) {
			return [];
		}
		if ( '' === $end ) {
			$end = $start;
		}

		$events = [];
		foreach ( $week_days as $date ) {
			if ( $date < $start || $date > $end ) {
				continue;
			}
			$event = self::build_standard_event( $class_data, $date );
			if ( $event ) {
				$events[] = $event;
			}
		}
		return $events;
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<int, string>   $week_days
	 * @return array<int, array<string, mixed>>
	 */
	private static function appointment_events_for_week( array $class_data, array $week_days ): array {
		$events = [];
		foreach ( $week_days as $date ) {
			foreach ( Slot_Rules::slots_for_date( $class_data, $date ) as $slot ) {
				$start_time = (string) ( $slot['start_time'] ?? '' );
				if ( '' === $start_time ) {
					continue;
				}
				$cancelled  = false;
				$selectable = ! empty( $slot['selectable'] );
				$full       = ! $selectable && ! $cancelled;
				$capacity   = max( 1, (int) ( $slot['capacity'] ?? 1 ) );
				$remaining  = $selectable ? $capacity : 0;
				$events[]   = self::compose_event(
					$class_data,
					$date,
					$start_time,
					(int) ( $slot['duration_minutes'] ?? (int) ( $class_data['duration'] ?? 45 ) ),
					[
						'selectable'    => $selectable,
						'cancelled'     => $cancelled,
						'full'          => $full,
						'remaining'     => $remaining,
						'capacity'      => $capacity,
						'slot_rule_id'  => (string) ( $slot['rule_id'] ?? '' ),
						'slot_label'    => (string) ( $slot['label'] ?? '' ),
						'location'      => (string) ( $slot['location'] ?? '' ),
						'external'      => false,
						'external_url'  => '',
					]
				);
			}
		}
		return $events;
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<int, string>   $week_days
	 * @return array<int, array<string, mixed>>
	 */
	private static function external_events_for_week( array $class_data, array $week_days ): array {
		$url = esc_url_raw( (string) ( $class_data['external_link_url'] ?? '' ) );
		if ( '' === $url ) {
			return [];
		}

		if ( ! empty( $class_data['is_one_off_event'] ) ) {
			$raw = self::one_off_events_for_week( $class_data, $week_days );
		} elseif ( ! empty( $class_data['is_appointments'] ) && ! empty( $class_data['slot_rules'] ) ) {
			$raw = self::appointment_events_for_week( $class_data, $week_days );
			foreach ( $raw as &$event ) {
				$event['external']     = true;
				$event['external_url'] = $url;
				$event['selectable']    = true;
				$event['full']          = false;
			}
			unset( $event );
			return $raw;
		} else {
			$raw = self::recurring_events_for_week( $class_data, $week_days );
		}

		$events = [];
		foreach ( $raw as $event ) {
			$event['external']     = true;
			$event['external_url'] = $url;
			$event['selectable']    = true;
			$event['full']          = false;
			$events[]              = $event;
		}
		return $events;
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @return array<string, mixed>|null
	 */
	private static function build_standard_event( array $class_data, string $date ): ?array {
		if ( empty( $class_data['start_time'] ) ) {
			return null;
		}
		try {
			$tz          = wp_timezone();
			$now         = new \DateTimeImmutable( 'now', $tz );
			$dt          = new \DateTimeImmutable( $date, $tz );
			$class_start = $dt->modify( (string) $class_data['start_time'] );
		} catch ( \Exception $e ) {
			return null;
		}
		if ( ! $class_start || $class_start <= $now ) {
			return null;
		}

		$cancelled = in_array( $date, (array) ( $class_data['cancelled_dates'] ?? [] ), true );
		$mode      = Helpers::cancelled_dates_display( $class_data );
		if ( $cancelled && 'show' !== $mode ) {
			// hide: no schedule card (empty day / normal unavailable).
			return null;
		}
		$remaining  = $cancelled ? 0 : Bookings::seats_remaining( $class_data, $date );
		$capacity   = max( 1, (int) ( $class_data['capacity'] ?? 1 ) );
		$selectable = ! $cancelled && $remaining > 0;
		$full       = ! $cancelled && $remaining <= 0;

		return self::compose_event(
			$class_data,
			$date,
			(string) $class_data['start_time'],
			max( 1, (int) ( $class_data['duration'] ?? 45 ) ),
			[
				'selectable'   => $selectable,
				'cancelled'    => $cancelled,
				'full'         => $full,
				'remaining'    => $remaining,
				'capacity'     => $capacity,
				'slot_rule_id' => '',
				'slot_label'   => '',
				'external'     => false,
				'external_url' => '',
			]
		);
	}

	/**
	 * @param array<string, mixed> $class_data
	 * @param array<string, mixed> $state
	 * @return array<string, mixed>
	 */
	private static function compose_event( array $class_data, string $date, string $start_time, int $duration, array $state ): array {
		$class_id   = (int) ( $class_data['id'] ?? 0 );
		$show_seats = ! empty( $class_data['show_seats_remaining'] ) && empty( $class_data['is_appointments'] );
		$name       = Helpers::class_display_name( $class_data );
		$slot_label = (string) ( $state['slot_label'] ?? '' );
		$location   = (string) ( $state['location'] ?? '' );
		if ( '' === $location ) {
			$location = (string) ( $class_data['location'] ?? '' );
		}

		return [
			'id'               => $class_id . '-' . $date . '-' . $start_time . '-' . (string) ( $state['slot_rule_id'] ?? '' ),
			'class_id'         => $class_id,
			'class_name'       => $name,
			'title'            => $name,
			'label'            => $slot_label,
			'location'         => $location,
			'date'             => $date,
			'start_time'       => substr( $start_time, 0, 5 ),
			'duration_minutes' => $duration,
			'selectable'       => (bool) ( $state['selectable'] ?? false ),
			'cancelled'        => (bool) ( $state['cancelled'] ?? false ),
			'full'             => (bool) ( $state['full'] ?? false ),
			'external'         => (bool) ( $state['external'] ?? false ),
			'external_url'     => (string) ( $state['external_url'] ?? '' ),
			'show_seats'       => $show_seats,
			'remaining'        => (int) ( $state['remaining'] ?? 0 ),
			'capacity'         => (int) ( $state['capacity'] ?? 0 ),
			'slot_rule_id'     => (string) ( $state['slot_rule_id'] ?? '' ),
			'color'            => self::class_calendar_color( $class_id, $class_data ),
			'icon'             => Helpers::class_calendar_icon( $class_data ),
			'show_image'       => ! empty( $class_data['calendar_show_image'] ),
			'image_url'        => Helpers::class_calendar_image_url( $class_data ),
			'is_appointments'  => ! empty( $class_data['is_appointments'] ),
		];
	}
}
