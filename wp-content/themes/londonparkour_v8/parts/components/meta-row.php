<?php
/**
 * MetaRow — a plain editorial eyebrow: left label, right label, space-between.
 *
 * Ported from src/stories/Components/MetaRow/MetaRow.js.
 *
 * Not departure-board data. The source node sets both labels to `normal`
 * weight, not the label voice's usual 600 — kept as captured.
 *
 * With `icon` the left slot becomes a glyph-label rather than re-declaring
 * that element's 600-weight type values here; the no-icon left/right stay
 * font-normal. `surface` passes straight through to it, never patched at the
 * call site, and `tone` stays 'ink' on all three surfaces.
 *
 * @param string $args['left']    Default '05 — WHY WE DO IT'.
 * @param string $args['right']   Default 'SINCE 2015'.
 * @param string $args['icon']    Glyph id; composes elements/glyph-label on the left.
 * @param string $args['surface'] page|board|accent. Default 'page'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'page'   => 'font-label text-[10px] font-normal tracking-[0.5px] uppercase text-base-content/65',
	'board'  => 'font-label text-[10px] font-normal tracking-[0.5px] uppercase text-neutral-content/50',
	'accent' => 'font-label text-[10px] font-normal tracking-[0.5px] uppercase text-accent-content/70',
);

$lp_surface = (string) ( $args['surface'] ?? 'page' );
$lp_label   = $lp_surfaces[ $lp_surface ] ?? $lp_surfaces['page'];
$lp_left    = (string) ( $args['left'] ?? '05 — WHY WE DO IT' );
$lp_right   = (string) ( $args['right'] ?? 'SINCE 2015' );
$lp_icon_id = (string) ( $args['icon'] ?? '' );
?>
<div class="w-full flex items-center justify-between gap-4 flex-wrap" data-component="meta-row">
	<?php if ( '' !== $lp_icon_id ) : ?>
		<?php
		lp_part(
			'elements/glyph-label',
			array(
				'label'   => $lp_left,
				'icon_id' => $lp_icon_id,
				'surface' => $lp_surface,
				'tone'    => 'ink',
			)
		);
		?>
	<?php else : ?>
		<span class="<?php echo esc_attr( $lp_label ); ?>"><?php echo esc_html( $lp_left ); ?></span>
	<?php endif; ?>
	<span class="<?php echo esc_attr( $lp_label ); ?>"><?php echo esc_html( $lp_right ); ?></span>
</div>
