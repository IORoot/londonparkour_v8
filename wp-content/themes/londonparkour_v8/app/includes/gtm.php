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
