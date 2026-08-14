<?php
/**
 * archive-lp_tutorial.php — TutorialsIndex.
 *
 * Ported from src/stories/Pages/TutorialsIndex/TutorialsIndex.js (`O3zEHg`).
 * Read that file's docblock before touching this one.
 *
 * Section order: breadcrumb → masthead → view rail → filter grid → "03 The
 * Board" (meta-row → rule → 4-per-row video-card grid → an inline PageOnward
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
 * Cards are `lp_video_card_args_from_tutorial( …, 'full' )`. Kicker / meta /
 * glyph read the hierarchical `tutorial-category` taxonomy (parent = category,
 * child = move). Duration is YouTube runtime when present. NEW is a post
 * published in the last 30 days. RESUME cannot be built (no user progress
 * store) and never renders.
 *
 * ── Filtering ──────────────────────────────────────────────────────────────
 *
 * A real GET form. Parameters are `tutorial_search` / `tutorial_category`
 * / `tutorial_series` / `tutorial_tag`. Category is parent or child
 * `tutorial-category`; series is an `lp_series` term; tag is challenge,
 * demonstration or tutorial. The board orders by category, then series,
 * then curriculum order number (award_level on challenges, order_position
 * on tutorials). Demonstrations have no order number and sort last inside
 * their series.
 *
 * The inline pagination rail inside "03 The Board" is prev/next PAGE of the
 * filtered listing. Category is the dropdown; this rail pages the board.
 * It only has something to show when there is more than one page.
 *
 * Numbered page pagination is separate: the main query is paged
 * (120 per page, set in lp_filter_tutorial_archive()), and when there
 * is more than one page the shared `components/pagination` band mounts
 * after the board via `lp_pagination_args( …, 'VIDEOS' )`.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_values           = lp_tutorial_filter_values();
$lp_category         = $lp_values['tutorial_category'];
$lp_series           = $lp_values['tutorial_series'];
$lp_tag              = $lp_values['tutorial_tag'];
$lp_archive_url      = (string) get_post_type_archive_link( 'lp_tutorial' );
$lp_total_tutorials  = (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );
$lp_category_options = lp_tutorial_category_filter_options();
$lp_series_options   = lp_tutorial_series_filter_options();
$lp_tag_options      = lp_tutorial_tag_filter_options();
$lp_category_term    = '' !== $lp_category
	? get_term_by( 'slug', $lp_category, 'tutorial-category' )
	: null;
if ( ! $lp_category_term instanceof WP_Term ) {
	$lp_category_term = null;
}
$lp_series_term = '' !== $lp_series
	? get_term_by( 'slug', $lp_series, 'lp_series' )
	: null;
if ( ! $lp_series_term instanceof WP_Term ) {
	$lp_series_term = null;
}
$lp_tag_term = '' !== $lp_tag
	? get_term_by( 'slug', $lp_tag, 'tutorial-tag' )
	: null;
if ( ! $lp_tag_term instanceof WP_Term ) {
	$lp_tag_term = null;
}

get_header();
?>

<main id="main">
	<?php
	// Breadcrumb reflects real filter state — HOME / TUTORIALS, plus the
	// category and/or series when the page is actually filtered.
	$lp_crumbs = array(
		array(
			'label' => 'HOME',
			'href'  => home_url( '/' ),
		),
	);

	if ( ! $lp_category_term && ! $lp_series_term && ! $lp_tag_term ) {
		$lp_crumbs[] = array( 'label' => 'TUTORIALS' );
	} else {
		$lp_crumbs[] = array(
			'label' => 'TUTORIALS',
			'href'  => $lp_archive_url,
		);
		if ( $lp_category_term ) {
			$lp_crumbs[] = array( 'label' => strtoupper( $lp_category_term->name ) );
		}
		if ( $lp_series_term ) {
			$lp_crumbs[] = array( 'label' => strtoupper( $lp_series_term->name ) );
		}
		if ( $lp_tag_term ) {
			$lp_crumbs[] = array( 'label' => strtoupper( $lp_tag_term->name ) );
		}
	}

	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => $lp_crumbs,
			'action' => array(
				'label' => 'BY SERIES ↗',
				'href'  => lp_tutorials_series_url(),
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'Tutorials.',
			'note'  => sprintf(
				'%d coached videos, filed by movement. Filter by category, series or tag — or browse the whole board.',
				$lp_total_tutorials
			),
		)
	);

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'tutorials',
			'aria_label' => 'Tutorials view',
			'tabs'       => lp_tutorials_view_tabs( 'tutorial' ),
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
					'key'     => 'Series',
					'name'    => 'tutorial_series',
					'options' => $lp_series_options,
					'value'   => $lp_series,
				),
				array(
					'type'    => 'select',
					'key'     => 'Tag',
					'name'    => 'tutorial_tag',
					'options' => $lp_tag_options,
					'value'   => $lp_tag,
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
		$lp_board_bits = array();
		if ( $lp_category_term ) {
			$lp_board_bits[] = strtoupper( $lp_category_term->name );
		}
		if ( $lp_series_term ) {
			$lp_board_bits[] = strtoupper( $lp_series_term->name );
		}
		if ( $lp_tag_term ) {
			$lp_board_bits[] = strtoupper( $lp_tag_term->name );
		}
		$lp_content_left = sprintf(
			'%s — %d VIDEOS',
			$lp_board_bits ? implode( ' · ', $lp_board_bits ) : 'ALL TUTORIALS',
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
						'right'   => '',
						'surface' => 'page',
					)
				);

				lp_part( 'elements/rule', array( 'tone' => 'ink' ) );
				?>
				<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-[24px]">
					<?php
					while ( have_posts() ) :
						the_post();
						lp_part( 'components/video-card', lp_video_card_args_from_tutorial( get_post(), 'full' ) );
					endwhile;
					?>
				</div>
				<?php
				// Prev/next PAGE of this listing — category is the dropdown,
				// this rail pages the board. Skip the empty hairline when
				// there is only one page.
				$lp_pagination = lp_pagination_args( null, 'VIDEOS' );
				$lp_paged      = max( 1, (int) $wp_query->get( 'paged' ) );
				$lp_per_page   = (int) $wp_query->get( 'posts_per_page' );
				$lp_max_pages  = (int) $wp_query->max_num_pages;
				$lp_prev_page  = array();
				$lp_next_page  = array();

				if ( $lp_max_pages > 1 && $lp_per_page > 0 ) {
					$lp_video_range = static function ( int $lp_page ) use ( $lp_per_page, $lp_found ): string {
						$lp_from = ( ( $lp_page - 1 ) * $lp_per_page ) + 1;
						$lp_to   = min( $lp_page * $lp_per_page, $lp_found );
						return sprintf( 'Videos %02d–%02d', $lp_from, $lp_to );
					};

					if ( $lp_paged > 1 ) {
						$lp_prev_page = array(
							'keyword' => '← PREVIOUS PAGE',
							'label'   => $lp_video_range( $lp_paged - 1 ),
							'href'    => get_pagenum_link( $lp_paged - 1 ),
						);
					}
					if ( $lp_paged < $lp_max_pages ) {
						$lp_next_page = array(
							'keyword' => 'NEXT PAGE →',
							'label'   => $lp_video_range( $lp_paged + 1 ),
							'href'    => get_pagenum_link( $lp_paged + 1 ),
						);
					}
				}

				if ( $lp_prev_page || $lp_next_page ) {
					?>
				<div class="pt-2">
					<?php
					lp_part(
						'components/page-onward',
						array(
							'prev'    => $lp_prev_page,
							'next'    => $lp_next_page,
							'surface' => 'page',
							'variant' => 'bare',
						)
					);
					?>
				</div>
					<?php
				}
				?>
			</div>
		</div>

		<?php
		if ( $lp_pagination ) {
			$lp_pagination['aria_label'] = 'Tutorial pages';
			lp_part( 'components/pagination', $lp_pagination );
		}
		?>
	<?php endif; ?>

	<?php lp_render_block( 'train-in-person', array() ); ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← THE SERIES',
				'label'   => 'The twelve lines',
				'href'    => lp_tutorials_series_url(),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Put it into practice',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
