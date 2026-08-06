<?php
/**
 * Email rendering: merge-tag substitution, wp_mail dispatch.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Emails {

	private static ?string $last_mail_error = null;

	/** @var array{delivered_to: string, intended_to: string, test_mode: bool} */
	private static array $last_send_meta = [
		'delivered_to' => '',
		'intended_to'  => '',
		'test_mode'    => false,
	];

	private const TAB_TEST_FIELD_KEYS = [
		'field_clasbpro_email_test_send_admin'            => 'admin',
		'field_clasbpro_email_test_send_customer'         => 'customer',
		'field_clasbpro_email_test_send_admin_coupon'     => 'admin_coupon',
		'field_clasbpro_email_test_send_customer_coupon'  => 'customer_coupon',
		'field_clasbpro_email_test_send_reminder'         => 'reminder',
		'field_clasbpro_email_test_send_post_class'       => 'post_class',
	];

	private const TAB_INTRO_FIELD_KEYS = [
		'field_clasbpro_email_tab_intro_admin'            => 'admin',
		'field_clasbpro_email_tab_intro_customer'         => 'customer',
		'field_clasbpro_email_tab_intro_admin_coupon'     => 'admin_coupon',
		'field_clasbpro_email_tab_intro_customer_coupon'  => 'customer_coupon',
		'field_clasbpro_email_tab_intro_reminder'         => 'reminder',
		'field_clasbpro_email_tab_intro_post_class'       => 'post_class',
	];

	public static function init(): void {
		add_action( 'admin_post_clasbpro_test_booking_email', [ self::class, 'handle_test_booking_email' ] );
		add_action( 'wp_mail_failed', [ self::class, 'capture_mail_failed' ] );
		foreach ( self::TAB_TEST_FIELD_KEYS as $field_key => $tab ) {
			add_filter( "acf/load_field/key={$field_key}", [ self::class, 'load_tab_test_send_field' ] );
		}
		foreach ( self::TAB_INTRO_FIELD_KEYS as $field_key => $tab ) {
			add_filter( "acf/load_field/key={$field_key}", [ self::class, 'load_tab_intro_field' ] );
		}
		add_action( 'admin_notices', [ self::class, 'render_test_mode_admin_notice' ] );
	}

	public static function is_local_test_mode(): bool {
		return ! empty( Helpers::get_option( 'email_local_test_mode', 0 ) );
	}

	public static function capture_mail_failed( \WP_Error $error ): void {
		$message = trim( (string) $error->get_error_message() );
		if ( '' !== $message ) {
			self::$last_mail_error = $message;
		}
	}

	public static function consume_last_mail_error(): string {
		$error                 = (string) ( self::$last_mail_error ?? '' );
		self::$last_mail_error = null;
		return $error;
	}

	/**
	 * @return array{delivered_to: string, intended_to: string, test_mode: bool}
	 */
	public static function consume_last_send_meta(): array {
		$meta                 = self::$last_send_meta;
		self::$last_send_meta = [
			'delivered_to' => '',
			'intended_to'  => '',
			'test_mode'    => false,
		];
		return $meta;
	}

	public static function get_test_recipient(): string {
		$email = trim( (string) Helpers::get_option( 'email_test_recipient', '' ) );
		if ( '' !== $email && is_email( $email ) ) {
			return $email;
		}

		$admin = trim( (string) Helpers::get_option( 'admin_email', '' ) );
		if ( '' !== $admin && is_email( $admin ) ) {
			return $admin;
		}

		$user = wp_get_current_user();
		if ( $user && $user->user_email && is_email( $user->user_email ) ) {
			return (string) $user->user_email;
		}

		$site_admin = (string) get_option( 'admin_email' );
		return is_email( $site_admin ) ? $site_admin : '';
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function load_tab_test_send_field( array $field ): array {
		$tab = self::TAB_TEST_FIELD_KEYS[ (string) ( $field['key'] ?? '' ) ] ?? '';
		$field['message']   = '' !== $tab ? self::render_tab_test_send_panel( $tab ) : '';
		$field['new_lines'] = '';
		$field['esc_html']  = false;
		return $field;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function load_tab_intro_field( array $field ): array {
		$tab = self::TAB_INTRO_FIELD_KEYS[ (string) ( $field['key'] ?? '' ) ] ?? '';
		$field['message']   = '' !== $tab ? self::render_email_tab_intro( $tab ) : '';
		$field['new_lines'] = '';
		$field['esc_html']  = false;
		return $field;
	}

	public static function render_email_tab_intro( string $tab ): string {
		return '<div class="clasbpro-email-tab-intro">' . self::render_merge_tags_accordion( $tab ) . '</div>';
	}

	/**
	 * One-line help shown below the email sub-tab bar for the active section.
	 *
	 * @return array<string, string>
	 */
	public static function get_email_section_descriptions(): array {
		return [
			'admin'            => __( 'Sent to you immediately when a customer completes a paid booking, with their contact details and booking summary.', 'class-bookings-with-stripe-pro' ),
			'customer'         => __( 'Sent to the customer right after payment to confirm their class, date, time, and booking reference.', 'class-bookings-with-stripe-pro' ),
			'admin-coupon'     => __( 'Sent to you immediately when a customer buys a coupon, with their contact details and purchase summary.', 'class-bookings-with-stripe-pro' ),
			'customer-coupon'  => __( 'Sent to the customer right after a coupon purchase with their code, uses, and restore link.', 'class-bookings-with-stripe-pro' ),
			'reminders'        => __( 'Sent automatically before class starts to remind booked customers; missed sends are skipped if the booking was made too late.', 'class-bookings-with-stripe-pro' ),
			'post-class'       => __( 'Sent automatically after class ends as a follow-up to customers who attended; timing is based on each class duration.', 'class-bookings-with-stripe-pro' ),
			'extras'           => __( 'Redirect all plugin emails to a test address while developing, and queue scheduled emails for existing upcoming bookings.', 'class-bookings-with-stripe-pro' ),
		];
	}

	public static function get_email_section_description( string $section ): string {
		$descriptions = self::get_email_section_descriptions();
		return $descriptions[ $section ] ?? '';
	}

	public static function render_tab_test_send_panel( string $tab ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$test_url      = wp_nonce_url(
			add_query_arg( 'email_type', $tab, admin_url( 'admin-post.php?action=clasbpro_test_booking_email' ) ),
			'clasbpro_test_booking_email'
		);
		$test_to       = self::get_test_recipient();
		$intended      = self::get_intended_test_recipient( $tab );
		$needs_rule    = in_array( $tab, [ 'reminder', 'post_class' ], true );
		$scheduled_rule = $needs_rule ? Scheduled_Emails::get_global_rule( $tab ) : null;

		$can_send = '' !== $test_to && ! ( $needs_rule && ! $scheduled_rule );

		$button_labels = [
			'admin'            => __( 'Send test admin email', 'class-bookings-with-stripe-pro' ),
			'customer'         => __( 'Send test customer email', 'class-bookings-with-stripe-pro' ),
			'admin_coupon'     => __( 'Send test admin coupon email', 'class-bookings-with-stripe-pro' ),
			'customer_coupon'  => __( 'Send test customer coupon email', 'class-bookings-with-stripe-pro' ),
			'reminder'         => __( 'Send test reminder', 'class-bookings-with-stripe-pro' ),
			'post_class'       => __( 'Send test post-class email', 'class-bookings-with-stripe-pro' ),
		];

		ob_start();
		?>
		<div class="clasbpro-email-tab-test">
			<p><?php esc_html_e( 'Send a sample message with merge tags filled in. Delivery goes to your test recipient; the email banner shows the intended production recipient.', 'class-bookings-with-stripe-pro' ); ?></p>
			<?php if ( $test_to ) : ?>
				<p>
					<strong><?php esc_html_e( 'Test recipient:', 'class-bookings-with-stripe-pro' ); ?></strong>
					<code><?php echo esc_html( $test_to ); ?></code>
				</p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Set a test recipient on the Extras tab (or save an admin notification email).', 'class-bookings-with-stripe-pro' ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $intended['to'] ) : ?>
				<p>
					<strong><?php esc_html_e( 'Intended recipient:', 'class-bookings-with-stripe-pro' ); ?></strong>
					<code><?php echo esc_html( $intended['to'] ); ?></code>
					<span class="description">(<?php echo esc_html( $intended['role'] ); ?>)</span>
				</p>
			<?php endif; ?>
			<?php if ( $needs_rule && ! $scheduled_rule ) : ?>
				<p class="description"><?php esc_html_e( 'Configure the scheduled email below before sending a test.', 'class-bookings-with-stripe-pro' ); ?></p>
			<?php else : ?>
				<div class="clasbpro-email-tab-test__form">
					<p class="clasbpro-email-test-tools__actions">
						<?php if ( $can_send ) : ?>
							<a class="button button-secondary" href="<?php echo esc_url( $test_url ); ?>">
								<?php echo esc_html( $button_labels[ $tab ] ?? __( 'Send test email', 'class-bookings-with-stripe-pro' ) ); ?>
							</a>
						<?php else : ?>
							<span class="button button-secondary disabled" aria-disabled="true">
								<?php echo esc_html( $button_labels[ $tab ] ?? __( 'Send test email', 'class-bookings-with-stripe-pro' ) ); ?>
							</span>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return list<string>
	 */
	public static function get_available_merge_tags( string $tab ): array {
		if ( in_array( $tab, [ 'admin_coupon', 'customer_coupon' ], true ) ) {
			return [
				'{customer_name}',
				'{customer_email}',
				'{pack_name}',
				'{pack_code}',
				'{pack_uses}',
				'{amount_total}',
				'{restore_url}',
				'{purchase_id}',
			];
		}

		$tags = [
			'{customer_name}',
			'{customer_email}',
			'{class_name}',
			'{class_date}',
			'{class_time}',
			'{location}',
			'{slot_label}',
			'{duration}',
			'{price}',
			'{seats}',
			'{amount_total}',
			'{booking_id}',
			'{description}',
			'{extra_fields}',
			'{acf:field_xxxxx}',
		];

		return $tags;
	}

	public static function render_merge_tags_accordion( string $tab ): string {
		$tags = self::get_available_merge_tags( $tab );
		$uid  = 'clasbpro-merge-tags-' . sanitize_html_class( str_replace( '_', '-', $tab ) );
		$is_coupon = in_array( $tab, [ 'admin_coupon', 'customer_coupon' ], true );

		ob_start();
		?>
		<details class="clasbpro-email-merge-tags-accordion">
			<summary class="clasbpro-email-merge-tags-accordion__summary"><?php esc_html_e( 'Available merge tags', 'class-bookings-with-stripe-pro' ); ?></summary>
			<div class="clasbpro-email-merge-tags-accordion__content" id="<?php echo esc_attr( $uid ); ?>">
				<p class="clasbpro-email-merge-tags-accordion__tags">
					<?php foreach ( $tags as $tag ) : ?>
						<code><?php echo esc_html( $tag ); ?></code>
					<?php endforeach; ?>
				</p>
				<?php if ( ! $is_coupon ) : ?>
					<p class="description clasbpro-email-merge-tags-accordion__note">
						<?php esc_html_e( 'For booking-form ACF extras, use {acf:FIELD_KEY} (or {FIELD_KEY}). Example: {acf:field_abc123}.', 'class-bookings-with-stripe-pro' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array{to: string, role: string}
	 */
	public static function get_intended_test_recipient( string $tab ): array {
		$tags = in_array( $tab, [ 'admin_coupon', 'customer_coupon' ], true )
			? self::sample_coupon_merge_tags()
			: Scheduled_Emails::sample_merge_tags();

		if ( in_array( $tab, [ 'admin', 'admin_coupon' ], true ) ) {
			$admin = trim( (string) Helpers::get_option( 'admin_email', '' ) );
			if ( '' === $admin ) {
				$admin = (string) get_option( 'admin_email' );
			}

			return [
				'to'   => is_email( $admin ) ? $admin : '',
				'role' => __( 'Admin', 'class-bookings-with-stripe-pro' ),
			];
		}

		$customer_email = (string) ( $tags['{customer_email}'] ?? '' );

		return [
			'to'   => $customer_email,
			'role' => __( 'Customer', 'class-bookings-with-stripe-pro' ),
		];
	}

	public static function test_sent_notice_message( string $type ): string {
		$labels = [
			'admin'            => __( 'Test admin booking email sent to your test recipient.', 'class-bookings-with-stripe-pro' ),
			'customer'         => __( 'Test customer booking email sent to your test recipient.', 'class-bookings-with-stripe-pro' ),
			'admin_coupon'     => __( 'Test admin coupon email sent to your test recipient.', 'class-bookings-with-stripe-pro' ),
			'customer_coupon'  => __( 'Test customer coupon email sent to your test recipient.', 'class-bookings-with-stripe-pro' ),
			'reminder'         => __( 'Test reminder email sent to your test recipient.', 'class-bookings-with-stripe-pro' ),
			'post_class'       => __( 'Test post-class email sent to your test recipient.', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $type ] ?? __( 'Test email sent to your test recipient.', 'class-bookings-with-stripe-pro' );
	}

	/**
	 * @return array<string, string>
	 */
	private static function test_redirect_subtab_hashes(): array {
		return [
			'admin'            => 'field_clasbpro_email_subtab_admin',
			'customer'         => 'field_clasbpro_email_subtab_customer',
			'admin_coupon'     => 'field_clasbpro_email_subtab_admin_coupon',
			'customer_coupon'  => 'field_clasbpro_email_subtab_customer_coupon',
			'reminder'         => 'field_clasbpro_email_subtab_reminders',
			'post_class'       => 'field_clasbpro_email_subtab_post_class',
		];
	}

	public static function handle_test_booking_email(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}
		check_admin_referer( 'clasbpro_test_booking_email' );

		$type = sanitize_key( (string) ( $_REQUEST['email_type'] ?? '' ) );

		$sent = self::dispatch_test_email( $type );

		$hashes   = self::test_redirect_subtab_hashes();
		$redirect = add_query_arg(
			array_merge(
				[
					'post_type' => CPT::CLASS_PT,
					'page'      => 'clasbowi-settings',
				],
				$sent
					? [ 'clasbpro_booking_test_sent' => $type ]
					: [ 'clasbpro_booking_test_failed' => '1' ]
			),
			admin_url( 'edit.php' )
		);
		$hash = 'clasbpro-tab-' . ( $hashes[ $type ] ?? 'field_clasbpro_email_subtab_extras' );
		wp_safe_redirect( $redirect . '#' . $hash );
		exit;
	}

	public static function dispatch_test_email( string $type ): bool {
		$test_to = self::get_test_recipient();
		if ( ! $test_to || ! is_email( $test_to ) ) {
			wp_die( esc_html__( 'No test recipient configured. Set one on the Extras tab or save an admin notification email.', 'class-bookings-with-stripe-pro' ) );
		}

		if ( 'customer' === $type ) {
			$subject_tpl = (string) Helpers::get_option( 'customer_email_subject', '' );
			$body        = self::resolve_body_template( 'customer' );
			if ( '' === $subject_tpl ) {
				$subject_tpl = self::default_customer_subject();
			}
			$intended = self::get_intended_test_recipient( 'customer' );
			$tags     = Scheduled_Emails::sample_merge_tags();
			return self::send_raw_template(
				$intended['to'] ?: $test_to,
				$subject_tpl,
				$body['body'],
				$tags,
				$intended['role'],
				true,
				$body['html_mode']
			);
		}

		if ( 'admin' === $type ) {
			$subject_tpl = (string) Helpers::get_option( 'admin_email_subject', '' );
			$body        = self::resolve_body_template( 'admin' );
			if ( '' === $subject_tpl ) {
				$subject_tpl = self::default_admin_subject();
			}
			$intended = self::get_intended_test_recipient( 'admin' );
			$tags     = Scheduled_Emails::sample_merge_tags();
			$to       = $intended['to'] ?: $test_to;
			return self::send_raw_template( $to, $subject_tpl, $body['body'], $tags, $intended['role'], true, $body['html_mode'] );
		}

		if ( 'customer_coupon' === $type ) {
			$subject_tpl = (string) Helpers::get_option( 'customer_coupon_email_subject', '' );
			$body        = self::resolve_body_template( 'customer_coupon' );
			if ( '' === $subject_tpl ) {
				$subject_tpl = self::default_customer_coupon_subject();
			}
			$intended = self::get_intended_test_recipient( 'customer_coupon' );
			$tags     = self::sample_coupon_merge_tags();
			return self::send_raw_template(
				$intended['to'] ?: $test_to,
				$subject_tpl,
				$body['body'],
				$tags,
				$intended['role'],
				true,
				$body['html_mode']
			);
		}

		if ( 'admin_coupon' === $type ) {
			$subject_tpl = (string) Helpers::get_option( 'admin_coupon_email_subject', '' );
			$body        = self::resolve_body_template( 'admin_coupon' );
			if ( '' === $subject_tpl ) {
				$subject_tpl = self::default_admin_coupon_subject();
			}
			$intended = self::get_intended_test_recipient( 'admin_coupon' );
			$tags     = self::sample_coupon_merge_tags();
			$to       = $intended['to'] ?: $test_to;
			return self::send_raw_template( $to, $subject_tpl, $body['body'], $tags, $intended['role'], true, $body['html_mode'] );
		}

		if ( 'reminder' === $type || 'post_class' === $type ) {
			return Scheduled_Emails::dispatch_test_rule_email( $type );
		}

		wp_die( esc_html__( 'Invalid email type.', 'class-bookings-with-stripe-pro' ) );
	}

	public static function render_test_mode_admin_notice(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! self::is_local_test_mode() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::CLASS_PT !== $screen->post_type ) {
			return;
		}

		$to = self::get_test_recipient();
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__(
			'Class Bookings email local test mode is ON. All plugin booking emails are redirected to your test address.',
			'class-bookings-with-stripe-pro'
		);
		if ( $to ) {
			echo ' <code>' . esc_html( $to ) . '</code>';
		}
		echo ' <a href="' . esc_url( admin_url( 'edit.php?post_type=' . CPT::CLASS_PT . '&page=clasbowi-settings#clasbpro-tab-field_clasbpro_email_subtab_extras' ) ) . '">';
		esc_html_e( 'Email settings', 'class-bookings-with-stripe-pro' );
		echo '</a></p></div>';
	}

	/**
	 * Send customer + admin emails for a paid booking. Idempotent within the same request.
	 */
	public static function send_for_booking( int $booking_id ): void {
		static $sent = [];
		if ( isset( $sent[ $booking_id ] ) ) {
			return;
		}
		$sent[ $booking_id ] = true;

		$tags = self::build_merge_tags( $booking_id );
		if ( empty( $tags ) ) {
			return;
		}

		self::send_customer( $booking_id, $tags );
		self::send_admin( $booking_id, $tags );
	}

	/**
	 * Email the pack code + restore link after a pack purchase.
	 */
	public static function send_for_pack_purchase( int $purchase_id, string $code, string $restore_url ): void {
		static $sent = [];
		if ( isset( $sent[ $purchase_id ] ) ) {
			return;
		}
		$sent[ $purchase_id ] = true;

		$email     = sanitize_email( (string) get_post_meta( $purchase_id, '_clasbpro_customer_email', true ) );
		$name      = sanitize_text_field( (string) get_post_meta( $purchase_id, '_clasbpro_customer_name', true ) );
		$pack_id   = (int) get_post_meta( $purchase_id, '_clasbpro_pack_id', true );
		$pack_name = $pack_id ? get_the_title( $pack_id ) : __( 'Coupon', 'class-bookings-with-stripe-pro' );
		$uses      = (int) get_post_meta( $purchase_id, '_clasbpro_pack_uses', true );
		$amount    = Helpers::format_stripe_amount( (int) get_post_meta( $purchase_id, '_clasbpro_amount_total', true ) );

		$tags = [
			'{customer_name}'  => $name ?: __( 'there', 'class-bookings-with-stripe-pro' ),
			'{customer_email}' => $email,
			'{pack_name}'      => (string) $pack_name,
			'{pack_code}'      => $code,
			'{pack_uses}'      => (string) $uses,
			'{amount_total}'   => $amount,
			'{restore_url}'    => $restore_url,
			'{purchase_id}'    => '#' . $purchase_id,
		];

		if ( $email && is_email( $email ) ) {
			$subject_tpl = (string) Helpers::get_option( 'customer_coupon_email_subject', '' );
			if ( '' === $subject_tpl ) {
				$subject_tpl = self::default_customer_coupon_subject();
			}
			$body = self::resolve_body_template( 'customer_coupon' );
			$ok   = self::send_raw_template(
				$email,
				$subject_tpl,
				$body['body'],
				$tags,
				__( 'Customer', 'class-bookings-with-stripe-pro' ),
				false,
				$body['html_mode']
			);
			self::record_instant_delivery( $purchase_id, Booking_Email_Status::TYPE_CUSTOMER, $ok );
		} else {
			Booking_Email_Status::record_instant_delivery(
				$purchase_id,
				Booking_Email_Status::TYPE_CUSTOMER,
				[
					'status' => 'failed',
					'error'  => __( 'No valid customer email on this purchase.', 'class-bookings-with-stripe-pro' ),
				]
			);
		}

		$admin = trim( (string) Helpers::get_option( 'admin_email', '' ) );
		if ( '' === $admin || ! is_email( $admin ) ) {
			$admin = (string) get_option( 'admin_email' );
		}
		if ( $admin && is_email( $admin ) ) {
			$subject_tpl = (string) Helpers::get_option( 'admin_coupon_email_subject', '' );
			if ( '' === $subject_tpl ) {
				$subject_tpl = self::default_admin_coupon_subject();
			}
			$body = self::resolve_body_template( 'admin_coupon' );
			$ok   = self::send_raw_template(
				$admin,
				$subject_tpl,
				$body['body'],
				$tags,
				__( 'Admin', 'class-bookings-with-stripe-pro' ),
				false,
				$body['html_mode']
			);
			self::record_instant_delivery( $purchase_id, Booking_Email_Status::TYPE_ADMIN, $ok );
		} else {
			Booking_Email_Status::record_instant_delivery(
				$purchase_id,
				Booking_Email_Status::TYPE_ADMIN,
				[
					'status' => 'failed',
					'error'  => __( 'No valid admin notification email configured.', 'class-bookings-with-stripe-pro' ),
				]
			);
		}
	}

	/**
	 * Sample merge tags for coupon email tests.
	 *
	 * @return array<string, string>
	 */
	public static function sample_coupon_merge_tags(): array {
		return [
			'{customer_name}'  => 'Alex Example',
			'{customer_email}' => 'alex@example.com',
			'{pack_name}'      => '10-class coupon',
			'{pack_code}'      => 'DEMO10CLASS',
			'{pack_uses}'      => '10',
			'{amount_total}'   => Helpers::format_stripe_amount( 15000 ),
			'{restore_url}'    => home_url( '/?clasbpro_pack_restore=sample' ),
			'{purchase_id}'    => '#1001',
		];
	}

	/**
	 * Build merge-tag values for a booking.
	 *
	 * @param array<string, string> $extra
	 * @return array<string, string>|null
	 */
	public static function build_merge_tags( int $booking_id, array $extra = [] ): ?array {
		$meta       = Bookings::get_meta( $booking_id );
		$class_data = Helpers::get_class_data( $meta['class_id'] );
		if ( ! $class_data ) {
			return null;
		}

		$display = Bookings::get_booking_display_context( $booking_id, $class_data );
		$tags    = [
			'{customer_name}'  => (string) $meta['customer_name'],
			'{customer_email}' => (string) $meta['customer_email'],
			'{class_name}'     => (string) ( $class_data['name'] ?? '' ),
			'{class_date}'     => Helpers::format_date( (string) $meta['class_date'] ),
			'{class_time}'     => Helpers::format_time( (string) $display['start_time'] ),
			'{location}'       => (string) $display['location'],
			'{duration}'       => (string) $display['duration'],
			'{price}'          => Helpers::format_price( (float) $display['price'] ),
			'{slot_label}'     => (string) $display['label'],
			'{seats}'          => (string) (int) $meta['seats'],
			'{amount_total}'   => Helpers::format_stripe_amount( (int) $meta['amount_total_pence'] ),
			'{booking_id}'     => '#' . $booking_id,
			'{description}'    => (string) ( $class_data['description'] ?? '' ),
		] + Extra_Fields::build_merge_tags( (int) $meta['class_id'], (string) ( $meta['extra_fields_json'] ?? '' ) );

		return array_merge( $tags, $extra );
	}

	/**
	 * @param array<string, string> $extra_tags
	 */
	public static function send_template( string $to, string $subject_tpl, string $body_tpl, int $booking_id, array $extra_tags = [], bool $html_mode = false ): bool {
		if ( ! $to || ! is_email( $to ) ) {
			return false;
		}

		$tags = self::build_merge_tags( $booking_id, $extra_tags );
		if ( empty( $tags ) ) {
			return false;
		}

		return self::send_raw_template( $to, $subject_tpl, $body_tpl, $tags, '', false, $html_mode );
	}

	/**
	 * @param array<string, string> $extra_tags
	 */
	public static function send_template_to_admin( string $subject_tpl, string $body_tpl, int $booking_id, array $extra_tags = [], bool $html_mode = false ): bool {
		$class_id    = (int) ( Bookings::get_meta( $booking_id )['class_id'] ?? 0 );
		$admin_email = Class_Email_Overrides::resolve_admin_recipient( $class_id );
		if ( ! $admin_email || ! is_email( $admin_email ) ) {
			return false;
		}

		return self::send_template( $admin_email, $subject_tpl, $body_tpl, $booking_id, $extra_tags, $html_mode );
	}

	/**
	 * @return array{body: string, html_mode: bool}
	 */
	public static function resolve_body_template( string $template_key ): array {
		$settings = Email_Body_Editor::get_body_settings( $template_key );
		$body     = $settings['body'];

		if ( '' === $body && ! $settings['html_mode'] ) {
			$body = self::default_body_template( $template_key );
		}

		return [
			'body'      => $body,
			'html_mode' => $settings['html_mode'],
		];
	}

	public static function default_body_template( string $template_key ): string {
		switch ( $template_key ) {
			case 'customer':
				return self::load_template_file( 'email-customer.php' );
			case 'admin':
				return self::load_template_file( 'email-admin.php' );
			case 'customer_coupon':
				return self::load_template_file( 'email-customer-coupon.php' );
			case 'admin_coupon':
				return self::load_template_file( 'email-admin-coupon.php' );
			case 'reminder':
				return Scheduled_Emails::default_reminder_rule_body();
			case 'post_class':
				return Scheduled_Emails::default_post_class_rule_body();
		}

		return '';
	}

	/**
	 * @param array<string, string> $tags
	 */
	public static function send_raw_template( string $to, string $subject_tpl, string $body_tpl, array $tags, string $recipient_role = '', bool $force_test_recipient = false, bool $html_mode = false ): bool {
		if ( ! $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = self::apply_tags( $subject_tpl, $tags );
		$body    = self::apply_tags( $body_tpl, $tags );

		return self::send( $to, $subject, $body, $recipient_role, $force_test_recipient, $html_mode );
	}

	/**
	 * @param array<string, string> $tags
	 */
	private static function send_customer( int $booking_id, array $tags ): void {
		$email = $tags['{customer_email}'] ?? '';
		if ( ! $email || ! is_email( $email ) ) {
			Booking_Email_Status::record_instant_delivery(
				$booking_id,
				Booking_Email_Status::TYPE_CUSTOMER,
				[
					'status' => 'failed',
					'error'  => __( 'No valid customer email on this booking.', 'class-bookings-with-stripe-pro' ),
				]
			);
			return;
		}

		$class_id = (int) ( Bookings::get_meta( $booking_id )['class_id'] ?? 0 );
		if ( ! Class_Email_Overrides::instant_enabled( $class_id, 'customer' ) ) {
			Booking_Email_Status::record_instant_delivery(
				$booking_id,
				Booking_Email_Status::TYPE_CUSTOMER,
				[
					'status' => 'failed',
					'error'  => __( 'Customer email is disabled for this class.', 'class-bookings-with-stripe-pro' ),
				]
			);
			return;
		}

		$subject_tpl = Class_Email_Overrides::resolve_subject( $class_id, 'customer' );
		$body        = Class_Email_Overrides::resolve_body( $class_id, 'customer' );

		$sent = self::send_raw_template( $email, $subject_tpl, $body['body'], $tags, __( 'Customer', 'class-bookings-with-stripe-pro' ), false, $body['html_mode'] );
		self::record_instant_delivery( $booking_id, Booking_Email_Status::TYPE_CUSTOMER, $sent );
	}

	/**
	 * @param array<string, string> $tags
	 */
	private static function send_admin( int $booking_id, array $tags ): void {
		$class_id = (int) ( Bookings::get_meta( $booking_id )['class_id'] ?? 0 );
		if ( ! Class_Email_Overrides::instant_enabled( $class_id, 'admin' ) ) {
			Booking_Email_Status::record_instant_delivery(
				$booking_id,
				Booking_Email_Status::TYPE_ADMIN,
				[
					'status' => 'failed',
					'error'  => __( 'Admin email is disabled for this class.', 'class-bookings-with-stripe-pro' ),
				]
			);
			return;
		}

		$admin_email = Class_Email_Overrides::resolve_admin_recipient( $class_id );
		if ( ! $admin_email || ! is_email( $admin_email ) ) {
			Booking_Email_Status::record_instant_delivery(
				$booking_id,
				Booking_Email_Status::TYPE_ADMIN,
				[
					'status' => 'failed',
					'error'  => __( 'No valid admin notification email configured.', 'class-bookings-with-stripe-pro' ),
				]
			);
			return;
		}

		$subject_tpl = Class_Email_Overrides::resolve_subject( $class_id, 'admin' );
		$body        = Class_Email_Overrides::resolve_body( $class_id, 'admin' );

		$sent = self::send_raw_template( $admin_email, $subject_tpl, $body['body'], $tags, __( 'Admin', 'class-bookings-with-stripe-pro' ), false, $body['html_mode'] );
		self::record_instant_delivery( $booking_id, Booking_Email_Status::TYPE_ADMIN, $sent );
	}

	private static function record_instant_delivery( int $booking_id, string $type, bool $sent ): void {
		$meta = self::consume_last_send_meta();
		$data = [
			'status'       => $sent ? 'sent' : 'failed',
			'delivered_to' => (string) ( $meta['delivered_to'] ?? '' ),
			'intended_to'  => (string) ( $meta['intended_to'] ?? '' ),
			'test_mode'    => ! empty( $meta['test_mode'] ),
		];

		if ( $sent ) {
			$data['sent_at'] = current_time( 'mysql' );
		} else {
			$error = self::consume_last_mail_error();
			if ( '' === $error ) {
				$error = __( 'WordPress could not send the email.', 'class-bookings-with-stripe-pro' );
			}
			$data['error'] = $error;
		}

		Booking_Email_Status::record_instant_delivery( $booking_id, $type, $data );
	}

	/**
	 * @param array<string, string> $tags
	 */
	private static function apply_tags( string $template, array $tags ): string {
		return strtr( $template, $tags );
	}

	private static function send( string $to, string $subject, string $body, string $recipient_role = '', bool $force_test_recipient = false, bool $html_mode = false ): bool {
		$intended_to = $to;
		$banner      = '';
		$test_mode   = $force_test_recipient || self::is_local_test_mode();

		if ( $force_test_recipient || self::is_local_test_mode() ) {
			$test_to = self::get_test_recipient();
			if ( ! $test_to || ! is_email( $test_to ) ) {
				if ( $force_test_recipient ) {
					return false;
				}
			} else {
				$role_label = '' !== $recipient_role ? $recipient_role : __( 'Recipient', 'class-bookings-with-stripe-pro' );
				$banner     = self::build_test_mode_banner( $intended_to, $role_label );
				$to         = $test_to;
				if ( ( $force_test_recipient || self::is_local_test_mode() ) && 0 !== strpos( $subject, '[TEST] ' ) ) {
					$subject = '[TEST] ' . $subject;
				}
			}
		}

		if ( ! $to || ! is_email( $to ) ) {
			self::$last_send_meta = [
				'delivered_to' => '',
				'intended_to'  => $intended_to,
				'test_mode'    => $test_mode,
			];
			return false;
		}

		$headers = self::mail_headers();

		$body_html = self::to_html( $body, $html_mode );
		if ( '' !== $banner ) {
			$body_html = preg_replace(
				'/(<div class="cbfs-mail">)/',
				'$1' . $banner,
				$body_html,
				1
			) ?? $body_html;
		}

		self::$last_mail_error = null;
		self::$last_send_meta  = [
			'delivered_to' => $to,
			'intended_to'  => $intended_to,
			'test_mode'    => $test_mode && '' !== $banner,
		];

		return (bool) wp_mail( $to, wp_strip_all_tags( $subject ), $body_html, $headers );
	}

	/**
	 * @return list<string>
	 */
	private static function mail_headers(): array {
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$from    = trim( (string) Helpers::get_option( 'admin_email', '' ) );
		if ( '' === $from || ! is_email( $from ) ) {
			$from = (string) get_option( 'admin_email' );
		}
		if ( is_email( $from ) ) {
			$name = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
			if ( '' === $name ) {
				$name = 'WordPress';
			}
			$headers[] = sprintf( 'From: %s <%s>', $name, $from );
		}

		return $headers;
	}

	private static function build_test_mode_banner( string $intended_to, string $role_label ): string {
		$message = sprintf(
			/* translators: 1: recipient role label, 2: intended email address */
			__( 'Local test mode — intended %1$s recipient: %2$s', 'class-bookings-with-stripe-pro' ),
			$role_label,
			$intended_to
		);

		return '<div class="cbfs-mail-test-banner" style="margin:0 0 16px;padding:12px 14px;border:1px solid #f0c36d;border-radius:8px;background:#fff8e5;color:#6b4e16;font-size:14px;line-height:1.45;">'
			. '<strong style="display:block;margin-bottom:4px;">'
			. esc_html__( 'Test email', 'class-bookings-with-stripe-pro' )
			. '</strong>'
			. esc_html( $message )
			. '</div>';
	}

	/**
	 * Convert a plain-or-rich body to HTML email markup.
	 */
	private static function to_html( string $body, bool $html_mode = false ): string {
		if ( ! $html_mode ) {
			$looks_like_html = (bool) preg_match( '/<\s*(p|br|ul|ol|li|div|h[1-6]|table|a)\b/i', $body );
			if ( ! $looks_like_html ) {
				$body = wpautop( $body );
			}
		}
		$css_path = CLASBOWPRO_DIR . 'assets/cbfs-booking-email.css';
		$css      = is_readable( $css_path )
			? wp_strip_all_tags( (string) file_get_contents( $css_path ) ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			: '';
		$head     = '<!doctype html><html><head><meta charset="utf-8">';
		if ( '' !== $css ) {
			$head .= '<style>' . $css . '</style>';
		}
		return $head . '</head><body><div class="cbfs-mail">' . $body . '</div></body></html>';
	}

	private static function load_template_file( string $relative ): string {
		$path = CLASBOWPRO_DIR . 'templates/' . ltrim( $relative, '/' );
		if ( ! is_readable( $path ) ) {
			return '';
		}
		$raw = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return self::strip_template_php_guard( $raw );
	}

	/**
	 * Remove optional ABSPATH guard from template files (not part of email body).
	 */
	private static function strip_template_php_guard( string $raw ): string {
		if ( preg_match( '/\A<\?php\b.*?\?>\s*/s', $raw ) ) {
			return (string) preg_replace( '/\A<\?php\b.*?\?>\s*/s', '', $raw, 1 );
		}
		return $raw;
	}

	public static function default_customer_subject(): string {
		return 'Your booking is confirmed: {class_name} on {class_date}';
	}

	public static function default_admin_subject(): string {
		return 'New booking: {customer_name} for {class_name} on {class_date}';
	}

	public static function default_customer_coupon_subject(): string {
		return 'Your coupon: {pack_name}';
	}

	public static function default_admin_coupon_subject(): string {
		return 'New coupon purchase: {pack_name}';
	}
}
