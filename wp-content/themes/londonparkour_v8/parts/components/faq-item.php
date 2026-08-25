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
 * @param bool   $args['collapsible']  When false, static Q then A — no accordion.
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
$lp_title        = lp_classes( 'flex items-center gap-[18px] px-0 py-[10px] font-heading text-[20px] font-medium tracking-[-0.3px]', $lp_tone['question'] );
$lp_title_static = lp_classes( 'flex items-center gap-[18px] px-0 font-heading text-[20px] font-medium tracking-[-0.3px] leading-[22px]', $lp_tone['question'] );
$lp_index_c      = lp_classes( 'font-label text-[10px] font-normal tracking-[0.9px] shrink-0', $lp_tone['index'] );
$lp_answer_c     = lp_classes( 'font-body text-[13px] font-normal tracking-[0.1px] leading-[1.7]', $lp_tone['answer'] );

if ( isset( $args['collapsible'] ) && false === $args['collapsible'] ) :
	?>
<div class="flex flex-col gap-4 py-[26px]" data-component="faq-item" data-collapsible="false" data-surface="<?php echo esc_attr( $lp_surface ); ?>">
	<div class="<?php echo $lp_title_static; ?>">
		<span class="<?php echo $lp_index_c; ?>"><?php echo esc_html( $lp_index ); ?></span>
		<span class="min-w-0"><?php echo esc_html( $lp_question ); ?></span>
	</div>
	<div class="pl-7 pr-[60px]">
		<p class="<?php echo $lp_answer_c; ?>"><?php echo esc_html( $lp_answer ); ?></p>
	</div>
</div>
	<?php
	return;
endif;
?>
<details class="collapse collapse-arrow" data-component="faq-item" data-surface="<?php echo esc_attr( $lp_surface ); ?>"<?php echo empty( $args['default_open'] ) ? '' : ' open'; ?>>
	<summary class="<?php echo lp_classes( 'collapse-title', $lp_title ); ?>">
		<span class="<?php echo $lp_index_c; ?>"><?php echo esc_html( $lp_index ); ?></span>
		<span class="min-w-0"><?php echo esc_html( $lp_question ); ?></span>
	</summary>
	<div class="collapse-content px-0">
		<p class="<?php echo $lp_answer_c; ?>"><?php echo esc_html( $lp_answer ); ?></p>
	</div>
</details>
