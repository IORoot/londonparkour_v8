<?php
/**
 * Template Name: Tutorials — Series
 *
 * tutorials-series.php — TutorialsSeriesOverview.
 *
 * Ported from src/stories/Pages/TutorialsSeriesOverview/TutorialsSeriesOverview.js
 * (`X0KgR`). Read that file's docblock before touching this one.
 *
 * Section order: breadcrumb → masthead → view rail → overview board (`vnaII`)
 * → Train In Person → onward. Nav/footer are get_header()/get_footer(),
 * outside the one <main>.
 *
 * `/tutorials/` is the tutorial CPT archive, so this page is reached at
 * `/tutorials/series/` via a top rewrite onto pagename `tutorials-series`
 * (see lp_tutorials_series_rewrite()).
 *
 * Overview board is `vnaII`: dark `bg-neutral` band, `bg-secondary` cards
 * with poster + body. First row is featured (larger 16:9 poster on the left,
 * primary border and CTA). Episode counts and minutes are computed. Empty
 * logline / level / coach / poster / tag slots are omitted rather than invented.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_archive_url  = (string) get_post_type_archive_link( 'lp_tutorial' );
$lp_series_terms = lp_series_terms_nonempty();
$lp_series_count = count( $lp_series_terms );
$lp_first_href   = $lp_archive_url;

if ( $lp_series_terms ) {
	$lp_first_link = get_term_link( $lp_series_terms[0] );
	if ( ! is_wp_error( $lp_first_link ) ) {
		$lp_first_href = (string) $lp_first_link;
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
				),
			),
			'action' => array(
				'label' => 'BY TUTORIAL ↗',
				'href'  => $lp_archive_url,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'The series.',
			'note'  => 'Eight taught progressions. Open a line for the full lesson board — image, summary, and a clear way into the videos.',
		)
	);

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'tutorials',
			'aria_label' => 'Tutorials view',
			'tabs'       => lp_tutorials_view_tabs( 'series' ),
		)
	);
	?>

	<section class="w-full bg-neutral" data-component="series-overview-board">
		<div class="px-6 lg:px-16 pt-16 pb-20 flex flex-col gap-9">
			<div>
				<div class="flex items-end justify-between gap-4 pb-[14px]">
					<div class="flex flex-col gap-2">
						<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-primary">NOW STREAMING</span>
						<h2 class="font-heading text-[32px] font-semibold tracking-[-0.8px] text-neutral-content m-0 [text-box:normal]">Series worth watching.</h2>
					</div>
					<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( sprintf( '%02d SERIES', $lp_series_count ) ); ?></span>
				</div>
				<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
			</div>
			<div class="flex flex-col gap-6">
				<?php
				$lp_idx = 0;
				foreach ( $lp_series_terms as $lp_term ) :
					++$lp_idx;
					$lp_featured = 1 === $lp_idx;
					$lp_fields   = function_exists( 'get_fields' ) ? get_fields( 'term_' . $lp_term->term_id ) : array();
					$lp_fields   = is_array( $lp_fields ) ? $lp_fields : array();
					list( $lp_series_no, $lp_level ) = lp_series_label_parts( (string) ( $lp_fields['series_label'] ?? '' ), $lp_idx );

					$lp_logline = (string) ( $lp_fields['logline'] ?? '' );
					$lp_tags    = lp_series_tag_list( (string) ( $lp_fields['tags'] ?? '' ) );
					$lp_coach   = (string) ( $lp_fields['coach_label'] ?? '' );
					$lp_tag     = (string) ( $lp_fields['tag'] ?? '' );
					$lp_cta     = (string) ( $lp_fields['cta_label'] ?? '' );
					if ( '' === $lp_cta ) {
						$lp_cta = 'WATCH SERIES';
					}

					$lp_lessons    = lp_tutorials_in_series( (int) $lp_term->term_id );
					$lp_poster_id  = lp_series_poster_id( (int) $lp_term->term_id, $lp_fields, $lp_lessons[0] ?? null );
					$lp_ep_count = count( $lp_lessons );
					$lp_mins     = lp_format_runtime_minutes( lp_tutorials_total_seconds( $lp_lessons ) );
					$lp_meta     = array_values(
						array_filter(
							array(
								sprintf( '%d EPISODES', $lp_ep_count ),
								$lp_mins,
								$lp_coach,
							)
						)
					);

					$lp_link = get_term_link( $lp_term );
					$lp_href = is_wp_error( $lp_link ) ? '#' : (string) $lp_link;

					$lp_row_cls     = $lp_featured
						? 'group flex flex-col lg:flex-row bg-secondary no-underline overflow-hidden text-left border border-primary hover:bg-primary'
						: 'group flex flex-col lg:flex-row bg-secondary no-underline overflow-hidden text-left border border-neutral-content/10 hover:bg-primary';
					$lp_poster_cls  = $lp_featured
						? 'relative w-full lg:w-[640px] shrink-0 aspect-video bg-neutral overflow-hidden'
						: 'relative w-full lg:w-[460px] shrink-0 min-h-[180px] lg:h-[272px] bg-neutral overflow-hidden';
					$lp_title_cls   = $lp_featured
						? 'font-heading text-[40px] font-semibold tracking-[-1.1px] leading-[1.02] text-neutral-content m-0 [text-box:normal] group-hover:text-neutral'
						: 'font-heading text-[34px] font-semibold tracking-[-0.9px] leading-[1.02] text-neutral-content m-0 [text-box:normal] group-hover:text-neutral';
					$lp_logline_cls = $lp_featured
						? 'font-label text-[14px] font-normal leading-[1.45] tracking-[0.1px] text-neutral-content/70 m-0 group-hover:text-neutral/80'
						: 'font-label text-[13px] font-normal leading-[1.45] tracking-[0.1px] text-neutral-content/70 m-0 group-hover:text-neutral/80';
					$lp_cta_cls     = $lp_featured
						? 'inline-flex items-center gap-2 py-[11px] px-4 bg-primary text-primary-content font-label text-[10px] font-bold uppercase tracking-[1px] group-hover:bg-neutral group-hover:text-primary'
						: 'inline-flex items-center gap-2 py-[11px] px-4 bg-neutral-content text-neutral font-label text-[10px] font-bold uppercase tracking-[1px] group-hover:bg-neutral group-hover:text-primary';
					?>
					<a
						href="<?php echo esc_url( $lp_href ); ?>"
						class="<?php echo esc_attr( $lp_row_cls ); ?>"
						data-component="series-overview-row"
						<?php echo $lp_featured ? ' data-featured="true"' : ''; ?>
					>
						<div class="<?php echo esc_attr( $lp_poster_cls ); ?>">
							<?php
							if ( $lp_poster_id ) {
								lp_part(
									'components/media-photo',
									array(
										'image_id' => $lp_poster_id,
										'alt'      => $lp_term->name,
										'layout'   => 'fill',
										'size'     => 'lp_wide',
										'sizes'    => $lp_featured ? '(min-width: 1024px) 640px, 100vw' : '(min-width: 1024px) 460px, 100vw',
									)
								);
							}
							?>
						</div>
						<div class="flex-1 min-w-0 flex flex-col gap-[14px] py-7 px-8 lg:pl-9 justify-between">
							<div class="flex flex-col gap-[14px]">
								<div class="flex items-center gap-2.5 flex-wrap">
									<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( $lp_series_no ); ?></span>
									<?php if ( '' !== $lp_tag ) : ?>
										<span class="inline-flex items-center py-[5px] px-[9px] bg-primary text-primary-content font-label text-[9px] font-bold uppercase tracking-[1px] group-hover:bg-neutral group-hover:text-primary"><?php echo esc_html( $lp_tag ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $lp_level ) : ?>
										<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( $lp_level ); ?></span>
									<?php endif; ?>
								</div>
								<h2 class="<?php echo esc_attr( $lp_title_cls ); ?>"><?php echo esc_html( $lp_term->name ); ?></h2>
								<?php if ( '' !== $lp_logline ) : ?>
									<p class="<?php echo esc_attr( $lp_logline_cls ); ?>"><?php echo esc_html( $lp_logline ); ?></p>
								<?php endif; ?>
								<?php if ( $lp_tags ) : ?>
									<div class="flex flex-wrap gap-2">
										<?php foreach ( $lp_tags as $lp_genre ) : ?>
											<span class="inline-flex items-center py-[5px] px-2.5 border border-neutral-content/20 font-label text-[9px] font-bold tracking-[0.8px] uppercase text-neutral-content/80 group-hover:border-neutral/40 group-hover:text-neutral"><?php echo esc_html( $lp_genre ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="flex items-center justify-between gap-4 flex-wrap pt-[6px]">
								<span class="flex items-center gap-3 font-label text-[10px] font-semibold uppercase tracking-[0.8px] text-neutral-content/50 group-hover:text-neutral/70">
									<?php foreach ( $lp_meta as $lp_mi => $lp_bit ) : ?>
										<?php if ( $lp_mi ) : ?>
											<span class="w-[3px] h-[3px] bg-neutral-content/25 group-hover:bg-neutral/40" aria-hidden="true"></span>
										<?php endif; ?>
										<span><?php echo esc_html( $lp_bit ); ?></span>
									<?php endforeach; ?>
								</span>
								<span class="<?php echo esc_attr( $lp_cta_cls ); ?>"><?php echo esc_html( $lp_cta ); ?></span>
							</div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	lp_render_block( 'train_in_person', array() );

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← ALL TUTORIALS',
				'label'   => 'The full tutorial board',
				'href'    => $lp_archive_url,
			),
			'next' => array(
				'keyword' => 'OPEN A SERIES →',
				'label'   => $lp_series_terms ? $lp_series_terms[0]->name . ' lessons' : 'The series',
				'href'    => $lp_first_href,
			),
		)
	);
	?>
</main>

<?php
get_footer();
