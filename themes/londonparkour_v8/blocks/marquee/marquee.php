<?php
/**
 * Marquee — the scrolling signal band.
 *
 * Ported from src/stories/Blocks/Marquee/Marquee.js.
 *
 * Repeater-only: no CPT source control, the items are the block's own copy.
 *
 * The track is aria-hidden and duplicated visually by the motion layer, so the
 * full list is also emitted once in an sr-only span — a screen reader gets one
 * clean reading instead of an endless loop. The `data-motion-marquee*`
 * attributes carry over verbatim; assets/js already reads them, and there is no
 * PHP-side motion wiring to do.
 *
 * @param array  $args['items']     Rows of array( 'label' => … ).
 * @param string $args['direction'] left|right. Default 'left'.
 * @param int    $args['speed']     Default 60.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_items = array(
	'NO EXPERIENCE NEEDED',
	'ALL AGES',
	'ALL BODIES',
	'INDOOR + OUTDOOR',
	'FIRST CLASS £15',
	'NO CONTRACT',
	'6 LONDON SITES',
	'COACH-LED',
);

$lp_items = array();

foreach ( is_array( $args['items'] ?? null ) ? $args['items'] : array() as $lp_row ) {
	$lp_text = is_array( $lp_row ) ? (string) ( $lp_row['label'] ?? '' ) : (string) $lp_row;
	if ( '' !== $lp_text ) {
		$lp_items[] = $lp_text;
	}
}

if ( ! $lp_items ) {
	$lp_items = $lp_default_items;
}

// Whole literal strings; the direction is a lookup, never interpolated.
$lp_directions = array(
	'left'  => 'left',
	'right' => 'right',
);
$lp_direction  = $lp_directions[ $args['direction'] ?? 'left' ] ?? 'left';

$lp_speed = is_numeric( $args['speed'] ?? null ) ? (int) $args['speed'] : 60;

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full', $lp_spacing ); ?>" data-component="marquee"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="w-full overflow-hidden bg-primary py-[19px]">
		<div
			class="flex items-center w-max"
			data-motion-marquee
			data-motion-marquee-direction="<?php echo esc_attr( $lp_direction ); ?>"
			data-motion-marquee-speed="<?php echo esc_attr( (string) $lp_speed ); ?>"
			aria-hidden="true"
		>
			<?php foreach ( $lp_items as $lp_item ) : ?>
				<span class="font-heading text-step--1 font-semibold tracking-[0.6px] uppercase text-primary-content whitespace-nowrap"><?php echo esc_html( $lp_item ); ?></span>
				<span class="font-heading text-step--2 text-primary-content px-[18px]" aria-hidden="true">✳</span>
			<?php endforeach; ?>
		</div>
		<span class="sr-only"><?php echo esc_html( implode( ', ', $lp_items ) ); ?></span>
	</div>
</section>
