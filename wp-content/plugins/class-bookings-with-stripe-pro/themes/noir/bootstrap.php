<?php
/**
 * Noir — fonts and bespoke form labels.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'clasbpro-noir-fonts',
			'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,500&family=Inter:wght@400;500;600&display=swap',
			[],
			null
		);
	},
	15
);

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		$labels['name']          = __( 'Your name', 'class-bookings-with-stripe-pro' );
		$labels['email']         = __( 'Email', 'class-bookings-with-stripe-pro' );
		$labels['date']          = __( 'Session date', 'class-bookings-with-stripe-pro' );
		$labels['seats']         = __( 'Party size', 'class-bookings-with-stripe-pro' );
		$labels['total']         = __( 'Order total', 'class-bookings-with-stripe-pro' );
		$labels['book_button']   = __( 'Complete booking', 'class-bookings-with-stripe-pro' );
		$labels['redirect_hint'] = __( 'Payment processed securely via Stripe.', 'class-bookings-with-stripe-pro' );
		return $labels;
	}
);
