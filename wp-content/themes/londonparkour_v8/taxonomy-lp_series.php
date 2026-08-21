<?php
/**
 * taxonomy-lp_series.php — TutorialsSeries.
 *
 * Ported from src/stories/Pages/TutorialsSeries/TutorialsSeries.js (`DW5fa`).
 * Read that file's docblock before touching this one.
 *
 * Section order: breadcrumb → masthead → view rail → "02 The Lessons"
 * (sidebar + series header + category shelves or a full-card grid) →
 * Train In Person → onward. Nav/footer are get_header()/get_footer(),
 * outside the one <main>.
 *
 * Layout is the series term's ACF `layout` field: `categories` (default) is
 * one horizontal lesson-card shelf per child tutorial-category, with a 2px
 * track and prev/next arrows; `grid` is one board of the same lesson cards.
 * Runtime is computed (YouTube data, then transcript) — ACF tutorial
 * `duration` and series `episode_count` / `duration` are ignored at render.
 *
 * Watch progress (WATCHED / RESUME / "4 OF 8 WATCHED" / progress bar fill)
 * needs a per-user store this theme does not have — omitted rather than faked.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_term = get_queried_object();
if ( ! ( $lp_term instanceof WP_Term ) || 'lp_series' !== $lp_term->taxonomy ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	return;
}

$lp_term_id     = (int) $lp_term->term_id;
$lp_fields      = function_exists( 'get_fields' ) ? get_fields( 'term_' . $lp_term_id ) : array();
$lp_fields      = is_array( $lp_fields ) ? $lp_fields : array();
$lp_logline     = (string) ( $lp_fields['logline'] ?? '' );
$lp_coach       = (string) ( $lp_fields['coach_label'] ?? '' );
$lp_layout_raw  = (string) ( $lp_fields['layout'] ?? 'categories' );
$lp_is_grid     = 'grid' === $lp_layout_raw;
$lp_cta_label   = 'PLAY SERIES';

$lp_archive_url = (string) get_post_type_archive_link( 'lp_tutorial' );
$lp_series_url  = lp_tutorials_series_url();
$lp_term_link   = get_term_link( $lp_term );
$lp_term_url    = is_wp_error( $lp_term_link ) ? $lp_archive_url : (string) $lp_term_link;

$lp_all_series      = lp_series_terms_nonempty();
$lp_total_tutorials = (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );

$lp_current_lessons = lp_tutorials_in_series( $lp_term_id );
$lp_poster_id       = lp_series_poster_id( $lp_term_id, $lp_fields, $lp_current_lessons[0] ?? null );
$lp_lesson_count    = count( $lp_current_lessons );
$lp_total_secs      = lp_tutorials_total_seconds( $lp_current_lessons );
$lp_mins_label      = lp_format_runtime_minutes( $lp_total_secs );
if ( '' !== $lp_mins_label && false === stripos( $lp_mins_label, 'TOTAL' ) ) {
	$lp_mins_label .= ' TOTAL';
}

$lp_series_index = 1;
foreach ( $lp_all_series as $lp_i => $lp_s ) {
	if ( (int) $lp_s->term_id === $lp_term_id ) {
		$lp_series_index = $lp_i + 1;
		break;
	}
}

list( , $lp_level ) = lp_series_label_parts( (string) ( $lp_fields['series_label'] ?? '' ), $lp_series_index );

$lp_facts = array_values(
	array_filter(
		array(
			$lp_lesson_count ? sprintf( '%02d LESSONS', $lp_lesson_count ) : '',
			$lp_mins_label,
			$lp_level ? strtoupper( $lp_level ) : '',
			$lp_coach ? 'COACH · ' . strtoupper( $lp_coach ) : '',
		)
	)
);

$lp_play_href = $lp_current_lessons
	? (string) get_permalink( $lp_current_lessons[0] )
	: $lp_term_url;

$lp_shelves = $lp_is_grid ? array() : lp_series_category_shelves( $lp_current_lessons );

$lp_masthead = array(
	'title' => $lp_term->name . ( str_ends_with( $lp_term->name, '.' ) ? '' : '.' ),
);
if ( '' !== $lp_logline ) {
	$lp_masthead['note'] = $lp_logline;
}

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
				array(
					'label' => 'TUTORIALS',
					'href'  => $lp_archive_url,
				),
				array(
					'label' => 'SERIES',
					'href'  => $lp_series_url,
				),
				array(
					'label' => strtoupper( $lp_term->name ),
				),
			),
			'action' => array(
				'label' => 'ALL SERIES ↗',
				'href'  => $lp_series_url,
			),
		)
	);

	lp_part( 'components/page-masthead', $lp_masthead );

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'tutorials',
			'aria_label' => 'Tutorials view',
			'tabs'       => lp_tutorials_view_tabs( 'series' ),
		)
	);
	?>

	<div class="w-full bg-neutral" data-section="lessons">
		<div class="px-6 lg:px-16 py-scale-2xl">
			<div class="flex flex-col lg:flex-row gap-9 items-start">
				<aside class="w-full lg:w-[360px] shrink-0 flex flex-col bg-secondary border border-neutral-content/10 overflow-hidden" data-component="series-sidebar">
					<div class="flex flex-col gap-2 px-4 pt-5 pb-4 border-b border-neutral-content/10">
						<span class="font-label text-[10px] font-bold tracking-[1.2px] uppercase text-primary">SERIES</span>
						<h2 class="font-heading text-[22px] font-semibold tracking-[-0.4px] text-neutral-content m-0">Every line.</h2>
						<p class="font-label text-[11px] font-normal tracking-[0.2px] text-neutral-content/50 m-0"><?php echo esc_html( sprintf( '%d episodes · pick a series', $lp_total_tutorials ) ); ?></p>
					</div>
					<nav class="flex flex-col gap-3 p-3.5" aria-label="<?php echo esc_attr__( 'Series list', 'londonparkour_v8' ); ?>">
						<?php
						$lp_idx = 0;
						foreach ( $lp_all_series as $lp_item ) :
							++$lp_idx;
							$lp_item_fields = function_exists( 'get_fields' ) ? get_fields( 'term_' . $lp_item->term_id ) : array();
							$lp_item_fields = is_array( $lp_item_fields ) ? $lp_item_fields : array();
							$lp_item_tag    = (string) ( $lp_item_fields['tag'] ?? '' );
							$lp_item_eps    = lp_series_published_count( (int) $lp_item->term_id );
							$lp_item_link   = get_term_link( $lp_item );
							$lp_item_href   = is_wp_error( $lp_item_link ) ? '#' : (string) $lp_item_link;
							$lp_active      = (int) $lp_item->term_id === $lp_term_id;
							$lp_item_poster = lp_series_poster_id( (int) $lp_item->term_id, $lp_item_fields );
							$lp_item_cls    = $lp_active
								? 'group flex h-[108px] overflow-hidden bg-neutral border border-primary no-underline text-left hover:bg-primary'
								: 'group flex h-[108px] overflow-hidden bg-neutral border border-neutral-content/10 no-underline text-left hover:bg-primary';
							$lp_no_cls      = $lp_active
								? 'font-label text-[10px] font-bold tracking-[0.7px] uppercase text-primary group-hover:text-neutral'
								: 'font-label text-[10px] font-bold tracking-[0.7px] uppercase text-neutral-content/50 group-hover:text-neutral/70';
							$lp_tag_cls     = $lp_active
								? 'font-label text-[9px] font-bold tracking-[0.7px] uppercase text-primary group-hover:text-neutral'
								: 'font-label text-[9px] font-bold tracking-[0.7px] uppercase text-neutral-content/50 group-hover:text-neutral/70';
							?>
							<a
								href="<?php echo esc_url( $lp_item_href ); ?>"
								class="<?php echo esc_attr( $lp_item_cls ); ?>"
								data-component="series-sidebar-item"
								<?php echo $lp_active ? ' aria-current="page"' : ''; ?>
							>
								<span class="relative h-full aspect-[16/9] shrink-0 bg-neutral overflow-hidden" aria-hidden="true">
									<?php
									if ( $lp_item_poster ) {
										lp_part(
											'components/media-photo',
											array(
												'image_id' => $lp_item_poster,
												'alt'      => '',
												'layout'   => 'fill',
												'size'     => 'lp_thumb',
												'sizes'    => '192px',
											)
										);
									}
									?>
									<span class="absolute inset-0 bg-gradient-to-r from-transparent to-neutral group-hover:to-primary"></span>
								</span>
								<span class="flex flex-col gap-1.5 min-w-0 flex-1 justify-center px-4 py-3.5">
									<span class="flex items-center gap-2">
										<span class="<?php echo esc_attr( $lp_no_cls ); ?>"><?php echo esc_html( sprintf( 'S%02d', $lp_idx ) ); ?></span>
										<?php if ( '' !== $lp_item_tag ) : ?>
											<span class="<?php echo esc_attr( $lp_tag_cls ); ?>"><?php echo esc_html( $lp_item_tag ); ?></span>
										<?php endif; ?>
									</span>
									<span class="font-heading text-[16px] font-semibold tracking-[-0.2px] leading-[1.1] text-neutral-content group-hover:text-neutral"><?php echo esc_html( $lp_item->name ); ?></span>
									<?php if ( $lp_item_eps ) : ?>
										<span class="font-label text-[10px] font-semibold tracking-[0.7px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( sprintf( '%d EPISODES', $lp_item_eps ) ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						<?php endforeach; ?>
					</nav>
				</aside>

				<div class="flex-1 min-w-0 flex flex-col gap-[64px]">
					<div class="flex flex-col gap-6" data-component="series-header">
						<div class="flex flex-col lg:flex-row gap-6 lg:gap-8 lg:items-stretch">
							<div class="w-full lg:w-1/2 flex flex-col gap-4 min-w-0 justify-center">
								<div class="flex items-center gap-3">
									<?php
									lp_part(
										'elements/badge',
										array(
											'variant' => 'paper',
											'label'   => sprintf( 'LINE %02d', $lp_series_index ),
										)
									);
									?>
									<span class="font-label text-[11px] font-bold uppercase tracking-[1.2px] text-primary">ACTIVE SERIES</span>
								</div>
								<h2 class="font-heading text-[40px] font-semibold tracking-[-1.1px] leading-[1.02] text-neutral-content m-0 [text-box:normal]"><?php echo esc_html( $lp_term->name ); ?></h2>
								<?php if ( '' !== $lp_logline ) : ?>
									<p class="font-body text-[16px] leading-[1.5] text-neutral-content/65 m-0"><?php echo esc_html( $lp_logline ); ?></p>
								<?php endif; ?>
								<div class="flex flex-wrap items-center gap-4 pt-1">
									<?php
									lp_part(
										'elements/button',
										array(
											'label'   => $lp_cta_label,
											'variant' => 'primary',
											'href'    => $lp_play_href,
										)
									);
									?>
								</div>
								<?php if ( $lp_facts ) : ?>
									<div class="flex flex-wrap items-center gap-x-3 gap-y-1 font-label text-[10px] uppercase tracking-[0.8px] text-neutral-content/50">
										<?php foreach ( $lp_facts as $lp_fi => $lp_fact ) : ?>
											<?php if ( $lp_fi ) : ?>
												<span aria-hidden="true">·</span>
											<?php endif; ?>
											<span><?php echo esc_html( $lp_fact ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<?php if ( $lp_poster_id ) : ?>
								<div class="w-full lg:w-1/2 aspect-[16/9] bg-secondary border border-neutral-content/10 relative overflow-hidden">
									<?php
									lp_part(
										'components/media-photo',
										array(
											'image_id' => $lp_poster_id,
											'alt'      => $lp_term->name,
											'layout'   => 'fill',
											'size'     => 'lp_wide',
											'sizes'    => '(min-width: 1024px) 40vw, 100vw',
										)
									);
									?>
								</div>
							<?php else : ?>
								<div class="w-full lg:w-1/2 aspect-[16/9] bg-secondary border border-neutral-content/10 flex items-center justify-center" aria-hidden="true">
									<span class="font-label text-[24px] text-primary">▶</span>
								</div>
							<?php endif; ?>
						</div>
						<div class="h-[2px] w-full bg-neutral-content/10" aria-hidden="true"></div>
					</div>

					<div class="flex flex-col gap-[64px]">
						<?php if ( $lp_is_grid ) : ?>
							<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6" data-component="series-card-grid">
								<?php foreach ( $lp_current_lessons as $lp_gi => $lp_lesson ) : ?>
									<?php lp_part( 'components/video-card', lp_video_card_args_from_tutorial( $lp_lesson, 'lesson', $lp_gi + 1 ) ); ?>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
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
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	lp_render_block( 'train_in_person', array() );

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← ALL SERIES',
				'label'   => 'Back to the series index',
				'href'    => $lp_series_url,
			),
			'next' => array(
				'keyword' => 'TRAIN IN PERSON →',
				'label'   => 'Book a class at Vauxhall',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
