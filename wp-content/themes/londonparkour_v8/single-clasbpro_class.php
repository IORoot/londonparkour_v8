<?php
/**
 * single-clasbpro_class.php — ClassDetail. Every public `clasbpro_class` post.
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
 * Theme fields: acf_subtitle, acf_location, acf_coaches, acf_what_to_expect.
 * Duration / price / image / sessions come from clasbpro helpers. The aside
 * CTA opens the shared booking drawer — no primary_action /book/… href.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lp_post_id = get_the_ID();
	$lp_classes = lp_classes_page_url( 'classes' );

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

	$lp_subtitle = lp_class_composed_subtitle( $lp_post_id );

	$lp_location_id    = lp_class_location_id( $lp_post_id );
	$lp_location_title = $lp_location_id ? get_the_title( $lp_location_id ) : '';
	$lp_meeting_point  = $lp_location_id ? (string) get_field( 'meeting_point', $lp_location_id ) : '';
	$lp_lat            = $lp_location_id ? (string) get_field( 'latitude', $lp_location_id ) : '';
	$lp_lon            = $lp_location_id ? (string) get_field( 'longitude', $lp_location_id ) : '';
	$lp_coords         = ( '' !== $lp_lat && '' !== $lp_lon ) ? sprintf( '%s°N %s°W', $lp_lat, ltrim( $lp_lon, '-' ) ) : '';

	$lp_level_terms = get_the_terms( $lp_post_id, 'lp_level' );
	$lp_level_name  = ( is_array( $lp_level_terms ) && $lp_level_terms ) ? $lp_level_terms[0]->name : '';

	$lp_price       = lp_class_price_display( $lp_post_id );
	$lp_price_label = lp_class_price_label();
	$lp_price_value = '' !== $lp_price ? trim( $lp_price . ' ' . strtolower( $lp_price_label ) ) : '';

	$lp_coach_ids = lp_class_coach_ids( $lp_post_id );
	$lp_coach_id  = $lp_coach_ids ? (int) $lp_coach_ids[0] : 0;

	$lp_upcoming = lp_class_upcoming_sessions( $lp_post_id, 8 );
	$lp_next     = $lp_upcoming[0] ?? null;

	$lp_book = lp_class_book_button_args(
		$lp_post_id,
		(string) ( $lp_next['date'] ?? '' ),
		'BOOK THIS SESSION',
		'band'
	);

	// A dated session's board label, derived — never the fabricated "Saturday".
	$lp_row_date_label = static function ( string $lp_date ): string {
		$lp_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $lp_date );
		return $lp_dt ? strtoupper( $lp_dt->format( 'D j M' ) ) : '';
	};

	// Fact Rail: SITE / LEVEL / PRICE only. WHEN and WHO are omitted — the
	// model still has no audience / recurrence fields (same gap as before).
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
	$lp_expect = function_exists( 'get_field' ) ? get_field( 'acf_what_to_expect', $lp_post_id ) : null;
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
				'variant'          => 'sell',
				'time'             => (string) ( $lp_row['time'] ?? '' ),
				'date_label'       => $lp_row_date_label( (string) ( $lp_row['date'] ?? '' ) ),
				'title'            => get_the_title( $lp_post_id ),
				'subtitle'         => $lp_subtitle,
				'location'         => $lp_location_title,
				'level'            => $lp_level_name,
				'spaces'           => (string) ( $lp_row['spaces'] ?? '' ),
				'sold_out'         => ! empty( $lp_row['sold_out'] ),
				'price'            => $lp_price,
				'price_label'      => $lp_price_label,
				'book_label'       => (string) ( $lp_row['book_label'] ?? ( empty( $lp_row['sold_out'] ) ? 'BOOK' : 'WAITLIST' ) ),
				'book_class_id'    => $lp_post_id,
				'book_preset_date' => (string) ( $lp_row['date'] ?? '' ),
			),
		);
	}

	$lp_board_title = strtoupper( trim( get_the_title( $lp_post_id ) . ( $lp_location_title ? ' — ' . $lp_location_title : '' ) ) );
	$lp_live_label  = sprintf( 'AVAILABILITY UPDATED %s', current_datetime()->format( 'H:i' ) );
	$lp_foot_left   = '';
	$lp_foot_href   = '';
	$lp_foot_right  = '' !== $lp_price ? sprintf( '%s PER %s · FREE CANCELLATION UP TO 24 HOURS BEFORE', $lp_price, strtoupper( $lp_price_label ) ) : '';

	// Onward — default Classes page is Agenda at /classes/.
	$lp_onward_next = array(
		'keyword' => 'ALL CLASSES →',
		'label'   => $lp_location_title ? sprintf( 'More sessions near %s', $lp_location_title ) : 'Back to every class type',
		'href'    => $lp_classes,
	);

	$lp_image_id = lp_class_image_id( $lp_post_id );
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
						'href'  => $lp_classes,
					),
					array( 'label' => strtoupper( get_the_title( $lp_post_id ) ) ),
				),
				'action' => array(
					'label' => 'ALL CLASSES ↗',
					'href'  => $lp_classes,
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
						<?php if ( $lp_image_id ) : ?>
							<?php
							lp_part(
								'components/media-photo',
								array(
									'image_id' => $lp_image_id,
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
							'title'       => 'BOOK THIS CLASS',
							'spots_left'  => $lp_next ? (string) ( $lp_next['spaces'] ?? '' ) : '',
							'rows'        => $lp_aside_rows,
							'cta_label'   => $lp_book['label'],
							'command'     => $lp_book['command'] ?? '',
							'command_for' => $lp_book['command_for'] ?? '',
							'data_attrs'  => $lp_book['data_attrs'] ?? array(),
							'note'        => 'Free cancellation up to 24 hours before the session. All kit provided.',
							'surface'     => 'page',
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
					'label'   => 'Back to this week’s agenda',
					'href'    => $lp_classes,
				),
				'next' => $lp_onward_next,
			)
		);
		?>
	</main>

<?php
endwhile;

get_footer();
