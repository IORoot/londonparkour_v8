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
