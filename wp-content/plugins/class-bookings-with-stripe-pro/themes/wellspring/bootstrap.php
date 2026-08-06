<?php
/**
 * Wellspring — fonts and bespoke form labels.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'clasbpro-wellspring-fonts',
			'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap',
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
		$labels['email']         = __( 'Email address', 'class-bookings-with-stripe-pro' );
		$labels['date']          = __( 'Schedule date', 'class-bookings-with-stripe-pro' );
		$labels['seats']         = __( 'How many people?', 'class-bookings-with-stripe-pro' );
		$labels['total']         = __( 'Total', 'class-bookings-with-stripe-pro' );
		$labels['book_button']   = __( 'Schedule now', 'class-bookings-with-stripe-pro' );
		$labels['redirect_hint'] = __( 'Secure payment powered by Stripe.', 'class-bookings-with-stripe-pro' );
		return $labels;
	}
);
