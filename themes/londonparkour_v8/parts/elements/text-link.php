<?php
/**
 * TextLink — uppercase text-link CTA, opacity-fade hover.
 *
 * Consolidated per docs/CONSOLIDATION.md §4a `text-link-cta` (5 occurrences:
 * Components/BoardShell.js `FOOT_CTA`, Blocks/Classes.js foot CTA,
 * Blocks/TrainInPerson.js `ctaLabel`, Blocks/Coaches.js `linkLabel`,
 * Components/BreadcrumbRail.js `action`). Verified byte-for-byte against the
 * Storybook source; `whitespace-nowrap` is stripped from every variant below
 * because the source only carries it on the two narrower/tighter instances
 * (TrainInPerson, BreadcrumbRail) and §4a is explicit it is a call-site
 * modifier, not part of the atom — pass it via $args['class'] where needed.
 *
 * `board_compact_accent` added for the Legal page's doc-meta actions
 * (`DOWNLOAD PDF ↓` / `PRINT THIS PAGE`) — byte-identical to `board_compact`
 * but `accent` in place of `primary`, matching the source's doc-meta ink.
 *
 * Always renders a real <a href> — never role="button" (Port Brief a11y rule).
 *
 * @param string $args['label']   Visible text.
 * @param string $args['href']    Required.
 * @param string $args['variant'] board|board_compact|board_compact_accent|accent_band|page_accent. Default 'board'.
 * @param string $args['class']   Extra call-site classes, e.g. 'whitespace-nowrap'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/*
 * Whole literal strings per variant, byte-identical to the Storybook source.
 * Never assembled from fragments — Tailwind v4 scans source text.
 */
$lp_variants = array(
	'board'                => 'font-label text-[12px] font-semibold uppercase tracking-[0.9px] text-primary hover:text-primary/70 transition-colors duration-150',
	'board_compact'        => 'font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary hover:text-primary/70 transition-colors duration-150',
	'board_compact_accent' => 'font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent hover:text-accent/70 transition-colors duration-150',
	'accent_band'          => 'font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-accent-content hover:text-accent-content/70 transition-colors duration-150',
	'page_accent'          => 'font-label text-step--2 font-semibold tracking-[0.5px] text-accent hover:text-accent/70 transition-colors duration-150',
);

$lp_variant = $args['variant'] ?? 'board';
$lp_classes = $lp_variants[ $lp_variant ] ?? $lp_variants['board'];
$lp_label   = (string) ( $args['label'] ?? '' );
$lp_href    = (string) ( $args['href'] ?? '' );

if ( ! empty( $args['class'] ) ) {
	$lp_classes .= ' ' . $args['class'];
}

if ( '' === $lp_label || '' === $lp_href ) {
	return;
}
?>
<a class="<?php echo esc_attr( $lp_classes ); ?>" href="<?php echo esc_url( $lp_href ); ?>" data-component="text-link"><?php echo esc_html( $lp_label ); ?></a>
