<?php
/**
 * Booking email delivery status (admin, customer, reminder, post-class).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Booking_Email_Status {

	public const TYPE_ADMIN      = 'admin';
	public const TYPE_CUSTOMER   = 'customer';
	public const TYPE_REMINDER   = 'reminder';
	public const TYPE_POST_CLASS = 'post_class';

	public const PILL_SENT             = 'sent';
	public const PILL_ERROR            = 'error';
	public const PILL_NOT_SENT         = 'not_sent';
	public const PILL_PAST_DATE        = 'past_date';
	public const PILL_DISABLED         = 'disabled';
	public const PILL_WAITING_PAYMENT  = 'waiting_payment';
	public const PILL_CANCELLED        = 'cancelled';
	public const PILL_NOT_RECORDED     = 'not_recorded';

	/**
	 * @return list<array{key: string, label: string}>
	 */
	public static function email_types(): array {
		return [
			[
				'key'   => self::TYPE_ADMIN,
				'label' => __( 'Admin email', 'class-bookings-with-stripe-pro' ),
			],
			[
				'key'   => self::TYPE_CUSTOMER,
				'label' => __( 'Customer email', 'class-bookings-with-stripe-pro' ),
			],
			[
				'key'   => self::TYPE_REMINDER,
				'label' => __( 'Reminder', 'class-bookings-with-stripe-pro' ),
			],
			[
				'key'   => self::TYPE_POST_CLASS,
				'label' => __( 'Post-class email', 'class-bookings-with-stripe-pro' ),
			],
		];
	}

	/**
	 * @return array{slug: string, label: string, detail: string}
	 */
	public static function resolve( int $booking_id, string $type ): array {
		$booking_status = Bookings::get_status( $booking_id );

		if ( in_array( $type, [ self::TYPE_ADMIN, self::TYPE_CUSTOMER ], true ) ) {
			return self::resolve_instant( $booking_id, $type, $booking_status );
		}

		return self::resolve_scheduled( $booking_id, $type, $booking_status );
	}

	public static function render_booking_panel( int $booking_id ): string {
		ob_start();
		?>
		<div class="cbfs-admin-summary__extras cbfs-admin-summary__emails">
			<h4 class="cbfs-admin-summary__extras-title"><?php esc_html_e( 'Emails', 'class-bookings-with-stripe-pro' ); ?></h4>
			<table class="cbfs-admin-summary__table cbfs-admin-summary__table--emails">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Email', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Details', 'class-bookings-with-stripe-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( self::email_types() as $email_type ) : ?>
					<?php
					$status = self::resolve( $booking_id, (string) $email_type['key'] );
					$pill   = self::pill_label( (string) $status['slug'] );
					$detail = (string) $status['detail'];
					?>
					<tr>
						<th scope="row"><?php echo esc_html( (string) $email_type['label'] ); ?></th>
						<td class="cbfs-admin-summary__email-status-cell">
							<span class="cbfs-admin-summary__status cbfs-admin-summary__status--email cbfs-admin-summary__status--email-<?php echo esc_attr( (string) $status['slug'] ); ?>">
								<?php echo esc_html( $pill ); ?>
							</span>
						</td>
						<td class="cbfs-admin-summary__email-detail-cell">
							<?php echo '' !== $detail ? esc_html( $detail ) : '—'; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Admin + customer email status for a coupon purchase (no scheduled emails).
	 */
	public static function render_purchase_panel( int $purchase_id ): string {
		$types = [
			[
				'key'   => self::TYPE_ADMIN,
				'label' => __( 'Admin email', 'class-bookings-with-stripe-pro' ),
			],
			[
				'key'   => self::TYPE_CUSTOMER,
				'label' => __( 'Customer email', 'class-bookings-with-stripe-pro' ),
			],
		];

		ob_start();
		?>
		<div class="cbfs-admin-summary__extras cbfs-admin-summary__emails">
			<h4 class="cbfs-admin-summary__extras-title"><?php esc_html_e( 'Emails', 'class-bookings-with-stripe-pro' ); ?></h4>
			<table class="cbfs-admin-summary__table cbfs-admin-summary__table--emails">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Email', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'class-bookings-with-stripe-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Details', 'class-bookings-with-stripe-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $types as $email_type ) : ?>
					<?php
					$status = self::resolve_purchase( $purchase_id, (string) $email_type['key'] );
					$pill   = self::pill_label( (string) $status['slug'] );
					$detail = (string) $status['detail'];
					?>
					<tr>
						<th scope="row"><?php echo esc_html( (string) $email_type['label'] ); ?></th>
						<td class="cbfs-admin-summary__email-status-cell">
							<span class="cbfs-admin-summary__status cbfs-admin-summary__status--email cbfs-admin-summary__status--email-<?php echo esc_attr( (string) $status['slug'] ); ?>">
								<?php echo esc_html( $pill ); ?>
							</span>
						</td>
						<td class="cbfs-admin-summary__email-detail-cell">
							<?php echo '' !== $detail ? esc_html( $detail ) : '—'; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * @return array{slug: string, label: string, detail: string}
	 */
	public static function resolve_purchase( int $purchase_id, string $type ): array {
		return self::resolve_instant_for_payment_status(
			$purchase_id,
			$type,
			Packs::get_purchase_status( $purchase_id ),
			'purchase'
		);
	}

	/**
	 * @param array{status: string, sent_at?: string, delivered_to?: string, intended_to?: string, test_mode?: bool, error?: string} $data
	 */
	public static function record_instant_delivery( int $booking_id, string $type, array $data ): void {
		if ( ! in_array( $type, [ self::TYPE_ADMIN, self::TYPE_CUSTOMER ], true ) ) {
			return;
		}

		update_post_meta( $booking_id, self::meta_key( $type ), wp_json_encode( $data ) );
	}

	/**
	 * @return array{status: string, sent_at?: string, delivered_to?: string, intended_to?: string, test_mode?: bool, error?: string}|null
	 */
	public static function get_instant_delivery( int $booking_id, string $type ): ?array {
		$raw = get_post_meta( $booking_id, self::meta_key( $type ), true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	private static function meta_key( string $type ): string {
		return '_clasbpro_email_delivery_' . $type;
	}

	/**
	 * @return array{slug: string, label: string, detail: string}
	 */
	private static function resolve_instant( int $booking_id, string $type, string $booking_status ): array {
		return self::resolve_instant_for_payment_status( $booking_id, $type, $booking_status, 'booking' );
	}

	/**
	 * @param 'booking'|'purchase' $context
	 * @return array{slug: string, label: string, detail: string}
	 */
	private static function resolve_instant_for_payment_status( int $post_id, string $type, string $payment_status, string $context ): array {
		$is_purchase = 'purchase' === $context;

		if ( Packs::STATUS_PAID !== $payment_status && Bookings::STATUS_PAID !== $payment_status ) {
			if ( Bookings::STATUS_REFUNDED === $payment_status ) {
				$log = self::get_instant_delivery( $post_id, $type );
				if ( $log && 'sent' === ( $log['status'] ?? '' ) ) {
					return self::instant_sent_status( $log );
				}

				return [
					'slug'   => self::PILL_CANCELLED,
					'label'  => self::pill_label( self::PILL_CANCELLED ),
					'detail' => $is_purchase
						? __( 'Purchase was refunded before this email was sent.', 'class-bookings-with-stripe-pro' )
						: __( 'Booking was refunded before this email was sent.', 'class-bookings-with-stripe-pro' ),
				];
			}

			if ( Packs::STATUS_EXPIRED === $payment_status || Bookings::STATUS_EXPIRED === $payment_status ) {
				return [
					'slug'   => self::PILL_WAITING_PAYMENT,
					'label'  => self::pill_label( self::PILL_WAITING_PAYMENT ),
					'detail' => __( 'Checkout expired before payment completed.', 'class-bookings-with-stripe-pro' ),
				];
			}

			return [
				'slug'   => self::PILL_WAITING_PAYMENT,
				'label'  => self::pill_label( self::PILL_WAITING_PAYMENT ),
				'detail' => __( 'Sent after payment is completed.', 'class-bookings-with-stripe-pro' ),
			];
		}

		$log = self::get_instant_delivery( $post_id, $type );
		if ( ! $log ) {
			return [
				'slug'   => self::PILL_NOT_RECORDED,
				'label'  => self::pill_label( self::PILL_NOT_RECORDED ),
				'detail' => $is_purchase
					? __( 'Send outcome was not logged for this purchase.', 'class-bookings-with-stripe-pro' )
					: __( 'Send outcome was not logged for this booking.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( 'sent' === ( $log['status'] ?? '' ) ) {
			return self::instant_sent_status( $log );
		}

		if ( 'failed' === ( $log['status'] ?? '' ) ) {
			$error = trim( (string) ( $log['error'] ?? '' ) );

			return [
				'slug'   => self::PILL_ERROR,
				'label'  => self::pill_label( self::PILL_ERROR ),
				'detail' => '' !== $error ? $error : __( 'WordPress could not send the email.', 'class-bookings-with-stripe-pro' ),
			];
		}

		return [
			'slug'   => self::PILL_NOT_SENT,
			'label'  => self::pill_label( self::PILL_NOT_SENT ),
			'detail' => __( 'This email was not sent.', 'class-bookings-with-stripe-pro' ),
		];
	}

	/**
	 * @param array{status?: string, sent_at?: string, delivered_to?: string, intended_to?: string, test_mode?: bool, error?: string} $log
	 * @return array{slug: string, label: string, detail: string}
	 */
	private static function instant_sent_status( array $log ): array {
		$parts = [];
		if ( ! empty( $log['sent_at'] ) ) {
			$parts[] = self::format_datetime( (string) $log['sent_at'] );
		}
		if ( ! empty( $log['delivered_to'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: email address */
				__( 'to %s', 'class-bookings-with-stripe-pro' ),
				(string) $log['delivered_to']
			);
		}

		$detail = implode( ' · ', $parts );

		if ( ! empty( $log['test_mode'] ) && ! empty( $log['intended_to'] ) ) {
			$detail = trim(
				$detail . ' · ' . sprintf(
					/* translators: 1: test recipient email, 2: intended recipient email */
					__( 'Delivered to test recipient (%1$s); intended: %2$s', 'class-bookings-with-stripe-pro' ),
					(string) ( $log['delivered_to'] ?? '' ),
					(string) $log['intended_to']
				)
			);
		}

		return [
			'slug'   => self::PILL_SENT,
			'label'  => self::pill_label( self::PILL_SENT ),
			'detail' => $detail,
		];
	}

	/**
	 * @return array{slug: string, label: string, detail: string}
	 */
	private static function resolve_scheduled( int $booking_id, string $type, string $booking_status ): array {
		$scheduled_type = self::TYPE_REMINDER === $type ? Scheduled_Emails::TYPE_REMINDER : Scheduled_Emails::TYPE_POST_CLASS;

		if ( Bookings::STATUS_PAID !== $booking_status ) {
			if ( Bookings::STATUS_REFUNDED === $booking_status ) {
				return [
					'slug'   => self::PILL_CANCELLED,
					'label'  => self::pill_label( self::PILL_CANCELLED ),
					'detail' => __( 'Scheduled email was cancelled when the booking was refunded.', 'class-bookings-with-stripe-pro' ),
				];
			}

			return [
				'slug'   => self::PILL_WAITING_PAYMENT,
				'label'  => self::pill_label( self::PILL_WAITING_PAYMENT ),
				'detail' => __( 'Queued after payment is completed.', 'class-bookings-with-stripe-pro' ),
			];
		}

		$meta     = Bookings::get_meta( $booking_id );
		$class_id = (int) ( $meta['class_id'] ?? 0 );

		if ( ! Scheduled_Emails::category_enabled( $scheduled_type ) ) {
			return [
				'slug'   => self::PILL_DISABLED,
				'label'  => self::pill_label( self::PILL_DISABLED ),
				'detail' => __( 'Turned off in email settings.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( $class_id > 0 && ! Scheduled_Emails::class_category_enabled( $class_id, $scheduled_type ) ) {
			return [
				'slug'   => self::PILL_DISABLED,
				'label'  => self::pill_label( self::PILL_DISABLED ),
				'detail' => __( 'Turned off for this class.', 'class-bookings-with-stripe-pro' ),
			];
		}

		$row = self::get_scheduled_row( $booking_id, $scheduled_type );
		if ( ! $row ) {
			if ( ! Scheduled_Emails::get_global_rule( $scheduled_type ) ) {
				return [
					'slug'   => self::PILL_DISABLED,
					'label'  => self::pill_label( self::PILL_DISABLED ),
					'detail' => __( 'Not configured in email settings.', 'class-bookings-with-stripe-pro' ),
				];
			}

			return [
				'slug'   => self::PILL_NOT_SENT,
				'label'  => self::pill_label( self::PILL_NOT_SENT ),
				'detail' => __( 'Not queued for this booking.', 'class-bookings-with-stripe-pro' ),
			];
		}

		$status      = (string) ( $row['status'] ?? '' );
		$skip_reason = (string) ( $row['skip_reason'] ?? '' );

		if ( Scheduled_Emails::STATUS_SENT === $status ) {
			$parts = [];
			if ( ! empty( $row['sent_at'] ) ) {
				$parts[] = self::format_datetime_gmt( (string) $row['sent_at'] );
			}
			if ( ! empty( $row['customer_email'] ) ) {
				$parts[] = sprintf(
					/* translators: %s: email address */
					__( 'to %s', 'class-bookings-with-stripe-pro' ),
					(string) $row['customer_email']
				);
			}
			if ( Emails::is_local_test_mode() ) {
				$test_to = Emails::get_test_recipient();
				if ( $test_to ) {
					$parts[] = sprintf(
						/* translators: 1: test recipient, 2: intended customer email */
						__( 'Delivered to test recipient (%1$s); intended: %2$s', 'class-bookings-with-stripe-pro' ),
						$test_to,
						(string) ( $row['customer_email'] ?? '' )
					);
				}
			}

			return [
				'slug'   => self::PILL_SENT,
				'label'  => self::pill_label( self::PILL_SENT ),
				'detail' => implode( ' · ', array_filter( $parts ) ),
			];
		}

		if ( Scheduled_Emails::STATUS_FAILED === $status ) {
			$error = trim( (string) ( $row['last_error'] ?? '' ) );

			return [
				'slug'   => self::PILL_ERROR,
				'label'  => self::pill_label( self::PILL_ERROR ),
				'detail' => '' !== $error ? $error : __( 'WordPress could not send the email.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( Scheduled_Emails::STATUS_CANCELLED === $status ) {
			return [
				'slug'   => self::PILL_CANCELLED,
				'label'  => self::pill_label( self::PILL_CANCELLED ),
				'detail' => __( 'Scheduled send was cancelled.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( Scheduled_Emails::STATUS_SKIPPED === $status ) {
			if ( Scheduled_Emails::SKIP_LATE === $skip_reason ) {
				return [
					'slug'   => self::PILL_PAST_DATE,
					'label'  => self::pill_label( self::PILL_PAST_DATE ),
					'detail' => __( 'Booking was made too late for the scheduled send time.', 'class-bookings-with-stripe-pro' ),
				];
			}

			if ( Scheduled_Emails::SKIP_DEDUP === $skip_reason ) {
				return [
					'slug'   => self::PILL_NOT_SENT,
					'label'  => self::pill_label( self::PILL_NOT_SENT ),
					'detail' => __( 'Already sent for another booking in this class.', 'class-bookings-with-stripe-pro' ),
				];
			}

			return [
				'slug'   => self::PILL_NOT_SENT,
				'label'  => self::pill_label( self::PILL_NOT_SENT ),
				'detail' => __( 'Send was skipped.', 'class-bookings-with-stripe-pro' ),
			];
		}

		if ( Scheduled_Emails::STATUS_PENDING === $status ) {
			$send_at = (string) ( $row['send_at'] ?? '' );
			$detail  = '';
			if ( '' !== $send_at ) {
				$detail = sprintf(
					/* translators: %s: formatted date and time */
					__( 'Scheduled for %s', 'class-bookings-with-stripe-pro' ),
					Scheduled_Emails::format_send_at_for_display( $send_at )
				);
			}

			return [
				'slug'   => self::PILL_NOT_SENT,
				'label'  => self::pill_label( self::PILL_NOT_SENT ),
				'detail' => $detail,
			];
		}

		return [
			'slug'   => self::PILL_NOT_SENT,
			'label'  => self::pill_label( self::PILL_NOT_SENT ),
			'detail' => '',
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function get_scheduled_row( int $booking_id, string $rule_type ): ?array {
		foreach ( Scheduled_Emails::get_rows_for_booking( $booking_id ) as $row ) {
			if ( $rule_type === (string) ( $row['rule_type'] ?? '' ) ) {
				return $row;
			}
		}

		return null;
	}

	private static function pill_label( string $slug ): string {
		$labels = [
			self::PILL_SENT            => __( 'Sent', 'class-bookings-with-stripe-pro' ),
			self::PILL_ERROR            => __( 'Error', 'class-bookings-with-stripe-pro' ),
			self::PILL_NOT_SENT         => __( 'Not sent', 'class-bookings-with-stripe-pro' ),
			self::PILL_PAST_DATE        => __( 'Not sent (past date)', 'class-bookings-with-stripe-pro' ),
			self::PILL_DISABLED         => __( 'Disabled', 'class-bookings-with-stripe-pro' ),
			self::PILL_WAITING_PAYMENT  => __( 'Waiting for payment', 'class-bookings-with-stripe-pro' ),
			self::PILL_CANCELLED        => __( 'Cancelled', 'class-bookings-with-stripe-pro' ),
			self::PILL_NOT_RECORDED     => __( 'Not recorded', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $slug ] ?? ucfirst( str_replace( '_', ' ', $slug ) );
	}

	private static function format_datetime( string $mysql_local ): string {
		if ( '' === $mysql_local ) {
			return '';
		}
		try {
			$dt = new \DateTimeImmutable( $mysql_local, wp_timezone() );
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dt->getTimestamp() );
		} catch ( \Exception $e ) {
			return $mysql_local;
		}
	}

	private static function format_datetime_gmt( string $mysql_gmt ): string {
		if ( '' === $mysql_gmt ) {
			return '';
		}
		try {
			$dt = new \DateTimeImmutable( $mysql_gmt, new \DateTimeZone( 'UTC' ) );
			$dt = $dt->setTimezone( wp_timezone() );
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dt->getTimestamp() );
		} catch ( \Exception $e ) {
			return $mysql_gmt;
		}
	}
}
