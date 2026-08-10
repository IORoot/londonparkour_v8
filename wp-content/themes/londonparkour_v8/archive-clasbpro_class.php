<?php
/**
 * archive-clasbpro_class.php — ClassesListings.
 *
 * Ported from src/stories/Pages/ClassesListings/ClassesListings.js (`KwyHc`).
 * Read that file's docblock before touching this one — it records which copy
 * was transcribed from the design file at leaf depth and which earlier pass
 * had wrongly hedged it as "representative".
 *
 * Section order: header cluster → featured class → all class types → onward.
 * Nav/footer are get_header()/get_footer(), outside the one <main>. The source
 * places the whole cluster BEFORE its <main> and its own docblock calls that a
 * defect it could not fix from the directory it owned — the masthead's <h1>
 * belongs inside the main landmark. parts/components/classes-header-cluster.php
 * does not render nav, so that is fixed here rather than carried over.
 *
 * The two bespoke sections stay inline. Featured Class is explicitly the one
 * bespoke composition in this page family (the source re-confirms MediaCard.js's
 * own ruling that the lead card is NOT a MediaCard), and All Class Types is a
 * MetaRow plus a MediaCard grid. Neither is promoted — PORT-BRIEF rule 3a.
 *
 * ── Which class is featured ────────────────────────────────────────────────
 *
 * `acf_is_featured` on clasbpro_class (via lp_class_is_featured), mirroring
 * `is_lead` on Coach and `is_flagship` on Location for the reason PORT-FINDINGS
 * §13 records: the page shows one class above the grid and the rest below, so
 * the grid query must know which is already on show or it renders twice.
 *
 * It heroes the UNFILTERED page only. The design draws no filtered state, so
 * that is a judgement call; the alternative is worse, because a filtered page
 * would lead with a class that does not match what was asked for.
 *
 * Listings are deduped by title via lp_class_dedupe_by_title() so one product
 * name does not appear once per weekday clasbpro row.
 *
 * BOOK opens the shared booking drawer — no /book/… hrefs.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/** Flatten one class into components/media-card.php's args. */
$lp_card = static function ( WP_Post $lp_post ): array {
	$lp_id       = (int) $lp_post->ID;
	$lp_levels   = get_the_terms( $lp_post, 'lp_level' );
	$lp_location = lp_class_location_id( $lp_id );
	$lp_price    = lp_class_price_display( $lp_id );
	$lp_dur      = lp_class_duration( $lp_id );

	return array(
		'aspect'   => 'wide',
		'image_id' => lp_class_image_id( $lp_id ) ?: 0,
		'kicker'   => is_array( $lp_levels ) && $lp_levels ? strtoupper( $lp_levels[0]->name ) : '',
		'meta'     => $lp_location ? strtoupper( get_the_title( $lp_location ) ) : '',
		'title'    => get_the_title( $lp_post ),
		'note'     => get_the_excerpt( $lp_post ),
		'foot'     => trim( $lp_price . ( '' !== $lp_dur ? ' · ' . strtoupper( $lp_dur ) : '' ), ' ·' ),
		'href'     => (string) get_permalink( $lp_post ),
	);
};

$lp_classes = array();
while ( have_posts() ) {
	the_post();
	$lp_classes[] = get_post();
}

$lp_classes = lp_class_dedupe_by_title( $lp_classes );

/*
 * The featured class heroes the UNFILTERED page only. The design has no
 * filtered state, so this is a judgement call, made this way because the
 * alternative is worse: with a filter applied, the most prominent thing on the
 * page would be a class that does not match what was asked for. Filtered, every
 * match goes in the grid and nothing is held back.
 */
$lp_is_filtered = (bool) array_filter( lp_class_filter_values() );

$lp_featured_ids = $lp_is_filtered ? array() : get_posts(
	lp_class_active_meta_query(
		array(
			'post_type'      => lp_class_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'acf_is_featured',
					'value' => '1',
				),
			),
		)
	)
);
$lp_featured     = $lp_featured_ids ? get_post( $lp_featured_ids[0] ) : null;

// Prefer the featured flag helper if the meta query returned nothing useful.
if ( ! $lp_featured && ! $lp_is_filtered ) {
	foreach ( $lp_classes as $lp_candidate ) {
		if ( lp_class_is_featured( (int) $lp_candidate->ID ) ) {
			$lp_featured = $lp_candidate;
			break;
		}
	}
}

$lp_grid = array_values(
	array_filter(
		$lp_classes,
		static function ( WP_Post $lp_p ) use ( $lp_featured ): bool {
			if ( ! $lp_featured ) {
				return true;
			}
			// Drop the featured post and any same-title weekday sibling.
			return $lp_p->ID !== $lp_featured->ID
				&& $lp_p->post_title !== $lp_featured->post_title;
		}
	)
);

$lp_arch = (string) get_post_type_archive_link( lp_class_post_type() );

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/classes-header-cluster',
		array(
			'crumbs'        => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'CLASSES',
					'href'  => $lp_arch,
				),
				array( 'label' => 'LISTINGS' ),
			),
			'action'        => array(
				'label' => 'AGENDA VIEW ↗',
				'href'  => home_url( '/classes/agenda' ),
			),
			'masthead'      => array(
				'title' => 'Every class we run.',
				'note'  => 'Filter by type, site, age or day. Every session is coach-led, capped at twelve, and fifteen pounds to drop in — no contract, ever.',
			),
			'active'        => 'listings',
			'filter_action' => $lp_arch,
			'filter_values' => lp_class_filter_values(),
		)
	);
	?>

	<?php
	if ( $lp_featured ) :
		$lp_f_id       = (int) $lp_featured->ID;
		$lp_f_levels   = get_the_terms( $lp_featured, 'lp_level' );
		$lp_f_level    = is_array( $lp_f_levels ) && $lp_f_levels ? $lp_f_levels[0]->name : '';
		$lp_f_location = lp_class_location_id( $lp_f_id );
		$lp_f_price    = lp_class_price_display( $lp_f_id );
		$lp_f_p_label  = lp_class_price_label();
		$lp_f_sessions = lp_class_upcoming_sessions( $lp_f_id, 3 );
		$lp_f_first    = $lp_f_sessions[0] ?? array();
		$lp_f_thumb    = lp_class_image_id( $lp_f_id ) ?: 0;
		$lp_f_book     = lp_class_book_button_args(
			$lp_f_id,
			(string) ( $lp_f_first['date'] ?? '' ),
			'BOOK THIS SESSION',
			'band'
		);

		$lp_f_specs = array(
			array(
				'label' => 'PRICE',
				'value' => $lp_f_price,
			),
			// Counted, not the design's literal 6 — see the docblock.
			array(
				'label' => 'SITES',
				'value' => (string) ( $lp_f_location ? 1 : 0 ),
			),
			array(
				'label' => 'DURATION',
				'value' => lp_class_duration( $lp_f_id ),
			),
			array(
				'label' => 'RUNS',
				'value' => implode( ' + ', array_filter( wp_list_pluck( $lp_f_sessions, 'date_label' ) ) ),
			),
		);
		?>
		<div class="w-full bg-neutral" data-component="listings-featured-class">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col lg:flex-row gap-10 lg:gap-16 items-start">
				<div class="flex-1 min-w-0 flex flex-col gap-[19px]">
					<?php if ( $lp_f_thumb ) : ?>
						<?php
						/*
						 * `relative` is added to the source's figure classes. The source
						 * draws an empty box because the Storybook has no media library;
						 * with a real image, media-photo's `fill` layout needs a
						 * positioned ancestor. Nothing else about the box changes.
						 */
						lp_part(
							'components/media-photo',
							array(
								'element'       => 'figure',
								'image_id'      => $lp_f_thumb,
								'scrim'         => 'none',
								'size'          => 'lp_wide_lg',
								'sizes'         => '(min-width: 1024px) 892px, 100vw',
								'wrapper_class' => 'relative w-full aspect-[892/300] bg-secondary overflow-hidden m-0',
							)
						);
						?>
					<?php else : ?>
						<figure class="w-full aspect-[892/300] bg-secondary overflow-hidden m-0"></figure>
					<?php endif; ?>
					<div class="flex items-center justify-between gap-3">
						<span class="min-w-0">
							<?php
							lp_part(
								'elements/glyph-label',
								array(
									'label'   => strtoupper( $lp_f_level ),
									'surface' => 'board',
									'tone'    => 'ink',
								)
							);
							?>
						</span>
						<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50 shrink-0">MOST BOOKED CLASS</span>
					</div>
					<h3 class="font-heading text-[32px] font-semibold tracking-[-0.6px] leading-none text-neutral-content"><?php echo esc_html( get_the_title( $lp_featured ) ); ?></h3>
					<p class="font-body text-[13px] leading-[1.6] tracking-[0.1px] text-neutral-content/70 max-w-[560px]"><?php echo esc_html( get_the_excerpt( $lp_featured ) ); ?></p>
					<div class="flex flex-wrap gap-[36px] pt-2">
						<?php foreach ( $lp_f_specs as $lp_spec ) : ?>
							<span>
								<?php
								lp_part(
									'components/fact-row',
									array(
										'label'   => $lp_spec['label'],
										'value'   => $lp_spec['value'],
										'surface' => 'board',
									)
								);
								?>
							</span>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="w-full lg:w-[380px] lg:shrink-0">
					<?php
					lp_part(
						'components/aside-panel',
						array(
							'title'       => 'TAKE A SLOT',
							'spots_left'  => (string) ( $lp_f_first['spaces'] ?? '' ),
							'rows'        => array(
								array(
									'label' => 'NEXT SESSION',
									'value' => trim( ( $lp_f_first['date_label'] ?? '' ) . ' · ' . ( $lp_f_first['time'] ?? '' ), ' ·' ),
								),
								array(
									'label' => 'SITE',
									'value' => $lp_f_location ? get_the_title( $lp_f_location ) : '',
								),
								array(
									'label' => 'LEVEL',
									'value' => $lp_f_level,
								),
								array(
									'label' => 'PRICE',
									'value' => trim( $lp_f_price . ' ' . strtolower( $lp_f_p_label ) ),
								),
							),
							'cta_label'   => $lp_f_book['label'],
							'command'     => $lp_f_book['command'] ?? '',
							'command_for' => $lp_f_book['command_for'] ?? '',
							'data_attrs'  => $lp_f_book['data_attrs'] ?? array(),
							'note'        => 'Free to cancel up to 12 hours before. All kit provided.',
							'surface'     => 'board',
						)
					);
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $lp_grid ) : ?>
		<div class="w-full bg-base-100" data-component="listings-all-class-types">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
				<?php
				lp_part(
					'components/meta-row',
					array(
						'left'  => 'ALL CLASS TYPES',
						'right' => sprintf( '%d MORE', count( $lp_grid ) ),
					)
				);
				?>
				<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
					<?php foreach ( $lp_grid as $lp_post ) : ?>
						<div><?php lp_part( 'components/media-card', $lp_card( $lp_post ) ); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php
		$lp_pagination = lp_pagination_args( null, 'CLASSES' );
		if ( $lp_pagination ) {
			lp_part( 'components/pagination', $lp_pagination );
		}
		?>
	<?php endif; ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← AGENDA VIEW',
				'label'   => 'This week, hour by hour',
				'href'    => home_url( '/classes/agenda' ),
			),
			'next' => array(
				'keyword' => 'MAP VIEW →',
				'label'   => 'Where we train across London',
				'href'    => home_url( '/classes/map' ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
