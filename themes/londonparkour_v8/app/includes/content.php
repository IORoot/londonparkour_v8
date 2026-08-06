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
 * The three Classes view-rail tabs, shared by Agenda, Listings and Map.
 *
 * Ported from ClassesHeaderCluster.js's `TABS`, whose own note says the three
 * are identical on every page that has one and only `active` changes. The
 * metas were static strings there (18 SESSIONS / 13 CLASS TYPES / 6 SITES);
 * here they are counted, because on a real site they would otherwise be wrong
 * the first time an editor adds a class.
 *
 * ponytail: the session count reads the `sessions` repeater per class, so it
 * is one ACF call per published class — fine at a dozen. Denormalise it into
 * a transient if the class list ever runs to hundreds.
 *
 * @param string $lp_active agenda|listings|map.
 * @return array Tabs in view-rail.php's shape.
 */
function lp_classes_view_tabs( string $lp_active = 'listings' ): array {
	$lp_class_ids = get_posts(
		array(
			'post_type'      => 'lp_class',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$lp_sessions = 0;
	foreach ( $lp_class_ids as $lp_id ) {
		$lp_rows      = function_exists( 'get_field' ) ? get_field( 'sessions', $lp_id ) : null;
		$lp_sessions += is_array( $lp_rows ) ? count( $lp_rows ) : 0;
	}

	$lp_sites = (int) ( wp_count_posts( 'lp_location' )->publish ?? 0 );

	return array(
		array(
			'label'   => 'AGENDA',
			'meta'    => sprintf( '%d SESSIONS', $lp_sessions ),
			'icon_id' => 'icon-calendar-days',
			'href'    => home_url( '/classes/agenda' ),
			'active'  => 'agenda' === $lp_active,
		),
		array(
			'label'   => 'LISTINGS',
			'meta'    => sprintf( '%d CLASS TYPES', count( $lp_class_ids ) ),
			'icon_id' => 'icon-squares-2x2',
			'href'    => (string) get_post_type_archive_link( 'lp_class' ),
			'active'  => 'listings' === $lp_active,
		),
		array(
			'label'   => 'MAP',
			'meta'    => sprintf( '%d SITES', $lp_sites ),
			'icon_id' => 'icon-map-pin',
			'href'    => home_url( '/classes/map' ),
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
 * `location` post_object field points at.
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
