<?php
/**
 * Custom Labels theme — override booking form labels.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		$labels['book_button'] = __( 'Reserve & pay securely', 'class-bookings-with-stripe-pro' );
		return $labels;
	}
);
