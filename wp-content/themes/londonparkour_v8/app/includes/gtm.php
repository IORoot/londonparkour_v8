<?php
/**
 * Google Tag Manager — container ID from Site Settings.
 *
 * Snippet placement follows Google's install guide: head script as high as
 * possible after charset/viewport, noscript immediately after <body>.
 * The dataLayer is initialised by the snippet; theme ecommerce pushes in
 * assets/js/utils/analytics.js reuse the same array.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitised GTM container ID from Site Settings, or empty.
 *
 * Accepts a bare ID or extracts GTM-… from pasted snippet text.
 */
function lp_gtm_container_id(): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$raw = trim( (string) get_field( 'gtm_container_id', 'option' ) );
	if ( '' === $raw ) {
		return '';
	}

	if ( preg_match( '/GTM-[A-Z0-9]+/i', $raw, $matches ) ) {
		return strtoupper( $matches[0] );
	}

	return '';
}

/**
 * Official GTM <head> snippet. Call after charset/viewport, before wp_head().
 */
function lp_gtm_print_head(): void {
	$id = lp_gtm_container_id();
	if ( '' === $id ) {
		return;
	}
	?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $id ); ?>');</script>
<!-- End Google Tag Manager -->
	<?php
}

/**
 * Official GTM noscript fallback. Call immediately after <body>.
 */
function lp_gtm_print_noscript(): void {
	$id = lp_gtm_container_id();
	if ( '' === $id ) {
		return;
	}
	?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $id ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
	<?php
}

/**
 * Hidden dataLayer marker. JS in assets/js/utils/analytics.js reads these.
 *
 * @param string               $event  Event name.
 * @param array<string,scalar> $params Flattened to data-lp-{key}.
 */
function lp_analytics_event_marker( string $event, array $params = array() ): void {
	$attrs = array( 'data-lp-event' => $event );
	foreach ( $params as $key => $value ) {
		$attrs[ 'data-lp-' . str_replace( '_', '-', (string) $key ) ] = (string) $value;
	}
	echo '<div hidden' . lp_html_attrs( $attrs ) . '></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lp_html_attrs escapes.
}

/**
 * One GA4 ecommerce item for view_item / purchase markers.
 *
 * @return array<string,mixed>
 */
function lp_analytics_commerce_item( string $category, int $id, string $name, float $price = 0.0 ): array {
	$prefix = 'coupon' === $category ? 'pack' : $category;
	$item   = array(
		'item_id'       => $prefix . ':' . $id,
		'item_name'     => $name,
		'item_category' => $category,
		'quantity'      => 1,
	);
	if ( $price > 0 ) {
		$item['price'] = $price;
	}

	return $item;
}

/**
 * Hidden view_item marker. $items is a list of lp_analytics_commerce_item() rows.
 *
 * @param array<int,array<string,mixed>> $items Ecommerce items.
 */
function lp_analytics_view_item_marker( array $items ): void {
	$items = array_values( array_filter( $items ) );
	if ( ! $items ) {
		return;
	}
	printf(
		'<div hidden data-lp-event="view_item" data-lp-items="%s"></div>',
		esc_attr( (string) wp_json_encode( $items ) )
	);
}

/**
 * data-* map for select_content clicks (PLAY SERIES, lesson cards, sibling rows).
 *
 * @return array<string,string>
 */
function lp_select_content_attrs( string $type, string $id, string $name, string $series = '' ): array {
	return array(
		'data-lp-select-content' => '1',
		'data-lp-content-type'   => $type,
		'data-lp-content-id'     => $id,
		'data-lp-content-name'   => $name,
		'data-lp-series-name'    => $series,
	);
}
