<?php
/**
 * Template Name: Classes — Agenda
 *
 * ClassesAgenda, ported from src/stories/Pages/ClassesAgenda/ClassesAgenda.js
 * (`z0TSK`). A page template rather than an archive: the board's unit is a
 * SESSION, and sessions are repeater rows on a class, not posts — so there is
 * no query for WordPress to route.
 *
 * Section order: header cluster → week controls → board → week pagination →
 * onward. Nav/footer are get_header()/get_footer(). The source reparents its
 * nav node after mount to get it outside <main>; the promoted cluster does not
 * render nav at all, so that workaround does not come across.
 *
 * ── The data-model change this page required ───────────────────────────────
 *
 * The `sessions` repeater had no date. It had `date_label` — the board label
 * ("TODAY", "THU") the departure boards print — which cannot be compared to a
 * week window. A dated agenda was therefore unbuildable, and mapping "TODAY"
 * onto a weekday would have been fabricating the timetable. A `date` sub-field
 * was added, with the repo owner's decision, and both fields now co-exist with
 * distinct jobs. PORT-FINDINGS §20.
 *
 * Demo sessions are seeded with `@MON`…`@SUN` tokens that
 * app/setup/seed.php resolves against the week of the seed run, so the board is
 * never empty and the committed content never names a week that has passed.
 *
 * ── Week navigation ────────────────────────────────────────────────────────
 *
 * `?week=±n`, the repo owner's choice over a JS-only control: the URL reflects
 * what is on screen, it is shareable, and it works with JS off. The source's
 * prev/next chevrons are Button `variant="icon"`, which renders an <a> as soon
 * as it is given a href — no element change was needed.
 *
 * ── Live and counted values ────────────────────────────────────────────────
 *
 * `3 RUNNING NOW` is computed, not assumed: a session is running when now sits
 * between its start (date + time) and its end (start + the duration in the
 * class's `subtitle`, whose ACF instructions document that format). Everything
 * else on the page — the week's session count, the per-day counts, the board
 * foot's site/day/session tally, the LIVE stamp's time — is counted or read
 * from the clock. The only fixed copy is the design's own: the masthead, the
 * board's terms line, and the onward labels.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only navigation, see app/setup/queries.php.
$lp_offset = isset( $_GET['week'] ) ? (int) $_GET['week'] : 0;

$lp_week  = lp_agenda_week( $lp_offset );
$lp_prev  = lp_agenda_week( $lp_offset - 1 );
$lp_next  = lp_agenda_week( $lp_offset + 1 );
$lp_url   = static fn( int $lp_n ): string => 0 === $lp_n ? (string) get_permalink() : add_query_arg( 'week', $lp_n, (string) get_permalink() );

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

// Board rows: a day band, then that day's sessions, in board-shell's row shape.
$lp_rows = array();
foreach ( $lp_week['days'] as $lp_day_group ) {
	$lp_total = count( $lp_day_group['sessions'] );

	$lp_rows[] = array(
		'part' => 'components/board-day-band',
		'args' => array(
			'day'   => $lp_day_group['day'],
			'date'  => $lp_day_group['date'],
			'count' => sprintf( _n( '%d SESSION', '%d SESSIONS', $lp_total, 'londonparkour_v8' ), $lp_total ),
		),
	);

	foreach ( $lp_day_group['sessions'] as $lp_session ) {
		$lp_href = (string) ( $lp_session['url'] ?? '' );
		unset( $lp_session['class_id'] );
		$lp_rows[] = array(
			'part' => 'components/board-row',
			'args' => array(
				'variant' => 'default',
				'href'    => $lp_href,
			) + $lp_session,
		);
	}
}

$lp_sites = (int) ( wp_count_posts( 'lp_location' )->publish ?? 0 );

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
					'href'  => (string) get_post_type_archive_link( lp_class_post_type() ),
				),
				array( 'label' => 'AGENDA' ),
			),
			'action'        => array(
				'label' => 'LISTINGS VIEW ↗',
				'href'  => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
			'masthead'      => array(
				'title' => 'Departures, day by day.',
				'note'  => 'Every session on the board for the week ahead. Coach-led, capped at twelve, £15 to drop in. Spaces update live — take the slot while it is there.',
			),
			'active'        => 'agenda',
			'filter_action' => (string) get_post_type_archive_link( lp_class_post_type() ),
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

	<?php
	lp_part(
		'components/board-shell',
		array(
			'board_title' => sprintf( 'AGENDA — WEEK %d', $lp_week['week'] ),
			'live_label'  => sprintf( 'LIVE · UPDATED %s', $lp_now->format( 'H:i' ) ),
			'columns'     => array( 'TIME', 'SESSION', 'SITE', 'LEVEL', 'SPACES' ),
			'rows'        => $lp_rows,
			'foot_left'   => '£15 DROP-IN · ALL KIT PROVIDED · FREE TO CANCEL 12H BEFORE',
			'foot_right'  => sprintf( '%d SITES · 7 DAYS · %d SESSIONS THIS WEEK', $lp_sites, $lp_week['count'] ),
		)
	);

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
