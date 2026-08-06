<?php
/**
 * Maison — fonts and bespoke form labels.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'clasbpro-maison-fonts',
			'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=DM+Sans:wght@400;500;600&display=swap',
			[],
			null
		);
	},
	15
);

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		$labels['name']          = __( 'Guest name', 'class-bookings-with-stripe-pro' );
		$labels['email']         = __( 'Email for confirmation', 'class-bookings-with-stripe-pro' );
		$labels['date']          = __( 'Preferred date', 'class-bookings-with-stripe-pro' );
		$labels['seats']         = __( 'Places required', 'class-bookings-with-stripe-pro' );
		$labels['total']         = __( 'Total due', 'class-bookings-with-stripe-pro' );
		$labels['book_button']   = __( 'Reserve your place', 'class-bookings-with-stripe-pro' );
		$labels['redirect_hint'] = '';
		return $labels;
	}
);
