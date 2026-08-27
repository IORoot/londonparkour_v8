<?php
/**
 * AgendaCard — horizontal session card on the Classes Agenda cards board.
 *
 * Ported from src/stories/Components/AgendaCard/AgendaCard.js (pen `O6Fhqs`
 * / `eEclx`). Media (badge + glyph chip) · body · yellow book rail with a
 * hairline rule above BOOK.
 *
 * `size` = featured|default — FBMCn (outer yellow ring, taller) vs BCXUX.
 * `past` = true greys the card out and removes the link (session already started).
 *
 * When `book_class_id` is set, the BOOK rail opens the shared clasbpro drawer
 * (same path as board-row sell). Optional `href` then wraps media+body in a
 * flex-row link to the class detail page (rail stays a sibling button).
 *
 * @param string $args['day']
 * @param string $args['time']
 * @param int    $args['media_id']
 * @param string $args['media_url']
 * @param string $args['media_alt']
 * @param string $args['glyph_icon_id'] Default 'glyph-flowing'.
 * @param string $args['kicker']
 * @param string $args['title']
 * @param string $args['sub']
 * @param array  $args['facts']      array of array( 'key' => …, 'value' => … ).
 * @param string $args['fare_label'] Default 'FARE'.
 * @param string $args['fare']
 * @param string $args['spaces']
 * @param string $args['cta_label']  Default 'BOOK'.
 * @param string $args['href']
 * @param int    $args['book_class_id']    Opens booking drawer when set.
 * @param string $args['book_preset_date'] Optional Y-m-d for the drawer.
 * @param string $args['size']       featured|default.
 * @param bool   $args['past']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_root = array(
	'featured' => 'relative group flex flex-col md:flex-row w-full min-h-[260px] bg-secondary no-underline text-left',
	'default'  => 'relative group flex flex-col md:flex-row w-full min-h-[176px] border border-neutral-content/10 bg-secondary no-underline text-left',
	'past'     => 'relative flex flex-col md:flex-row w-full min-h-[176px] border border-neutral-content/10 bg-secondary no-underline text-left opacity-50 grayscale pointer-events-none select-none cursor-default',
);

$lp_media = array(
	'featured' => 'relative w-full md:w-[340px] md:shrink-0 min-h-[180px] md:min-h-full bg-neutral overflow-hidden',
	'default'  => 'relative w-full md:w-[240px] md:shrink-0 min-h-[160px] md:min-h-full bg-neutral overflow-hidden',
);

$lp_body = array(
	'featured' => 'flex-1 min-w-0 flex flex-col justify-between px-7 py-6 bg-secondary border-r border-neutral-content/10 transition-colors duration-150',
	'default'  => 'flex-1 min-w-0 flex flex-col justify-between px-6 py-5 bg-secondary border-r border-neutral-content/10 transition-colors duration-150',
);

$lp_body_hover = array(
	'card'   => 'group-hover:bg-neutral',
	'detail' => 'group-hover/detail:bg-neutral',
);

$lp_media_scrim = array(
	'card'   => 'pointer-events-none absolute inset-0 bg-neutral/0 transition-colors duration-150 group-hover:bg-neutral/25',
	'detail' => 'pointer-events-none absolute inset-0 bg-neutral/0 transition-colors duration-150 group-hover/detail:bg-neutral/25',
);

$lp_rail = array(
	'featured' => 'w-full md:w-[140px] md:shrink-0 bg-primary flex flex-col justify-between gap-4 p-5',
	'default'  => 'w-full md:w-[116px] md:shrink-0 bg-primary flex flex-col justify-between gap-4 p-4',
	'past'     => 'w-full md:w-[116px] md:shrink-0 bg-neutral-content/15 flex flex-col justify-between gap-4 p-4',
);

/* BOOK rail — Concourse button inversion (primary → neutral / signal text). */
$lp_rail_book = array(
	'featured' => 'group/rail relative z-30 w-full md:w-[140px] md:shrink-0 bg-primary flex flex-col justify-between gap-4 p-5 cursor-pointer text-left border-0 transition-colors duration-150 hover:bg-neutral',
	'default'  => 'group/rail relative z-30 w-full md:w-[116px] md:shrink-0 bg-primary flex flex-col justify-between gap-4 p-4 cursor-pointer text-left border-0 transition-colors duration-150 hover:bg-neutral',
);

$lp_rail_card_hover = array(
	'featured' => 'w-full md:w-[140px] md:shrink-0 bg-primary flex flex-col justify-between gap-4 p-5 transition-colors duration-150 group-hover:bg-neutral',
	'default'  => 'w-full md:w-[116px] md:shrink-0 bg-primary flex flex-col justify-between gap-4 p-4 transition-colors duration-150 group-hover:bg-neutral',
);

$lp_top_gap = array(
	'featured' => 'flex flex-col gap-[10px]',
	'default'  => 'flex flex-col gap-1.5',
);

$lp_title = array(
	'featured' => 'font-heading text-[30px] font-semibold tracking-[-0.7px] leading-[1.02] text-neutral-content m-0',
	'default'  => 'font-heading text-[22px] font-semibold tracking-[-0.7px] leading-[1.02] text-neutral-content m-0',
	'past'     => 'font-heading text-[22px] font-semibold tracking-[-0.7px] leading-[1.02] text-neutral-content/50 m-0',
);

$lp_fare = array(
	'featured' => 'font-heading text-[42px] font-bold tracking-[-1.4px] leading-[0.9] text-primary-content',
	'default'  => 'font-heading text-[32px] font-bold tracking-[-1.4px] leading-[0.9] text-primary-content',
	'past'     => 'font-heading text-[32px] font-bold tracking-[-1.4px] leading-[0.9] text-neutral-content/50',
);

$lp_fare_book = array(
	'featured' => 'font-heading text-[42px] font-bold tracking-[-1.4px] leading-[0.9] text-primary-content group-hover/rail:text-primary',
	'default'  => 'font-heading text-[32px] font-bold tracking-[-1.4px] leading-[0.9] text-primary-content group-hover/rail:text-primary',
);

$lp_fare_card = array(
	'featured' => 'font-heading text-[42px] font-bold tracking-[-1.4px] leading-[0.9] text-primary-content group-hover:text-primary',
	'default'  => 'font-heading text-[32px] font-bold tracking-[-1.4px] leading-[0.9] text-primary-content group-hover:text-primary',
);

$lp_fact_val = array(
	'featured' => 'font-heading text-[18px] font-semibold tracking-[-0.3px] leading-[1.15] text-neutral-content',
	'default'  => 'font-heading text-[15px] font-semibold tracking-[-0.3px] leading-[1.15] text-neutral-content',
	'past'     => 'font-heading text-[15px] font-semibold tracking-[-0.3px] leading-[1.15] text-neutral-content/50',
);

$lp_glyph_box = array(
	'featured' => 'w-14 h-14 flex items-center justify-center bg-neutral/90 border border-base-300 shrink-0',
	'default'  => 'w-[42px] h-[42px] flex items-center justify-center bg-neutral/90 border border-base-300 shrink-0',
);

$lp_glyph_icon = array(
	'featured' => 'w-[30px] h-[30px] text-neutral-content',
	'default'  => 'w-[22px] h-[22px] text-neutral-content',
	'past'     => 'w-[22px] h-[22px] text-neutral-content opacity-50',
);

$lp_past     = ! empty( $args['past'] );
$lp_size_in  = ( 'featured' === ( $args['size'] ?? '' ) ) ? 'featured' : 'default';
$lp_size     = $lp_past ? 'default' : $lp_size_in;
$lp_root_key = $lp_past ? 'past' : $lp_size;
$lp_rail_key = $lp_past ? 'past' : $lp_size;
$lp_tone     = $lp_past ? 'past' : $lp_size;

$lp_day      = (string) ( $args['day'] ?? 'WED' );
$lp_time     = (string) ( $args['time'] ?? '18:30' );
$lp_kicker   = (string) ( $args['kicker'] ?? '' );
$lp_title_t  = (string) ( $args['title'] ?? 'Beginners Parkour' );
$lp_sub      = (string) ( $args['sub'] ?? '' );
$lp_facts    = is_array( $args['facts'] ?? null ) ? $args['facts'] : array();
$lp_fare_lbl = (string) ( $args['fare_label'] ?? 'FARE' );
$lp_fare_t   = (string) ( $args['fare'] ?? '£15' );
$lp_spaces   = (string) ( $args['spaces'] ?? '' );
$lp_cta      = $lp_past ? 'PASSED' : (string) ( $args['cta_label'] ?? 'BOOK' );
$lp_spaces_d = $lp_past ? 'PASSED' : $lp_spaces;
$lp_href     = $lp_past ? '' : (string) ( $args['href'] ?? '' );
$lp_glyph_id = (string) ( $args['glyph_icon_id'] ?? 'glyph-flowing' );

$lp_book_class_id    = ! $lp_past ? absint( $args['book_class_id'] ?? 0 ) : 0;
$lp_book_preset_date = (string) ( $args['book_preset_date'] ?? '' );
$lp_can_book         = $lp_book_class_id > 0 && function_exists( 'lp_class_book_button_args' );

// Whole-card link only when Storybook-style href and no drawer booking.
$lp_is_card_link = '' !== $lp_href && ! $lp_can_book;
$lp_detail_link  = '' !== $lp_href && $lp_can_book;

$lp_media_id  = ! empty( $args['media_id'] ) ? (int) $args['media_id'] : 0;
$lp_media_url = (string) ( $args['media_url'] ?? '' );
$lp_has_media = $lp_media_id || '' !== $lp_media_url;

$lp_tag = $lp_is_card_link ? 'a' : 'div';

$lp_hover_mode = '';
if ( ! $lp_past ) {
	if ( $lp_detail_link ) {
		$lp_hover_mode = 'detail';
	} elseif ( $lp_is_card_link ) {
		$lp_hover_mode = 'card';
	}
}

$lp_book_attrs = '';
if ( $lp_can_book ) {
	$lp_book = lp_class_book_button_args( $lp_book_class_id, $lp_book_preset_date, $lp_cta, 'primary' );
	foreach ( (array) ( $lp_book['data_attrs'] ?? array() ) as $lp_ak => $lp_av ) {
		$lp_book_attrs .= sprintf( ' %s="%s"', esc_attr( (string) $lp_ak ), esc_attr( (string) $lp_av ) );
	}
}
?>
<<?php echo esc_html( $lp_tag ); ?>
	<?php if ( $lp_is_card_link ) : ?>
		href="<?php echo esc_url( $lp_href ); ?>"
	<?php endif; ?>
	<?php if ( $lp_past ) : ?>
		aria-disabled="true"
		tabindex="-1"
	<?php endif; ?>
	class="<?php echo lp_classes( $lp_root[ $lp_root_key ] ); ?>"
	data-component="agenda-card"
	data-size="<?php echo esc_attr( $lp_size ); ?>"
	data-past="<?php echo $lp_past ? 'true' : 'false'; ?>"
>
	<?php if ( 'featured' === $lp_size && ! $lp_past ) : ?>
		<span class="pointer-events-none absolute inset-0 z-20 ring-2 ring-inset ring-primary" aria-hidden="true"></span>
	<?php endif; ?>

	<?php if ( $lp_detail_link ) : ?>
		<a href="<?php echo esc_url( $lp_href ); ?>" class="group/detail flex flex-col md:flex-row flex-1 min-w-0 no-underline text-left cursor-pointer">
	<?php endif; ?>

	<div class="<?php echo lp_classes( $lp_media[ $lp_size ] ); ?>">
		<?php
		if ( $lp_has_media ) {
			lp_part(
				'components/media-photo',
				array(
					'image_id'  => $lp_media_id,
					'image_url' => $lp_media_url,
					'alt'       => (string) ( $args['media_alt'] ?? '' ),
					'scrim'     => 'none',
					'size'      => 'lp_wide',
					'sizes'     => '(min-width: 768px) 340px, 100vw',
					'class'     => $lp_past ? 'opacity-60' : '',
				)
			);
		}
		?>
		<?php if ( '' !== $lp_hover_mode ) : ?>
			<span class="<?php echo lp_classes( $lp_media_scrim[ $lp_hover_mode ] ); ?>" aria-hidden="true"></span>
		<?php endif; ?>
		<div class="absolute inset-0 p-[14px] flex flex-col justify-between pointer-events-none z-[1]">
			<?php if ( $lp_past ) : ?>
				<div class="inline-flex self-start items-center gap-[6px] bg-neutral-content/25 px-2.5 py-1.5">
					<span class="font-label text-[10px] font-bold tracking-[1px] uppercase text-neutral-content/70"><?php echo esc_html( $lp_day ); ?></span>
					<span class="font-label text-[10px] font-bold tracking-[0.6px] uppercase text-neutral-content/70"><?php echo esc_html( $lp_time ); ?></span>
				</div>
			<?php else : ?>
				<div class="inline-flex self-start items-center gap-[6px] bg-primary px-2.5 py-1.5">
					<span class="font-label text-[10px] font-bold tracking-[1px] uppercase text-primary-content"><?php echo esc_html( $lp_day ); ?></span>
					<span class="font-label text-[10px] font-bold tracking-[0.6px] uppercase text-primary-content"><?php echo esc_html( $lp_time ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $lp_glyph_id ) : ?>
				<div class="<?php echo lp_classes( $lp_glyph_box[ $lp_size ] ); ?>" aria-hidden="true">
					<?php lp_icon( $lp_glyph_id, $lp_glyph_icon[ $lp_tone ] ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="<?php echo lp_classes( $lp_body[ $lp_size ], '' !== $lp_hover_mode ? $lp_body_hover[ $lp_hover_mode ] : '' ); ?>">
		<div class="<?php echo lp_classes( $lp_top_gap[ $lp_size ] ); ?>">
			<?php if ( '' !== $lp_kicker ) : ?>
				<?php if ( $lp_past ) : ?>
					<span class="font-label text-[10px] font-bold tracking-[1.2px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_kicker ); ?></span>
				<?php else : ?>
					<span class="font-label text-[10px] font-bold tracking-[1.2px] uppercase text-primary"><?php echo esc_html( $lp_kicker ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
			<h3 class="<?php echo lp_classes( $lp_title[ $lp_tone ] ); ?>"><?php echo esc_html( $lp_title_t ); ?></h3>
			<?php if ( '' !== $lp_sub ) : ?>
				<p class="font-label text-[11px] font-normal leading-[1.5] tracking-[0.2px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_sub ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $lp_facts ) : ?>
			<div class="grid grid-cols-2 md:grid-cols-4 w-full">
				<?php foreach ( $lp_facts as $lp_fact ) : ?>
					<div class="min-w-0 flex flex-col gap-[3px] pt-3 pr-3 border-t border-neutral-content/10">
						<span class="font-label text-[9px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50"><?php echo esc_html( (string) ( $lp_fact['key'] ?? '' ) ); ?></span>
						<span class="<?php echo lp_classes( $lp_fact_val[ $lp_tone ] ); ?>"><?php echo esc_html( (string) ( $lp_fact['value'] ?? '' ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $lp_detail_link ) : ?>
		</a>
	<?php endif; ?>

	<?php if ( $lp_can_book ) : ?>
		<button
			type="button"
			class="<?php echo lp_classes( $lp_rail_book[ $lp_size ] ); ?>"
			command="<?php echo esc_attr( (string) ( $lp_book['command'] ?? 'show-modal' ) ); ?>"
			commandfor="<?php echo esc_attr( (string) ( $lp_book['command_for'] ?? 'lp-booking-drawer' ) ); ?>"
			<?php echo $lp_book_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per attr above. ?>
		>
			<div class="flex flex-col gap-0.5 items-start text-left">
				<span class="font-label text-[9px] font-bold tracking-[1.1px] uppercase text-primary-content/70 group-hover/rail:text-primary/70"><?php echo esc_html( $lp_fare_lbl ); ?></span>
				<span class="<?php echo lp_classes( $lp_fare_book[ $lp_size ] ); ?>"><?php echo esc_html( $lp_fare_t ); ?></span>
				<?php if ( '' !== $lp_spaces_d ) : ?>
					<span class="font-label text-[9px] font-bold tracking-[0.9px] uppercase text-primary-content/70 group-hover/rail:text-primary/70"><?php echo esc_html( $lp_spaces_d ); ?></span>
				<?php endif; ?>
			</div>
			<div class="flex items-center justify-between w-full pt-2 border-t border-primary-content group-hover/rail:border-primary">
				<span class="font-label text-[12px] font-extrabold tracking-[1.4px] uppercase text-primary-content group-hover/rail:text-primary"><?php echo esc_html( $lp_cta ); ?></span>
				<span class="font-label text-[14px] font-extrabold text-primary-content group-hover/rail:text-primary" aria-hidden="true">→</span>
			</div>
		</button>
	<?php else : ?>
		<div class="<?php echo lp_classes( $lp_is_card_link && ! $lp_past ? $lp_rail_card_hover[ $lp_size ] : $lp_rail[ $lp_rail_key ] ); ?>">
			<div class="flex flex-col gap-0.5 items-start text-left">
				<?php if ( $lp_past ) : ?>
					<span class="font-label text-[9px] font-bold tracking-[1.1px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_fare_lbl ); ?></span>
					<span class="<?php echo lp_classes( $lp_fare['past'] ); ?>"><?php echo esc_html( $lp_fare_t ); ?></span>
					<span class="font-label text-[9px] font-bold tracking-[0.9px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_spaces_d ); ?></span>
				<?php elseif ( $lp_is_card_link ) : ?>
					<span class="font-label text-[9px] font-bold tracking-[1.1px] uppercase text-primary-content/70 group-hover:text-primary/70"><?php echo esc_html( $lp_fare_lbl ); ?></span>
					<span class="<?php echo lp_classes( $lp_fare_card[ $lp_size ] ); ?>"><?php echo esc_html( $lp_fare_t ); ?></span>
					<?php if ( '' !== $lp_spaces_d ) : ?>
						<span class="font-label text-[9px] font-bold tracking-[0.9px] uppercase text-primary-content/70 group-hover:text-primary/70"><?php echo esc_html( $lp_spaces_d ); ?></span>
					<?php endif; ?>
				<?php else : ?>
					<span class="font-label text-[9px] font-bold tracking-[1.1px] uppercase text-primary-content/70"><?php echo esc_html( $lp_fare_lbl ); ?></span>
					<span class="<?php echo lp_classes( $lp_fare[ $lp_size ] ); ?>"><?php echo esc_html( $lp_fare_t ); ?></span>
					<?php if ( '' !== $lp_spaces_d ) : ?>
						<span class="font-label text-[9px] font-bold tracking-[0.9px] uppercase text-primary-content/70"><?php echo esc_html( $lp_spaces_d ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php if ( $lp_past ) : ?>
				<div class="flex items-center justify-between w-full pt-2 border-t border-neutral-content/25">
					<span class="font-label text-[12px] font-extrabold tracking-[1.4px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_cta ); ?></span>
					<span class="font-label text-[14px] font-extrabold text-neutral-content/50" aria-hidden="true">–</span>
				</div>
			<?php elseif ( $lp_is_card_link ) : ?>
				<div class="flex items-center justify-between w-full pt-2 border-t border-primary-content group-hover:border-primary">
					<span class="font-label text-[12px] font-extrabold tracking-[1.4px] uppercase text-primary-content group-hover:text-primary"><?php echo esc_html( $lp_cta ); ?></span>
					<span class="font-label text-[14px] font-extrabold text-primary-content group-hover:text-primary" aria-hidden="true">→</span>
				</div>
			<?php else : ?>
				<div class="flex items-center justify-between w-full pt-2 border-t border-primary-content">
					<span class="font-label text-[12px] font-extrabold tracking-[1.4px] uppercase text-primary-content"><?php echo esc_html( $lp_cta ); ?></span>
					<span class="font-label text-[14px] font-extrabold text-primary-content" aria-hidden="true">→</span>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</<?php echo esc_html( $lp_tag ); ?>>
