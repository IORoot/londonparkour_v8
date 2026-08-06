<?php
/**
 * Badge — Concourse design system (london_parkour_V4.pen).
 *
 * Ported from src/stories/Elements/Badge/Badge.js. One component for both
 * design nodes: `paper` (rest only — no hover node in the source file) and
 * `category` (rest + hover). Built on daisyUI's `badge` class per the
 * Blueprint rules, with the design's exact geometry layered on as Tailwind
 * utilities.
 *
 * @param string $args['variant'] paper|category.
 * @param string $args['label']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per variant — Tailwind v4 scans source text.
$lp_variants = array(
	'paper'    => 'badge rounded-none border-none py-[5px] px-[9px] bg-neutral-content text-neutral ' .
		'font-label text-[10px] font-semibold uppercase tracking-[1px]',
	'category' => 'badge rounded-none border-none py-[5px] px-[8px] bg-neutral/89 text-neutral-content ' .
		'hover:bg-primary hover:text-neutral transition-colors duration-150 ' .
		'font-label text-[9px] font-semibold uppercase tracking-[1px]',
);

$lp_variant = $args['variant'] ?? 'paper';
$lp_label   = (string) ( $args['label'] ?? 'Badge' );
$lp_classes = $lp_variants[ $lp_variant ] ?? $lp_variants['paper'];
?>
<span class="<?php echo esc_attr( $lp_classes ); ?>" data-component="badge" data-variant="<?php echo esc_attr( $lp_variant ); ?>"><?php echo esc_html( $lp_label ); ?></span>
