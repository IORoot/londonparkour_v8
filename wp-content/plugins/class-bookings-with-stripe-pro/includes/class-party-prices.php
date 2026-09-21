<?php
/**
 * Appointment party-size prices: per-person rates keyed by party size.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Party_Prices {

	public const META_KEY = '_clasbpro_party_prices';

	public static function init(): void {
		add_action( 'acf/render_field/key=field_clasbpro_party_prices', [ self::class, 'render_field' ] );
		add_action( 'save_post_' . CPT::CLASS_PT, [ self::class, 'save_from_request' ], 20, 2 );
		add_action( 'admin_notices', [ self::class, 'maybe_show_incomplete_notice' ] );
	}

	/**
	 * @param mixed $raw Posted map or stored meta.
	 * @return array<int, float>
	 */
	public static function sanitize_map( $raw, int $capacity ): array {
		$capacity = max( 0, $capacity );
		if ( $capacity < 1 || ! is_array( $raw ) ) {
			return [];
		}

		$clean = [];
		foreach ( $raw as $key => $value ) {
			$seats = (int) $key;
			if ( $seats < 1 || $seats > $capacity ) {
				continue;
			}
			if ( ! self::is_present_amount( $value ) ) {
				continue;
			}
			$clean[ $seats ] = (float) max( 0, (float) $value );
		}

		ksort( $clean, SORT_NUMERIC );
		return $clean;
	}

	/**
	 * Empty string / null is missing. 0 is a valid free rate.
	 *
	 * @param mixed $value
	 */
	public static function is_present_amount( $value ): bool {
		if ( null === $value || false === $value ) {
			return false;
		}
		if ( is_string( $value ) ) {
			return '' !== trim( $value );
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			return true;
		}
		if ( is_bool( $value ) ) {
			return false;
		}
		return false;
	}

	/**
	 * @param array<int, float> $map
	 */
	public static function unit_price_for_seats( array $map, int $seats ): ?float {
		if ( $seats < 1 || ! array_key_exists( $seats, $map ) ) {
			return null;
		}
		return (float) $map[ $seats ];
	}

	/**
	 * @param array<int, float> $map
	 */
	public static function total_pence_for_seats( array $map, int $seats ): ?int {
		$unit = self::unit_price_for_seats( $map, $seats );
		if ( null === $unit ) {
			return null;
		}
		return Helpers::to_pence( $unit ) * $seats;
	}

	/**
	 * Cheapest session total (people × per-person), not the cheapest per-person rate.
	 *
	 * @param array<int, float> $map
	 */
	public static function cheapest_session_total( array $map ): ?float {
		$min = null;
		foreach ( $map as $seats => $unit ) {
			$seats = (int) $seats;
			if ( $seats < 1 ) {
				continue;
			}
			$total = (float) $unit * $seats;
			if ( null === $min || $total < $min ) {
				$min = $total;
			}
		}
		return $min;
	}

	/**
	 * @param array<int, float> $map
	 */
	public static function one_person_rate( array $map ): float {
		$unit = self::unit_price_for_seats( $map, 1 );
		return null === $unit ? 0.0 : $unit;
	}

	/**
	 * @param array<int, float> $map
	 */
	public static function is_complete( array $map, int $capacity ): bool {
		$capacity = max( 0, $capacity );
		if ( $capacity < 1 ) {
			return false;
		}
		for ( $seats = 1; $seats <= $capacity; $seats++ ) {
			if ( ! array_key_exists( $seats, $map ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return array<int, float>
	 */
	public static function get_map( int $class_id, int $capacity = 0 ): array {
		if ( $class_id <= 0 ) {
			return [];
		}
		if ( $capacity < 1 ) {
			$capacity = function_exists( 'get_field' ) ? (int) get_field( 'capacity', $class_id ) : 0;
		}
		$raw = get_post_meta( $class_id, self::META_KEY, true );
		return self::sanitize_map( is_array( $raw ) ? $raw : [], $capacity );
	}

	/**
	 * @param mixed $raw
	 * @return array<int, float>
	 */
	public static function save_map( int $class_id, $raw, int $capacity ): array {
		$clean = self::sanitize_map( $raw, $capacity );
		if ( empty( $clean ) ) {
			delete_post_meta( $class_id, self::META_KEY );
			return [];
		}
		update_post_meta( $class_id, self::META_KEY, $clean );
		return $clean;
	}

	/**
	 * @param array<string, mixed> $field
	 */
	public static function render_field( array $field ): void {
		unset( $field );
		$post_id = function_exists( 'acf_get_form_data' ) ? acf_get_form_data( 'post_id' ) : get_the_ID();
		$post    = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || CPT::CLASS_PT !== $post->post_type ) {
			return;
		}
		self::render_table( $post );
	}

	public static function render_table( \WP_Post $post ): void {
		static $nonce_printed = false;
		if ( ! $nonce_printed ) {
			wp_nonce_field( 'clasbpro_save_party_prices', 'clasbpro_party_prices_nonce' );
			$nonce_printed = true;
		}

		$capacity = function_exists( 'get_field' ) ? max( 1, (int) get_field( 'capacity', $post->ID ) ) : 1;
		$map      = self::get_map( (int) $post->ID, $capacity );
		$step     = Helpers::price_input_step();
		$symbol   = trim( (string) Helpers::currency_config()['symbol'] );
		?>
		<div id="clasbpro-party-prices" class="clasbpro-party-prices" data-step="<?php echo esc_attr( $step ); ?>" data-symbol="<?php echo esc_attr( $symbol ); ?>">
			<p class="clasbpro-party-prices__intro">
				<?php esc_html_e( 'Per-person rate for each party size. The session total updates as you type. 0 = free for that size.', 'class-bookings-with-stripe-pro' ); ?>
			</p>
			<div class="clasbpro-party-prices__head">
				<span><?php esc_html_e( 'People', 'class-bookings-with-stripe-pro' ); ?></span>
				<span><?php echo esc_html( sprintf( __( 'Per person (%s)', 'class-bookings-with-stripe-pro' ), $symbol ) ); ?></span>
				<span><?php esc_html_e( 'Session total', 'class-bookings-with-stripe-pro' ); ?></span>
			</div>
			<div class="clasbpro-party-prices__rows">
				<?php for ( $seats = 1; $seats <= $capacity; $seats++ ) : ?>
					<?php self::render_row( $seats, $map[ $seats ] ?? null, $step, $symbol ); ?>
				<?php endfor; ?>
			</div>
			<template id="clasbpro-party-price-row-template">
				<?php self::render_row( '__SEATS__', null, $step, $symbol ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * @param int|string $seats
	 */
	private static function render_row( $seats, ?float $rate, string $step, string $symbol ): void {
		$value = null !== $rate ? (string) $rate : '';
		$label = is_int( $seats ) ? (string) $seats : (string) $seats;
		?>
		<div class="clasbpro-party-prices__row" data-seats="<?php echo esc_attr( $label ); ?>">
			<span class="clasbpro-party-prices__people"><?php echo esc_html( $label ); ?></span>
			<label class="clasbpro-party-prices__input-wrap">
				<span class="screen-reader-text"><?php esc_html_e( 'Per person', 'class-bookings-with-stripe-pro' ); ?></span>
				<input
					type="number"
					min="0"
					step="<?php echo esc_attr( $step ); ?>"
					name="clasbpro_party_prices[<?php echo esc_attr( $label ); ?>]"
					value="<?php echo esc_attr( $value ); ?>"
					class="clasbpro-party-prices__input"
					inputmode="decimal"
				>
			</label>
			<span class="clasbpro-party-prices__total" data-symbol="<?php echo esc_attr( $symbol ); ?>">—</span>
		</div>
		<?php
	}

	/**
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save_from_request( int $post_id, \WP_Post $post ): void {
		unset( $post );
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['clasbpro_party_prices_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['clasbpro_party_prices_nonce'] ) ), 'clasbpro_save_party_prices' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$schedule = '';
		if ( isset( $_POST['acf']['field_clasbpro_schedule_type'] ) ) {
			$schedule = sanitize_text_field( wp_unslash( (string) $_POST['acf']['field_clasbpro_schedule_type'] ) );
		} elseif ( function_exists( 'get_field' ) ) {
			$schedule = (string) get_field( 'schedule_type', $post_id );
		}
		if ( 'appointments' !== $schedule ) {
			return;
		}

		$capacity = 0;
		if ( isset( $_POST['acf']['field_clasbpro_capacity'] ) ) {
			$capacity = (int) wp_unslash( $_POST['acf']['field_clasbpro_capacity'] );
		} elseif ( function_exists( 'get_field' ) ) {
			$capacity = (int) get_field( 'capacity', $post_id );
		}
		$capacity = max( 1, $capacity );

		$raw = isset( $_POST['clasbpro_party_prices'] ) && is_array( $_POST['clasbpro_party_prices'] )
			? wp_unslash( $_POST['clasbpro_party_prices'] )
			: [];

		self::save_map( $post_id, $raw, $capacity );
	}

	public static function maybe_show_incomplete_notice(): void {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || CPT::CLASS_PT !== $screen->post_type ) {
			return;
		}
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( $post_id <= 0 ) {
			return;
		}
		if ( ! function_exists( 'get_field' ) || 'appointments' !== (string) get_field( 'schedule_type', $post_id ) ) {
			return;
		}
		$capacity = max( 1, (int) get_field( 'capacity', $post_id ) );
		$map      = self::get_map( $post_id, $capacity );
		if ( self::is_complete( $map, $capacity ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Fill in a per-person price for every party size from 1 up to Capacity. Missing sizes cannot be booked.', 'class-bookings-with-stripe-pro' );
		echo '</p></div>';
	}
}
