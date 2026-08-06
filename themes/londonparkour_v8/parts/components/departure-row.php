<?php
/**
 * DepartureRow — compact 3-column "next departures" row: time, class +
 * location, spaces left. No chevron, no background of its own.
 *
 * Ported from src/stories/Components/DepartureRow/DepartureRow.js.
 *
 * The source node is transparent — it is always nested inside another dark
 * surface and inherits that ground, which is why its text sits in the fixed
 * neutral-content family rather than a themed token.
 *
 * No hover design node exists for this row, so a static row gets no hover
 * treatment at all. Only `href` — which makes it a real link — earns the
 * minimal affordance, so a clickable row is not silently inert.
 *
 * @param string $args['time']      Default '18:30'.
 * @param string $args['title']
 * @param string $args['location']
 * @param string $args['spaces']    Default '4 LEFT'.
 * @param bool   $args['sold_out']  Drops the signal colour off `spaces`.
 * @param string $args['href']      Renders the whole row as one focusable <a>.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_root_base = 'group relative flex items-center gap-[12px] sm:gap-[16px] w-full py-[12px] border-b border-neutral-content/10 transition-colors duration-150 no-underline text-left';

$lp_root_interactive = 'cursor-pointer hover:bg-neutral-content/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';

// A full session drops the signal colour so it stops reading as available at a
// glance. Muted, never hidden — the spaces text always carries the meaning.
$lp_spaces_tones = array(
	'available' => 'text-primary',
	'sold_out'  => 'text-neutral-content/50',
);

$lp_time     = (string) ( $args['time'] ?? '18:30' );
$lp_title    = (string) ( $args['title'] ?? 'Beginners Parkour' );
$lp_location = (string) ( $args['location'] ?? 'Vauxhall' );
$lp_spaces   = (string) ( $args['spaces'] ?? '4 LEFT' );
$lp_href     = (string) ( $args['href'] ?? '' );
$lp_is_link  = '' !== $lp_href;

$lp_spaces_tone = empty( $args['sold_out'] ) ? $lp_spaces_tones['available'] : $lp_spaces_tones['sold_out'];
$lp_root        = $lp_is_link ? $lp_root_base . ' ' . $lp_root_interactive : $lp_root_base;
?>
<?php if ( $lp_is_link ) : ?>
<a class="<?php echo esc_attr( $lp_root ); ?>" data-component="departure-row" href="<?php echo esc_url( $lp_href ); ?>">
<?php else : ?>
<div class="<?php echo esc_attr( $lp_root ); ?>" data-component="departure-row">
<?php endif; ?>
	<span class="font-heading text-[16px] font-semibold tracking-[-0.3px] text-neutral-content shrink-0 w-[52px] sm:w-[60px]"><?php echo esc_html( $lp_time ); ?></span>
	<div class="flex-1 min-w-0 flex flex-col gap-[3px]">
		<p class="font-heading text-[14px] font-medium text-neutral-content truncate"><?php echo esc_html( $lp_title ); ?></p>
		<p class="font-label text-[10px] font-normal tracking-[0.5px] text-neutral-content/50 truncate"><?php echo esc_html( $lp_location ); ?></p>
	</div>
	<span class="<?php echo lp_classes( 'font-label text-[10px] font-semibold tracking-[0.8px] uppercase', $lp_spaces_tone, 'shrink-0' ); ?>"><?php echo esc_html( $lp_spaces ); ?></span>
<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
