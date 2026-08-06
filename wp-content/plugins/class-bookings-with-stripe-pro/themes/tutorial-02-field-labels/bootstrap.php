<?php
/**
 * Tutorial 02 — change all booking form field labels.
 *
 * Edit any label key in the array below. Keys match Booking_Form_View defaults.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		// ↓↓↓ CHANGE THESE — field and hint labels ↓↓↓
		$labels['name']            = __( 'Full name', 'class-bookings-with-stripe-pro' );
		$labels['email']           = __( 'Your email', 'class-bookings-with-stripe-pro' );
		$labels['date']            = __( 'Pick a session', 'class-bookings-with-stripe-pro' );
		$labels['event_date']      = __( 'Session date', 'class-bookings-with-stripe-pro' );
		$labels['seats']           = __( 'Number of guests', 'class-bookings-with-stripe-pro' );
		$labels['total']           = __( 'Amount due', 'class-bookings-with-stripe-pro' );
		$labels['book_button']     = __( 'Pay & confirm', 'class-bookings-with-stripe-pro' );
		$labels['redirect_hint']   = __( 'Stripe opens in a new step to take payment.', 'class-bookings-with-stripe-pro' );
		$labels['waiver_label']    = __( 'I accept the participation waiver.', 'class-bookings-with-stripe-pro' );
		$labels['waiver_page_link_text'] = __( 'Read waiver', 'class-bookings-with-stripe-pro' );
		$labels['mailchimp_optin_label'] = __( 'Send me class news by email.', 'class-bookings-with-stripe-pro' );
		// ↑↑↑ CHANGE THESE ↑↑↑
		return $labels;
	}
);
