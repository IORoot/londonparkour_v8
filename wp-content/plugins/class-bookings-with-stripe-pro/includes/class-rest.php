<?php
/**
 * REST endpoints: create checkout, Stripe webhook, booking status poll.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class REST {

	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register' ] );
	}

	public static function register(): void {
		register_rest_route( CLASBOWPRO_REST_NS, '/checkout', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'create_checkout' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id'       => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'class_date'     => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				'slot_rule_id'   => [ 'required' => false, 'sanitize_callback' => 'sanitize_key' ],
				'seats'          => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'customer_name'  => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'customer_email' => [ 'required' => false, 'sanitize_callback' => 'sanitize_email' ],
				'origin_url'     => [ 'required' => false, 'sanitize_callback' => 'esc_url_raw' ],
				'waiver_accepted' => [ 'required' => false ],
				'mailchimp_opt_in' => [ 'required' => false ],
				'extra_fields'   => [ 'required' => false ],
				'use_pack'       => [ 'required' => false ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/pack-checkout', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'create_pack_checkout' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'pack_id'        => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'customer_name'  => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'customer_email' => [ 'required' => false, 'sanitize_callback' => 'sanitize_email' ],
				'origin_url'     => [ 'required' => false, 'sanitize_callback' => 'esc_url_raw' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/pack-status', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'pack_status' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id'       => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'customer_email' => [ 'required' => false, 'sanitize_callback' => 'sanitize_email' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/pack-purchase-status', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'pack_purchase_status' ],
			'permission_callback' => [ self::class, 'can_view_pack_purchase_status' ],
			'args'                => [
				'session'  => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'token'    => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'purchase' => [ 'required' => false, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/pack-attach', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'pack_attach' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'code'           => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				'customer_email' => [ 'required' => false, 'sanitize_callback' => 'sanitize_email' ],
				'class_id'       => [ 'required' => false, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/pack-clear', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'pack_clear' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/pack-restore', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'pack_restore' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'token'          => [
					'required'          => true,
					'sanitize_callback' => static function ( $value ) {
						return is_string( $value ) ? trim( wp_unslash( $value ) ) : '';
					},
				],
				'class_id'       => [ 'required' => false, 'sanitize_callback' => 'absint' ],
				'customer_email' => [ 'required' => false, 'sanitize_callback' => 'sanitize_email' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/stripe-webhook', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'handle_webhook' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/booking-status', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'booking_status' ],
			'permission_callback' => [ self::class, 'can_view_booking_status' ],
			'args'                => [
				'session' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				'token'   => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/availability', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'availability' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/appointment-calendar', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'appointment_calendar' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'year'     => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'month'    => [ 'required' => true, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/appointment-slots', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'appointment_slots' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'date'     => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/class-calendar', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'class_calendar' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'year'     => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'month'    => [ 'required' => true, 'sanitize_callback' => 'absint' ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/schedule-calendar', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'schedule_calendar' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'week'       => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
				'class_ids'  => [ 'required' => false, 'sanitize_callback' => [ self::class, 'sanitize_class_ids_param' ] ],
			],
		] );

		register_rest_route( CLASBOWPRO_REST_NS, '/schedule-booking-form', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'schedule_booking_form' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'class_id'           => [ 'required' => true, 'sanitize_callback' => 'absint' ],
				'preset_date'        => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'preset_slot_rule_id' => [ 'required' => false, 'sanitize_callback' => 'sanitize_key' ],
			],
		] );
	}

	/**
	 * @param mixed $value
	 * @return array<int, int>
	 */
	public static function sanitize_class_ids_param( $value ): array {
		if ( is_array( $value ) ) {
			return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
		}
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			$parts = preg_split( '/\s*,\s*/', $value ) ?: [];
			return array_values( array_unique( array_filter( array_map( 'absint', $parts ) ) ) );
		}
		return [];
	}

	/**
	 * POST /checkout — validates, creates a pending booking with soft-hold, then a Stripe Checkout Session.
	 */
	public static function create_checkout( \WP_REST_Request $request ) {
		$class_id     = (int) $request['class_id'];
		$class_date   = Helpers::normalise_date_string( (string) $request['class_date'] );
		$slot_rule_id = sanitize_key( (string) ( $request['slot_rule_id'] ?? '' ) );
		$seats        = max( 1, (int) $request['seats'] );
		$name       = (string) ( $request['customer_name'] ?? '' );
		$email      = (string) ( $request['customer_email'] ?? '' );
		$origin     = Helpers::sanitise_internal_url( (string) ( $request['origin_url'] ?? '' ), home_url( '/' ) );
		$waiver_accepted  = rest_sanitize_boolean( $request['waiver_accepted'] ?? false );
		$mailchimp_opt_in = rest_sanitize_boolean( $request['mailchimp_opt_in'] ?? false );
		$use_pack         = rest_sanitize_boolean( $request['use_pack'] ?? false );
		$extra_fields_raw = is_array( $request['extra_fields'] ?? null ) ? (array) $request['extra_fields'] : [];

		if ( $use_pack ) {
			$seats = 1;
		}

		if ( $class_id <= 0 ) {
			return self::error( 422, 'validation', __( 'Missing class.', 'class-bookings-with-stripe-pro' ) );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			return self::error( 422, 'validation', __( 'Please enter a valid email address.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'customer_email' ] );
		}
		if ( '' === $name ) {
			return self::error( 422, 'validation', __( 'Please enter your name.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'customer_name' ] );
		}

		$rate_limited = self::checkout_rate_limit_error( $email );
		if ( $rate_limited ) {
			return $rate_limited;
		}
		if ( (bool) Helpers::get_option( 'enable_waiver', false ) && ! $waiver_accepted ) {
			return self::error( 422, 'validation', __( 'Please accept the waiver before continuing to payment.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'waiver_accepted' ] );
		}

		$class_data = Helpers::get_class_data( $class_id );
		if ( ! $class_data ) {
			return self::error( 404, 'class_not_found', __( 'That class could not be found.', 'class-bookings-with-stripe-pro' ) );
		}

		if ( empty( $class_data['class_active'] ) ) {
			return self::error( 409, 'class_inactive', __( 'Bookings for this class are currently unavailable.', 'class-bookings-with-stripe-pro' ) );
		}
		$extra_fields = Extra_Fields::validate_submission( $class_id, $extra_fields_raw );
		if ( is_wp_error( $extra_fields ) ) {
			return self::error(
				422,
				'validation',
				$extra_fields->get_error_message(),
				[ 'field' => (string) ( $extra_fields->get_error_data()['field'] ?? '' ) ]
			);
		}

		if ( ! empty( $class_data['is_appointments'] ) ) {
			if ( '' === $slot_rule_id ) {
				return self::error( 422, 'validation', __( 'Please choose an appointment slot.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'slot_rule_id' ] );
			}
			if ( empty( $class_data['has_slot_rules'] ) ) {
				return self::error( 409, 'class_inactive', __( 'No appointment slots are configured for this class.', 'class-bookings-with-stripe-pro' ) );
			}
		}

		$reason = Bookings::validate_date( $class_data, $class_date, $slot_rule_id );
		if ( '' !== $reason ) {
			return self::error( 409, $reason, __( 'That date is no longer available. Please choose another.', 'class-bookings-with-stripe-pro' ) );
		}

		$remaining = Bookings::seats_remaining( $class_data, $class_date, $slot_rule_id );
		if ( $remaining <= 0 ) {
			return self::error( 409, 'capacity_full', __( 'Sorry, this class just filled up. Please choose another date.', 'class-bookings-with-stripe-pro' ) );
		}
		if ( $seats < 1 || $seats > $remaining ) {
			return self::error(
				409,
				'capacity_full',
				sprintf(
					/* translators: %d: seats remaining */
					_n( 'Only %d seat is left for that date.', 'Only %d seats are left for that date.', $remaining, 'class-bookings-with-stripe-pro' ),
					$remaining
				),
				[ 'remaining' => $remaining ]
			);
		}

		if ( '' === Helpers::stripe_secret_key() ) {
			return self::error( 502, 'stripe_error', __( 'Payments are not configured. Please contact us.', 'class-bookings-with-stripe-pro' ) );
		}

		$checkout_class_data = $class_data;
		$slot_snapshot       = [];
		if ( ! empty( $class_data['is_appointments'] ) ) {
			$rule = Slot_Rules::find_rule( $class_data, $slot_rule_id );
			if ( ! $rule ) {
				return self::error( 409, 'date_invalid', __( 'That slot is no longer available. Please choose another.', 'class-bookings-with-stripe-pro' ) );
			}
			$slot_snapshot       = Slot_Rules::build_snapshot( $class_data, $rule, $class_date );
			$checkout_class_data = array_merge( $class_data, [
				'start_time' => (string) ( $slot_snapshot['start_time'] ?? '' ),
				'location'   => (string) ( $slot_snapshot['location'] ?? '' ),
				'duration'   => (int) ( $slot_snapshot['duration_minutes'] ?? 0 ),
				'price'      => (float) ( $slot_snapshot['price_gbp'] ?? 0 ),
			] );
		}

		$unit_pence   = Helpers::to_pence( $checkout_class_data['price'] );
		$amount_total = $unit_pence * $seats;
		$pack_promo   = '';

		if ( $use_pack ) {
			$pack_check = Packs::validate_for_checkout( $class_id, $email );
			if ( is_wp_error( $pack_check ) ) {
				return self::error( 409, $pack_check->get_error_code(), $pack_check->get_error_message() );
			}
			$pack_promo = (string) $pack_check['promo_id'];
		}

		$booking_id = Bookings::create_pending_booking( [
			'class_id'        => $class_id,
			'class_date'      => $class_date,
			'slot_rule_id'    => $slot_rule_id,
			'slot_snapshot'   => $slot_snapshot,
			'seats'           => $seats,
			'customer_name'   => $name,
			'customer_email'  => $email,
			'amount_pence'    => $amount_total,
			'waiver_accepted' => $waiver_accepted ? 1 : 0,
			'mailchimp_opt_in' => $mailchimp_opt_in ? 1 : 0,
			'extra_fields'     => $extra_fields,
		] );

		if ( is_wp_error( $booking_id ) ) {
			return self::error( 500, 'internal', __( 'Could not start a booking. Please try again.', 'class-bookings-with-stripe-pro' ) );
		}

		if ( $pack_promo ) {
			update_post_meta( $booking_id, '_clasbpro_pack_promo_id', $pack_promo );
		}

		$status_token = (string) get_post_meta( $booking_id, '_clasbpro_status_token', true );
		$success_url  = Result_Pages::success_url( '{CHECKOUT_SESSION_ID}', $origin, $status_token );
		$cancel_url   = Result_Pages::cancel_url( $origin );

		try {
			if ( $pack_promo ) {
				$session = Stripe_Service::create_pack_booking_checkout_session(
					$checkout_class_data,
					$class_date,
					$unit_pence,
					$email,
					$name,
					$booking_id,
					$pack_promo,
					$success_url,
					$cancel_url
				);
			} else {
				$session = Stripe_Service::create_checkout_session(
					$checkout_class_data,
					$class_date,
					$seats,
					$unit_pence,
					$email,
					$name,
					$booking_id,
					$success_url,
					$cancel_url
				);
			}
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Stripe API error: ' . $e->getMessage() );
			Bookings::set_status( $booking_id, Bookings::STATUS_EXPIRED );
			return self::error( 502, 'stripe_error', __( 'Could not connect to Stripe. Please try again.', 'class-bookings-with-stripe-pro' ) );
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Checkout error: ' . $e->getMessage() );
			Bookings::set_status( $booking_id, Bookings::STATUS_EXPIRED );
			return self::error( 502, 'stripe_error', __( 'Could not start the payment. Please try again.', 'class-bookings-with-stripe-pro' ) );
		}

		Bookings::attach_stripe_session( $booking_id, $session->id );

		return new \WP_REST_Response( [
			'url'        => $session->url,
			'booking_id' => $booking_id,
		], 200 );
	}

	/**
	 * POST /pack-checkout — buy a class pack via Stripe Checkout.
	 */
	public static function create_pack_checkout( \WP_REST_Request $request ) {
		$pack_id = (int) $request['pack_id'];
		$name    = (string) ( $request['customer_name'] ?? '' );
		$email   = (string) ( $request['customer_email'] ?? '' );
		$origin  = Helpers::sanitise_internal_url( (string) ( $request['origin_url'] ?? '' ), home_url( '/' ) );

		if ( $pack_id <= 0 ) {
			return self::error( 422, 'validation', __( 'Missing coupon.', 'class-bookings-with-stripe-pro' ) );
		}
		if ( '' === $email || ! is_email( $email ) ) {
			return self::error( 422, 'validation', __( 'Please enter a valid email address.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'customer_email' ] );
		}
		if ( '' === $name ) {
			return self::error( 422, 'validation', __( 'Please enter your name.', 'class-bookings-with-stripe-pro' ), [ 'field' => 'customer_name' ] );
		}

		$rate_limited = self::checkout_rate_limit_error( $email );
		if ( $rate_limited ) {
			return $rate_limited;
		}

		$pack_post = get_post( $pack_id );
		$pack      = Packs::get_pack_data( $pack_id );
		if ( ! $pack_post || 'publish' !== $pack_post->post_status || ! $pack || empty( $pack['active'] ) ) {
			return self::error( 404, 'pack_not_found', __( 'That coupon could not be found.', 'class-bookings-with-stripe-pro' ) );
		}
		if ( $pack['price'] <= 0 || empty( $pack['class_ids'] ) ) {
			return self::error( 409, 'pack_invalid', __( 'That coupon is not available for purchase.', 'class-bookings-with-stripe-pro' ) );
		}
		if ( '' === Helpers::stripe_secret_key() ) {
			return self::error( 502, 'stripe_error', __( 'Payments are not configured. Please contact us.', 'class-bookings-with-stripe-pro' ) );
		}

		$amount_pence = Helpers::to_pence( $pack['price'] );
		$purchase_id  = Packs::create_pending_purchase( [
			'pack_id'        => $pack_id,
			'customer_name'  => $name,
			'customer_email' => $email,
			'amount_pence'   => $amount_pence,
		] );
		if ( is_wp_error( $purchase_id ) ) {
			return self::error( 500, 'internal', __( 'Could not start the coupon purchase. Please try again.', 'class-bookings-with-stripe-pro' ) );
		}

		$status_token = (string) get_post_meta( $purchase_id, '_clasbpro_status_token', true );
		$success_url  = Packs::success_url_with_claim(
			Result_Pages::success_url( '{CHECKOUT_SESSION_ID}', $origin, $status_token ),
			$purchase_id
		);
		$cancel_url = Result_Pages::cancel_url( $origin );

		try {
			$session = Stripe_Service::create_pack_purchase_checkout_session(
				$pack,
				$email,
				$name,
				$purchase_id,
				$success_url,
				$cancel_url
			);
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Pack checkout error: ' . $e->getMessage() );
			Packs::set_purchase_status( $purchase_id, Packs::STATUS_EXPIRED );
			return self::error( 502, 'stripe_error', __( 'Could not start the payment. Please try again.', 'class-bookings-with-stripe-pro' ) );
		}

		Packs::attach_stripe_session( $purchase_id, $session->id );

		return new \WP_REST_Response( [
			'url'         => $session->url,
			'purchase_id' => $purchase_id,
		], 200 );
	}

	/**
	 * GET /pack-status — active pack cookie status for a class booking form.
	 */
	public static function pack_status( \WP_REST_Request $request ) {
		$class_id = (int) $request['class_id'];
		$email    = (string) ( $request['customer_email'] ?? '' );
		$status   = Packs::status_for_class( $class_id, $email );
		return new \WP_REST_Response( $status, 200 );
	}

	/**
	 * POST /pack-attach — attach a pack by promotion code (sets signed cookie).
	 */
	public static function pack_attach( \WP_REST_Request $request ) {
		$code     = (string) $request['code'];
		$email    = (string) ( $request['customer_email'] ?? '' );
		$class_id = (int) ( $request['class_id'] ?? 0 );
		$result   = Packs::attach_by_code( $code, $email );
		if ( empty( $result['ok'] ) ) {
			return self::error( 422, 'pack_attach_failed', (string) ( $result['message'] ?? __( 'Could not attach that coupon.', 'class-bookings-with-stripe-pro' ) ) );
		}
		$status = $class_id > 0
			? Packs::status_for_class( $class_id, $email ?: (string) ( $result['email'] ?? '' ) )
			: $result;
		return new \WP_REST_Response( $status, 200 );
	}

	/**
	 * POST /pack-clear — clear the active pack cookie.
	 */
	public static function pack_clear( \WP_REST_Request $request ) {
		unset( $request );
		Packs::clear_active_cookie();
		return new \WP_REST_Response( [ 'ok' => true ], 200 );
	}

	/**
	 * POST /pack-restore — re-apply a signed restore token and return pack status.
	 */
	public static function pack_restore( \WP_REST_Request $request ) {
		$token    = (string) $request['token'];
		$class_id = (int) ( $request['class_id'] ?? 0 );
		$email    = (string) ( $request['customer_email'] ?? '' );
		$status   = Packs::restore_from_token( $token, $class_id, $email );
		if ( empty( $status['ok'] ) && empty( $status['recognised'] ) ) {
			return self::error( 422, 'pack_restore_failed', (string) ( $status['message'] ?? __( 'Could not restore that coupon.', 'class-bookings-with-stripe-pro' ) ) );
		}
		return new \WP_REST_Response( $status, 200 );
	}

	/**
	 * POST /stripe-webhook — verifies signature and dispatches events.
	 */
	public static function handle_webhook( \WP_REST_Request $request ) {
		$payload    = $request->get_body();
		$sig_header = $request->get_header( 'stripe_signature' );
		if ( ! $sig_header ) {
			$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] )
				? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_STRIPE_SIGNATURE'] ) )
				: '';
		}

		if ( '' === Helpers::stripe_webhook_secret() ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Webhook rejected: signing secret not configured in Settings → Stripe.' );
			return new \WP_REST_Response( [ 'error' => 'webhook_secret_missing' ], 400 );
		}

		try {
			$event = Stripe_Service::verify_webhook( $payload, (string) $sig_header );
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Webhook signature failed: ' . $e->getMessage() );
			return new \WP_REST_Response( [ 'error' => 'invalid_signature' ], 400 );
		} catch ( \UnexpectedValueException $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Webhook payload invalid: ' . $e->getMessage() );
			return new \WP_REST_Response( [ 'error' => 'invalid_payload' ], 400 );
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Webhook error: ' . $e->getMessage() );
			return new \WP_REST_Response( [ 'error' => 'webhook_error' ], 400 );
		}

		switch ( $event->type ) {
			case 'checkout.session.completed':
				self::handle_session_completed( $event->data->object );
				break;
			case 'checkout.session.expired':
			case 'checkout.session.async_payment_failed':
				self::handle_session_expired( $event->data->object );
				break;
		}

		return new \WP_REST_Response( [ 'received' => true ], 200 );
	}

	private static function handle_session_completed( $session ): void {
		$session_type = '';
		if ( is_object( $session ) && isset( $session->metadata->clasbpro_type ) ) {
			$session_type = (string) $session->metadata->clasbpro_type;
		}
		if ( Packs::META_TYPE_PURCHASE === $session_type ) {
			Packs::handle_pack_purchase_completed( $session );
			return;
		}

		$booking_id = self::resolve_booking_from_session( $session );
		if ( ! $booking_id ) {
			$session_id = is_object( $session ) ? (string) ( $session->id ?? '' ) : '';
			$meta_booking_id = is_object( $session ) && isset( $session->metadata->booking_id ) ? (string) $session->metadata->booking_id : '';
			Helpers::debug_log(
				'[class-bookings-with-stripe-pro] checkout.session.completed could not resolve booking. session_id=' .
				$session_id .
				' metadata.booking_id=' .
				$meta_booking_id
			);
			return;
		}
		if ( Bookings::STATUS_PAID === Bookings::get_status( $booking_id ) ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] checkout.session.completed already paid. booking_id=' . $booking_id );
			return; // idempotent
		}

		$payment_status = is_object( $session ) ? ( $session->payment_status ?? '' ) : '';
		if ( 'paid' !== $payment_status && 'no_payment_required' !== $payment_status ) {
			Helpers::debug_log(
				'[class-bookings-with-stripe-pro] checkout.session.completed ignored due to payment_status=' .
				(string) $payment_status .
				' booking_id=' .
				$booking_id
			);
			return;
		}

		// Pull customer details from Stripe (Checkout collects them on hosted page).
		$name = (string) get_post_meta( $booking_id, '_clasbpro_customer_name', true );
		$email = (string) get_post_meta( $booking_id, '_clasbpro_customer_email', true );

		$details = is_object( $session ) ? ( $session->customer_details ?? null ) : null;
		if ( $details ) {
			if ( ! empty( $details->name ) ) {
				$name = sanitize_text_field( $details->name );
			}
			if ( ! empty( $details->email ) ) {
				$email = sanitize_email( $details->email );
			}
		}

		update_post_meta( $booking_id, '_clasbpro_customer_name', $name );
		update_post_meta( $booking_id, '_clasbpro_customer_email', $email );

		$amount_total = is_object( $session ) ? (int) ( $session->amount_total ?? 0 ) : 0;
		$used_pack    = (string) get_post_meta( $booking_id, '_clasbpro_pack_promo_id', true );
		if ( $amount_total > 0 || '' !== $used_pack ) {
			update_post_meta( $booking_id, '_clasbpro_amount_total', $amount_total );
		}
		if ( is_object( $session ) && ! empty( $session->metadata->clasbpro_pack_promo_id ) ) {
			update_post_meta( $booking_id, '_clasbpro_pack_promo_id', (string) $session->metadata->clasbpro_pack_promo_id );
		}

		$payment_intent = '';
		if ( is_object( $session ) ) {
			$payment_intent = is_string( $session->payment_intent ?? null )
				? $session->payment_intent
				: ( is_object( $session->payment_intent ?? null ) ? $session->payment_intent->id : '' );
		}
		if ( $payment_intent ) {
			update_post_meta( $booking_id, '_clasbpro_stripe_payment_intent', $payment_intent );
		}

		// Update post title to reflect customer.
		wp_update_post( [
			'ID'         => $booking_id,
			'post_title' => sprintf(
				'%s · %s · %s',
				$name ?: __( 'Customer', 'class-bookings-with-stripe-pro' ),
				get_the_title( (int) get_post_meta( $booking_id, '_clasbpro_class_id', true ) ),
				Helpers::format_date( (string) get_post_meta( $booking_id, '_clasbpro_class_date', true ) )
			),
		] );

		Bookings::set_status( $booking_id, Bookings::STATUS_PAID );
		Mailchimp::subscribe_booking( $booking_id );

		Emails::send_for_booking( $booking_id );
		Scheduled_Emails::queue_for_booking( $booking_id );
		Helpers::debug_log( '[class-bookings-with-stripe-pro] checkout.session.completed marked paid. booking_id=' . $booking_id );
	}

	private static function handle_session_expired( $session ): void {
		$session_type = '';
		if ( is_object( $session ) && isset( $session->metadata->clasbpro_type ) ) {
			$session_type = (string) $session->metadata->clasbpro_type;
		}
		if ( Packs::META_TYPE_PURCHASE === $session_type ) {
			Packs::handle_pack_purchase_expired( $session );
			return;
		}

		$booking_id = self::resolve_booking_from_session( $session );
		if ( ! $booking_id ) {
			return;
		}
		if ( Bookings::STATUS_PAID === Bookings::get_status( $booking_id ) ) {
			return;
		}
		Bookings::set_status( $booking_id, Bookings::STATUS_EXPIRED );
	}

	private static function resolve_booking_from_session( $session ): int {
		if ( ! is_object( $session ) ) {
			return 0;
		}
		$session_id = (string) ( $session->id ?? '' );
		$booking_id = Bookings::find_by_stripe_session( $session_id );
		if ( $booking_id ) {
			return $booking_id;
		}
		// Fallback: metadata.booking_id
		$meta = $session->metadata ?? null;
		if ( $meta && ! empty( $meta->booking_id ) ) {
			$candidate = (int) $meta->booking_id;
			$post = get_post( $candidate );
			if ( $post && CPT::BOOKING_PT === $post->post_type ) {
				return $candidate;
			}
		}
		return 0;
	}

	/**
	 * Authorize booking-status: site admins, or holder of the per-booking status token.
	 */
	public static function can_view_booking_status( \WP_REST_Request $request ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$session_id = (string) $request->get_param( 'session' );
		$token      = (string) $request->get_param( 'token' );
		if ( '' === $session_id || '' === $token ) {
			return false;
		}

		$booking_id = Bookings::find_by_stripe_session( $session_id );
		if ( ! $booking_id ) {
			return false;
		}

		return Bookings::verify_status_token( $booking_id, $token );
	}

	/**
	 * GET /booking-status?session=cs_...&token=...
	 */
	public static function booking_status( \WP_REST_Request $request ) {
		$session_id = (string) $request['session'];
		$booking_id = Bookings::find_by_stripe_session( $session_id );
		if ( ! $booking_id || Bookings::STATUS_PENDING === Bookings::get_status( $booking_id ) ) {
			$booking_id = self::reconcile_session_from_stripe( $session_id ) ?: $booking_id;
		}
		if ( ! $booking_id ) {
			return new \WP_REST_Response( [ 'status' => 'pending' ], 200 );
		}

		$meta       = Bookings::get_meta( $booking_id );
		$class_data = Helpers::get_class_data( $meta['class_id'] );
		$display    = Bookings::get_booking_display_context( $booking_id, $class_data );

		return new \WP_REST_Response( [
			'status'        => $meta['status'],
			'booking_id'    => $booking_id,
			'class_name'    => $class_data['name'] ?? '',
			'class_date'    => Helpers::format_date( $meta['class_date'] ),
			'class_time'    => Helpers::format_time( $display['start_time'] ),
			'location'      => $display['location'],
			'seats'         => $meta['seats'],
			'amount_total'  => Helpers::format_stripe_amount( (int) $meta['amount_total_pence'] ),
			'customer_name' => $meta['customer_name'],
			'kind'          => 'booking',
		], 200 );
	}

	/**
	 * Authorize pack-purchase-status: site admins or holder of the per-purchase status token.
	 */
	public static function can_view_pack_purchase_status( \WP_REST_Request $request ): bool {
		$session_id  = (string) $request->get_param( 'session' );
		$token       = (string) $request->get_param( 'token' );
		$purchase_id = (int) $request->get_param( 'purchase' );

		if ( $purchase_id <= 0 && '' !== $session_id ) {
			$purchase_id = Packs::find_purchase_by_session( $session_id );
		}

		return Packs::can_view_purchase_status( $purchase_id, $session_id, $token );
	}

	/**
	 * GET /pack-purchase-status?session=cs_...&token=...&purchase=20
	 */
	public static function pack_purchase_status( \WP_REST_Request $request ) {
		$session_id  = (string) $request['session'];
		$purchase_id = (int) ( $request['purchase'] ?? 0 );
		if ( $purchase_id <= 0 ) {
			$purchase_id = Packs::find_purchase_by_session( $session_id );
		}
		if ( $purchase_id && Packs::STATUS_PENDING === Packs::get_purchase_status( $purchase_id ) ) {
			Packs::reconcile_purchase_from_stripe( $purchase_id );
		}
		if ( ! $purchase_id ) {
			return new \WP_REST_Response( [ 'status' => 'pending', 'kind' => 'coupon' ], 200 );
		}

		$display = Packs::get_purchase_display( $purchase_id );
		if ( ! $display ) {
			return new \WP_REST_Response( [ 'status' => 'pending', 'kind' => 'coupon' ], 200 );
		}

		return new \WP_REST_Response( $display, 200 );
	}

	/**
	 * Fallback when webhooks are delayed/missed: fetch session directly from Stripe
	 * and apply the same completion logic.
	 */
	private static function reconcile_session_from_stripe( string $session_id ): ?int {
		if ( '' === trim( $session_id ) ) {
			return null;
		}
		try {
			$session = Stripe_Service::retrieve_checkout_session( $session_id );
			if ( ! $session ) {
				return null;
			}
			$payment_status = is_object( $session ) ? (string) ( $session->payment_status ?? '' ) : '';
			$booking_id = self::resolve_booking_from_session( $session );
			if ( ! $booking_id ) {
				return null;
			}
			if ( 'paid' === $payment_status || 'no_payment_required' === $payment_status ) {
				self::handle_session_completed( $session );
			}
			return $booking_id;
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Stripe status reconcile failed: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * GET /availability?class_id=123 — used by the form to refresh after errors.
	 */
	public static function availability( \WP_REST_Request $request ) {
		$class_id   = (int) $request['class_id'];
		$class_data = Helpers::get_class_data( $class_id );
		if ( ! $class_data ) {
			return new \WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}
		$dates_count = ! empty( $class_data['is_one_off_event'] )
			? 1
			: Helpers::class_upcoming_dates_count( $class_data );
		$dates = Bookings::next_available_dates( $class_data, $dates_count );
		return new \WP_REST_Response( [
			'class_active' => (bool) $class_data['class_active'],
			'dates'        => $dates,
		], 200 );
	}

	/**
	 * GET /appointment-calendar?class_id=&year=&month=
	 */
	public static function appointment_calendar( \WP_REST_Request $request ) {
		$class_id   = (int) $request['class_id'];
		$year       = max( 1970, (int) $request['year'] );
		$month      = max( 1, min( 12, (int) $request['month'] ) );
		$class_data = Helpers::get_class_data( $class_id );

		if ( ! $class_data || empty( $class_data['is_appointments'] ) ) {
			return new \WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}

		return new \WP_REST_Response( [
			'class_active' => (bool) $class_data['class_active'],
			'year'         => $year,
			'month'        => $month,
			'days'         => Slot_Rules::month_availability( $class_data, $year, $month ),
		], 200 );
	}

	/**
	 * GET /appointment-slots?class_id=&date=YYYY-MM-DD
	 */
	public static function appointment_slots( \WP_REST_Request $request ) {
		$class_id   = (int) $request['class_id'];
		$date       = Helpers::normalise_date_string( (string) $request['date'] );
		$class_data = Helpers::get_class_data( $class_id );

		if ( ! $class_data || empty( $class_data['is_appointments'] ) || '' === $date ) {
			return new \WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}

		$slots = Slot_Rules::slots_for_date( $class_data, $date );

		return new \WP_REST_Response( [
			'date'  => $date,
			'slots' => $slots,
		], 200 );
	}

	/**
	 * GET /class-calendar?class_id=&year=&month=
	 */
	public static function class_calendar( \WP_REST_Request $request ) {
		$class_id   = (int) $request['class_id'];
		$year       = max( 1970, (int) $request['year'] );
		$month      = max( 1, min( 12, (int) $request['month'] ) );
		$class_data = Helpers::get_class_data( $class_id );

		if ( ! $class_data || empty( $class_data['uses_date_calendar'] ) ) {
			return new \WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}

		$days = Bookings::recurring_month_calendar_days( $class_data, $year, $month );
		$has_selectable_in_month = false;
		foreach ( $days as $day ) {
			if ( ! empty( $day['selectable'] ) ) {
				$has_selectable_in_month = true;
				break;
			}
		}

		return new \WP_REST_Response( [
			'class_active'            => (bool) $class_data['class_active'],
			'year'                    => $year,
			'month'                   => $month,
			'days'                    => $days,
			'has_any_bookable'        => Bookings::has_bookable_recurring_dates_in_calendar_window( $class_data ),
			'has_selectable_in_month' => $has_selectable_in_month,
		], 200 );
	}

	/**
	 * GET /schedule-calendar?week=YYYY-MM-DD&class_ids=1,2,3
	 */
	public static function schedule_calendar( \WP_REST_Request $request ) {
		$week       = Schedule_Calendar::monday_of_week( (string) $request['week'] );
		$class_ids  = Schedule_Calendar::resolve_class_ids( self::sanitize_class_ids_param( $request['class_ids'] ?? [] ) );

		if ( empty( $class_ids ) ) {
			return new \WP_REST_Response( [ 'error' => 'no_classes' ], 404 );
		}
		if ( ! Schedule_Calendar::is_week_in_range( $week ) ) {
			return new \WP_REST_Response( [ 'error' => 'week_out_of_range' ], 422 );
		}

		$payload = Schedule_Calendar::week_payload( $class_ids, $week );
		$payload['weeks_ahead'] = Schedule_Calendar::weeks_ahead_cap();

		return new \WP_REST_Response( $payload, 200 );
	}

	/**
	 * GET /schedule-booking-form — booking form HTML for schedule modal.
	 */
	public static function schedule_booking_form( \WP_REST_Request $request ) {
		$class_id           = (int) $request['class_id'];
		$preset_date        = Helpers::normalise_date_string( (string) ( $request['preset_date'] ?? '' ) );
		$preset_slot_rule_id = sanitize_key( (string) ( $request['preset_slot_rule_id'] ?? '' ) );
		$class_data         = Helpers::get_class_data( $class_id );

		if ( ! $class_data || empty( $class_data['class_active'] ) ) {
			return new \WP_REST_Response( [ 'error' => 'not_found' ], 404 );
		}

		$html = Shortcode::render_booking_html(
			[
				'class_id'              => (string) $class_id,
				'heading'               => '0',
				'preset_date'           => $preset_date,
				'preset_slot_rule_id'   => $preset_slot_rule_id,
			]
		);

		return new \WP_REST_Response( [ 'html' => $html ], 200 );
	}

	/**
	 * Limit public checkout attempts that create soft-holds / Stripe sessions.
	 *
	 * @return \WP_REST_Response|null
	 */
	private static function checkout_rate_limit_error( string $email ): ?\WP_REST_Response {
		$email = strtolower( sanitize_email( $email ) );
		$ip    = self::client_ip();
		$ttl   = (int) apply_filters( 'clasbpro_checkout_rate_limit_window', 15 * MINUTE_IN_SECONDS );
		if ( $ttl < MINUTE_IN_SECONDS ) {
			$ttl = MINUTE_IN_SECONDS;
		}

		$buckets = [
			[
				'key' => 'clasbpro_chk_ip_' . md5( $ip ),
				'max' => (int) apply_filters( 'clasbpro_checkout_rate_limit_ip', 8 ),
			],
			[
				'key' => 'clasbpro_chk_em_' . md5( $email ),
				'max' => (int) apply_filters( 'clasbpro_checkout_rate_limit_email', 5 ),
			],
		];

		foreach ( $buckets as $bucket ) {
			if ( $bucket['max'] <= 0 ) {
				continue;
			}
			if ( (int) get_transient( $bucket['key'] ) >= $bucket['max'] ) {
				return self::error(
					429,
					'rate_limited',
					__( 'Too many checkout attempts. Please wait a few minutes and try again.', 'class-bookings-with-stripe-pro' )
				);
			}
		}

		foreach ( $buckets as $bucket ) {
			if ( $bucket['max'] <= 0 ) {
				continue;
			}
			$count = (int) get_transient( $bucket['key'] );
			set_transient( $bucket['key'], $count + 1, $ttl );
		}

		return null;
	}

	private static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
			: '';
		return '' !== $ip ? $ip : 'unknown';
	}

	private static function error( int $status, string $reason, string $message, array $extra = [] ): \WP_REST_Response {
		return new \WP_REST_Response( array_merge( [
			'error'   => true,
			'reason'  => $reason,
			'message' => $message,
		], $extra ), $status );
	}
}
