<?php
/**
 * Classes — "03 — BOOK A SESSION": the departure-board booking section.
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
 * COLUMN_HEAD stays a hardcoded array (the plan is explicit that it is not a
 * field): it carries layout classes as well as labels, and it is hand-coupled
 * to board-row.php's own column geometry, which that part does not export.
 * If board-row's widths or breakpoints change, update these too:
 *   TIME   sm:w-[92px]
 *   SITE   sm:w-[196px], hidden below md
 *   LEVEL  sm:w-[150px], hidden below lg
 *   SPACES sm:min-w-[70px]
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
		'time'        => '07:00',
		'date_label'  => 'TODAY',
		'title'       => 'Sunrise Session',
		'subtitle'    => '60 min · outdoor, rain or shine',
		'location'    => 'Peckham Rye',
		'level'       => 'Level 2 · Improver',
		'spaces'      => 'FULL',
		'price'       => '£15',
		'price_label' => 'DROP-IN',
		'book_label'  => 'WAITLIST',
		'sold_out'    => true,
	),
	array(
		'time'        => '10:00',
		'date_label'  => 'TODAY',
		'title'       => 'Kids Parkour 5–11',
		'subtitle'    => '45 min · parents welcome to watch',
		'location'    => 'Hackney Marshes',
		'level'       => 'All levels',
		'spaces'      => '2 LEFT',
		'price'       => '£12',
		'price_label' => 'PER CHILD',
		'book_label'  => 'BOOK',
	),
	array(
		'time'        => '18:30',
		'date_label'  => 'TODAY',
		'title'       => 'Beginners Parkour',
		'subtitle'    => '60 min · all kit provided',
		'location'    => 'Vauxhall',
		'level'       => 'Level 1 · Beginner',
		'spaces'      => '4 LEFT',
		'price'       => '£15',
		'price_label' => 'DROP-IN',
		'book_label'  => 'BOOK',
	),
	array(
		'time'        => '19:45',
		'date_label'  => 'TODAY',
		'title'       => 'Open Gym',
		'subtitle'    => '90 min · unstructured, coach on floor',
		'location'    => 'Stratford East',
		'level'       => 'All levels',
		'spaces'      => '11 LEFT',
		'price'       => '£8',
		'price_label' => 'DROP-IN',
		'book_label'  => 'BOOK',
	),
	array(
		'time'        => '07:15',
		'date_label'  => 'FRI',
		'title'       => "Women's Session",
		'subtitle'    => '60 min · women and non-binary only',
		'location'    => 'Southbank',
		'level'       => 'All levels',
		'spaces'      => '6 LEFT',
		'price'       => '£15',
		'price_label' => 'DROP-IN',
		'book_label'  => 'BOOK',
	),
	array(
		'time'        => '12:00',
		'date_label'  => 'FRI',
		'title'       => 'Advanced Movement',
		'subtitle'    => '75 min · by coach invitation',
		'location'    => 'Vauxhall',
		'level'       => 'Level 3 · Advanced',
		'spaces'      => '3 LEFT',
		'price'       => '£15',
		'price_label' => 'DROP-IN',
		'book_label'  => 'BOOK',
	),
	array(
		'time'        => '09:30',
		'date_label'  => 'SAT',
		'title'       => 'Family Session',
		'subtitle'    => '60 min · ages 5+ with an adult',
		'location'    => 'Wembley Park',
		'level'       => 'All levels',
		'spaces'      => '9 LEFT',
		'price'       => '£24',
		'price_label' => '2 PEOPLE',
		'book_label'  => 'BOOK',
	),
);

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_column_head = array(
	array(
		'label' => 'TIME',
		'class' => 'w-[92px] shrink-0',
	),
	array(
		'label' => 'SESSION',
		'class' => 'flex-1 min-w-0',
	),
	array(
		'label' => 'SITE',
		'class' => 'hidden md:block md:w-[196px] shrink-0',
	),
	array(
		'label' => 'LEVEL',
		'class' => 'hidden lg:block lg:w-[150px] shrink-0',
	),
	array(
		'label' => 'FARE',
		'class' => 'hidden sm:block w-[76px] shrink-0 text-right',
	),
	array(
		'label' => 'SPACES',
		'class' => 'w-[70px] shrink-0 text-right',
	),
);

$lp_eyebrow   = (string) ( $args['eyebrow'] ?? '03 — BOOK A SESSION' );
$lp_heading   = (string) ( $args['heading'] ?? 'This week. Book a seat.' );
$lp_note      = (string) ( $args['note'] ?? "Coach-led, capped at 12, all kit provided. If your first session isn't for you we refund it — no questions." );
$lp_board_ttl = (string) ( $args['board_title'] ?? 'LIVE TIMETABLE' );
$lp_stamp     = (string) ( $args['stamp'] ?? 'UPDATED 09:12 · THU 30 JUL' );
$lp_foot_note = (string) ( $args['foot_note'] ?? '6 SITES · 7 DAYS · 40+ SESSIONS A WEEK' );

// The source always renders this link, falling back to its own default copy.
$lp_foot = lp_action( $args['primary_action'] ?? null ) ?? array(
	'label' => 'VIEW THE FULL TIMETABLE →',
	'href'  => '#',
);

// One query layer; the projection is this block's own. A class record gives
// eleven fields here — the Hero board reads four of the same names.
$lp_sessions = array_map(
	static function ( array $item ): array {
		return array(
			'variant'     => 'sell',
			'time'        => (string) ( $item['time'] ?? '' ),
			'date_label'  => (string) ( $item['date_label'] ?? '' ),
			'title'       => (string) ( $item['title'] ?? '' ),
			'subtitle'    => (string) ( $item['subtitle'] ?? '' ),
			'location'    => (string) ( $item['location'] ?? '' ),
			'level'       => (string) ( $item['level'] ?? '' ),
			'spaces'      => (string) ( $item['spaces'] ?? '' ),
			'price'       => (string) ( $item['price'] ?? '' ),
			'price_label' => (string) ( $item['price_label'] ?? '' ),
			// A class record has no book_label — it is derivable, and a field an
			// editor has to keep in step with `sold_out` is a field that drifts.
			// The source's own defaults follow exactly this rule.
			'book_label'  => (string) ( $item['book_label'] ?? ( empty( $item['sold_out'] ) ? 'BOOK' : 'WAITLIST' ) ),
			'sold_out'    => ! empty( $item['sold_out'] ),
			'book_href'   => (string) ( $item['url'] ?? '' ),
		);
	},
	lp_resolve_source( $args, 'lp_class', array( 'expand' => 'sessions' ) )
);

if ( ! $lp_sessions ) {
	$lp_sessions = array_map(
		static function ( array $row ): array {
			$row['variant'] = 'sell';
			return $row;
		},
		$lp_default_sessions
	);
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'bg-neutral pt-[96px] px-16 pb-[100px]', $lp_spacing ); ?>" data-component="classes"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
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

		<div class="hidden sm:flex items-center gap-[24px] py-[13px] border-b border-neutral-content/10" aria-hidden="true">
			<?php foreach ( $lp_column_head as $lp_cell ) : ?>
				<span class="<?php echo lp_classes( $lp_cell['class'], 'font-label text-[10px] font-semibold uppercase tracking-[1.1px] text-neutral-content/50' ); ?>"><?php echo esc_html( $lp_cell['label'] ); ?></span>
			<?php endforeach; ?>
			<span class="w-[84px] shrink-0"></span>
		</div>

		<div data-slot="rows">
			<?php foreach ( $lp_sessions as $lp_session ) : ?>
				<?php lp_part( 'components/board-row', $lp_session ); ?>
			<?php endforeach; ?>
		</div>

		<div class="flex items-center justify-between gap-4 flex-wrap py-[17px] border-t border-neutral-content/10">
			<a href="<?php echo esc_url( $lp_foot['href'] ); ?>" class="font-label text-[12px] font-semibold uppercase tracking-[0.9px] text-primary hover:text-primary/70 transition-colors duration-150"><?php echo esc_html( $lp_foot['label'] ); ?></a>
			<span class="font-label text-[10px] tracking-[0.9px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_foot_note ); ?></span>
		</div>
	</div>
</section>
