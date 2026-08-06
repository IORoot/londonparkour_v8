<?php
/**
 * Admin UI for appointment slot rules (ACF Free — no repeaters).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Appointment_Admin {

	public static function init(): void {
		add_action( 'acf/render_field/key=field_clasbpro_appointment_slot_rules', [ self::class, 'render_slot_rules_field' ] );
		add_action( 'save_post_' . CPT::CLASS_PT, [ self::class, 'save_slot_rules' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_action( 'admin_notices', [ self::class, 'maybe_show_no_rules_notice' ] );
	}

	public static function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || CPT::CLASS_PT !== $screen->post_type ) {
			return;
		}

		$css_path = CLASBOWPRO_DIR . 'assets/cbfs-appointment-admin.css';
		$js_path  = CLASBOWPRO_DIR . 'assets/cbfs-appointment-admin.js';
		wp_enqueue_style(
			'clasbpro-appointment-admin',
			CLASBOWPRO_URL . 'assets/cbfs-appointment-admin.css',
			[],
			is_readable( $css_path ) ? (string) filemtime( $css_path ) : CLASBOWPRO_VERSION
		);
		wp_enqueue_script(
			'clasbpro-appointment-admin',
			CLASBOWPRO_URL . 'assets/cbfs-appointment-admin.js',
			[],
			is_readable( $js_path ) ? (string) filemtime( $js_path ) : CLASBOWPRO_VERSION,
			true
		);
	}

	/**
	 * @param array<string, mixed> $field ACF field array.
	 */
	public static function render_slot_rules_field( array $field ): void {
		unset( $field );
		$post_id = function_exists( 'acf_get_form_data' ) ? acf_get_form_data( 'post_id' ) : get_the_ID();
		$post    = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || CPT::CLASS_PT !== $post->post_type ) {
			return;
		}
		self::render_slot_rules_ui( $post );
	}

	public static function render_slot_rules_ui( \WP_Post $post ): void {
		static $nonce_printed = false;
		if ( ! $nonce_printed ) {
			wp_nonce_field( 'clasbpro_save_slot_rules', 'clasbpro_slot_rules_nonce' );
			$nonce_printed = true;
		}

		$rules = Slot_Rules::get_rules( (int) $post->ID );
		$days  = [
			'monday'    => __( 'Monday', 'class-bookings-with-stripe-pro' ),
			'tuesday'   => __( 'Tuesday', 'class-bookings-with-stripe-pro' ),
			'wednesday' => __( 'Wednesday', 'class-bookings-with-stripe-pro' ),
			'thursday'  => __( 'Thursday', 'class-bookings-with-stripe-pro' ),
			'friday'    => __( 'Friday', 'class-bookings-with-stripe-pro' ),
			'saturday'  => __( 'Saturday', 'class-bookings-with-stripe-pro' ),
			'sunday'    => __( 'Sunday', 'class-bookings-with-stripe-pro' ),
		];
		?>
		<div id="clasbpro-slot-rules" class="clasbpro-slot-rules">
			<div class="clasbpro-slot-rules__rows">
				<?php
				if ( empty( $rules ) ) {
					self::render_rule_row( [], $days, 0 );
				} else {
					foreach ( $rules as $index => $rule ) {
						self::render_rule_row( $rule, $days, (int) $index );
					}
				}
				?>
			</div>
			<p>
				<button type="button" class="button button-secondary clasbpro-slot-rules__add">
					<?php esc_html_e( 'Add slot', 'class-bookings-with-stripe-pro' ); ?>
				</button>
			</p>
			<template id="clasbpro-slot-rule-template">
				<?php self::render_rule_row( [], $days, '__INDEX__' ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $rule
	 * @param array<string, string> $days
	 * @param int|string $index
	 */
	private static function render_rule_row( array $rule, array $days, $index ): void {
		$type      = (string) ( $rule['type'] ?? 'recurring' );
		$is_oneoff = 'one_off' === $type;
		$prefix    = 'clasbpro_slot_rules[' . $index . ']';
		$skip_text = '';
		if ( ! empty( $rule['skip_dates'] ) && is_array( $rule['skip_dates'] ) ) {
			$skip_text = implode( "\n", $rule['skip_dates'] );
		}
		$price_val = isset( $rule['price_gbp'] ) && null !== $rule['price_gbp'] ? (string) $rule['price_gbp'] : '';
		?>
		<div class="clasbpro-slot-rule" data-type="<?php echo esc_attr( $type ); ?>">
			<div class="clasbpro-slot-rule__header">
				<strong><?php esc_html_e( 'Slot', 'class-bookings-with-stripe-pro' ); ?></strong>
				<button type="button" class="button-link-delete clasbpro-slot-rule__remove" aria-label="<?php esc_attr_e( 'Remove slot', 'class-bookings-with-stripe-pro' ); ?>">&times;</button>
			</div>
			<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[id]" value="<?php echo esc_attr( (string) ( $rule['id'] ?? '' ) ); ?>">
			<div class="clasbpro-slot-rule__grid">
				<label>
					<span><?php esc_html_e( 'Type', 'class-bookings-with-stripe-pro' ); ?></span>
					<select name="<?php echo esc_attr( $prefix ); ?>[type]" class="clasbpro-slot-rule__type">
						<option value="recurring" <?php selected( $type, 'recurring' ); ?>><?php esc_html_e( 'Recurring', 'class-bookings-with-stripe-pro' ); ?></option>
						<option value="one_off" <?php selected( $type, 'one_off' ); ?>><?php esc_html_e( 'One-off date', 'class-bookings-with-stripe-pro' ); ?></option>
					</select>
				</label>
				<label class="clasbpro-slot-rule__field--recurring">
					<span><?php esc_html_e( 'Day', 'class-bookings-with-stripe-pro' ); ?></span>
					<select name="<?php echo esc_attr( $prefix ); ?>[day_of_week]">
						<?php foreach ( $days as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) ( $rule['day_of_week'] ?? '' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="clasbpro-slot-rule__field--oneoff">
					<span><?php esc_html_e( 'Date', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="date" name="<?php echo esc_attr( $prefix ); ?>[specific_date]" value="<?php echo esc_attr( (string) ( $rule['specific_date'] ?? '' ) ); ?>">
				</label>
				<label class="clasbpro-slot-rule__field--recurring">
					<span><?php esc_html_e( 'From (optional)', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="date" name="<?php echo esc_attr( $prefix ); ?>[recurring_start]" value="<?php echo esc_attr( (string) ( $rule['recurring_start'] ?? '' ) ); ?>">
				</label>
				<label class="clasbpro-slot-rule__field--recurring">
					<span><?php esc_html_e( 'Until (optional)', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="date" name="<?php echo esc_attr( $prefix ); ?>[recurring_end]" value="<?php echo esc_attr( (string) ( $rule['recurring_end'] ?? '' ) ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'Start time', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="time" name="<?php echo esc_attr( $prefix ); ?>[start_time]" value="<?php echo esc_attr( (string) ( $rule['start_time'] ?? '' ) ); ?>" required>
				</label>
				<label>
					<span><?php esc_html_e( 'Duration (min)', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="number" min="1" name="<?php echo esc_attr( $prefix ); ?>[duration_minutes]" value="<?php echo esc_attr( (string) ( $rule['duration_minutes'] ?? 60 ) ); ?>" required>
				</label>
				<label>
					<span><?php esc_html_e( 'Location', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $prefix ); ?>[location]" value="<?php echo esc_attr( (string) ( $rule['location'] ?? '' ) ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'Label (optional)', 'class-bookings-with-stripe-pro' ); ?></span>
					<input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( (string) ( $rule['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Coach Sarah', 'class-bookings-with-stripe-pro' ); ?>">
				</label>
				<label>
					<span><?php echo esc_html( sprintf( __( 'Price override (%s)', 'class-bookings-with-stripe-pro' ), trim( Helpers::currency_config()['symbol'] ) ) ); ?></span>
					<input type="number" min="0" step="<?php echo esc_attr( Helpers::price_input_step() ); ?>" name="<?php echo esc_attr( $prefix ); ?>[price_gbp]" value="<?php echo esc_attr( $price_val ); ?>" placeholder="<?php esc_attr_e( 'Class default', 'class-bookings-with-stripe-pro' ); ?>">
				</label>
				<label class="clasbpro-slot-rule__full">
					<span><?php esc_html_e( 'Skip dates (one per line)', 'class-bookings-with-stripe-pro' ); ?></span>
					<textarea name="<?php echo esc_attr( $prefix ); ?>[skip_dates]" rows="2" placeholder="YYYY-MM-DD"><?php echo esc_textarea( $skip_text ); ?></textarea>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save_slot_rules( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['clasbpro_slot_rules_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['clasbpro_slot_rules_nonce'] ) ), 'clasbpro_save_slot_rules' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['clasbpro_slot_rules'] ) && is_array( $_POST['clasbpro_slot_rules'] )
			? wp_unslash( $_POST['clasbpro_slot_rules'] )
			: [];

		$rules = [];
		foreach ( $raw as $row ) {
			if ( is_array( $row ) ) {
				$rules[] = $row;
			}
		}

		Slot_Rules::save_rules( $post_id, $rules );
	}

	public static function maybe_show_no_rules_notice(): void {
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
		if ( ! empty( Slot_Rules::get_rules( $post_id ) ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'This appointment class has no availability slots yet. Add at least one slot in Class details before customers can book.', 'class-bookings-with-stripe-pro' );
		echo '</p></div>';
	}
}
