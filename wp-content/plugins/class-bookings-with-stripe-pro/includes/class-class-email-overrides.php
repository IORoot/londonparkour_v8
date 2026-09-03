<?php
/**
 * Per-class email template overrides (admin, customer, reminder, post-class).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Class_Email_Overrides {

	public const MODE_GLOBAL = 'global';
	public const MODE_CUSTOM = 'custom';

	/** @var list<string> */
	public const INSTANT_TYPES = [ 'admin', 'customer' ];

	/** @var list<string> */
	public const SCHEDULED_TYPES = [ 'reminder', 'post_class' ];

	/** @var list<string> */
	public const ALL_TYPES = [ 'admin', 'customer', 'reminder', 'post_class' ];

	/** @var list<string> */
	public const HTML_BODY_FIELD_KEYS = [
		'field_clasbpro_class_email_admin_body_html',
		'field_clasbpro_class_email_customer_body_html',
		'field_clasbpro_class_email_reminder_body_html',
		'field_clasbpro_class_email_post_class_body_html',
	];

	private const INIT_META_PREFIX = '_clasbpro_class_email_init_';

	/** @var array<int, array<string, string>> */
	private static array $mode_before_save = [];

	public static function init(): void {
		add_action( 'acf/save_post', [ self::class, 'maybe_prefill_after_save' ], 30 );
		add_action( 'acf/save_post', [ self::class, 'on_class_save' ], 40 );
		add_action( 'admin_post_clasbpro_reset_class_email', [ self::class, 'handle_reset_to_global' ] );
		add_action( 'admin_post_clasbpro_test_class_email', [ self::class, 'handle_test_class_email' ] );

		add_filter( 'acf/pre_save_post', [ self::class, 'stash_modes_before_save' ], 1 );
		add_filter( 'acf/pre_save_post', [ self::class, 'stash_raw_html_values' ], 2 );

		foreach ( self::HTML_BODY_FIELD_KEYS as $field_key ) {
			add_filter( "acf/update_value/key={$field_key}", [ self::class, 'restore_raw_html_value' ], 5, 3 );
		}

		$tab_intro_keys = [
			'field_clasbpro_class_email_tab_intro_admin'      => 'admin',
			'field_clasbpro_class_email_tab_intro_customer'   => 'customer',
			'field_clasbpro_class_email_tab_intro_reminder'   => 'reminder',
			'field_clasbpro_class_email_tab_intro_post_class' => 'post_class',
		];
		foreach ( $tab_intro_keys as $field_key => $tab ) {
			add_filter( "acf/load_field/key={$field_key}", static function ( array $field ) use ( $tab ): array {
				$field['message']   = Emails::render_email_tab_intro( $tab );
				$field['new_lines'] = '';
				$field['esc_html']  = false;
				return $field;
			} );
		}

		$test_keys = [
			'field_clasbpro_class_email_test_send_admin'      => 'admin',
			'field_clasbpro_class_email_test_send_customer'   => 'customer',
			'field_clasbpro_class_email_test_send_reminder'   => 'reminder',
			'field_clasbpro_class_email_test_send_post_class' => 'post_class',
		];
		foreach ( $test_keys as $field_key => $tab ) {
			add_filter( "acf/load_field/key={$field_key}", static function ( array $field ) use ( $tab ): array {
				$field['message']   = self::render_custom_test_send_panel( $tab );
				$field['new_lines'] = '';
				$field['esc_html']  = false;
				return $field;
			} );
		}

		$reset_keys = [
			'field_clasbpro_class_email_reset_admin'      => 'admin',
			'field_clasbpro_class_email_reset_customer'   => 'customer',
			'field_clasbpro_class_email_reset_reminder'   => 'reminder',
			'field_clasbpro_class_email_reset_post_class' => 'post_class',
		];
		foreach ( $reset_keys as $field_key => $tab ) {
			add_filter( "acf/load_field/key={$field_key}", static function ( array $field ) use ( $tab ): array {
				$field['message']   = self::render_reset_panel( $tab );
				$field['new_lines'] = '';
				$field['esc_html']  = false;
				return $field;
			} );
		}
	}

	public static function mode_field_name( string $type ): string {
		return 'class_email_' . $type . '_mode';
	}

	public static function uses_custom( int $class_id, string $type ): bool {
		if ( ! $class_id || ! function_exists( 'get_field' ) ) {
			return false;
		}

		$mode = sanitize_key( (string) get_field( self::mode_field_name( $type ), $class_id ) );
		return self::MODE_CUSTOM === $mode;
	}

	public static function is_external_link_class( int $class_id ): bool {
		if ( ! $class_id || ! function_exists( 'get_field' ) ) {
			return false;
		}

		return 'external_link' === (string) get_field( 'schedule_type', $class_id );
	}

	public static function instant_enabled( int $class_id, string $type ): bool {
		if ( ! in_array( $type, self::INSTANT_TYPES, true ) ) {
			return true;
		}

		if ( self::is_external_link_class( $class_id ) ) {
			return false;
		}

		if ( ! self::uses_custom( $class_id, $type ) ) {
			return true;
		}

		return (bool) get_field( 'class_email_' . $type . '_enabled', $class_id );
	}

	public static function scheduled_send_enabled( int $class_id, string $type ): bool {
		if ( ! in_array( $type, self::SCHEDULED_TYPES, true ) ) {
			return false;
		}

		if ( self::is_external_link_class( $class_id ) ) {
			return false;
		}

		if ( ! Scheduled_Emails::category_enabled( $type ) ) {
			return false;
		}

		if ( self::uses_custom( $class_id, $type ) ) {
			return (bool) get_field( 'class_email_' . $type . '_enabled', $class_id );
		}

		return Scheduled_Emails::class_category_enabled( $class_id, $type );
	}

	public static function resolve_subject( int $class_id, string $type ): string {
		if ( self::uses_custom( $class_id, $type ) ) {
			$subject = trim( (string) get_field( 'class_email_' . $type . '_subject', $class_id ) );
			if ( '' !== $subject ) {
				return $subject;
			}
		}

		$option_key = self::global_subject_option( $type );
		$subject    = trim( (string) Helpers::get_option( $option_key, '' ) );
		if ( '' !== $subject ) {
			return $subject;
		}

		return self::default_subject( $type );
	}

	/**
	 * @return array{body: string, html_mode: bool, editor_mode: string}
	 */
	public static function resolve_body( int $class_id, string $type ): array {
		if ( self::uses_custom( $class_id, $type ) ) {
			$settings = self::get_class_body_settings( $class_id, $type );
			$body     = $settings['body'];
			if ( '' !== $body || $settings['html_mode'] ) {
				return [
					'body'        => $body,
					'html_mode'   => $settings['html_mode'],
					'editor_mode' => $settings['editor_mode'],
				];
			}
		}

		return Emails::resolve_body_template( $type );
	}

	public static function resolve_admin_recipient( int $class_id ): string {
		if ( self::uses_custom( $class_id, 'admin' ) ) {
			$custom = trim( (string) get_field( 'class_email_admin_recipient', $class_id ) );
			if ( '' !== $custom && is_email( $custom ) ) {
				return $custom;
			}
		}

		$admin_email = (string) Helpers::get_option( 'admin_email', '' );
		if ( '' !== $admin_email && is_email( $admin_email ) ) {
			return $admin_email;
		}

		$site_admin = (string) get_option( 'admin_email' );
		return is_email( $site_admin ) ? $site_admin : '';
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function build_class_rule( int $class_id, string $type ): ?array {
		if ( ! in_array( $type, self::SCHEDULED_TYPES, true ) || ! self::uses_custom( $class_id, $type ) ) {
			return null;
		}

		if ( ! self::scheduled_send_enabled( $class_id, $type ) ) {
			return null;
		}

		$amount = max( 0, (int) get_field( 'class_email_' . $type . '_offset_amount', $class_id ) );
		$unit   = sanitize_key( (string) get_field( 'class_email_' . $type . '_offset_unit', $class_id ) );
		if ( ! in_array( $unit, [ 'minutes', 'hours', 'days', 'weeks', 'months' ], true ) ) {
			$unit = 'hours';
		}

		$subject = trim( self::resolve_subject( $class_id, $type ) );
		$body    = self::resolve_body( $class_id, $type );
		$body_tx = Scheduled_Emails::strip_feedback_merge_tags( trim( (string) $body['body'] ) );

		if ( $amount <= 0 || '' === $subject || '' === $body_tx ) {
			return null;
		}

		$uuid = trim( (string) get_field( 'class_email_' . $type . '_rule_uuid', $class_id ) );
		if ( '' === $uuid ) {
			$uuid = wp_generate_uuid4();
			if ( function_exists( 'update_field' ) ) {
				update_field( 'class_email_' . $type . '_rule_uuid', $uuid, $class_id );
			}
		}

		$label = Scheduled_Emails::TYPE_REMINDER === $type
			? __( 'Reminder', 'class-bookings-with-stripe-pro' )
			: __( 'Post-class email', 'class-bookings-with-stripe-pro' );

		return [
			'uuid'           => $uuid,
			'label'          => $label,
			'type'           => $type,
			'offset_amount'  => $amount,
			'offset_unit'    => $unit,
			'subject'        => $subject,
			'body'           => $body_tx,
			'body_html_mode' => Email_Body_Editor::queue_flag( (string) ( $body['editor_mode'] ?? Email_Body_Editor::MODE_VISUAL ) ),
			'admin_copy'     => ! empty( get_field( 'class_email_' . $type . '_admin_copy', $class_id ) ),
			'max_sends'      => 1,
		];
	}

	/**
	 * @return array<string, string>
	 */
	public static function sample_merge_tags_for_class( int $class_id ): array {
		$class_data = Helpers::get_class_data( $class_id );
		$tags       = Scheduled_Emails::sample_merge_tags();

		if ( ! $class_data ) {
			return $tags;
		}

		$tags['{class_name}']  = (string) ( $class_data['name'] ?? $tags['{class_name}'] );
		$tags['{location}']    = (string) ( $class_data['location'] ?? $tags['{location}'] );
		$tags['{duration}']    = (string) ( $class_data['duration_minutes'] ?? $tags['{duration}'] );
		$tags['{price}']       = Helpers::format_price( (float) ( $class_data['price_gbp'] ?? 0 ) );
		$tags['{description}'] = (string) ( $class_data['description'] ?? $tags['{description}'] );

		$tags['{class_id}'] = (string) $class_id;

		if ( ! empty( $class_data['start_time'] ) ) {
			$tags['{class_time}'] = Helpers::format_time( (string) $class_data['start_time'] );
		}

		return Merge_Tags::filter_values(
			$tags,
			[
				'kind'        => 'booking',
				'booking_id'  => 0,
				'class_id'    => $class_id,
				'purchase_id' => 0,
				'sample'      => true,
			]
		);
	}

	public static function prefill_from_global( int $class_id, string $type ): void {
		if ( ! function_exists( 'update_field' ) || ! in_array( $type, self::ALL_TYPES, true ) ) {
			return;
		}

		$subject_key = self::global_subject_option( $type );
		$subject     = trim( (string) Helpers::get_option( $subject_key, '' ) );
		if ( '' === $subject ) {
			$subject = self::default_subject( $type );
		}
		update_field( 'class_email_' . $type . '_subject', $subject, $class_id );

		$prefix      = Email_Body_Editor::template_option_prefix( $type );
		$editor_mode = Email_Body_Editor::sanitize_mode( (string) Helpers::get_option( $prefix . '_body_editor_mode', Email_Body_Editor::MODE_VISUAL ) );

		$visual_body = (string) Helpers::get_option( $prefix . '_body', '' );
		$html_body   = (string) Helpers::get_option( $prefix . '_body_html', '' );
		if ( '' === trim( $visual_body ) && '' === trim( $html_body ) ) {
			$default_body = Emails::default_body_template( $type );
			if ( Email_Body_Editor::uses_html_field( $editor_mode ) ) {
				$html_body = $default_body;
			} else {
				$visual_body = $default_body;
			}
		}

		update_field( 'class_email_' . $type . '_body_editor_mode', $editor_mode, $class_id );
		update_field( 'class_email_' . $type . '_body', $visual_body, $class_id );
		update_field( 'class_email_' . $type . '_body_html', $html_body, $class_id );

		if ( 'admin' === $type ) {
			update_field( 'class_email_admin_enabled', 1, $class_id );
			update_field( 'class_email_admin_recipient', '', $class_id );
		} elseif ( 'customer' === $type ) {
			update_field( 'class_email_customer_enabled', 1, $class_id );
		} elseif ( in_array( $type, self::SCHEDULED_TYPES, true ) ) {
			$opt_prefix = Scheduled_Emails::TYPE_REMINDER === $type ? 'reminder' : 'post_class';
			update_field( 'class_email_' . $type . '_enabled', 1, $class_id );
			update_field(
				'class_email_' . $type . '_offset_amount',
				max( 1, (int) Helpers::get_option( $opt_prefix . '_offset_amount', Scheduled_Emails::TYPE_REMINDER === $type ? 24 : 3 ) ),
				$class_id
			);
			update_field( 'class_email_' . $type . '_offset_unit', (string) Helpers::get_option( $opt_prefix . '_offset_unit', 'hours' ), $class_id );
			update_field( 'class_email_' . $type . '_admin_copy', (int) Helpers::get_option( $opt_prefix . '_admin_copy', 0 ), $class_id );
			$uuid = trim( (string) get_field( 'class_email_' . $type . '_rule_uuid', $class_id ) );
			if ( '' === $uuid ) {
				update_field( 'class_email_' . $type . '_rule_uuid', wp_generate_uuid4(), $class_id );
			}
		}

		update_post_meta( $class_id, self::init_meta_key( $type ), '1' );
	}

	/**
	 * @param int|string $post_id
	 */
	public static function on_class_save( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ! $post_id || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return;
		}

		if ( self::scheduled_settings_changed( $post_id ) ) {
			Scheduled_Emails::requeue_bookings_for_class( $post_id );
		}

		unset( self::$mode_before_save[ $post_id ] );
	}

	/**
	 * @param int|string $post_id
	 */
	public static function maybe_prefill_after_save( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ! $post_id || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return;
		}

		foreach ( self::ALL_TYPES as $type ) {
			if ( ! self::uses_custom( $post_id, $type ) ) {
				continue;
			}
			if ( get_post_meta( $post_id, self::init_meta_key( $type ), true ) ) {
				continue;
			}
			if ( self::custom_fields_have_saved_content( $post_id, $type ) ) {
				self::mark_custom_initialized( $post_id, $type );
				continue;
			}
			self::prefill_from_global( $post_id, $type );
		}
	}

	private static function custom_fields_have_saved_content( int $class_id, string $type ): bool {
		if ( ! function_exists( 'get_field' ) ) {
			return false;
		}

		if ( '' !== trim( (string) get_field( 'class_email_' . $type . '_subject', $class_id ) ) ) {
			return true;
		}

		if ( '' !== trim( (string) get_field( 'class_email_' . $type . '_body', $class_id ) ) ) {
			return true;
		}

		if ( '' !== trim( (string) get_field( 'class_email_' . $type . '_body_html', $class_id ) ) ) {
			return true;
		}

		return false;
	}

	private static function mark_custom_initialized( int $class_id, string $type ): void {
		if ( in_array( $type, self::SCHEDULED_TYPES, true ) && function_exists( 'update_field' ) ) {
			$uuid = trim( (string) get_field( 'class_email_' . $type . '_rule_uuid', $class_id ) );
			if ( '' === $uuid ) {
				update_field( 'class_email_' . $type . '_rule_uuid', wp_generate_uuid4(), $class_id );
			}
		}

		update_post_meta( $class_id, self::init_meta_key( $type ), '1' );
	}

	public static function handle_reset_to_global(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}

		$class_id = absint( $_GET['class_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type     = sanitize_key( (string) ( $_GET['email_type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $class_id || ! in_array( $type, self::ALL_TYPES, true ) ) {
			wp_die( esc_html__( 'Invalid request.', 'class-bookings-with-stripe-pro' ) );
		}

		check_admin_referer( 'clasbpro_reset_class_email_' . $class_id . '_' . $type );

		if ( ! current_user_can( 'edit_post', $class_id ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}

		self::prefill_from_global( $class_id, $type );

		if ( in_array( $type, self::SCHEDULED_TYPES, true ) ) {
			Scheduled_Emails::requeue_bookings_for_class( $class_id );
		}

		$redirect = add_query_arg(
			[
				'post'                       => $class_id,
				'action'                     => 'edit',
				'clasbpro_class_email_reset' => $type,
			],
			admin_url( 'post.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function handle_test_class_email(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}

		$class_id = absint( $_GET['class_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type     = sanitize_key( (string) ( $_GET['email_type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $class_id || ! in_array( $type, self::ALL_TYPES, true ) ) {
			wp_die( esc_html__( 'Invalid request.', 'class-bookings-with-stripe-pro' ) );
		}

		check_admin_referer( 'clasbpro_test_class_email_' . $class_id . '_' . $type );

		if ( ! self::uses_custom( $class_id, $type ) ) {
			wp_die( esc_html__( 'Enable custom email settings for this class first.', 'class-bookings-with-stripe-pro' ) );
		}

		$sent = self::dispatch_test_email( $class_id, $type );

		$redirect = add_query_arg(
			array_merge(
				[
					'post'   => $class_id,
					'action' => 'edit',
				],
				$sent
					? [ 'clasbpro_class_email_test_sent' => $type ]
					: [ 'clasbpro_class_email_test_failed' => '1' ]
			),
			admin_url( 'post.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function dispatch_test_email( int $class_id, string $type ): bool {
		$test_to = Emails::get_test_recipient();
		if ( ! $test_to || ! is_email( $test_to ) ) {
			return false;
		}

		$tags        = self::sample_merge_tags_for_class( $class_id );
		$subject_tpl = self::resolve_subject( $class_id, $type );
		$body        = self::resolve_body( $class_id, $type );

		if ( in_array( $type, self::INSTANT_TYPES, true ) ) {
			$intended = Emails::get_intended_test_recipient( $type );
			if ( 'admin' === $type ) {
				$to = self::resolve_admin_recipient( $class_id ) ?: $test_to;
			} else {
				$to = $intended['to'] ?: $test_to;
			}

			return Emails::send_raw_template(
				$to,
				$subject_tpl,
				$body['body'],
				$tags,
				$intended['role'],
				true,
				$body['editor_mode'] ?? false
			);
		}

		$rule = self::build_class_rule( $class_id, $type );
		if ( ! $rule ) {
			return false;
		}

		$role_label = sprintf(
			/* translators: %s: scheduled email rule label */
			__( 'Customer (%s)', 'class-bookings-with-stripe-pro' ),
			(string) ( $rule['label'] ?? $type )
		);

		return Emails::send_raw_template(
			$tags['{customer_email}'] ?? $test_to,
			(string) $rule['subject'],
			(string) $rule['body'],
			$tags,
			$role_label,
			true,
			(int) ( $rule['body_html_mode'] ?? 0 )
		);
	}

	public static function render_custom_test_send_panel( string $type ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$test_url = wp_nonce_url(
			add_query_arg(
				[
					'action'     => 'clasbpro_test_class_email',
					'class_id'   => (int) $post_id,
					'email_type' => $type,
				],
				admin_url( 'admin-post.php' )
			),
			'clasbpro_test_class_email_' . (int) $post_id . '_' . $type
		);
		$test_to  = Emails::get_test_recipient();

		ob_start();
		?>
		<div class="clasbpro-email-tab-test">
			<p><?php esc_html_e( 'Send a sample using this class’s custom template and merge tags.', 'class-bookings-with-stripe-pro' ); ?></p>
			<?php if ( $test_to ) : ?>
				<p>
					<strong><?php esc_html_e( 'Test recipient:', 'class-bookings-with-stripe-pro' ); ?></strong>
					<code><?php echo esc_html( $test_to ); ?></code>
				</p>
				<p class="clasbpro-email-tab-test__form">
					<a class="button button-secondary" href="<?php echo esc_url( $test_url ); ?>">
						<?php echo esc_html( self::test_button_label( $type ) ); ?>
					</a>
				</p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Set a test recipient under Settings → Emails → Extras.', 'class-bookings-with-stripe-pro' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_reset_panel( string $type ): string {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$reset_url = wp_nonce_url(
			add_query_arg(
				[
					'action'     => 'clasbpro_reset_class_email',
					'class_id'   => (int) $post_id,
					'email_type' => $type,
				],
				admin_url( 'admin-post.php' )
			),
			'clasbpro_reset_class_email_' . (int) $post_id . '_' . $type
		);

		ob_start();
		?>
		<div class="clasbpro-class-email-reset-panel">
			<p class="description"><?php esc_html_e( 'Replace this tab’s custom content with the current global defaults from Settings → Emails.', 'class-bookings-with-stripe-pro' ); ?></p>
			<p>
				<a
					class="button button-secondary clasbpro-class-email-reset-btn"
					href="<?php echo esc_url( $reset_url ); ?>"
					data-clasbpro-confirm="<?php esc_attr_e( 'Replace your custom content for this email with the current global defaults?', 'class-bookings-with-stripe-pro' ); ?>"
				><?php esc_html_e( 'Reset to global defaults', 'class-bookings-with-stripe-pro' ); ?></a>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array{body: string, html_mode: bool, editor_mode: string}
	 */
	private static function get_class_body_settings( int $class_id, string $type ): array {
		$editor_mode = Email_Body_Editor::sanitize_mode( (string) get_field( 'class_email_' . $type . '_body_editor_mode', $class_id ) );
		$html_mode   = Email_Body_Editor::uses_html_field( $editor_mode );
		$body        = $html_mode
			? (string) get_field( 'class_email_' . $type . '_body_html', $class_id )
			: (string) get_field( 'class_email_' . $type . '_body', $class_id );

		return [
			'body'        => $body,
			'html_mode'   => $html_mode,
			'editor_mode' => $editor_mode,
		];
	}

	private static function global_subject_option( string $type ): string {
		$map = [
			'admin'      => 'admin_email_subject',
			'customer'   => 'customer_email_subject',
			'reminder'   => 'reminder_email_subject',
			'post_class' => 'post_class_email_subject',
		];

		return $map[ $type ] ?? '';
	}

	private static function default_subject( string $type ): string {
		switch ( $type ) {
			case 'admin':
				return Emails::default_admin_subject();
			case 'customer':
				return Emails::default_customer_subject();
			case 'reminder':
				return __( 'Reminder: {class_name} on {class_date}', 'class-bookings-with-stripe-pro' );
			case 'post_class':
				return __( 'How was {class_name}?', 'class-bookings-with-stripe-pro' );
		}

		return '';
	}

	private static function test_button_label( string $type ): string {
		$labels = [
			'admin'      => __( 'Send test admin email', 'class-bookings-with-stripe-pro' ),
			'customer'   => __( 'Send test customer email', 'class-bookings-with-stripe-pro' ),
			'reminder'   => __( 'Send test reminder', 'class-bookings-with-stripe-pro' ),
			'post_class' => __( 'Send test post-class email', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $type ] ?? __( 'Send test email', 'class-bookings-with-stripe-pro' );
	}

	private static function init_meta_key( string $type ): string {
		return self::INIT_META_PREFIX . $type;
	}

	private static function scheduled_settings_changed( int $class_id ): bool {
		$before = self::$mode_before_save[ $class_id ] ?? [];

		foreach ( self::SCHEDULED_TYPES as $type ) {
			$prev_mode = (string) ( $before[ $type ] ?? self::MODE_GLOBAL );
			$new_mode  = self::uses_custom( $class_id, $type ) ? self::MODE_CUSTOM : self::MODE_GLOBAL;
			if ( $prev_mode !== $new_mode ) {
				return true;
			}
		}

		foreach ( self::SCHEDULED_TYPES as $type ) {
			if ( ! self::uses_custom( $class_id, $type ) ) {
				$field = Scheduled_Emails::TYPE_REMINDER === $type ? 'send_reminder_emails' : 'send_post_class_emails';
				$prev  = array_key_exists( $field, $before ) ? (bool) $before[ $field ] : null;
				$now   = Scheduled_Emails::class_category_enabled( $class_id, $type );
				if ( null !== $prev && $prev !== $now ) {
					return true;
				}
				continue;
			}

			foreach ( [ 'enabled', 'offset_amount', 'offset_unit', 'admin_copy', 'subject' ] as $suffix ) {
				$name = 'class_email_' . $type . '_' . $suffix;
				if ( array_key_exists( $name, $before ) && (string) $before[ $name ] !== (string) get_field( $name, $class_id ) ) {
					return true;
				}
			}

			$body = self::get_class_body_settings( $class_id, $type );
			$key  = Email_Body_Editor::uses_html_field( (string) ( $body['editor_mode'] ?? '' ) )
				? 'class_email_' . $type . '_body_html'
				: 'class_email_' . $type . '_body';
			if ( array_key_exists( $key, $before ) && (string) $before[ $key ] !== (string) $body['body'] ) {
				return true;
			}
		}

		return false;
	}

	/** @var array<string, string> */
	private static array $raw_html_stash = [];

	/**
	 * @param int|string $post_id
	 * @return int|string
	 */
	public static function stash_modes_before_save( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return $post_id;
		}

		$snapshot = [];
		foreach ( self::ALL_TYPES as $type ) {
			$snapshot[ $type ] = self::uses_custom( $post_id, $type ) ? self::MODE_CUSTOM : self::MODE_GLOBAL;
		}
		$snapshot['send_reminder_emails']    = Scheduled_Emails::class_category_enabled( $post_id, Scheduled_Emails::TYPE_REMINDER );
		$snapshot['send_post_class_emails']  = Scheduled_Emails::class_category_enabled( $post_id, Scheduled_Emails::TYPE_POST_CLASS );

		foreach ( self::SCHEDULED_TYPES as $type ) {
			if ( ! self::uses_custom( $post_id, $type ) ) {
				continue;
			}
			foreach ( [ 'enabled', 'offset_amount', 'offset_unit', 'admin_copy', 'subject', 'body', 'body_html' ] as $suffix ) {
				$name             = 'class_email_' . $type . '_' . $suffix;
				$snapshot[ $name ] = (string) get_field( $name, $post_id );
			}
		}

		self::$mode_before_save[ $post_id ] = $snapshot;
		return $post_id;
	}

	/**
	 * @param int|string $post_id
	 * @return int|string
	 */
	public static function stash_raw_html_values( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return $post_id;
		}

		self::$raw_html_stash = [];
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $post_id;
		}

		foreach ( self::HTML_BODY_FIELD_KEYS as $field_key ) {
			if ( isset( $_POST['acf'][ $field_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				self::$raw_html_stash[ $field_key ] = wp_unslash( (string) $_POST['acf'][ $field_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}

		return $post_id;
	}

	/**
	 * @param mixed               $value
	 * @param int|string          $post_id
	 * @param array<string,mixed> $field
	 * @return mixed
	 */
	public static function restore_raw_html_value( $value, $post_id, array $field ) {
		$post_id = (int) $post_id;
		if ( ! $post_id || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return $value;
		}

		$key = (string) ( $field['key'] ?? '' );
		if ( isset( self::$raw_html_stash[ $key ] ) ) {
			return self::$raw_html_stash[ $key ];
		}

		return $value;
	}
}
