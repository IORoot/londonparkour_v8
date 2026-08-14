<?php
/**
 * GlyphLabel — the smallest labelling unit: optional 13px glyph + a 10px/600
 * uppercase word. The design file calls it a Kicker, a Tag and a Category
 * label in different places; it is one element.
 *
 * Ported from src/stories/Elements/GlyphLabel/GlyphLabel.js.
 *
 * Colour is a surface x tone matrix, not a flat list, because the same three
 * tones recur on four different grounds and the correct token differs on each.
 * Getting this wrong is the most repeated bug in the design system:
 *   page   — `signal` MUST be text-accent, never text-primary (primary on the
 *            light ground measures 1.54:1 yellow / 1.27:1 lime). `muted` is
 *            /65, not /50 — /50 measures 3.38:1.
 *   board  — the fixed dark band, where text-primary is safe and base-content
 *            is invisible in both light themes.
 *   fill   — on a bg-primary signal band.
 *   accent — on a bg-accent band.
 * The full matrix and its measured contrast floors are canonical in the
 * Storybook's docs/phase7/surface-axis.md. Do not re-derive them.
 *
 * @param string $args['label']
 * @param string $args['icon_id'] Optional leading glyph.
 * @param string $args['surface'] page|board|fill|accent.
 * @param string $args['tone']    signal|ink|muted.
 * @param string $args['class']   Extra call-site classes (whole literals).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_tones = array(
	'page'   => array(
		'signal' => 'text-accent',
		'ink'    => 'text-base-content',
		'muted'  => 'text-base-content/65',
	),
	'board'  => array(
		'signal' => 'text-primary',
		'ink'    => 'text-neutral-content',
		'muted'  => 'text-neutral-content/50',
	),
	'fill'   => array(
		'signal' => 'text-primary-content',
		'ink'    => 'text-primary-content',
		'muted'  => 'text-primary-content/70',
	),
	'accent' => array(
		'signal' => 'text-accent-content',
		'ink'    => 'text-accent-content',
		'muted'  => 'text-accent-content/70',
	),
);

$lp_root    = 'inline-flex items-center gap-[8px] font-label text-[10px] font-semibold tracking-[1px] uppercase whitespace-nowrap';
$lp_surface = $lp_tones[ $args['surface'] ?? 'page' ] ?? $lp_tones['page'];
$lp_tone    = $lp_surface[ $args['tone'] ?? 'muted' ] ?? $lp_surface['muted'];
$lp_extra   = (string) ( $args['class'] ?? '' );
$lp_label   = (string) ( $args['label'] ?? '' );

if ( '' === $lp_label ) {
	return;
}
?>
<?php // No stray whitespace inside the span — it is inline-flex and gap already spaces the glyph. ?>
<span class="<?php echo lp_classes( $lp_root, $lp_tone, $lp_extra ); ?>" data-component="glyph-label"><?php
if ( ! empty( $args['icon_id'] ) ) {
	// Decorative: the label carries the meaning, and currentColor makes the glyph track the tone.
	echo '<span class="shrink-0" aria-hidden="true">';
	lp_icon( $args['icon_id'], 'w-[13px] h-[13px]' );
	echo '</span>';
}
echo esc_html( $lp_label );
?></span>
