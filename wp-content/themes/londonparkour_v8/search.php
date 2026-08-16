<?php
/**
 * search.php — SearchResults.
 *
 * Ported from src/stories/Search/SearchResults/SearchResults.js (`IL6Nj`
 * "Search (Concourse)" under `SXvw6`). Section order: breadcrumb (`Ukz5c`) →
 * query bar (`Zqc9v`) → filter rail (`wsypA`) → results (`CL8zt`) →
 * pagination (`pjHyS`). Nav/footer are get_header()/get_footer(), outside
 * the one <main>.
 *
 * The <h1> is the query bar's own 12px "SEARCH" label (`Zqc9v/BiYwM`) — the
 * design gives this page no masthead and no large headline, and the source
 * makes the same call rather than inventing a heading string.
 *
 * Token mapping is the source's, not re-derived: query bar `#141310` →
 * bg-neutral with the neutral-content family, its inner input box `#0E0D0A` →
 * bg-secondary; filter rail → bg-base-100 closed by a border-base-content
 * rule; results → bg-base-200; pagination → bg-primary.
 *
 * ── Departures from the source, all deliberate ─────────────────────────────
 *
 * 1. **The filter tabs are links, not buttons.** The source renders ViewTab,
 *    whose onClick is a Storybook callback that explicitly does not come
 *    across. On WordPress a post-type filter is a URL, so the tabs are
 *    view-tab.php's `href` form: same class strings, aria-current="page"
 *    instead of role="tab". Counts are real. Zero-count tabs stay — `wsypA`
 *    always draws all four kinds.
 *
 * 2. **The SORT select is visual.** `wsypA/Hehde` + `D5fss/GzfsU` are in the
 *    source as `h-[42px] w-[200px]` with one option, "Most relevant". That
 *    is WordPress search's default order. Extra options or onchange JS would
 *    be invented. The markup is NOT `forms/select.php` — see PORT-FINDINGS
 *    §15.2.
 *
 * 3. **The result count loses its `· 0.04s`.** WordPress exposes no search
 *    timing; timer_stop() measures page generation, not the query, so
 *    printing it would be a wrong number rather than a missing one.
 *
 * 4. **ARTICLES is the `blog` CPT**, not native `post`. The design was drawn
 *    against a four-kind model (classes, tutorials, articles, pages) — the
 *    query-bar hint names those four. This theme's articles are `blog` (v7
 *    import). Coaches, locations, support and notifications are public but
 *    not on the rail; `lp_filter_search` keeps them out so ALL equals the
 *    sum of the four tabs.
 *
 * 5. **`CLEAR SEARCH ✕` and `CLEAR ✕` are links to home.** The source's
 *    breadcrumb action has no href and the query-bar control is
 *    `<button type="reset">`, which on a submitted query restores the
 *    submitted value rather than clearing the search. Home is the honest
 *    empty-search destination this theme has.
 *
 * 6. **No zero-results state**, because the design has none. With no hits
 *    the query bar reports `0 RESULTS` and the filter rail still renders
 *    (so the tabs remain operable). The results band and pagination do not.
 *
 * 7. **Eight results to a page**, matching `pjHyS/h0BaW` "SHOWING 01–08 OF
 *    24 RESULTS". Set in `lp_filter_search`.
 *
 * Row content maps to the design's own vocabulary: `category` is the singular
 * word the design uses per kind (LESSON for a tutorial, ARTICLE for a post),
 * `meta` is the plural section word (BLOG for posts) plus the one real detail
 * that kind carries — a class's `price` field, a post's month. The design's
 * `TUTORIALS · FREE` has no field behind it and is not reproduced; claiming a
 * price is a claim.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_q     = get_search_query();
$lp_types = lp_search_types();

/*
 * ponytail: one count query per type, four in all, each returning ids only and
 * one row. The tab rail needs a per-type total that the main query cannot give
 * — it counts whatever filter is active.
 */
$lp_counts = array();
foreach ( array_keys( $lp_types ) as $lp_type ) {
	$lp_count_query        = new WP_Query(
		array(
			's'                   => $lp_q,
			'post_type'           => $lp_type,
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
		)
	);
	$lp_counts[ $lp_type ] = (int) $lp_count_query->found_posts;
}
$lp_total = array_sum( $lp_counts );

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter, see app/setup/queries.php.
$lp_active_type = isset( $_GET['post_type'] ) && is_string( $_GET['post_type'] )
	? sanitize_key( wp_unslash( $_GET['post_type'] ) )
	: '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
if ( ! isset( $lp_types[ $lp_active_type ] ) ) {
	$lp_active_type = '';
}

/** Build a filter-rail href. An empty type is the ALL tab. */
$lp_tab_href = static function ( string $lp_type ) use ( $lp_q ): string {
	$lp_args = array( 's' => $lp_q );
	if ( '' !== $lp_type ) {
		$lp_args['post_type'] = $lp_type;
	}

	return add_query_arg( $lp_args, home_url( '/' ) );
};

$lp_tabs = array(
	array(
		'label'  => sprintf( 'ALL %d', $lp_total ),
		'href'   => $lp_tab_href( '' ),
		'active' => '' === $lp_active_type,
	),
);
foreach ( $lp_types as $lp_type => $lp_words ) {
	$lp_tabs[] = array(
		'label'  => sprintf( '%s %d', $lp_words['tab'], $lp_counts[ $lp_type ] ),
		'href'   => $lp_tab_href( $lp_type ),
		'active' => $lp_type === $lp_active_type,
	);
}

/** The row's trailing meta: the section word, plus that kind's one real detail. */
$lp_row_meta = static function ( WP_Post $lp_post ) use ( $lp_types ): string {
	$lp_word = $lp_types[ $lp_post->post_type ]['meta'] ?? strtoupper( $lp_post->post_type );

	if ( in_array( $lp_post->post_type, array( 'post', 'blog' ), true ) ) {
		return $lp_word . ' · ' . strtoupper( get_the_date( 'M Y', $lp_post ) );
	}

	if ( lp_class_post_type() === $lp_post->post_type && function_exists( 'lp_class_price_display' ) ) {
		$lp_price = lp_class_price_display( (int) $lp_post->ID );
		if ( '' !== $lp_price ) {
			return $lp_word . ' · ' . $lp_price;
		}
	}

	return $lp_word;
};

$lp_results = array();
while ( have_posts() ) {
	the_post();
	$lp_results[] = get_post();
}

$lp_found  = (int) $GLOBALS['wp_query']->found_posts;
$lp_offset = ( max( 1, (int) get_query_var( 'paged' ) ) - 1 ) * (int) get_query_var( 'posts_per_page' );
$lp_home   = home_url( '/' );

// `Zqc9v` Query Bar copy. `label` is also the page's <h1>.
$lp_bar = array(
	'label' => 'SEARCH',
	'clear' => 'CLEAR ✕',
	'hint'  => 'Searching classes, tutorials, articles and pages.',
);

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array(
					'label' => 'HOME',
					'href'  => $lp_home,
				),
				array( 'label' => 'SEARCH' ),
			),
			'action' => array(
				'label' => 'CLEAR SEARCH ✕',
				'href'  => $lp_home,
			),
		)
	);
	?>

	<div class="w-full bg-neutral" data-component="search-query-bar">
		<div class="px-6 lg:px-16 pt-16 pb-[54px]">
			<div class="flex items-center justify-between gap-4 flex-wrap">
				<h1 class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary m-0"><?php echo esc_html( $lp_bar['label'] ); ?></h1>
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50"><?php printf( '%d RESULTS', (int) $lp_found ); ?></span>
			</div>

			<form role="search" method="get" action="<?php echo esc_url( $lp_home ); ?>" class="mt-6 flex items-center gap-4 h-[68px] px-[22px] bg-secondary border border-neutral-content/[.14]">
				<?php if ( '' !== $lp_active_type ) : ?>
					<input type="hidden" name="post_type" value="<?php echo esc_attr( $lp_active_type ); ?>" />
				<?php endif; ?>
				<span class="shrink-0 text-neutral-content/50" aria-hidden="true"><?php lp_icon( 'icon-magnifying-glass', 'w-5 h-5' ); ?></span>
				<label class="sr-only" for="search-results-query"><?php echo esc_html( $lp_bar['label'] ); ?></label>
				<input id="search-results-query" name="s" type="search" value="<?php echo esc_attr( $lp_q ); ?>"
					class="flex-1 min-w-0 bg-transparent border-0 p-0 font-heading text-[24px] font-medium tracking-[-0.5px] text-neutral-content placeholder:text-neutral-content/50 focus:outline-none" />
				<?php
				lp_part(
					'elements/button',
					array(
						'variant' => 'band_text',
						'href'    => $lp_home,
						'label'   => $lp_bar['clear'],
						'class'   => 'shrink-0',
					)
				);
				?>
			</form>

			<p class="mt-4 font-body text-[11px] font-normal tracking-[0.2px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_bar['hint'] ); ?></p>
		</div>
	</div>

	<div class="w-full bg-base-100" data-component="search-filter-rail">
		<div class="px-6 lg:px-16">
			<div class="flex items-center justify-between gap-6 flex-wrap py-2 border-b border-base-content">
				<div class="flex items-center gap-[30px] flex-wrap">
					<?php foreach ( $lp_tabs as $lp_tab ) : ?>
						<?php lp_part( 'elements/view-tab', $lp_tab ); ?>
					<?php endforeach; ?>
				</div>
				<div class="flex items-center gap-4">
					<span id="search-sort-label" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">SORT</span>
					<select aria-labelledby="search-sort-label"
						class="h-[42px] w-[200px] px-[14px] rounded-none bg-transparent border border-base-300 font-body text-[11px] tracking-[0.4px] text-base-content focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-accent">
						<option>Most relevant</option>
					</select>
				</div>
			</div>
		</div>
	</div>

	<?php if ( $lp_results ) : ?>
		<div class="w-full bg-base-200" data-component="search-results-list">
			<div class="px-6 lg:px-16 pt-16 pb-[84px]">
				<ul role="list" class="flex flex-col m-0 p-0 list-none">
					<?php foreach ( $lp_results as $lp_i => $lp_result ) : ?>
						<li>
							<?php
							lp_part(
								'components/search-result-row',
								array(
									'index'    => sprintf( '%02d', $lp_offset + $lp_i + 1 ),
									'category' => $lp_types[ $lp_result->post_type ]['row'] ?? strtoupper( $lp_result->post_type ),
									'title'    => get_the_title( $lp_result ),
									'snippet'  => get_the_excerpt( $lp_result ),
									'meta'     => $lp_row_meta( $lp_result ),
									'href'     => (string) get_permalink( $lp_result ),
								)
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<?php
		$lp_pagination = lp_pagination_args( null, 'RESULTS' );
		if ( $lp_pagination ) {
			$lp_pagination['aria_label'] = 'Search results pages';
			lp_part( 'components/pagination', $lp_pagination );
		}
		?>
	<?php endif; ?>
</main>

<?php
get_footer();
