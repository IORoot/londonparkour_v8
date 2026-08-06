<?php
/**
 * Tutorial 01 — change the submit button label.
 *
 * Edit the book_button value below. No layout or CSS changes.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		// ↓↓↓ CHANGE THIS — submit button text ↓↓↓
		$labels['book_button'] = __( 'Complete my booking', 'class-bookings-with-stripe-pro' );
		// ↑↑↑ CHANGE THIS ↑↑↑
		return $labels;
	}
);
