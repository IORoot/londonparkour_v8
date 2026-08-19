<?php
/**
 * Classes — "02 — BOOK A SESSION": the departure-board booking section.
 *
 * Ported from src/stories/Blocks/Classes/Classes.js.
 *
 * Takes the CPT source control for the board; every row is
 * components/board-row.php with variant `sell` — each session here shows a
 * fare and a book control, so no row is the plain `default` variant.
 *
 * Sits on the FIXED dark band (`bg-neutral`), so every content colour is drawn
 * from the `neutral-content` family, never `base-content` — in both light
 * themes those two resolve to the same hex. The stamp is elements/status.php
 * with `surface => 'board'` for the same reason.
 *
 * V3 board anatomy (`bhjl3`): THUMB 112 → TIME 112 → GLYPH 40 → SESSION →
 * SITE 168 → LEVEL 164 → FARE 88 → BOOK 96. There is no Spaces column — rows
 * pass `show_spaces => false` and `size => 'lg'`. COLUMN_HEAD stays a
 * hardcoded array (layout classes as well as labels), hand-coupled to
 * board-row.php's lg column geometry.
 *
 * Day splits (pen `Vc2hk`): consecutive rows sharing a `date_label` are grouped
 * under `components/board-day-band` — the slim agenda-style divider, not the
 * large card-agenda day headers. When bands render, the per-row `date_label`
 * under TIME is cleared.
 *
 * @param string $args['eyebrow']
 * @param string $args['heading']
 * @param string $args['note']
 * @param string $args['board_title']
 * @param string $args['stamp']
 * @param array  $args['primary_action'] The board's foot link.
 * @param string $args['foot_note']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_sessions = array(
	array(
		'time'          => '07:00',
		'date_label'    => 'TODAY',
		'day_date'      => '30 JULY 2026',
		'title'         => 'Sunrise Session',
		'subtitle'      => '60 min · outdoor, rain or shine',
		'location'      => 'Peckham Rye',
		'level'         => 'Level 2 · Improver',
		'price'         => '£15',
		'price_label'   => 'DROP-IN',
		'book_label'    => 'WAITLIST',
		'sold_out'      => true,
		'href'          => '/classes/sunrise-session',
		'glyph_icon_id' => 'icon-sun',
	),
	array(
		'time'          => '10:00',
		'date_label'    => 'TODAY',
		'day_date'      => '30 JULY 2026',
		'title'         => 'Kids Parkour 5–11',
		'subtitle'      => '45 min · parents welcome to watch',
		'location'      => 'Hackney Marshes',
		'level'         => 'All levels',
		'price'         => '£12',
		'price_label'   => 'PER CHILD',
		'book_label'    => 'BOOK',
		'href'          => '/classes/kids-parkour-5-11',
		'glyph_icon_id' => 'icon-user-group',
	),
	array(
		'time'          => '18:30',
		'date_label'    => 'TODAY',
		'day_date'      => '30 JULY 2026',
		'title'         => 'Beginners Parkour',
		'subtitle'      => '60 min · all kit provided',
		'location'      => 'Vauxhall',
		'level'         => 'Level 1 · Beginner',
		'price'         => '£15',
		'price_label'   => 'DROP-IN',
		'book_label'    => 'BOOK',
		'href'          => '/classes/beginners-parkour',
		'glyph_icon_id' => 'icon-academic-cap',
	),
	array(
		'time'          => '19:45',
		'date_label'    => 'TODAY',
		'day_date'      => '30 JULY 2026',
		'title'         => 'Open Gym',
		'subtitle'      => '90 min · unstructured, coach on floor',
		'location'      => 'Stratford East',
		'level'         => 'All levels',
		'price'         => '£8',
		'price_label'   => 'DROP-IN',
		'book_label'    => 'BOOK',
		'href'          => '/classes/open-gym',
		'glyph_icon_id' => 'icon-building-library',
	),
	array(
		'time'          => '07:15',
		'date_label'    => 'FRI',
		'day_date'      => '31 JULY 2026',
		'title'         => "Women's Session",
		'subtitle'      => '60 min · women and non-binary only',
		'location'      => 'Southbank',
		'level'         => 'All levels',
		'price'         => '£15',
		'price_label'   => 'DROP-IN',
		'book_label'    => 'BOOK',
		'href'          => '/classes/womens-session',
		'glyph_icon_id' => 'icon-heart',
	),
	array(
		'time'          => '12:00',
		'date_label'    => 'FRI',
		'day_date'      => '31 JULY 2026',
		'title'         => 'Advanced Movement',
		'subtitle'      => '75 min · by coach invitation',
		'location'      => 'Vauxhall',
		'level'         => 'Level 3 · Advanced',
		'price'         => '£15',
		'price_label'   => 'DROP-IN',
		'book_label'    => 'BOOK',
		'href'          => '/classes/advanced-movement',
		'glyph_icon_id' => 'icon-bolt',
	),
	array(
		'time'          => '09:30',
		'date_label'    => 'SAT',
		'day_date'      => '1 AUGUST 2026',
		'title'         => 'Family Session',
		'subtitle'      => '60 min · ages 5+ with an adult',
		'location'      => 'Wembley Park',
		'level'         => 'All levels',
		'price'         => '£24',
		'price_label'   => '2 PEOPLE',
		'book_label'    => 'BOOK',
		'href'          => '/classes/family-session',
		'glyph_icon_id' => 'icon-home',
	),
);

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_column_head = array(
	array(
		'label' => '',
		'class' => 'hidden sm:block w-28 shrink-0',
	),
	array(
		'label' => 'TIME',
		'class' => 'w-[112px] shrink-0',
	),
	array(
		'label' => '',
		'class' => 'hidden sm:block w-10 shrink-0',
	),
	array(
		'label' => 'SESSION',
		'class' => 'flex-1 min-w-0',
	),
	array(
		'label' => 'SITE',
		'class' => 'hidden md:block md:w-[168px] shrink-0',
	),
	array(
		'label' => 'LEVEL',
		'class' => 'hidden lg:block lg:w-[164px] shrink-0',
	),
	array(
		'label' => 'FARE',
		'class' => 'hidden sm:block w-[88px] shrink-0 text-right',
	),
);

$lp_eyebrow   = lp_section_label( (string) ( $args['eyebrow'] ?? '02 — BOOK A SESSION' ), $args['_section_number'] ?? null );
$lp_heading   = (string) ( $args['heading'] ?? 'Coming up. Book a place.' );
$lp_note      = (string) ( $args['note'] ?? 'Coach-led, no kit needed — just trainers and water. Book while the place is still open.' );
$lp_board_ttl = (string) ( $args['board_title'] ?? 'LIVE TIMETABLE' );
$lp_stamp     = (string) ( $args['stamp'] ?? 'UPDATED 09:12 · THU 30 JUL' );
$lp_foot_note = (string) ( $args['foot_note'] ?? '6 SITES · 7 DAYS · 40+ SESSIONS A WEEK' );

// The source always renders this link, falling back to its own default copy.
$lp_foot = lp_action( $args['primary_action'] ?? null ) ?? array(
	'label' => 'VIEW THE FULL TIMETABLE →',
	'href'  => '/classes',
);

// One query layer; the projection is this block's own. CPT records contribute
// `thumb` from the featured image via lp_resolve_source; manual rows may set
// thumb / glyph_icon_id directly. Spaces stay in the source data but are not
// shown — the V3 Classes board has no Spaces column.
$lp_sessions = array_map(
	static function ( array $item ): array {
		$lp_ymd = (string) ( $item['date'] ?? '' );
		$lp_day_date = (string) ( $item['day_date'] ?? '' );
		if ( '' === $lp_day_date && '' !== $lp_ymd ) {
			$lp_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $lp_ymd );
			if ( $lp_dt ) {
				$lp_day_date = strtoupper( $lp_dt->format( 'j F Y' ) );
			}
		}

		return array(
			'variant'          => 'sell',
			'show_spaces'      => false,
			'size'             => 'lg',
			'time'             => (string) ( $item['time'] ?? '' ),
			'date_label'       => (string) ( $item['date_label'] ?? '' ),
			'day_date'         => $lp_day_date,
			'title'            => (string) ( $item['title'] ?? '' ),
			'subtitle'         => (string) ( $item['subtitle'] ?? '' ),
			'location'         => (string) ( $item['location'] ?? '' ),
			'level'            => (string) ( $item['level'] ?? '' ),
			'price'            => (string) ( $item['price'] ?? '' ),
			'price_label'      => (string) ( $item['price_label'] ?? '' ),
			// A class record has no book_label — it is derivable, and a field an
			// editor has to keep in step with `sold_out` is a field that drifts.
			// The source's own defaults follow exactly this rule.
			'book_label'       => (string) ( $item['book_label'] ?? ( empty( $item['sold_out'] ) ? 'BOOK' : 'WAITLIST' ) ),
			'sold_out'         => ! empty( $item['sold_out'] ),
			'href'             => (string) ( $item['url'] ?? $item['href'] ?? '' ),
			'book_class_id'    => ! empty( $item['id'] ) ? (int) $item['id'] : 0,
			'book_preset_date' => $lp_ymd,
			'thumb'            => ! empty( $item['thumb'] ) ? (int) $item['thumb'] : 0,
			'thumb_alt'        => (string) ( $item['thumb_alt'] ?? '' ),
			'glyph_icon_id'    => (string) ( $item['glyph_icon_id'] ?? '' ),
			'glyph_svg'        => (string) ( $item['glyph_svg'] ?? '' ),
		);
	},
	lp_resolve_source( $args, lp_class_post_type(), array( 'expand' => 'sessions' ) )
);

if ( ! $lp_sessions ) {
	$lp_sessions = array_map(
		static function ( array $row ): array {
			$row['variant']     = 'sell';
			$row['show_spaces'] = false;
			$row['size']        = 'lg';
			return $row;
		},
		$lp_default_sessions
	);
}

// Live clasbpro expand returns one row per class in query order — re-sort by
// occurrence so consecutive date_label runs form real day bands.
$lp_has_ymd = false;
foreach ( $lp_sessions as $lp_session ) {
	if ( ! empty( $lp_session['book_preset_date'] ) ) {
		$lp_has_ymd = true;
		break;
	}
}
if ( $lp_has_ymd ) {
	usort(
		$lp_sessions,
		static function ( array $lp_a, array $lp_b ): int {
			return strcmp(
				( $lp_a['book_preset_date'] ?? '' ) . ( $lp_a['time'] ?? '' ),
				( $lp_b['book_preset_date'] ?? '' ) . ( $lp_b['time'] ?? '' )
			);
		}
	);
}

// Consecutive runs of the same date_label → BoardDayBand + rows (pen Vc2hk).
$lp_day_groups     = array();
$lp_show_day_bands = false;
foreach ( $lp_sessions as $lp_session ) {
	$lp_day = (string) ( $lp_session['date_label'] ?? '' );
	if ( '' !== $lp_day ) {
		$lp_show_day_bands = true;
	}
	$lp_last = $lp_day_groups ? $lp_day_groups[ count( $lp_day_groups ) - 1 ] : null;
	if ( $lp_last && $lp_last['day'] === $lp_day ) {
		$lp_day_groups[ count( $lp_day_groups ) - 1 ]['sessions'][] = $lp_session;
		continue;
	}
	$lp_day_groups[] = array(
		'day'      => $lp_day,
		'date'     => (string) ( $lp_session['day_date'] ?? '' ),
		'sessions' => array( $lp_session ),
	);
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'bg-neutral pt-[120px] px-16 pb-[124px]', $lp_spacing ); ?>" data-component="classes"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
		<div class="flex flex-col gap-5 lg:w-[640px]">
			<span class="font-label text-[12px] tracking-[0.5px] uppercase text-primary"><?php echo esc_html( $lp_eyebrow ); ?></span>
			<h2 class="font-heading text-step-3 font-semibold leading-none tracking-[-1.6px] text-neutral-content"><?php echo esc_html( $lp_heading ); ?></h2>
		</div>
		<p class="w-full lg:w-[330px] text-left lg:text-right font-label text-[11px] leading-[1.6] tracking-[0.2px] text-neutral-content/50"><?php echo esc_html( $lp_note ); ?></p>
	</div>

	<div class="mt-[56px] flex flex-col">
		<div class="flex items-center justify-between gap-3 pb-[18px] border-b border-neutral-content/20">
			<h3 class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary"><?php echo esc_html( $lp_board_ttl ); ?></h3>
			<?php
			lp_part(
				'elements/status',
				array(
					'variant' => 'live',
					'surface' => 'board',
					'label'   => $lp_stamp,
				)
			);
			?>
		</div>

		<div class="hidden sm:flex items-center gap-[28px] py-[13px] border-b border-neutral-content/10" aria-hidden="true">
			<?php foreach ( $lp_column_head as $lp_cell ) : ?>
				<span class="<?php echo lp_classes( $lp_cell['class'], 'font-label text-[10px] font-semibold uppercase tracking-[1.1px] text-neutral-content/50' ); ?>"><?php echo esc_html( $lp_cell['label'] ); ?></span>
			<?php endforeach; ?>
			<span class="w-[96px] shrink-0"></span>
		</div>

		<div data-slot="rows">
			<?php foreach ( $lp_day_groups as $lp_group ) : ?>
				<?php if ( $lp_show_day_bands && '' !== $lp_group['day'] ) : ?>
					<?php
					$lp_count = count( $lp_group['sessions'] );
					lp_part(
						'components/board-day-band',
						array(
							'day'   => $lp_group['day'],
							'date'  => $lp_group['date'],
							'count' => $lp_count . ' SESSION' . ( 1 === $lp_count ? '' : 'S' ),
							'level' => 4,
						)
					);
					?>
				<?php endif; ?>
				<?php foreach ( $lp_group['sessions'] as $lp_session ) : ?>
					<?php
					if ( $lp_show_day_bands ) {
						$lp_session['date_label'] = '';
					}
					lp_part( 'components/board-row', $lp_session );
					?>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</div>

		<div class="flex items-center justify-between gap-4 flex-wrap py-[17px] border-t border-neutral-content/10">
			<a href="<?php echo esc_url( $lp_foot['href'] ); ?>" class="font-label text-[12px] font-semibold uppercase tracking-[0.9px] text-primary hover:text-primary/70 transition-colors duration-150"><?php echo esc_html( $lp_foot['label'] ); ?></a>
			<span class="font-label text-[10px] tracking-[0.9px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_foot_note ); ?></span>
		</div>
	</div>
</section>
