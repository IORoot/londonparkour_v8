<?php
/**
 * Thin wrapper around the official stripe-php SDK.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Stripe_Service {

	private static ?\Stripe\StripeClient $client = null;

	public static function client(): ?\Stripe\StripeClient {
		$secret = Helpers::stripe_secret_key();
		if ( '' === $secret ) {
			return null;
		}
		if ( ! self::$client || self::$client->getApiKey() !== $secret ) {
			self::$client = new \Stripe\StripeClient( [
				'api_key'        => $secret,
				'stripe_version' => '2024-04-10',
			] );
		}
		return self::$client;
	}

	/**
	 * Create a Checkout Session with an inline product (price_data).
	 *
	 * @return \Stripe\Checkout\Session
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public static function create_checkout_session(
		array $class_data,
		string $class_date,
		int $seats,
		int $unit_amount_pence,
		string $customer_email,
		string $customer_name,
		int $booking_id,
		string $success_url,
		string $cancel_url
	): \Stripe\Checkout\Session {
		$client = self::client();
		if ( ! $client ) {
			throw new \RuntimeException( 'Stripe secret key is not configured.' );
		}

		$date_human = Helpers::format_date( $class_date );
		$time_human = Helpers::format_time( $class_data['start_time'] ?? '' );

		$product_name = self::resolve_product_name(
			$class_data,
			$class_date,
			$date_human,
			$time_human,
			$seats,
			$customer_name,
			$booking_id
		);

		$description_parts = array_filter( [
			$class_data['location'] ?? '',
			! empty( $class_data['duration'] ) ? sprintf( '%d min', (int) $class_data['duration'] ) : '',
		] );
		$product_description = implode( ' · ', $description_parts );

		$params = [
			'mode'         => 'payment',
			'success_url'  => $success_url,
			'cancel_url'   => $cancel_url,
			'expires_at'   => time() + CLASBOWPRO_HOLD_SECONDS,
			'line_items'   => [
				[
					'quantity'   => $seats,
					'price_data' => [
						'currency'     => Helpers::currency(),
						'unit_amount'  => $unit_amount_pence,
						'product_data' => [
							'name'        => $product_name,
							'description' => $product_description ?: null,
						],
					],
				],
			],
			'metadata'     => [
				'booking_id' => (string) $booking_id,
				'class_id'   => (string) ( $class_data['id'] ?? 0 ),
				'class_date' => $class_date,
				'seats'      => (string) $seats,
			],
			'payment_intent_data' => [
				'metadata' => [
					'booking_id' => (string) $booking_id,
				],
			],
		];

		if ( $customer_email && is_email( $customer_email ) ) {
			$params['customer_email'] = $customer_email;
		}

		$params['line_items'][0]['price_data']['product_data'] = array_filter(
			$params['line_items'][0]['price_data']['product_data'],
			static fn( $v ) => null !== $v && '' !== $v
		);

		return $client->checkout->sessions->create( $params );
	}

	/**
	 * Class booking Checkout with a pack Promotion Code pre-applied (quantity must be 1).
	 *
	 * @throws \Stripe\Exception\ApiErrorException|\RuntimeException
	 */
	public static function create_pack_booking_checkout_session(
		array $class_data,
		string $class_date,
		int $unit_amount_pence,
		string $customer_email,
		string $customer_name,
		int $booking_id,
		string $promotion_code_id,
		string $success_url,
		string $cancel_url
	): \Stripe\Checkout\Session {
		$client = self::client();
		if ( ! $client ) {
			throw new \RuntimeException( 'Stripe secret key is not configured.' );
		}

		$date_human = Helpers::format_date( $class_date );
		$time_human = Helpers::format_time( $class_data['start_time'] ?? '' );
		$product_name = self::resolve_product_name(
			$class_data,
			$class_date,
			$date_human,
			$time_human,
			1,
			$customer_name,
			$booking_id
		);

		$description_parts = array_filter( [
			$class_data['location'] ?? '',
			! empty( $class_data['duration'] ) ? sprintf( '%d min', (int) $class_data['duration'] ) : '',
			__( 'Coupon', 'class-bookings-with-stripe-pro' ),
		] );

		$params = [
			'mode'        => 'payment',
			'success_url' => $success_url,
			'cancel_url'  => $cancel_url,
			'expires_at'  => time() + CLASBOWPRO_HOLD_SECONDS,
			'line_items'  => [
				[
					'quantity'   => 1,
					'price_data' => [
						'currency'     => Helpers::currency(),
						'unit_amount'  => $unit_amount_pence,
						'product_data' => array_filter( [
							'name'        => $product_name,
							'description' => implode( ' · ', $description_parts ) ?: null,
						], static fn( $v ) => null !== $v && '' !== $v ),
					],
				],
			],
			'discounts'   => [
				[ 'promotion_code' => $promotion_code_id ],
			],
			'metadata'    => [
				'booking_id'             => (string) $booking_id,
				'class_id'               => (string) ( $class_data['id'] ?? 0 ),
				'class_date'             => $class_date,
				'seats'                  => '1',
				'clasbpro_type'          => 'class_booking',
				'clasbpro_pack_promo_id'  => $promotion_code_id,
			],
		];

		if ( $customer_email && is_email( $customer_email ) ) {
			$params['customer_email'] = $customer_email;
		}

		return $client->checkout->sessions->create( $params );
	}

	/**
	 * Checkout Session for buying a class pack product.
	 *
	 * @throws \Stripe\Exception\ApiErrorException|\RuntimeException
	 */
	public static function create_pack_purchase_checkout_session(
		array $pack,
		string $customer_email,
		string $customer_name,
		int $purchase_id,
		string $success_url,
		string $cancel_url
	): \Stripe\Checkout\Session {
		$client = self::client();
		if ( ! $client ) {
			throw new \RuntimeException( 'Stripe secret key is not configured.' );
		}

		$unit_pence = Helpers::to_pence( $pack['price'] );
		$uses       = (int) $pack['uses'];
		$name       = (string) $pack['name'];
		$desc       = sprintf(
			/* translators: 1: number of class uses, 2: unit price formatted */
			__( '%1$d class uses · valid for classes priced %2$s', 'class-bookings-with-stripe-pro' ),
			$uses,
			Helpers::format_price( $pack['unit_price'] )
		);

		$params = [
			'mode'                => 'payment',
			'success_url'         => $success_url,
			'cancel_url'          => $cancel_url,
			'expires_at'          => time() + CLASBOWPRO_HOLD_SECONDS,
			'line_items'          => [
				[
					'quantity'   => 1,
					'price_data' => [
						'currency'     => Helpers::currency(),
						'unit_amount'  => $unit_pence,
						'product_data' => [
							'name'        => $name,
							'description' => $desc,
						],
					],
				],
			],
			'metadata'            => [
				'clasbpro_type' => Packs::META_TYPE_PURCHASE,
				'purchase_id'   => (string) $purchase_id,
				'pack_id'       => (string) ( $pack['id'] ?? 0 ),
			],
			'payment_intent_data' => [
				'metadata' => [
					'clasbpro_type' => Packs::META_TYPE_PURCHASE,
					'purchase_id'   => (string) $purchase_id,
					'pack_id'       => (string) ( $pack['id'] ?? 0 ),
				],
			],
		];

		if ( $customer_email && is_email( $customer_email ) ) {
			$params['customer_email'] = $customer_email;
		}

		unset( $customer_name );

		return $client->checkout->sessions->create( $params );
	}

	/**
	 * Ensure a shared 100% off coupon exists for pack redemptions.
	 *
	 * @throws \Stripe\Exception\ApiErrorException|\RuntimeException
	 */
	public static function ensure_pack_coupon_id(): string {
		$client = self::client();
		if ( ! $client ) {
			throw new \RuntimeException( 'Stripe secret key is not configured.' );
		}

		$existing = (string) get_option( Packs::COUPON_OPTION, '' );
		if ( '' !== $existing ) {
			try {
				$coupon = $client->coupons->retrieve( $existing );
				if ( ! empty( $coupon->id ) && empty( $coupon->deleted ) ) {
					return (string) $coupon->id;
				}
			} catch ( \Throwable $e ) {
				// Recreate below.
			}
		}

		$coupon = $client->coupons->create( [
			'percent_off' => 100,
			'duration'    => 'once',
			'name'        => 'Coupon redemption',
			'metadata'    => [
				'clasbpro_pack_coupon' => '1',
			],
		] );

		update_option( Packs::COUPON_OPTION, (string) $coupon->id, false );
		return (string) $coupon->id;
	}

	/**
	 * Create a unique Promotion Code for a paid pack purchase.
	 *
	 * @return array{promo_id: string, code: string, expires_at: int}
	 * @throws \Stripe\Exception\ApiErrorException|\RuntimeException
	 */
	public static function create_pack_promotion_code( array $pack, int $purchase_id, string $email ): array {
		$client = self::client();
		if ( ! $client ) {
			throw new \RuntimeException( 'Stripe secret key is not configured.' );
		}

		$coupon_id = self::ensure_pack_coupon_id();
		$code      = self::generate_pack_code();
		$expires_at = 0;
		$months     = (int) ( $pack['expiry_months'] ?? 0 );
		if ( $months > 0 ) {
			$expires_at = time() + ( $months * MONTH_IN_SECONDS );
		}

		$params = [
			'coupon'          => $coupon_id,
			'code'            => $code,
			'max_redemptions' => max( 1, (int) $pack['uses'] ),
			'metadata'        => [
				'clasbpro_pack_id'      => (string) ( $pack['id'] ?? 0 ),
				'clasbpro_purchase_id'  => (string) $purchase_id,
				'clasbpro_pack_name'    => substr( (string) ( $pack['name'] ?? '' ), 0, 100 ),
				'clasbpro_unit_price'   => (string) ( $pack['unit_price'] ?? '' ),
				'clasbpro_class_ids'    => implode( ',', array_map( 'strval', $pack['class_ids'] ?? [] ) ),
				'clasbpro_email'        => strtolower( sanitize_email( $email ) ),
			],
		];
		if ( $expires_at > 0 ) {
			$params['expires_at'] = $expires_at;
		}

		$promo = $client->promotionCodes->create( $params );

		return [
			'promo_id'   => (string) $promo->id,
			'code'       => (string) ( $promo->code ?? $code ),
			'expires_at' => $expires_at,
		];
	}

	/**
	 * @throws \Stripe\Exception\ApiErrorException|\RuntimeException
	 */
	public static function find_promotion_code( string $code ): ?\Stripe\PromotionCode {
		$client = self::client();
		if ( ! $client ) {
			throw new \RuntimeException( 'Stripe secret key is not configured.' );
		}
		$code = strtoupper( trim( $code ) );
		if ( '' === $code ) {
			return null;
		}
		$list = $client->promotionCodes->all( [
			'code'  => $code,
			'limit' => 1,
		] );
		if ( empty( $list->data[0] ) ) {
			return null;
		}
		return $list->data[0];
	}

	/**
	 * @throws \Stripe\Exception\ApiErrorException|\RuntimeException
	 */
	public static function retrieve_promotion_code( string $promo_id ): ?\Stripe\PromotionCode {
		$client = self::client();
		if ( ! $client || '' === trim( $promo_id ) ) {
			return null;
		}
		return $client->promotionCodes->retrieve( $promo_id, [] );
	}

	private static function generate_pack_code(): string {
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$code     = 'PACK-';
		for ( $i = 0; $i < 8; $i++ ) {
			$code .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
		}
		return $code;
	}

	/**
	 * Build Stripe line-item title from settings template + placeholders.
	 */
	private static function resolve_product_name(
		array $class_data,
		string $class_date,
		string $date_human,
		string $time_human,
		int $seats,
		string $customer_name,
		int $booking_id
	): string {
		$tpl = (string) Helpers::get_option( 'stripe_item_title_template', '' );
		if ( '' === trim( $tpl ) ) {
			$tpl = '{class_name} — {class_date}, {class_time}';
		}

		$tags = [
			'{class_name}'    => (string) ( $class_data['name'] ?? __( 'Yoga class', 'class-bookings-with-stripe-pro' ) ),
			'{class_date}'    => $date_human,
			'{class_time}'    => $time_human,
			'{location}'      => (string) ( $class_data['location'] ?? '' ),
			'{seats}'         => (string) $seats,
			'{customer_name}' => $customer_name,
			'{booking_id}'    => '#' . $booking_id,
			'{class_date_raw}' => $class_date,
		];
		$title = trim( preg_replace( '/\s+/', ' ', strtr( $tpl, $tags ) ) ?? '' );
		if ( '' === $title ) {
			$title = (string) ( $class_data['name'] ?? __( 'Yoga class', 'class-bookings-with-stripe-pro' ) );
		}
		return substr( $title, 0, 127 );
	}

	/**
	 * Verify and parse an incoming webhook payload.
	 *
	 * @throws \UnexpectedValueException|\Stripe\Exception\SignatureVerificationException
	 */
	public static function verify_webhook( string $payload, string $sig_header ): \Stripe\Event {
		$secret = Helpers::stripe_webhook_secret();
		if ( '' === $secret ) {
			throw new \RuntimeException( 'Stripe webhook secret is not configured.' );
		}
		return \Stripe\Webhook::constructEvent( $payload, $sig_header, $secret );
	}

	/**
	 * Retrieve a Checkout Session by id.
	 *
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public static function retrieve_checkout_session( string $session_id ): ?\Stripe\Checkout\Session {
		$client = self::client();
		if ( ! $client || '' === trim( $session_id ) ) {
			return null;
		}
		return $client->checkout->sessions->retrieve( $session_id, [] );
	}

	/**
	 * Look up the customer-facing promotion code string for admin display.
	 */
	public static function retrieve_promotion_code_string( string $promo_id ): string {
		$promo_id = trim( $promo_id );
		if ( '' === $promo_id ) {
			return '';
		}
		try {
			$client = self::client();
			if ( ! $client ) {
				return '';
			}
			$promo = $client->promotionCodes->retrieve( $promo_id, [] );
			return is_object( $promo ) ? (string) ( $promo->code ?? '' ) : '';
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Could not retrieve promo code: ' . $e->getMessage() );
			return '';
		}
	}
}
