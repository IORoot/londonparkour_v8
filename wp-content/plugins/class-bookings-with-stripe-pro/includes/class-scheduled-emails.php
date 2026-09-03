<?php
/**
 * Scheduled reminder and post-class emails: queue, cron dispatch, deduplication.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Scheduled_Emails {

	public const TYPE_REMINDER   = 'reminder';
	public const TYPE_POST_CLASS = 'post_class';

	public const STATUS_PENDING   = 'pending';
	public const STATUS_SENT      = 'sent';
	public const STATUS_SKIPPED   = 'skipped';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_FAILED    = 'failed';

	public const SKIP_LATE  = 'late';
	public const SKIP_DEDUP = 'dedup';

	private const OPTIONS_POST_ID = 'clasbpro_options';

	public static function init(): void {
		add_action( Bookings::CRON_HOOK, [ self::class, 'process_due_queue' ], 20 );
		add_action( 'acf/save_post', [ self::class, 'ensure_rule_uuids_on_save' ], 25 );
		add_action( 'admin_post_clasbpro_backfill_scheduled_emails', [ self::class, 'handle_backfill' ] );
		add_filter( 'acf/load_field/key=field_clasbpro_scheduled_email_tools', [ self::class, 'load_admin_tools_field' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_assets' ] );
	}

	public static function enqueue_admin_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::CLASS_PT . '_page_clasbowi-settings' !== $screen->id ) {
			return;
		}

		$rules_css = CLASBOWPRO_DIR . 'assets/cbfs-scheduled-email-rules.css';
		wp_enqueue_style(
			'clasbpro-scheduled-email-rules',
			CLASBOWPRO_URL . 'assets/cbfs-scheduled-email-rules.css',
			[],
			is_readable( $rules_css ) ? (string) filemtime( $rules_css ) : CLASBOWPRO_VERSION
		);
	}

	public static function default_reminder_rule_body(): string {
		return __(
			'<p>Hi {customer_name},</p>'
			. '<p>This is a reminder about your upcoming class.</p>'
			. '<ul>'
			. '<li>Class: {class_name}</li>'
			. '<li>When: {class_date} at {class_time}</li>'
			. '<li>Where: {location}</li>'
			. '</ul>'
			. '<p>See you soon!</p>',
			'class-bookings-with-stripe-pro'
		);
	}

	public static function default_post_class_rule_body(): string {
		return __(
			'<p>Hi {customer_name},</p>'
			. '<p>Thanks for joining {class_name}. We hope you enjoyed the class!</p>',
			'class-bookings-with-stripe-pro'
		);
	}

	/**
	 * Remove legacy {feedback_url} merge tags and related markup from email templates.
	 */
	public static function strip_feedback_merge_tags( string $text ): string {
		if ( '' === $text || false === stripos( $text, 'feedback' ) ) {
			return $text;
		}

		$text = (string) preg_replace(
			'/<p>\s*(?:<a[^>]*href=["\']?\{feedback_url\}["\']?[^>]*>.*?<\/a>|[^<]*\{feedback_url\}[^<]*)\s*<\/p>/is',
			'',
			$text
		);
		$text = str_replace( '{feedback_url}', '', $text );
		$text = (string) preg_replace( '/<p>\s*<\/p>/', '', $text );

		return trim( $text );
	}

	public static function ensure_body_html_mode_column(): void {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$column = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'body_html_mode'" );
		if ( ! empty( $column ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table} ADD COLUMN body_html_mode tinyint(1) NOT NULL DEFAULT 0 AFTER body_tpl" );
	}

	public static function drop_feedback_url_column(): void {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$column = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'feedback_url'" );
		if ( empty( $column ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table} DROP COLUMN feedback_url" );
	}

	public static function clean_feedback_merge_tags_from_queue(): void {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT id, subject_tpl, body_tpl FROM {$table} WHERE subject_tpl LIKE '%{feedback_url}%' OR body_tpl LIKE '%{feedback_url}%'",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$id = (int) ( $row['id'] ?? 0 );
			if ( ! $id ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				[
					'subject_tpl' => self::strip_feedback_merge_tags( (string) ( $row['subject_tpl'] ?? '' ) ),
					'body_tpl'    => self::strip_feedback_merge_tags( (string) ( $row['body_tpl'] ?? '' ) ),
				],
				[ 'id' => $id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
		}
	}

	/**
	 * @param int|string $post_id
	 */
	public static function ensure_rule_uuids_on_save( $post_id ): void {
		if ( ! function_exists( 'update_field' ) ) {
			return;
		}

		if ( (string) $post_id !== self::OPTIONS_POST_ID && (string) $post_id !== 'options' ) {
			return;
		}

		foreach ( [ self::TYPE_REMINDER, self::TYPE_POST_CLASS ] as $type ) {
			$field = self::TYPE_REMINDER === $type ? 'reminder_email_rule_uuid' : 'post_class_email_rule_uuid';
			$uuid  = trim( (string) Helpers::get_option( $field, '' ) );
			if ( '' === $uuid ) {
				update_field( $field, wp_generate_uuid4(), $post_id );
			}
		}
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_global_rule( string $type ): ?array {
		if ( ! self::category_enabled( $type ) ) {
			return null;
		}

		return self::build_rule_from_options( $type );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function build_rule_from_options( string $type ): ?array {
		$prefix = self::TYPE_REMINDER === $type ? 'reminder' : 'post_class';

		$amount = max( 0, (int) Helpers::get_option( $prefix . '_offset_amount', 0 ) );
		$unit   = (string) Helpers::get_option( $prefix . '_offset_unit', 'hours' );
		if ( ! in_array( $unit, [ 'minutes', 'hours', 'days', 'weeks', 'months' ], true ) ) {
			$unit = 'hours';
		}

		$subject = trim( (string) Helpers::get_option( $prefix . '_email_subject', '' ) );
		$body_settings = Emails::resolve_body_template( self::TYPE_REMINDER === $type ? 'reminder' : 'post_class' );
		$body          = self::strip_feedback_merge_tags( trim( (string) $body_settings['body'] ) );
		if ( $amount <= 0 || '' === $subject || '' === $body ) {
			return null;
		}

		$uuid = trim( (string) Helpers::get_option( $prefix . '_email_rule_uuid', '' ) );
		if ( '' === $uuid ) {
			$uuid = wp_generate_uuid4();
		}

		$label = self::TYPE_REMINDER === $type
			? __( 'Reminder', 'class-bookings-with-stripe-pro' )
			: __( 'Post-class email', 'class-bookings-with-stripe-pro' );

		return [
			'uuid'           => $uuid,
			'label'          => $label,
			'type'           => $type,
			'offset_amount'  => $amount,
			'offset_unit'    => $unit,
			'subject'        => $subject,
			'body'           => $body,
			'body_html_mode' => Email_Body_Editor::queue_flag( (string) ( $body_settings['editor_mode'] ?? Email_Body_Editor::MODE_VISUAL ) ),
			'admin_copy'     => ! empty( Helpers::get_option( $prefix . '_admin_copy', 0 ) ),
			'max_sends'      => 1,
		];
	}

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'clasbpro_scheduled_emails';
	}

	public static function create_table(): void {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			class_id bigint(20) unsigned NOT NULL,
			customer_email varchar(200) NOT NULL DEFAULT '',
			rule_id varchar(36) NOT NULL DEFAULT '',
			rule_type varchar(20) NOT NULL DEFAULT '',
			rule_label varchar(200) NOT NULL DEFAULT '',
			send_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			skip_reason varchar(50) NOT NULL DEFAULT '',
			last_error text NOT NULL,
			sent_at datetime DEFAULT NULL,
			subject_tpl text NOT NULL,
			body_tpl longtext NOT NULL,
			body_html_mode tinyint(1) NOT NULL DEFAULT 0,
			admin_copy tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_rule (booking_id, rule_id, rule_type),
			KEY due_queue (status, send_at),
			KEY dedup_lookup (customer_email(100), class_id, rule_id, rule_type)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Queue scheduled emails when a booking is marked paid.
	 */
	public static function queue_for_booking( int $booking_id ): void {
		if ( Bookings::STATUS_PAID !== Bookings::get_status( $booking_id ) ) {
			return;
		}

		$meta = Bookings::get_meta( $booking_id );
		if ( empty( $meta['class_id'] ) || empty( $meta['customer_email'] ) ) {
			return;
		}

		$class_id = (int) $meta['class_id'];
		$email    = sanitize_email( (string) $meta['customer_email'] );
		if ( ! is_email( $email ) ) {
			return;
		}

		$start = self::booking_start_datetime( $booking_id );
		$end   = self::booking_end_datetime( $booking_id );
		if ( ! $start || ! $end ) {
			return;
		}

		$now_gmt = gmdate( 'Y-m-d H:i:s' );

		foreach ( self::resolve_rules( $class_id, self::TYPE_REMINDER ) as $rule ) {
			$seconds  = self::offset_to_seconds( (int) $rule['offset_amount'], (string) $rule['offset_unit'] );
			$send_local = $start->modify( '-' . $seconds . ' seconds' );
			$send_at  = $send_local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			$status   = self::STATUS_PENDING;
			$skip     = '';

			if ( $send_at <= $now_gmt ) {
				$status = self::STATUS_SKIPPED;
				$skip   = self::SKIP_LATE;
			}

			self::insert_queue_row(
				$booking_id,
				$class_id,
				$email,
				$rule,
				self::TYPE_REMINDER,
				$send_at,
				$status,
				$skip
			);
		}

		foreach ( self::resolve_rules( $class_id, self::TYPE_POST_CLASS ) as $rule ) {
			$seconds    = self::offset_to_seconds( (int) $rule['offset_amount'], (string) $rule['offset_unit'] );
			$send_local = $end->modify( '+' . $seconds . ' seconds' );
			$send_at    = $send_local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			$status     = self::STATUS_PENDING;
			$skip       = '';

			if ( $send_at <= $now_gmt ) {
				$status = self::STATUS_SKIPPED;
				$skip   = self::SKIP_LATE;
			} elseif ( self::dedup_cap_reached( $email, $class_id, (string) $rule['uuid'], (int) $rule['max_sends'] ) ) {
				$status = self::STATUS_SKIPPED;
				$skip   = self::SKIP_DEDUP;
			}

			self::insert_queue_row(
				$booking_id,
				$class_id,
				$email,
				$rule,
				self::TYPE_POST_CLASS,
				$send_at,
				$status,
				$skip
			);
		}
	}

	public static function cancel_for_booking( int $booking_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			self::table_name(),
			[
				'status' => self::STATUS_CANCELLED,
			],
			[
				'booking_id' => $booking_id,
				'status'     => self::STATUS_PENDING,
			],
			[ '%s' ],
			[ '%d', '%s' ]
		);
	}

	public static function process_due_queue(): void {
		if ( ! self::category_enabled( self::TYPE_REMINDER ) && ! self::category_enabled( self::TYPE_POST_CLASS ) ) {
			return;
		}

		global $wpdb;

		$table   = self::table_name();
		$now_gmt = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s AND send_at <= %s ORDER BY send_at ASC LIMIT 50",
				self::STATUS_PENDING,
				$now_gmt
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			self::process_queue_row( $row );
		}
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function process_queue_row( array $row ): void {
		$id         = (int) ( $row['id'] ?? 0 );
		$booking_id = (int) ( $row['booking_id'] ?? 0 );
		$class_id   = (int) ( $row['class_id'] ?? 0 );
		$rule_type  = (string) ( $row['rule_type'] ?? '' );

		if ( ! $id || ! $booking_id ) {
			return;
		}

		if ( ! self::category_enabled( $rule_type ) ) {
			self::update_row_status( $id, self::STATUS_CANCELLED, '' );
			return;
		}

		if ( $class_id > 0 && ! self::class_category_enabled( $class_id, $rule_type ) ) {
			self::update_row_status( $id, self::STATUS_CANCELLED, '' );
			return;
		}

		if ( Bookings::STATUS_PAID !== Bookings::get_status( $booking_id ) ) {
			self::update_row_status( $id, self::STATUS_CANCELLED, '' );
			return;
		}

		if ( self::TYPE_POST_CLASS === $rule_type ) {
			$max_sends = self::max_sends_for_rule( $class_id, (string) ( $row['rule_id'] ?? '' ) );
			if ( self::dedup_cap_reached(
				(string) ( $row['customer_email'] ?? '' ),
				$class_id,
				(string) ( $row['rule_id'] ?? '' ),
				$max_sends
			) ) {
				self::update_row_status( $id, self::STATUS_SKIPPED, self::SKIP_DEDUP );
				return;
			}
		}

		$html_mode = (int) ( $row['body_html_mode'] ?? 0 );

		$sent = Emails::send_template(
			(string) ( $row['customer_email'] ?? '' ),
			(string) ( $row['subject_tpl'] ?? '' ),
			(string) ( $row['body_tpl'] ?? '' ),
			$booking_id,
			[],
			$html_mode
		);

		if ( ! $sent ) {
			$error = Emails::consume_last_mail_error();
			if ( '' === $error ) {
				$error = __( 'WordPress could not send the email.', 'class-bookings-with-stripe-pro' );
			}
			self::update_row_status( $id, self::STATUS_FAILED, '', '', $error );
			return;
		}

		if ( ! empty( $row['admin_copy'] ) ) {
			Emails::send_template_to_admin(
				(string) ( $row['subject_tpl'] ?? '' ),
				(string) ( $row['body_tpl'] ?? '' ),
				$booking_id,
				[],
				$html_mode
			);
		}

		self::update_row_status( $id, self::STATUS_SENT, '', gmdate( 'Y-m-d H:i:s' ) );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_rows_for_booking( int $booking_id ): array {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE booking_id = %d ORDER BY send_at ASC, id ASC",
				$booking_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function decode_rules_json( string $raw ): array {
		if ( '' === trim( $raw ) ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function load_admin_tools_field( array $field ): array {
		$field['message']   = self::render_admin_tools();
		$field['new_lines'] = '';
		$field['esc_html']  = false;
		return $field;
	}

	public static function render_admin_tools(): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$backfill_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=clasbpro_backfill_scheduled_emails' ),
			'clasbpro_backfill_scheduled_emails'
		);

		ob_start();
		?>
		<div class="clasbpro-scheduled-email-tools">
			<p><?php esc_html_e( 'Queue reminders and post-class emails for existing paid bookings whose class has not ended yet. Skip-if-late and dedup rules still apply.', 'class-bookings-with-stripe-pro' ); ?></p>
			<p class="clasbpro-scheduled-email-tools__actions">
				<a class="button button-secondary" href="<?php echo esc_url( $backfill_url ); ?>"><?php esc_html_e( 'Schedule emails for existing upcoming bookings', 'class-bookings-with-stripe-pro' ); ?></a>
			</p>

		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function handle_backfill(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'class-bookings-with-stripe-pro' ) );
		}
		check_admin_referer( 'clasbpro_backfill_scheduled_emails' );

		$query = new \WP_Query(
			[
				'post_type'      => CPT::BOOKING_PT,
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					[
						'key'   => '_clasbpro_status',
						'value' => Bookings::STATUS_PAID,
					],
				],
			]
		);

		$queued = 0;
		foreach ( $query->posts as $booking_id ) {
			$booking_id = (int) $booking_id;
			$end        = self::booking_end_datetime( $booking_id );
			if ( ! $end ) {
				continue;
			}
			$now = new \DateTimeImmutable( 'now', wp_timezone() );
			if ( $end < $now ) {
				continue;
			}
			$before = count( self::get_rows_for_booking( $booking_id ) );
			self::queue_for_booking( $booking_id );
			if ( count( self::get_rows_for_booking( $booking_id ) ) > $before ) {
				++$queued;
			}
		}

		$redirect = add_query_arg(
			[
				'post_type'              => CPT::CLASS_PT,
				'page'                   => 'clasbowi-settings',
				'clasbpro_backfill_done' => $queued,
			],
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function dispatch_test_rule_email( string $type ): bool {
		$rule = self::get_global_rule( $type );
		if ( ! $rule ) {
			wp_die( esc_html__( 'Configure the scheduled email on the settings screen first.', 'class-bookings-with-stripe-pro' ) );
		}

		$tags = self::sample_merge_tags();

		$intended_to  = (string) ( $tags['{customer_email}'] ?? '' );
		$role_label   = sprintf(
			/* translators: %s: scheduled email rule label */
			__( 'Customer (%s)', 'class-bookings-with-stripe-pro' ),
			(string) $rule['label']
		);
		$test_to      = Emails::get_test_recipient();
		$intended_for = ( $intended_to && is_email( $intended_to ) ) ? $intended_to : $test_to;

		return Emails::send_raw_template(
			$intended_for,
			(string) $rule['subject'],
			(string) $rule['body'],
			$tags,
			$role_label,
			true,
			(int) ( $rule['body_html_mode'] ?? 0 )
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function sample_merge_tags(): array {
		return Merge_Tags::sample_booking_tags();
	}

	public static function render_booking_queue_table( int $booking_id ): string {
		$rows = self::get_rows_for_booking( $booking_id );
		if ( empty( $rows ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="cbfs-admin-summary__scheduled">
			<h4 class="cbfs-admin-summary__extras-title"><?php esc_html_e( 'Scheduled emails', 'class-bookings-with-stripe-pro' ); ?></h4>
			<table class="cbfs-admin-summary__table cbfs-admin-summary__table--scheduled">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Rule', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Send at', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'class-bookings-with-stripe-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( (string) ( $row['rule_label'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( self::format_send_at_for_display( (string) ( $row['send_at'] ?? '' ) ) ); ?></td>
						<td><?php echo esc_html( self::status_label( (string) ( $row['status'] ?? '' ), (string) ( $row['skip_reason'] ?? '' ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function format_send_at_for_display( string $send_at_gmt ): string {
		if ( '' === $send_at_gmt ) {
			return '—';
		}
		try {
			$dt = new \DateTimeImmutable( $send_at_gmt, new \DateTimeZone( 'UTC' ) );
			$dt = $dt->setTimezone( wp_timezone() );
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dt->getTimestamp() );
		} catch ( \Exception $e ) {
			return $send_at_gmt;
		}
	}

	public static function status_label( string $status, string $skip_reason ): string {
		if ( self::STATUS_SENT === $status ) {
			return __( 'Sent', 'class-bookings-with-stripe-pro' );
		}
		if ( self::STATUS_PENDING === $status ) {
			return __( 'Pending', 'class-bookings-with-stripe-pro' );
		}
		if ( self::STATUS_CANCELLED === $status ) {
			return __( 'Cancelled', 'class-bookings-with-stripe-pro' );
		}
		if ( self::STATUS_FAILED === $status ) {
			return __( 'Failed', 'class-bookings-with-stripe-pro' );
		}
		if ( self::STATUS_SKIPPED === $status ) {
			if ( self::SKIP_LATE === $skip_reason ) {
				return __( 'Skipped (late)', 'class-bookings-with-stripe-pro' );
			}
			if ( self::SKIP_DEDUP === $skip_reason ) {
				return __( 'Skipped (already sent for class type)', 'class-bookings-with-stripe-pro' );
			}
			return __( 'Skipped', 'class-bookings-with-stripe-pro' );
		}
		return ucfirst( $status );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve_rules( int $class_id, string $type ): array {
		if ( ! self::category_enabled( $type ) ) {
			return [];
		}

		if ( ! Class_Email_Overrides::scheduled_send_enabled( $class_id, $type ) ) {
			return [];
		}

		if ( Class_Email_Overrides::uses_custom( $class_id, $type ) ) {
			$rule = Class_Email_Overrides::build_class_rule( $class_id, $type );
			return $rule ? [ $rule ] : [];
		}

		$rule = self::get_global_rule( $type );
		return $rule ? [ $rule ] : [];
	}

	public static function requeue_bookings_for_class( int $class_id ): void {
		if ( $class_id <= 0 ) {
			return;
		}

		$query = new \WP_Query(
			[
				'post_type'      => CPT::BOOKING_PT,
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => '_clasbpro_status',
						'value' => Bookings::STATUS_PAID,
					],
					[
						'key'   => '_clasbpro_class_id',
						'value' => $class_id,
					],
				],
			]
		);

		foreach ( $query->posts as $booking_id ) {
			$booking_id = (int) $booking_id;
			$end        = self::booking_end_datetime( $booking_id );
			if ( ! $end ) {
				continue;
			}
			$now = new \DateTimeImmutable( 'now', wp_timezone() );
			if ( $end < $now ) {
				continue;
			}

			self::delete_pending_for_booking( $booking_id );
			self::queue_for_booking( $booking_id );
		}
	}

	public static function delete_pending_for_booking( int $booking_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->delete(
			self::table_name(),
			[
				'booking_id' => $booking_id,
				'status'     => self::STATUS_PENDING,
			],
			[ '%d', '%s' ]
		);
	}

	public static function category_enabled( string $type ): bool {
		if ( self::TYPE_REMINDER === $type ) {
			$val = Helpers::get_option( 'enable_reminder_emails', 1 );
			return (bool) $val;
		}
		if ( self::TYPE_POST_CLASS === $type ) {
			$val = Helpers::get_option( 'enable_post_class_emails', 1 );
			return (bool) $val;
		}
		return false;
	}

	public static function class_category_enabled( int $class_id, string $type ): bool {
		if ( ! function_exists( 'get_field' ) ) {
			return true;
		}

		$field = self::TYPE_REMINDER === $type ? 'send_reminder_emails' : 'send_post_class_emails';
		$val   = get_field( $field, $class_id );
		if ( null === $val || '' === $val ) {
			return true;
		}

		return (bool) $val;
	}

	private static function max_sends_for_rule( int $class_id, string $rule_uuid ): int {
		unset( $class_id, $rule_uuid );
		return 1;
	}

	private static function dedup_cap_reached( string $email, int $class_id, string $rule_uuid, int $max_sends ): bool {
		if ( $max_sends < 1 || '' === $email || '' === $rule_uuid ) {
			return true;
		}

		global $wpdb;
		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table}
				WHERE customer_email = %s AND class_id = %d AND rule_id = %s AND rule_type = %s AND status = %s",
				$email,
				$class_id,
				$rule_uuid,
				self::TYPE_POST_CLASS,
				self::STATUS_SENT
			)
		);

		return $count >= $max_sends;
	}

	/**
	 * @param array<string, mixed> $rule
	 */
	private static function insert_queue_row(
		int $booking_id,
		int $class_id,
		string $email,
		array $rule,
		string $type,
		string $send_at,
		string $status,
		string $skip_reason
	): void {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE booking_id = %d AND rule_id = %s AND rule_type = %s LIMIT 1",
				$booking_id,
				(string) $rule['uuid'],
				$type
			)
		);
		if ( $exists > 0 ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'booking_id'     => $booking_id,
				'class_id'       => $class_id,
				'customer_email' => $email,
				'rule_id'        => (string) $rule['uuid'],
				'rule_type'      => $type,
				'rule_label'     => (string) $rule['label'],
				'send_at'        => $send_at,
				'status'         => $status,
				'skip_reason'    => $skip_reason,
				'subject_tpl'    => (string) $rule['subject'],
				'body_tpl'       => (string) $rule['body'],
				'body_html_mode' => (int) ( $rule['body_html_mode'] ?? 0 ),
				'admin_copy'     => ! empty( $rule['admin_copy'] ) ? 1 : 0,
				'created_at'     => gmdate( 'Y-m-d H:i:s' ),
			],
			[
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
			]
		);
	}

	private static function update_row_status( int $id, string $status, string $skip_reason, string $sent_at = '', string $last_error = '' ): void {
		global $wpdb;

		$data = [
			'status'      => $status,
			'skip_reason' => $skip_reason,
		];
		$format = [ '%s', '%s' ];

		if ( '' !== $last_error ) {
			$data['last_error'] = $last_error;
			$format[]           = '%s';
		}

		if ( '' !== $sent_at ) {
			$data['sent_at'] = $sent_at;
			$format[]        = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			self::table_name(),
			$data,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		);
	}

	private static function offset_to_seconds( int $amount, string $unit ): int {
		switch ( $unit ) {
			case 'minutes':
				return $amount * MINUTE_IN_SECONDS;
			case 'hours':
				return $amount * HOUR_IN_SECONDS;
			case 'days':
				return $amount * DAY_IN_SECONDS;
			case 'weeks':
				return $amount * WEEK_IN_SECONDS;
			case 'months':
				return $amount * 30 * DAY_IN_SECONDS;
		}
		return 0;
	}

	private static function booking_start_datetime( int $booking_id ): ?\DateTimeImmutable {
		$meta       = Bookings::get_meta( $booking_id );
		$class_data = Helpers::get_class_data( (int) $meta['class_id'] );
		$display    = Bookings::get_booking_display_context( $booking_id, $class_data );
		$start_time = (string) ( $display['start_time'] ?? '' );
		if ( '' === $start_time ) {
			$start_time = '00:00';
		}

		try {
			return new \DateTimeImmutable(
				(string) $meta['class_date'] . ' ' . $start_time,
				wp_timezone()
			);
		} catch ( \Exception $e ) {
			return null;
		}
	}

	private static function booking_end_datetime( int $booking_id ): ?\DateTimeImmutable {
		$start = self::booking_start_datetime( $booking_id );
		if ( ! $start ) {
			return null;
		}

		$class_data = Helpers::get_class_data( (int) Bookings::get_meta( $booking_id )['class_id'] );
		$display    = Bookings::get_booking_display_context( $booking_id, $class_data );
		$duration   = max( 0, (int) ( $display['duration'] ?? 0 ) );

		return $start->modify( '+' . $duration . ' minutes' );
	}
}
