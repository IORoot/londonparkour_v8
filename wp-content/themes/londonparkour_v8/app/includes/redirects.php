<?php
/**
 * V7 → V8 URL map.
 *
 * The repo used to ship a `docs/` folder of developer markdown at the web
 * root. On Cloudways that folder *was* `/docs/` and nginx 403'd it, which
 * also hid the WordPress Docs wiki. Markdown now lives in the theme at
 * `wp-content/themes/londonparkour_v8/docs/` (not a public URL). This file
 * only removes a leftover empty web-root `docs/` directory — it never
 * publishes that folder.
 *
 * 301s are from the live V7 sitemap + GSC pages + seed-page crawl (2026-09-11).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exact path (no leading/trailing slash) → destination path or absolute URL.
 *
 * Destinations starting with `/` are passed through home_url(). http(s) URLs
 * are external. Dynamic prefixes (`tutorial/`, `pulse/`, class clones) are
 * handled in lp_v7_redirects(), not here.
 *
 * @return array<string, string>
 */
function lp_v7_exact_redirects(): array {
	$classes = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' );
	$map     = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes-map' ) : home_url( '/classes-map/' );
	$about   = home_url( '/about/' );
	$blog    = function_exists( 'lp_docs_blog_url' ) ? lp_docs_blog_url() : home_url( '/blog/' );
	$docs    = function_exists( 'lp_docs_url' ) ? lp_docs_url() : home_url( '/docs/' );
	$private = function_exists( 'lp_private_coaching_url' ) ? lp_private_coaching_url() : home_url( '/private-coaching/' );
	$coupons = home_url( '/coupons/' );
	$contact = home_url( '/contact/' );
	$gift    = function_exists( 'lp_docs_gift_cards_url' ) ? lp_docs_gift_cards_url() : home_url( '/docs/gift-cards/' );

	$about_page = get_page_by_path( 'about' );
	if ( $about_page instanceof WP_Post ) {
		$about = (string) get_permalink( $about_page );
	}

	return array(
		'classes/map'                => $map,
		'studio'                     => $about,
		'team'                       => $about,
		'map'                        => $map,
		'class-locations'            => $map,
		'pt'                         => $private,
		'bookings'                   => $classes,
		'booking'                    => $classes,
		'book'                       => $classes,
		'booking-test-class-bookings-for-stripe-form' => $classes,
		'giftcards'                  => $gift,
		'products'                   => $coupons,
		'promotions'                 => $coupons,
		'courses'                    => $classes,
		'course'                     => $classes,
		'support'                    => $docs,
		'pricing'                    => home_url( '/docs/pricing/' ),
		'feedback'                   => $contact,
		'pulse'                      => $blog,
		'pulse_grid'                 => $blog,
		'tutorials-todo'             => home_url( '/tutorials/' ),
		'whatsapp'                   => $contact,
		'instagram'                  => 'https://www.instagram.com/london_parkour/',
		'youtube'                    => 'https://www.youtube.com/londonparkour',
		'reviews'                    => 'https://g.page/r/CaEUXmf0e4IHEAE/review',
		'review'                     => 'https://g.page/r/CaEUXmf0e4IHEAE/review',
		'blogs/article'              => home_url( '/blog-category/article/' ),
		'blogs/project'              => home_url( '/blog-category/project/' ),
		'blogs/update'               => home_url( '/blog-category/update/' ),
		'supports/classes'           => home_url( '/support-category/classes/' ),
		'supports/company'           => home_url( '/support-category/company/' ),
		'supports/website'           => home_url( '/support-category/website/' ),
	);
}

/**
 * Retired V7 class slugs (and dated clones) → current clasbpro slug.
 *
 * @return array<string, string>
 */
function lp_v7_class_slug_aliases(): array {
	return array(
		'outdoor-class-old-street' => 'adult-beginners-outdoor',
		'outdoor-class-east'       => 'adult-beginners-outdoor',
		'beginners-parkour'        => 'adult-beginners-outdoor',
		'evening-outdoor-class'    => 'evening-intermediate-outdoor',
		'outdoor-class-southbank'  => 'evening-intermediate-outdoor',
		'outdoor-class-north'      => 'adult-outdoor-north',
		'teens-class-west-10-14s'  => 'youth-class-west-10-14s',
		'youth-class-east'         => 'youth-class-west-10-14s',
	);
}

/**
 * 301 helper. Allows a small host allowlist for the live social/review URLs.
 *
 * @param string $to   Absolute URL or site-relative path.
 * @param int    $code HTTP status.
 */
function lp_redirect_to( string $to, int $code = 301 ): void {
	if ( '' === $to ) {
		return;
	}
	if ( 0 === strpos( $to, '/' ) && 0 !== strpos( $to, '//' ) ) {
		$to = home_url( $to );
	}

	$host = wp_parse_url( $to, PHP_URL_HOST );
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( is_string( $host ) && is_string( $home ) && 0 !== strcasecmp( $host, $home ) ) {
		add_filter(
			'allowed_redirect_hosts',
			static function ( array $hosts ) use ( $host ): array {
				$hosts[] = $host;
				return $hosts;
			}
		);
	}

	wp_safe_redirect( $to, $code );
	exit;
}

/**
 * Strip a WordPress duplicate-slug suffix (`-2`, `-6`) for alias lookup.
 *
 * @param string $slug Class slug.
 */
function lp_v7_class_slug_base( string $slug ): string {
	return (string) preg_replace( '/-\d+$/', '', $slug );
}

/**
 * Map a V7 class slug to a V8 permalink, or null if this slug should 200.
 *
 * @param string $slug Path segment after /classes/.
 */
function lp_v7_class_redirect_url( string $slug ): ?string {
	$type = function_exists( 'lp_class_post_type' ) ? lp_class_post_type() : 'clasbpro_class';
	if ( get_page_by_path( $slug, OBJECT, $type ) instanceof WP_Post ) {
		return null;
	}

	$base = lp_v7_class_slug_base( $slug );

	if ( 'outdoor-class-vauxhall' === $base ) {
		return home_url( '/classes/locations/vauxhall/' );
	}

	$aliases = lp_v7_class_slug_aliases();
	$mapped  = $aliases[ $base ] ?? ( $base !== $slug ? $base : null );
	if ( ! is_string( $mapped ) || '' === $mapped ) {
		return null;
	}

	$post = get_page_by_path( $mapped, OBJECT, $type );
	if ( $post instanceof WP_Post ) {
		return (string) get_permalink( $post );
	}

	return home_url( '/classes/' . $mapped . '/' );
}

/**
 * Tutorial permalink if that slug is a published lp_tutorial.
 *
 * @param string $slug Tutorial slug.
 */
function lp_v7_tutorial_url( string $slug ): ?string {
	$post = get_page_by_path( $slug, OBJECT, 'lp_tutorial' );
	if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
		return (string) get_permalink( $post );
	}
	return null;
}

/**
 * Tutorial-category permalink if the slug is a term.
 *
 * @param string $slug Term slug.
 */
function lp_v7_tutorial_category_url( string $slug ): ?string {
	if ( ! taxonomy_exists( 'tutorial-category' ) ) {
		return null;
	}
	$term = get_term_by( 'slug', $slug, 'tutorial-category' );
	if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		return is_string( $link ) ? $link : null;
	}
	return null;
}

/**
 * Fire 301s for V7 paths that do not exist (or changed) on V8.
 */
function lp_v7_redirects(): void {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	$request = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );
	if ( '' === $request ) {
		return;
	}

	$exact = lp_v7_exact_redirects();
	if ( isset( $exact[ $request ] ) ) {
		lp_redirect_to( $exact[ $request ] );
	}

	if ( preg_match( '#^landing(?:/.*)?$#', $request ) ) {
		lp_redirect_to( function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' ) );
	}

	if ( preg_match( '#^course/.+#', $request ) ) {
		lp_redirect_to( function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' ) );
	}

	if ( preg_match( '#^pulse/.+#', $request ) ) {
		$blog = function_exists( 'lp_docs_blog_url' ) ? lp_docs_blog_url() : home_url( '/blog/' );
		lp_redirect_to( $blog );
	}

	if ( preg_match( '#^tutorial/([^/]+)$#', $request, $lp_m ) ) {
		$to = lp_v7_tutorial_url( $lp_m[1] );
		lp_redirect_to( $to ? $to : home_url( '/tutorials/' ) );
	}

	if ( preg_match( '#^tutorials/(.+)$#', $request, $lp_m ) ) {
		$rest = trim( $lp_m[1], '/' );
		if ( in_array( $rest, array( 'series', 'category' ), true ) ) {
			return;
		}
		if ( preg_match( '#^page/\d+$#', $rest ) ) {
			return;
		}
		$parts = array_values( array_filter( explode( '/', $rest ) ) );
		if ( count( $parts ) >= 2 ) {
			$leaf = (string) end( $parts );
			$to   = lp_v7_tutorial_category_url( $leaf );
			if ( ! $to && isset( $parts[0] ) ) {
				$to = lp_v7_tutorial_category_url( $parts[0] );
			}
			lp_redirect_to( $to ? $to : home_url( '/tutorials/' ) );
		}
		if ( 1 === count( $parts ) ) {
			if ( lp_v7_tutorial_url( $parts[0] ) ) {
				return;
			}
			$to = lp_v7_tutorial_category_url( $parts[0] );
			if ( $to ) {
				lp_redirect_to( $to );
			}
		}
	}

	if ( preg_match( '#^classes/([^/]+)$#', $request, $lp_m ) ) {
		$to = lp_v7_class_redirect_url( $lp_m[1] );
		if ( $to ) {
			lp_redirect_to( $to );
		}
	}

	if ( preg_match( '#^support/([^/]+)$#', $request, $lp_m ) ) {
		$post = function_exists( 'lp_docs_find_support' )
			? lp_docs_find_support( array( $lp_m[1] ) )
			: get_page_by_path( $lp_m[1], OBJECT, 'support' );
		if ( $post instanceof WP_Post ) {
			lp_redirect_to( (string) get_permalink( $post ) );
		} else {
			lp_redirect_to( function_exists( 'lp_docs_url' ) ? lp_docs_url() : home_url( '/docs/' ) );
		}
	}
}
add_action( 'template_redirect', 'lp_v7_redirects', 1 );

/**
 * Leftover web-root folders that are developer files, not public URLs.
 * Only remove an empty directory (or one that only has our old helper files).
 */
function lp_docs_remove_leftover_webroot_dir(): void {
	$names = array( 'docs', 'staging.londonparkour.com-audit' );
	$ignorable = array( '.', '..', 'index.php', '.htaccess', '.lp-docs-unshadow' );

	foreach ( $names as $name ) {
		$dir = untrailingslashit( ABSPATH ) . '/' . $name;
		if ( ! is_dir( $dir ) ) {
			continue;
		}

		$entries = @scandir( $dir );
		if ( ! is_array( $entries ) ) {
			continue;
		}

		$empty = true;
		foreach ( $entries as $entry ) {
			if ( ! in_array( $entry, $ignorable, true ) ) {
				$empty = false;
				break;
			}
		}
		if ( ! $empty ) {
			continue;
		}

		foreach ( array( 'index.php', '.htaccess', '.lp-docs-unshadow' ) as $file ) {
			$path = $dir . '/' . $file;
			if ( is_file( $path ) ) {
				@unlink( $path );
			}
		}
		@rmdir( $dir );
	}
}
add_action( 'init', 'lp_docs_remove_leftover_webroot_dir', 0 );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'lp redirects',
		static function (): void {
			foreach ( lp_v7_exact_redirects() as $from => $to ) {
				WP_CLI::log( '/' . $from . '/ → ' . $to );
			}
			WP_CLI::log( '/tutorial/{slug}/ → /tutorials/{slug}/ (or /tutorials/)' );
			WP_CLI::log( '/tutorials/{category}/… → /tutorial-category/{term}/' );
			WP_CLI::log( '/classes/{v7-slug}/ → mapped clasbpro class' );
			WP_CLI::log( '/support/{slug}/ → /docs/{slug}/' );
			WP_CLI::log( '/pulse/{slug}/ → /blog/' );
			WP_CLI::log( '/landing/… → /classes/' );
			$dir = untrailingslashit( ABSPATH ) . '/docs';
			WP_CLI::log( 'ABSPATH/docs exists: ' . ( is_dir( $dir ) ? 'yes' : 'no' ) );
		}
	);
}
