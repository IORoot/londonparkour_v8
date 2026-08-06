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
 * @param string $args['spaces']
 * @param string $args['price']            sell only.
 * @param string $args['price_label']      sell only.
 * @param string $args['book_label']       sell only. Default 'BOOK'.
 * @param bool   $args['sold_out']
 * @param string $args['tone']             available|sold_out|watched|new|now_playing. Overrides sold_out.
 * @param string $args['href']             Renders the row as one focusable <a>. Ignored by `sell`.
 * @param string $args['book_href']        sell only.
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
$lp_sold_out    = ! empty( $args['sold_out'] );

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
		<div class="flex items-baseline gap-2 sm:w-[92px] sm:shrink-0 sm:flex-col sm:items-start sm:gap-[3px]">
			<span class="font-heading text-[20px] font-semibold tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_time ); ?></span>
			<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_date_label ); ?></span>
		</div>
		<div class="flex-1 min-w-0 flex flex-col gap-[5px]">
			<p class="font-heading text-[17px] font-medium tracking-[-0.2px] text-neutral-content truncate"><?php echo esc_html( $lp_title ); ?></p>
			<p class="font-label text-[11px] font-normal tracking-[0.2px] text-neutral-content/50 truncate"><?php echo esc_html( $lp_subtitle ); ?></p>
		</div>
	</div>
	<div class="flex items-center justify-between gap-3 sm:contents">
		<div class="hidden md:flex items-center gap-2 sm:w-[196px] sm:shrink-0">
			<span class="text-neutral-content/50 group-hover:text-neutral-content/80 transition-colors duration-150" aria-hidden="true"><?php lp_icon( $lp_location_icon, 'w-3 h-3' ); ?></span>
			<span class="font-label text-[12px] font-normal tracking-[0.2px] text-neutral-content/80 group-hover:text-neutral-content transition-colors duration-150 truncate"><?php echo esc_html( $lp_location ); ?></span>
		</div>
		<div class="hidden lg:flex items-center gap-2 sm:w-[150px] sm:shrink-0">
			<span class="text-neutral-content/80" aria-hidden="true"><?php lp_icon( $lp_level_icon, 'w-3.5 h-3.5' ); ?></span>
			<span class="font-label text-[11px] font-normal tracking-[0.2px] text-neutral-content/80 truncate"><?php echo esc_html( $lp_level ); ?></span>
		</div>
		<?php if ( $lp_is_sell ) : ?>
			<div class="hidden sm:flex flex-col items-end gap-[2px] w-[76px] sm:shrink-0">
				<span class="font-heading text-[19px] font-semibold tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_price ); ?></span>
				<span class="font-label text-[9px] font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_price_lbl ); ?></span>
			</div>
		<?php endif; ?>
		<span class="<?php echo lp_classes( 'font-label text-[11px] font-semibold tracking-[0.8px] uppercase', $lp_spaces_tone, 'shrink-0 sm:min-w-[70px] sm:text-right' ); ?>"><?php echo esc_html( $lp_spaces ); ?></span>
		<?php if ( $lp_is_sell ) : ?>
			<span class="shrink-0">
				<?php
				lp_part(
					'elements/button',
					array(
						'variant' => $lp_sold_out ? 'inverse' : 'primary',
						'label'   => $lp_book_label,
						'href'    => $args['book_href'] ?? '',
					)
				);
				?>
			</span>
		<?php else : ?>
			<?php lp_part( 'elements/chevron', array( 'variant' => 'board_row' ) ); ?>
		<?php endif; ?>
	</div>
<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
