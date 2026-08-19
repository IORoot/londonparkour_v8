<?php
/**
 * Hero — page-opening claim with Swiss grid, coordinates, and Next Class
 * board (Homepage default) or Next Sessions board (master alternate).
 *
 * Ported from src/stories/Blocks/Hero/Hero.js (`T1cC4` / homepage `NolKj`).
 *
 * Default board_style is `next` — the soonest upcoming clasbpro session
 * (past start times drop out automatically). The multi-row sessions board
 * remains the master alternate. Scrim is media-photo `hero` → `bg-neutral/50`.
 *
 * The primary CTA is a conversion shortcut: it opens the booking drawer on
 * Adult Beginners East's next Saturday (see lp_hero_first_class_book_args()).
 * The next-class board is independent — soonest session of any class —
 * and the whole board is the reserve hit target (yellow fill hover).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_sessions = array(
	array(
		'time'     => '18:30',
		'day'      => 'TODAY',
		'title'    => 'Beginners Parkour',
		'location' => 'Vauxhall · Level 1',
		'spaces'   => '4 left',
	),
	array(
		'time'     => '19:45',
		'day'      => 'TODAY',
		'title'    => 'All Levels Open Gym',
		'location' => 'Stratford East · Open',
		'spaces'   => '2 left',
	),
	array(
		'time'     => '07:00',
		'day'      => 'FRI',
		'title'    => 'Sunrise Session',
		'location' => 'Southbank · Open',
		'spaces'   => '9 spaces',
	),
	array(
		'time'     => '10:00',
		'day'      => 'SAT',
		'title'    => 'Kids & Families',
		'location' => 'Peckham Rye · Ages 6–12',
		'spaces'   => 'Waitlist',
		'sold_out' => true,
	),
);

$lp_default_trust = array( '2,400+ TRAINED', '11 YEARS', '3 LONDON SITES' );

$lp_eyebrow      = lp_section_label( (string) ( $args['eyebrow'] ?? '01 — PARKOUR CLASSES / LONDON / EST. 2015' ), $args['_section_number'] ?? null );
$lp_headline     = (string) ( $args['headline'] ?? "the world is \nyour playground." );
$lp_lead         = (string) ( $args['lead'] ?? 'Parkour is the practice of getting where you want to go. We teach it across three London sites, to every age and every body. No experience needed.' );
$lp_coordinates  = (string) ( $args['coordinates'] ?? 'N 51.5074° / W 0.1278°' );
$lp_coordinates_link = (string) ( $args['coordinates_link'] ?? '' );
$lp_board_style  = (string) ( $args['board_style'] ?? 'next' );
$lp_board_ttl    = (string) ( $args['board_title'] ?? 'NEXT SESSIONS' );
$lp_board_stmp   = (string) ( $args['board_stamp'] ?? 'UPDATED 09:12 · THU 30 JUL' );
$lp_foot_label   = (string) ( $args['board_foot_label'] ?? 'View full timetable' );
$lp_foot_href    = (string) ( $args['board_foot_href'] ?? '#' );
$lp_foot_count   = (string) ( $args['board_foot_count'] ?? '32 SESSIONS / WEEK' );
$lp_scroll       = (string) ( $args['scroll_label'] ?? '↓ SCROLL' );
$lp_rating       = (string) ( $args['rating'] ?? '4.9 ★ (312)' );

// Legacy ACF value from before next-class rename.
if ( 'featured' === $lp_board_style ) {
	$lp_board_style = 'next';
}
if ( ! in_array( $lp_board_style, array( 'next', 'sessions' ), true ) ) {
	$lp_board_style = 'next';
}

$lp_primary   = lp_action( $args['primary_action'] ?? null );
$lp_secondary = lp_action( $args['secondary_action'] ?? null );

if ( ! $lp_primary ) {
	$lp_primary = array(
		'label'  => 'BOOK YOUR FIRST CLASS — £15',
		'href'   => '',
		'target' => '',
	);
}

$lp_next = array();
if ( 'next' === $lp_board_style ) {
	$lp_next = lp_hero_next_class_board();
}

$lp_sessions = array();
if ( 'sessions' === $lp_board_style ) {
	$lp_sessions = array_map(
		static function ( array $item ): array {
			$day = (string) ( $item['day'] ?? $item['date_label'] ?? '' );
			$loc = (string) ( $item['location'] ?? '' );
			return array(
				'time'     => (string) ( $item['time'] ?? '' ),
				'title'    => (string) ( $item['title'] ?? '' ),
				'location' => $day ? ( $day . ' · ' . $loc ) : $loc,
				'spaces'   => (string) ( $item['spaces'] ?? '' ),
				'sold_out' => ! empty( $item['sold_out'] ),
				'href'     => (string) ( $item['url'] ?? '' ),
			);
		},
		lp_resolve_source( $args, lp_class_post_type(), array( 'expand' => 'sessions' ) )
	);
	if ( ! $lp_sessions ) {
		$lp_sessions = array_map(
			static function ( array $item ): array {
				$day = (string) ( $item['day'] ?? '' );
				$loc = (string) ( $item['location'] ?? '' );
				return array(
					'time'     => (string) ( $item['time'] ?? '' ),
					'title'    => (string) ( $item['title'] ?? '' ),
					'location' => $day ? ( $day . ' · ' . $loc ) : $loc,
					'spaces'   => (string) ( $item['spaces'] ?? '' ),
					'sold_out' => ! empty( $item['sold_out'] ),
					'href'     => '',
				);
			},
			$lp_default_sessions
		);
	}
}

$lp_trust = array();
foreach ( is_array( $args['trust'] ?? null ) ? $args['trust'] : array() as $lp_row ) {
	$lp_text = is_array( $lp_row ) ? (string) ( $lp_row['label'] ?? '' ) : (string) $lp_row;
	if ( '' !== $lp_text ) {
		$lp_trust[] = $lp_text;
	}
}
if ( ! $lp_trust ) {
	$lp_trust = $lp_default_trust;
}

$lp_media_id  = ! empty( $args['media'] ) ? (int) $args['media'] : 0;
$lp_has_media = (bool) $lp_media_id;

$lp_slides = array();
foreach ( is_array( $args['media_slides'] ?? null ) ? $args['media_slides'] : array() as $lp_row ) {
	if ( ! is_array( $lp_row ) ) {
		continue;
	}
	$lp_slide_id = ! empty( $lp_row['image'] ) ? (int) $lp_row['image'] : 0;
	if ( ! $lp_slide_id ) {
		continue;
	}
	$lp_slides[] = $lp_row;
}
if ( ! $lp_slides && $lp_has_media ) {
	$lp_slides[] = array(
		'image'       => $lp_media_id,
		'coordinates' => $lp_coordinates,
		'link'        => $lp_coordinates_link,
	);
}

$lp_spacing   = lp_section_spacing( $args );
// Homepage: SiteNav is absolutely positioned over this band. Pad by the bar
// height (60 mobile / 76 desktop) on top of the Hero Body spacer (88 / 132)
// so claim/board keep their place — 148 / 208. Default on for the front page.
$lp_under_nav = array_key_exists( 'under_nav', $args )
	? ! empty( $args['under_nav'] )
	: is_front_page();
// Whole literals — Tailwind v4 scans source text. No negative margin: the
// over-hero SiteNav is `absolute`, so this band starts at y=0 under it.
$lp_shell = $lp_under_nav
	? 'relative overflow-hidden bg-neutral min-h-[700px] lg:min-h-[940px]'
	: 'relative overflow-hidden bg-neutral min-h-[640px] lg:min-h-[864px]';
$lp_body = $lp_under_nav
	? 'relative z-10 px-6 lg:px-16 pt-[148px] lg:pt-[208px] pb-scale-xl flex flex-col min-h-[700px] lg:min-h-[940px]'
	: 'relative z-10 px-6 lg:px-16 pt-[88px] lg:pt-[132px] pb-scale-xl flex flex-col min-h-[640px] lg:min-h-[864px]';
// Coords are absolute against the hero shell, so under-nav they need the bar
// height added to the original top-6 / lg:top-10 offsets (24+60 / 40+76).
$lp_coords_class = $lp_under_nav
	? 'absolute top-[84px] right-6 lg:top-[116px] lg:right-16 font-label text-step--2 font-normal tracking-[0.6px] uppercase text-neutral-content/50 m-0 hover:text-primary transition-colors duration-150'
	: 'absolute top-6 right-6 lg:top-10 lg:right-16 font-label text-step--2 font-normal tracking-[0.6px] uppercase text-neutral-content/50 m-0 hover:text-primary transition-colors duration-150';

$lp_headline_html = nl2br( esc_html( $lp_headline ), false );
$lp_headline_decode = esc_attr( str_replace( array( "\r\n", "\n", "\r" ), '\\n', $lp_headline ) );

$lp_initial_coords = $lp_coordinates;
$lp_initial_link   = $lp_coordinates_link;
if ( $lp_slides ) {
	$lp_first = $lp_slides[0];
	$lp_fc    = (string) ( $lp_first['coordinates'] ?? '' );
	if ( '' !== $lp_fc ) {
		$lp_initial_coords = $lp_fc;
	}
	$lp_fl = (string) ( $lp_first['link'] ?? '' );
	if ( '' !== $lp_fl ) {
		$lp_initial_link = $lp_fl;
	} elseif ( '' === $lp_initial_link ) {
		$lp_initial_link = $lp_coordinates_link;
	}
}
$lp_show_coords = ( '' !== $lp_initial_coords || '' !== $lp_coordinates );
?>
<div
	class="<?php echo lp_classes( $lp_shell, $lp_spacing ); ?>"
	data-component="hero"
	data-board-style="<?php echo esc_attr( $lp_board_style ); ?>"
	data-under-nav="<?php echo $lp_under_nav ? 'true' : 'false'; ?>"
	<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<?php if ( $lp_slides ) : ?>
		<?php // Ken Burns imgs get z-index from the effect — isolate so they stay behind scrim/grid/claim. ?>
		<div class="absolute inset-0 z-0 isolate overflow-hidden" data-motion-ken-burns aria-hidden="true">
			<?php
			foreach ( $lp_slides as $lp_si => $lp_slide ) :
				$lp_sid = (int) ( $lp_slide['image'] ?? 0 );
				if ( ! $lp_sid ) {
					continue;
				}
				$lp_kb_attrs = array();
				if ( isset( $lp_slide['duration'] ) && '' !== $lp_slide['duration'] ) {
					$lp_kb_attrs['data-kb-duration'] = (string) $lp_slide['duration'];
				}
				if ( isset( $lp_slide['fade'] ) && '' !== $lp_slide['fade'] ) {
					$lp_kb_attrs['data-kb-fade'] = (string) $lp_slide['fade'];
				}
				if ( ! empty( $lp_slide['zoom'] ) ) {
					$lp_kb_attrs['data-kb-zoom'] = (string) $lp_slide['zoom'];
				}
				if ( isset( $lp_slide['scale'] ) && '' !== $lp_slide['scale'] ) {
					$lp_kb_attrs['data-kb-scale'] = (string) $lp_slide['scale'];
				}
				if ( ! empty( $lp_slide['origin'] ) ) {
					$lp_kb_attrs['data-kb-origin'] = (string) $lp_slide['origin'];
				}
				$lp_slide_coords = (string) ( $lp_slide['coordinates'] ?? '' );
				if ( '' === $lp_slide_coords ) {
					$lp_slide_coords = $lp_coordinates;
				}
				if ( '' !== $lp_slide_coords ) {
					$lp_kb_attrs['data-kb-coordinates'] = $lp_slide_coords;
				}
				$lp_slide_link = (string) ( $lp_slide['link'] ?? '' );
				if ( '' === $lp_slide_link ) {
					$lp_slide_link = $lp_coordinates_link;
				}
				if ( '' !== $lp_slide_link ) {
					$lp_kb_attrs['data-kb-href'] = $lp_slide_link;
				}
				$lp_photo = array(
					'image_id'      => $lp_sid,
					'scrim'         => 'none',
					'size'          => 'lp_wide_lg',
					'sizes'         => '100vw',
					'loading'       => 0 === $lp_si ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $lp_si ? 'high' : 'auto',
					'attrs'         => $lp_kb_attrs,
				);
				if ( 0 === $lp_si && array_key_exists( 'media_alt', $args ) ) {
					$lp_photo['alt'] = (string) $args['media_alt'];
				}
				lp_part( 'components/media-photo', $lp_photo );
			endforeach;
			?>
		</div>
		<div class="absolute inset-0 z-[1] bg-neutral/50" aria-hidden="true"></div>
	<?php else : ?>
		<div class="absolute inset-0 z-0 bg-neutral" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="absolute inset-0 z-[1] pointer-events-none opacity-[0.16]" aria-hidden="true" data-slot="grid">
		<div class="absolute inset-0 flex">
			<?php for ( $lp_i = 0; $lp_i < 13; $lp_i++ ) : ?>
				<span class="flex-1 border-l border-neutral-content last:border-r"></span>
			<?php endfor; ?>
		</div>
		<div class="absolute left-0 right-0 top-[12%] h-px bg-neutral-content"></div>
		<div class="absolute left-0 right-0 top-[35%] h-px bg-neutral-content"></div>
		<div class="absolute left-0 right-0 top-[65%] h-px bg-neutral-content"></div>
		<div class="absolute left-0 right-0 top-[88%] h-px bg-neutral-content"></div>
	</div>

	<div class="<?php echo esc_attr( $lp_body ); ?>">
		<?php if ( $lp_show_coords ) : ?>
			<?php if ( '' !== $lp_initial_link ) : ?>
				<a
					class="<?php echo esc_attr( $lp_coords_class ); ?>"
					data-kb-live-coords
					data-motion-decode="<?php echo esc_attr( $lp_initial_coords ); ?>"
					data-motion-decode-charset="gps"
					href="<?php echo esc_url( $lp_initial_link ); ?>"
					target="_blank"
					rel="noopener noreferrer"
				><?php echo esc_html( $lp_initial_coords ); ?></a>
			<?php else : ?>
				<a
					class="<?php echo esc_attr( $lp_coords_class ); ?>"
					data-kb-live-coords
					data-motion-decode="<?php echo esc_attr( $lp_initial_coords ); ?>"
					data-motion-decode-charset="gps"
					aria-disabled="true"
				><?php echo esc_html( $lp_initial_coords ); ?></a>
			<?php endif; ?>
		<?php endif; ?>

		<div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-10 xl:gap-x-[72px] flex-1">
			<div class="flex flex-col gap-6 lg:gap-8 xl:max-w-[664px]" data-slot="claim">
				<p class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-primary"><?php echo esc_html( $lp_eyebrow ); ?></p>
				<h1 class="font-display text-step-5 lg:text-step-7 font-bold tracking-[-0.04em] leading-[0.92] text-neutral-content m-0" data-motion-decode="<?php echo $lp_headline_decode; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped via esc_attr above. ?>" data-motion-decode-charset="board"><?php echo $lp_headline_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped then nl2br. ?></h1>
				<p class="font-body text-step--1 text-neutral-content/70 max-w-[470px] m-0"><?php echo esc_html( $lp_lead ); ?></p>
				<div class="flex items-center gap-[28px] flex-wrap">
					<?php
					$lp_primary_btn = array(
						'variant'          => 'primary',
						'label'            => $lp_primary['label'],
						'href'             => $lp_primary['href'],
						'target'           => $lp_primary['target'],
						'trailing_icon_id' => 'icon-arrow-right',
					);
					if ( function_exists( 'lp_hero_first_class_book_args' ) ) {
						$lp_first_book = lp_hero_first_class_book_args( (string) $lp_primary['label'] );
						if ( is_array( $lp_first_book ) ) {
							$lp_primary_btn = $lp_first_book;
						}
					}
					lp_part( 'elements/button', $lp_primary_btn );
					if ( $lp_secondary && '' !== $lp_secondary['label'] ) :
						if ( '' !== $lp_secondary['href'] ) :
							?>
							<a href="<?php echo esc_url( $lp_secondary['href'] ); ?>" class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-neutral-content/80 hover:text-primary transition-colors duration-150"><?php echo esc_html( $lp_secondary['label'] ); ?></a>
						<?php else : ?>
							<span class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-neutral-content/80"><?php echo esc_html( $lp_secondary['label'] ); ?></span>
							<?php
						endif;
					endif;
					?>
				</div>
			</div>

			<?php if ( 'next' === $lp_board_style && $lp_next && '' !== (string) ( $lp_next['name'] ?? '' ) ) : ?>
				<?php
				$lp_next_id   = (int) ( $lp_next['class_id'] ?? 0 );
				$lp_next_date = (string) ( $lp_next['date'] ?? '' );
				$lp_next_lab  = (string) ( $lp_next['foot_label'] ?? 'Reserve a place' );
				$lp_next_aria = implode(
					' — ',
					array_filter(
						array(
							$lp_next_lab,
							(string) ( $lp_next['name'] ?? '' ),
							(string) ( $lp_next['time'] ?? '' ),
							(string) ( $lp_next['when'] ?? '' ),
						)
					)
				);
				$lp_board_cls = 'group block w-full xl:w-[576px] xl:shrink-0 xl:self-end bg-secondary/95 border border-neutral-content/10 hover:bg-primary hover:border-neutral p-0 text-left no-underline cursor-pointer transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';
				if ( $lp_next_id ) {
					$lp_book       = lp_class_book_button_args( $lp_next_id, $lp_next_date, $lp_next_lab );
					$lp_book_attrs = '';
					foreach ( (array) ( $lp_book['data_attrs'] ?? array() ) as $lp_bk => $lp_bv ) {
						$lp_book_attrs .= sprintf( ' %s="%s"', esc_attr( (string) $lp_bk ), esc_attr( (string) $lp_bv ) );
					}
					printf(
						'<button type="button" class="%s" data-slot="next-class-board" aria-label="%s" command="%s" commandfor="%s"%s>',
						esc_attr( $lp_board_cls ),
						esc_attr( $lp_next_aria ),
						esc_attr( (string) ( $lp_book['command'] ?? 'show-modal' ) ),
						esc_attr( (string) ( $lp_book['command_for'] ?? 'lp-booking-drawer' ) ),
						$lp_book_attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per attr above.
					);
				} else {
					echo '<div class="w-full xl:w-[576px] xl:shrink-0 xl:self-end bg-secondary/95 border border-neutral-content/10" data-slot="next-class-board">';
				}
				?>
					<div class="flex items-center justify-between gap-3 px-5 py-[15px] border-b border-neutral-content/10 group-hover:border-neutral/20">
						<span class="font-label text-step--2 font-semibold tracking-[1px] uppercase text-primary group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) $lp_next['title'] ); ?></span>
						<span class="font-label text-[10px] font-normal tracking-[0.6px] text-neutral-content/50 group-hover:text-neutral transition-colors duration-150" data-slot="board-date"><?php echo esc_html( (string) ( $lp_next['when'] ?? '' ) ); ?></span>
					</div>
					<div class="flex items-start gap-4 px-5 py-5">
						<div class="flex-1 min-w-0 flex flex-col gap-1.5">
							<span class="font-heading text-[22px] font-semibold leading-none tracking-[-0.4px] text-neutral-content group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) $lp_next['name'] ); ?></span>
							<span class="font-label text-[11px] font-normal tracking-[0.3px] text-neutral-content/50 truncate group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) $lp_next['meta'] ); ?></span>
						</div>
						<span class="shrink-0 flex items-center gap-2">
							<span class="font-label text-[11px] font-semibold tracking-[0.6px] uppercase text-primary group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) $lp_next['spaces'] ); ?></span>
							<?php lp_icon( 'icon-arrow-right', 'w-3.5 h-3.5 shrink-0 text-primary group-hover:text-neutral transition-colors duration-150' ); ?>
						</span>
					</div>
					<?php if ( ! empty( $lp_next['facts'] ) && is_array( $lp_next['facts'] ) ) : ?>
						<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 px-5 pb-5 border-b border-neutral-content/10 group-hover:border-neutral/20">
							<?php foreach ( $lp_next['facts'] as $lp_fact ) : ?>
								<div class="flex flex-col gap-1.5 min-w-0 pr-3">
									<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-neutral-content/50 group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) ( $lp_fact['label'] ?? '' ) ); ?></span>
									<span class="font-heading text-[15px] font-medium tracking-[-0.2px] text-neutral-content truncate group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) ( $lp_fact['value'] ?? '' ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<div class="flex items-center justify-between gap-3 px-5 py-[15px]">
						<span class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-primary group-hover:text-neutral group-hover:font-semibold transition-colors duration-150"><?php echo esc_html( $lp_next_lab ); ?></span>
						<span class="font-label text-[10px] font-normal tracking-[0.6px] text-neutral-content/50 group-hover:text-neutral transition-colors duration-150"><?php echo esc_html( (string) ( $lp_next['foot_meta'] ?? '' ) ); ?></span>
					</div>
				<?php echo $lp_next_id ? '</button>' : '</div>'; ?>
			<?php elseif ( 'sessions' === $lp_board_style ) : ?>
				<div class="w-full xl:w-[576px] xl:shrink-0 xl:self-end bg-secondary/95" data-slot="board">
					<div class="flex items-center justify-between gap-3 px-5 py-[15px] border-b border-neutral-content/10">
						<span class="font-label text-step--2 font-semibold tracking-[1px] uppercase text-primary"><?php echo esc_html( $lp_board_ttl ); ?></span>
						<span class="font-label text-step--2 font-normal tracking-[0.6px] text-neutral-content/50"><?php echo esc_html( $lp_board_stmp ); ?></span>
					</div>
					<div data-slot="rows">
						<?php
						foreach ( $lp_sessions as $lp_session ) :
							?>
							<div class="px-5">
								<?php
								lp_part(
									'components/departure-row',
									array(
										'time'     => $lp_session['time'],
										'title'    => $lp_session['title'],
										'location' => $lp_session['location'],
										'spaces'   => $lp_session['spaces'],
										'sold_out' => ! empty( $lp_session['sold_out'] ),
										'href'     => $lp_session['href'] ?? '',
									)
								);
								?>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="flex items-center justify-between gap-3 px-5 py-[15px] border-t border-neutral-content/10">
						<?php if ( '' !== $lp_foot_href && '#' !== $lp_foot_href ) : ?>
							<a href="<?php echo esc_url( $lp_foot_href ); ?>" class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-neutral-content/80 hover:text-primary transition-colors duration-150"><?php echo esc_html( $lp_foot_label ); ?></a>
						<?php else : ?>
							<span class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-neutral-content/80"><?php echo esc_html( $lp_foot_label ); ?></span>
						<?php endif; ?>
						<span class="font-label text-step--2 font-normal tracking-[0.6px] text-neutral-content/50"><?php echo esc_html( $lp_foot_count ); ?></span>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="flex items-center justify-between gap-4 flex-wrap mt-auto pt-scale-s border-t border-neutral-content/20">
			<span class="font-label text-step--2 font-normal tracking-[1px] uppercase text-neutral-content/80"><?php echo esc_html( $lp_scroll ); ?></span>
			<div class="flex items-center gap-[22px] flex-wrap">
				<?php foreach ( $lp_trust as $lp_ti => $lp_mark ) : ?>
					<?php if ( $lp_ti > 0 ) : ?>
						<span class="w-px h-[11px] bg-neutral-content/20" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="font-label text-step--2 font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_mark ); ?></span>
				<?php endforeach; ?>
				<span class="w-px h-[11px] bg-neutral-content/20" aria-hidden="true"></span>
				<span class="font-label text-step--2 font-normal tracking-[0.8px] uppercase text-primary"><?php echo esc_html( $lp_rating ); ?></span>
			</div>
		</div>
	</div>
</div>
