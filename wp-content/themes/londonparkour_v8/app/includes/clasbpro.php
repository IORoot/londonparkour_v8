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
 * Age-range choice keys for acf_age_range.
 *
 * @return array<string, string> value => label
 */
function lp_class_age_range_choices(): array {
	return array(
		'6-9'     => '6 to 9s',
		'10-14'   => '10 to 14s',
		'14-plus' => '15+ Adults',
	);
}

/**
 * Stored age-range value. Empty / unknown falls back to 15+ Adults.
 *
 * @param int $class_id Post ID.
 */
function lp_class_age_range( int $class_id ): string {
	$choices = lp_class_age_range_choices();
	$value   = '';
	if ( function_exists( 'get_field' ) ) {
		$value = (string) get_field( 'acf_age_range', $class_id );
	}
	return isset( $choices[ $value ] ) ? $value : '14-plus';
}

/**
 * Human label for the class age-range selector.
 *
 * @param int $class_id Post ID.
 */
function lp_class_age_range_label( int $class_id ): string {
	$choices = lp_class_age_range_choices();
	return $choices[ lp_class_age_range( $class_id ) ];
}

/**
 * Youth classes: 6 to 9s and 10 to 14s. 15+ Adults is not youth.
 *
 * @param int $class_id Post ID.
 */
function lp_class_is_youth( int $class_id ): bool {
	return in_array( lp_class_age_range( $class_id ), array( '6-9', '10-14' ), true );
}

/**
 * Composed subtitle: duration plus optional ACF note, e.g. "60 min · Jump. Climb. Vault. Swing.".
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
 * Public one-off clasbpro class — the Workshops identity.
 *
 * Clasbpro stores this as schedule_type = one_off, exposed on class_data as
 * is_one_off_event. Prefer the plugin helper; fall back to the ACF field.
 *
 * @param int $class_id Post ID.
 */
function lp_class_is_one_off( int $class_id ): bool {
	$raw = lp_clasbpro_raw( $class_id );
	if ( $raw ) {
		return ! empty( $raw['is_one_off_event'] );
	}
	if ( function_exists( 'get_field' ) ) {
		return 'one_off' === (string) get_field( 'schedule_type', $class_id );
	}
	return 'one_off' === (string) get_post_meta( $class_id, 'schedule_type', true );
}

/**
 * Public appointment (1:1) clasbpro class — not a weekly group class.
 *
 * @param int $class_id Post ID.
 */
function lp_class_is_appointment( int $class_id ): bool {
	$raw = lp_clasbpro_raw( $class_id );
	if ( $raw ) {
		return ! empty( $raw['is_appointments'] ) || 'appointments' === (string) ( $raw['schedule_type'] ?? '' );
	}
	if ( function_exists( 'get_field' ) ) {
		return 'appointments' === (string) get_field( 'schedule_type', $class_id );
	}
	return 'appointments' === (string) get_post_meta( $class_id, 'schedule_type', true );
}

/**
 * Published, active, recurring weekly group classes — not workshops, 1:1s, or
 * external-link listings.
 *
 * @return int[]
 */
function lp_weekly_class_ids(): array {
	$ids = get_posts(
		lp_class_query_exclude_one_offs(
			lp_class_active_meta_query(
				array(
					'post_type'              => lp_class_post_type(),
					'post_status'            => 'publish',
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false,
				)
			)
		)
	);

	$weekly = array();
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id <= 0 || lp_class_is_appointment( $id ) || lp_class_is_one_off( $id ) ) {
			continue;
		}
		$raw = lp_clasbpro_raw( $id );
		if ( $raw && 'external_link' === (string) ( $raw['schedule_type'] ?? '' ) ) {
			continue;
		}
		$weekly[] = $id;
	}

	return $weekly;
}

/**
 * Weekly group classes that run at one lp_location site.
 *
 * @param int $location_id Location post ID.
 * @return WP_Post[]
 */
function lp_classes_at_location( int $location_id ): array {
	if ( $location_id <= 0 ) {
		return array();
	}

	$posts = array();
	foreach ( lp_weekly_class_ids() as $id ) {
		$id = (int) $id;
		if ( lp_class_location_id( $id ) !== $location_id ) {
			continue;
		}
		$post = get_post( $id );
		if ( $post instanceof WP_Post ) {
			$posts[] = $post;
		}
	}

	return function_exists( 'lp_class_dedupe_by_title' )
		? lp_class_dedupe_by_title( $posts )
		: $posts;
}

/**
 * Board stamp for a site, e.g. "2 CLASSES". Empty when none run there.
 *
 * @param int $location_id Location post ID.
 */
function lp_location_class_count_label( int $location_id ): string {
	$n = count( lp_classes_at_location( $location_id ) );
	if ( $n < 1 ) {
		return '';
	}

	return sprintf(
		/* translators: %d: number of weekly classes at this site */
		_n( '%d CLASS', '%d CLASSES', $n, 'londonparkour_v8' ),
		$n
	);
}

/**
 * Distinct `lp_location` sites that weekly classes actually run at.
 *
 * @param int[] $class_ids Weekly class post IDs. Defaults to lp_weekly_class_ids().
 * @return int[] Location post IDs.
 */
function lp_weekly_class_location_ids( array $class_ids = array() ): array {
	if ( ! $class_ids ) {
		$class_ids = lp_weekly_class_ids();
	}

	$sites = array();
	foreach ( $class_ids as $id ) {
		$location_id = lp_class_location_id( (int) $id );
		if ( $location_id > 0 ) {
			$sites[ $location_id ] = $location_id;
		}
	}

	return array_values( $sites );
}

/**
 * Classes-board foot note from live weekly classes, e.g. "3 SITES · 5 CLASSES A WEEK".
 *
 * Empty when there are no weekly classes — callers keep their editor fallback.
 */
function lp_weekly_class_foot_note(): string {
	$class_ids = lp_weekly_class_ids();
	$n_class   = count( $class_ids );
	if ( $n_class < 1 ) {
		return '';
	}

	$n_site = count( lp_weekly_class_location_ids( $class_ids ) );

	return sprintf(
		'%s · %s',
		sprintf(
			/* translators: %d: number of weekly class sites */
			_n( '%d SITE', '%d SITES', $n_site, 'londonparkour_v8' ),
			$n_site
		),
		sprintf(
			/* translators: %d: number of weekly classes */
			_n( '%d CLASS A WEEK', '%d CLASSES A WEEK', $n_class, 'londonparkour_v8' ),
			$n_class
		)
	);
}

/**
 * Meta query that keeps only one-off workshops.
 *
 * @return array<string,string>
 */
function lp_class_one_off_meta_clause(): array {
	return array(
		'key'   => 'schedule_type',
		'value' => 'one_off',
	);
}

/**
 * Meta query that drops one-off workshops (recurring + appointments remain).
 *
 * @return array<string,mixed>
 */
function lp_class_not_one_off_meta_clause(): array {
	return array(
		'relation' => 'OR',
		array(
			'key'     => 'schedule_type',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'schedule_type',
			'value'   => 'one_off',
			'compare' => '!=',
		),
	);
}

/**
 * AND a not-one-off clause onto existing query args.
 *
 * @param array $args WP_Query / get_posts args.
 * @return array
 */
function lp_class_query_exclude_one_offs( array $args ): array {
	$clause = lp_class_not_one_off_meta_clause();
	if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'AND',
			$args['meta_query'],
			$clause,
		);
	} else {
		$args['meta_query'] = $clause; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	return $args;
}

/**
 * True while a one-off sitting has not yet ended (end date + start time + duration).
 *
 * @param int $class_id Post ID.
 */
function lp_class_one_off_is_upcoming( int $class_id ): bool {
	$raw = lp_clasbpro_raw( $class_id );
	if ( ! $raw ) {
		return false;
	}
	$start = (string) ( $raw['start_date'] ?? '' );
	$end   = (string) ( $raw['end_date'] ?? '' );
	$date  = '' !== $end ? $end : $start;
	$time  = (string) ( $raw['start_time'] ?? '00:00' );
	if ( strlen( $time ) > 5 ) {
		$time = substr( $time, 0, 5 );
	}
	if ( '' === $date || '' === $time ) {
		return false;
	}
	$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . $time, wp_timezone() );
	if ( ! $dt ) {
		return false;
	}
	$mins = (int) ( $raw['duration'] ?? 0 );
	if ( $mins > 0 ) {
		$dt = $dt->modify( '+' . $mins . ' minutes' );
	}
	return $dt > current_datetime();
}

/**
 * Workshops index URL (`/workshops/`).
 */
function lp_workshops_url(): string {
	return lp_classes_page_url( 'workshops' );
}

/**
 * Published one-off classes, split into lead / remaining upcoming / past.
 *
 * Lead is the soonest upcoming sitting and is not repeated in `$rest`.
 *
 * @return array{lead:?WP_Post, rest:WP_Post[], past:WP_Post[]}
 */
function lp_class_workshops_split(): array {
	$posts = get_posts(
		lp_class_active_meta_query(
			array(
				'post_type'      => lp_class_post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					lp_class_one_off_meta_clause(),
				),
			)
		)
	);

	$upcoming = array();
	$past     = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		if ( ! lp_class_is_one_off( (int) $post->ID ) ) {
			continue;
		}
		if ( lp_class_one_off_is_upcoming( (int) $post->ID ) ) {
			$upcoming[] = $post;
		} else {
			$past[] = $post;
		}
	}

	$by_start = static function ( WP_Post $a, WP_Post $b ): int {
		$raw_a = lp_clasbpro_raw( (int) $a->ID );
		$raw_b = lp_clasbpro_raw( (int) $b->ID );
		return strcmp( (string) ( $raw_a['start_date'] ?? '' ), (string) ( $raw_b['start_date'] ?? '' ) );
	};
	usort( $upcoming, $by_start );
	usort( $past, $by_start );
	$past = array_reverse( $past );

	$lead = $upcoming[0] ?? null;
	$rest = array_slice( $upcoming, 1 );

	return array(
		'lead' => $lead,
		'rest' => $rest,
		'past' => $past,
	);
}

/**
 * Uppercase sitting date for overview rows, e.g. SAT 12 SEP.
 *
 * @param int $class_id Post ID.
 */
function lp_class_workshop_date_label( int $class_id ): string {
	$raw  = lp_clasbpro_raw( $class_id );
	$date = $raw ? (string) ( $raw['start_date'] ?? '' ) : '';
	$dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', $date );
	return $dt ? strtoupper( $dt->format( 'D j M' ) ) : '';
}

/**
 * Duration for workshop chrome: "6 HOURS", "3 DAYS", or "45 MIN".
 *
 * @param int $class_id Post ID.
 */
function lp_class_workshop_duration_label( int $class_id ): string {
	$raw  = lp_clasbpro_raw( $class_id );
	$mins = $raw ? (int) ( $raw['duration'] ?? 0 ) : 0;
	if ( $mins <= 0 ) {
		return '';
	}
	if ( $mins >= 1440 && 0 === $mins % 1440 ) {
		$days = (int) ( $mins / 1440 );
		return sprintf( '%d %s', $days, 1 === $days ? 'DAY' : 'DAYS' );
	}
	if ( $mins >= 60 && 0 === $mins % 60 ) {
		$hours = (int) ( $mins / 60 );
		return sprintf( '%d %s', $hours, 1 === $hours ? 'HOUR' : 'HOURS' );
	}
	return sprintf( '%d MIN', $mins );
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
 * Every occurrence date for a class inside [start, end] inclusive — including
 * dates that have already passed. Clasbpro's next_weekday_occurrences() skips
 * the past; the Agenda week board still needs those rows (greyed out).
 *
 * @param int               $class_id Post ID.
 * @param DateTimeImmutable $start    Range start (date only matters).
 * @param DateTimeImmutable $end      Range end inclusive.
 * @return array<int,string> Y-m-d strings in order.
 */
function lp_class_dates_between( int $class_id, DateTimeImmutable $start, DateTimeImmutable $end ): array {
	$raw = lp_clasbpro_raw( $class_id );
	if ( ! $raw || empty( $raw['start_time'] ) ) {
		return array();
	}

	$tz      = wp_timezone();
	$walk    = $start->setTimezone( $tz )->setTime( 0, 0 );
	$last    = $end->setTimezone( $tz )->setTime( 0, 0 );
	$skip    = (array) ( $raw['cancelled_dates'] ?? array() );
	$dates   = array();
	$one_off = ! empty( $raw['is_one_off_event'] );

	$weekday_map = array(
		'monday'    => 1,
		'tuesday'   => 2,
		'wednesday' => 3,
		'thursday'  => 4,
		'friday'    => 5,
		'saturday'  => 6,
		'sunday'    => 7,
	);
	$target = $weekday_map[ strtolower( (string) ( $raw['day_of_week'] ?? '' ) ) ] ?? 0;

	if ( $one_off ) {
		$run_start = (string) ( $raw['start_date'] ?? '' );
		$run_end   = (string) ( $raw['end_date'] ?? $run_start );
		if ( '' === $run_start ) {
			return array();
		}
		if ( '' === $run_end ) {
			$run_end = $run_start;
		}
	}

	$max = 14;
	while ( $walk <= $last && $max-- > 0 ) {
		$ymd = $walk->format( 'Y-m-d' );

		$matches = $one_off
			? ( $ymd >= $run_start && $ymd <= $run_end )
			: ( $target && (int) $walk->format( 'N' ) === $target );

		if ( $matches
			&& ! in_array( $ymd, $skip, true )
			&& ( ! lp_clasbpro_ready() || \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::date_in_class_run_window( $raw, $ymd ) )
		) {
			$dates[] = $ymd;
		}

		$walk = $walk->modify( '+1 day' );
	}

	return $dates;
}

/**
 * Session rows whose date falls in [start, end] inclusive (Agenda).
 *
 * Includes past occurrences in the window so the week board can grey them out.
 * Callers that only want future sessions must filter with
 * lp_class_session_is_future().
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

	$rows = array();

	foreach ( $class_ids as $class_id ) {
		$class_id = (int) $class_id;
		if ( ! lp_class_is_active( $class_id ) ) {
			continue;
		}
		if ( lp_class_is_one_off( $class_id ) ) {
			continue;
		}

		$raw = lp_clasbpro_raw( $class_id );
		if ( ! $raw || empty( $raw['start_time'] ) ) {
			continue;
		}

		$time = (string) $raw['start_time'];
		if ( strlen( $time ) > 5 ) {
			$time = substr( $time, 0, 5 );
		}
		$capacity = max( 0, (int) ( $raw['capacity'] ?? 0 ) );
		$board    = lp_class_board_fields( $class_id );

		foreach ( lp_class_dates_between( $class_id, $start, $end ) as $date ) {
			$remaining = lp_clasbpro_ready()
				? \IOROOT_STRIPE_BOOKINGS_PRO\Bookings::seats_remaining( $raw, $date )
				: $capacity;
			$sold_out = $remaining <= 0;
			$session  = array(
				'date'       => $date,
				'date_label' => lp_class_date_label( $date ),
				'time'       => $time,
				'spaces'     => lp_class_spaces_label( $remaining, $capacity ),
				'sold_out'   => $sold_out,
				'remaining'  => $remaining,
				'capacity'   => $capacity,
				'book_label' => $sold_out ? 'WAITLIST' : 'BOOK',
			);
			$rows[] = array_merge( $board, $session );
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
	$glyph_svg   = lp_class_calendar_icon_svg( $class_id );

	return array(
		'id'            => $class_id,
		'title'         => get_the_title( $class_id ),
		'url'           => (string) get_permalink( $class_id ),
		'thumb'         => lp_class_image_id( $class_id ) ?: null,
		'subtitle'      => lp_class_composed_subtitle( $class_id ),
		'location'      => $location_id ? get_the_title( $location_id ) : '',
		'location_id'   => $location_id,
		'level'         => $level,
		'price'         => lp_class_price_display( $class_id ),
		'price_label'   => lp_class_price_label(),
		'glyph_svg'     => $glyph_svg,
		'glyph_icon_id' => '' === $glyph_svg ? 'glyph-balancing' : '',
		'coaches'       => implode(
			', ',
			array_map( 'get_the_title', lp_class_coach_ids( $class_id ) )
		),
	);
}

/**
 * Class calendar icon from ACF Card icon (SVG). Empty when unset — board rows
 * then fall back to sprite `glyph-balancing`.
 *
 * @param int $class_id Post ID.
 * @return string Sanitized SVG markup, or ''.
 */
function lp_class_calendar_icon_svg( int $class_id ): string {
	$custom = '';
	if ( $class_id && function_exists( 'get_field' ) ) {
		$custom = (string) get_field( 'calendar_icon', $class_id );
	}

	if ( class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Helpers' ) ) {
		return \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::sanitize_calendar_icon_svg( $custom );
	}

	if ( '' === trim( $custom ) || ! preg_match( '/<svg\b/i', $custom ) ) {
		return '';
	}

	return $custom;
}

/**
 * Board / masthead / nav glyph for a class — Card icon SVG, else sprite fallback.
 *
 * @param int $class_id Post ID.
 * @return array{svg:string, icon_id:string}
 */
function lp_class_glyph( int $class_id ): array {
	$svg = lp_class_calendar_icon_svg( $class_id );

	return array(
		'svg'     => $svg,
		'icon_id' => '' === $svg ? 'glyph-balancing' : '',
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
		'data-lp-panel'  => 'booking',
		'data-lp-book'   => '1',
		'data-class-id'  => (string) $class_id,
		'data-lp-id'     => (string) $class_id,
		'data-lp-list'   => 'classes',
	);
	if ( '' !== $preset_date ) {
		$attrs['data-preset-date'] = $preset_date;
	}

	$title = get_the_title( $class_id );
	if ( is_string( $title ) && '' !== $title ) {
		$attrs['data-lp-item-name'] = $title;
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
 * Clasbpro slug for Adult Beginners East (Saturday 10:30, Old Street).
 *
 * Title and slug diverge — pin the conversion shortcut to the slug.
 */
function lp_hero_first_class_slug(): string {
	return 'adult-beginners-outdoor';
}

/**
 * Post ID for the homepage hero first-class shortcut, or 0.
 */
function lp_hero_first_class_id(): int {
	$type = function_exists( 'lp_class_post_type' ) ? lp_class_post_type() : 'clasbpro_class';
	$post = get_page_by_path( lp_hero_first_class_slug(), OBJECT, $type );
	if ( ! $post instanceof WP_Post ) {
		return 0;
	}
	$id = (int) $post->ID;
	if ( $id < 1 || ( function_exists( 'lp_class_is_active' ) && ! lp_class_is_active( $id ) ) ) {
		return 0;
	}
	return $id;
}

/**
 * Booking-drawer args for the hero primary CTA.
 *
 * Presets the next upcoming Saturday of Adult Beginners East. Returns null
 * when that class is missing so the ACF link still works.
 *
 * @param string $label   Visible button text (from the design / ACF).
 * @param string $variant Button variant.
 * @return array<string,mixed>|null
 */
function lp_hero_first_class_book_args( string $label, string $variant = 'primary' ): ?array {
	$id = lp_hero_first_class_id();
	if ( $id < 1 ) {
		return null;
	}

	$date = '';
	foreach ( lp_class_upcoming_sessions( $id, 4 ) as $row ) {
		if ( lp_class_session_is_future( $row ) ) {
			$date = (string) ( $row['date'] ?? '' );
			break;
		}
	}

	$args                      = lp_class_book_button_args( $id, $date, $label, $variant );
	$args['trailing_icon_id']  = 'icon-arrow-right';
	$args['data_attrs']['data-lp-list'] = 'hero';
	return $args;
}

/**
 * Args for elements/button.php that open the shared coupon drawer.
 *
 * @param int    $pack_id Pack post ID (clasbpro_pack).
 * @param string $label   Button label.
 * @param string $variant Button variant.
 * @return array<string,mixed>
 */
function lp_pack_buy_button_args( int $pack_id, string $label = 'BUY', string $variant = 'primary' ): array {
	$attrs = array(
		'data-lp-panel' => 'coupon',
		'data-pack-id'  => (string) $pack_id,
		'data-lp-id'    => (string) $pack_id,
		'data-lp-list'  => 'pricing',
	);

	$title = get_the_title( $pack_id );
	if ( is_string( $title ) && '' !== $title ) {
		$attrs['data-lp-item-name'] = $title;
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
 * Relative day label for a Y-m-d session date: "Today", "Tomorrow", "In 2 days".
 *
 * @param string $date Y-m-d.
 */
function lp_class_relative_day( string $date ): string {
	$target = DateTimeImmutable::createFromFormat( 'Y-m-d', $date, wp_timezone() );
	if ( ! $target ) {
		return '';
	}

	$today = current_datetime()->setTime( 0, 0 );
	$day   = $target->setTime( 0, 0 );
	$diff  = (int) $today->diff( $day )->format( '%r%a' );

	if ( 0 === $diff ) {
		return 'Today';
	}
	if ( 1 === $diff ) {
		return 'Tomorrow';
	}
	if ( $diff > 1 ) {
		return sprintf( 'In %d days', $diff );
	}

	return $day->format( 'D j M' );
}

/**
 * Project a clasbpro session row into the homepage CTA next-session panel.
 *
 * @param array<string,mixed>|null $session From lp_class_next_session(); looked up if null.
 * @return array<string,string>
 */
function lp_cta_session_panel( ?array $session = null ): array {
	if ( null === $session ) {
		$session = lp_class_next_session();
	}

	$defaults = array(
		'kicker'     => 'NEXT SESSION',
		'when'       => 'In 2 days',
		'meta'       => 'Vauxhall · 18:30 · Level 1',
		'foot_label' => 'CLASS',
		'foot_value' => 'Beginners Parkour',
		'href'       => function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : '/classes/',
	);

	if ( ! $session ) {
		return $defaults;
	}

	$time     = (string) ( $session['time'] ?? '' );
	$location = (string) ( $session['location'] ?? '' );
	$level    = (string) ( $session['level'] ?? '' );
	$date     = (string) ( $session['date'] ?? '' );
	$when     = '' !== $date ? lp_class_relative_day( $date ) : '';
	$meta     = implode( ' · ', array_filter( array( $location, $time, $level ) ) );
	$title    = (string) ( $session['title'] ?? '' );

	return array(
		'kicker'     => 'NEXT SESSION',
		'when'       => '' !== $when ? $when : $defaults['when'],
		'meta'       => '' !== $meta ? $meta : $defaults['meta'],
		'foot_label' => 'CLASS',
		'foot_value' => '' !== $title ? $title : $defaults['foot_value'],
		'href'       => $defaults['href'],
	);
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

	$when     = '';
	if ( '' !== $date ) {
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $date, wp_timezone() );
		if ( $dt ) {
			$when = strtoupper( $dt->format( 'D' ) ) . ' - ' . $dt->format( 'jS' );
		}
	}
	if ( '' === $when ) {
		$when = $day;
	}

	$location = (string) ( $session['location'] ?? '' );
	$level    = (string) ( $session['level'] ?? '' );
	$price    = (string) ( $session['price'] ?? '' );
	if ( '' === $price && $class_id ) {
		$price = lp_class_price_display( $class_id );
	}

	$coach = trim( explode( ',', (string) ( $session['coaches'] ?? '' ) )[0] );

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
	if ( '' !== $time ) {
		$facts[] = array(
			'label' => 'START TIME',
			'value' => $time,
		);
	}
	if ( '' !== $price ) {
		$facts[] = array(
			'label' => 'PRICE',
			'value' => $price,
		);
	}
	if ( '' !== $coach ) {
		$facts[] = array(
			'label' => 'COACH',
			'value' => $coach,
		);
	}

	return array(
		'title'      => 'NEXT CLASS',
		'time'       => $time,
		'when'       => $when,
		'name'       => (string) ( $session['title'] ?? '' ),
		'meta'       => $location,
		'spaces'     => $spaces_ui,
		'facts'      => $facts,
		'foot_label' => 'Reserve a place',
		'foot_meta'  => $level,
		'class_id'   => $class_id,
		'date'       => $date,
		'sold_out'   => ! empty( $session['sold_out'] ),
	);
}

/**
 * Normalise a clasbpro time string to 24-hour HH:MM (pen when-line uses 18:30).
 *
 * @param string $time Raw start_time (H:i, H:i:s, or already formatted).
 */
function lp_booking_form_hhmm( string $time ): string {
	$time = trim( $time );
	if ( '' === $time ) {
		return '';
	}
	if ( preg_match( '/^(\d{1,2}):(\d{2})/', $time, $m ) ) {
		return sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] );
	}

	return $time;
}

/**
 * CTA label for clasbpro booking submit (pen hoH6b: CONFIRM AND PAY £15).
 *
 * @param array<string,mixed> $class_data Plugin class_data.
 */
function lp_booking_form_pay_label( array $class_data ): string {
	$id    = (int) ( $class_data['id'] ?? 0 );
	$price = ( $id && function_exists( 'lp_class_price_display' ) )
		? lp_class_price_display( $id )
		: '';

	if ( '' === $price && ! empty( $class_data['price'] ) ) {
		$amount = (float) $class_data['price'];
		$price  = '£' . ( ( floor( $amount ) === $amount )
			? (string) (int) $amount
			: number_format( $amount, 2, '.', '' ) );
	}

	return '' !== $price
		? sprintf(
			/* translators: %s: formatted price, e.g. £15 */
			__( 'CONFIRM AND PAY %s', 'londonparkour_v8' ),
			$price
		)
		: __( 'CONFIRM AND PAY', 'londonparkour_v8' );
}

/**
 * Session header copy for clasbpro booking forms (pen AKJMB).
 *
 * Used by recurring classes, one-offs, and appointments. Does not replace
 * the form fields — only the chrome above them.
 *
 * @param object $view Booking_Form_View.
 * @return array{when:string,name:string,sub:string}
 */
function lp_booking_form_session( $view ): array {
	$data = ( is_object( $view ) && isset( $view->class_data ) && is_array( $view->class_data ) )
		? $view->class_data
		: array();
	$id   = (int) ( $data['id'] ?? 0 );

	$date = '';
	if ( is_object( $view ) ) {
		$date = (string) ( $view->preset_date ?? '' );
		if ( '' === $date && ! empty( $view->primary_date['date'] ) ) {
			$date = (string) $view->primary_date['date'];
		}
		if ( '' === $date && ! empty( $data['is_one_off_event'] ) ) {
			$date = (string) ( $data['start_date'] ?? '' );
		}
		if ( '' === $date && ! empty( $view->dates[0]['date'] ) ) {
			$date = (string) $view->dates[0]['date'];
		}
	}

	$time     = (string) ( $data['start_time'] ?? '' );
	$duration = (int) ( $data['duration'] ?? 0 );
	$location = '';
	$level    = '';

	if ( $id ) {
		$board    = lp_class_board_fields( $id );
		$location = (string) ( $board['location'] ?? '' );
		$level    = (string) ( $board['level'] ?? '' );
	}
	if ( '' === $location ) {
		$raw_location = trim( (string) ( $data['location'] ?? '' ) );
		if ( '' !== $raw_location && ctype_digit( $raw_location ) ) {
			$titled = get_the_title( (int) $raw_location );
			$location = is_string( $titled ) ? $titled : $raw_location;
		} else {
			$location = $raw_location;
		}
	}

	if ( ! empty( $view->is_appointments )
		&& ! empty( $view->preset_slot_rule_id )
		&& class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Slot_Rules' )
	) {
		$rule = \IOROOT_STRIPE_BOOKINGS_PRO\Slot_Rules::find_rule( $data, (string) $view->preset_slot_rule_id );
		if ( is_array( $rule ) && '' !== $date ) {
			$snap = \IOROOT_STRIPE_BOOKINGS_PRO\Slot_Rules::build_snapshot( $data, $rule, $date );
			if ( ! empty( $snap['start_time'] ) ) {
				$time = (string) $snap['start_time'];
			}
			if ( ! empty( $snap['duration_minutes'] ) ) {
				$duration = (int) $snap['duration_minutes'];
			}
			if ( ! empty( $snap['location'] ) ) {
				$location = (string) $snap['location'];
			}
		} elseif ( is_array( $rule ) ) {
			if ( ! empty( $rule['start_time'] ) ) {
				$time = (string) $rule['start_time'];
			}
			if ( ! empty( $rule['duration_minutes'] ) ) {
				$duration = (int) $rule['duration_minutes'];
			}
		}
	}

	$when_bits = array();
	if ( '' !== $date ) {
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $date, wp_timezone() );
		if ( $dt ) {
			$when_bits[] = strtoupper( $dt->format( 'D j M' ) );
		}
	}
	$hhmm = lp_booking_form_hhmm( $time );
	if ( '' !== $hhmm ) {
		$when_bits[] = $hhmm;
	}
	if ( $duration > 0 ) {
		$when_bits[] = $duration . ' MIN';
	}

	$sub_bits = array();
	if ( '' !== $location ) {
		$sub_bits[] = $location;
	}
	if ( '' !== $level ) {
		$sub_bits[] = $level;
	}

	$remaining = null;
	$show_seats = is_object( $view ) && ! empty( $view->show_seats_remaining );
	if ( $show_seats && empty( $view->is_appointments ) && '' !== $date ) {
		foreach ( (array) ( $view->dates ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( (string) ( $row['date'] ?? '' ) === $date && isset( $row['remaining'] ) ) {
				$remaining = (int) $row['remaining'];
				break;
			}
		}
		if ( null === $remaining
			&& $id
			&& class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Bookings' )
		) {
			$remaining = (int) \IOROOT_STRIPE_BOOKINGS_PRO\Bookings::seats_remaining( $data, $date );
		}
	}
	if ( is_int( $remaining ) && $remaining > 0 ) {
		$sub_bits[] = sprintf(
			/* translators: %d: places remaining */
			_n( '%d place left', '%d places left', $remaining, 'londonparkour_v8' ),
			$remaining
		);
	}

	$name = ( is_object( $view ) && method_exists( $view, 'get_title' ) )
		? (string) $view->get_title()
		: (string) ( $data['name'] ?? '' );

	return array(
		'when' => implode( ' · ', $when_bits ),
		'name' => $name,
		'sub'  => implode( ' · ', $sub_bits ),
	);
}

/**
 * Format a clasbpro pack price like the fare board ("£15", not "£15.00").
 *
 * @param float $price Major-unit amount.
 */
function lp_clasbpro_pack_price_display( float $price ): string {
	if ( $price <= 0 ) {
		return '';
	}

	$formatted = ( floor( $price ) === $price )
		? (string) (int) $price
		: number_format( $price, 2, '.', '' );

	return '£' . $formatted;
}

/**
 * Pricing-board glyph for a coupon size (pen k4hV1).
 *
 * @param int $uses Classes included.
 */
function lp_clasbpro_pack_glyph_id( int $uses ): string {
	if ( $uses >= 10 ) {
		return 'glyph-chaining';
	}
	if ( $uses >= 5 ) {
		return 'glyph-flowing';
	}

	return 'glyph-step';
}

/**
 * Physical gift-card SVG (pen ZSzpV): primary face, brand, glyph, amount.
 *
 * @param array<string,mixed> $pack Packs::get_pack_data() row.
 */
function lp_clasbpro_pack_gift_card( array $pack ): void {
	$id     = (int) ( $pack['id'] ?? 0 );
	$uses   = (int) ( $pack['uses'] ?? 1 );
	$name   = (string) ( $pack['name'] ?? '' );
	$price  = lp_clasbpro_pack_price_display( (float) ( $pack['price'] ?? 0 ) );
	$glyph  = lp_clasbpro_pack_glyph_id( $uses );
	$sprite = get_theme_file_uri( 'assets/img/glyphs.svg' );
	$title  = $id ? 'cbfs-gift-' . $id : 'cbfs-gift';
	$label  = '' !== $price
		? sprintf(
			/* translators: 1: pack name, 2: price */
			__( '%1$s gift card, %2$s', 'londonparkour_v8' ),
			$name !== '' ? $name : __( 'Coupon', 'londonparkour_v8' ),
			$price
		)
		: ( $name !== '' ? $name : __( 'Gift card', 'londonparkour_v8' ) );
	?>
	<svg class="cbfs-packs__gift" viewBox="0 0 300 186" role="img" aria-labelledby="<?php echo esc_attr( $title ); ?>">
		<title id="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $label ); ?></title>
		<rect class="cbfs-packs__gift-face" width="300" height="186" rx="12" ry="12"/>
		<text class="cbfs-packs__gift-ink cbfs-packs__gift-brand" x="22" y="44">LONDONPARKOUR</text>
		<svg class="cbfs-packs__gift-glyph" x="252" y="22" width="26" height="26" viewBox="0 0 240 240" aria-hidden="true">
			<use href="<?php echo esc_url( $sprite ); ?>#<?php echo esc_attr( $glyph ); ?>"/>
		</svg>
		<?php if ( '' !== $price ) : ?>
			<text class="cbfs-packs__gift-ink cbfs-packs__gift-amount" x="22" y="164"><?php echo esc_html( $price ); ?></text>
		<?php endif; ?>
		<text class="cbfs-packs__gift-ink cbfs-packs__gift-label" x="278" y="160" text-anchor="end">GIFT CARD</text>
	</svg>
	<?php
}

/**
 * Extra class context for the Concourse booking-status overlay.
 *
 * The shortcode booking payload has no class_id. We look it up from booking
 * meta so meeting point, map, film and permalink can come from the class post.
 *
 * @param object $view Booking_Status_View.
 * @return array<string,mixed>
 */
function lp_clasbpro_status_context( $view ): array {
	$booking    = ( is_object( $view ) && isset( $view->booking ) && is_array( $view->booking ) ) ? $view->booking : array();
	$booking_id = (int) ( $booking['booking_id'] ?? 0 );
	$class_id   = (int) ( $booking['class_id'] ?? 0 );
	if ( $class_id <= 0 && $booking_id > 0 ) {
		$class_id = (int) get_post_meta( $booking_id, '_clasbpro_class_id', true );
	}

	$origin = ( is_object( $view ) && isset( $view->origin ) ) ? (string) $view->origin : '';
	$origin = $origin ? $origin : home_url( '/' );

	$class_name = (string) ( $booking['class_name'] ?? '' );
	$class_href = $class_id ? (string) get_permalink( $class_id ) : $origin;
	$location   = (string) ( $booking['location'] ?? '' );
	$session    = trim( (string) ( $booking['class_date'] ?? '' ) . ' · ' . (string) ( $booking['class_time'] ?? '' ), ' ·' );
	$note       = trim( implode( ', ', array_filter( array( $class_name, (string) ( $booking['class_date'] ?? '' ), (string) ( $booking['class_time'] ?? '' ) ) ) ) );

	$location_id     = ( $class_id && function_exists( 'lp_class_location_id' ) ) ? lp_class_location_id( $class_id ) : 0;
	$meeting_point   = $location_id ? (string) get_field( 'meeting_point', $location_id ) : '';
	$transport_rail  = $location_id ? (string) get_field( 'transport_rail', $location_id ) : '';
	$transport_bus   = $location_id ? (string) get_field( 'transport_bus', $location_id ) : '';
	$lat             = $location_id ? trim( (string) get_field( 'latitude', $location_id ) ) : '';
	$lon             = $location_id ? trim( (string) get_field( 'longitude', $location_id ) ) : '';
	$maps_href       = ( $lat && $lon && function_exists( 'lp_google_maps_url' ) ) ? lp_google_maps_url( $lat, $lon ) : '';
	$image_id        = ( $class_id && function_exists( 'lp_class_image_id' ) ) ? lp_class_image_id( $class_id ) : 0;
	$location_image  = ( $location_id && has_post_thumbnail( $location_id ) ) ? (int) get_post_thumbnail_id( $location_id ) : 0;
	$location_type   = $location_id ? (string) get_field( 'type', $location_id ) : '';
	$location_meta   = $location_id ? (string) get_field( 'meta', $location_id ) : '';
	$site_kicker     = strtoupper( trim( implode( ' · ', array_filter( array( $location_type, $location ) ) ) ) );
	$foot            = $location_meta;
	if ( '' === $foot && $lat && $lon ) {
		$foot = $lat . ' / ' . $lon;
	}
	$video_url       = ( $class_id && function_exists( 'get_field' ) ) ? (string) get_field( 'video_url', $class_id ) : '';
	$video_id        = ( $video_url && function_exists( 'lp_youtube_id_from_url' ) ) ? lp_youtube_id_from_url( $video_url ) : '';
	$ref             = $booking_id ? ( '#' . $booking_id ) : '';
	$whatsapp_raw    = (string) apply_filters(
		'lp_clasbpro_whatsapp_url',
		function_exists( 'lp_whatsapp_invite_url' ) ? lp_whatsapp_invite_url( $class_id, $location_id ) : '',
		$class_id,
		$booking
	);
	$whatsapp        = function_exists( 'lp_whatsapp_invite_url_sanitize' )
		? lp_whatsapp_invite_url_sanitize( $whatsapp_raw )
		: '';
	$show_whatsapp   = '' !== $ref;
	$qr_src          = '';
	if ( $show_whatsapp ) {
		$qr_src = '' !== $whatsapp
			? ( 'https://api.qrserver.com/v1/create-qr-code/?size=264x264&ecc=M&data=' . rawurlencode( $whatsapp ) )
			: content_url( 'uploads/Page_images/londonparkour_com.png' );
	}
	$private_href    = home_url( '/private-coaching/' );
	$coupons_href    = home_url( '/coupons/' );
	$contact_href    = home_url( '/contact/' );
	$contact_mail    = 'mailto:hello@londonparkour.com';

	$faqs = array(
		array(
			'index'    => '01',
			'question' => 'Do I need any experience?',
			'answer'   => "No. Outdoor class is built for adults of every level. Read the beginners wiki if it's your first session.",
		),
		array(
			'index'    => '02',
			'question' => 'What should I wear?',
			'answer'   => 'Unrestrictive kit — tracksuit bottoms, a tee, trainers. Skip jeans and boots. What to wear is on the class page.',
		),
		array(
			'index'    => '03',
			'question' => 'What should I bring?',
			'answer'   => 'A bottle of water. Leave jewellery, watches, phones and wallets out of the session. Full kit list sits in the wiki.',
		),
		array(
			'index'    => '04',
			'question' => 'Can I cancel?',
			'answer'   => 'Free cancellation up to 24 hours before. After that we move you to another Saturday. Questions: contact page.',
		),
	);

	return (array) apply_filters(
		'lp_clasbpro_status_context',
		array(
			'booking'         => $booking,
			'booking_id'      => $booking_id,
			'class_id'        => $class_id,
			'class_name'      => $class_name,
			'class_href'      => $class_href,
			'location'        => $location,
			'session'         => $session,
			'note'            => $note,
			'seats'           => (string) ( $booking['seats'] ?? '' ),
			'total'           => (string) ( $booking['amount_total'] ?? '' ),
			'ref'             => $ref,
			'customer_name'   => (string) ( $booking['customer_name'] ?? '' ),
			'origin'          => $origin,
			'location_id'     => $location_id,
			'meeting_point'   => $meeting_point,
			'transport_rail'  => $transport_rail,
			'transport_bus'   => $transport_bus,
			'lat'             => $lat,
			'lon'             => $lon,
			'maps_href'       => $maps_href,
			'image_id'        => $image_id,
			'location_image_id' => $location_image,
			'site_kicker'     => $site_kicker,
			'foot'            => $foot,
			'video_id'        => $video_id,
			'qr_src'          => $qr_src,
			'whatsapp_href'   => $whatsapp,
			'show_whatsapp'   => $show_whatsapp,
			'private_href'    => $private_href,
			'coupons_href'    => $coupons_href,
			'contact_href'    => $contact_href,
			'contact_mail'    => $contact_mail,
			'faqs'            => $faqs,
		),
		$view
	);
}

/**
 * Receipt row used on confirmed + compact status boards.
 *
 * Whole class strings — Tailwind v4 scans source text.
 *
 * @param string $label   Term.
 * @param string $value   Definition.
 * @param string $surface page|board.
 */
function lp_clasbpro_status_ticket_row( string $label, string $value, string $surface = 'page' ): void {
	if ( '' === $value ) {
		return;
	}
	$rows = array(
		'page'  => 'flex items-center justify-between gap-4 py-[14px] border-t border-base-300',
		'board' => 'flex items-center justify-between gap-4 py-[14px] border-t border-neutral-content/20',
		'error'      => 'flex items-center justify-between gap-4 py-3 border-b border-neutral-content/40',
		'cancelled'  => 'flex items-center justify-between gap-4 py-3 border-b border-base-300',
	);
		$dts  = array(
		'page'  => 'font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65 m-0',
		'board' => 'font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50 m-0',
		'error'      => 'font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50 m-0',
		'cancelled'  => 'font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65 m-0',
	);
	$dds  = array(
		'page'  => 'font-heading text-[16px] font-medium text-base-content m-0 text-right',
		'board' => 'font-heading text-[16px] font-medium text-neutral-content m-0 text-right',
		'error'      => 'font-heading text-[16px] font-medium text-neutral-content m-0 text-right',
		'cancelled'  => 'font-heading text-[16px] font-medium text-base-content m-0 text-right',
	);
	$row = $rows[ $surface ] ?? $rows['page'];
	$dt  = $dts[ $surface ] ?? $dts['page'];
	$dd  = $dds[ $surface ] ?? $dds['page'];
	?>
	<div class="<?php echo esc_attr( $row ); ?>">
		<dt class="<?php echo esc_attr( $dt ); ?>"><?php echo esc_html( $label ); ?></dt>
		<dd class="<?php echo esc_attr( $dd ); ?>"><?php echo esc_html( $value ); ?></dd>
	</div>
	<?php
}
