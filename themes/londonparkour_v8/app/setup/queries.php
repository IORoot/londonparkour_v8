<?php
/**
 * Front-end query filters driven by the Concourse filter grid.
 *
 * The filter grid submits a plain GET form (parts/components/filter-grid.php),
 * so every filter is a URL and every filtered view is shareable. This file is
 * where those parameters reach the main query.
 *
 * **The parameters are this theme's own, not WordPress's, deliberately.**
 *
 * - `class_search`, not `s`. A `?s=` on any URL makes `is_search()` true at
 *   parse_query time, so WordPress would route `/classes/?s=foo` to search.php
 *   and the Classes archive template would never run. Setting `s` here instead,
 *   from pre_get_posts, filters the archive without touching the conditional
 *   flags — which is exactly why this hook is the right place for it.
 * - `class_level`, not the public `lp_level` taxonomy query var. Mixing a
 *   taxonomy var into a post-type-archive URL muddies which archive the query
 *   is, and the three cells behave identically this way.
 * - `class_site` is a post id, because a class's `location` is an ACF
 *   post_object pointing at an `lp_location` — not a taxonomy term.
 *
 * No nonce: these are public, read-only navigation parameters on published
 * content. Sanitisation at the boundary is the guard, and a nonce on a
 * shareable URL would break the sharing.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apply the Classes filter grid to the lp_class archive's main query.
 *
 * @param WP_Query $lp_query The query being prepared.
 */
function lp_filter_class_archive( WP_Query $lp_query ): void {
	if ( is_admin() || ! $lp_query->is_main_query() || ! $lp_query->is_post_type_archive( 'lp_class' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter, see the file docblock.
	$lp_search = isset( $_GET['class_search'] ) ? sanitize_text_field( wp_unslash( $_GET['class_search'] ) ) : '';
	$lp_level  = isset( $_GET['class_level'] ) ? sanitize_title( wp_unslash( $_GET['class_level'] ) ) : '';
	$lp_site   = isset( $_GET['class_site'] ) ? absint( $_GET['class_site'] ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' !== $lp_search ) {
		$lp_query->set( 's', $lp_search );
	}

	if ( '' !== $lp_level ) {
		$lp_query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'lp_level',
					'field'    => 'slug',
					'terms'    => $lp_level,
				),
			)
		);
	}

	if ( $lp_site ) {
		$lp_query->set(
			'meta_query',
			array(
				array(
					'key'   => 'location',
					'value' => $lp_site,
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'lp_filter_class_archive' );

/**
 * The Classes filter values currently in the URL, keyed by field name.
 *
 * Read by the header cluster so each control renders showing what is applied.
 * Sanitised identically to the query filter above — one boundary, one set of
 * rules.
 *
 * @return array<string, string>
 */
function lp_class_filter_values(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter, see the file docblock.
	return array(
		'class_search' => isset( $_GET['class_search'] ) ? sanitize_text_field( wp_unslash( $_GET['class_search'] ) ) : '',
		'class_level'  => isset( $_GET['class_level'] ) ? sanitize_title( wp_unslash( $_GET['class_level'] ) ) : '',
		'class_site'   => isset( $_GET['class_site'] ) ? (string) absint( $_GET['class_site'] ) : '',
	);
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}
