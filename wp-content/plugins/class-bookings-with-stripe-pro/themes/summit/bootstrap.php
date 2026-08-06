<?php
/**
 * Summit — fonts and bespoke form labels.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'clasbpro-summit-fonts',
			'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600&display=swap',
			[],
			null
		);
	},
	15
);

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		$labels['name']          = __( 'Full name', 'class-bookings-with-stripe-pro' );
		$labels['email']         = __( 'Email address', 'class-bookings-with-stripe-pro' );
		$labels['date']          = __( 'Select your session', 'class-bookings-with-stripe-pro' );
		$labels['seats']         = __( 'Group size', 'class-bookings-with-stripe-pro' );
		$labels['total']         = __( 'Trip total', 'class-bookings-with-stripe-pro' );
		$labels['book_button']   = __( 'Book & pay', 'class-bookings-with-stripe-pro' );
		$labels['redirect_hint'] = __( 'Secure encrypted checkout with Stripe.', 'class-bookings-with-stripe-pro' );
		return $labels;
	}
);
