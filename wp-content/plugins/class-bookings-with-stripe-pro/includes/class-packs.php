<?php
/**
 * Coupons: purchase, Stripe promotion codes, cookie recognition, redeem on booking.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Packs {

	public const COOKIE_NAME          = 'clasbpro_pack';
	public const RESTORE_QUERY_ARG    = 'clasbpro_pack_restore';
	public const META_TYPE_PURCHASE   = 'pack_purchase';
	public const STATUS_PENDING       = 'pending';
	public const STATUS_PAID          = 'paid';
	public const STATUS_EXPIRED       = 'expired';
	public const COUPON_OPTION        = 'clasbpro_stripe_pack_coupon_id';

	public static function init(): void {
		add_action( 'init', [ self::class, 'maybe_claim_restore_token' ], 1 );
		add_action( 'init', [ self::class, 'maybe_claim_from_purchase_query' ], 2 );
		add_filter( 'manage_' . CPT::PACK_PT . '_posts_columns', [ self::class, 'pack_columns' ] );
		add_action( 'manage_' . CPT::PACK_PT . '_posts_custom_column', [ self::class, 'pack_column_value' ], 10, 2 );
		add_filter( 'manage_' . CPT::PACK_PURCHASE_PT . '_posts_columns', [ self::class, 'purchase_columns' ] );
		add_action( 'manage_' . CPT::PACK_PURCHASE_PT . '_posts_custom_column', [ self::class, 'purchase_column_value' ], 10, 2 );
		add_action( 'acf/save_post', [ self::class, 'validate_pack_on_save' ], 20 );
		add_action( 'post_submitbox_misc_actions', [ self::class, 'render_pack_shortcode_hint' ] );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_pack_data( int $pack_id ): ?array {
		$post = get_post( $pack_id );
		if ( ! $post || CPT::PACK_PT !== $post->post_type ) {
			return null;
		}

		$active = true;
		if ( function_exists( 'get_field' ) ) {
			$active_raw = get_field( 'pack_active', $pack_id );
			if ( null !== $active_raw && false !== $active_raw && '' !== $active_raw ) {
				$active = (bool) $active_raw;
			}
		} else {
			$active = (bool) get_post_meta( $pack_id, 'pack_active', true );
		}

		$uses = (int) ( function_exists( 'get_field' ) ? get_field( 'pack_uses', $pack_id ) : get_post_meta( $pack_id, 'pack_uses', true ) );
		$price = (float) ( function_exists( 'get_field' ) ? get_field( 'pack_price', $pack_id ) : get_post_meta( $pack_id, 'pack_price', true ) );
		$unit  = (float) ( function_exists( 'get_field' ) ? get_field( 'pack_unit_price', $pack_id ) : get_post_meta( $pack_id, 'pack_unit_price', true ) );
		$expiry_months = (int) ( function_exists( 'get_field' ) ? get_field( 'pack_expiry_months', $pack_id ) : get_post_meta( $pack_id, 'pack_expiry_months', true ) );
		$description = (string) ( function_exists( 'get_field' ) ? get_field( 'pack_description', $pack_id ) : get_post_meta( $pack_id, 'pack_description', true ) );

		$class_ids = [];
		$raw_classes = function_exists( 'get_field' ) ? get_field( 'pack_classes', $pack_id ) : get_post_meta( $pack_id, 'pack_classes', true );
		if ( is_array( $raw_classes ) ) {
			foreach ( $raw_classes as $item ) {
				if ( is_object( $item ) && isset( $item->ID ) ) {
					$class_ids[] = (int) $item->ID;
				} elseif ( is_numeric( $item ) ) {
					$class_ids[] = (int) $item;
				} elseif ( is_array( $item ) && isset( $item['ID'] ) ) {
					$class_ids[] = (int) $item['ID'];
				}
			}
		}
		$class_ids = array_values( array_unique( array_filter( $class_ids ) ) );

		return [
			'id'            => $pack_id,
			'name'          => get_the_title( $pack_id ),
			'active'        => $active,
			'uses'          => max( 1, $uses ),
			'price'         => max( 0, $price ),
			'unit_price'    => max( 0, $unit ),
			'expiry_months' => max( 0, $expiry_months ),
			'description'   => $description,
			'class_ids'     => $class_ids,
		];
	}

	/**
	 * @param array<int, int> $ids Optional pack IDs to include (empty = all active packs).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_active_packs( array $ids = [] ): array {
		$args = [
			'post_type'      => CPT::PACK_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		];
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		if ( ! empty( $ids ) ) {
			$args['post__in'] = $ids;
			$args['orderby']  = 'post__in';
			$args['posts_per_page'] = count( $ids );
		}

		$posts = get_posts( $args );
		$out = [];
		foreach ( $posts as $post ) {
			$data = self::get_pack_data( (int) $post->ID );
			if (
				$data
				&& 'publish' === $post->post_status
				&& ! empty( $data['active'] )
				&& $data['price'] > 0
				&& $data['uses'] > 0
				&& ! empty( $data['class_ids'] )
			) {
				$out[] = $data;
			}
		}
		return $out;
	}

	public static function class_is_eligible( array $pack, int $class_id ): bool {
		if ( $class_id <= 0 || empty( $pack['class_ids'] ) || ! in_array( $class_id, $pack['class_ids'], true ) ) {
			return false;
		}
		$class = Helpers::get_class_data( $class_id );
		if ( ! $class || empty( $class['class_active'] ) ) {
			return false;
		}
		return Helpers::to_pence( $class['price'] ) === Helpers::to_pence( $pack['unit_price'] );
	}

	/**
	 * @return array{promo_id: string, email: string, exp: int}|null
	 */
	public static function get_active_cookie(): ?array {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return null;
		}
		$raw = wp_unslash( (string) $_COOKIE[ self::COOKIE_NAME ] );
		$payload = self::verify_signed_token( $raw );
		if ( ! $payload ) {
			return null;
		}
		$promo_id = sanitize_text_field( (string) ( $payload['promo_id'] ?? '' ) );
		$email    = sanitize_email( (string) ( $payload['email'] ?? '' ) );
		$exp      = (int) ( $payload['exp'] ?? 0 );
		if ( '' === $promo_id || ! is_email( $email ) || ( $exp > 0 && $exp < time() ) ) {
			return null;
		}
		return [
			'promo_id' => $promo_id,
			'email'    => strtolower( $email ),
			'exp'      => $exp,
		];
	}

	public static function set_active_cookie( string $promo_id, string $email, int $expires_at = 0 ): void {
		$email = strtolower( sanitize_email( $email ) );
		if ( '' === $promo_id || ! is_email( $email ) ) {
			return;
		}
		$exp = $expires_at > time() ? $expires_at : ( time() + YEAR_IN_SECONDS );
		$token = self::sign_payload( [
			'promo_id' => $promo_id,
			'email'    => $email,
			'exp'      => $exp,
		] );
		$secure = is_ssl();
		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie( self::COOKIE_NAME, $token, [
				'expires'  => $exp,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			] );
		} else {
			setcookie( self::COOKIE_NAME, $token, $exp, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
		}
		$_COOKIE[ self::COOKIE_NAME ] = $token;
	}

	public static function clear_active_cookie(): void {
		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie( self::COOKIE_NAME, '', [
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			] );
		} else {
			setcookie( self::COOKIE_NAME, '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	public static function build_restore_token( string $promo_id, string $email, int $expires_at = 0 ): string {
		$email = strtolower( sanitize_email( $email ) );
		$exp   = $expires_at > time() ? $expires_at : ( time() + YEAR_IN_SECONDS );
		return self::sign_payload( [
			'promo_id' => $promo_id,
			'email'    => $email,
			'exp'      => $exp,
		] );
	}

	public static function restore_url( string $promo_id, string $email, int $expires_at = 0 ): string {
		$token = self::build_restore_token( $promo_id, $email, $expires_at );
		return add_query_arg( self::RESTORE_QUERY_ARG, rawurlencode( $token ), home_url( '/' ) );
	}

	public static function maybe_claim_restore_token(): void {
		if ( empty( $_GET[ self::RESTORE_QUERY_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$token = sanitize_text_field( wp_unslash( (string) $_GET[ self::RESTORE_QUERY_ARG ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$payload = self::verify_signed_token( $token );
		if ( ! $payload ) {
			return;
		}
		$promo_id = sanitize_text_field( (string) ( $payload['promo_id'] ?? '' ) );
		$email    = sanitize_email( (string) ( $payload['email'] ?? '' ) );
		$exp      = (int) ( $payload['exp'] ?? 0 );
		if ( '' === $promo_id || ! is_email( $email ) ) {
			return;
		}
		self::set_active_cookie( $promo_id, $email, $exp );

		$redirect = remove_query_arg( self::RESTORE_QUERY_ARG );
		if ( '' === $redirect ) {
			$redirect = home_url( '/' );
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @return array{ok: bool, message?: string, promo_id?: string, email?: string, uses_remaining?: int, uses_total?: int, pack_id?: int, pack_name?: string, expires_at?: int}
	 */
	public static function attach_by_code( string $code, string $email = '' ): array {
		$code = strtoupper( trim( $code ) );
		if ( '' === $code ) {
			return [ 'ok' => false, 'message' => __( 'Please enter a coupon code.', 'class-bookings-with-stripe-pro' ) ];
		}

		try {
			$promo = Stripe_Service::find_promotion_code( $code );
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Pack code lookup failed: ' . $e->getMessage() );
			return [ 'ok' => false, 'message' => __( 'Could not verify that coupon code. Please try again.', 'class-bookings-with-stripe-pro' ) ];
		}

		if ( ! $promo ) {
			return [ 'ok' => false, 'message' => __( 'That coupon code was not found.', 'class-bookings-with-stripe-pro' ) ];
		}

		$state = self::promotion_state( $promo );
		if ( ! $state['active'] ) {
			return [ 'ok' => false, 'message' => $state['message'] ?: __( 'That coupon is no longer valid.', 'class-bookings-with-stripe-pro' ) ];
		}

		$meta_email = strtolower( sanitize_email( (string) ( $promo->metadata->clasbpro_email ?? '' ) ) );
		$email      = strtolower( sanitize_email( $email ) );
		if ( is_email( $email ) && is_email( $meta_email ) && $email !== $meta_email ) {
			return [ 'ok' => false, 'message' => __( 'That coupon belongs to a different email address.', 'class-bookings-with-stripe-pro' ) ];
		}
		$bind_email = is_email( $meta_email ) ? $meta_email : $email;
		if ( ! is_email( $bind_email ) ) {
			return [ 'ok' => false, 'message' => __( 'Enter the email used when you bought the coupon.', 'class-bookings-with-stripe-pro' ) ];
		}

		$expires_at = ! empty( $promo->expires_at ) ? (int) $promo->expires_at : 0;
		self::set_active_cookie( (string) $promo->id, $bind_email, $expires_at );

		return [
			'ok'             => true,
			'promo_id'       => (string) $promo->id,
			'email'          => $bind_email,
			'uses_remaining' => $state['uses_remaining'],
			'uses_total'     => $state['uses_total'],
			'pack_id'        => (int) ( $promo->metadata->clasbpro_pack_id ?? 0 ),
			'pack_name'      => (string) ( $promo->metadata->clasbpro_pack_name ?? '' ),
			'expires_at'     => $expires_at,
		];
	}

	/**
	 * @param \Stripe\PromotionCode|object $promo
	 * @return array{active: bool, message: string, uses_remaining: int, uses_total: int, pack_id: int}
	 */
	public static function promotion_state( $promo ): array {
		$uses_total = (int) ( $promo->max_redemptions ?? 0 );
		$redeemed   = (int) ( $promo->times_redeemed ?? 0 );
		$remaining  = $uses_total > 0 ? max( 0, $uses_total - $redeemed ) : 0;
		$pack_id    = (int) ( $promo->metadata->clasbpro_pack_id ?? 0 );

		if ( empty( $promo->active ) ) {
			return [
				'active'         => false,
				'message'        => __( 'That coupon code is inactive.', 'class-bookings-with-stripe-pro' ),
				'uses_remaining' => $remaining,
				'uses_total'     => $uses_total,
				'pack_id'        => $pack_id,
			];
		}
		if ( ! empty( $promo->expires_at ) && (int) $promo->expires_at < time() ) {
			return [
				'active'         => false,
				'message'        => __( 'That coupon has expired.', 'class-bookings-with-stripe-pro' ),
				'uses_remaining' => $remaining,
				'uses_total'     => $uses_total,
				'pack_id'        => $pack_id,
			];
		}
		if ( $uses_total > 0 && $remaining <= 0 ) {
			return [
				'active'         => false,
				'message'        => __( 'That coupon has no uses left.', 'class-bookings-with-stripe-pro' ),
				'uses_remaining' => 0,
				'uses_total'     => $uses_total,
				'pack_id'        => $pack_id,
			];
		}

		return [
			'active'         => true,
			'message'        => '',
			'uses_remaining' => $remaining,
			'uses_total'     => $uses_total,
			'pack_id'        => $pack_id,
		];
	}

	/**
	 * Status for the booking form (cookie + class eligibility).
	 *
	 * @return array<string, mixed>
	 */
	public static function status_for_class( int $class_id, string $form_email = '' ): array {
		$cookie = self::get_active_cookie();
		if ( ! $cookie ) {
			return [
				'recognised'     => false,
				'eligible'       => false,
				'uses_remaining' => 0,
				'uses_total'     => 0,
				'pack_name'      => '',
				'email'          => '',
			];
		}

		try {
			$promo = Stripe_Service::retrieve_promotion_code( $cookie['promo_id'] );
		} catch ( \Throwable $e ) {
			return [
				'recognised'     => false,
				'eligible'       => false,
				'uses_remaining' => 0,
				'uses_total'     => 0,
				'pack_name'      => '',
				'email'          => $cookie['email'],
				'message'        => __( 'Could not load your coupon. Try entering the code again.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( ! $promo ) {
			self::clear_active_cookie();
			return [
				'recognised'     => false,
				'eligible'       => false,
				'uses_remaining' => 0,
				'uses_total'     => 0,
				'pack_name'      => '',
				'email'          => '',
				'message'        => __( 'Your saved coupon is no longer available.', 'class-bookings-with-stripe-pro' ),
			];
		}

		$state = self::promotion_state( $promo );
		$pack_id = $state['pack_id'];
		$pack = $pack_id ? self::get_pack_data( $pack_id ) : null;
		$pack_name = $pack ? (string) $pack['name'] : (string) ( $promo->metadata->clasbpro_pack_name ?? '' );

		$form_email = strtolower( sanitize_email( $form_email ) );
		$email_ok   = '' === $form_email || $form_email === $cookie['email'];

		$eligible = false;
		$reason   = '';
		if ( ! $state['active'] ) {
			$reason = $state['message'];
		} elseif ( ! $email_ok ) {
			$reason = __( 'Use the same email you bought the coupon with to redeem it.', 'class-bookings-with-stripe-pro' );
		} elseif ( ! $pack ) {
			$reason = __( 'This coupon is no longer available for booking.', 'class-bookings-with-stripe-pro' );
		} elseif ( ! self::class_is_eligible( $pack, $class_id ) ) {
			$reason = __( 'This coupon cannot be used for this class (or the class price has changed).', 'class-bookings-with-stripe-pro' );
		} else {
			$eligible = true;
		}

		return [
			'recognised'     => true,
			'eligible'       => $eligible,
			'uses_remaining' => $state['uses_remaining'],
			'uses_total'     => $state['uses_total'],
			'uses_used'      => max( 0, $state['uses_total'] - $state['uses_remaining'] ),
			'pack_name'      => $pack_name,
			'pack_id'        => $pack_id,
			'promo_id'       => (string) $promo->id,
			'email'          => $cookie['email'],
			'message'        => $reason,
			'restore_token'  => self::build_restore_token( (string) $promo->id, $cookie['email'], (int) ( $cookie['exp'] ?? 0 ) ),
		];
	}

	/**
	 * Re-apply a signed restore token (same payload as the email restore link).
	 *
	 * @return array<string, mixed>
	 */
	public static function restore_from_token( string $token, int $class_id = 0, string $form_email = '' ): array {
		$payload = self::verify_signed_token( $token );
		if ( ! $payload ) {
			return [
				'recognised' => false,
				'eligible'   => false,
				'ok'         => false,
				'message'    => __( 'That restore link is invalid or expired.', 'class-bookings-with-stripe-pro' ),
			];
		}
		$promo_id = sanitize_text_field( (string) ( $payload['promo_id'] ?? '' ) );
		$email    = sanitize_email( (string) ( $payload['email'] ?? '' ) );
		$exp      = (int) ( $payload['exp'] ?? 0 );
		if ( '' === $promo_id || ! is_email( $email ) || ( $exp > 0 && $exp < time() ) ) {
			return [
				'recognised' => false,
				'eligible'   => false,
				'ok'         => false,
				'message'    => __( 'That restore link is invalid or expired.', 'class-bookings-with-stripe-pro' ),
			];
		}
		self::set_active_cookie( $promo_id, $email, $exp );
		$status = $class_id > 0
			? self::status_for_class( $class_id, $form_email !== '' ? $form_email : $email )
			: [
				'recognised' => true,
				'eligible'   => true,
				'email'      => strtolower( $email ),
				'promo_id'   => $promo_id,
				'ok'         => true,
			];
		$status['ok'] = ! empty( $status['recognised'] );
		return $status;
	}

	/**
	 * Validate cookie pack for a booking checkout.
	 *
	 * @return array{promo_id: string, email: string}| \WP_Error
	 */
	public static function validate_for_checkout( int $class_id, string $customer_email ) {
		$status = self::status_for_class( $class_id, $customer_email );
		if ( empty( $status['recognised'] ) || empty( $status['eligible'] ) || empty( $status['promo_id'] ) ) {
			return new \WP_Error(
				'pack_invalid',
				(string) ( $status['message'] ?: __( 'Your coupon cannot be used for this booking.', 'class-bookings-with-stripe-pro' ) )
			);
		}
		$email = strtolower( sanitize_email( $customer_email ) );
		if ( $email !== (string) $status['email'] ) {
			return new \WP_Error(
				'pack_email_mismatch',
				__( 'Use the same email you bought the coupon with to redeem it.', 'class-bookings-with-stripe-pro' )
			);
		}
		return [
			'promo_id' => (string) $status['promo_id'],
			'email'    => $email,
		];
	}

	/**
	 * @return int|\WP_Error
	 */
	public static function create_pending_purchase( array $args ) {
		$pack_id = (int) ( $args['pack_id'] ?? 0 );
		$name    = sanitize_text_field( (string) ( $args['customer_name'] ?? '' ) );
		$email   = sanitize_email( (string) ( $args['customer_email'] ?? '' ) );
		$amount  = (int) ( $args['amount_pence'] ?? 0 );

		$post_id = wp_insert_post( [
			'post_type'   => CPT::PACK_PURCHASE_PT,
			'post_status' => 'publish',
			'post_title'  => sprintf(
				'%s · %s',
				$name ?: __( 'Customer', 'class-bookings-with-stripe-pro' ),
				get_the_title( $pack_id )
			),
		], true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_clasbpro_pack_id', $pack_id );
		update_post_meta( $post_id, '_clasbpro_customer_name', $name );
		update_post_meta( $post_id, '_clasbpro_customer_email', $email );
		update_post_meta( $post_id, '_clasbpro_amount_total', $amount );
		update_post_meta( $post_id, '_clasbpro_status', self::STATUS_PENDING );
		update_post_meta( $post_id, '_clasbpro_created_gmt', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $post_id, '_clasbpro_expires_at', time() + CLASBOWPRO_HOLD_SECONDS );
		update_post_meta( $post_id, '_clasbpro_status_token', wp_generate_password( 32, false, false ) );

		return (int) $post_id;
	}

	public static function verify_status_token( int $purchase_id, string $token ): bool {
		if ( '' === $token ) {
			return false;
		}
		$stored = (string) get_post_meta( $purchase_id, '_clasbpro_status_token', true );
		return '' !== $stored && hash_equals( $stored, $token );
	}

	/**
	 * Allow status page / poll for site admins or the holder of the per-purchase status token.
	 * A Stripe Checkout Session id alone is not sufficient (same rule as booking status).
	 *
	 * @param string $session_id Kept for call-site compatibility; not used for authorization.
	 */
	public static function can_view_purchase_status( int $purchase_id, string $session_id, string $token ): bool {
		unset( $session_id );
		if ( $purchase_id <= 0 ) {
			return false;
		}
		$post = get_post( $purchase_id );
		if ( ! $post || CPT::PACK_PURCHASE_PT !== $post->post_type ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return self::verify_status_token( $purchase_id, $token );
	}

	/**
	 * Display payload for the confirmation shortcode / status poll.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_purchase_display( int $purchase_id ): ?array {
		$post = get_post( $purchase_id );
		if ( ! $post || CPT::PACK_PURCHASE_PT !== $post->post_type ) {
			return null;
		}
		$pack_id = (int) get_post_meta( $purchase_id, '_clasbpro_pack_id', true );
		$pack    = $pack_id ? self::get_pack_data( $pack_id ) : null;
		$uses    = (int) get_post_meta( $purchase_id, '_clasbpro_pack_uses', true );
		if ( $uses <= 0 && $pack ) {
			$uses = (int) $pack['uses'];
		}
		$expires_at = (int) get_post_meta( $purchase_id, '_clasbpro_pack_expires_at', true );

		$promo_id = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_promo_id', true );
		$code     = '';
		if ( '' !== $promo_id ) {
			$code = Stripe_Service::retrieve_promotion_code_string( $promo_id );
		}
		$email = (string) get_post_meta( $purchase_id, '_clasbpro_customer_email', true );
		$restore_token = ( '' !== $promo_id && is_email( $email ) )
			? self::build_restore_token( $promo_id, $email, $expires_at )
			: '';

		return [
			'kind'           => 'coupon',
			'status'         => self::get_purchase_status( $purchase_id ),
			'purchase_id'    => $purchase_id,
			'pack_id'        => $pack_id,
			'pack_name'      => $pack['name'] ?? get_the_title( $pack_id ),
			'uses'           => $uses,
			'amount_total'   => Helpers::format_stripe_amount( (int) get_post_meta( $purchase_id, '_clasbpro_amount_total', true ) ),
			'customer_name'  => (string) get_post_meta( $purchase_id, '_clasbpro_customer_name', true ),
			'customer_email' => $email,
			'expires_label'  => $expires_at > 0
				? Helpers::format_date( gmdate( 'Y-m-d', $expires_at ) )
				: '',
			'promo_id'       => $promo_id,
			'code'           => $code,
			'restore_token'  => $restore_token,
		];
	}

	/**
	 * Bookings that redeemed this purchase's Stripe promotion code.
	 *
	 * @return list<array{
	 *   booking_id: int,
	 *   status: string,
	 *   class_id: int,
	 *   class_name: string,
	 *   class_date: string,
	 *   class_date_raw: string,
	 *   class_time: string,
	 *   location: string,
	 *   customer_name: string,
	 *   edit_url: string
	 * }>
	 */
	public static function get_purchase_usages( int $purchase_id ): array {
		$promo_id = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_promo_id', true );
		if ( '' === $promo_id ) {
			return [];
		}

		$q = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'any',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'orderby'        => 'meta_value',
			'meta_key'       => '_clasbpro_class_date',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key'   => '_clasbpro_pack_promo_id',
					'value' => $promo_id,
				],
			],
		] );

		$out = [];
		foreach ( $q->posts as $booking_id ) {
			$booking_id = (int) $booking_id;
			$meta       = Bookings::get_meta( $booking_id );
			$class_id   = (int) ( $meta['class_id'] ?? 0 );
			$class_data = $class_id ? Helpers::get_class_data( $class_id ) : null;
			$display    = Bookings::get_booking_display_context( $booking_id, $class_data );
			$edit_url   = current_user_can( 'edit_post', $booking_id )
				? (string) get_edit_post_link( $booking_id, 'raw' )
				: '';

			$out[] = [
				'booking_id'     => $booking_id,
				'status'         => (string) ( $meta['status'] ?? '' ),
				'class_id'       => $class_id,
				'class_name'     => (string) ( $class_data['name'] ?? get_the_title( $class_id ) ),
				'class_date'     => Helpers::format_date( (string) ( $meta['class_date'] ?? '' ) ),
				'class_date_raw' => (string) ( $meta['class_date'] ?? '' ),
				'class_time'     => Helpers::format_time( (string) ( $display['start_time'] ?? '' ) ),
				'location'       => (string) ( $display['location'] ?? '' ),
				'customer_name'  => (string) ( $meta['customer_name'] ?? '' ),
				'edit_url'       => $edit_url,
			];
		}

		return $out;
	}

	public static function attach_stripe_session( int $purchase_id, string $session_id ): void {
		update_post_meta( $purchase_id, '_clasbpro_stripe_session_id', $session_id );
	}

	public static function find_purchase_by_session( string $session_id ): int {
		if ( '' === $session_id ) {
			return 0;
		}
		$q = new \WP_Query( [
			'post_type'      => CPT::PACK_PURCHASE_PT,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => '_clasbpro_stripe_session_id',
					'value' => $session_id,
				],
			],
		] );
		return ! empty( $q->posts[0] ) ? (int) $q->posts[0] : 0;
	}

	public static function get_purchase_status( int $purchase_id ): string {
		return (string) get_post_meta( $purchase_id, '_clasbpro_status', true );
	}

	public static function set_purchase_status( int $purchase_id, string $status ): void {
		update_post_meta( $purchase_id, '_clasbpro_status', $status );
	}

	/**
	 * @param object $session Stripe Checkout Session.
	 */
	public static function handle_pack_purchase_completed( $session ): void {
		$purchase_id = self::resolve_purchase_from_session( $session );
		if ( ! $purchase_id ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] pack purchase completed but purchase not found.' );
			return;
		}
		if ( self::STATUS_PAID === self::get_purchase_status( $purchase_id ) ) {
			return;
		}

		$payment_status = is_object( $session ) ? (string) ( $session->payment_status ?? '' ) : '';
		if ( 'paid' !== $payment_status && 'no_payment_required' !== $payment_status ) {
			return;
		}

		$pack_id = (int) get_post_meta( $purchase_id, '_clasbpro_pack_id', true );
		$pack    = self::get_pack_data( $pack_id );
		if ( ! $pack ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] pack purchase paid but pack missing. purchase_id=' . $purchase_id );
			return;
		}

		$name  = (string) get_post_meta( $purchase_id, '_clasbpro_customer_name', true );
		$email = (string) get_post_meta( $purchase_id, '_clasbpro_customer_email', true );
		$details = is_object( $session ) ? ( $session->customer_details ?? null ) : null;
		if ( $details ) {
			if ( ! empty( $details->name ) ) {
				$name = sanitize_text_field( (string) $details->name );
			}
			if ( ! empty( $details->email ) ) {
				$email = sanitize_email( (string) $details->email );
			}
		}
		update_post_meta( $purchase_id, '_clasbpro_customer_name', $name );
		update_post_meta( $purchase_id, '_clasbpro_customer_email', $email );

		$amount_total = is_object( $session ) ? (int) ( $session->amount_total ?? 0 ) : 0;
		if ( $amount_total > 0 ) {
			update_post_meta( $purchase_id, '_clasbpro_amount_total', $amount_total );
		}

		$payment_intent = '';
		if ( is_object( $session ) ) {
			$payment_intent = is_string( $session->payment_intent ?? null )
				? $session->payment_intent
				: ( is_object( $session->payment_intent ?? null ) ? (string) $session->payment_intent->id : '' );
		}
		if ( $payment_intent ) {
			update_post_meta( $purchase_id, '_clasbpro_stripe_payment_intent', $payment_intent );
		}

		try {
			$created = Stripe_Service::create_pack_promotion_code( $pack, $purchase_id, $email );
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Failed to create pack promo: ' . $e->getMessage() );
			return;
		}

		$promo_id   = (string) ( $created['promo_id'] ?? '' );
		$code       = (string) ( $created['code'] ?? '' );
		$expires_at = (int) ( $created['expires_at'] ?? 0 );

		update_post_meta( $purchase_id, '_clasbpro_stripe_promo_id', $promo_id );
		update_post_meta( $purchase_id, '_clasbpro_pack_uses', (int) $pack['uses'] );
		if ( $expires_at > 0 ) {
			update_post_meta( $purchase_id, '_clasbpro_pack_expires_at', $expires_at );
		}

		self::set_purchase_status( $purchase_id, self::STATUS_PAID );

		wp_update_post( [
			'ID'         => $purchase_id,
			'post_title' => sprintf(
				'%s · %s',
				$name ?: __( 'Customer', 'class-bookings-with-stripe-pro' ),
				$pack['name']
			),
		] );

		$restore_url = self::restore_url( $promo_id, $email, $expires_at );
		Emails::send_for_pack_purchase( $purchase_id, $code, $restore_url );
		Helpers::debug_log( '[class-bookings-with-stripe-pro] pack purchase completed. purchase_id=' . $purchase_id . ' promo_id=' . $promo_id );
	}

	/**
	 * @param object $session
	 */
	public static function handle_pack_purchase_expired( $session ): void {
		$purchase_id = self::resolve_purchase_from_session( $session );
		if ( ! $purchase_id || self::STATUS_PAID === self::get_purchase_status( $purchase_id ) ) {
			return;
		}
		self::set_purchase_status( $purchase_id, self::STATUS_EXPIRED );
	}

	/**
	 * @param object $session
	 */
	private static function resolve_purchase_from_session( $session ): int {
		if ( ! is_object( $session ) ) {
			return 0;
		}
		$session_id = (string) ( $session->id ?? '' );
		$found      = self::find_purchase_by_session( $session_id );
		if ( $found ) {
			return $found;
		}
		$meta = $session->metadata ?? null;
		if ( $meta && ! empty( $meta->purchase_id ) ) {
			$candidate = (int) $meta->purchase_id;
			$post      = get_post( $candidate );
			if ( $post && CPT::PACK_PURCHASE_PT === $post->post_type ) {
				return $candidate;
			}
		}
		return 0;
	}

	/**
	 * Claim cookie after pack Checkout return (success URL carries purchase id).
	 */
	public static function success_url_with_claim( string $base_success_url, int $purchase_id ): string {
		// Append manually — add_query_arg() would URL-encode Stripe's {CHECKOUT_SESSION_ID} placeholder.
		$sep = false === strpos( $base_success_url, '?' ) ? '?' : '&';
		return $base_success_url . $sep . 'clasbpro_pack_purchase=' . absint( $purchase_id );
	}

	/**
	 * After pack purchase Checkout return: reconcile with Stripe if webhook missed, then claim cookie.
	 */
	public static function maybe_claim_from_purchase_query(): void {
		if ( empty( $_GET['clasbpro_pack_purchase'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$purchase_id = absint( $_GET['clasbpro_pack_purchase'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $purchase_id <= 0 ) {
			return;
		}

		$post = get_post( $purchase_id );
		if ( ! $post || CPT::PACK_PURCHASE_PT !== $post->post_type ) {
			return;
		}

		if ( self::STATUS_PENDING === self::get_purchase_status( $purchase_id ) ) {
			self::reconcile_purchase_from_stripe( $purchase_id );
		}

		if ( self::STATUS_PAID !== self::get_purchase_status( $purchase_id ) ) {
			return;
		}
		$promo_id = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_promo_id', true );
		$email    = (string) get_post_meta( $purchase_id, '_clasbpro_customer_email', true );
		$expires  = (int) get_post_meta( $purchase_id, '_clasbpro_pack_expires_at', true );
		if ( '' === $promo_id || ! is_email( $email ) ) {
			return;
		}
		self::set_active_cookie( $promo_id, $email, $expires );
	}

	/**
	 * Fallback when webhooks are delayed/missed: fetch Checkout Session and complete the purchase.
	 */
	public static function reconcile_purchase_from_stripe( int $purchase_id ): void {
		if ( $purchase_id <= 0 || self::STATUS_PAID === self::get_purchase_status( $purchase_id ) ) {
			return;
		}

		$session_id = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_session_id', true );
		if ( '' === $session_id && ! empty( $_GET['booking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$candidate = sanitize_text_field( wp_unslash( (string) $_GET['booking'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 0 === strpos( $candidate, 'cs_' ) ) {
				$session_id = $candidate;
				self::attach_stripe_session( $purchase_id, $session_id );
			}
		}
		if ( '' === $session_id ) {
			return;
		}

		try {
			$session = Stripe_Service::retrieve_checkout_session( $session_id );
			if ( $session ) {
				self::handle_pack_purchase_completed( $session );
			}
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Pack purchase reconcile failed: ' . $e->getMessage() );
		}
	}

	public static function validate_pack_on_save( $post_id ): void {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || CPT::PACK_PT !== get_post_type( $post_id ) ) {
			return;
		}
		$pack = self::get_pack_data( $post_id );
		if ( ! $pack || empty( $pack['class_ids'] ) ) {
			return;
		}
		$unit_pence = Helpers::to_pence( $pack['unit_price'] );
		$mismatch   = [];
		foreach ( $pack['class_ids'] as $class_id ) {
			$class = Helpers::get_class_data( (int) $class_id );
			if ( ! $class ) {
				continue;
			}
			if ( Helpers::to_pence( $class['price'] ) !== $unit_pence ) {
				$mismatch[] = get_the_title( $class_id );
			}
		}
		if ( $mismatch ) {
			set_transient(
				'clasbpro_pack_price_warning_' . $post_id,
				$mismatch,
				MINUTE_IN_SECONDS * 5
			);
		}
		add_action( 'admin_notices', [ self::class, 'render_pack_price_warning' ] );
	}

	public static function render_pack_price_warning(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::PACK_PT !== $screen->post_type ) {
			return;
		}
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $post_id ) {
			return;
		}
		$mismatch = get_transient( 'clasbpro_pack_price_warning_' . $post_id );
		if ( ! is_array( $mismatch ) || ! $mismatch ) {
			return;
		}
		delete_transient( 'clasbpro_pack_price_warning_' . $post_id );
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Some eligible classes do not match this coupon’s unit price. Coupon redemption will be blocked for those classes until prices match:', 'class-bookings-with-stripe-pro' );
		echo ' <strong>' . esc_html( implode( ', ', $mismatch ) ) . '</strong>';
		echo '</p></div>';
	}

	public static function render_pack_shortcode_hint(): void {
		$post = get_post();
		if ( ! $post || CPT::PACK_PT !== $post->post_type ) {
			return;
		}
		$shortcode = sprintf( '[clasbpro_coupons id="%d"]', (int) $post->ID );
		echo '<div class="misc-pub-section misc-pub-cbfs-shortcode">';
		echo '<span>' . esc_html__( 'Shortcode:', 'class-bookings-with-stripe-pro' ) . '</span><br>';
		echo '<code style="display:inline-block;margin-top:6px;user-select:all;">' . esc_html( $shortcode ) . '</code>';
		echo '<p style="margin:6px 0 0;color:#646970;font-size:12px;">' . esc_html__( 'Copy and paste this into any page, post, or Elementor shortcode widget. Use comma-separated ids to list several coupons, e.g. id="1,2".', 'class-bookings-with-stripe-pro' ) . '</p>';
		echo '</div>';
	}

	public static function pack_columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['clasbpro_pack_price']  = __( 'Coupon price', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_pack_uses']  = __( 'Uses', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_pack_unit']  = __( 'Unit price', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_pack_classes'] = __( 'Classes', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_pack_status'] = __( 'Status', 'class-bookings-with-stripe-pro' );
			}
		}
		return $new;
	}

	public static function pack_column_value( string $column, int $post_id ): void {
		$pack = self::get_pack_data( $post_id );
		if ( ! $pack ) {
			return;
		}
		switch ( $column ) {
			case 'clasbpro_pack_price':
				echo esc_html( Helpers::format_price( $pack['price'] ) );
				break;
			case 'clasbpro_pack_uses':
				echo esc_html( (string) $pack['uses'] );
				break;
			case 'clasbpro_pack_unit':
				echo esc_html( Helpers::format_price( $pack['unit_price'] ) );
				break;
			case 'clasbpro_pack_classes':
				echo esc_html( (string) count( $pack['class_ids'] ) );
				break;
			case 'clasbpro_pack_status':
				echo ! empty( $pack['active'] )
					? esc_html__( 'Active', 'class-bookings-with-stripe-pro' )
					: '<strong style="color:#b00;">' . esc_html__( 'Inactive', 'class-bookings-with-stripe-pro' ) . '</strong>';
				break;
		}
	}

	public static function purchase_columns( array $columns ): array {
		unset( $columns['date'] );
		return [
			'cb'                => $columns['cb'] ?? '<input type="checkbox" />',
			'title'             => __( 'Purchase', 'class-bookings-with-stripe-pro' ),
			'clasbpro_pack'     => __( 'Coupon', 'class-bookings-with-stripe-pro' ),
			'clasbpro_customer' => __( 'Customer', 'class-bookings-with-stripe-pro' ),
			'clasbpro_amount'   => __( 'Amount', 'class-bookings-with-stripe-pro' ),
			'clasbpro_status'   => __( 'Status', 'class-bookings-with-stripe-pro' ),
			'clasbpro_promo'    => __( 'Stripe promo', 'class-bookings-with-stripe-pro' ),
			'clasbpro_created'  => __( 'Created', 'class-bookings-with-stripe-pro' ),
		];
	}

	public static function purchase_column_value( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'clasbpro_pack':
				$pack_id = (int) get_post_meta( $post_id, '_clasbpro_pack_id', true );
				if ( $pack_id ) {
					echo '<a href="' . esc_url( get_edit_post_link( $pack_id ) ) . '">' . esc_html( get_the_title( $pack_id ) ) . '</a>';
				}
				break;
			case 'clasbpro_customer':
				$name  = (string) get_post_meta( $post_id, '_clasbpro_customer_name', true );
				$email = (string) get_post_meta( $post_id, '_clasbpro_customer_email', true );
				echo esc_html( $name );
				if ( $email ) {
					echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
				}
				break;
			case 'clasbpro_amount':
				echo esc_html( Helpers::format_stripe_amount( (int) get_post_meta( $post_id, '_clasbpro_amount_total', true ) ) );
				break;
			case 'clasbpro_status':
				$status = (string) get_post_meta( $post_id, '_clasbpro_status', true );
				$colors = [
					'paid'    => '#0a7e1a',
					'pending' => '#a86b00',
					'expired' => '#888',
				];
				$color = $colors[ $status ] ?? '#444';
				echo '<strong style="color:' . esc_attr( $color ) . ';">' . esc_html( ucfirst( $status ?: 'unknown' ) ) . '</strong>';
				break;
			case 'clasbpro_promo':
				$id = (string) get_post_meta( $post_id, '_clasbpro_stripe_promo_id', true );
				if ( $id ) {
					echo '<code style="font-size:11px;">' . esc_html( $id ) . '</code>';
				}
				break;
			case 'clasbpro_created':
				$post = get_post( $post_id );
				if ( $post ) {
					echo esc_html( get_the_date( 'Y-m-d H:i', $post ) );
				}
				break;
		}
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private static function sign_payload( array $payload ): string {
		$json = wp_json_encode( $payload );
		$b64  = rtrim( strtr( base64_encode( (string) $json ), '+/', '-_' ), '=' );
		$sig  = hash_hmac( 'sha256', $b64, self::signing_key() );
		return $b64 . '.' . $sig;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function verify_signed_token( string $token ): ?array {
		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) ) {
			return null;
		}
		[ $b64, $sig ] = $parts;
		$expected = hash_hmac( 'sha256', $b64, self::signing_key() );
		if ( ! hash_equals( $expected, $sig ) ) {
			return null;
		}
		$json = base64_decode( strtr( $b64, '-_', '+/' ), true );
		if ( false === $json ) {
			return null;
		}
		$data = json_decode( $json, true );
		return is_array( $data ) ? $data : null;
	}

	private static function signing_key(): string {
		return 'clasbpro_pack|' . wp_salt( 'auth' );
	}
}
