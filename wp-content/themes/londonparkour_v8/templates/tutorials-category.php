<?php
/**
 * Template Name: Tutorials — Category
 *
 * tutorials-category.php — TutorialsCategory.
 *
 * Ported from src/stories/Pages/TutorialsCategory/TutorialsCategory.js.
 * There is no v3 frame for this view — it is the series-detail lessons
 * band (`lmIjE`) without the sidebar or series header: one full-width
 * horizontal lesson-card shelf per child `tutorial-category`, falling back
 * to the parent when a tutorial has no child term.
 *
 * Section order: breadcrumb → masthead → view rail → filter row
 * (category select + kind toggles in a second cell; demos off by default) →
 * category shelves → Train In Person → onward. Nav/footer are
 * get_header()/get_footer(), outside the one <main>.
 *
 * `/tutorials/` is the tutorial CPT archive, so this page is reached at
 * `/tutorials/category/` via a top rewrite onto pagename `tutorials-category`
 * (see lp_tutorials_series_rewrite()).
 *
 * Shelf class strings are copied from taxonomy-lp_series.php (the series
 * category layout). Duplication is intentional until a coordinator promotes
 * a shared shelf partial.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_archive_url      = (string) get_post_type_archive_link( 'lp_tutorial' );
$lp_series_url       = lp_tutorials_series_url();
$lp_values           = lp_tutorial_filter_values();
$lp_category         = $lp_values['tutorial_category'];
$lp_kinds            = lp_tutorial_kind_filter_values();
$lp_category_options = lp_tutorial_category_filter_options();
$lp_shelves          = lp_category_board_shelves( $lp_category, $lp_kinds );
$lp_category_term    = '' !== $lp_category
	? get_term_by( 'slug', $lp_category, 'tutorial-category' )
	: null;
if ( ! $lp_category_term instanceof WP_Term ) {
	$lp_category_term = null;
}

$lp_kind_toggles = array(
	'tutorial'      => 'Tutorials',
	'challenge'     => 'Challenges',
	'demonstration' => 'Demos',
);

get_header();
?>

<main id="main">
	<?php
	$lp_crumbs = array(
		array(
			'label' => 'HOME',
			'href'  => home_url( '/' ),
		),
		array(
			'label' => 'TUTORIALS',
			'href'  => $lp_archive_url,
		),
	);
	if ( $lp_category_term ) {
		$lp_crumbs[] = array(
			'label' => 'CATEGORIES',
			'href'  => lp_tutorials_category_url(),
		);
		$lp_crumbs[] = array(
			'label' => strtoupper( $lp_category_term->name ),
		);
	} else {
		$lp_crumbs[] = array(
			'label' => 'CATEGORIES',
		);
	}

	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => $lp_crumbs,
			'action' => array(
				'label' => 'BY TUTORIAL ↗',
				'href'  => $lp_archive_url,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'Categories.',
			'note'  => sprintf(
				'%d categories. %d coached videos, filed by category.',
				lp_tutorials_category_count(),
				lp_tutorials_published_count()
			),
		)
	);

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'tutorials',
			'aria_label' => 'Tutorials view',
			'tabs'       => lp_tutorials_view_tabs( 'category' ),
		)
	);

	?>
	<div class="flex flex-wrap bg-base-100 border-b border-base-300" data-component="filter-grid">
		<form method="get" action="<?php echo esc_url( lp_tutorials_category_url() ); ?>" data-filter-form class="flex-1 min-w-[220px] px-6 py-4">
			<input type="hidden" name="tutorial_kinds" value="<?php echo esc_attr( implode( ',', $lp_kinds ) ); ?>" />
			<?php
			lp_part(
				'forms/select',
				array(
					'label'   => 'Category',
					'name'    => 'tutorial_category',
					'options' => $lp_category_options,
					'value'   => $lp_category,
				)
			);
			lp_part(
				'elements/button',
				array(
					'type'  => 'submit',
					'label' => 'Apply category filter',
					'class' => 'sr-only',
				)
			);
			?>
		</form>
		<div class="shrink-0 max-w-full px-6 py-4 border-l border-base-300" data-component="kind-toggles">
			<div class="flex flex-col gap-[13px]">
				<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-base-content"><?php echo esc_html__( 'Type', 'londonparkour_v8' ); ?></span>
				<div class="flex flex-wrap items-center gap-2" role="group" aria-label="<?php echo esc_attr__( 'Video type', 'londonparkour_v8' ); ?>">
					<?php
					foreach ( $lp_kind_toggles as $lp_kind => $lp_kind_label ) :
						$lp_kind_on = in_array( $lp_kind, $lp_kinds, true );
						lp_part(
							'elements/button',
							array(
								'variant'    => $lp_kind_on ? 'primary' : 'ghost',
								'label'      => $lp_kind_label,
								'href'       => lp_tutorials_category_filter_url( $lp_category, lp_tutorial_kind_toggle( $lp_kind, $lp_kinds ) ),
								'aria_label' => $lp_kind_on
									? sprintf( __( 'Hide %s', 'londonparkour_v8' ), $lp_kind_label )
									: sprintf( __( 'Show %s', 'londonparkour_v8' ), $lp_kind_label ),
								'data_attrs' => array(
									'aria-pressed' => $lp_kind_on ? 'true' : 'false',
								),
							)
						);
					endforeach;
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="w-full bg-neutral" data-section="categories">
		<div class="px-6 lg:px-16 py-scale-2xl">
			<div class="flex flex-col gap-[64px]">
				<?php foreach ( $lp_shelves as $lp_shelf ) : ?>
					<div class="flex flex-col gap-[24px]" data-component="series-category-shelf">
						<div class="flex items-center justify-between gap-3">
							<div class="flex items-center gap-3 min-w-0">
								<?php if ( '' !== ( $lp_shelf['glyph_id'] ?? '' ) ) : ?>
									<span class="w-7 h-7 shrink-0 text-primary" aria-hidden="true"><?php lp_icon( $lp_shelf['glyph_id'], 'w-7 h-7' ); ?></span>
								<?php endif; ?>
								<h3 class="font-heading text-[26px] font-medium tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_shelf['title'] ); ?></h3>
							</div>
							<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50 whitespace-nowrap"><?php echo esc_html( $lp_shelf['meta'] ); ?></span>
						</div>
						<?php lp_part( 'elements/rule', array( 'tone' => 'board' ) ); ?>
						<div class="flex flex-col gap-4" data-component="series-card-shelf">
							<div class="flex gap-4 overflow-x-auto snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-shelf-scroller>
								<?php foreach ( $lp_shelf['posts'] as $lp_si => $lp_lesson ) : ?>
									<div class="w-[248px] shrink-0 snap-start">
										<?php lp_part( 'components/video-card', lp_video_card_args_from_tutorial( $lp_lesson, 'lesson', $lp_si + 1 ) ); ?>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="flex items-center gap-4">
								<div class="relative flex-1 h-[2px] bg-neutral-content/10" aria-hidden="true">
									<div class="absolute left-0 top-0 h-[2px] bg-primary" data-shelf-thumb></div>
								</div>
								<div class="flex gap-2">
									<?php
									lp_part(
										'elements/button',
										array(
											'variant'    => 'shelf_nav',
											'label'      => '‹',
											'aria_label' => __( 'Previous lessons', 'londonparkour_v8' ),
											'data_attrs' => array( 'data-shelf-prev' => '' ),
										)
									);
									lp_part(
										'elements/button',
										array(
											'variant'    => 'shelf_nav',
											'label'      => '›',
											'aria_label' => __( 'Next lessons', 'londonparkour_v8' ),
											'data_attrs' => array( 'data-shelf-next' => '' ),
										)
									);
									?>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php
	lp_render_block( 'train_in_person', array() );

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← THE SERIES',
				'label'   => 'The twelve lines',
				'href'    => $lp_series_url,
			),
			'next' => array(
				'keyword' => 'BY TUTORIAL →',
				'label'   => 'The full tutorial board',
				'href'    => $lp_archive_url,
			),
		)
	);
	?>
</main>

<?php
get_footer();
