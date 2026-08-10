<?php
/**
 * taxonomy-lp_series.php — TutorialsSeries.
 *
 * Ported from src/stories/Pages/TutorialsSeries/TutorialsSeries.js (`DW5fa`).
 * Read that file's docblock before touching this one.
 *
 * Section order: breadcrumb → masthead → view rail → "02 The Lessons"
 * (sidebar + series header + lesson boards) → Train In Person → onward.
 * Nav/footer are get_header()/get_footer(), outside the one <main>.
 *
 * Train In Person is the already-ported block, called via lp_render_block().
 *
 * ── Data projection ─────────────────────────────────────────────────────
 * Masthead title / note come from the term name and `logline` term field.
 * Sidebar lists every `lp_series` term (menu_order / term_id), tagging the
 * current one. Lesson boards: the current series first, then up to two
 * sibling series that have published tutorials (the source shows three LINE
 * boards as composition furniture — we only emit boards that have real
 * lessons). Each lesson row is an `lp_tutorial` in that term.
 *
 * ── Gaps (do not invent) ────────────────────────────────────────────────
 * Watch progress (WATCHED / RESUME / "4 OF 8 WATCHED" / progress bar fill)
 * needs a per-user store this theme does not have — same class as
 * archive-lp_tutorial.php's RESUME gap. Rows use WATCH, or NEW when the
 * tutorial is ≤30 days old. The progress bar and watched-count copy are
 * omitted rather than faked at 50%.
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

$lp_term_id   = (int) $lp_term->term_id;
$lp_fields    = function_exists( 'get_fields' ) ? get_fields( 'term_' . $lp_term_id ) : array();
$lp_fields    = is_array( $lp_fields ) ? $lp_fields : array();
$lp_logline   = (string) ( $lp_fields['logline'] ?? '' );
$lp_tag       = (string) ( $lp_fields['tag'] ?? '' );
$lp_ep_count  = isset( $lp_fields['episode_count'] ) ? (int) $lp_fields['episode_count'] : 0;
$lp_duration  = (string) ( $lp_fields['duration'] ?? '' );
$lp_coach     = (string) ( $lp_fields['coach_label'] ?? '' );
$lp_cta_label = (string) ( $lp_fields['cta_label'] ?? 'PLAY SERIES' );

$lp_archive_url = (string) get_post_type_archive_link( 'lp_tutorial' );
$lp_term_link   = get_term_link( $lp_term );
$lp_term_url    = is_wp_error( $lp_term_link ) ? $lp_archive_url : (string) $lp_term_link;

$lp_all_series = get_terms(
	array(
		'taxonomy'   => 'lp_series',
		'hide_empty' => false,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	)
);
$lp_all_series = is_array( $lp_all_series ) ? $lp_all_series : array();

$lp_total_tutorials = (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );
$lp_total_series    = count( $lp_all_series );

/**
 * Published tutorials in a series term, menu_order then date.
 *
 * @param int $term_id Series term ID.
 * @return WP_Post[]
 */
$lp_lessons_in = static function ( int $term_id ): array {
	$q = new WP_Query(
		array(
			'post_type'              => 'lp_tutorial',
			'post_status'            => 'publish',
			'posts_per_page'         => 24,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'ASC',
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'lp_series',
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		)
	);
	return $q->posts;
};

/**
 * Project tutorials into board-row args for a lesson board.
 *
 * @param WP_Post[] $posts Lessons.
 * @return array<int,array{part:string,args:array}>
 */
$lp_board_rows = static function ( array $posts ): array {
	$rows = array();
	$i    = 0;
	foreach ( $posts as $post ) {
		++$i;
		$duration = function_exists( 'get_field' ) ? (string) get_field( 'duration', $post->ID ) : '';
		$coaches  = function_exists( 'get_field' ) ? get_field( 'coaches', $post->ID ) : array();
		$coach_ids = array_filter( array_map( 'intval', (array) $coaches ) );
		$subtitle  = $coach_ids ? get_the_title( $coach_ids[0] ) : '';

		$is_new = ( time() - get_post_time( 'U', true, $post ) ) <= ( 30 * DAY_IN_SECONDS );

		$rows[] = array(
			'part' => 'components/board-row',
			'args' => array(
				'time'             => sprintf( '%02d', $i ),
				'title'            => get_the_title( $post ),
				'subtitle'         => $subtitle,
				'location'         => 'Lesson',
				'level'            => $duration,
				'location_icon_id' => 'icon-tag',
				'level_icon_id'    => 'icon-clock',
				'spaces'           => $is_new ? 'NEW' : 'WATCH',
				'tone'             => $is_new ? 'new' : 'available',
				'href'             => (string) get_permalink( $post ),
			),
		);
	}
	return $rows;
};

$lp_current_lessons = $lp_lessons_in( $lp_term_id );
$lp_lesson_count    = count( $lp_current_lessons );
if ( ! $lp_ep_count ) {
	$lp_ep_count = $lp_lesson_count;
}

$lp_facts = array_values(
	array_filter(
		array(
			$lp_ep_count ? sprintf( '%02d LESSONS', $lp_ep_count ) : '',
			$lp_duration ? strtoupper( $lp_duration ) . ( false === stripos( $lp_duration, 'TOTAL' ) ? ' TOTAL' : '' ) : '',
			$lp_coach ? 'COACH · ' . strtoupper( $lp_coach ) : '',
		)
	)
);

// Boards: current series, then siblings with lessons (max 3 boards total).
$lp_board_specs = array(
	array(
		'term'    => $lp_term,
		'fields'  => $lp_fields,
		'lessons' => $lp_current_lessons,
		'line'    => 1,
	),
);
$lp_line = 1;
foreach ( $lp_all_series as $lp_sib ) {
	if ( (int) $lp_sib->term_id === $lp_term_id ) {
		continue;
	}
	$lp_sib_lessons = $lp_lessons_in( (int) $lp_sib->term_id );
	if ( ! $lp_sib_lessons ) {
		continue;
	}
	++$lp_line;
	$lp_sib_fields = function_exists( 'get_fields' ) ? get_fields( 'term_' . $lp_sib->term_id ) : array();
	$lp_board_specs[] = array(
		'term'    => $lp_sib,
		'fields'  => is_array( $lp_sib_fields ) ? $lp_sib_fields : array(),
		'lessons' => $lp_sib_lessons,
		'line'    => $lp_line,
	);
	if ( count( $lp_board_specs ) >= 3 ) {
		break;
	}
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
					'href'  => $lp_archive_url,
				),
				array(
					'label' => strtoupper( $lp_term->name ),
				),
			),
			'action' => array(
				'label' => 'ALL SERIES ↗',
				'href'  => $lp_archive_url,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => $lp_term->name . ( str_ends_with( $lp_term->name, '.' ) ? '' : '.' ),
			'note'  => $lp_logline
				? $lp_logline
				: 'Work the line in order — or jump to wherever you already are.',
		)
	);

	lp_part(
		'components/view-rail',
		array(
			'context'   => 'tutorials',
			'aria_label' => 'Tutorials view',
			'tabs'      => array(
				array(
					'label'   => 'By series',
					'meta'    => sprintf( '%d series', $lp_total_series ),
					'icon_id' => 'icon-square-3-stack-3d',
					'active'  => true,
					'href'    => $lp_archive_url,
				),
				array(
					'label'   => 'By tutorial',
					'meta'    => sprintf( '%d videos', $lp_total_tutorials ),
					'icon_id' => 'icon-play-circle',
					'active'  => false,
					'href'    => $lp_archive_url,
				),
			),
		)
	);
	?>

	<div class="w-full bg-neutral" data-section="lessons">
		<div class="px-6 lg:px-16 py-scale-2xl">
			<div class="flex flex-col lg:flex-row gap-9 items-start">
				<aside class="w-full lg:w-[300px] shrink-0 flex flex-col gap-6 bg-secondary p-4" data-component="series-sidebar">
					<div class="flex flex-col gap-1">
						<span class="font-label text-[10px] font-semibold uppercase tracking-[0.8px] text-neutral-content/50">SERIES</span>
						<h2 class="font-heading text-[20px] font-medium tracking-[-0.3px] text-neutral-content m-0">All eight lines.</h2>
						<p class="font-label text-[10px] uppercase tracking-[0.6px] text-neutral-content/50 m-0"><?php echo esc_html( sprintf( '%d episodes · pick a series', $lp_total_tutorials ) ); ?></p>
					</div>
					<nav class="flex flex-col gap-2" aria-label="<?php echo esc_attr__( 'Series list', 'londonparkour_v8' ); ?>">
						<?php
						$lp_idx = 0;
						foreach ( $lp_all_series as $lp_item ) :
							++$lp_idx;
							$lp_item_fields = function_exists( 'get_fields' ) ? get_fields( 'term_' . $lp_item->term_id ) : array();
							$lp_item_fields = is_array( $lp_item_fields ) ? $lp_item_fields : array();
							$lp_item_tag    = (string) ( $lp_item_fields['tag'] ?? '' );
							$lp_item_eps    = isset( $lp_item_fields['episode_count'] ) ? (int) $lp_item_fields['episode_count'] : 0;
							$lp_item_link   = get_term_link( $lp_item );
							$lp_item_href   = is_wp_error( $lp_item_link ) ? '#' : (string) $lp_item_link;
							$lp_active      = (int) $lp_item->term_id === $lp_term_id;
							$lp_item_cls    = $lp_active
								? 'flex gap-3 p-2 border border-primary bg-neutral no-underline text-left transition-colors'
								: 'flex gap-3 p-2 border border-neutral-content/10 bg-secondary hover:border-neutral-content/25 no-underline text-left transition-colors';
							?>
							<a
								href="<?php echo esc_url( $lp_item_href ); ?>"
								class="<?php echo esc_attr( $lp_item_cls ); ?>"
								data-component="series-sidebar-item"
								<?php echo $lp_active ? ' aria-current="page"' : ''; ?>
							>
								<span class="w-[72px] h-[56px] shrink-0 bg-neutral-content/10" aria-hidden="true"></span>
								<span class="flex flex-col gap-1 min-w-0 justify-center">
									<span class="flex items-center gap-2">
										<span class="font-label text-[10px] font-semibold uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( sprintf( 'S%02d', $lp_idx ) ); ?></span>
										<?php if ( '' !== $lp_item_tag ) : ?>
											<span class="font-label text-[9px] font-semibold uppercase tracking-[0.6px] text-primary"><?php echo esc_html( $lp_item_tag ); ?></span>
										<?php endif; ?>
									</span>
									<span class="font-heading text-[13px] font-medium tracking-[-0.2px] text-neutral-content truncate"><?php echo esc_html( $lp_item->name ); ?></span>
									<?php if ( $lp_item_eps ) : ?>
										<span class="font-label text-[10px] uppercase tracking-[0.6px] text-neutral-content/50"><?php echo esc_html( sprintf( '%d EPISODES', $lp_item_eps ) ); ?></span>
									<?php endif; ?>
								</span>
							</a>
						<?php endforeach; ?>
					</nav>
				</aside>

				<div class="flex-1 min-w-0 flex flex-col gap-[64px]">
					<div class="flex flex-col gap-5" data-component="series-header">
						<div class="flex flex-col lg:flex-row gap-6 lg:items-start justify-between">
							<div class="flex flex-col gap-3 min-w-0 flex-1">
								<div class="flex items-center gap-3">
									<?php
									lp_part(
										'elements/badge',
										array(
											'variant' => 'paper',
											'label'   => sprintf( 'LINE %02d', 1 ),
										)
									);
									?>
									<span class="font-label text-[10px] font-semibold uppercase tracking-[0.8px] text-primary">ACTIVE SERIES</span>
								</div>
								<h2 class="font-heading text-[32px] font-medium tracking-[-0.6px] text-neutral-content m-0"><?php echo esc_html( $lp_term->name ); ?></h2>
								<?php if ( '' !== $lp_logline ) : ?>
									<p class="font-body text-[14px] leading-[1.55] text-neutral-content/65 m-0 max-w-[520px]"><?php echo esc_html( $lp_logline ); ?></p>
								<?php endif; ?>
								<div class="flex flex-wrap items-center gap-4 pt-1">
									<?php
									lp_part(
										'elements/button',
										array(
											'label'   => $lp_cta_label ? $lp_cta_label : 'PLAY SERIES',
											'variant' => 'primary',
											'href'    => $lp_current_lessons ? (string) get_permalink( $lp_current_lessons[0] ) : $lp_term_url,
										)
									);
									?>
								</div>
							</div>
							<div class="w-full lg:w-[280px] h-[160px] shrink-0 bg-secondary border border-neutral-content/10 flex items-center justify-center" aria-hidden="true">
								<span class="font-label text-[24px] text-primary">▶</span>
							</div>
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

					<div class="flex flex-col gap-[64px]">
						<?php foreach ( $lp_board_specs as $lp_spec ) : ?>
							<?php
							$lp_b_term    = $lp_spec['term'];
							$lp_b_fields  = $lp_spec['fields'];
							$lp_b_lessons = $lp_spec['lessons'];
							$lp_b_line    = (int) $lp_spec['line'];
							$lp_b_eps     = isset( $lp_b_fields['episode_count'] ) ? (int) $lp_b_fields['episode_count'] : count( $lp_b_lessons );
							$lp_b_dur     = (string) ( $lp_b_fields['duration'] ?? '' );
							$lp_b_meta    = trim(
								sprintf(
									'%02d LESSONS%s',
									$lp_b_eps ? $lp_b_eps : count( $lp_b_lessons ),
									$lp_b_dur ? ' · ' . strtoupper( $lp_b_dur ) : ''
								)
							);
							$lp_b_link  = get_term_link( $lp_b_term );
							$lp_b_href  = is_wp_error( $lp_b_link ) ? '#' : (string) $lp_b_link;
							$lp_b_rows  = $lp_board_rows( $lp_b_lessons );
							?>
							<div class="flex flex-col gap-[24px]">
								<div class="flex items-center justify-between gap-3">
									<div class="flex items-center gap-[16px]">
										<?php
										lp_part(
											'elements/badge',
											array(
												'variant' => 'paper',
												'label'   => sprintf( 'LINE %02d', $lp_b_line ),
											)
										);
										?>
										<h3 class="font-heading text-[26px] font-medium tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_b_term->name ); ?></h3>
									</div>
									<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50 whitespace-nowrap"><?php echo esc_html( $lp_b_meta ); ?></span>
								</div>
								<?php lp_part( 'elements/rule', array( 'tone' => 'board' ) ); ?>
								<?php
								lp_part(
									'components/board-shell',
									array(
										'columns'    => array(),
										'rows'       => $lp_b_rows,
										'foot_left'  => sprintf( 'VIEW ALL %d LESSONS →', count( $lp_b_lessons ) ),
										'foot_href'  => $lp_b_href,
										'foot_right' => 'PROGRESSION · WATCH IN ORDER',
									)
								);
								?>
							</div>
						<?php endforeach; ?>
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
				'href'    => $lp_archive_url,
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
