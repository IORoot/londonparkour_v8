<?php
/**
 * Pay form — admin-post fallback and REST URL for `/pay/`.
 *
 * JS (assets/js/elements/PayForm.js) posts JSON to clasbpro `/custom-checkout`.
 * Without JS the same payload goes through admin-post.php → this handler,
 * which calls the plugin and redirects to Stripe Checkout.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canonical URL for the quiet Pay page.
 */
function lp_pay_url(): string {
	$page = get_page_by_path( 'pay' );
	return $page instanceof WP_Post ? (string) get_permalink( $page ) : home_url( '/pay/' );
}

/**
 * Localise the custom-checkout REST URL onto the main bundle on /pay/.
 */
function lp_pay_localize(): void {
	if ( ! wp_script_is( 'londonparkour', 'enqueued' ) ) {
		return;
	}
	if ( ! is_page_template( 'templates/pay.php' ) && ! is_page( 'pay' ) ) {
		return;
	}

	$rest = '';
	if ( defined( 'CLASBOWPRO_REST_NS' ) ) {
		$rest = esc_url_raw( rest_url( CLASBOWPRO_REST_NS . '/custom-checkout' ) );
	}

	wp_localize_script(
		'londonparkour',
		'lpPay',
		array(
			'restUrl' => $rest,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lp_pay_localize', 21 );

/**
 * Handle Pay form submissions (logged-in and anonymous).
 */
function lp_handle_pay_form(): void {
	$lp_redirect = wp_get_referer();
	if ( ! $lp_redirect ) {
		$lp_redirect = lp_pay_url();
	}

	$lp_fail = static function ( string $message ) use ( $lp_redirect ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'pay' => 'error',
					'msg' => $message,
				),
				$lp_redirect
			)
		);
		exit;
	};

	if ( ! isset( $_POST['lp_pay_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lp_pay_nonce'] ) ), 'lp_pay' ) ) {
		$lp_fail( __( 'Something went wrong starting payment. Please try again.', 'londonparkour_v8' ) );
	}

	if ( ! empty( $_POST['lp_company'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$lp_fail( __( 'Something went wrong starting payment. Please try again.', 'londonparkour_v8' ) );
	}

	if ( ! class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Custom_Payments' ) ) {
		$lp_fail( __( 'Payments are not configured. Please contact us.', 'londonparkour_v8' ) );
	}

	$result = \IOROOT_STRIPE_BOOKINGS_PRO\Custom_Payments::start_checkout(
		isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '',
		$lp_redirect
	);

	if ( is_wp_error( $result ) ) {
		$lp_fail( $result->get_error_message() );
	}

	$url = (string) ( $result['url'] ?? '' );
	if ( '' === $url ) {
		$lp_fail( __( 'No payment URL returned. Please try again.', 'londonparkour_v8' ) );
	}

	wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Stripe Checkout is an external payment page.
	exit;
}
add_action( 'admin_post_lp_pay', 'lp_handle_pay_form' );
add_action( 'admin_post_nopriv_lp_pay', 'lp_handle_pay_form' );
