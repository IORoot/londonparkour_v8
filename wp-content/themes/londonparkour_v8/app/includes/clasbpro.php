<?php
/**
 * Theme adapter over clasbpro class data.
 *
 * Blocks and templates call these helpers — never clasbpro namespaces directly.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether clasbpro Helpers are available.
 */
function lp_clasbpro_ready(): bool {
	return class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Helpers' )
		&& class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Bookings' );
}

/**
 * Raw clasbpro class_data array, or null.
 *
 * @param int $class_id Post ID.
 * @return array<string,mixed>|null
 */
function lp_clasbpro_raw( int $class_id ): ?array {
	if ( ! lp_clasbpro_ready() || $class_id <= 0 ) {
		return null;
	}
	return \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::get_class_data( $class_id );
}

/**
 * Whether a class is active for front-end listings/boards.
 *
 * @param int $class_id Post ID.
 */
function lp_class_is_active( int $class_id ): bool {
	$raw = lp_clasbpro_raw( $class_id );
	if ( null === $raw ) {
		// Plugin missing — fall back to published post existence.
		return 'publish' === get_post_status( $class_id );
	}
	return ! empty( $raw['class_active'] );
}

/**
 * Theme image for a class: clasbpro class_image, then featured image.
 *
 * @param int $class_id Post ID.
 */
function lp_class_image_id( int $class_id ): int {
	if ( function_exists( 'get_field' ) ) {
		$image = get_field( 'class_image', $class_id );
		if ( is_numeric( $image ) ) {
			return (int) $image;
		}
		if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
			return (int) $image['ID'];
		}
	}
	return (int) get_post_thumbnail_id( $class_id );
}

/**
 * Duration label from clasbpro duration_minutes, e.g. "60 min".
 *
 * @param int $class_id Post ID.
 */
function lp_class_duration( int $class_id ): string {
	$raw = lp_clasbpro_raw( $class_id );
	$mins = $raw ? (int) ( $raw['duration'] ?? 0 ) : 0;
	if ( $mins <= 0 && function_exists( 'get_field' ) ) {
		$mins = (int) get_field( 'duration_minutes', $class_id );
	}
	return $mins > 0 ? sprintf( '%d min', $mins ) : '';
}

/**
 * Trailing note from acf_subtitle (no duration baked in).
 *
 * @param int $class_id Post ID.
 */
function lp_class_subtitle_note( int $class_id ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	return trim( (string) get_field( 'acf_subtitle', $class_id ) );
}

/**
 * Composed subtitle: "60 min · all kit provided".
 *
 * @param int $class_id Post ID.
 */
function lp_class_composed_subtitle( int $class_id ): string {
	$duration = lp_class_duration( $class_id );
	$note     = lp_class_subtitle_note( $class_id );
	if ( '' !== $duration && '' !== $note ) {
		return $duration . ' · ' . $note;
	}
	return $duration !== '' ? $duration : $note;
}

/**
 * Format price_gbp as a display string, e.g. "£15".
 *
 * @param int $class_id Post ID.
 */
function lp_class_price_display( int $class_id ): string {
	$raw   = lp_clasbpro_raw( $class_id );
	$price = $raw ? (float) ( $raw['price'] ?? 0 ) : 0.0;
	if ( $price <= 0 && function_exists( 'get_field' ) ) {
		$price = (float) get_field( 'price_gbp', $class_id );
	}
	if ( $price <= 0 ) {
		return '';
	}
	$formatted = ( floor( $price ) === $price )
		? (string) (int) $price
		: number_format( $price, 2, '.', '' );
	return '£' . $formatted;
}

/**
 * Default price label for fact rails / board rows.
 */
function lp_class_price_label(): string {
	return 'DROP-IN';
}

/**
 * lp_location post ID from acf_location.
 *
 * @param int $class_id Post ID.
 */
function lp_class_location_id( int $class_id ): int {
	if ( ! function_exists( 'get_field' ) ) {
		return 0;
	}
	return (int) get_field( 'acf_location', $class_id );
}

/**
 * Coach post IDs from acf_coaches.
 *
 * @param int $class_id Post ID.
 * @return int[]
 */
function lp_class_coach_ids( int $class_id ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}
	$raw = get_field( 'acf_coaches', $class_id );
	return array_values( array_filter( array_map( 'intval', (array) $raw ) ) );
}

/**
 * Featured flag.
 *
 * @param int $class_id Post ID.
 */
function lp_class_is_featured( int $class_id ): bool {
	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}
	return (bool) get_field( 'acf_is_featured', $class_id );
}

/**
 * Spaces label from remaining seats.
 *
 * @param int $remaining Seats left.
 * @param int $capacity  Total capacity.
 */
function lp_class_spaces_label( int $remaining, int $capacity = 0 ): string {
	if ( $remaining <= 0 ) {
		return 'FULL';
	}
	if ( 1 === $remaining ) {
		return '1 LEFT';
	}
	return sprintf( '%d LEFT', $remaining );
}

/**
 * Board date_label for a Y-m-d date.
 *
 * @param string $date Y-m-d.
 */
function lp_class_date_label( string $date ): string {
	$today = current_datetime()->format( 'Y-m-d' );
	if ( $date === $today ) {
		return 'TODAY';
	}
	$dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
	return $dt ? strtoupper( $dt->format( 'D' ) ) : '';
}

/**
 * Upcoming session rows for one class (includes sold-out dates).
 *
 * Shape matches the old sessions repeater projection so boards stay stable:
 * date, date_label, time, spaces, sold_out, remaining, capacity.
 *
 * @param int $class_id Post ID.
 * @param int $limit    Max occurrences.
 * @return array<int,array<string,mixed>>
 */
function lp_class_upcoming_sessions( int $class_id, int $limit = 3 ): array {
	if ( ! lp_class_is_active( $class_id ) ) {
		return array();
	}

	$raw = lp_clasbpro_raw( $class_id );
	if ( ! $raw || empty( $raw['start_time'] ) ) {
		return array();
	}

	$limit    = max( 1, $limit );
	$capacity = max( 0, (int) ( $raw['capacity'] ?? 0 ) );
	$time     = (string) $raw['start_time'];
	if ( strlen( $time ) > 5 ) {
		$time = substr( $time, 0, 5 );
	}

	$dates = array();
	if ( lp_clasbpro_ready() ) {
		$weekday = strtolower( (string) ( $raw['day_of_week'] ?? '' ) );
		$from    = (string) ( $raw['start_date'] ?? '' );
		$to      = (string) ( $raw['end_date'] ?? '' );
		// Pull extra so cancelled skips still leave enough rows.
		$pool = ! empty( $raw['is_one_off_event'] )
			? \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::date_range_occurrences(
				(string) ( $raw['start_date'] ?? '' ),
				(string) ( $raw['end_date'] ?? '' ),
				$time,
				$limit * 4,
				array()
			)
			: \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::next_weekday_occurrences(
				$weekday,
				$time,
				$limit * 4,
				array(),
				$from,
				$to
			);
		$cancelled = (array) ( $raw['cancelled_dates'] ?? array() );
		foreach ( $pool as $date ) {
			if ( in_array( $date, $cancelled, true ) ) {
				continue;
			}
			$dates[] = $date;
			if ( count( $dates ) >= $limit ) {
				break;
			}
		}
	}

	$rows = array();
	foreach ( $dates as $date ) {
		$remaining = lp_clasbpro_ready()
			? \IOROOT_STRIPE_BOOKINGS_PRO\Bookings::seats_remaining( $raw, $date )
			: $capacity;
		$sold_out = $remaining <= 0;
		$rows[]   = array(
			'date'       => $date,
			'date_label' => lp_class_date_label( $date ),
			'time'       => $time,
			'spaces'     => lp_class_spaces_label( $remaining, $capacity ),
			'sold_out'   => $sold_out,
			'remaining'  => $remaining,
			'capacity'   => $capacity,
			'book_label' => $sold_out ? 'WAITLIST' : 'BOOK',
		);
	}

	return $rows;
}

/**
 * Session rows whose date falls in [start, end] inclusive (Agenda).
 *
 * @param DateTimeImmutable $start Week start (Monday).
 * @param DateTimeImmutable $end   Week end (Sunday).
 * @return array<int,array<string,mixed>> Flat session items with class fields merged.
 */
function lp_class_sessions_between( DateTimeImmutable $start, DateTimeImmutable $end ): array {
	$class_ids = get_posts(
		array(
			'post_type'              => lp_class_post_type(),
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
		)
	);

	$start_s = $start->format( 'Y-m-d' );
	$end_s   = $end->format( 'Y-m-d' );
	$rows    = array();

	foreach ( $class_ids as $class_id ) {
		$class_id = (int) $class_id;
		if ( ! lp_class_is_active( $class_id ) ) {
			continue;
		}

		// Enough horizon to cover a far week-offset agenda view.
		foreach ( lp_class_upcoming_sessions( $class_id, 16 ) as $session ) {
			$date = (string) ( $session['date'] ?? '' );
			if ( $date < $start_s || $date > $end_s ) {
				continue;
			}
			$rows[] = array_merge(
				lp_class_board_fields( $class_id ),
				$session
			);
		}
	}

	usort(
		$rows,
		static function ( array $a, array $b ): int {
			return strcmp(
				( $a['date'] ?? '' ) . ( $a['time'] ?? '' ),
				( $b['date'] ?? '' ) . ( $b['time'] ?? '' )
			);
		}
	);

	return $rows;
}

/**
 * Flat fields a board row needs from a class post.
 *
 * @param int $class_id Post ID.
 * @return array<string,mixed>
 */
function lp_class_board_fields( int $class_id ): array {
	$location_id = lp_class_location_id( $class_id );
	$levels      = get_the_terms( $class_id, 'lp_level' );
	$level       = ( is_array( $levels ) && $levels ) ? $levels[0]->name : '';

	return array(
		'id'          => $class_id,
		'title'       => get_the_title( $class_id ),
		'url'         => (string) get_permalink( $class_id ),
		'thumb'       => lp_class_image_id( $class_id ) ?: null,
		'subtitle'    => lp_class_composed_subtitle( $class_id ),
		'location'    => $location_id ? get_the_title( $location_id ) : '',
		'location_id' => $location_id,
		'level'       => $level,
		'price'       => lp_class_price_display( $class_id ),
		'price_label' => lp_class_price_label(),
		'coaches'     => implode(
			', ',
			array_map( 'get_the_title', lp_class_coach_ids( $class_id ) )
		),
	);
}

/**
 * Args for elements/button.php that open the shared booking drawer.
 *
 * @param int    $class_id    Post ID.
 * @param string $preset_date Optional Y-m-d.
 * @param string $label       Button label.
 * @param string $variant     Button variant.
 * @return array<string,mixed>
 */
function lp_class_book_button_args( int $class_id, string $preset_date = '', string $label = 'BOOK', string $variant = 'primary' ): array {
	$attrs = array(
		'data-lp-book'  => '1',
		'data-class-id' => (string) $class_id,
	);
	if ( '' !== $preset_date ) {
		$attrs['data-preset-date'] = $preset_date;
	}

	return array(
		'variant'     => $variant,
		'label'       => $label,
		'command'     => 'show-modal',
		'command_for' => 'lp-booking-drawer',
		'data_attrs'  => $attrs,
	);
}

/**
 * Deduplicate class posts by title (Listings). Keeps the soonest upcoming slot.
 *
 * @param WP_Post[] $posts Class posts.
 * @return WP_Post[]
 */
function lp_class_dedupe_by_title( array $posts ): array {
	$best = array();

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$title = $post->post_title;
		$sessions = lp_class_upcoming_sessions( (int) $post->ID, 1 );
		$next     = $sessions[0]['date'] ?? '9999-99-99';

		if ( ! isset( $best[ $title ] ) ) {
			$best[ $title ] = array(
				'post' => $post,
				'next' => $next,
			);
			continue;
		}
		if ( $next < $best[ $title ]['next'] ) {
			$best[ $title ] = array(
				'post' => $post,
				'next' => $next,
			);
		}
	}

	return array_values(
		array_map(
			static fn( array $row ): WP_Post => $row['post'],
			$best
		)
	);
}

/**
 * Query args that exclude inactive clasbpro classes.
 *
 * @param array $args WP_Query / get_posts args.
 * @return array
 */
function lp_class_active_meta_query( array $args = array() ): array {
	$meta = array(
		'relation' => 'OR',
		array(
			'key'     => 'class_active',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'class_active',
			'value'   => '0',
			'compare' => '!=',
		),
	);

	if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'AND',
			$args['meta_query'],
			$meta,
		);
	} else {
		$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	return $args;
}

/**
 * True when a session's date+time is still in the future (site timezone).
 *
 * @param array $session Row with date (Y-m-d) and time (H:i).
 */
function lp_class_session_is_future( array $session ): bool {
	$date = (string) ( $session['date'] ?? '' );
	$time = (string) ( $session['time'] ?? '' );
	if ( '' === $date || '' === $time ) {
		return false;
	}
	if ( strlen( $time ) > 5 ) {
		$time = substr( $time, 0, 5 );
	}
	$start = DateTimeImmutable::createFromFormat(
		'Y-m-d H:i',
		$date . ' ' . $time,
		wp_timezone()
	);
	if ( ! $start ) {
		return false;
	}
	return $start > current_datetime();
}

/**
 * The chronologically next upcoming class session across all active classes.
 *
 * Once a class's start time has passed, it drops out and the following session
 * becomes next. Returns board fields merged with the session row, or null.
 *
 * @param int $horizon_days How far ahead to search (inclusive of today).
 * @return array<string,mixed>|null
 */
function lp_class_next_session( int $horizon_days = 28 ): ?array {
	$now   = current_datetime();
	$start = $now->setTime( 0, 0 );
	$end   = $start->modify( '+' . max( 1, $horizon_days ) . ' days' );

	foreach ( lp_class_sessions_between( $start, $end ) as $row ) {
		if ( lp_class_session_is_future( $row ) ) {
			return $row;
		}
	}

	return null;
}

/**
 * Live "UPDATED HH:MM · DDD D MON" stamp for hero / board headers.
 */
function lp_hero_board_stamp( ?DateTimeInterface $at = null ): string {
	$dt = $at
		? DateTimeImmutable::createFromInterface( $at )->setTimezone( wp_timezone() )
		: current_datetime();
	return sprintf(
		'UPDATED %s · %s',
		$dt->format( 'H:i' ),
		strtoupper( $dt->format( 'D j M' ) )
	);
}

/**
 * Project a clasbpro session row into the Hero next-class board shape.
 *
 * @param array<string,mixed>|null $session From lp_class_next_session(); looked up if null.
 * @return array<string,mixed> Empty array when nothing is upcoming.
 */
function lp_hero_next_class_board( ?array $session = null ): array {
	if ( null === $session ) {
		$session = lp_class_next_session();
	}
	if ( ! $session ) {
		return array();
	}

	$class_id = (int) ( $session['id'] ?? 0 );
	$time     = (string) ( $session['time'] ?? '' );
	$date     = (string) ( $session['date'] ?? '' );
	$day      = (string) ( $session['date_label'] ?? '' );
	$duration = $class_id ? lp_class_duration( $class_id ) : '';
	$duration_ui = $duration
		? strtoupper( str_replace( ' min', ' MIN', $duration ) )
		: '';
	$when = implode( ' · ', array_filter( array( $day, $duration_ui ) ) );

	$meta_bits = array_filter(
		array(
			(string) ( $session['location'] ?? '' ),
			(string) ( $session['level'] ?? '' ),
			$class_id && (string) ( $session['coaches'] ?? '' )
				? 'Coach ' . (string) $session['coaches']
				: '',
		)
	);

	$date_ui = '';
	if ( '' !== $date ) {
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $date, wp_timezone() );
		if ( $dt ) {
			$date_ui = strtoupper( $dt->format( 'D j M' ) );
		}
	}

	$location = (string) ( $session['location'] ?? '' );
	$level    = (string) ( $session['level'] ?? '' );
	$price    = (string) ( $session['price'] ?? '' );
	if ( '' === $price && $class_id ) {
		$price = lp_class_price_display( $class_id );
	}

	$spaces = (string) ( $session['spaces'] ?? '' );
	// Hero pen uses sentence case ("4 left"); clasbpro labels are UPPER.
	$spaces_ui = '' !== $spaces ? strtolower( $spaces ) : '';

	$facts = array();
	if ( '' !== $duration ) {
		$facts[] = array(
			'label' => 'DURATION',
			'value' => $duration,
		);
	}
	if ( '' !== $level ) {
		$facts[] = array(
			'label' => 'LEVEL',
			'value' => $level,
		);
	}
	if ( '' !== $price ) {
		$facts[] = array(
			'label' => 'PRICE',
			'value' => $price,
		);
	}
	if ( '' !== $location ) {
		$facts[] = array(
			'label' => 'LOCATION',
			'value' => $location,
		);
	}

	return array(
		'title'      => 'NEXT CLASS',
		'stamp'      => lp_hero_board_stamp(),
		'time'       => $time,
		'when'       => $when,
		'name'       => (string) ( $session['title'] ?? '' ),
		'meta'       => implode( ' · ', $meta_bits ),
		'spaces'     => $spaces_ui,
		'facts'      => $facts,
		'foot_label' => 'Reserve a place',
		'foot_meta'  => trim( sprintf( 'NEXT · %s %s', $time, $date_ui ) ),
		'class_id'   => $class_id,
		'date'       => $date,
		'sold_out'   => ! empty( $session['sold_out'] ),
	);
}
