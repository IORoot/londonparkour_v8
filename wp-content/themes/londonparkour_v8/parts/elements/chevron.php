<?php
/**
 * Chevron — trailing "icon-chevron-right" affordance that brightens on row hover.
 *
 * Consolidated per docs/CONSOLIDATION.md §2b `chevron-affordance` (6 files /
 * 7 instances). Verified byte-for-byte against the Storybook source:
 *   - Components/BoardRow.js                         -> variant 'board_row'
 *   - Components/ListRow.js `surface: 'board'`        -> variant 'list_row_board'
 *   - Components/ListRow.js `surface: 'page'`         -> variant 'list_row_page'
 *   - Components/MediaCard.js (isLink)                -> variant 'media_card'
 *   - Components/VideoCard.js `variant="compact"`     -> variant 'media_card_static'
 *     plus call-site `group-hover:text-neutral` (card hover is yellow/black,
 *     not the MediaCard accent chevron)
 *   - Components/SearchResultRow.js                   -> variant 'search_result_row'
 *     (the one outlier icon size: w-[13px] h-[13px] vs everyone else's w-3.5 h-3.5)
 *   - Blocks/Locations.js site row                     -> variant 'accent_band'
 *
 * MediaCard/VideoCard's chevron is only an "affordance" (group-hover colour
 * change) when the card is a link; the non-link branch in the source renders
 * a bare `text-base-content` icon with no hover class at all. That static,
 * non-interactive case isn't a hover affordance and isn't worth a variant
 * here — a caller with a non-link card should call lp_icon() directly instead
 * of this part.
 *
 * Every variant below relies on `group-hover:` — the CALLER must put a
 * `group` class on the ancestor row/link for the hover state to fire. This
 * mirrors every one of the 7 source instances, which all render inside a
 * `group`-classed row.
 *
 * @param string $args['variant'] board_row|list_row_board|list_row_page|media_card|media_card_static|search_result_row|accent_band. Default 'list_row_board'.
 * @param string $args['class']   Extra call-site classes.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/*
 * Whole literal wrapper strings per variant, byte-identical to the Storybook
 * source. Never assembled from fragments — Tailwind v4 scans source text.
 */
$lp_variants = array(
	'board_row'         => array(
		'wrapper' => 'hidden sm:inline-flex text-neutral-content/50 group-hover:text-primary transition-colors duration-150 shrink-0',
		'icon'    => 'w-3.5 h-3.5',
	),
	'list_row_board'    => array(
		'wrapper' => 'text-neutral-content/30 group-hover:text-primary transition-colors duration-150 shrink-0',
		'icon'    => 'w-3.5 h-3.5',
	),
	'list_row_page'     => array(
		'wrapper' => 'text-base-content/30 group-hover:text-accent transition-colors duration-150 shrink-0',
		'icon'    => 'w-3.5 h-3.5',
	),
	'media_card'        => array(
		'wrapper' => 'text-base-content group-hover:text-accent transition-colors duration-150',
		'icon'    => 'w-3.5 h-3.5',
	),
	// MediaCard drops the hover half when the card is not a link — the source
	// writes the hover classes behind an `isLink` ternary, so a static card is
	// this exact string, not the one above.
	'media_card_static' => array(
		'wrapper' => 'text-base-content',
		'icon'    => 'w-3.5 h-3.5',
	),
	'search_result_row' => array(
		'wrapper' => 'shrink-0 text-base-content/65 group-hover:text-accent transition-colors duration-150',
		'icon'    => 'w-[13px] h-[13px]',
	),
	'accent_band'       => array(
		'wrapper' => 'text-accent-content/70 group-hover:text-accent-content transition-colors duration-150 shrink-0',
		'icon'    => 'w-3.5 h-3.5',
	),
);

$lp_variant = $lp_variants[ $args['variant'] ?? 'list_row_board' ] ?? $lp_variants['list_row_board'];
$lp_wrapper = $lp_variant['wrapper'];

if ( ! empty( $args['class'] ) ) {
	$lp_wrapper .= ' ' . $args['class'];
}
?>
<span class="<?php echo esc_attr( $lp_wrapper ); ?>" data-component="chevron" aria-hidden="true"><?php lp_icon( 'icon-chevron-right', $lp_variant['icon'] ); ?></span>
