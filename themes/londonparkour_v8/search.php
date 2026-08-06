<?php
/**
 * search.php — SearchResults.
 *
 * Ported from src/stories/Search/SearchResults/SearchResults.js. That file
 * lives in `src/stories/Search/`, NOT `src/stories/Pages/`, which is why
 * docs/HANDOFF.md's Phase 5b table listed search.php as having "no Storybook
 * source". It has one: a 293-line designed page (`Lc4uQ` "Search (Concourse)")
 * with five section masters. Read its docblock before touching this file — it
 * records that an earlier version of the design was authored copy with an
 * invented column-header strip, and what replaced it.
 *
 * Section order: breadcrumb → query bar → filter rail → results → pagination.
 * Nav/footer are get_header()/get_footer(), outside the one <main>.
 *
 * The <h1> is the query bar's own 12px "SEARCH" label (`gOnqF/BiYwM`) — the
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
 *    view-tab.php's new `href` form: same class strings, aria-current="page"
 *    instead of role="tab". Counts are real.
 *
 * 2. **The SORT select is dropped.** A GET select with no submit control needs
 *    JS this theme does not have, and the design draws no submit next to it.
 *    Building one would mean inventing a control. WordPress search is
 *    relevance-ordered already. Recorded in docs/PORT-FINDINGS.md.
 *
 * 3. **The result count loses its `· 0.04s`.** WordPress exposes no search
 *    timing; timer_stop() measures page generation, not the query, so
 *    printing it would be a wrong number rather than a missing one.
 *
 * 4. **Two tabs the design predates.** The design has four (CLASSES,
 *    TUTORIALS, ARTICLES, PAGES) because it was drawn against a four-kind
 *    content model. This theme registers six public post types, so coaches and
 *    locations are searchable too and would otherwise appear in results with
 *    no tab to filter them — ALL would not equal the sum. Their labels are the
 *    registered CPT labels uppercased; the design's own four words are used
 *    verbatim for the four it drew. Zero-count tabs are not rendered.
 *
 * 5. **`CLEAR ✕` stays a <button type="reset">**, as drawn. On a submitted
 *    query a reset restores the submitted value, so it clears edits rather
 *    than the search — a semantic mismatch with the label. Reported rather
 *    than redesigned. The breadcrumb's `CLEAR SEARCH ✕` action has no href in
 *    the source and gets none here, the same treatment home.php gives
 *    `ALL DOCS ↗` and Legal gives its unbuilt pager targets.
 *
 * 6. **No zero-results state**, because the design has none — confirmed in the
 *    source by a whole-document search. With no results the query bar reports
 *    `0 RESULTS` and the filter rail and results band are not rendered. An
 *    invented empty state is exactly what the Port Brief forbids.
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

$lp_q = get_search_query();

/*
 * Tab word / row word / meta word per searchable post type, in the design's
 * order. The first four carry the design's own strings (`sDjGx`/`n8GzJ` tab
 * labels and the `iuNxZ` row anatomy); the last two are this theme's other
 * public types — see departure 4 above.
 */
$lp_types = array(
	'lp_class'    => array(
		'tab'  => 'CLASSES',
		'row'  => 'CLASS',
		'meta' => 'CLASSES',
	),
	'lp_tutorial' => array(
		'tab'  => 'TUTORIALS',
		'row'  => 'LESSON',
		'meta' => 'TUTORIALS',
	),
	'post'        => array(
		'tab'  => 'ARTICLES',
		'row'  => 'ARTICLE',
		'meta' => 'BLOG',
	),
	'page'        => array(
		'tab'  => 'PAGES',
		'row'  => 'PAGE',
		'meta' => 'PAGES',
	),
	'lp_coach'    => array(
		'tab'  => 'COACHES',
		'row'  => 'COACH',
		'meta' => 'COACHES',
	),
	'lp_location' => array(
		'tab'  => 'LOCATIONS',
		'row'  => 'LOCATION',
		'meta' => 'LOCATIONS',
	),
);

/*
 * ponytail: one count query per type, six in all, each returning ids only and
 * one row. The tab rail needs a per-type total that the main query cannot give
 * — it counts whatever filter is active. Collapse into a single GROUP BY
 * post_type query if search ever gets hot enough to measure.
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

$lp_active_type = get_query_var( 'post_type' );
$lp_active_type = ( is_string( $lp_active_type ) && isset( $lp_types[ $lp_active_type ] ) ) ? $lp_active_type : '';

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
	if ( ! $lp_counts[ $lp_type ] ) {
		continue;
	}
	$lp_tabs[] = array(
		'label'  => sprintf( '%s %d', $lp_words['tab'], $lp_counts[ $lp_type ] ),
		'href'   => $lp_tab_href( $lp_type ),
		'active' => $lp_type === $lp_active_type,
	);
}

/** The row's trailing meta: the section word, plus that kind's one real detail. */
$lp_row_meta = static function ( WP_Post $lp_post ) use ( $lp_types ): string {
	$lp_word = $lp_types[ $lp_post->post_type ]['meta'] ?? strtoupper( $lp_post->post_type );

	if ( 'post' === $lp_post->post_type ) {
		return $lp_word . ' · ' . strtoupper( get_the_date( 'M Y', $lp_post ) );
	}

	if ( 'lp_class' === $lp_post->post_type && function_exists( 'get_field' ) ) {
		$lp_price = (string) get_field( 'price', $lp_post->ID );
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

// `gOnqF` Query Bar copy. `label` is also the page's <h1>.
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
					'href'  => home_url( '/' ),
				),
				array( 'label' => 'SEARCH' ),
			),
			// `Y7Nn9/xIrgr` — no href in the source, so none here.
			'action' => array( 'label' => 'CLEAR SEARCH ✕' ),
		)
	);
	?>

	<div class="w-full bg-neutral" data-component="search-query-bar">
		<div class="px-6 lg:px-16 pt-16 pb-[54px]">
			<div class="flex items-center justify-between gap-4 flex-wrap">
				<h1 class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary m-0"><?php echo esc_html( $lp_bar['label'] ); ?></h1>
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50"><?php printf( '%d RESULTS', (int) $lp_found ); ?></span>
			</div>

			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="mt-6 flex items-center gap-4 h-[68px] px-[22px] bg-secondary border border-neutral-content/[.14]">
				<span class="shrink-0 text-neutral-content/50" aria-hidden="true"><?php lp_icon( 'icon-magnifying-glass', 'w-5 h-5' ); ?></span>
				<label class="sr-only" for="search-results-query"><?php echo esc_html( $lp_bar['label'] ); ?></label>
				<input id="search-results-query" name="s" type="search" value="<?php echo esc_attr( $lp_q ); ?>"
					class="flex-1 min-w-0 bg-transparent border-0 p-0 font-heading text-[24px] font-medium tracking-[-0.5px] text-neutral-content placeholder:text-neutral-content/50 focus:outline-none" />
				<?php
				lp_part(
					'elements/button',
					array(
						'variant' => 'band_text',
						'type'    => 'reset',
						'label'   => $lp_bar['clear'],
						'class'   => 'shrink-0',
					)
				);
				?>
			</form>

			<p class="mt-4 font-body text-[11px] font-normal tracking-[0.2px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_bar['hint'] ); ?></p>
		</div>
	</div>

	<?php if ( $lp_results ) : ?>
		<div class="w-full bg-base-100" data-component="search-filter-rail">
			<div class="px-6 lg:px-16">
				<div class="flex items-center justify-between gap-6 flex-wrap py-2 border-b border-base-content">
					<div class="flex items-center gap-[30px] flex-wrap">
						<?php foreach ( $lp_tabs as $lp_tab ) : ?>
							<?php lp_part( 'elements/view-tab', $lp_tab ); ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

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
