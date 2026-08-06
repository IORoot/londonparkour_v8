<?php
/**
 * FactRow — a label + value pair.
 *
 * Ported from src/stories/Components/FactRow/FactRow.js.
 *
 * Built on daisyUI's stats > stat > stat-title/stat-value hierarchy (the
 * `stats` root is required even for a single fact), with every size, spacing
 * and colour overridden to the literal design pixels.
 *
 * One canonical 15px/500 value, deliberately — the source's five locations
 * measure 14/15/15/16/17px, a continuous range with no clean split, which
 * reads as per-context tuning rather than a scale. No `size` prop.
 *
 * @param string $args['label']   Default 'PRICE'.
 * @param string $args['value']   Default '£15 drop-in'.
 * @param string $args['icon']    Optional 12px leading glyph; decorative.
 * @param string $args['surface'] board|accent|page|fill. Default 'board'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'board'  => array(
		'label' => 'stat-title p-0 m-0 flex items-center gap-2 font-label text-[10px] font-normal tracking-[0.9px] uppercase text-neutral-content/50',
		'value' => 'stat-value p-0 mt-[7px] font-body text-[15px] font-medium tracking-[-0.2px] normal-case text-neutral-content',
	),
	'accent' => array(
		'label' => 'stat-title p-0 m-0 flex items-center gap-2 font-label text-[10px] font-normal tracking-[0.9px] uppercase text-accent-content/70',
		'value' => 'stat-value p-0 mt-[7px] font-body text-[15px] font-medium tracking-[-0.2px] normal-case text-accent-content',
	),
	'page'   => array(
		'label' => 'stat-title p-0 m-0 flex items-center gap-2 font-label text-[10px] font-normal tracking-[0.9px] uppercase text-base-content/65',
		'value' => 'stat-value p-0 mt-[7px] font-body text-[15px] font-medium tracking-[-0.2px] normal-case text-base-content',
	),
	'fill'   => array(
		'label' => 'stat-title p-0 m-0 flex items-center gap-2 font-label text-[10px] font-normal tracking-[0.9px] uppercase text-primary-content/70',
		'value' => 'stat-value p-0 mt-[7px] font-body text-[15px] font-medium tracking-[-0.2px] normal-case text-primary-content',
	),
);

$lp_tone    = $lp_surfaces[ $args['surface'] ?? 'board' ] ?? $lp_surfaces['board'];
$lp_label   = (string) ( $args['label'] ?? 'PRICE' );
$lp_value   = (string) ( $args['value'] ?? '£15 drop-in' );
$lp_icon_id = (string) ( $args['icon'] ?? '' );
?>
<div class="stats bg-transparent shadow-none" data-component="fact-row">
	<div class="stat p-0 min-h-0 w-auto place-items-start">
		<div class="<?php echo esc_attr( $lp_tone['label'] ); ?>">
			<?php if ( '' !== $lp_icon_id ) : ?>
				<?php /* Decorative — the label carries the meaning, currentColor tracks its tone. */ ?>
				<span class="shrink-0" aria-hidden="true"><?php lp_icon( $lp_icon_id, 'w-[12px] h-[12px]' ); ?></span>
			<?php endif; ?>
			<?php echo esc_html( $lp_label ); ?>
		</div>
		<div class="<?php echo esc_attr( $lp_tone['value'] ); ?>"><?php echo esc_html( $lp_value ); ?></div>
	</div>
</div>
