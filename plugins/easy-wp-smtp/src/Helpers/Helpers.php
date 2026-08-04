<?php

namespace EasyWPSMTP\Helpers;

use EasyWPSMTP\Options;
use EasyWPSMTP\WP;
use WP_Error;

/**
 * Class with all the misc helper functions that don't belong elsewhere.
 *
 * @since 2.0.0
 */
class Helpers {

	/**
	 * Check if the current active mailer has email send confirmation functionality.
	 *
	 * @since 2.1.0
	 *
	 * @return bool
	 */
	public static function mailer_without_send_confirmation() {

		return ! in_array(
			Options::init()->get( 'mail', 'mailer' ),
			[
				'sendlayer',
				'smtpcom',
				'sendinblue',
				'mailgun',
				'postmark',
				'smtp2go',
				'sparkpost',
				'mailjet',
				'elasticemail',
				'mailersend',
				'mandrill',
				'resend',
			],
			true
		);
	}

	/**
	 * Include mbstring polyfill.
	 *
	 * @since 2.0.0
	 */
	public static function include_mbstring_polyfill() {

		static $included = false;

		if ( $included === true ) {
			return;
		}

		require_once easy_wp_smtp()->plugin_path . '/vendor_prefixed/symfony/polyfill-mbstring/Mbstring.php';
		require_once easy_wp_smtp()->plugin_path . '/vendor_prefixed/symfony/polyfill-mbstring/bootstrap.php';

		$included = true;
	}

	/**
	 * Test if the REST API is accessible.
	 *
	 * @since 2.0.0
	 *
	 * @return true|\WP_Error
	 */
	public static function test_rest_availability() {

		$headers = [
			'Cache-Control' => 'no-cache',
		];

		/** This filter is documented in wp-includes/class-wp-http-streams.php */
		$sslverify = apply_filters( 'https_local_ssl_verify', false );

		$url = rest_url( 'easy-wp-smtp/v1' );

		$response = wp_remote_get(
			$url,
			[
				'headers'   => $headers,
				'sslverify' => $sslverify,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_body( $response ) );
		}

		return true;
	}

	/**
	 * Get string size in bytes.
	 *
	 * @since 2.0.0
	 *
	 * @param string $str String.
	 *
	 * @return int
	 */
	public static function strsize( $str ) {

		if ( ! function_exists( 'mb_strlen' ) ) {
			self::include_mbstring_polyfill();
		}

		return mb_strlen( $str, '8bit' );
	}

	/**
	 * Format a variable for debug-style output as an inline <code> block.
	 *
	 * Bools and empty values are rendered via var_dump (which shows the type);
	 * everything else via print_r. Line breaks are stripped so the output fits
	 * on a single rendered line.
	 *
	 * @since 2.15.0
	 *
	 * @param mixed $var Variable to format.
	 *
	 * @return string
	 */
	public static function pvar( $var = '' ) {

		ob_start();

		echo '<code>';

		if ( is_bool( $var ) || empty( $var ) ) {
			var_dump( $var );
		} else {
			print_r( $var );
		}

		echo '</code>';

		$output = ob_get_clean();

		return str_replace( [ "\r\n", "\r", "\n" ], '', $output );
	}

	/**
	 * Format error message.
	 *
	 * @since 2.0.0
	 *
	 * @param string $message     Error message.
	 * @param string $code        Error code.
	 * @param string $description Error description.
	 *
	 * @return string
	 */
	public static function format_error_message( $message, $code = '', $description = '' ) {

		$error_text = '';

		if ( ! empty( $code ) ) {
			$error_text .= $code . ': ';
		}

		if ( ! is_string( $message ) ) {
			$error_text .= wp_json_encode( $message );
		} else {
			$error_text .= $message;
		}

		if ( ! empty( $description ) ) {
			$error_text .= WP::EOL . $description;
		}

		return $error_text;
	}

	/**
	 * Whether it's allowed to send emails from the website's current domain.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_domain_blocked() {

		$options = Options::init();

		$check_domain    = $options->get( 'general', 'domain_check' );
		$allowed_domains = $options->get( 'general', 'domain_check_allowed_domains' );

		if ( $check_domain && ! empty( $allowed_domains ) ) {
			$allowed_domains = explode( ',', $allowed_domains );
			$site_domain     = wp_parse_url( get_site_url(), PHP_URL_HOST );
			$match_found     = false;

			foreach ( $allowed_domains as $domain ) {
				if ( strtolower( trim( $domain ) ) === strtolower( trim( $site_domain ) ) ) {
					$match_found = true;

					break;
				}
			}

			if ( ! $match_found ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the default user agent.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function get_default_user_agent() {

		$license_type = easy_wp_smtp()->get_license_type();

		return 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) . '; EasyWPSMTP/' . $license_type . '-' . EasyWPSMTP_PLUGIN_VERSION;
	}

	/**
	 * Import Plugin_Upgrader class from core.
	 *
	 * @since 2.3.0
	 */
	public static function include_plugin_upgrader() {

		/** \WP_Upgrader class */
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		/** \Plugin_Upgrader class */
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
	}

	/**
	 * Whether the current request is a WP CLI request.
	 *
	 * @since 2.3.0
	 */
	public static function is_wp_cli() {

		return defined( 'WP_CLI' ) && WP_CLI;
	}
}
