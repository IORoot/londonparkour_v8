<?php
/**
 * FaqItem — ordinal + question + chevron over an answer paragraph.
 *
 * Ported from src/stories/Components/FaqItem/FaqItem.js.
 *
 * Built on daisyUI's collapse + collapse-arrow using the native
 * <details>/<summary> variant — never the checkbox variant, never a
 * hand-rolled click handler — so open/close state, keyboard operation and
 * screen-reader state come for free. `collapse-arrow` draws the chevron as a
 * CSS pseudo-element tracking the title's text colour, so the source's
 * separate chevron layer is intentionally NOT reproduced as markup (and this
 * is why it does not use parts/elements/chevron.php).
 *
 * Both muted roles resolve to one token per ground rather than encoding a
 * two-tier muted system from what reads as incidental sampling variance.
 *
 * @param string $args['index']        Ordinal. Default '01'.
 * @param string $args['question']
 * @param string $args['answer']
 * @param string $args['surface']      page|board|accent. Default 'page'.
 * @param bool   $args['default_open'] Renders the <details> open.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'page'   => array(
		'index'    => 'text-base-content/65',
		'question' => 'text-base-content',
		'answer'   => 'text-base-content/65',
	),
	'board'  => array(
		'index'    => 'text-neutral-content/50',
		'question' => 'text-neutral-content',
		'answer'   => 'text-neutral-content/50',
	),
	'accent' => array(
		'index'    => 'text-accent-content/70',
		'question' => 'text-accent-content',
		'answer'   => 'text-accent-content/70',
	),
);

$lp_surface  = (string) ( $args['surface'] ?? 'page' );
$lp_tone     = $lp_surfaces[ $lp_surface ] ?? $lp_surfaces['page'];
$lp_index    = (string) ( $args['index'] ?? '01' );
$lp_question = (string) ( $args['question'] ?? 'Do I need any experience?' );
$lp_answer   = (string) ( $args['answer'] ?? 'No. This is an outdoor parkour class built for adults of all abilities.' );
?>
<details class="collapse collapse-arrow" data-component="faq-item" data-surface="<?php echo esc_attr( $lp_surface ); ?>"<?php echo empty( $args['default_open'] ) ? '' : ' open'; ?>>
	<summary class="<?php echo lp_classes( 'collapse-title flex items-center gap-[18px] px-0 py-[10px] font-heading text-[20px] font-medium tracking-[-0.3px]', $lp_tone['question'] ); ?>">
		<span class="<?php echo lp_classes( 'font-label text-[10px] font-normal tracking-[0.9px] shrink-0', $lp_tone['index'] ); ?>"><?php echo esc_html( $lp_index ); ?></span>
		<span class="min-w-0"><?php echo esc_html( $lp_question ); ?></span>
	</summary>
	<div class="collapse-content px-0">
		<p class="<?php echo lp_classes( 'text-[13px] font-normal tracking-[0.1px]', $lp_tone['answer'] ); ?>"><?php echo esc_html( $lp_answer ); ?></p>
	</div>
</details>
