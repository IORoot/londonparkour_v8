<?php
/**
 * Mailchimp API integration for optional list opt-ins.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Mailchimp {

	public static function subscribe_booking( int $booking_id ): void {
		if ( ! (bool) Helpers::get_option( 'enable_mailchimp_optin', false ) ) {
			return;
		}
		if ( 1 !== (int) get_post_meta( $booking_id, '_clasbpro_mailchimp_opt_in', true ) ) {
			return;
		}
		if ( get_post_meta( $booking_id, '_clasbpro_mailchimp_synced_at', true ) ) {
			return;
		}

		$email = sanitize_email( (string) get_post_meta( $booking_id, '_clasbpro_customer_email', true ) );
		if ( ! $email || ! is_email( $email ) ) {
			return;
		}

		$api_key     = trim( (string) Helpers::get_option( 'mailchimp_api_key', '' ) );
		$audience_id = trim( (string) Helpers::get_option( 'mailchimp_audience_id', '' ) );
		if ( '' === $api_key || '' === $audience_id ) {
			return;
		}

		$parts      = explode( '-', $api_key );
		$dc         = strtolower( trim( (string) end( $parts ) ) );
		$email_hash = md5( strtolower( $email ) );
		if ( '' === $dc ) {
			return;
		}

		$name = trim( (string) get_post_meta( $booking_id, '_clasbpro_customer_name', true ) );
		$name_parts = preg_split( '/\s+/', $name ) ?: [];
		$first_name = $name_parts[0] ?? '';
		$last_name  = count( $name_parts ) > 1 ? implode( ' ', array_slice( $name_parts, 1 ) ) : '';
		$status     = (bool) Helpers::get_option( 'mailchimp_double_optin', true ) ? 'pending' : 'subscribed';

		$payload = [
			'email_address' => $email,
			'status_if_new' => $status,
			'status'        => $status,
			'merge_fields'  => [
				'FNAME' => $first_name,
				'LNAME' => $last_name,
			],
		];
		$payload = apply_filters( 'clasbpro_mailchimp_payload', $payload, $booking_id );

		$url = sprintf( 'https://%s.api.mailchimp.com/3.0/lists/%s/members/%s', rawurlencode( $dc ), rawurlencode( $audience_id ), $email_hash );
		$res = wp_remote_request(
			$url,
			[
				'method'  => 'PUT',
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
					'Content-Type'  => 'application/json',
				],
				'timeout' => 15,
				'body'    => wp_json_encode( $payload ),
			]
		);

		if ( is_wp_error( $res ) ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Mailchimp subscribe failed: ' . $res->get_error_message() );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code < 200 || $code >= 300 ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Mailchimp subscribe HTTP ' . $code . ' for booking #' . $booking_id );
			return;
		}

		update_post_meta( $booking_id, '_clasbpro_mailchimp_synced_at', gmdate( 'Y-m-d H:i:s' ) );
	}
}
