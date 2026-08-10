<?php
/**
 * single-lp_tutorial.php — TutorialDetail.
 *
 * Ported from src/stories/Pages/TutorialDetail/TutorialDetail.js (`Fyf9i`).
 * Read that file's docblock before touching this one.
 *
 * Section order: breadcrumb → masthead → view rail → filter grid →
 * "01 Now Playing" → "02 The Series" → "03 Video Details" →
 * "04 Demonstrations" (when seeded) → Train In Person → onward.
 * Nav/footer are get_header()/get_footer(), outside the one <main>.
 *
 * ── Native / ACF data ───────────────────────────────────────────────────
 * Title, excerpt, featured image are native. `duration`, `video_url`,
 * `coaches`, `body`, `key_takeaways`, `closing_*`, `demonstrations` are
 * group_lp_tutorial. Series board rows are sibling `lp_tutorial` posts in
 * the same `lp_series` term. Filter grid posts to the tutorials archive
 * (same GET contract as archive-lp_tutorial.php).
 *
 * ── Gaps ────────────────────────────────────────────────────────────────
 * Resources with `href="#"` in the source are omitted as interactive links
 * (Port Brief: no dead anchors). Watch-progress tones (WATCHED / NOW PLAYING
 * fill) are derived only for the current post as NOW PLAYING; others are
 * WATCH or NEW — no per-user progress store.
 * Player chrome (scrub position, quality chip) uses duration + defaults;
 * there is no live player JS in this port.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

while ( have_posts() ) :
	the_post();

	$lp_post_id   = get_the_ID();
	$lp_title     = get_the_title();
	$lp_excerpt   = get_the_excerpt();
	$lp_thumb_id  = get_post_thumbnail_id() ?: 0;
	$lp_duration  = function_exists( 'get_field' ) ? (string) get_field( 'duration' ) : '';
	$lp_video_url = function_exists( 'get_field' ) ? (string) get_field( 'video_url' ) : '';
	$lp_coaches   = function_exists( 'get_field' ) ? get_field( 'coaches' ) : array();
	$lp_coach_ids = array_filter( array_map( 'intval', (array) $lp_coaches ) );

	$lp_body       = function_exists( 'get_field' ) ? get_field( 'body' ) : array();
	$lp_takeaways  = function_exists( 'get_field' ) ? get_field( 'key_takeaways' ) : array();
	$lp_closing    = function_exists( 'get_field' ) ? (string) get_field( 'closing_text' ) : '';
	$lp_ai_note    = function_exists( 'get_field' ) ? (string) get_field( 'closing_ai_note' ) : '';
	$lp_demos      = function_exists( 'get_field' ) ? get_field( 'demonstrations' ) : array();
	$lp_body       = is_array( $lp_body ) ? $lp_body : array();
	$lp_takeaways  = is_array( $lp_takeaways ) ? $lp_takeaways : array();
	$lp_demos      = is_array( $lp_demos ) ? $lp_demos : array();

	$lp_series_terms = get_the_terms( $lp_post_id, 'lp_series' );
	$lp_series_term  = ( is_array( $lp_series_terms ) && $lp_series_terms ) ? $lp_series_terms[0] : null;
	$lp_level_terms  = get_the_terms( $lp_post_id, 'lp_level' );
	$lp_level_term   = ( is_array( $lp_level_terms ) && $lp_level_terms ) ? $lp_level_terms[0] : null;

	$lp_archive_url = (string) get_post_type_archive_link( 'lp_tutorial' );
	$lp_series_url  = $lp_archive_url;
	if ( $lp_series_term ) {
		$lp_link = get_term_link( $lp_series_term );
		if ( ! is_wp_error( $lp_link ) ) {
			$lp_series_url = (string) $lp_link;
		}
	}

	$lp_parent = ( $lp_series_term && $lp_series_term->parent ) ? get_term( $lp_series_term->parent, 'lp_series' ) : null;
	$lp_category_name = ( $lp_parent && ! is_wp_error( $lp_parent ) ) ? $lp_parent->name : '';

	$lp_siblings = array();
	if ( $lp_series_term ) {
		$lp_sib_q = new WP_Query(
			array(
				'post_type'      => 'lp_tutorial',
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'date'       => 'ASC',
				),
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => 'lp_series',
						'field'    => 'term_id',
						'terms'    => (int) $lp_series_term->term_id,
					),
				),
			)
		);
		$lp_siblings = $lp_sib_q->posts;
	}
	if ( ! $lp_siblings ) {
		$lp_siblings = array( get_post( $lp_post_id ) );
	}

	$lp_lesson_index = 1;
	$lp_lesson_total = count( $lp_siblings );
	$lp_next_post    = null;
	foreach ( $lp_siblings as $lp_i => $lp_sib ) {
		if ( (int) $lp_sib->ID === (int) $lp_post_id ) {
			$lp_lesson_index = $lp_i + 1;
			$lp_next_post    = $lp_siblings[ $lp_i + 1 ] ?? null;
			break;
		}
	}

	$lp_total_tutorials = (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );
	$lp_all_series      = get_terms( array( 'taxonomy' => 'lp_series', 'hide_empty' => false ) );
	$lp_total_series    = is_array( $lp_all_series ) ? count( $lp_all_series ) : 0;

	$lp_values          = lp_tutorial_filter_values();
	$lp_category_terms  = get_terms( array( 'taxonomy' => 'lp_series', 'parent' => 0, 'hide_empty' => false ) );
	$lp_category_terms  = is_array( $lp_category_terms ) ? $lp_category_terms : array();
	$lp_category_options = array( array( 'value' => '', 'label' => 'All categories' ) );
	foreach ( $lp_category_terms as $lp_t ) {
		$lp_category_options[] = array( 'value' => $lp_t->slug, 'label' => $lp_t->name );
	}
	$lp_move_options = array( array( 'value' => '', 'label' => 'All moves' ) );

	$lp_board_rows = array();
	foreach ( $lp_siblings as $lp_i => $lp_sib ) {
		$lp_sib_dur   = function_exists( 'get_field' ) ? (string) get_field( 'duration', $lp_sib->ID ) : '';
		$lp_is_here   = (int) $lp_sib->ID === (int) $lp_post_id;
		$lp_is_new    = ( time() - get_post_time( 'U', true, $lp_sib ) ) <= ( 30 * DAY_IN_SECONDS );
		$lp_level_n   = $lp_level_term ? $lp_level_term->name : '';
		$lp_board_rows[] = array(
			'part' => 'components/board-row',
			'args' => array(
				'time'             => $lp_sib_dur ? $lp_sib_dur : sprintf( '%02d', $lp_i + 1 ),
				'date_label'       => sprintf( 'LESSON %02d', $lp_i + 1 ),
				'title'            => get_the_title( $lp_sib ),
				'subtitle'         => get_the_excerpt( $lp_sib ),
				'location'         => $lp_series_term ? $lp_series_term->name : '',
				'level'            => $lp_level_n,
				'location_icon_id' => 'icon-tag',
				'level_icon_id'    => 'icon-level-beginner',
				'spaces'           => $lp_is_here ? 'NOW PLAYING' : ( $lp_is_new ? 'NEW' : 'WATCH' ),
				'tone'             => $lp_is_here ? 'now_playing' : ( $lp_is_new ? 'new' : 'available' ),
				'href'             => $lp_is_here ? '' : (string) get_permalink( $lp_sib ),
			),
		);
	}

	$lp_stage_meta_bits = array_filter(
		array(
			$lp_category_name ? strtoupper( $lp_category_name ) : '',
			$lp_series_term ? strtoupper( $lp_series_term->name ) : '',
			$lp_level_term ? strtoupper( $lp_level_term->name ) : '',
		)
	);

	$lp_aside_rows = array_values(
		array_filter(
			array(
				$lp_series_term ? array( 'label' => 'SERIES', 'value' => $lp_series_term->name ) : null,
				$lp_category_name ? array( 'label' => 'CATEGORY', 'value' => $lp_category_name ) : null,
				$lp_level_term ? array( 'label' => 'LEVEL', 'value' => $lp_level_term->name ) : null,
				array(
					'label' => 'RUNTIME',
					'value' => trim(
						sprintf(
							'%d lesson%s%s',
							$lp_lesson_total,
							1 === $lp_lesson_total ? '' : 's',
							$lp_duration ? ' · ' . $lp_duration : ''
						)
					),
				),
			)
		)
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
	if ( $lp_category_name ) {
		$lp_crumbs[] = array(
			'label' => strtoupper( $lp_category_name ),
			'href'  => $lp_archive_url,
		);
	}
	if ( $lp_series_term ) {
		$lp_crumbs[] = array( 'label' => strtoupper( $lp_series_term->name ) );
	} else {
		$lp_crumbs[] = array( 'label' => strtoupper( $lp_title ) );
	}

	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => $lp_crumbs,
			'action' => array(
				'label' => 'ALL TUTORIALS ↗',
				'href'  => $lp_archive_url,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => $lp_title,
			'note'  => $lp_excerpt
				? $lp_excerpt
				: ( $lp_series_term
					? sprintf( 'A lesson in the %s series.', $lp_series_term->name )
					: '' ),
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
					'href'    => $lp_series_url,
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
					'value'   => $lp_values['tutorial_category'],
				),
				array(
					'type'    => 'select',
					'key'     => 'Move',
					'name'    => 'tutorial_move',
					'options' => $lp_move_options,
					'value'   => $lp_values['tutorial_move'],
				),
				array(
					'type'    => 'select',
					'key'     => 'Sort',
					'name'    => 'tutorial_sort',
					'options' => array(
						array( 'value' => 'sequence', 'label' => 'Sequence' ),
						array( 'value' => 'newest', 'label' => 'Newest' ),
					),
					'value'   => $lp_values['tutorial_sort'],
				),
			),
			'action' => $lp_archive_url,
			'submit' => 'Apply tutorial filters',
		)
	);
	?>

	<div class="w-full bg-base-100" data-section="now-playing">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[26px]">
			<?php lp_part( 'elements/rule', array( 'tone' => 'hairline' ) ); ?>
			<div class="flex flex-col lg:flex-row gap-[32px] items-start">
				<div class="flex-1 min-w-0">
					<?php
					lp_part(
						'components/video-stage',
						array(
							'image_id'       => $lp_thumb_id,
							'status_label'   => sprintf( 'NOW PLAYING · LESSON %02d', $lp_lesson_index ),
							'quality_label'  => $lp_duration ? 'EN · HD · ' . $lp_duration : 'EN · HD',
							'badge_label'    => $lp_series_term
								? sprintf( '%s · LESSON %02d OF %02d', strtoupper( $lp_series_term->name ), $lp_lesson_index, $lp_lesson_total )
								: sprintf( 'LESSON %02d OF %02d', $lp_lesson_index, $lp_lesson_total ),
							'duration_label' => $lp_duration,
							'title'          => $lp_title,
							'stage_meta'     => implode( ' · ', $lp_stage_meta_bits ),
							'time_label'     => $lp_duration ? '00:00 / ' . $lp_duration : '',
							'up_next_label'  => $lp_next_post
								? 'UP NEXT — ' . get_the_title( $lp_next_post )
								: '',
							'progress'       => 0,
						)
					);
					?>
				</div>
				<div class="w-full lg:w-[340px] shrink-0 flex flex-col gap-[24px]">
					<?php
					lp_part(
						'components/aside-panel',
						array(
							'title'      => 'THIS TUTORIAL',
							'spots_left' => sprintf( 'LESSON %d / %d', $lp_lesson_index, $lp_lesson_total ),
							'rows'       => $lp_aside_rows,
							'cta_label'  => $lp_series_term ? 'VIEW SERIES' : 'ALL TUTORIALS',
							'href'       => $lp_series_url,
							'note'       => 'Free to watch. No account needed. New tutorials added every month.',
							'surface'    => 'page',
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="w-full bg-neutral" data-section="series">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[20px]">
			<?php
			lp_part(
				'components/meta-row',
				array(
					'left'    => '02 — THE SERIES',
					'right'   => sprintf( '%d LESSONS%s', $lp_lesson_total, $lp_duration ? ' · ' . strtoupper( $lp_duration ) : '' ),
					'surface' => 'board',
				)
			);
			lp_part( 'elements/rule', array( 'tone' => 'board' ) );
			lp_part(
				'components/board-shell',
				array(
					'board_title' => $lp_series_term
						? strtoupper( $lp_series_term->name ) . ' — LESSON BOARD'
						: 'LESSON BOARD',
					'columns'     => array( 'RUNTIME', 'LESSON', 'MOVE', 'LEVEL', 'STATUS' ),
					'rows'        => $lp_board_rows,
					'foot_left'   => $lp_series_term ? 'PLAY ALL LESSONS →' : '',
					'foot_href'   => $lp_series_term ? $lp_series_url : '',
					'foot_right'  => sprintf( '%d TUTORIALS · %d SERIES · FREE TO WATCH', $lp_total_tutorials, $lp_total_series ),
				)
			);
			?>
		</div>
	</div>

	<?php if ( $lp_body || $lp_takeaways || $lp_closing ) : ?>
	<div class="w-full bg-base-100" data-section="video-details">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[32px]">
			<?php
			lp_part(
				'components/meta-row',
				array(
					'left'    => 'VIDEO DETAILS',
					'right'   => '',
					'surface' => 'page',
				)
			);
			lp_part( 'elements/rule', array( 'tone' => 'hairline' ) );
			?>
			<?php if ( $lp_body ) : ?>
				<div class="grid sm:grid-cols-2 gap-[32px]">
					<?php foreach ( $lp_body as $lp_para ) : ?>
						<div class="flex flex-col gap-[10px]">
							<?php
							lp_part(
								'elements/glyph-label',
								array(
									'label'   => (string) ( $lp_para['index'] ?? '' ),
									'surface' => 'page',
									'tone'    => 'muted',
								)
							);
							?>
							<p class="m-0 font-body text-[13px] leading-[1.6] text-base-content/65"><?php echo esc_html( (string) ( $lp_para['text'] ?? '' ) ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $lp_takeaways ) : ?>
				<div class="flex flex-col gap-[16px]">
					<div class="flex items-center justify-between gap-3 pb-2.5 border-b border-base-300">
						<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content">KEY TAKEAWAYS</span>
						<span class="font-label text-[10px] font-normal text-base-content/65"><?php echo esc_html( sprintf( '%02d', count( $lp_takeaways ) ) ); ?></span>
					</div>
					<ul role="list" class="flex flex-col gap-[14px] m-0 p-0 list-none">
						<?php foreach ( $lp_takeaways as $lp_t ) : ?>
							<li>
								<?php
								lp_part(
									'components/checklist-item',
									array(
										'text' => is_array( $lp_t ) ? (string) ( $lp_t['text'] ?? '' ) : (string) $lp_t,
									)
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( $lp_closing ) : ?>
				<?php lp_part( 'elements/rule', array( 'tone' => 'ink' ) ); ?>
				<div class="flex flex-col gap-[10px]">
					<p class="font-body text-[24px] leading-[1.4] text-base-content"><?php echo esc_html( $lp_closing ); ?></p>
					<?php if ( $lp_ai_note ) : ?>
						<p class="font-label text-[10px] font-normal text-base-content/65"><?php echo esc_html( $lp_ai_note ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $lp_demos ) : ?>
	<div class="w-full bg-base-100" data-section="demonstrations">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[32px]">
			<?php
			lp_part( 'elements/rule', array( 'tone' => 'hairline' ) );
			lp_part(
				'components/section-head',
				array(
					'heading' => 'Two demonstrations.',
					'note'    => 'The same movement at full speed.',
					'surface' => 'page',
				)
			);
			?>
			<div class="grid sm:grid-cols-2 gap-[24px]">
				<?php foreach ( $lp_demos as $lp_demo ) : ?>
					<?php
					lp_part(
						'components/video-card',
						array(
							'variant'    => 'compact',
							'image_id'   => ! empty( $lp_demo['image'] ) ? (int) $lp_demo['image'] : 0,
							'kicker'     => (string) ( $lp_demo['kicker'] ?? '' ),
							'title'      => (string) ( $lp_demo['title'] ?? '' ),
							'meta'       => (string) ( $lp_demo['duration'] ?? '' ),
							'foot_label' => (string) ( $lp_demo['foot_label'] ?? '' ),
							'note'       => (string) ( $lp_demo['note'] ?? '' ),
						)
					);
					?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php
	lp_render_block( 'train_in_person', array() );

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← THE SERIES',
				'label'   => $lp_series_term ? 'Back to ' . $lp_series_term->name : 'Back to tutorials',
				'href'    => $lp_series_url,
			),
			'next' => $lp_next_post
				? array(
					'keyword' => 'NEXT LESSON →',
					'label'   => get_the_title( $lp_next_post ),
					'href'    => (string) get_permalink( $lp_next_post ),
				)
				: array(
					'keyword' => 'TRAIN IN PERSON →',
					'label'   => 'Book a class',
					'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
				),
		)
	);
	?>
</main>

	<?php
endwhile;

get_footer();
