<?php
/**
 * archive-lp_tutorial.php — TutorialsIndex.
 *
 * Ported from src/stories/Pages/TutorialsIndex/TutorialsIndex.js (`O3zEHg`).
 * Read that file's docblock before touching this one.
 *
 * Section order: breadcrumb → masthead → view rail → filter grid → "03 The
 * Board" (meta-row → rule → 3-per-row video-card grid → an inline PageOnward
 * pagination rail) → Train In Person → onward. Nav/footer are
 * get_header()/get_footer(), outside the one <main>.
 *
 * "03 The Board" is confirmed NOT a `BoardShell` by the source's own JSDoc
 * (and `BoardShell.js`'s), so it is built directly here per the coordinator's
 * decision: meta-row (surface 'page') → rule (tone 'ink') → the card grid →
 * page-onward (variant 'bare', surface 'page') as the inline rail — a
 * DIFFERENT instance from the page-level page-onward lower down.
 *
 * Train In Person is the already-ported blocks/train-in-person block, called
 * directly via lp_render_block() rather than re-ported — see that file.
 *
 * ── Every count is counted, not hardcoded ──────────────────────────────────
 *
 * The source's `840 videos`, `12 series`, `10 VIDEOS` and `Search 840
 * tutorials…` are literals. Here they are all derived from real data: total
 * published lp_tutorial count, total lp_series term count, and the current
 * (possibly filtered) query's `found_posts`. With only three seeded
 * tutorials the numbers are small — that is correct, not a bug.
 *
 * ── Card facts ──────────────────────────────────────────────────────────
 *
 * `duration` is the existing `duration` field on group_lp_tutorial. `note` is
 * the post excerpt (`get_the_excerpt()` — native data, not an ACF field; three
 * short excerpts were seeded in bin/demo-content/lp_tutorial.json for this
 * port, same pattern as lp_class.json's own excerpts). The "01 ·" sequence
 * number is the post's own `menu_order`.
 *
 * `kicker` / `meta` (category / move) read the hierarchical `lp_series`
 * taxonomy: a parent term is the category, a child term is the move. This
 * reads literally — the kicker is the term's PARENT's name (empty when the
 * term has no parent, since there is then nothing to read), and the meta is
 * the sequence number plus the term's own name. The three seeded tutorials
 * each carry one FLAT (parent-less) `lp_series` term, so their kicker is
 * currently empty and their meta is just "0N · TERMNAME" — an honest
 * reflection of the data, not a defect. The moment an editor nests a term
 * under a category, the kicker appears for that tutorial.
 *
 * ── The two flags ───────────────────────────────────────────────────────
 *
 * RESUME (source card 4) is per-user watch progress. This theme has no user
 * model, no auth and no progress store, so it CANNOT be built and is not
 * faked — same gap class as PORT-FINDINGS §19's SITES. No card ever renders
 * it.
 *
 * NEW (source card 9) IS derivable — from the post date. A tutorial published
 * within the last 30 days gets the flag. Thirty days was chosen as a plain,
 * unremarkable "recently added" window for a slow-moving tutorial library;
 * there is no design-specified value to match.
 *
 * ── Filtering ──────────────────────────────────────────────────────────────
 *
 * A real GET form, following archive-lp_class.php's own pattern. See
 * app/setup/queries.php for why the parameters are `tutorial_search` /
 * `tutorial_category` / `tutorial_move` / `tutorial_sort` and not `s` or the
 * taxonomy query var. Category options are the taxonomy's parent (top-level)
 * terms; Move options are the selected category's children, so the two
 * cascade through a normal page reload — no extra JS needed beyond
 * FilterForm.js, which filter-grid.php already wires up.
 *
 * The inline pagination rail inside "03 The Board" is prev/next MOVE (a
 * sibling `lp_series` term), per the source design — NOT numbered paging.
 * It only has something to show once a move is selected; with none of the
 * seeded terms nested, both sides render empty (page-onward's own guard),
 * which is correct given the data, not a broken control.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/** Kicker (parent term name) / meta ("0N · TERM") for one tutorial, from lp_series. */
$lp_series_info = static function ( WP_Post $lp_post ): array {
	$lp_terms = get_the_terms( $lp_post, 'lp_series' );
	$lp_term  = ( is_array( $lp_terms ) && $lp_terms ) ? $lp_terms[0] : null;

	if ( ! $lp_term ) {
		return array(
			'kicker' => '',
			'meta'   => '',
		);
	}

	$lp_parent = $lp_term->parent ? get_term( $lp_term->parent, 'lp_series' ) : null;
	$lp_kicker = ( $lp_parent && ! is_wp_error( $lp_parent ) ) ? strtoupper( $lp_parent->name ) : '';

	return array(
		'kicker' => $lp_kicker,
		'meta'   => sprintf( '%02d · %s', (int) $lp_post->menu_order, strtoupper( $lp_term->name ) ),
		'term'   => $lp_term,
	);
};

/** Flatten one tutorial into components/video-card.php's `full` args. */
$lp_card = static function ( WP_Post $lp_post ) use ( $lp_series_info ): array {
	$lp_info     = $lp_series_info( $lp_post );
	$lp_duration = function_exists( 'get_field' ) ? (string) get_field( 'duration', $lp_post->ID ) : '';

	// NEW: published within the last 30 days. RESUME cannot be built (no user
	// model/progress store) and never renders — see the docblock.
	$lp_is_new = ( time() - get_post_time( 'U', true, $lp_post ) ) <= ( 30 * DAY_IN_SECONDS );

	return array(
		'variant'     => 'full',
		'image_id'    => get_post_thumbnail_id( $lp_post ) ?: 0,
		'kicker'      => $lp_info['kicker'],
		'meta'        => $lp_info['meta'],
		'title'       => get_the_title( $lp_post ),
		'note'        => get_the_excerpt( $lp_post ),
		'duration'    => $lp_duration,
		'badge_label' => 'Lesson',
		'flag'        => $lp_is_new ? 'NEW' : '',
		'cta_label'   => 'Watch lesson',
		'cta_href'    => (string) get_permalink( $lp_post ),
	);
};

$lp_values      = lp_tutorial_filter_values();
$lp_category    = $lp_values['tutorial_category'];
$lp_move        = $lp_values['tutorial_move'];
$lp_sort        = $lp_values['tutorial_sort'];
$lp_archive_url = (string) get_post_type_archive_link( 'lp_tutorial' );

$lp_total_tutorials = (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );
$lp_all_series      = get_terms( array( 'taxonomy' => 'lp_series', 'hide_empty' => false ) );
$lp_total_series    = is_array( $lp_all_series ) ? count( $lp_all_series ) : 0;

// Category (parent) / Move (child of the selected category) terms.
$lp_category_terms = get_terms(
	array(
		'taxonomy'   => 'lp_series',
		'parent'     => 0,
		'hide_empty' => false,
	)
);
$lp_category_terms = is_array( $lp_category_terms ) ? $lp_category_terms : array();
$lp_category_term   = null;
foreach ( $lp_category_terms as $lp_t ) {
	if ( $lp_t->slug === $lp_category ) {
		$lp_category_term = $lp_t;
		break;
	}
}

$lp_move_terms = array();
if ( $lp_category_term ) {
	$lp_move_terms = get_terms(
		array(
			'taxonomy'   => 'lp_series',
			'parent'     => $lp_category_term->term_id,
			'hide_empty' => false,
		)
	);
	$lp_move_terms = is_array( $lp_move_terms ) ? $lp_move_terms : array();
}

$lp_move_term = null;
foreach ( $lp_move_terms as $lp_t ) {
	if ( $lp_t->slug === $lp_move ) {
		$lp_move_term = $lp_t;
		break;
	}
}

$lp_category_options = array( array( 'value' => '', 'label' => 'All categories' ) );
foreach ( $lp_category_terms as $lp_t ) {
	$lp_category_options[] = array(
		'value' => $lp_t->slug,
		'label' => $lp_t->name,
	);
}

$lp_move_options = array( array( 'value' => '', 'label' => 'All moves' ) );
foreach ( $lp_move_terms as $lp_t ) {
	$lp_move_options[] = array(
		'value' => $lp_t->slug,
		'label' => $lp_t->name,
	);
}

get_header();
?>

<main id="main">
	<?php
	// Breadcrumb reflects real filter state — HOME / TUTORIALS, plus the
	// category and/or move when the page is actually filtered to them.
	$lp_crumbs = array(
		array(
			'label' => 'HOME',
			'href'  => home_url( '/' ),
		),
	);

	if ( ! $lp_category_term && ! $lp_move_term ) {
		$lp_crumbs[] = array( 'label' => 'TUTORIALS' );
	} else {
		$lp_crumbs[] = array(
			'label' => 'TUTORIALS',
			'href'  => $lp_archive_url,
		);
	}

	if ( $lp_category_term ) {
		$lp_crumbs[] = $lp_move_term
			? array(
				'label' => strtoupper( $lp_category_term->name ),
				'href'  => add_query_arg( 'tutorial_category', $lp_category_term->slug, $lp_archive_url ),
			)
			: array( 'label' => strtoupper( $lp_category_term->name ) );
	}

	if ( $lp_move_term ) {
		$lp_crumbs[] = array( 'label' => strtoupper( $lp_move_term->name ) );
	}

	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => $lp_crumbs,
			'action' => array(
				'label' => 'BY SERIES ↗',
				'href'  => home_url( '/tutorials/series' ),
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'Tutorials.',
			'note'  => sprintf(
				'%d coached videos, filed by movement. Filter down to the exact move — or browse the whole board.',
				$lp_total_tutorials
			),
		)
	);

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'tutorials',
			'aria_label' => 'Tutorials view',
			'tabs'       => array(
				array(
					'label'   => 'By series',
					'meta'    => sprintf( '%d series', $lp_total_series ),
					'icon_id' => 'icon-square-3-stack-3d',
					'href'    => home_url( '/tutorials/series' ),
					'active'  => false,
				),
				array(
					'label'   => 'By tutorial',
					'meta'    => sprintf( '%d videos', $lp_total_tutorials ),
					'icon_id' => 'icon-play-circle',
					'href'    => $lp_archive_url,
					'active'  => true,
				),
			),
		)
	);

	lp_part(
		'components/filter-grid',
		array(
			'cells'  => array(
				array(
					'type'        => 'search',
					'key'         => 'Search',
					'name'        => 'tutorial_search',
					'placeholder' => sprintf( 'Search %d tutorials…', $lp_total_tutorials ),
					'value'       => $lp_values['tutorial_search'],
				),
				array(
					'type'    => 'select',
					'key'     => 'Category',
					'name'    => 'tutorial_category',
					'options' => $lp_category_options,
					'value'   => $lp_category,
				),
				array(
					'type'    => 'select',
					'key'     => 'Move',
					'name'    => 'tutorial_move',
					'options' => $lp_move_options,
					'value'   => $lp_move,
				),
				array(
					'type'    => 'select',
					'key'     => 'Sort',
					'name'    => 'tutorial_sort',
					'options' => array(
						array( 'value' => 'sequence', 'label' => 'Sequence' ),
						array( 'value' => 'newest', 'label' => 'Newest' ),
					),
					'value'   => $lp_sort,
				),
			),
			'action' => $lp_archive_url,
			'submit' => 'Apply tutorial filters',
		)
	);
	?>

	<?php
	global $wp_query;
	$lp_found = (int) $wp_query->found_posts;

	if ( have_posts() ) :
		$lp_left_bits = array_filter(
			array(
				$lp_category_term ? strtoupper( $lp_category_term->name ) : '',
				$lp_move_term ? strtoupper( $lp_move_term->name ) : '',
			)
		);
		$lp_content_left = sprintf(
			'%s — %d VIDEOS',
			$lp_left_bits ? implode( ' · ', $lp_left_bits ) : 'ALL TUTORIALS',
			$lp_found
		);
		?>
		<div class="w-full bg-base-100" data-component="board">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[24px]">
				<?php
				lp_part(
					'components/meta-row',
					array(
						'left'    => $lp_content_left,
						'right'   => sprintf( 'SORT — %s ↓', strtoupper( $lp_sort ) ),
						'surface' => 'page',
					)
				);

				lp_part( 'elements/rule', array( 'tone' => 'ink' ) );
				?>
				<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-[24px]">
					<?php
					while ( have_posts() ) :
						the_post();
						lp_part( 'components/video-card', $lp_card( get_post() ) );
					endwhile;
					?>
				</div>
				<?php
				// Prev/next MOVE — a sibling lp_series term, not numbered
				// paging. Only has something to show once a move is selected;
				// see the docblock.
				$lp_prev_move = array();
				$lp_next_move = array();

				if ( $lp_move_term ) {
					$lp_siblings = $lp_move_terms; // Already the current move's siblings (children of its category).
					usort( $lp_siblings, static fn( $lp_a, $lp_b ) => strcmp( $lp_a->name, $lp_b->name ) );

					$lp_index = null;
					foreach ( $lp_siblings as $lp_i => $lp_t ) {
						if ( $lp_t->term_id === $lp_move_term->term_id ) {
							$lp_index = $lp_i;
							break;
						}
					}

					if ( null !== $lp_index ) {
						$lp_sibling_href = static function ( WP_Term $lp_t ) use ( $lp_archive_url, $lp_category_term ) {
							return add_query_arg(
								array(
									'tutorial_category' => $lp_category_term->slug,
									'tutorial_move'      => $lp_t->slug,
								),
								$lp_archive_url
							);
						};

						if ( $lp_index > 0 ) {
							$lp_prev_term = $lp_siblings[ $lp_index - 1 ];
							$lp_prev_move = array(
								'keyword' => '← PREVIOUS MOVE',
								'label'   => sprintf( '%s (%d videos)', $lp_prev_term->name, (int) $lp_prev_term->count ),
								'href'    => $lp_sibling_href( $lp_prev_term ),
							);
						}
						if ( $lp_index < count( $lp_siblings ) - 1 ) {
							$lp_next_term = $lp_siblings[ $lp_index + 1 ];
							$lp_next_move = array(
								'keyword' => 'NEXT MOVE →',
								'label'   => sprintf( '%s (%d videos)', $lp_next_term->name, (int) $lp_next_term->count ),
								'href'    => $lp_sibling_href( $lp_next_term ),
							);
						}
					}
				}
				?>
				<div class="pt-2">
					<?php
					lp_part(
						'components/page-onward',
						array(
							'prev'    => $lp_prev_move,
							'next'    => $lp_next_move,
							'surface' => 'page',
							'variant' => 'bare',
						)
					);
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php lp_render_block( 'train-in-person', array() ); ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← THE SERIES',
				'label'   => 'The twelve lines',
				'href'    => home_url( '/tutorials/series' ),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Put it into practice',
				'href'    => (string) get_post_type_archive_link( 'lp_class' ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
