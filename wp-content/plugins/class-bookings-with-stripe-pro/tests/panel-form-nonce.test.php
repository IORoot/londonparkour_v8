<?php
/**
 * Cached HTML can ship a stale wp_rest nonce. Public clasbpro routes must
 * treat that as anonymous, not 403 the booking drawer.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/panel-form-nonce.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Constants;
use IOROOT_STRIPE_BOOKINGS_PRO\Helpers;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$prev_nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? null;
$prev_route = $GLOBALS['wp']->query_vars['rest_route'] ?? null;

$_SERVER['HTTP_X_WP_NONCE'] = 'stale-nonce';
$core = rest_cookie_check_errors( null );
$assert(
	is_wp_error( $core ) && 'rest_cookie_invalid_nonce' === $core->get_error_code(),
	'WordPress still flags a stale X-WP-Nonce as rest_cookie_invalid_nonce'
);

$GLOBALS['wp']->query_vars['rest_route'] = '/wp/v2/posts';
$core_rest = apply_filters( 'rest_authentication_errors', null );
$assert(
	is_wp_error( $core_rest ) && 'rest_cookie_invalid_nonce' === $core_rest->get_error_code(),
	'stale nonce still 403s core REST routes'
);

$GLOBALS['wp']->query_vars['rest_route'] = '/clasbpro/v1/panel-form';
$clasbpro = apply_filters( 'rest_authentication_errors', null );
$assert( ! is_wp_error( $clasbpro ), 'stale nonce on clasbpro/v1/panel-form is allowed as anonymous' );

$GLOBALS['wp']->query_vars['rest_route'] = '/clasbpro/v1/checkout';
$checkout = apply_filters( 'rest_authentication_errors', null );
$assert( ! is_wp_error( $checkout ), 'stale nonce on clasbpro/v1/checkout is allowed as anonymous' );

if ( null === $prev_nonce ) {
	unset( $_SERVER['HTTP_X_WP_NONCE'] );
} else {
	$_SERVER['HTTP_X_WP_NONCE'] = $prev_nonce;
}
if ( null === $prev_route ) {
	unset( $GLOBALS['wp']->query_vars['rest_route'] );
} else {
	$GLOBALS['wp']->query_vars['rest_route'] = $prev_route;
}

$class_id = 0;
$posts    = get_posts(
	[
		'post_type'      => Constants::CPT_CLASS,
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	]
);
foreach ( $posts as $post ) {
	$data = Helpers::get_class_data( (int) $post->ID );
	if ( $data && ! empty( $data['class_active'] ) ) {
		$class_id = (int) $post->ID;
		break;
	}
}

$assert( $class_id > 0, 'found an active class for panel-form' );
if ( $class_id > 0 ) {
	$request = new \WP_REST_Request( 'GET', '/clasbpro/v1/panel-form' );
	$request->set_param( 'type', 'booking' );
	$request->set_param( 'id', $class_id );
	$response = rest_do_request( $request );
	$data     = $response->get_data();
	$assert( 200 === $response->get_status(), 'panel-form without nonce returns 200' );
	$assert( is_array( $data ) && ! empty( $data['html'] ), 'panel-form without nonce returns html' );
}

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}

\WP_CLI::success( 'panel-form nonce tests passed' );
