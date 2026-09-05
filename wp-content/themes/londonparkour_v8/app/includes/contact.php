<?php
/**
 * Contact form handler — admin-post.php action `lp_contact`.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handle contact form submissions (logged-in and anonymous).
 */
function lp_handle_contact_form(): void {
	$lp_redirect = wp_get_referer();

	if ( ! $lp_redirect ) {
		$lp_redirect = home_url( '/' );
	}

	$lp_fail = static function () use ( $lp_redirect ): void {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $lp_redirect ) );
		exit;
	};

	if ( ! isset( $_POST['lp_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lp_contact_nonce'] ) ), 'lp_contact' ) ) {
		$lp_fail();
	}

	// Honeypot — bots fill hidden fields; humans never see this.
	if ( ! empty( $_POST['lp_company'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$lp_fail();
	}

	$lp_name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$lp_email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$lp_subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$lp_message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $lp_name || '' === $lp_email || '' === $lp_message || ! is_email( $lp_email ) ) {
		$lp_fail();
	}

	if ( '' === $lp_subject ) {
		$lp_subject = sprintf(
			/* translators: %s: submitter name. */
			__( 'Contact from %s', 'londonparkour_v8' ),
			$lp_name
		);
	}

	$lp_to = function_exists( 'get_field' )
		? (string) get_field( 'contact_email', 'option' )
		: '';

	if ( '' === $lp_to || ! is_email( $lp_to ) ) {
		$lp_to = (string) get_option( 'admin_email' );
	}

	$lp_body = sprintf(
		"Name: %s\nEmail: %s\n\n%s",
		$lp_name,
		$lp_email,
		$lp_message
	);

	$lp_headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'From: ' . wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) . ' <' . $lp_to . '>',
		'Reply-To: ' . $lp_name . ' <' . $lp_email . '>',
	);

	$lp_sent = wp_mail( $lp_to, $lp_subject, $lp_body, $lp_headers );

	if ( ! $lp_sent ) {
		$lp_fail();
	}

	wp_safe_redirect( add_query_arg( 'contact', 'sent', $lp_redirect ) );
	exit;
}
add_action( 'admin_post_lp_contact', 'lp_handle_contact_form' );
add_action( 'admin_post_nopriv_lp_contact', 'lp_handle_contact_form' );
