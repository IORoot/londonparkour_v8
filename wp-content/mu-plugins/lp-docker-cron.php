<?php
/**
 * WP-Cron loopback for local Docker.
 *
 * spawn_cron POSTs to siteurl (http://localhost:8102/wp-cron.php). Inside the
 * wordpress container nothing listens on 8102, so the request never lands and
 * scheduled events (Class Bookings reminder / post-class emails, hold expiry)
 * sit overdue indefinitely. Rewrite the cron URL to the compose service name
 * on port 80. No-op when the site is not on localhost.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'cron_request',
	static function ( $cron_request ) {
		if ( ! is_array( $cron_request ) || empty( $cron_request['url'] ) ) {
			return $cron_request;
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! in_array( $host, array( 'localhost', '127.0.0.1' ), true ) ) {
			return $cron_request;
		}

		$cron_request['url'] = (string) preg_replace(
			'#^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?#',
			'http://wordpress',
			(string) $cron_request['url']
		);

		return $cron_request;
	}
);
