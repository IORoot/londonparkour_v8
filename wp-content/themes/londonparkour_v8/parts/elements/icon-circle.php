<?php
/**
 * IconCircle — icon centred in a solid rounded-full colour disc.
 *
 * Consolidated per docs/CONSOLIDATION.md §2a `icon-circle` (2 occurrences).
 * Verified byte-for-byte against the Storybook source:
 *   - Components/VideoCard.js `variant="full"` play glyph, decorative,
 *     34px, non-interactive `<span>` -> variant '34'.
 *   - Components/VideoStage.js `PLAY_BUTTON`, 78px, a real `<button>` with
 *     hover-inversion and a focus ring -> variant '78'.
 * Same visual atom (icon-in-a-disc), two sizes, two interactivity levels;
 * the interactive variant is a strict superset of classes, never a computed
 * diff from the decorative one.
 *
 * Icon size is part of each variant's literal (the source pairs 34px discs
 * with a w-3.5 h-3.5 glyph and the 78px disc with a w-[28px] h-[28px] glyph)
 * — $args['icon_id'] only swaps which glyph renders, not its size.
 *
 * @param string $args['icon_id']     Glyph id. Default 'icon-play' (both source instances).
 * @param string $args['variant']     '34'|'78'. Default '34'.
 * @param string $args['aria_label']  Required for variant '78' (interactive) — the button's accessible name.
 * @param string $args['command']     @tailwindplus/elements dialog trigger.
 * @param string $args['command_for']
 * @param array  $args['data_attrs']  Extra data-* attributes on the interactive button.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/*
 * Whole literal strings per variant, byte-identical to the Storybook source.
 * Never assembled from fragments — Tailwind v4 scans source text.
 */
$lp_variants = array(
	'34' => array(
		'class'       => 'w-[34px] h-[34px] rounded-full bg-primary text-primary-content grid place-items-center shrink-0',
		'icon_class'  => 'w-3.5 h-3.5',
		'interactive' => false,
	),
	'78' => array(
		'class'       => 'group relative w-[78px] h-[78px] shrink-0 rounded-full bg-primary text-primary-content grid place-items-center transition-colors duration-150 hover:bg-neutral hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
		'icon_class'  => 'w-[28px] h-[28px]',
		'interactive' => true,
	),
);

$lp_variant   = $lp_variants[ $args['variant'] ?? '34' ] ?? $lp_variants['34'];
$lp_icon_id   = (string) ( $args['icon_id'] ?? 'icon-play' );
$lp_aria      = (string) ( $args['aria_label'] ?? '' );
$lp_interactive = $lp_variant['interactive'];

$lp_btn_attrs = array(
	'type'            => 'button',
	'class'           => $lp_variant['class'],
	'data-component'  => 'icon-circle',
);
if ( '' !== $lp_aria ) {
	$lp_btn_attrs['aria-label'] = $lp_aria;
}
foreach ( array( 'command', 'command_for' ) as $lp_pass ) {
	if ( ! empty( $args[ $lp_pass ] ) ) {
		$lp_btn_attrs[ str_replace( '_', '', $lp_pass ) ] = $args[ $lp_pass ];
	}
}
if ( ! empty( $args['data_attrs'] ) && is_array( $args['data_attrs'] ) ) {
	foreach ( $args['data_attrs'] as $lp_dk => $lp_dv ) {
		$lp_dk = (string) $lp_dk;
		if ( '' === $lp_dk ) {
			continue;
		}
		if ( 'command_for' === $lp_dk ) {
			$lp_dk = 'commandfor';
		}
		$lp_btn_attrs[ $lp_dk ] = (string) $lp_dv;
	}
}
?>
<?php if ( $lp_interactive ) : ?>
	<button
		<?php foreach ( $lp_btn_attrs as $lp_k => $lp_v ) : ?>
		<?php echo esc_attr( $lp_k ); ?>="<?php echo esc_attr( $lp_v ); ?>"
		<?php endforeach; ?>
	>
		<?php lp_icon( $lp_icon_id, $lp_variant['icon_class'] ); ?>
	</button>
<?php else : ?>
	<span class="<?php echo esc_attr( $lp_variant['class'] ); ?>" data-component="icon-circle" aria-hidden="true">
		<?php lp_icon( $lp_icon_id, $lp_variant['icon_class'] ); ?>
	</span>
<?php endif; ?>
