<?php
/**
 * Appointment party-size pricing.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/party-prices.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Helpers;
use IOROOT_STRIPE_BOOKINGS_PRO\Party_Prices;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$table = Party_Prices::sanitize_map(
	[
		1 => '65',
		2 => '45',
		3 => '40',
	],
	3
);

$assert( 65.0 === $table[1] && 45.0 === $table[2] && 40.0 === $table[3], '1/2/3 people store per-person 65/45/40' );
$assert( 3 === count( $table ), 'capacity 3 keeps three sizes' );

$assert( 65.0 === Party_Prices::unit_price_for_seats( $table, 1 ), '1 person unit is 65' );
$assert( 45.0 === Party_Prices::unit_price_for_seats( $table, 2 ), '2 people unit is 45' );
$assert( 40.0 === Party_Prices::unit_price_for_seats( $table, 3 ), '3 people unit is 40' );

$assert( 6500 === Party_Prices::total_pence_for_seats( $table, 1 ), '1 person total is 6500 pence' );
$assert( 9000 === Party_Prices::total_pence_for_seats( $table, 2 ), '2 people total is 9000 pence' );
$assert( 12000 === Party_Prices::total_pence_for_seats( $table, 3 ), '3 people total is 12000 pence' );

$assert( 65.0 === Party_Prices::one_person_rate( $table ), 'class price is the 1-person rate' );
$assert( 65.0 === Party_Prices::cheapest_session_total( $table ), 'from uses cheapest session total not cheapest per-person' );

$missing = Party_Prices::sanitize_map(
	[
		1 => '65',
		2 => '45',
		3 => '40',
	],
	4
);
$assert( ! isset( $missing[4] ), 'capacity 4 with no 4th row leaves that size missing' );
$assert( null === Party_Prices::unit_price_for_seats( $missing, 4 ), 'missing size lookup fails' );
$assert( null === Party_Prices::total_pence_for_seats( $missing, 4 ), 'missing size has no total' );
$assert( false === Party_Prices::is_complete( $missing, 4 ), 'incomplete table is not complete' );
$assert( true === Party_Prices::is_complete( $table, 3 ), 'filled 1..capacity is complete' );

$trimmed = Party_Prices::sanitize_map(
	[
		1 => '65',
		2 => '45',
		3 => '40',
		4 => '35',
	],
	3
);
$assert( ! isset( $trimmed[4] ), 'sizes above capacity are dropped' );

$zeros = Party_Prices::sanitize_map( [ 1 => '0', 2 => '' ], 2 );
$assert( isset( $zeros[1] ) && 0.0 === $zeros[1], '0 is a valid free rate' );
$assert( ! isset( $zeros[2] ), 'empty string is missing not zero' );

$assert( null === Party_Prices::unit_price_for_seats( $table, 0 ), 'seats below 1 are rejected' );
$assert( null === Party_Prices::unit_price_for_seats( $table, -1 ), 'negative seats are rejected' );

$weekly = Party_Prices::sanitize_map( [], 20 );
$assert( [] === $weekly, 'empty map stays empty for weekly classes' );
$assert( 0.0 === Party_Prices::one_person_rate( [] ), 'missing 1-person rate is 0' );
$assert( null === Party_Prices::cheapest_session_total( [] ), 'empty table has no from-price' );

$unit_pence = Helpers::to_pence( 15 );
$assert( 4500 === $unit_pence * 3, 'weekly class still charges unit times seats' );

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'appointment party prices' );
