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
