<?php
/**
 * single-lp_class.php — ClassDetail. Every native `lp_class` post.
 *
 * Ported from src/stories/Pages/ClassDetail/ClassDetail.js. Read that file's
 * docblock in full before touching this one.
 *
 * Unlike the other Classes pages this one does NOT use
 * `components/classes-header-cluster` — no View Rail, no Filter Grid. Nav is
 * get_header(); Breadcrumb and Masthead mount directly inside the one <main>,
 * per the source's own landmark note (the breadcrumb sits between Nav and
 * Masthead visually but is a landmark child of <main>).
 *
 * Section order: breadcrumb → masthead → fact rail → class body (media +
 * about + what-to-expect + booking aside) → meeting point (accent band) →
 * upcoming sessions (board) → onward. Nav/footer are get_header()/get_footer().
 *
 * ── Native data ─────────────────────────────────────────────────────────
 * Title is the post title. The class media is the featured image, through
 * components/media-photo.php. "ABOUT THIS CLASS" is get_the_content(), kses'd
 * — one paragraph of prose is what WordPress already has a home for; this is
 * NOT an ACF field and NOT the section-repeater pattern single.php uses (no
 * per-section anchors on this page). "WHAT TO EXPECT" is the `what_to_expect`
 * repeater on group_lp_class, rendered as <ol>/<li> — the numerals are
 * meaningful order, not decoration (ChecklistItem's own a11y note). Meeting
 * Point reads the linked `location`'s `meeting_point` / `latitude` /
 * `longitude`. "Your Coach" is the class's first `coaches` entry, reading its
 * `bio` field (a different field to `quote`, which is the Coaches block's
 * pull-quote — not used here). Upcoming Sessions is the `sessions` repeater,
 * filtered to today-or-later and sorted soonest first, projected into
 * board-row exactly as templates/classes-agenda.php projects the same
 * repeater — same shape, not a second invention.
 *
 * ── Seeded for this port ────────────────────────────────────────────────
 * `what_to_expect` (4 rows, transcribed from ClassDetail.js's own
 * WHAT_TO_EXPECT constant) and the coach `bio` (transcribed from the source's
 * Byline call, `b81HaB/Pza3g`) were added to ONE record each — the
 * "Women's Session" class (bin/demo-content/lp_class.json) and its first
 * coach, Sofia Reyes (bin/demo-content/lp_coach.json) — because "Women's
 * Session" is the only seeded class whose `location` is Southbank Undercroft,
 * the design's own meeting point ("Under the Queen Elizabeth Hall, by the
 * painted banks at the river end." matches the seeded location's
 * `meeting_point` verbatim). Its audience field doesn't literally say "adult
 * drop-in" (it's women/non-binary only, price £15 not £12) — location was the
 * strongest and only unambiguous match, so it won over the other two facts.
 *
 * ── Data-model gaps (do not fabricate; report, don't invent fields) ───────
 * FACT RAIL / WHEN, WHO: the source asserts a weekly recurrence + time range
 * ("Saturdays · 10:30–12:00") and an audience/age policy ("Adults (14+)").
 * `sessions` stores dated one-off occurrences with a single `time`, not a
 * recurrence; there is no audience field at all. Both rows are OMITTED
 * rather than fabricated — same gap class as PORT-FINDINGS §19/§20. The
 * AsidePanel LEVEL row and PageMasthead note make the same call: the source's
 * "All levels · 14+" / "Saturdays, 10:30 to 12:00…" strings assert facts the
 * model doesn't have, so only the level-term half (or the class's own
 * `subtitle`) survives.
 *
 * WATCH THE CLASS: `lp_class` has no video field (`lp_tutorial.video_url` is
 * the only video field in the schema, and there is no relationship from a
 * class to a tutorial). The control is ported as the source has it — Button
 * `variant="primary"`, no href, so it renders <button type="button"> rather
 * than a link to nowhere. The source's caption chip beneath it
 * ("A Saturday session at the Southbank · 1:24") names a day and a running
 * time that don't exist as data either, so — unlike the button, which the
 * brief explicitly says to keep — the chip is dropped rather than printed
 * with invented numbers.
 *
 * STREETVIEW: the source's anchor has `href="#"` — not a real destination.
 * Per the port brief, that isn't ported as a dead link (an `<a>` with no
 * target is worse than no control: it's focusable, announces as a link, and
 * silently reloads the page on activation). The label survives as plain text
 * in the same position; the interactive affordance does not.
 *
 * Board foot / Onward "next": the source's copy names a fixed day
 * ("BOOK A SATURDAY →") and a fixed site ("Next open session at Vauxhall")
 * regardless of which class or location is on screen. Both are rebuilt from
 * real fields instead — the class's own `primary_action` link and its actual
 * `location` — rather than copied verbatim, for the same reason as the WHEN
 * gap above: this page's session pattern is not always Saturdays.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lp_post_id = get_the_ID();

	/*
	 * Raw, not `the_content` filtered: the design puts the About copy inside
	 * ONE styled <p>, and running the filter would wrap it in <p> tags of its
	 * own — a <p> nested in a <p>, which is invalid. A class body that grows
	 * past one paragraph is the moment to drop the design's <p> and let the
	 * filter own the markup.
	 *
	 * Guarded because the label and the paragraph are one group: without this
	 * a class with an empty body renders "ABOUT THIS CLASS" over nothing.
	 */
	$lp_about = trim( (string) get_the_content() );

	$lp_subtitle = (string) ( function_exists( 'get_field' ) ? get_field( 'subtitle', $lp_post_id ) : '' );

	$lp_location_id    = (int) ( function_exists( 'get_field' ) ? get_field( 'location', $lp_post_id ) : 0 );
	$lp_location_title = $lp_location_id ? get_the_title( $lp_location_id ) : '';
	$lp_meeting_point  = $lp_location_id ? (string) get_field( 'meeting_point', $lp_location_id ) : '';
	$lp_lat            = $lp_location_id ? (string) get_field( 'latitude', $lp_location_id ) : '';
	$lp_lon            = $lp_location_id ? (string) get_field( 'longitude', $lp_location_id ) : '';
	$lp_coords         = ( '' !== $lp_lat && '' !== $lp_lon ) ? sprintf( '%s°N %s°W', $lp_lat, ltrim( $lp_lon, '-' ) ) : '';

	$lp_level_terms = get_the_terms( $lp_post_id, 'lp_level' );
	$lp_level_name  = ( is_array( $lp_level_terms ) && $lp_level_terms ) ? $lp_level_terms[0]->name : '';

	$lp_price       = (string) ( function_exists( 'get_field' ) ? get_field( 'price', $lp_post_id ) : '' );
	$lp_price_label = (string) ( function_exists( 'get_field' ) ? get_field( 'price_label', $lp_post_id ) : '' );
	$lp_price_value = '' !== $lp_price ? trim( $lp_price . ( '' !== $lp_price_label ? ' ' . strtolower( $lp_price_label ) : '' ) ) : '';

	$lp_coach_ids = function_exists( 'get_field' ) ? get_field( 'coaches', $lp_post_id ) : null;
	$lp_coach_id  = ( is_array( $lp_coach_ids ) && $lp_coach_ids ) ? (int) $lp_coach_ids[0] : 0;

	$lp_primary = lp_action( function_exists( 'get_field' ) ? get_field( 'primary_action', $lp_post_id ) : null );

	// Upcoming sessions: today or later, soonest first. Mirrors
	// templates/classes-agenda.php's own projection of this repeater.
	$lp_sessions_field = function_exists( 'get_field' ) ? get_field( 'sessions', $lp_post_id ) : null;
	$lp_today          = current_datetime()->format( 'Y-m-d' );
	$lp_upcoming       = array();
	foreach ( is_array( $lp_sessions_field ) ? $lp_sessions_field : array() as $lp_row ) {
		$lp_date = (string) ( $lp_row['date'] ?? '' );
		if ( '' === $lp_date || $lp_date < $lp_today ) {
			continue;
		}
		$lp_upcoming[] = $lp_row;
	}
	usort(
		$lp_upcoming,
		static fn( $lp_a, $lp_b ): int => strcmp( $lp_a['date'] . $lp_a['time'], $lp_b['date'] . $lp_b['time'] )
	);
	$lp_next = $lp_upcoming[0] ?? null;

	// A dated session's board label, derived — never the fabricated "Saturday".
	$lp_row_date_label = static function ( string $lp_date ): string {
		$lp_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $lp_date );
		return $lp_dt ? strtoupper( $lp_dt->format( 'D j M' ) ) : '';
	};

	// Fact Rail: SITE / LEVEL / PRICE only. WHEN and WHO are the two gaps
	// documented above — omitted, not fabricated.
	$lp_facts = array();
	if ( '' !== $lp_location_title ) {
		$lp_facts[] = array(
			'icon'  => 'icon-map-pin',
			'label' => 'SITE',
			'value' => $lp_location_title,
		);
	}
	if ( '' !== $lp_level_name ) {
		$lp_facts[] = array(
			'icon'  => 'icon-academic-cap',
			'label' => 'LEVEL',
			'value' => $lp_level_name,
		);
	}
	if ( '' !== $lp_price_value ) {
		$lp_facts[] = array(
			'icon'  => 'icon-currency-pound',
			'label' => 'PRICE',
			'value' => $lp_price_value,
		);
	}

	// What to expect.
	$lp_expect = function_exists( 'get_field' ) ? get_field( 'what_to_expect', $lp_post_id ) : null;
	$lp_expect = is_array( $lp_expect ) ? $lp_expect : array();

	// Booking aside.
	$lp_aside_rows = array();
	if ( $lp_next ) {
		$lp_next_dt      = DateTimeImmutable::createFromFormat( 'Y-m-d', (string) ( $lp_next['date'] ?? '' ) );
		$lp_aside_rows[] = array(
			'label' => 'NEXT SESSION',
			'value' => $lp_next_dt
				? sprintf( '%s · %s', $lp_next_dt->format( 'D j M' ), (string) ( $lp_next['time'] ?? '' ) )
				: (string) ( $lp_next['time'] ?? '' ),
		);
	}
	if ( '' !== $lp_location_title ) {
		$lp_aside_rows[] = array(
			'label' => 'SITE',
			'value' => $lp_location_title,
		);
	}
	if ( '' !== $lp_level_name ) {
		$lp_aside_rows[] = array(
			'label' => 'LEVEL',
			'value' => $lp_level_name,
		);
	}
	if ( '' !== $lp_price_value ) {
		$lp_aside_rows[] = array(
			'label' => 'PRICE',
			'value' => $lp_price_value,
		);
	}

	// Coach.
	$lp_coach_name  = '';
	$lp_coach_role  = '';
	$lp_coach_bio   = '';
	$lp_coach_photo = 0;
	if ( $lp_coach_id ) {
		$lp_coach_name  = get_the_title( $lp_coach_id );
		$lp_coach_role  = (string) get_field( 'role', $lp_coach_id );
		$lp_coach_bio   = (string) get_field( 'bio', $lp_coach_id );
		$lp_coach_photo = has_post_thumbnail( $lp_coach_id ) ? (int) get_post_thumbnail_id( $lp_coach_id ) : 0;
	}

	// Upcoming Sessions board.
	$lp_board_rows = array();
	foreach ( $lp_upcoming as $lp_row ) {
		$lp_board_rows[] = array(
			'part' => 'components/board-row',
			'args' => array(
				'variant'    => 'default',
				'time'       => (string) ( $lp_row['time'] ?? '' ),
				'date_label' => $lp_row_date_label( (string) ( $lp_row['date'] ?? '' ) ),
				'title'      => get_the_title( $lp_post_id ),
				'subtitle'   => $lp_subtitle,
				'location'   => $lp_location_title,
				'level'      => $lp_level_name,
				'spaces'     => (string) ( $lp_row['spaces'] ?? '' ),
				'sold_out'   => ! empty( $lp_row['sold_out'] ),
			),
		);
	}

	$lp_board_title = strtoupper( trim( get_the_title( $lp_post_id ) . ( $lp_location_title ? ' — ' . $lp_location_title : '' ) ) );
	$lp_live_label  = sprintf( 'AVAILABILITY UPDATED %s', current_datetime()->format( 'H:i' ) );
	$lp_foot_left   = $lp_primary ? strtoupper( $lp_primary['label'] ) . ' →' : '';
	$lp_foot_href   = (string) ( $lp_primary['href'] ?? '' );
	$lp_foot_right  = '' !== $lp_price ? sprintf( '%s PER %s · FREE CANCELLATION UP TO 24 HOURS BEFORE', $lp_price, strtoupper( $lp_price_label ) ) : '';

	// Onward.
	$lp_onward_next = array();
	if ( $lp_primary ) {
		$lp_onward_next = array(
			'keyword' => 'BOOK THIS CLASS →',
			'label'   => $lp_location_title ? sprintf( 'Next open session at %s', $lp_location_title ) : 'Book your next session',
			'href'    => $lp_primary['href'],
		);
	}
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
						'label' => 'CLASSES',
						'href'  => (string) get_post_type_archive_link( 'lp_class' ),
					),
					array( 'label' => strtoupper( get_the_title( $lp_post_id ) ) ),
				),
				'action' => array(
					'label' => 'ALL CLASSES ↗',
					'href'  => (string) get_post_type_archive_link( 'lp_class' ),
				),
			)
		);

		lp_part(
			'components/page-masthead',
			array(
				'title' => get_the_title( $lp_post_id ),
				'note'  => $lp_subtitle,
			)
		);
		?>

		<div class="w-full bg-neutral" data-component="class-detail-fact-rail">
			<div class="px-6 lg:px-16 py-scale-s grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-x-[40px] gap-y-6" data-mount="rail">
				<?php foreach ( $lp_facts as $lp_fact ) : ?>
					<?php lp_part( 'components/fact-row', array_merge( $lp_fact, array( 'surface' => 'board' ) ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="w-full bg-base-100" data-component="class-detail-body">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col lg:flex-row gap-10 lg:gap-16 items-start">
				<div class="flex-1 min-w-0 flex flex-col gap-[28px]">
					<div class="relative w-full aspect-video bg-base-300 overflow-hidden">
						<?php if ( has_post_thumbnail( $lp_post_id ) ) : ?>
							<?php
							lp_part(
								'components/media-photo',
								array(
									'image_id' => get_post_thumbnail_id( $lp_post_id ),
									'size'     => 'lp_wide',
									'sizes'    => '(min-width: 1024px) 50vw, 100vw',
								)
							);
							?>
						<?php endif; ?>
						<span class="absolute top-[16px] left-[16px]">
							<?php
							lp_part(
								'elements/button',
								array(
									'variant'          => 'primary',
									'label'            => 'WATCH THE CLASS',
									'trailing_icon_id' => 'icon-play',
								)
							);
							?>
						</span>
					</div>
					<?php if ( $lp_about ) : ?>
						<div class="flex flex-col gap-[12px]">
							<?php
							lp_part(
								'elements/glyph-label',
								array(
									'label'   => 'ABOUT THIS CLASS',
									'surface' => 'page',
									'tone'    => 'muted',
								)
							);
							?>
							<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/80 max-w-[560px]"><?php echo wp_kses_post( $lp_about ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( $lp_expect ) : ?>
						<div class="flex flex-col gap-[16px]">
							<?php
							lp_part(
								'elements/glyph-label',
								array(
									'label'   => 'WHAT TO EXPECT',
									'surface' => 'page',
									'tone'    => 'muted',
								)
							);
							?>
							<ol class="flex flex-col gap-[14px] m-0 p-0 list-none max-w-[560px]">
								<?php foreach ( $lp_expect as $lp_i => $lp_step ) : ?>
									<li>
										<?php
										lp_part(
											'components/checklist-item',
											array(
												'text'  => (string) ( $lp_step['text'] ?? '' ),
												'index' => str_pad( (string) ( $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
											)
										);
										?>
									</li>
								<?php endforeach; ?>
							</ol>
						</div>
					<?php endif; ?>
				</div>
				<div class="w-full lg:w-[380px] lg:shrink-0">
					<?php
					lp_part(
						'components/aside-panel',
						array(
							'title'      => 'BOOK THIS CLASS',
							'spots_left' => $lp_next ? (string) ( $lp_next['spaces'] ?? '' ) : '',
							'rows'       => $lp_aside_rows,
							'cta_label'  => 'BOOK THIS SESSION',
							'href'       => (string) ( $lp_primary['href'] ?? '' ),
							'note'       => 'Free cancellation up to 24 hours before the session. All kit provided.',
							'surface'    => 'page',
						)
					);
					?>
				</div>
			</div>
		</div>

		<div class="w-full bg-accent" data-component="class-detail-meeting-point">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-16">
				<?php if ( '' !== $lp_meeting_point || '' !== $lp_coords ) : ?>
					<div class="flex flex-col gap-[14px] max-w-[560px]">
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => 'MEETING POINT',
								'surface' => 'accent',
								'tone'    => 'ink',
							)
						);
						?>
						<?php if ( '' !== $lp_meeting_point ) : ?>
							<p class="font-body text-[14px] leading-[1.6] tracking-[0.1px] text-accent-content/85 m-0"><?php echo esc_html( $lp_meeting_point ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $lp_coords ) : ?>
							<div class="flex flex-wrap items-center gap-[16px] pt-1.5">
								<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-accent-content/70"><?php echo esc_html( $lp_coords ); ?></span>
								<?php /* Source href is '#' — no real destination. Label kept, no dead <a>; see docblock. */ ?>
								<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary">STREETVIEW ↗</span>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $lp_coach_id ) : ?>
					<div class="flex flex-col gap-[16px]">
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => 'YOUR COACH',
								'surface' => 'accent',
								'tone'    => 'ink',
							)
						);
						lp_part(
							'components/byline',
							array(
								'name'      => $lp_coach_name,
								'secondary' => $lp_coach_role,
								'bio'       => $lp_coach_bio,
								'size'      => 'lg',
								'surface'   => 'accent',
								'photo_id'  => $lp_coach_photo,
							)
						);
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="w-full bg-neutral" data-component="class-detail-upcoming-sessions">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
				<?php
				lp_part(
					'components/section-head',
					array(
						'surface' => 'board',
						'eyebrow' => 'UPCOMING SESSIONS',
						'heading' => 'Upcoming sessions.',
						'note'    => sprintf( '(%02d)', count( $lp_upcoming ) ),
					)
				);

				lp_part(
					'components/board-shell',
					array(
						'board_title' => $lp_board_title,
						'live_label'  => $lp_live_label,
						'columns'     => array( 'TIME', 'SESSION', 'LOCATION', 'LEVEL', 'AVAILABILITY' ),
						'rows'        => $lp_board_rows,
						'foot_left'   => $lp_foot_left,
						'foot_href'   => $lp_foot_href,
						'foot_right'  => $lp_foot_right,
					)
				);
				?>
			</div>
		</div>

		<?php
		lp_part(
			'components/page-onward',
			array(
				'prev' => array(
					'keyword' => '← ALL CLASSES',
					'label'   => 'Back to the class listings',
					'href'    => (string) get_post_type_archive_link( 'lp_class' ),
				),
				'next' => $lp_onward_next,
			)
		);
		?>
	</main>

<?php
endwhile;

get_footer();
