<?php
/**
 * BoardDayBand — the divider band grouping a run of board rows under a day.
 *
 * Ported from src/stories/Components/BoardDayBand/BoardDayBand.js (pen `BJjX7`).
 *
 * Sits at the full fixed `neutral` tone, not the recessed tone the rows rest
 * at — bands read as slightly elevated above the unlit rows they introduce.
 * Day name is the heading face at 22px so the split reads as a section break
 * without jumping to the agenda-card 42px day headers.
 * Static: no hover node exists and none is invented; it groups rows, it does
 * not navigate.
 *
 * A11y: a real heading element carrying one combined aria-label, so assistive
 * tech announces "MON 13 JULY 2026, 3 SESSIONS" as a single group heading when
 * scanning by headings. The visible spans are aria-hidden to avoid a
 * double read.
 *
 * @param string $args['day']   Default 'MON'.
 * @param string $args['date']  Default '13 JULY 2026'.
 * @param string $args['count'] Default '3 SESSIONS'.
 * @param int    $args['level'] 2|3|4 — the heading level. Default 3.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_tags = array(
	2 => 'h2',
	3 => 'h3',
	4 => 'h4',
);

$lp_day   = (string) ( $args['day'] ?? 'MON' );
$lp_date  = (string) ( $args['date'] ?? '13 JULY 2026' );
$lp_count = (string) ( $args['count'] ?? '3 SESSIONS' );
$lp_tag   = $lp_tags[ (int) ( $args['level'] ?? 3 ) ] ?? $lp_tags[3];

$lp_label = implode( ' ', array_filter( array( $lp_day, $lp_date ) ) );
if ( '' !== $lp_count ) {
	$lp_label .= ', ' . $lp_count;
}
?>
<<?php echo esc_html( $lp_tag ); ?>
	class="w-full flex items-center justify-between gap-3 py-[20px] px-[16px] sm:px-[28px] bg-neutral border-t border-b border-neutral-content/10"
	data-component="board-day-band"
	aria-label="<?php echo esc_attr( $lp_label ); ?>"
>
	<div class="flex items-end gap-[14px]" aria-hidden="true">
		<span class="font-heading text-[22px] font-semibold tracking-[-0.6px] uppercase text-neutral-content leading-none"><?php echo esc_html( $lp_day ); ?></span>
		<span class="font-label text-[11px] font-normal tracking-[0.6px] uppercase text-neutral-content/50 pb-[2px]"><?php echo esc_html( $lp_date ); ?></span>
	</div>
	<span class="font-label text-[11px] font-semibold tracking-[1px] uppercase text-neutral-content/50" aria-hidden="true"><?php echo esc_html( $lp_count ); ?></span>
</<?php echo esc_html( $lp_tag ); ?>>
