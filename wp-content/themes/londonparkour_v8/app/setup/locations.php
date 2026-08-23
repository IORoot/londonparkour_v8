<?php
/**
 * Site location permalinks, archive query, and spot 404s.
 *
 * Public sites live at `/classes/locations/{slug}/`. The clasbpro class CPT
 * already owns `/classes/{slug}/`, so location rules are registered `top` and
 * the rewrite slug is forced here even if ACF JSON is stale. Map-only spots
 * stay off the front-end (404, no archive, no sitemap). Old `/locations/{slug}/`
 * URLs 301 to the new path for sites.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public rewrite slug for class-site location pages.
 */
function lp_location_rewrite_slug(): string {
	return 'classes/locations';
}

/**
 * Force the location CPT onto /classes/locations/ regardless of ACF JSON.
 *
 * @param array  $args      register_post_type args.
 * @param string $post_type Post type name.
 * @return array
 */
function lp_location_post_type_args( array $args, string $post_type ): array {
	if ( 'lp_location' !== $post_type ) {
		return $args;
	}

	$slug = lp_location_rewrite_slug();

	$args['public']             = true;
	$args['publicly_queryable'] = true;
	$args['has_archive']        = $slug;
	$args['rewrite']            = array(
		'slug'       => $slug,
		'with_front' => false,
		'feeds'      => false,
		'pages'      => true,
	);

	return $args;
}
add_filter( 'register_post_type_args', 'lp_location_post_type_args', 20, 2 );

/**
 * Beat clasbpro's `classes/{slug}` rule and keep legacy /locations/{slug}/ resolving.
 */
function lp_location_rewrite_rules(): void {
	$slug = lp_location_rewrite_slug();

	add_rewrite_rule( '^' . $slug . '/?$', 'index.php?post_type=lp_location', 'top' );
	add_rewrite_rule( '^' . $slug . '/page/([0-9]{1,})/?$', 'index.php?post_type=lp_location&paged=$matches[1]', 'top' );
	add_rewrite_rule( '^' . $slug . '/([^/]+)/?$', 'index.php?lp_location=$matches[1]', 'top' );
	add_rewrite_rule( '^locations/([^/]+)/?$', 'index.php?lp_location=$matches[1]', 'top' );
}
add_action( 'init', 'lp_location_rewrite_rules', 11 );

/**
 * Flush rewrites once after the location slug moved under /classes/locations/.
 */
function lp_location_maybe_flush_rewrites(): void {
	$flag = 'lp_location_rewrite_v1';
	if ( get_option( $flag ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( $flag, 1, true );
}
add_action( 'init', 'lp_location_maybe_flush_rewrites', 99 );

/**
 * Meta query that keeps only class sites (kind=site, or the field missing).
 *
 * @return array<int|string, mixed>
 */
function lp_location_site_meta_query(): array {
	return array(
		'relation' => 'OR',
		array(
			'key'     => 'location_kind',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'   => 'location_kind',
			'value' => 'site',
		),
	);
}

/**
 * Location archive lists sites only.
 *
 * @param WP_Query $query The query being prepared.
 */
function lp_filter_location_archive( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'lp_location' ) ) {
		return;
	}

	$query->set( 'posts_per_page', 50 );
	$query->set( 'orderby', 'menu_order title' );
	$query->set( 'order', 'ASC' );
	$query->set( 'meta_query', lp_location_site_meta_query() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
}
add_action( 'pre_get_posts', 'lp_filter_location_archive' );

/**
 * Spots are map-only: 404 the singular. Sites on the old /locations/{slug}/ path 301.
 */
function lp_location_template_redirect(): void {
	if ( is_admin() ) {
		return;
	}

	$request = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );
	if ( 'locations' === $request ) {
		$archive = get_post_type_archive_link( 'lp_location' );
		if ( is_string( $archive ) && '' !== $archive ) {
			wp_safe_redirect( $archive, 301 );
			exit;
		}
	}

	if ( ! is_singular( 'lp_location' ) ) {
		return;
	}

	$id = (int) get_queried_object_id();
	if ( $id < 1 ) {
		return;
	}

	$kind = function_exists( 'lp_location_kind' ) ? lp_location_kind( $id ) : 'site';
	if ( 'spot' === $kind ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}

	$request = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );
	if ( preg_match( '#^locations/([^/]+)$#', $request ) ) {
		$permalink = get_permalink( $id );
		if ( is_string( $permalink ) && '' !== $permalink ) {
			wp_safe_redirect( $permalink, 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'lp_location_template_redirect', 8 );

/**
 * Spots 404. Stop WordPress guessing a canonical permalink for them
 * (otherwise /locations/{spot}/ 301s to /classes/locations/{spot}/ then 404s).
 *
 * @param string|false $redirect_url  Canonical URL, or false to skip.
 * @param string       $requested_url Requested URL.
 * @return string|false
 */
function lp_location_redirect_canonical( $redirect_url, $_requested_url ) {
	$post = get_queried_object();
	if ( ! ( $post instanceof WP_Post ) || 'lp_location' !== $post->post_type ) {
		return $redirect_url;
	}

	if ( function_exists( 'lp_location_kind' ) && 'spot' === lp_location_kind( (int) $post->ID ) ) {
		return false;
	}

	return $redirect_url;
}
add_filter( 'redirect_canonical', 'lp_location_redirect_canonical', 10, 2 );

/**
 * Keep map-only spots out of the location sitemap.
 *
 * @param array  $args      WP_Query args for the sitemap.
 * @param string $post_type Post type being listed.
 * @return array
 */
function lp_location_sitemap_query_args( array $args, string $post_type ): array {
	if ( 'lp_location' !== $post_type ) {
		return $args;
	}

	$meta = isset( $args['meta_query'] ) && is_array( $args['meta_query'] )
		? $args['meta_query']
		: array();

	$args['meta_query'] = $meta // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		? array(
			'relation' => 'AND',
			$meta,
			lp_location_site_meta_query(),
		)
		: lp_location_site_meta_query();

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'lp_location_sitemap_query_args', 10, 2 );
