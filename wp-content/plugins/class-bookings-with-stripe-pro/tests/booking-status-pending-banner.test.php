<?php
/**
 * Pending Stripe banner must be dismissible without a full reload.
 *
 * Mobile Safari often ignores window.location.reload() from a fetch timer
 * (no user gesture, or it serves the cached pending HTML). The V8 overlay
 * already paints the confirmed receipt while pending, so the banner has to
 * come off the DOM when /booking-status returns paid.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/booking-status-pending-banner.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$theme   = (string) file_get_contents( get_stylesheet_directory() . '/class-bookings-with-stripe/booking-status.php' );
$js      = (string) file_get_contents( CLASBOWPRO_DIR . 'assets/cbfs-booking.js' );
$results = (string) file_get_contents( CLASBOWPRO_DIR . 'includes/class-result-pages.php' );

$assert( '' !== $theme, 'V8 booking-status overlay is readable' );
$assert( '' !== $js, 'cbfs-booking.js is readable' );

$assert(
	false !== strpos( $theme, 'cbfs-status__pending-text' ),
	'V8 settling banner uses cbfs-status__pending-text so the poller can update and remove it'
);
$assert(
	false !== strpos( $theme, 'data-cbfs-pending-banner' ),
	'V8 settling banner has data-cbfs-pending-banner for the poller'
);

$assert(
	false !== strpos( $js, "data.status === 'paid'" ),
	'poller reacts when booking-status returns paid'
);
$assert(
	false !== strpos( $js, 'dismissPendingBanner' )
	&& false !== strpos( $js, '[data-cbfs-pending-banner]' ),
	'poller removes pending UI from the DOM when paid instead of waiting on reload'
);
$assert(
	false !== strpos( $js, 'cbfs_paid' ),
	'poller cache-busts the status URL after paid instead of location.reload() only'
);
$assert(
	false !== strpos( $js, 'pageshow' ) && false !== strpos( $js, 'event.persisted' ),
	'poller restarts after a back-forward cache restore'
);
$assert(
	false !== strpos( $results, 'nocache_headers' ),
	'result pages send nocache headers so mobile Safari cannot keep the pending HTML'
);

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'booking status pending banner' );
