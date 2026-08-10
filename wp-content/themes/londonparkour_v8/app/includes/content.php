<?php
/**
 * Post → partial projections shared by more than one template.
 *
 * DATA shaping only — the markup these feed lives under parts/. A projection
 * with a single caller stays in that caller (search.php's post-type label map);
 * anything here has at least two.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Flatten a post into the shape parts/components/blog-card.php reads.
 *
 * Everything comes from core except `read_time`, the one value WordPress has
 * no native equivalent for — see the `group_lp_post` note in
 * app/setup/acf-groups.php. The two fallbacks are BlogIndex's own source
 * defaults, kept so a post with no category or no author still renders the
 * design rather than a gap.
 *
 * @param WP_Post $lp_post The post.
 * @return array
 */
function lp_post_card_args( WP_Post $lp_post ): array {
	$lp_cats     = get_the_category( $lp_post->ID );
	$lp_category = $lp_cats ? $lp_cats[0]->name : 'Project';
	$lp_read     = function_exists( 'get_field' ) ? (string) get_field( 'read_time', $lp_post->ID ) : '';
	$lp_author   = get_the_author_meta( 'display_name', (int) $lp_post->post_author );

	return array(
		'category'  => strtoupper( $lp_category ),
		'read_time' => $lp_read ?: '3 MIN READ',
		'title'     => get_the_title( $lp_post ),
		'excerpt'   => get_the_excerpt( $lp_post ),
		'author'    => $lp_author ?: 'Andy Pearson',
		'date'      => get_the_date( 'M j, Y', $lp_post ),
		'date_meta' => strtoupper( get_the_date( 'j M Y', $lp_post ) ),
		'href'      => get_permalink( $lp_post ),
		'image_id'  => get_post_thumbnail_id( $lp_post ) ?: 0,
	);
}

/**
 * Project a query's paging state into parts/components/pagination.php's args.
 *
 * Returns an EMPTY array when there is only one page, so a caller can write
 * `$p = lp_pagination_args(); if ( $p ) { lp_part( …, $p ); }` and needs no
 * second condition.
 *
 * Hrefs come from get_pagenum_link(), which reads the current request — so
 * this suits the MAIN query. A secondary WP_Query would need its own link
 * builder; none of the three callers has one.
 *
 * @param WP_Query|null $lp_query Defaults to the main query.
 * @param string        $lp_noun  Plural noun for the count line. Default 'RESULTS'.
 * @return array
 */
function lp_pagination_args( ?WP_Query $lp_query = null, string $lp_noun = 'RESULTS' ): array {
	$lp_query = $lp_query ?? $GLOBALS['wp_query'];
	$lp_total = (int) $lp_query->max_num_pages;

	if ( $lp_total < 2 ) {
		return array();
	}

	$lp_current  = max( 1, (int) $lp_query->get( 'paged' ) );
	$lp_per_page = (int) $lp_query->get( 'posts_per_page' );
	$lp_found    = (int) $lp_query->found_posts;
	$lp_from     = ( ( $lp_current - 1 ) * $lp_per_page ) + 1;
	$lp_to       = min( $lp_current * $lp_per_page, $lp_found );

	// ponytail: every page number, no ellipsis — the design draws three boxes
	// and has no truncated state to port. Window this when a real query runs
	// past ~10 pages AND the design gains an ellipsis to window it with.
	$lp_pages = array();
	for ( $lp_i = 1; $lp_i <= $lp_total; $lp_i++ ) {
		$lp_pages[] = array(
			'label'   => (string) $lp_i,
			'href'    => get_pagenum_link( $lp_i ),
			'current' => $lp_i === $lp_current,
		);
	}

	return array(
		'pages' => $lp_pages,
		'prev'  => $lp_current > 1
			? array(
				'label' => '← PREVIOUS',
				'href'  => get_pagenum_link( $lp_current - 1 ),
			)
			: array(),
		'next'  => $lp_current < $lp_total
			? array(
				'label' => 'NEXT →',
				'href'  => get_pagenum_link( $lp_current + 1 ),
			)
			: array(),
		// En dash, not a hyphen — SearchResults.js flags this on `h0BaW`.
		'count' => sprintf( 'SHOWING %02d–%02d OF %d %s', $lp_from, $lp_to, $lp_found, $lp_noun ),
	);
}

/**
 * URL of one of the two Classes view PAGES, by slug.
 *
 * The source hrefs are `/classes/agenda` and `/classes/map`, but `/classes/` is
 * the clasbpro_class post-type archive, so a page cannot live under it without
 * a parent page whose slug collides with that archive. The pages are therefore
 * `classes-agenda` and `classes-map` at the top level. A URL is routing, not
 * design — the source's hrefs are Storybook literals, not a signed-off `.pen`
 * decision — so this is a departure worth recording rather than worth building
 * rewrite rules for. See docs/PORT-FINDINGS.md §21.
 *
 * Falls back to the source's own path if the page has not been seeded yet, so
 * the rail still points somewhere rather than at nothing.
 *
 * @param string $lp_slug Page slug.
 * @return string
 */
function lp_classes_page_url( string $lp_slug ): string {
	$lp_page = get_page_by_path( $lp_slug );

	return $lp_page ? (string) get_permalink( $lp_page ) : home_url( '/' . str_replace( 'classes-', 'classes/', $lp_slug ) );
}

/**
 * The three Classes view-rail tabs, shared by Agenda, Listings and Map.
 *
 * Ported from ClassesHeaderCluster.js's `TABS`. Agenda and Map only —
 * Listings is no longer a page. Metas are counted so they stay accurate when
 * editors add classes.
 *
 * Session counts come from clasbpro via lp_class_upcoming_sessions().
 *
 * @param string $lp_active agenda|map.
 * @return array Tabs in view-rail.php's shape.
 */
function lp_classes_view_tabs( string $lp_active = 'agenda' ): array {
	$lp_class_ids = get_posts(
		lp_class_active_meta_query(
			array(
				'post_type'      => lp_class_post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		)
	);

	$lp_sessions = 0;
	foreach ( $lp_class_ids as $lp_id ) {
		$lp_sessions += count( lp_class_upcoming_sessions( (int) $lp_id, 16 ) );
	}

	$lp_sites = (int) ( wp_count_posts( 'lp_location' )->publish ?? 0 );

	return array(
		array(
			'label'   => 'AGENDA',
			'meta'    => sprintf( '%d SESSIONS', $lp_sessions ),
			'icon_id' => 'icon-calendar-days',
			'href'    => lp_classes_page_url( 'classes-agenda' ),
			'active'  => 'agenda' === $lp_active,
		),
		array(
			'label'   => 'MAP',
			'meta'    => sprintf( '%d SITES', $lp_sites ),
			'icon_id' => 'icon-map-pin',
			'href'    => lp_classes_page_url( 'classes-map' ),
			'active'  => 'map' === $lp_active,
		),
	);
}

/**
 * The three Classes filter cells, in filter-grid.php's shape.
 *
 * Ported from ClassesHeaderCluster.js's `DEFAULT_FILTER_CELLS`. The keys and
 * the placeholder are the design's own strings, including `All six sites` —
 * which hardcodes the site count. It is correct for the six sites this site
 * has; if a seventh is added, that string is the design's to change, not this
 * file's to rewrite. Recorded in docs/PORT-FINDINGS.md.
 *
 * Options come from real records: CLASS TYPE from the `lp_level` taxonomy
 * (the design's kickers — ALL LEVELS, LEVEL 2 · IMPROVER, 6–9 AGE — are level
 * terms), SITE from published `lp_location` posts, which is what a class's
 * `acf_location` post_object field points at.
 *
 * Field names match app/setup/queries.php. Nothing here is a taxonomy query
 * var: see that file for why the filter uses its own parameters.
 *
 * @param array $lp_current Current values keyed by field name.
 * @return array
 */
function lp_class_filter_cells( array $lp_current = array() ): array {
	$lp_levels = get_terms(
		array(
			'taxonomy'   => 'lp_level',
			'hide_empty' => false,
		)
	);

	$lp_level_options = array(
		array(
			'value' => '',
			'label' => 'All classes',
		),
	);
	foreach ( is_array( $lp_levels ) ? $lp_levels : array() as $lp_term ) {
		$lp_level_options[] = array(
			'value' => $lp_term->slug,
			'label' => $lp_term->name,
		);
	}

	$lp_site_options = array(
		array(
			'value' => '',
			'label' => 'All six sites',
		),
	);
	foreach ( get_posts(
		array(
			'post_type'      => 'lp_location',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	) as $lp_site ) {
		$lp_site_options[] = array(
			'value' => (string) $lp_site->ID,
			'label' => get_the_title( $lp_site ),
		);
	}

	return array(
		array(
			'type'        => 'search',
			'key'         => 'SEARCH',
			'name'        => 'class_search',
			'placeholder' => 'Class, coach or site',
			'value'       => (string) ( $lp_current['class_search'] ?? '' ),
		),
		array(
			'type'    => 'select',
			'key'     => 'CLASS TYPE',
			'name'    => 'class_level',
			'options' => $lp_level_options,
			'value'   => (string) ( $lp_current['class_level'] ?? '' ),
		),
		array(
			'type'    => 'select',
			'key'     => 'SITE',
			'name'    => 'class_site',
			'options' => $lp_site_options,
			'value'   => (string) ( $lp_current['class_site'] ?? '' ),
		),
	);
}

/**
 * WHEN fact for an agenda card: "18:30 – 20:00" from start time + duration.
 *
 * @param string $lp_time     H:i.
 * @param int    $lp_class_id Class post ID (for duration).
 */
function lp_agenda_when_label( string $lp_time, int $lp_class_id ): string {
	if ( '' === $lp_time ) {
		return '';
	}

	$lp_minutes = (int) filter_var( lp_class_duration( $lp_class_id ), FILTER_SANITIZE_NUMBER_INT );
	if ( ! $lp_minutes ) {
		return $lp_time;
	}

	$lp_start = DateTimeImmutable::createFromFormat( 'H:i', $lp_time );
	if ( ! $lp_start ) {
		return $lp_time;
	}

	return $lp_start->format( 'H:i' ) . ' – ' . $lp_start->modify( sprintf( '+%d minutes', $lp_minutes ) )->format( 'H:i' );
}

/**
 * Attachment ID for a seeded demo-media filename (e.g. DSC01072.jpeg).
 *
 * @param string $lp_filename Basename under bin/demo-media/.
 */
function lp_demo_media_id( string $lp_filename ): int {
	$lp_slug = sanitize_title( pathinfo( $lp_filename, PATHINFO_FILENAME ) );
	if ( '' === $lp_slug ) {
		return 0;
	}

	$lp_ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'name'           => $lp_slug,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return (int) ( $lp_ids[0] ?? 0 );
}

/**
 * Every session in one week, grouped by day, for the Agenda board.
 *
 * Sessions come from clasbpro via lp_class_sessions_between() — active classes
 * only, dated occurrences in the requested Monday–Sunday window.
 *
 * @param int $lp_offset Weeks from the current one. Negative is the past.
 * @return array start, end, week number, day groups and a session count.
 */
function lp_agenda_week( int $lp_offset = 0 ): array {
	$lp_start = ( new DateTimeImmutable( 'monday this week' ) )->modify( sprintf( '%+d weeks', $lp_offset ) );
	$lp_end   = $lp_start->modify( '+6 days' );

	// Keyed by Y-m-d so the seven day buckets stay in calendar order.
	$lp_days = array();
	for ( $lp_i = 0; $lp_i < 7; $lp_i++ ) {
		$lp_days[ $lp_start->modify( sprintf( '+%d days', $lp_i ) )->format( 'Y-m-d' ) ] = array();
	}

	$lp_count = 0;
	foreach ( lp_class_sessions_between( $lp_start, $lp_end ) as $lp_row ) {
		$lp_date = (string) ( $lp_row['date'] ?? '' );

		if ( ! isset( $lp_days[ $lp_date ] ) ) {
			continue;
		}

		$lp_class_id = (int) ( $lp_row['id'] ?? 0 );
		$lp_location = (string) ( $lp_row['location'] ?? '' );
		$lp_level    = (string) ( $lp_row['level'] ?? '' );
		$lp_time     = (string) ( $lp_row['time'] ?? '' );

		$lp_days[ $lp_date ][] = array(
			'time'       => $lp_time,
			// BoardRow's own per-row date label is left blank: the day is
			// already announced by the band above it, and repeating it is
			// redundant. The source makes the same call.
			'date_label' => '',
			'title'      => (string) ( $lp_row['title'] ?? '' ),
			'subtitle'   => (string) ( $lp_row['subtitle'] ?? '' ),
			'location'   => $lp_location,
			'level'      => $lp_level,
			'spaces'     => (string) ( $lp_row['spaces'] ?? '' ),
			'sold_out'   => ! empty( $lp_row['sold_out'] ),
			'href'       => (string) ( $lp_row['url'] ?? '' ),
			'class_id'   => $lp_class_id,
			// Cards board fields (O6Fhqs) — kept alongside the row shape.
			'thumb'      => (int) ( $lp_row['thumb'] ?? 0 ),
			'price'      => (string) ( $lp_row['price'] ?? '' ),
			'coaches'    => (string) ( $lp_row['coaches'] ?? '' ),
			'when'       => lp_agenda_when_label( $lp_time, $lp_class_id ),
			'kicker'     => '' !== $lp_location ? strtoupper( $lp_location ) : strtoupper( $lp_level ),
			'past'       => ! lp_class_session_is_future( $lp_row ),
		);
		++$lp_count;
	}

	$lp_groups = array();
	foreach ( $lp_days as $lp_date => $lp_sessions ) {
		usort( $lp_sessions, static fn( $lp_a, $lp_b ): int => strcmp( $lp_a['time'], $lp_b['time'] ) );
		$lp_day = new DateTimeImmutable( $lp_date );

		$lp_groups[] = array(
			'iso'      => $lp_date,
			'day'      => strtoupper( $lp_day->format( 'D' ) ),
			'date'     => strtoupper( $lp_day->format( 'j F Y' ) ),
			'sessions' => $lp_sessions,
		);
	}

	return array(
		'start'  => $lp_start,
		'end'    => $lp_end,
		'week'   => (int) $lp_start->format( 'W' ),
		'days'   => $lp_groups,
		'count'  => $lp_count,
		'offset' => $lp_offset,
	);
}

/**
 * The design's week label: "Week 29 · 13th – 20th July 2026".
 *
 * @param array $lp_week A lp_agenda_week() result.
 * @return string
 */
function lp_agenda_week_label( array $lp_week ): string {
	// The design's example week sits inside one month, so it names the month
	// once. A week that straddles two needs both, or "27th – 2nd August" reads
	// as though the 27th were in August.
	$lp_same_month = $lp_week['start']->format( 'F' ) === $lp_week['end']->format( 'F' );

	return sprintf(
		'Week %d · %s – %s',
		$lp_week['week'],
		$lp_week['start']->format( $lp_same_month ? 'jS' : 'jS F' ),
		$lp_week['end']->format( 'jS F Y' )
	);
}
