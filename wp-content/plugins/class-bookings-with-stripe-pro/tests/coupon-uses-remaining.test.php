<?php
/**
 * Booking confirmation must snapshot coupon uses *after* this booking.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/coupon-uses-remaining.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Bookings;
use IOROOT_STRIPE_BOOKINGS_PRO\CPT;
use IOROOT_STRIPE_BOOKINGS_PRO\Merge_Tags;
use IOROOT_STRIPE_BOOKINGS_PRO\Packs;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$promo_id = 'promo_test_uses_' . wp_generate_password( 8, false );

$purchase_id = wp_insert_post(
	[
		'post_type'   => CPT::PACK_PURCHASE_PT,
		'post_status' => 'publish',
		'post_title'  => 'Test 5-pack',
	]
);
$assert( $purchase_id > 0, 'can insert a pack purchase' );
update_post_meta( $purchase_id, '_clasbpro_pack_uses', 5 );
update_post_meta( $purchase_id, '_clasbpro_stripe_promo_id', $promo_id );
update_post_meta( $purchase_id, '_clasbpro_status', Packs::STATUS_PAID );

$booking_id = wp_insert_post(
	[
		'post_type'   => CPT::BOOKING_PT,
		'post_status' => 'publish',
		'post_title'  => 'Test coupon booking',
	]
);
$assert( $booking_id > 0, 'can insert a booking' );
update_post_meta( $booking_id, '_clasbpro_pack_promo_id', $promo_id );
update_post_meta( $booking_id, '_clasbpro_status', Bookings::STATUS_PENDING );

$promo                  = new \stdClass();
$promo->id              = $promo_id;
$promo->active          = true;
$promo->expires_at      = null;
$promo->max_redemptions = 5;
$promo->times_redeemed  = 0;
$promo->metadata        = (object) [
	'clasbpro_purchase_id' => (string) $purchase_id,
	'clasbpro_pack_id'     => '0',
];

Packs::forget_consumed_uses( $purchase_id );
$assert( 0 === Packs::count_consumed_uses( $purchase_id ), 'pending booking is not a consumed use' );

$remaining = Merge_Tags::coupon_uses_remaining_after_booking( $booking_id, $promo );
$assert( 4 === $remaining, 'first use of a 5-pack leaves 4 while the booking is still pending' );

Bookings::set_status( $booking_id, Bookings::STATUS_PAID );
$remaining_paid = Merge_Tags::coupon_uses_remaining_after_booking( $booking_id, $promo );
$assert( 4 === $remaining_paid, 'first use of a 5-pack still leaves 4 after the booking is paid (not 3)' );

wp_delete_post( $booking_id, true );
wp_delete_post( $purchase_id, true );
Packs::forget_consumed_uses( $purchase_id );

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'coupon uses remaining snapshot' );
