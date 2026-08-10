<?php
/**
 * Template Name: Classes — Agenda
 *
 * ClassesAgenda, ported from src/stories/Pages/ClassesAgenda/ClassesAgenda.js
 * (`GdUt4` + cards board `O6Fhqs`). A page template rather than an archive: the
 * board's unit is a SESSION, and sessions come from clasbpro — so there is no
 * query for WordPress to route.
 *
 * Section order: header cluster (media masthead, no filter) → week controls →
 * cards board → week pagination → onward. Nav/footer are get_header()/get_footer().
 *
 * ── Week navigation ────────────────────────────────────────────────────────
 *
 * `?week=±n`, the repo owner's choice over a JS-only control: the URL reflects
 * what is on screen, it is shareable, and it works with JS off.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only navigation, see app/setup/queries.php.
$lp_offset = isset( $_GET['week'] ) ? (int) $_GET['week'] : 0;

$lp_week = lp_agenda_week( $lp_offset );
$lp_prev = lp_agenda_week( $lp_offset - 1 );
$lp_next = lp_agenda_week( $lp_offset + 1 );
$lp_url  = static fn( int $lp_n ): string => 0 === $lp_n ? (string) get_permalink() : add_query_arg( 'week', $lp_n, (string) get_permalink() );

/*
 * Running now: start = date + time, end = start + the class's duration. A row
 * with no parseable duration cannot be said to be running, so it is not
 * counted rather than assumed.
 */
$lp_now     = current_datetime();
$lp_running = 0;
foreach ( $lp_week['days'] as $lp_day_group ) {
	foreach ( $lp_day_group['sessions'] as $lp_session ) {
		$lp_minutes = (int) filter_var( lp_class_duration( $lp_session['class_id'] ), FILTER_SANITIZE_NUMBER_INT );

		if ( ! $lp_minutes || '' === $lp_session['time'] ) {
			continue;
		}

		$lp_start = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i',
			$lp_day_group['iso'] . ' ' . $lp_session['time'],
			$lp_now->getTimezone()
		);

		if ( ! $lp_start ) {
			continue;
		}

		if ( $lp_now >= $lp_start && $lp_now <= $lp_start->modify( sprintf( '+%d minutes', $lp_minutes ) ) ) {
			++$lp_running;
		}
	}
}

$lp_sites      = (int) ( wp_count_posts( 'lp_location' )->publish ?? 0 );
$lp_mast_media = lp_demo_media_id( 'DSC01072.jpeg' );
$lp_mast_url   = $lp_mast_media ? '' : (string) get_theme_file_uri( 'bin/demo-media/DSC01072.jpeg' );

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/classes-header-cluster',
		array(
			'crumbs'      => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'CLASSES',
					'href'  => (string) get_post_type_archive_link( lp_class_post_type() ),
				),
				array( 'label' => 'AGENDA' ),
			),
			'action'      => array(
				'label' => 'MAP VIEW ↗',
				'href'  => lp_classes_page_url( 'classes-map' ),
			),
			'masthead'    => array(
				'title'     => 'Departures, day by day.',
				'note'      => 'Every session on the board for the week ahead. Coach-led, capped at twelve, £15 to drop in. Spaces update live — take the slot while it is there.',
				'media_id'  => $lp_mast_media,
				'media_url' => $lp_mast_url,
				'media_alt' => '',
			),
			'active'      => 'agenda',
			'show_filter' => false,
		)
	);
	?>

	<div class="w-full bg-base-100 border-b border-base-300" data-component="agenda-week-controls">
		<div class="flex items-center justify-between gap-6 flex-wrap px-6 lg:px-16 py-scale-s">
			<span>
				<?php
				lp_part(
					'elements/button',
					array(
						'variant'    => 'icon',
						'icon_id'    => 'icon-chevron-left',
						'aria_label' => 'Previous week',
						'href'       => $lp_url( $lp_offset - 1 ),
					)
				);
				?>
			</span>
			<div class="flex flex-col items-center gap-[10px]">
				<h2 class="font-heading text-[26px] font-semibold tracking-[-0.3px] text-base-content text-center"><?php echo esc_html( lp_agenda_week_label( $lp_week ) ); ?></h2>
				<div class="flex items-center gap-[16px]">
					<span>
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => sprintf( _n( '%d CLASS THIS WEEK', '%d CLASSES THIS WEEK', $lp_week['count'], 'londonparkour_v8' ), $lp_week['count'] ),
								'surface' => 'page',
								'tone'    => 'muted',
							)
						);
						?>
					</span>
					<?php if ( $lp_running ) : ?>
						<span>
							<?php
							lp_part(
								'elements/status',
								array(
									'variant' => 'live',
									'surface' => 'page',
									'label'   => sprintf( _n( '%d RUNNING NOW', '%d RUNNING NOW', $lp_running, 'londonparkour_v8' ), $lp_running ),
								)
							);
							?>
						</span>
					<?php endif; ?>
				</div>
			</div>
			<span>
				<?php
				lp_part(
					'elements/button',
					array(
						'variant'    => 'icon',
						'icon_id'    => 'icon-chevron-right',
						'aria_label' => 'Next week',
						'href'       => $lp_url( $lp_offset + 1 ),
					)
				);
				?>
			</span>
		</div>
	</div>

	<section class="w-full bg-neutral" data-component="agenda-cards-board">
		<div class="px-6 lg:px-16 pt-scale-2xl pb-scale-2xl flex flex-col gap-3.5">
			<div class="flex items-center justify-between gap-4 flex-wrap pb-[18px] border-b border-neutral-content/20">
				<h2 class="font-label text-[12px] font-semibold tracking-[1px] uppercase text-primary m-0"><?php echo esc_html( sprintf( 'AGENDA · WEEK %d', $lp_week['week'] ) ); ?></h2>
				<span class="inline-flex items-center gap-[9px]">
					<span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0" aria-hidden="true"></span>
					<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-neutral-content/80"><?php echo esc_html( sprintf( 'UPDATED %s · %s', $lp_now->format( 'H:i' ), strtoupper( $lp_now->format( 'D j M' ) ) ) ); ?></span>
				</span>
			</div>

			<div class="flex flex-col gap-0">
				<?php
				$lp_day_groups = array_values(
					array_filter(
						$lp_week['days'],
						static fn( array $lp_g ): bool => ! empty( $lp_g['sessions'] )
					)
				);
				$lp_day_total = count( $lp_day_groups );

				/*
				 * Featured = the globally next upcoming session (same source as
				 * the hero board), not merely the first future row in this
				 * week's list. A future/past week offset therefore shows no
				 * yellow highlight unless that next session falls in-window.
				 *
				 * Named $lp_upcoming_* so we do not clobber $lp_next / $lp_prev
				 * (the adjacent week payloads used by week pagination).
				 */
				$lp_upcoming     = lp_class_next_session();
				$lp_upcoming_key = '';
				$lp_upcoming_day = '';
				if ( $lp_upcoming ) {
					$lp_upcoming_key = sprintf(
						'%s|%s|%d',
						(string) ( $lp_upcoming['date'] ?? '' ),
						(string) ( $lp_upcoming['time'] ?? '' ),
						(int) ( $lp_upcoming['id'] ?? 0 )
					);
					foreach ( $lp_day_groups as $lp_g ) {
						foreach ( $lp_g['sessions'] as $lp_s ) {
							$lp_key = sprintf(
								'%s|%s|%d',
								(string) ( $lp_g['iso'] ?? '' ),
								(string) ( $lp_s['time'] ?? '' ),
								(int) ( $lp_s['class_id'] ?? 0 )
							);
							if ( $lp_key === $lp_upcoming_key ) {
								$lp_upcoming_day = $lp_g['day'];
								break 2;
							}
						}
					}
				}

				foreach ( $lp_day_groups as $lp_day_index => $lp_day_group ) :
					$lp_total = count( $lp_day_group['sessions'] );
					?>
					<div class="w-full flex items-center justify-between gap-3 pt-6 pb-4" data-component="agenda-day-header">
						<div class="flex items-end gap-[14px] min-w-0">
							<span class="font-heading text-[42px] font-semibold tracking-[-1px] uppercase text-neutral-content leading-none"><?php echo esc_html( $lp_day_group['day'] ); ?></span>
							<span class="font-label text-[11px] font-normal tracking-[0.6px] uppercase text-neutral-content/50 pb-[6px]"><?php echo esc_html( $lp_day_group['date'] ); ?></span>
						</div>
						<div class="flex items-center gap-3 shrink-0">
							<span class="font-label text-[11px] font-semibold tracking-[1px] uppercase text-neutral-content/50"><?php echo esc_html( sprintf( _n( '%d SESSION', '%d SESSIONS', $lp_total, 'londonparkour_v8' ), $lp_total ) ); ?></span>
							<?php if ( $lp_upcoming_day === $lp_day_group['day'] ) : ?>
								<span class="inline-flex items-center gap-1.5 bg-primary px-2.5 py-1">
									<span class="w-[5px] h-[5px] rounded-full bg-primary-content" aria-hidden="true"></span>
									<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-primary-content">NEXT UP</span>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<div class="flex flex-col gap-0">
						<?php
						foreach ( $lp_day_group['sessions'] as $lp_session ) :
							$lp_is_past = ! empty( $lp_session['past'] );
							$lp_key     = sprintf(
								'%s|%s|%d',
								(string) ( $lp_day_group['iso'] ?? '' ),
								(string) ( $lp_session['time'] ?? '' ),
								(int) ( $lp_session['class_id'] ?? 0 )
							);
							$lp_is_next = ( '' !== $lp_upcoming_key && $lp_key === $lp_upcoming_key );
							$lp_size    = $lp_is_next ? 'featured' : 'default';
							$lp_kicker  = (string) ( $lp_session['kicker'] ?? '' );
							if ( $lp_is_next && '' !== $lp_kicker && ! str_starts_with( $lp_kicker, 'NEXT UP' ) ) {
								$lp_kicker = 'NEXT UP · ' . $lp_kicker;
							}

							$lp_facts = array(
								array(
									'key'   => 'WHEN',
									'value' => (string) ( $lp_session['when'] ?? $lp_session['time'] ),
								),
								array(
									'key'   => 'WHERE',
									'value' => (string) ( $lp_session['location'] ?? '' ),
								),
								array(
									'key'   => 'LEVEL',
									'value' => (string) ( $lp_session['level'] ?? '' ),
								),
								array(
									'key'   => 'COACH',
									'value' => (string) ( $lp_session['coaches'] ?? '' ),
								),
							);

							lp_part(
								'components/agenda-card',
								array(
									'day'           => $lp_day_group['day'],
									'time'          => (string) ( $lp_session['time'] ?? '' ),
									'media_id'      => (int) ( $lp_session['thumb'] ?? 0 ),
									'media_alt'     => '',
									'glyph_icon_id' => 'glyph-flowing',
									'kicker'        => $lp_kicker,
									'title'         => (string) ( $lp_session['title'] ?? '' ),
									'sub'           => (string) ( $lp_session['subtitle'] ?? '' ),
									'facts'         => $lp_facts,
									'fare'          => (string) ( $lp_session['price'] ?? '' ),
									'spaces'        => (string) ( $lp_session['spaces'] ?? '' ),
									'href'          => $lp_is_past ? '' : (string) ( $lp_session['href'] ?? '' ),
									'size'          => $lp_size,
									'past'          => $lp_is_past,
								)
							);
						endforeach;
						?>
					</div>

					<?php if ( $lp_day_index < $lp_day_total - 1 ) : ?>
						<div class="w-full h-px bg-neutral-content/10 my-0" aria-hidden="true"></div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<div class="flex items-baseline justify-between gap-4 flex-wrap pt-[19px] border-t border-neutral-content/15">
				<?php
				lp_part(
					'elements/text-link',
					array(
						'label'   => 'VIEW THE FULL TIMETABLE →',
						'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
						'variant' => 'board',
					)
				);
				?>
				<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50"><?php echo esc_html( sprintf( '%d SITES · %d CLASSES A WEEK', $lp_sites, $lp_week['count'] ) ); ?></span>
			</div>
		</div>
	</section>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'surface' => 'page',
			'prev'    => array(
				'keyword' => '← PREVIOUS WEEK',
				'label'   => lp_agenda_week_label( $lp_prev ),
				'href'    => $lp_url( $lp_offset - 1 ),
			),
			'next'    => array(
				'keyword' => 'NEXT WEEK →',
				'label'   => lp_agenda_week_label( $lp_next ),
				'href'    => $lp_url( $lp_offset + 1 ),
			),
		)
	);

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← MAP VIEW',
				'label'   => 'Sites across the network',
				'href'    => lp_classes_page_url( 'classes-map' ),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Take the next open slot',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
