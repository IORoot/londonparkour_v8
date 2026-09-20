<?php
/**
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/manual-coupons.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Manual_Coupons;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$assert( class_exists( Manual_Coupons::class ), 'Manual_Coupons class exists' );

$assert( 'INTRO-20' === Manual_Coupons::normalize_code( ' intro-20 ' ), 'normalize_code uppercases, trims, and keeps hyphens' );
$assert( 'INTRO20' === Manual_Coupons::normalize_code( 'intro20' ), 'normalize_code uppercases a plain code' );
$assert( '' === Manual_Coupons::normalize_code( '!!!' ), 'normalize_code rejects punctuation-only input' );

$code = Manual_Coupons::generate_code();
$assert( is_string( $code ) && strlen( $code ) >= 8, 'generate_code returns a usable code' );
$assert( $code === strtoupper( $code ), 'generate_code is uppercase' );
$assert( $code === Manual_Coupons::normalize_code( $code ), 'generate_code matches normalize_code' );

$assert( 800 === Manual_Coupons::remaining_unit_pence( 1000, 'percent', 20 ), '20% off £10 leaves 800 pence' );
$assert( 0 === Manual_Coupons::remaining_unit_pence( 1000, 'percent', 100 ), '100% off leaves 0' );
$assert( 500 === Manual_Coupons::remaining_unit_pence( 1000, 'amount', 5 ), '£5 off £10 leaves 500 pence' );
$assert( 0 === Manual_Coupons::remaining_unit_pence( 400, 'amount', 10 ), 'amount off cannot go below 0' );
$assert( 1000 === Manual_Coupons::remaining_unit_pence( 1000, 'percent', 0 ), '0% off leaves the original amount' );

$assert( true === Manual_Coupons::class_is_eligible( 12, [] ), 'empty class list is valid for every class' );
$assert( true === Manual_Coupons::class_is_eligible( 12, [ 12, 15 ] ), 'listed class is eligible' );
$assert( false === Manual_Coupons::class_is_eligible( 9, [ 12, 15 ] ), 'unlisted class is not eligible' );
$assert( false === Manual_Coupons::class_is_eligible( 0, [] ), 'class 0 is never eligible' );

$assert( true === Manual_Coupons::email_lock_allows( '', 'anyone@example.com' ), 'shared code accepts any email' );
$assert( true === Manual_Coupons::email_lock_allows( '', '' ), 'shared code can attach before email is typed' );
$assert( true === Manual_Coupons::email_lock_allows( 'Andy@Example.com', 'andy@example.com' ), 'email lock is case-insensitive' );
$assert( false === Manual_Coupons::email_lock_allows( 'andy@example.com', 'sam@example.com' ), 'email lock rejects a different address' );
$assert( false === Manual_Coupons::email_lock_allows( 'andy@example.com', '' ), 'email lock requires an address at checkout' );

$uses = Manual_Coupons::uses_state( 0, 3 );
$assert( ! empty( $uses['unlimited'] ) && 0 === (int) $uses['remaining'], 'uses 0 is unlimited' );
$uses = Manual_Coupons::uses_state( 4, 1 );
$assert( empty( $uses['unlimited'] ) && 3 === (int) $uses['remaining'] && 4 === (int) $uses['total'], '4 uses with 1 consumed leaves 3' );
$uses = Manual_Coupons::uses_state( 2, 9 );
$assert( empty( $uses['unlimited'] ) && 0 === (int) $uses['remaining'], 'over-consumed uses floor at 0' );

$from = strtotime( '2026-03-20 10:00:00' );
$day  = Manual_Coupons::expiry_from_duration( 10, 'days', $from );
$assert( $day > $from, 'duration days lands in the future' );
$month = Manual_Coupons::expiry_from_duration( 1, 'months', $from );
$assert( $month > $day, 'one month is later than ten days from 20 Mar' );

$ids = Manual_Coupons::normalize_class_ids( [ 3, '3', 0, 7, [ 'ID' => 7 ], (object) [ 'ID' => 9 ] ] );
$assert( [ 3, 7, 9 ] === $ids, 'normalize_class_ids uniques ACF relationship shapes' );

$post_id = wp_insert_post( [
	'post_type'   => \IOROOT_STRIPE_BOOKINGS_PRO\CPT::MANUAL_COUPON_PT,
	'post_status' => 'publish',
	'post_title'  => 'Test intro',
] );
$assert( $post_id > 0, 'can insert a manual coupon post' );
if ( $post_id > 0 ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( 'manual_code', 'INTRO20', $post_id );
		update_field( 'manual_active', 1, $post_id );
		update_field( 'manual_discount_type', 'percent', $post_id );
		update_field( 'manual_discount_value', 20, $post_id );
		update_field( 'manual_uses', 5, $post_id );
		update_field( 'manual_classes', [ 12, 15 ], $post_id );
	} else {
		update_post_meta( $post_id, 'manual_code', 'INTRO20' );
		update_post_meta( $post_id, 'manual_active', 1 );
		update_post_meta( $post_id, 'manual_discount_type', 'percent' );
		update_post_meta( $post_id, 'manual_discount_value', 20 );
		update_post_meta( $post_id, 'manual_uses', 5 );
		update_post_meta( $post_id, 'manual_classes', [ 12, 15 ] );
	}
	update_post_meta( $post_id, Manual_Coupons::META_CODE, 'INTRO20' );
	update_post_meta( $post_id, Manual_Coupons::META_PROMO_ID, 'promo_test_intro20' );

	$found = Manual_Coupons::find_by_code( 'intro20' );
	$assert( is_array( $found ) && (int) $found['id'] === (int) $post_id, 'find_by_code locates the WP record' );
	$assert( is_array( $found ) && Manual_Coupons::class_is_eligible( 12, $found['class_ids'] ), 'stored class list allows class 12' );
	$assert( is_array( $found ) && ! Manual_Coupons::class_is_eligible( 99, $found['class_ids'] ), 'stored class list blocks class 99' );

	$block = Manual_Coupons::ineligibility_reason( $found, 99, '' );
	$assert( is_array( $block ) && 'class_not_covered' === $block['code'], 'ineligibility_reason reports the wrong class' );

	wp_delete_post( $post_id, true );
}

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed.' );
}

\WP_CLI::success( 'manual-coupons tests passed.' );
