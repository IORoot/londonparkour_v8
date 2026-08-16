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
 * - `class_site` is a post id, because a class's `acf_location` is an ACF
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
 * Apply the Classes filter grid to the clasbpro_class archive's main query.
 *
 * @param WP_Query $lp_query The query being prepared.
 */
function lp_filter_class_archive( WP_Query $lp_query ): void {
	if ( is_admin() || ! $lp_query->is_main_query() || ! $lp_query->is_post_type_archive( lp_class_post_type() ) ) {
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

	$lp_meta = array();

	if ( $lp_site ) {
		$lp_meta[] = array(
			'key'   => 'acf_location',
			'value' => $lp_site,
		);
	}

	// Inactive clasbpro classes stay out of the public archive.
	$lp_active = array(
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

	if ( $lp_meta ) {
		$lp_query->set(
			'meta_query',
			array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				$lp_meta,
				$lp_active,
			)
		);
	} else {
		$lp_query->set( 'meta_query', $lp_active ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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

/**
 * Apply the Tutorials filter grid to the lp_tutorial archive's main query.
 *
 * Same rationale as lp_filter_class_archive() above: `tutorial_search`, not
 * `s`, for the same is_search() reason. `tutorial_category` is a
 * `tutorial-category` term slug — parent (Vaulting) or child (Step-Vault).
 * Parents roll up their moves via `include_children`. `tutorial_series` is
 * an `lp_series` term slug. `tutorial_tag` is a `tutorial-tag` slug
 * (challenge, demonstration, tutorial). Filters AND together when more
 * than one is set.
 *
 * @param WP_Query $lp_query The query being prepared.
 */
function lp_filter_tutorial_archive( WP_Query $lp_query ): void {
	if ( is_admin() || ! $lp_query->is_main_query() || ! $lp_query->is_post_type_archive( 'lp_tutorial' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter, see the file docblock.
	$lp_search   = isset( $_GET['tutorial_search'] ) ? sanitize_text_field( wp_unslash( $_GET['tutorial_search'] ) ) : '';
	$lp_category = isset( $_GET['tutorial_category'] ) ? sanitize_title( wp_unslash( $_GET['tutorial_category'] ) ) : '';
	$lp_series   = isset( $_GET['tutorial_series'] ) ? sanitize_title( wp_unslash( $_GET['tutorial_series'] ) ) : '';
	$lp_tag      = isset( $_GET['tutorial_tag'] ) ? sanitize_title( wp_unslash( $_GET['tutorial_tag'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' !== $lp_search ) {
		$lp_query->set( 's', $lp_search );
	}

	$lp_tax = array();

	if ( '' !== $lp_category ) {
		$lp_tax[] = array(
			'taxonomy'         => 'tutorial-category',
			'field'            => 'slug',
			'terms'            => $lp_category,
			'include_children' => true,
		);
	}

	if ( '' !== $lp_series ) {
		$lp_tax[] = array(
			'taxonomy' => 'lp_series',
			'field'    => 'slug',
			'terms'    => $lp_series,
		);
	}

	if ( '' !== $lp_tag ) {
		$lp_tax[] = array(
			'taxonomy' => 'tutorial-tag',
			'field'    => 'slug',
			'terms'    => $lp_tag,
		);
	}

	if ( $lp_tax ) {
		if ( count( $lp_tax ) > 1 ) {
			$lp_tax = array_merge( array( 'relation' => 'AND' ), $lp_tax );
		}
		$lp_query->set( 'tax_query', $lp_tax ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$lp_query->set( 'lp_natural_order', true );
	$lp_query->set( 'posts_per_page', 120 );
}
add_action( 'pre_get_posts', 'lp_filter_tutorial_archive' );

/**
 * SQL for category → series → curriculum order.
 *
 * Used by the public archive, series/category boards, and the admin list
 * when `lp_natural_order` is set on the query. Child tutorial-category
 * wins over parent so Arm-Jump how-tos cluster; demonstrations sort last
 * inside a series (they have no order number).
 *
 * @param array<string, string> $clauses Query clauses.
 * @param WP_Query              $query   Current query.
 * @return array<string, string>
 */
function lp_tutorial_natural_order_clauses( array $clauses, WP_Query $query ): array {
	if ( ! $query->get( 'lp_natural_order' ) ) {
		return $clauses;
	}

	$post_type = $query->get( 'post_type' );
	if ( is_array( $post_type ) ) {
		if ( ! in_array( 'lp_tutorial', $post_type, true ) ) {
			return $clauses;
		}
	} elseif ( 'lp_tutorial' !== $post_type ) {
		return $clauses;
	}

	global $wpdb;

	$posts = $wpdb->posts;
	$tr    = $wpdb->term_relationships;
	$tt    = $wpdb->term_taxonomy;
	$t     = $wpdb->terms;
	$pm    = $wpdb->postmeta;

	$cat_child = "(SELECT MIN(t.name) FROM {$tr} tr INNER JOIN {$tt} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$t} t ON tt.term_id = t.term_id WHERE tr.object_id = {$posts}.ID AND tt.taxonomy = 'tutorial-category' AND tt.parent <> 0)";
	$cat_parent = "(SELECT MIN(t.name) FROM {$tr} tr INNER JOIN {$tt} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$t} t ON tt.term_id = t.term_id WHERE tr.object_id = {$posts}.ID AND tt.taxonomy = 'tutorial-category' AND tt.parent = 0)";
	$cat_sql    = "COALESCE({$cat_child}, {$cat_parent}, '')";

	$series_sql = "(SELECT MIN(t.name) FROM {$tr} tr INNER JOIN {$tt} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$t} t ON tt.term_id = t.term_id WHERE tr.object_id = {$posts}.ID AND tt.taxonomy = 'lp_series')";

	$tag_slug = "(SELECT t.slug FROM {$tr} tr INNER JOIN {$tt} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN {$t} t ON tt.term_id = t.term_id WHERE tr.object_id = {$posts}.ID AND tt.taxonomy = 'tutorial-tag' LIMIT 1)";
	$award    = "(SELECT meta_value FROM {$pm} WHERE post_id = {$posts}.ID AND meta_key = 'award_level' LIMIT 1)";
	$position = "(SELECT meta_value FROM {$pm} WHERE post_id = {$posts}.ID AND meta_key = 'order_position' LIMIT 1)";
	$num_sql  = "(CASE {$tag_slug} WHEN 'demonstration' THEN 10000 WHEN 'challenge' THEN IF({$award} IS NULL OR {$award} = '', 10000, CAST({$award} AS UNSIGNED)) ELSE IF({$position} IS NULL OR {$position} = '', 10000, CAST({$position} AS UNSIGNED)) END)";

	$clauses['orderby'] = "{$cat_sql} ASC, {$series_sql} ASC, {$num_sql} ASC, {$posts}.post_title ASC";

	return $clauses;
}
add_filter( 'posts_clauses', 'lp_tutorial_natural_order_clauses', 10, 2 );

/**
 * The Tutorials filter values currently in the URL, keyed by field name.
 *
 * @return array<string, string>
 */
function lp_tutorial_filter_values(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter, see the file docblock.
	return array(
		'tutorial_search'   => isset( $_GET['tutorial_search'] ) ? sanitize_text_field( wp_unslash( $_GET['tutorial_search'] ) ) : '',
		'tutorial_category' => isset( $_GET['tutorial_category'] ) ? sanitize_title( wp_unslash( $_GET['tutorial_category'] ) ) : '',
		'tutorial_series'   => isset( $_GET['tutorial_series'] ) ? sanitize_title( wp_unslash( $_GET['tutorial_series'] ) ) : '',
		'tutorial_tag'      => isset( $_GET['tutorial_tag'] ) ? sanitize_title( wp_unslash( $_GET['tutorial_tag'] ) ) : '',
	);
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

/**
 * Post types the search page lists, in `wsypA` tab order.
 *
 * The design's ARTICLES tab is native `post`; this site's articles are the
 * `blog` CPT (v7 import). Coaches, locations, support and notifications are
 * public but not on the designed rail — searching them would make ALL
 * disagree with the four tabs. The query-bar hint names these four kinds.
 *
 * @return array<string, array{tab: string, row: string, meta: string}>
 */
function lp_search_types(): array {
	$lp_articles = post_type_exists( 'blog' ) ? 'blog' : 'post';

	return array(
		lp_class_post_type() => array(
			'tab'  => 'CLASSES',
			'row'  => 'CLASS',
			'meta' => 'CLASSES',
		),
		'lp_tutorial'        => array(
			'tab'  => 'TUTORIALS',
			'row'  => 'LESSON',
			'meta' => 'TUTORIALS',
		),
		$lp_articles         => array(
			'tab'  => 'ARTICLES',
			'row'  => 'ARTICLE',
			'meta' => 'BLOG',
		),
		'page'               => array(
			'tab'  => 'PAGES',
			'row'  => 'PAGE',
			'meta' => 'PAGES',
		),
	);
}

/**
 * Constrain front-end search to the designed four kinds, 8 to a page.
 *
 * `pjHyS/h0BaW` is "SHOWING 01–08 OF 24 RESULTS". A `post_type` query arg
 * that matches a tab filters the main query; anything else (or none) is ALL.
 *
 * @param WP_Query $lp_query The query being prepared.
 */
function lp_filter_search( WP_Query $lp_query ): void {
	if ( is_admin() || ! $lp_query->is_main_query() || ! $lp_query->is_search() ) {
		return;
	}

	$lp_types     = array_keys( lp_search_types() );
	$lp_requested = '';
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter, see the file docblock.
	if ( isset( $_GET['post_type'] ) && is_string( $_GET['post_type'] ) ) {
		$lp_requested = sanitize_key( wp_unslash( $_GET['post_type'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( in_array( $lp_requested, $lp_types, true ) ) {
		$lp_query->set( 'post_type', $lp_requested );
	} else {
		$lp_query->set( 'post_type', $lp_types );
	}

	$lp_query->set( 'posts_per_page', 8 );
	$lp_query->set( 'ignore_sticky_posts', true );
}
add_action( 'pre_get_posts', 'lp_filter_search' );

/**
 * Posts page lists the `blog` CPT, 24 to a page.
 *
 * The v7 import writes `blog` and deletes native `post`, so the posts-page
 * main query is otherwise empty. home.php reads $wp_query->posts; a secondary
 * WP_Query cannot paginate (and used to cap the board at four).
 *
 * @param WP_Query $lp_query The query being prepared.
 */
function lp_filter_blog_home( WP_Query $lp_query ): void {
	if ( is_admin() || ! $lp_query->is_main_query() || ! $lp_query->is_home() ) {
		return;
	}

	if ( post_type_exists( 'blog' ) ) {
		$lp_query->set( 'post_type', 'blog' );
	}

	$lp_query->set( 'posts_per_page', 24 );
	$lp_query->set( 'ignore_sticky_posts', true );
}
add_action( 'pre_get_posts', 'lp_filter_blog_home' );

/**
 * `/blog/page/2/` is parsed as a `blog` CPT single named "page".
 *
 * The CPT rewrite slug is `blog`, the same as page_for_posts, so the more
 * specific CPT rule wins over the posts-page pagination rule. Send it back.
 *
 * @param array<string, mixed> $lp_vars Parsed request vars.
 * @return array<string, mixed>
 */
function lp_fix_blog_paged_request( array $lp_vars ): array {
	if ( ( $lp_vars['post_type'] ?? '' ) !== 'blog' ) {
		return $lp_vars;
	}

	if ( ( $lp_vars['name'] ?? '' ) !== 'page' ) {
		return $lp_vars;
	}

	$lp_page = isset( $lp_vars['page'] ) ? absint( $lp_vars['page'] ) : 0;
	if ( $lp_page < 2 ) {
		return $lp_vars;
	}

	unset( $lp_vars['post_type'], $lp_vars['name'], $lp_vars['blog'] );
	$lp_vars['pagename'] = 'blog';
	$lp_vars['paged']    = $lp_page;
	unset( $lp_vars['page'] );

	return $lp_vars;
}
add_filter( 'request', 'lp_fix_blog_paged_request' );
