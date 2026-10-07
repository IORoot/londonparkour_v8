<?php
/**
 * Custom-amount payments (the quiet /pay/ page). Not a class booking.
 *
 * Stored as clasbpro_booking rows with `_clasbpro_type = custom_payment` so
 * the existing status page + webhook session lookup still work. Class
 * reminders are skipped. Pay confirmation, admin, and optional thank-you
 * emails use the Emails → Pay settings.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Custom_Payments {

	public const META_TYPE = 'custom_payment';

	/** Sanity cap: £10,000. */
	public const MAX_PENCE = 1000000;

	public static function is( int $booking_id ): bool {
		return $booking_id > 0 && self::META_TYPE === (string) get_post_meta( $booking_id, '_clasbpro_type', true );
	}

	/**
	 * @param float|int|string $raw User-entered major-unit amount, possibly with £.
	 */
	public static function parse_major_amount( $raw ): float {
		if ( is_int( $raw ) || is_float( $raw ) ) {
			return (float) $raw;
		}
		$s = str_replace( [ ',', ' ' ], '', (string) $raw );
		$s = preg_replace( '/[^0-9.]/', '', $s );
		return '' === $s ? 0.0 : (float) $s;
	}

	/**
	 * Start Stripe Checkout for a custom amount.
	 *
	 * @param float|int|string $amount_raw
	 * @return array{url: string, booking_id: int}|\WP_Error
	 */
	public static function start_checkout( string $name, string $email, $amount_raw, string $origin = '' ) {
		$name  = sanitize_text_field( $name );
		$email = sanitize_email( $email );
		$origin = Helpers::sanitise_internal_url( $origin, home_url( '/pay/' ) );

		if ( '' === $name ) {
			return new \WP_Error( 'validation', __( 'Please enter your name.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'customer_name' ] );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			return new \WP_Error( 'validation', __( 'Please enter a valid email address.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'customer_email' ] );
		}

		$major = self::parse_major_amount( $amount_raw );
		$pence = Helpers::to_pence( $major );
		if ( $pence < 1 ) {
			return new \WP_Error( 'validation', __( 'Enter the amount you agreed.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'amount' ] );
		}
		if ( $pence > self::MAX_PENCE ) {
			return new \WP_Error( 'validation', __( 'That amount is too large. Write to us if you need to pay more.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'amount' ] );
		}

		if ( '' === Helpers::stripe_secret_key() ) {
			return new \WP_Error( 'stripe_error', __( 'Payments are not configured. Please contact us.', 'class-bookings-with-stripe-pro' ) );
		}

		$rate_limited = REST::checkout_rate_limit_error( $email );
		if ( $rate_limited instanceof \WP_REST_Response ) {
			$data = $rate_limited->get_data();
			$msg  = is_array( $data ) ? (string) ( $data['message'] ?? '' ) : '';
			return new \WP_Error( 'rate_limited', $msg ?: __( 'Please wait a moment before trying again.', 'class-bookings-with-stripe-pro' ) );
		}

		$booking_id = self::create_pending( $name, $email, $pence );
		if ( is_wp_error( $booking_id ) ) {
			return $booking_id;
		}

		$status_token = (string) get_post_meta( $booking_id, '_clasbpro_status_token', true );
		$success_url  = Result_Pages::success_url( '{CHECKOUT_SESSION_ID}', $origin, $status_token );
		$cancel_url   = Result_Pages::cancel_url( $origin, '{CHECKOUT_SESSION_ID}', $status_token );

		try {
			$session = Stripe_Service::create_custom_payment_checkout_session(
				$pence,
				$email,
				$name,
				$booking_id,
				$success_url,
				$cancel_url
			);
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Custom checkout error: ' . $e->getMessage() );
			Bookings::set_status( $booking_id, Bookings::STATUS_EXPIRED );
			return new \WP_Error( 'stripe_error', __( 'Could not start the payment. Please try again.', 'class-bookings-with-stripe-pro' ) );
		}

		Bookings::attach_stripe_session( $booking_id, $session->id );

		return [
			'url'        => (string) $session->url,
			'booking_id' => $booking_id,
		];
	}

	/**
	 * @return int|\WP_Error
	 */
	public static function create_pending( string $name, string $email, int $amount_pence ) {
		$post_id = wp_insert_post(
			[
				'post_type'   => CPT::BOOKING_PT,
				'post_status' => 'publish',
				'post_title'  => sprintf(
					/* translators: 1: customer name, 2: formatted amount */
					__( '%1$s · Custom payment · %2$s', 'class-bookings-with-stripe-pro' ),
					$name ?: __( 'Pending', 'class-bookings-with-stripe-pro' ),
					Helpers::format_stripe_amount( $amount_pence )
				),
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + CLASBOWPRO_HOLD_SECONDS );

		update_post_meta( $post_id, '_clasbpro_type', self::META_TYPE );
		update_post_meta( $post_id, '_clasbpro_class_id', 0 );
		update_post_meta( $post_id, '_clasbpro_class_date', gmdate( 'Y-m-d' ) );
		update_post_meta( $post_id, '_clasbpro_seats', 1 );
		update_post_meta( $post_id, '_clasbpro_customer_name', $name );
		update_post_meta( $post_id, '_clasbpro_customer_email', $email );
		update_post_meta( $post_id, '_clasbpro_amount_total', $amount_pence );
		update_post_meta( $post_id, '_clasbpro_status', Bookings::STATUS_PENDING );
		update_post_meta( $post_id, '_clasbpro_expires_at', $expires_at );
		update_post_meta( $post_id, '_clasbpro_created_gmt', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $post_id, '_clasbpro_status_token', wp_generate_password( 32, false, false ) );

		return (int) $post_id;
	}
}
