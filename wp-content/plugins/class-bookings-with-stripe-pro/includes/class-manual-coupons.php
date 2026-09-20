<?php
/**
 * Admin-issued Stripe promotion codes (shared or email-locked).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Manual_Coupons {

	public const META_CODE      = '_clasbpro_manual_code';
	public const META_COUPON_ID = '_clasbpro_stripe_coupon_id';
	public const META_PROMO_ID  = '_clasbpro_stripe_promo_id';

	/** @var array<int, int> */
	private static array $consumed_cache = [];

	public static function init(): void {
		add_filter( 'enter_title_here', [ self::class, 'enter_title_here' ], 10, 2 );
		add_action( 'acf/save_post', [ self::class, 'on_save' ], 30 );
		add_action( 'acf/save_post', [ self::class, 'apply_duration_shortcut' ], 15 );
		add_action( 'trashed_post', [ self::class, 'on_trashed' ] );
		add_action( 'untrashed_post', [ self::class, 'on_untrashed' ] );
		add_action( 'admin_notices', [ self::class, 'render_admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_assets' ] );
		add_action( 'acf/render_field/key=field_clasbpro_mc_code', [ self::class, 'render_generate_button' ] );
		add_filter( 'acf/prepare_field/key=field_clasbpro_mc_code', [ self::class, 'lock_stripe_owned_field' ] );
		add_filter( 'acf/prepare_field/key=field_clasbpro_mc_discount_type', [ self::class, 'lock_stripe_owned_field' ] );
		add_filter( 'acf/prepare_field/key=field_clasbpro_mc_discount_value', [ self::class, 'lock_stripe_owned_field' ] );
		add_filter( 'acf/update_value/name=manual_code', [ self::class, 'sanitize_code_value' ], 10, 3 );
		add_filter( 'manage_' . CPT::MANUAL_COUPON_PT . '_posts_columns', [ self::class, 'columns' ] );
		add_action( 'manage_' . CPT::MANUAL_COUPON_PT . '_posts_custom_column', [ self::class, 'column_value' ], 10, 2 );
	}

	public static function enter_title_here( string $title, $post ): string {
		if ( $post && CPT::MANUAL_COUPON_PT === $post->post_type ) {
			return __( 'Internal name (e.g. Spring intro)', 'class-bookings-with-stripe-pro' );
		}
		return $title;
	}

	public static function normalize_code( string $code ): string {
		$code = strtoupper( trim( $code ) );
		$code = (string) preg_replace( '/[^A-Z0-9-]/', '', $code );
		return $code;
	}

	public static function generate_code(): string {
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$code     = 'MC-';
		for ( $i = 0; $i < 8; $i++ ) {
			$code .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
		}
		return $code;
	}

	/**
	 * Remaining charge for one seat after this discount, in Stripe minor units.
	 *
	 * @param float|int|string $discount_value Percent (0–100) or major-unit amount.
	 */
	public static function remaining_unit_pence( int $unit_pence, string $type, $discount_value ): int {
		$unit_pence = max( 0, $unit_pence );
		if ( 'amount' === $type ) {
			$off = Helpers::to_pence( $discount_value );
			return max( 0, $unit_pence - $off );
		}
		$percent = (float) $discount_value;
		if ( $percent < 0 ) {
			$percent = 0;
		}
		if ( $percent > 100 ) {
			$percent = 100;
		}
		return (int) round( $unit_pence * ( ( 100 - $percent ) / 100 ) );
	}

	/**
	 * @param list<int> $class_ids Empty = every class.
	 */
	public static function class_is_eligible( int $class_id, array $class_ids ): bool {
		if ( $class_id <= 0 ) {
			return false;
		}
		if ( [] === $class_ids ) {
			return true;
		}
		return in_array( $class_id, $class_ids, true );
	}

	public static function email_lock_allows( string $lock_email, string $form_email ): bool {
		$lock = strtolower( sanitize_email( $lock_email ) );
		$form = strtolower( sanitize_email( $form_email ) );
		if ( ! is_email( $lock ) ) {
			return true;
		}
		if ( ! is_email( $form ) ) {
			return false;
		}
		return $lock === $form;
	}

	/**
	 * @return array{unlimited: bool, remaining: int, total: int}
	 */
	public static function uses_state( int $total, int $consumed ): array {
		$total     = max( 0, $total );
		$consumed  = max( 0, $consumed );
		$unlimited = $total <= 0;
		return [
			'unlimited' => $unlimited,
			'total'     => $total,
			'remaining' => $unlimited ? 0 : max( 0, $total - $consumed ),
		];
	}

	/**
	 * Unix timestamp at end of the resulting civil day in the site timezone.
	 */
	public static function expiry_from_duration( int $n, string $unit, ?int $from_ts = null ): int {
		$n = max( 0, $n );
		if ( $n <= 0 ) {
			return 0;
		}
		$tz   = wp_timezone();
		$from = null !== $from_ts
			? ( new \DateTimeImmutable( '@' . $from_ts ) )->setTimezone( $tz )
			: Helpers::now();
		$mod  = 'months' === $unit ? '+' . $n . ' months' : '+' . $n . ' days';
		$end  = $from->modify( $mod );
		if ( ! $end instanceof \DateTimeImmutable ) {
			return 0;
		}
		return (int) $end->setTime( 23, 59, 59 )->getTimestamp();
	}

	/**
	 * @param mixed $raw
	 * @return list<int>
	 */
	public static function normalize_class_ids( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$ids = [];
		foreach ( $raw as $item ) {
			$id = 0;
			if ( is_object( $item ) && isset( $item->ID ) ) {
				$id = (int) $item->ID;
			} elseif ( is_numeric( $item ) ) {
				$id = (int) $item;
			} elseif ( is_array( $item ) && isset( $item['ID'] ) ) {
				$id = (int) $item['ID'];
			}
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_coupon_data( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || CPT::MANUAL_COUPON_PT !== $post->post_type ) {
			return null;
		}

		$code = self::normalize_code(
			(string) ( function_exists( 'get_field' ) ? get_field( 'manual_code', $post_id ) : get_post_meta( $post_id, 'manual_code', true ) )
		);
		if ( '' === $code ) {
			$code = self::normalize_code( (string) get_post_meta( $post_id, self::META_CODE, true ) );
		}

		$active = true;
		if ( function_exists( 'get_field' ) ) {
			$active_raw = get_field( 'manual_active', $post_id );
			if ( null !== $active_raw && false !== $active_raw && '' !== $active_raw ) {
				$active = (bool) $active_raw;
			} elseif ( false === $active_raw ) {
				$active = false;
			}
		} else {
			$active = (bool) get_post_meta( $post_id, 'manual_active', true );
		}

		$type = (string) ( function_exists( 'get_field' ) ? get_field( 'manual_discount_type', $post_id ) : get_post_meta( $post_id, 'manual_discount_type', true ) );
		if ( 'amount' !== $type ) {
			$type = 'percent';
		}
		$value = (float) ( function_exists( 'get_field' ) ? get_field( 'manual_discount_value', $post_id ) : get_post_meta( $post_id, 'manual_discount_value', true ) );
		$uses  = (int) ( function_exists( 'get_field' ) ? get_field( 'manual_uses', $post_id ) : get_post_meta( $post_id, 'manual_uses', true ) );
		$email = strtolower( sanitize_email( (string) ( function_exists( 'get_field' ) ? get_field( 'manual_email', $post_id ) : get_post_meta( $post_id, 'manual_email', true ) ) ) );

		$expires_on = (string) ( function_exists( 'get_field' ) ? get_field( 'manual_expires_on', $post_id ) : get_post_meta( $post_id, 'manual_expires_on', true ) );
		$expires_at = self::timestamp_from_date_field( $expires_on );

		$raw_classes = function_exists( 'get_field' ) ? get_field( 'manual_classes', $post_id ) : null;
		if ( ! is_array( $raw_classes ) ) {
			$raw_classes = get_post_meta( $post_id, 'manual_classes', true );
		}
		$class_ids   = self::normalize_class_ids( is_array( $raw_classes ) ? $raw_classes : [] );

		$trashed = 'trash' === $post->post_status;

		return [
			'id'            => $post_id,
			'name'          => get_the_title( $post_id ) ?: $code,
			'code'          => $code,
			'active'        => $active && ! $trashed,
			'discount_type' => $type,
			'discount_value'=> $value,
			'uses'          => max( 0, $uses ),
			'email'         => is_email( $email ) ? $email : '',
			'expires_at'    => $expires_at,
			'class_ids'     => $class_ids,
			'promo_id'      => (string) get_post_meta( $post_id, self::META_PROMO_ID, true ),
			'coupon_id'     => (string) get_post_meta( $post_id, self::META_COUPON_ID, true ),
			'trashed'       => $trashed,
		];
	}

	public static function timestamp_from_date_field( string $date ): int {
		$date = trim( $date );
		if ( '' === $date ) {
			return 0;
		}
		if ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $date, $m ) ) {
			$date = $m[1] . '-' . $m[2] . '-' . $m[3];
		}
		$tz = wp_timezone();
		$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $date . ' 23:59:59', $tz );
		if ( ! $dt instanceof \DateTimeImmutable ) {
			$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date, $tz );
			if ( $dt instanceof \DateTimeImmutable ) {
				$dt = $dt->setTime( 23, 59, 59 );
			}
		}
		return $dt instanceof \DateTimeImmutable ? (int) $dt->getTimestamp() : 0;
	}

	public static function find_by_code( string $code ): ?array {
		$code = self::normalize_code( $code );
		if ( '' === $code ) {
			return null;
		}
		$q = new \WP_Query( [
			'post_type'              => CPT::MANUAL_COUPON_PT,
			'post_status'            => [ 'publish', 'draft', 'private' ],
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'meta_query'             => [
				[
					'key'   => self::META_CODE,
					'value' => $code,
				],
			],
		] );
		$id = (int) ( $q->posts[0] ?? 0 );
		return $id > 0 ? self::get_coupon_data( $id ) : null;
	}

	public static function find_by_promo_id( string $promo_id ): ?array {
		$promo_id = trim( $promo_id );
		if ( '' === $promo_id ) {
			return null;
		}
		$q = new \WP_Query( [
			'post_type'      => CPT::MANUAL_COUPON_PT,
			'post_status'    => [ 'publish', 'draft', 'private', 'trash' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'   => self::META_PROMO_ID,
					'value' => $promo_id,
				],
			],
		] );
		$id = (int) ( $q->posts[0] ?? 0 );
		return $id > 0 ? self::get_coupon_data( $id ) : null;
	}

	public static function is_manual_promo( $promo ): bool {
		if ( ! is_object( $promo ) ) {
			return false;
		}
		$manual_id = (int) ( $promo->metadata->clasbpro_manual_id ?? 0 );
		if ( $manual_id > 0 ) {
			return true;
		}
		return '1' === (string) ( $promo->metadata->clasbpro_manual ?? '' );
	}

	public static function count_consumed_uses( int $post_id ): int {
		if ( $post_id <= 0 ) {
			return 0;
		}
		if ( isset( self::$consumed_cache[ $post_id ] ) ) {
			return self::$consumed_cache[ $post_id ];
		}
		$promo_id = (string) get_post_meta( $post_id, self::META_PROMO_ID, true );
		if ( '' === $promo_id ) {
			self::$consumed_cache[ $post_id ] = 0;
			return 0;
		}
		$q = new \WP_Query( [
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'   => '_clasbpro_pack_promo_id',
					'value' => $promo_id,
				],
				[
					'key'     => '_clasbpro_status',
					'value'   => [ Bookings::STATUS_PAID, Bookings::STATUS_REFUNDED ],
					'compare' => 'IN',
				],
			],
		] );
		$count = (int) $q->found_posts;
		self::$consumed_cache[ $post_id ] = $count;
		return $count;
	}

	/**
	 * Why this coupon cannot be used, or null if it can.
	 *
	 * @param array<string, mixed> $coupon
	 * @return array{code: string, message: string}|null
	 */
	public static function ineligibility_reason( array $coupon, int $class_id, string $form_email ): ?array {
		if ( empty( $coupon['active'] ) ) {
			return [
				'code'    => 'inactive',
				'message' => __( 'This coupon has been deactivated.', 'class-bookings-with-stripe-pro' ),
			];
		}
		$expires_at = (int) ( $coupon['expires_at'] ?? 0 );
		if ( $expires_at > 0 && $expires_at < time() ) {
			return [
				'code'    => 'expired',
				'message' => __( 'This coupon has expired.', 'class-bookings-with-stripe-pro' ),
			];
		}
		$uses = self::uses_state( (int) ( $coupon['uses'] ?? 0 ), self::count_consumed_uses( (int) ( $coupon['id'] ?? 0 ) ) );
		if ( empty( $uses['unlimited'] ) && $uses['remaining'] <= 0 ) {
			return [
				'code'    => 'no_uses',
				'message' => __( 'This coupon has no uses left.', 'class-bookings-with-stripe-pro' ),
			];
		}
		if ( ! self::email_lock_allows( (string) ( $coupon['email'] ?? '' ), $form_email ) ) {
			return [
				'code'    => 'email_mismatch',
				'message' => __( 'That coupon belongs to a different email address.', 'class-bookings-with-stripe-pro' ),
			];
		}
		if ( $class_id > 0 && ! self::class_is_eligible( $class_id, $coupon['class_ids'] ?? [] ) ) {
			return [
				'code'    => 'class_not_covered',
				'message' => __( 'This coupon isn’t valid for this class.', 'class-bookings-with-stripe-pro' ),
			];
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $coupon
	 * @return array<string, mixed>
	 */
	public static function attach( array $coupon, string $email, int $class_id = 0 ): array {
		$code = (string) ( $coupon['code'] ?? '' );
		if ( '' === $code ) {
			return [ 'ok' => false, 'message' => __( 'Please enter a coupon code.', 'class-bookings-with-stripe-pro' ) ];
		}
		$promo_id = (string) ( $coupon['promo_id'] ?? '' );
		if ( '' === $promo_id ) {
			return [ 'ok' => false, 'message' => __( 'That coupon is not registered with Stripe yet.', 'class-bookings-with-stripe-pro' ) ];
		}

		$email = strtolower( sanitize_email( $email ) );
		$lock  = (string) ( $coupon['email'] ?? '' );
		if ( is_email( $lock ) && is_email( $email ) && $email !== $lock ) {
			return [ 'ok' => false, 'message' => __( 'That coupon belongs to a different email address.', 'class-bookings-with-stripe-pro' ) ];
		}
		if ( is_email( $lock ) && ! is_email( $email ) ) {
			return [ 'ok' => false, 'message' => __( 'Enter the email this coupon is registered to.', 'class-bookings-with-stripe-pro' ) ];
		}

		$bind_email = is_email( $lock ) ? $lock : $email;
		$block      = self::ineligibility_reason( $coupon, $class_id, $bind_email );
		if ( $block && in_array( $block['code'], [ 'inactive', 'expired', 'no_uses' ], true ) ) {
			return [ 'ok' => false, 'message' => $block['message'] ];
		}

		Packs::set_active_cookie( $promo_id, $bind_email, (int) ( $coupon['expires_at'] ?? 0 ) );

		if ( $class_id > 0 ) {
			return Packs::status_for_class( $class_id, $bind_email );
		}

		$uses = self::uses_state( (int) $coupon['uses'], self::count_consumed_uses( (int) $coupon['id'] ) );
		return [
			'ok'             => true,
			'recognised'     => true,
			'eligible'       => true,
			'is_manual'      => true,
			'promo_id'       => $promo_id,
			'email'          => $bind_email,
			'code'           => $code,
			'pack_name'      => (string) $coupon['name'],
			'uses_remaining' => $uses['remaining'],
			'uses_total'     => $uses['total'],
			'uses_unlimited' => $uses['unlimited'],
			'expires_at'     => (int) ( $coupon['expires_at'] ?? 0 ),
		];
	}

	/**
	 * Status payload overlay when the active promo is a manual coupon.
	 *
	 * @param array<string, mixed> $coupon
	 * @return array<string, mixed>
	 */
	public static function status_for_class( array $coupon, int $class_id, string $form_email, string $cookie_email ): array {
		$form_email = strtolower( sanitize_email( $form_email ) );
		$check_email = is_email( $form_email ) ? $form_email : $cookie_email;
		$uses        = self::uses_state( (int) $coupon['uses'], self::count_consumed_uses( (int) $coupon['id'] ) );
		$block       = self::ineligibility_reason( $coupon, $class_id, $check_email );

		$eligible = null === $block;
		$pay_pence = 0;
		$class     = $class_id ? Helpers::get_class_data( $class_id ) : null;
		if ( $class ) {
			$pay_pence = self::remaining_unit_pence(
				Helpers::to_pence( $class['price'] ?? 0 ),
				(string) $coupon['discount_type'],
				$coupon['discount_value']
			);
		}
		$code = (string) $coupon['code'];
		$choice_label = sprintf(
			/* translators: 1: coupon code, 2: remaining price */
			__( 'Use %1$s — pay %2$s (1 seat)', 'class-bookings-with-stripe-pro' ),
			$code,
			Helpers::format_stripe_amount( $pay_pence )
		);

		return [
			'recognised'       => true,
			'eligible'         => $eligible,
			'is_manual'        => true,
			'email_locked'     => is_email( (string) ( $coupon['email'] ?? '' ) ),
			'uses_remaining'   => $uses['remaining'],
			'uses_total'       => $uses['total'],
			'uses_used'        => $uses['unlimited'] ? self::count_consumed_uses( (int) $coupon['id'] ) : max( 0, $uses['total'] - $uses['remaining'] ),
			'uses_unlimited'   => $uses['unlimited'],
			'pack_name'        => (string) $coupon['name'],
			'pack_id'          => 0,
			'promo_id'         => (string) $coupon['promo_id'],
			'code'             => $code,
			'email'            => is_email( (string) ( $coupon['email'] ?? '' ) ) ? (string) $coupon['email'] : $cookie_email,
			'message'          => $block['message'] ?? '',
			'reason_code'      => $block['code'] ?? '',
			'pay_pence'        => $pay_pence,
			'pay_formatted'    => Helpers::format_stripe_amount( $pay_pence ),
			'choice_label'     => $choice_label,
			'restore_token'    => Packs::build_restore_token(
				(string) $coupon['promo_id'],
				is_email( $cookie_email ) ? $cookie_email : (string) ( $coupon['email'] ?? '' ),
				(int) ( $coupon['expires_at'] ?? 0 )
			),
		];
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @param array<string, mixed>|false $field
	 * @return mixed
	 */
	public static function sanitize_code_value( $value, $post_id, $field ) {
		unset( $field );
		$normalized = self::normalize_code( (string) $value );
		$post_id    = (int) $post_id;
		if ( $post_id > 0 && '' !== (string) get_post_meta( $post_id, self::META_COUPON_ID, true ) ) {
			$existing = self::normalize_code( (string) get_post_meta( $post_id, self::META_CODE, true ) );
			return '' !== $existing ? $existing : $normalized;
		}
		return $normalized;
	}

	/**
	 * @param array<string, mixed>|false $field
	 * @return array<string, mixed>|false
	 */
	public static function lock_stripe_owned_field( $field ) {
		if ( ! is_array( $field ) ) {
			return $field;
		}
		$post_id = get_the_ID();
		if ( ! $post_id || CPT::MANUAL_COUPON_PT !== get_post_type( $post_id ) ) {
			return $field;
		}
		if ( '' === (string) get_post_meta( (int) $post_id, self::META_COUPON_ID, true ) ) {
			return $field;
		}
		$field['disabled']     = 1;
		$field['readonly']     = 1;
		$field['instructions'] = trim( (string) ( $field['instructions'] ?? '' ) . ' ' . __( 'Locked after Stripe registration. Pause this coupon and create a new code to change it.', 'class-bookings-with-stripe-pro' ) );
		return $field;
	}

	public static function render_generate_button( $field ): void {
		unset( $field );
		$post_id = get_the_ID();
		$locked  = $post_id && '' !== (string) get_post_meta( (int) $post_id, self::META_COUPON_ID, true );
		if ( $locked ) {
			return;
		}
		echo '<p><button type="button" class="button" data-clasbpro-generate-code>';
		echo esc_html__( 'Generate', 'class-bookings-with-stripe-pro' );
		echo '</button></p>';
	}

	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::MANUAL_COUPON_PT !== $screen->post_type ) {
			return;
		}
		$path = CLASBOWPRO_DIR . 'assets/cbfs-manual-coupon-admin.js';
		wp_enqueue_script(
			'clasbpro-manual-coupon-admin',
			CLASBOWPRO_URL . 'assets/cbfs-manual-coupon-admin.js',
			[ 'jquery' ],
			is_readable( $path ) ? (string) filemtime( $path ) : CLASBOWPRO_VERSION,
			true
		);
	}

	/**
	 * Duration shortcut writes a date once, then clears itself.
	 */
	/** @var bool */
	private static bool $saving = false;

	public static function apply_duration_shortcut( $post_id ): void {
		if ( self::$saving ) {
			return;
		}
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || CPT::MANUAL_COUPON_PT !== get_post_type( $post_id ) ) {
			return;
		}
		$expires_on = (string) ( function_exists( 'get_field' ) ? get_field( 'manual_expires_on', $post_id ) : '' );
		if ( '' !== trim( $expires_on ) ) {
			return;
		}
		$amount = (int) ( function_exists( 'get_field' ) ? get_field( 'manual_expiry_amount', $post_id ) : 0 );
		$unit   = (string) ( function_exists( 'get_field' ) ? get_field( 'manual_expiry_unit', $post_id ) : 'days' );
		if ( $amount <= 0 ) {
			return;
		}
		$ts   = self::expiry_from_duration( $amount, $unit );
		$date = wp_date( 'Y-m-d', $ts );
		update_post_meta( $post_id, 'manual_expires_on', $date );
		update_post_meta( $post_id, 'manual_expiry_amount', 0 );
		if ( function_exists( 'update_field' ) ) {
			self::$saving = true;
			update_field( 'manual_expires_on', $date, $post_id );
			update_field( 'manual_expiry_amount', 0, $post_id );
			self::$saving = false;
		}
	}

	public static function on_save( $post_id ): void {
		if ( self::$saving ) {
			return;
		}
		$post_id = (int) $post_id;
		if ( $post_id <= 0 || CPT::MANUAL_COUPON_PT !== get_post_type( $post_id ) ) {
			return;
		}
		$coupon = self::get_coupon_data( $post_id );
		if ( ! $coupon ) {
			return;
		}
		if ( '' === $coupon['code'] ) {
			self::set_notice( $post_id, 'error', __( 'Enter or generate a coupon code.', 'class-bookings-with-stripe-pro' ) );
			return;
		}

		update_post_meta( $post_id, self::META_CODE, $coupon['code'] );

		$other = self::find_by_code( $coupon['code'] );
		if ( $other && (int) $other['id'] !== $post_id ) {
			self::set_notice( $post_id, 'error', __( 'That code is already used by another manual coupon.', 'class-bookings-with-stripe-pro' ) );
			return;
		}

		try {
			if ( '' === $coupon['coupon_id'] || '' === $coupon['promo_id'] ) {
				$created = Stripe_Service::create_manual_coupon( $coupon );
				update_post_meta( $post_id, self::META_COUPON_ID, (string) $created['coupon_id'] );
				update_post_meta( $post_id, self::META_PROMO_ID, (string) $created['promo_id'] );
				self::set_notice( $post_id, 'success', __( 'Coupon registered in Stripe.', 'class-bookings-with-stripe-pro' ) );
				return;
			}
			Stripe_Service::update_manual_promotion_code( $coupon );
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Manual coupon Stripe sync failed: ' . $e->getMessage() );
			self::set_notice(
				$post_id,
				'error',
				sprintf(
					/* translators: %s: error message */
					__( 'Could not register this coupon in Stripe: %s', 'class-bookings-with-stripe-pro' ),
					$e->getMessage()
				)
			);
		}
	}

	public static function on_trashed( int $post_id ): void {
		if ( CPT::MANUAL_COUPON_PT !== get_post_type( $post_id ) ) {
			return;
		}
		self::set_stripe_active( $post_id, false );
	}

	public static function on_untrashed( int $post_id ): void {
		if ( CPT::MANUAL_COUPON_PT !== get_post_type( $post_id ) ) {
			return;
		}
		$coupon = self::get_coupon_data( $post_id );
		if ( ! $coupon ) {
			return;
		}
		$expired = (int) $coupon['expires_at'] > 0 && (int) $coupon['expires_at'] < time();
		self::set_stripe_active( $post_id, ! empty( $coupon['active'] ) && ! $expired );
	}

	private static function set_stripe_active( int $post_id, bool $active ): void {
		$promo_id = (string) get_post_meta( $post_id, self::META_PROMO_ID, true );
		if ( '' === $promo_id ) {
			return;
		}
		try {
			Stripe_Service::set_promotion_code_active( $promo_id, $active );
		} catch ( \Throwable $e ) {
			Helpers::debug_log( '[class-bookings-with-stripe-pro] Manual coupon active sync failed: ' . $e->getMessage() );
		}
	}

	private static function set_notice( int $post_id, string $type, string $message ): void {
		set_transient(
			'clasbpro_mc_notice_' . $post_id,
			[
				'type'    => $type,
				'message' => $message,
			],
			MINUTE_IN_SECONDS * 5
		);
	}

	public static function render_admin_notices(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::MANUAL_COUPON_PT !== $screen->post_type ) {
			return;
		}
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $post_id ) {
			return;
		}
		$notice = get_transient( 'clasbpro_mc_notice_' . $post_id );
		if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
			return;
		}
		delete_transient( 'clasbpro_mc_notice_' . $post_id );
		$class = 'success' === ( $notice['type'] ?? '' ) ? 'notice-success' : 'notice-error';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>';
		echo esc_html( (string) $notice['message'] );
		echo '</p></div>';
	}

	public static function columns( array $columns ): array {
		unset( $columns['date'] );
		return [
			'cb'                 => $columns['cb'] ?? '<input type="checkbox" />',
			'title'              => __( 'Name', 'class-bookings-with-stripe-pro' ),
			'clasbpro_mc_code'   => __( 'Code', 'class-bookings-with-stripe-pro' ),
			'clasbpro_mc_disc'   => __( 'Discount', 'class-bookings-with-stripe-pro' ),
			'clasbpro_mc_uses'   => __( 'Uses', 'class-bookings-with-stripe-pro' ),
			'clasbpro_mc_exp'    => __( 'Expires', 'class-bookings-with-stripe-pro' ),
			'clasbpro_mc_email'  => __( 'Email lock', 'class-bookings-with-stripe-pro' ),
			'clasbpro_mc_status' => __( 'Status', 'class-bookings-with-stripe-pro' ),
		];
	}

	public static function column_value( string $column, int $post_id ): void {
		$coupon = self::get_coupon_data( $post_id );
		if ( ! $coupon ) {
			return;
		}
		switch ( $column ) {
			case 'clasbpro_mc_code':
				echo '<code>' . esc_html( (string) $coupon['code'] ) . '</code>';
				break;
			case 'clasbpro_mc_disc':
				if ( 'amount' === $coupon['discount_type'] ) {
					echo esc_html( Helpers::format_price( $coupon['discount_value'] ) . ' ' . __( 'off', 'class-bookings-with-stripe-pro' ) );
				} else {
					echo esc_html( rtrim( rtrim( (string) (float) $coupon['discount_value'], '0' ), '.' ) . '%' );
				}
				break;
			case 'clasbpro_mc_uses':
				$uses = self::uses_state( (int) $coupon['uses'], self::count_consumed_uses( $post_id ) );
				if ( $uses['unlimited'] ) {
					echo esc_html__( 'Unlimited', 'class-bookings-with-stripe-pro' );
				} else {
					echo esc_html(
						sprintf(
							/* translators: 1: used count, 2: total uses */
							__( '%1$d of %2$d used', 'class-bookings-with-stripe-pro' ),
							$uses['total'] - $uses['remaining'],
							$uses['total']
						)
					);
				}
				break;
			case 'clasbpro_mc_exp':
				if ( (int) $coupon['expires_at'] > 0 ) {
					echo esc_html( Helpers::format_date( Helpers::civil_date_from_timestamp( (int) $coupon['expires_at'] ) ) );
				} else {
					echo esc_html__( 'Never', 'class-bookings-with-stripe-pro' );
				}
				break;
			case 'clasbpro_mc_email':
				echo $coupon['email'] ? esc_html( (string) $coupon['email'] ) : '—';
				break;
			case 'clasbpro_mc_status':
				echo ! empty( $coupon['active'] )
					? esc_html__( 'Active', 'class-bookings-with-stripe-pro' )
					: '<strong style="color:#b00;">' . esc_html__( 'Inactive', 'class-bookings-with-stripe-pro' ) . '</strong>';
				break;
		}
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function get_usages( int $post_id ): array {
		$promo_id = (string) get_post_meta( $post_id, self::META_PROMO_ID, true );
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
				'booking_id'   => $booking_id,
				'status'       => (string) ( $meta['status'] ?? '' ),
				'class_name'   => (string) ( $class_data['name'] ?? get_the_title( $class_id ) ),
				'class_date'   => Helpers::format_date( (string) ( $meta['class_date'] ?? '' ) ),
				'class_time'   => Helpers::format_time( (string) ( $display['start_time'] ?? '' ) ),
				'location'     => (string) ( $display['location'] ?? '' ),
				'customer_name'=> (string) ( $meta['customer_name'] ?? '' ),
				'edit_url'     => $edit_url,
			];
		}
		return $out;
	}
}
