<?php
/**
 * Checkout rate-limit settings clamp to safe bounds.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/checkout-rate-limit.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\REST;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$defaults = REST::sanitize_checkout_rate_limit_config( 8, 5, 15 );
$assert( 8 === $defaults['ip'], 'default IP max is 8' );
$assert( 5 === $defaults['email'], 'default email max is 5' );
$assert( 15 * MINUTE_IN_SECONDS === $defaults['ttl'], 'default window is 15 minutes in seconds' );

$zero = REST::sanitize_checkout_rate_limit_config( 0, 0, 15 );
$assert( 0 === $zero['ip'] && 0 === $zero['email'], '0 disables that bucket' );

$floor = REST::sanitize_checkout_rate_limit_config( -3, 20, 0 );
$assert( 0 === $floor['ip'], 'negative IP max floors at 0' );
$assert( 20 === $floor['email'], 'positive email max is kept' );
$assert( MINUTE_IN_SECONDS === $floor['ttl'], 'window below 1 minute floors at 1 minute' );

$custom = REST::sanitize_checkout_rate_limit_config( 12, 3, 30 );
$assert( 12 === $custom['ip'] && 3 === $custom['email'] && ( 30 * MINUTE_IN_SECONDS ) === $custom['ttl'], 'custom settings pass through' );

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'checkout rate limit settings' );
