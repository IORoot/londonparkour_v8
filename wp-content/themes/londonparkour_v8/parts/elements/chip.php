<?php
/**
 * Chip — Concourse design system (london_parkour_V4.pen).
 *
 * Ported from src/stories/Elements/Chip/Chip.js. One component for all 3
 * variants (each with rest/hover). No daisyUI equivalent exists for a chip,
 * so this is built purely from Tailwind utilities. Hover is always an
 * inversion — implemented once per variant below.
 *
 * `live` adds a 5px dot using `bg-current` so it tracks the label colour
 * automatically through the hover inversion, rather than needing its own
 * hover state.
 *
 * @param string $args['variant'] signal|live|dark.
 * @param string $args['label']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per variant — Tailwind v4 scans source text.
$lp_variants = array(
	'signal' => 'inline-flex items-center py-[5px] px-[9px] bg-primary text-primary-content ' .
		'hover:bg-neutral hover:text-primary transition-colors duration-150 ' .
		'font-label text-[10px] font-semibold uppercase tracking-[0.8px]',
	'live'   => 'inline-flex items-center gap-[8px] py-[7px] px-[11px] bg-primary text-primary-content ' .
		'hover:bg-neutral hover:text-primary transition-colors duration-150 ' .
		'font-label text-[10px] font-semibold uppercase tracking-[0.8px]',
	'dark'   => 'inline-flex items-center py-[7px] px-[11px] bg-neutral/88 text-neutral-content ' .
		'hover:bg-primary hover:text-neutral transition-colors duration-150 ' .
		'font-label text-[10px] font-semibold uppercase tracking-[1px]',
);

$lp_variant = $args['variant'] ?? 'signal';
$lp_label   = (string) ( $args['label'] ?? 'Chip' );
$lp_classes = $lp_variants[ $lp_variant ] ?? $lp_variants['signal'];
$lp_is_live = 'live' === $lp_variant;
?>
<?php // No stray whitespace inside the span — it is inline-flex and gap (live only) already spaces the dot. ?>
<span class="<?php echo esc_attr( $lp_classes ); ?>" data-component="chip" data-variant="<?php echo esc_attr( $lp_variant ); ?>"><?php
if ( $lp_is_live ) {
	echo '<span class="inline-block w-[5px] h-[5px] rounded-full bg-current" aria-hidden="true"></span>';
}
echo esc_html( $lp_label );
?></span>
