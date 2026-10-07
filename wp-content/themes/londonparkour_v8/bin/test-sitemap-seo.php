<?php
/**
 * Sitemap, canonical, robots and class-redirect contract.
 *
 * Run: bin/wp eval-file bin/test-sitemap-seo.php
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_fail = 0;

$lp_assert = static function ( bool $ok, string $msg ) use ( &$lp_fail ): void {
	if ( $ok ) {
		WP_CLI::log( "ok  $msg" );
		return;
	}
	++$lp_fail;
	WP_CLI::warning( "FAIL $msg" );
};

$lp_home = wp_parse_url( home_url( '/' ) );
$lp_home = is_array( $lp_home ) ? $lp_home : array();

$lp_assert( lp_seo_legacy_yoast_sitemap_path( '/sitemap_index.xml' ), 'Yoast index is a legacy sitemap' );
$lp_assert( lp_seo_legacy_yoast_sitemap_path( '/page-sitemap.xml' ), 'Yoast page sitemap is legacy' );
$lp_assert( lp_seo_legacy_yoast_sitemap_path( '/classes-sitemap.xml' ), 'Yoast classes sitemap is legacy' );
$lp_assert( lp_seo_legacy_yoast_sitemap_path( '/tutorial-sitemap1.xml' ), 'Yoast tutorial part is legacy' );
$lp_assert( lp_seo_legacy_yoast_sitemap_path( '/blog_category-sitemap.xml' ), 'Yoast taxonomy sitemap is legacy' );
$lp_assert( ! lp_seo_legacy_yoast_sitemap_path( '/wp-sitemap.xml' ), 'core sitemap index is not legacy' );
$lp_assert( ! lp_seo_legacy_yoast_sitemap_path( '/wp-sitemap-posts-page-1.xml' ), 'core page sitemap is not legacy' );
$lp_assert( ! lp_seo_legacy_yoast_sitemap_path( '/classes/' ), 'a class URL is not a sitemap' );

$lp_aligned = wp_parse_url( lp_seo_align_url_to_home( 'http://dev.londonparkour.com/classes/adult-beginners-outdoor/?date=2026-10-01' ) );
$lp_aligned = is_array( $lp_aligned ) ? $lp_aligned : array();
$lp_assert( ( $lp_aligned['scheme'] ?? '' ) === ( $lp_home['scheme'] ?? '' ), 'aligned URL uses the home scheme' );
$lp_assert( ( $lp_aligned['host'] ?? '' ) === ( $lp_home['host'] ?? '' ), 'aligned URL uses the home host' );
$lp_assert( '/classes/adult-beginners-outdoor/' === ( $lp_aligned['path'] ?? '' ), 'aligned URL keeps the path' );

$lp_entry = lp_seo_sitemap_entry(
	array(
		'loc' => 'http://www.londonparkour.com/tutorials/cat-balance/?date=2026-01-01',
	)
);
$lp_assert( is_array( $lp_entry ) && false === strpos( (string) $lp_entry['loc'], '?' ), 'sitemap loc drops the date query' );
$lp_assert( is_array( $lp_entry ) && false === strpos( (string) $lp_entry['loc'], 'www.' ), 'sitemap loc drops www' );
$lp_assert( is_array( $lp_entry ) && false === strpos( (string) $lp_entry['loc'], 'dev.' ), 'sitemap loc drops dev' );
$lp_entry_parts = is_array( $lp_entry ) ? wp_parse_url( (string) $lp_entry['loc'] ) : array();
$lp_entry_parts = is_array( $lp_entry_parts ) ? $lp_entry_parts : array();
$lp_assert( ( $lp_entry_parts['scheme'] ?? '' ) === ( $lp_home['scheme'] ?? '' ), 'sitemap loc scheme matches home' );
$lp_assert( ( $lp_entry_parts['host'] ?? '' ) === ( $lp_home['host'] ?? '' ), 'sitemap loc host matches home' );

$lp_robots_in = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: http://dev.londonparkour.com/sitemap_index.xml\nSitemap: http://www.londonparkour.com/sitemap_index.xml\nSitemap: http://londonparkour.com/sitemap_index.xml\n";
$lp_robots    = lp_seo_robots_txt( $lp_robots_in, true );
preg_match_all( '/^Sitemap:\s*(\S+)\s*$/m', $lp_robots, $lp_sitemap_lines );
$lp_assert( 1 === count( $lp_sitemap_lines[1] ?? array() ), 'robots.txt advertises one sitemap' );
$lp_assert(
	( $lp_sitemap_lines[1][0] ?? '' ) === home_url( '/wp-sitemap.xml' ),
	'robots.txt sitemap is the core index'
);
$lp_assert( false === strpos( $lp_robots, 'dev.londonparkour' ), 'robots.txt does not name dev' );
$lp_assert( false === strpos( $lp_robots, 'www.' ), 'robots.txt does not name www' );
$lp_assert( false === strpos( $lp_robots, 'sitemap_index.xml' ), 'robots.txt does not name the Yoast index' );
$lp_private = lp_seo_robots_txt( "User-agent: *\nDisallow: /\n", false );
$lp_assert( false === strpos( $lp_private, 'Sitemap:' ), 'a private site does not advertise a sitemap' );

$lp_assert(
	in_array( 'legal', lp_seo_utility_page_slugs(), true ) && in_array( 'blocks-qa', lp_seo_utility_page_slugs(), true ),
	'redirecting and utility pages share one exclusion list'
);
$lp_assert(
	'outdoor-class-old-street' === lp_v7_class_slug_base( 'outdoor-class-old-street-4' ),
	'dated class slug strips the numeric suffix'
);
$lp_aliases = lp_v7_class_slug_aliases();
$lp_assert(
	'adult-beginners-outdoor' === ( $lp_aliases['outdoor-class-old-street'] ?? '' ),
	'old street maps to the evergreen beginners class'
);
$lp_existing_clone = get_page_by_path( 'outdoor-class-old-street-4', OBJECT, function_exists( 'lp_class_post_type' ) ? lp_class_post_type() : 'clasbpro_class' );
if ( $lp_existing_clone instanceof WP_Post ) {
	WP_CLI::log( 'skip dated old-street slug exists as its own class' );
} else {
	$lp_old_street = lp_v7_class_redirect_url( 'outdoor-class-old-street-4' );
	$lp_assert(
		is_string( $lp_old_street ) && false !== strpos( $lp_old_street, '/classes/adult-beginners-outdoor' ),
		'dated old-street URL redirects to the evergreen class'
	);
}

$lp_registry = wp_sitemaps_get_server()->registry;
$lp_assert( ! $lp_registry->get_provider( 'users' ), 'author archives are not a sitemap provider' );
$lp_posts_provider = $lp_registry->get_provider( 'posts' );
$lp_post_types     = $lp_posts_provider ? array_keys( $lp_posts_provider->get_object_subtypes() ) : array();
$lp_assert( in_array( 'lp_landing', $lp_post_types, true ), 'landing pages are in the sitemap' );
$lp_assert( ! in_array( 'attachment', $lp_post_types, true ), 'attachments are not in the sitemap' );

$lp_locs = array();
foreach ( array( 'posts', 'taxonomies' ) as $lp_provider_name ) {
	$lp_provider = $lp_registry->get_provider( $lp_provider_name );
	if ( ! $lp_provider ) {
		continue;
	}
	foreach ( array_keys( $lp_provider->get_object_subtypes() ) as $lp_subtype ) {
		$lp_pages = (int) $lp_provider->get_max_num_pages( $lp_subtype );
		for ( $lp_page = 1; $lp_page <= $lp_pages; $lp_page++ ) {
			foreach ( $lp_provider->get_url_list( $lp_page, $lp_subtype ) as $lp_row ) {
				if ( is_array( $lp_row ) && ! empty( $lp_row['loc'] ) ) {
					$lp_locs[] = (string) $lp_row['loc'];
				}
			}
		}
	}
}

$lp_index = wp_sitemaps_get_server()->index->get_sitemap_list();
foreach ( $lp_index as $lp_row ) {
	if ( is_array( $lp_row ) && ! empty( $lp_row['loc'] ) ) {
		$lp_locs[] = (string) $lp_row['loc'];
	}
}

$lp_assert( count( $lp_locs ) === count( array_unique( $lp_locs ) ), 'sitemap locs are unique' );

$lp_landings = get_posts(
	array(
		'post_type'      => 'lp_landing',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
	)
);
foreach ( $lp_landings as $lp_landing ) {
	if ( ! $lp_landing instanceof WP_Post ) {
		continue;
	}
	$lp_landing_url = (string) get_permalink( $lp_landing );
	$lp_assert( in_array( $lp_landing_url, $lp_locs, true ), 'published landing is listed: ' . $lp_landing_url );
}

$lp_bad_paths = array(
	'/legal/',
	'/docs/class-locations/',
	'/docs/frequently-asked-questions/',
	'/docs-faq/',
);
$lp_bad_locs = array();
foreach ( $lp_locs as $lp_loc ) {
	$lp_parts = wp_parse_url( $lp_loc );
	$lp_parts = is_array( $lp_parts ) ? $lp_parts : array();
	$lp_path  = trailingslashit( (string) ( $lp_parts['path'] ?? '/' ) );
	$lp_why   = array();
	if ( ( $lp_parts['scheme'] ?? '' ) !== ( $lp_home['scheme'] ?? '' ) || ( $lp_parts['host'] ?? '' ) !== ( $lp_home['host'] ?? '' ) ) {
		$lp_why[] = 'origin';
	}
	if ( ! empty( $lp_parts['query'] ) ) {
		$lp_why[] = 'query';
	}
	if ( false !== strpos( $lp_loc, 'dev.londonparkour' ) || false !== strpos( $lp_loc, '://www.' ) ) {
		$lp_why[] = 'alias-host';
	}
	foreach ( $lp_bad_paths as $lp_bad ) {
		if ( $lp_path === trailingslashit( $lp_bad ) ) {
			$lp_why[] = 'redirect:' . $lp_bad;
		}
	}
	if ( $lp_why ) {
		$lp_bad_locs[] = $lp_loc . ' (' . implode( ',', $lp_why ) . ')';
	}
}
$lp_assert( array() === $lp_bad_locs, 'sitemap locs are canonical indexable URLs' . ( $lp_bad_locs ? ': ' . implode( '; ', array_slice( $lp_bad_locs, 0, 8 ) ) : '' ) );

$lp_query_backup = array(
	'query'    => $GLOBALS['wp_query'],
	'the'      => $GLOBALS['wp_the_query'] ?? null,
);

$lp_with_query = static function ( WP_Query $query, callable $fn ) use ( $lp_query_backup ) {
	$GLOBALS['wp_query']     = $query;
	$GLOBALS['wp_the_query'] = $query;
	try {
		return $fn();
	} finally {
		$GLOBALS['wp_query']     = $lp_query_backup['query'];
		$GLOBALS['wp_the_query'] = $lp_query_backup['the'];
	}
};

$lp_classes = get_page_by_path( 'classes' );
if ( $lp_classes instanceof WP_Post ) {
	$lp_with_query(
		new WP_Query( array( 'page_id' => (int) $lp_classes->ID ) ),
		static function () use ( $lp_assert, $lp_home ): void {
			$lp_assert( ! lp_seo_is_noindex(), 'classes page is indexable' );
			$lp_filter = static function ( string $url ): string {
				return remove_query_arg( array( 'date', 'utm_source' ), $url ) . '?date=2026-10-10&utm_source=test';
			};
			add_filter( 'get_canonical_url', $lp_filter );
			$lp_canon = lp_seo_canonical_url();
			remove_filter( 'get_canonical_url', $lp_filter );
			$lp_parts = wp_parse_url( $lp_canon );
			$lp_parts = is_array( $lp_parts ) ? $lp_parts : array();
			$lp_assert( ( $lp_parts['scheme'] ?? '' ) === ( $lp_home['scheme'] ?? '' ), 'classes canonical scheme matches home' );
			$lp_assert( ( $lp_parts['host'] ?? '' ) === ( $lp_home['host'] ?? '' ), 'classes canonical host matches home' );
			$lp_assert( false === strpos( $lp_canon, 'date=' ), 'class occurrence date is not in the canonical' );
			$lp_assert( false === strpos( $lp_canon, 'utm_' ), 'tracking params are not in the canonical' );
		}
	);
} else {
	WP_CLI::log( 'skip classes page is not seeded' );
}

$lp_front_id = (int) get_option( 'page_on_front' );
if ( $lp_front_id > 0 ) {
	$lp_with_query(
		new WP_Query( array( 'page_id' => $lp_front_id ) ),
		static function () use ( $lp_assert ): void {
			$lp_assert( ! lp_seo_is_noindex(), 'front page is indexable' );
		}
	);
}

$lp_with_query(
	new WP_Query( array( 'pagename' => 'does-not-exist-sitemap-test' ) ),
	static function () use ( $lp_assert ): void {
		$GLOBALS['wp_query']->is_404      = true;
		$GLOBALS['wp_query']->is_singular = false;
		$GLOBALS['wp_query']->is_page     = false;
		$lp_assert( lp_seo_is_noindex(), '404 is noindex' );
		$lp_graph = lp_seo_graph();
		$lp_json  = (string) wp_json_encode( $lp_graph );
		$lp_assert( false === strpos( $lp_json, 'aggregateRating' ), '404 graph has no aggregate rating' );
		$lp_assert( false === strpos( $lp_json, 'streetAddress' ), 'place schema has no street address' );
		$lp_assert( false === strpos( $lp_json, 'Page not found' ), '404 graph does not name the homepage as missing' );
		$lp_types = array();
		foreach ( (array) ( $lp_graph['@graph'] ?? array() ) as $lp_node ) {
			if ( ! is_array( $lp_node ) ) {
				continue;
			}
			$lp_type = $lp_node['@type'] ?? '';
			foreach ( (array) $lp_type as $lp_one ) {
				$lp_types[] = (string) $lp_one;
			}
		}
		$lp_assert( ! in_array( 'WebPage', $lp_types, true ), '404 graph has no WebPage node' );
	}
);

$lp_class_ids = get_posts(
	array(
		'post_type'      => function_exists( 'lp_class_post_type' ) ? lp_class_post_type() : 'clasbpro_class',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$lp_saw_event = false;
foreach ( $lp_class_ids as $lp_class_id ) {
	$lp_nodes = lp_seo_class_nodes( (int) $lp_class_id );
	$lp_json  = (string) wp_json_encode( $lp_nodes );
	$lp_assert( false === strpos( $lp_json, 'validFrom' ), 'class offer does not set validFrom to the session start' );
	foreach ( $lp_nodes as $lp_node ) {
		if ( ! is_array( $lp_node ) ) {
			continue;
		}
		$lp_type = (string) ( $lp_node['@type'] ?? '' );
		if ( 'Event' !== $lp_type && 'SportsEvent' !== $lp_type ) {
			continue;
		}
		$lp_performer = $lp_node['performer'] ?? null;
		$lp_performer_name = '';
		if ( is_array( $lp_performer ) && isset( $lp_performer['name'] ) ) {
			$lp_performer_name = (string) $lp_performer['name'];
		} elseif ( is_array( $lp_performer ) && isset( $lp_performer[0]['name'] ) ) {
			$lp_performer_name = (string) $lp_performer[0]['name'];
		}
		$lp_assert( '' !== $lp_performer_name, 'event names a real coach: ' . (string) ( $lp_node['name'] ?? '' ) );
		$lp_price = is_array( $lp_node['offers'] ?? null ) ? ( $lp_node['offers']['price'] ?? null ) : null;
		$lp_assert( is_numeric( $lp_price ) && (float) $lp_price > 0, 'event offer has a price: ' . (string) ( $lp_node['name'] ?? '' ) );
		$lp_currency = is_array( $lp_node['offers'] ?? null ) ? (string) ( $lp_node['offers']['priceCurrency'] ?? '' ) : '';
		$lp_assert( 'GBP' === $lp_currency, 'event offer currency is GBP: ' . (string) ( $lp_node['name'] ?? '' ) );
	}
	foreach ( $lp_nodes as $lp_node ) {
		if ( ! is_array( $lp_node ) || empty( $lp_node['startDate'] ) ) {
			continue;
		}
		$lp_saw_event = true;
		$lp_start     = strtotime( (string) $lp_node['startDate'] );
		$lp_assert( false !== $lp_start && $lp_start >= strtotime( '-1 day' ), 'class event date is upcoming: ' . (string) $lp_node['startDate'] );
	}
}
if ( ! $lp_class_ids ) {
	WP_CLI::log( 'skip no published classes to check event dates' );
} elseif ( ! $lp_saw_event ) {
	WP_CLI::log( 'ok  published classes emitted no past events' );
}

if ( $lp_fail > 0 ) {
	WP_CLI::error( sprintf( '%d assertion(s) failed', $lp_fail ), false );
	exit( 1 );
}

WP_CLI::success( 'sitemap and indexing contract (' . count( $lp_locs ) . ' sitemap locs)' );
