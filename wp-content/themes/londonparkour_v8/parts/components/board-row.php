<?php
/**
 * BoardRow — the full departure-board row: time, session, site, level,
 * optional fare, spaces, and a trailing chevron or BOOK button.
 *
 * Ported from src/stories/Components/BoardRow/BoardRow.js.
 *
 * DELIBERATE DEPARTURE: the source vendors an inline <svg> pin path as the
 * default location mark. Per docs/CONSOLIDATION.md §2's blanket rule every
 * raw <svg> becomes an lp_icon() sprite call, so the default is the sprite's
 * `icon-map-pin` at the same w-3 h-3; `location_icon_id` still overrides it.
 *
 * V3 Classes sell geometry (`bhjl3`): optional 56×56 thumb + 28×28 glyph,
 * SITE 150 / LEVEL 146, and `show_spaces => false` (no Spaces column). Defaults
 * keep Spaces on so agenda / tutorial / class-single callers stay unchanged.
 *
 * The trailing chevron is parts/elements/chevron.php variant `board_row`
 * (§2b). The sell variant's BOOK control goes through elements/button.php —
 * `inverse`, not `ghost`, when sold out: ghost is page-ground only and goes
 * invisible on this fixed dark band in both light themes.
 *
 * Sold-out rows drop the signal colour so a full session does not read as
 * available at a glance. Muted, not hidden — `spaces` stays text, never
 * colour alone.
 *
 * @param string $args['variant']          default|sell. `sell` swaps the chevron for a BOOK button.
 * @param string $args['time']
 * @param string $args['date_label']
 * @param string $args['title']
 * @param string $args['subtitle']
 * @param string $args['location']
 * @param string $args['level']
 * @param string $args['level_icon_id']    Default 'icon-level-beginner'.
 * @param string $args['location_icon_id'] Overrides the default map pin.
 * @param bool   $args['show_spaces']      Default true. Pass false for the Classes board.
 * @param string $args['spaces']
 * @param int    $args['thumb']            Attachment ID for the optional 56×56 thumb.
 * @param string $args['thumb_alt']        Alt for the thumb; default '' (decorative).
 * @param string $args['glyph_icon_id']    Optional 28×28 row glyph sprite id (e.g. 'icon-sun').
 * @param string $args['glyph_svg']        Optional inline SVG (clasbpro Card icon). Wins over glyph_icon_id.
 * @param string $args['price']            sell only.
 * @param string $args['price_label']      sell only.
 * @param string $args['book_label']       sell only. Default 'BOOK'.
 * @param bool   $args['sold_out']
 * @param string $args['tone']             available|sold_out|watched|new|now_playing. Overrides sold_out.
 * @param string $args['href']             default: whole-row link. sell: MORE DETAILS
 *                                         under the subtitle (class page); row stays a div.
 * @param string $args['detail_label']     sell only. Default 'MORE DETAILS'.
 * @param string $args['book_href']        sell only. Ignored when book_class_id is set.
 * @param int    $args['book_class_id']    sell only. Opens the booking drawer (no href).
 * @param string $args['book_preset_date'] sell only. Optional Y-m-d for the drawer.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_root_base = 'group relative flex flex-col gap-[10px] sm:flex-row sm:items-center sm:gap-[24px] w-full py-[14px] sm:py-[18px] px-[16px] sm:px-[28px] bg-secondary hover:bg-neutral border-b border-neutral-content/10 transition-colors duration-150 no-underline text-left';

$lp_root_interactive = 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';

$lp_spaces_tones = array(
	'available'   => 'text-primary',
	'sold_out'    => 'text-neutral-content/50',
	'watched'     => 'text-neutral-content/50',
	'new'         => 'text-primary',
	'now_playing' => 'text-primary',
);

$lp_variant     = (string) ( $args['variant'] ?? 'default' );
$lp_is_sell     = 'sell' === $lp_variant;
$lp_href        = (string) ( $args['href'] ?? '' );
$lp_is_link     = ! $lp_is_sell && '' !== $lp_href;
$lp_detail_href = $lp_is_sell ? $lp_href : '';
$lp_detail_lbl  = (string) ( $args['detail_label'] ?? 'MORE DETAILS' );
$lp_sold_out    = ! empty( $args['sold_out'] );
$lp_show_spaces = ! isset( $args['show_spaces'] ) || (bool) $args['show_spaces'];

$lp_time       = (string) ( $args['time'] ?? '18:30' );
$lp_date_label = (string) ( $args['date_label'] ?? 'TODAY' );
$lp_title      = (string) ( $args['title'] ?? 'Beginners Parkour' );
$lp_subtitle   = (string) ( $args['subtitle'] ?? '60 min · all kit provided' );
$lp_location   = (string) ( $args['location'] ?? 'Vauxhall' );
$lp_level      = (string) ( $args['level'] ?? 'Level 1 · Beginner' );
$lp_spaces     = (string) ( $args['spaces'] ?? '4 LEFT' );
$lp_price      = (string) ( $args['price'] ?? '£15' );
$lp_price_lbl  = (string) ( $args['price_label'] ?? 'DROP-IN' );
$lp_book_label = (string) ( $args['book_label'] ?? 'BOOK' );

$lp_level_icon    = (string) ( $args['level_icon_id'] ?? 'icon-level-beginner' );
$lp_location_icon = (string) ( $args['location_icon_id'] ?? 'icon-map-pin' );
$lp_glyph_icon    = (string) ( $args['glyph_icon_id'] ?? '' );
$lp_glyph_svg     = (string) ( $args['glyph_svg'] ?? '' );
$lp_thumb         = ! empty( $args['thumb'] ) ? (int) $args['thumb'] : 0;
$lp_thumb_alt     = (string) ( $args['thumb_alt'] ?? '' );

// Sell rows always show a glyph (pen `b6g5C`). Prefer ACF Card icon SVG, then
// sprite id, then glyph-balancing.
if ( $lp_is_sell && '' === $lp_glyph_svg && '' === $lp_glyph_icon ) {
	$lp_glyph_icon = 'glyph-balancing';
}

// `tone` is additive: with none passed, sold_out resolves exactly as before.
$lp_tone_key = (string) ( $args['tone'] ?? '' );
if ( ! isset( $lp_spaces_tones[ $lp_tone_key ] ) ) {
	$lp_tone_key = $lp_sold_out ? 'sold_out' : 'available';
}
$lp_spaces_tone = $lp_spaces_tones[ $lp_tone_key ];

$lp_root = $lp_is_link ? $lp_root_base . ' ' . $lp_root_interactive : $lp_root_base;
?>
<?php if ( $lp_is_link ) : ?>
<a class="<?php echo esc_attr( $lp_root ); ?>" data-component="board-row" data-variant="<?php echo esc_attr( $lp_variant ); ?>" href="<?php echo esc_url( $lp_href ); ?>">
<?php else : ?>
<div class="<?php echo esc_attr( $lp_root ); ?>" data-component="board-row" data-variant="<?php echo esc_attr( $lp_variant ); ?>">
<?php endif; ?>
	<div class="flex items-center justify-between gap-3 sm:contents">
		<?php if ( $lp_thumb ) : ?>
			<div class="hidden sm:block w-14 h-14 shrink-0 overflow-hidden bg-neutral" data-slot="thumb">
				<?php
				lp_part(
					'components/media-photo',
					array(
						'image_id' => $lp_thumb,
						'alt'      => $lp_thumb_alt,
						'scrim'    => 'none',
						'layout'   => 'none',
						'class'    => 'w-full h-full object-cover',
						'size'     => 'lp_thumb',
						'sizes'    => '56px',
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="flex items-baseline gap-2 sm:w-[92px] sm:shrink-0 sm:flex-col sm:items-start sm:gap-[3px]">
			<span class="font-heading text-[20px] font-semibold tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_time ); ?></span>
			<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_date_label ); ?></span>
		</div>
		<?php if ( '' !== $lp_glyph_svg || '' !== $lp_glyph_icon ) : ?>
			<span class="hidden sm:inline-flex w-7 h-7 shrink-0 text-neutral-content items-center justify-center" aria-hidden="true" data-slot="glyph">
				<?php
				if ( '' !== $lp_glyph_svg && function_exists( 'lp_inline_svg' ) ) {
					lp_inline_svg( $lp_glyph_svg, 'w-7 h-7' );
				} else {
					lp_icon( $lp_glyph_icon, 'w-7 h-7' );
				}
				?>
			</span>
		<?php endif; ?>
		<div class="flex-1 min-w-0 flex flex-col gap-[5px]">
			<p class="font-heading text-[17px] font-medium tracking-[-0.2px] text-neutral-content truncate"><?php echo esc_html( $lp_title ); ?></p>
			<p class="font-label text-[11px] font-normal tracking-[0.2px] text-neutral-content/50 truncate"><?php echo esc_html( $lp_subtitle ); ?></p>
			<?php
			if ( '' !== $lp_detail_href ) {
				lp_part(
					'elements/text-link',
					array(
						'label'   => $lp_detail_lbl,
						'href'    => $lp_detail_href,
						'variant' => 'board_compact',
						'class'   => 'w-fit',
					)
				);
			}
			?>
		</div>
	</div>
	<div class="flex items-center justify-between gap-3 sm:contents">
		<div class="hidden md:flex items-center gap-2 sm:w-[150px] sm:shrink-0">
			<span class="text-neutral-content/50 group-hover:text-neutral-content/80 transition-colors duration-150" aria-hidden="true"><?php lp_icon( $lp_location_icon, 'w-3 h-3' ); ?></span>
			<span class="font-label text-[12px] font-normal tracking-[0.2px] text-neutral-content/80 group-hover:text-neutral-content transition-colors duration-150 truncate"><?php echo esc_html( $lp_location ); ?></span>
		</div>
		<div class="hidden lg:flex items-center gap-2 sm:w-[146px] sm:shrink-0">
			<span class="text-neutral-content/80" aria-hidden="true"><?php lp_icon( $lp_level_icon, 'w-3.5 h-3.5' ); ?></span>
			<span class="font-label text-[11px] font-normal tracking-[0.2px] text-neutral-content/80 truncate"><?php echo esc_html( $lp_level ); ?></span>
		</div>
		<?php if ( $lp_is_sell ) : ?>
			<div class="hidden sm:flex flex-col items-end gap-[2px] w-[76px] sm:shrink-0">
				<span class="font-heading text-[19px] font-semibold tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_price ); ?></span>
				<span class="font-label text-[9px] font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_price_lbl ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( $lp_show_spaces ) : ?>
			<span class="<?php echo lp_classes( 'font-label text-[11px] font-semibold tracking-[0.8px] uppercase', $lp_spaces_tone, 'shrink-0 sm:min-w-[70px] sm:text-right' ); ?>"><?php echo esc_html( $lp_spaces ); ?></span>
		<?php endif; ?>
		<?php if ( $lp_is_sell ) : ?>
			<span class="shrink-0 w-[84px] flex justify-end">
				<?php
				$lp_book_class_id = ! empty( $args['book_class_id'] ) ? (int) $args['book_class_id'] : 0;
				if ( $lp_book_class_id && function_exists( 'lp_class_book_button_args' ) ) {
					lp_part(
						'elements/button',
						lp_class_book_button_args(
							$lp_book_class_id,
							(string) ( $args['book_preset_date'] ?? '' ),
							$lp_book_label,
							$lp_sold_out ? 'inverse' : 'primary'
						)
					);
				} else {
					lp_part(
						'elements/button',
						array(
							'variant' => $lp_sold_out ? 'inverse' : 'primary',
							'label'   => $lp_book_label,
							'href'    => $args['book_href'] ?? '',
						)
					);
				}
				?>
			</span>
		<?php else : ?>
			<?php lp_part( 'elements/chevron', array( 'variant' => 'board_row' ) ); ?>
		<?php endif; ?>
	</div>
<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
