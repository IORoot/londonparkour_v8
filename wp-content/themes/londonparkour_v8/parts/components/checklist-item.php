<?php
/**
 * ChecklistItem — a tick (or a numeral) + a line of text.
 *
 * Ported from src/stories/Components/ChecklistItem/ChecklistItem.js.
 *
 * No list markup here on purpose: the source is a set of loose rows, not a
 * real <ul>. If the caller's context is a semantic list, the PARENT renders
 * the <ul>/<li> (or <ol>/<li> when using `index`) around these rows.
 *
 * A11y: the tick is decorative and aria-hidden — the text carries the meaning.
 * The numeral is NOT: it IS the step order, so it is never aria-hidden and
 * never a CSS ::before counter(), either of which would erase the order for a
 * reader that only sees the DOM.
 *
 * When `index` is set, markup follows Classes/Class Detail "What To Expect"
 * (pen `pqD25`): 24px heading numeral, 14px label copy, full-width hairline
 * row. Omitting `index` keeps the tick row for Key Takeaways callers.
 *
 * @param string $args['text']  The row copy.
 * @param string $args['index'] Any truthy value swaps the tick for that numeral;
 *                              the caller formats it ('1', '01'), as in list-row.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_numeral_class = 'shrink-0 w-[44px] font-heading text-[24px] font-semibold leading-none tracking-[-0.8px] text-base-content/40';

$lp_text  = (string) ( $args['text'] ?? 'Start with your feet hitting the wall first to control impact.' );
$lp_index = (string) ( $args['index'] ?? '' );
?>
<?php if ( '' !== $lp_index ) : ?>
	<div class="flex items-center gap-[28px] w-full border-b border-base-300 py-[20px]" data-component="checklist-item" data-variant="expect">
		<span class="<?php echo esc_attr( $lp_numeral_class ); ?>"><?php echo esc_html( $lp_index ); ?></span>
		<p class="m-0 flex-1 min-w-0 font-label text-[14px] font-normal leading-[1.6] tracking-[0.1px] text-base-content/80"><?php echo esc_html( $lp_text ); ?></p>
	</div>
<?php else : ?>
	<div class="flex items-start gap-3" data-component="checklist-item">
		<span class="shrink-0 text-base-content" aria-hidden="true"><?php lp_icon( 'icon-check', 'w-[13px] h-[13px]' ); ?></span>
		<p class="m-0 font-body text-[12px] font-normal leading-[1.4] tracking-[0.1px] text-base-content/70"><?php echo esc_html( $lp_text ); ?></p>
	</div>
<?php endif; ?>
