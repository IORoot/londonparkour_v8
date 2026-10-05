<?php
/**
 * Last-minute reminder behaviour is gated by skip_late_reminder_emails.
 * Reminders must never send after the class has started.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/scheduled-emails-late-booking.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Bookings;
use IOROOT_STRIPE_BOOKINGS_PRO\Constants;
use IOROOT_STRIPE_BOOKINGS_PRO\CPT;
use IOROOT_STRIPE_BOOKINGS_PRO\Helpers;
use IOROOT_STRIPE_BOOKINGS_PRO\Scheduled_Emails;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$option_keys = [
	'enable_reminder_emails',
	'enable_post_class_emails',
	'reminder_offset_amount',
	'reminder_offset_unit',
	'reminder_email_subject',
	'reminder_email_body',
	'reminder_email_rule_uuid',
	'skip_late_reminder_emails',
];

$saved_options = [];
foreach ( $option_keys as $key ) {
	$saved_options[ $key ] = function_exists( 'get_field' ) ? get_field( $key, Constants::OPTIONS_POST_ID ) : null;
}

$created_ids = [];
$mail_sends  = 0;
$mail_tos    = [];
$mail_filter = static function ( $null, $atts ) use ( &$mail_sends, &$mail_tos ) {
	++$mail_sends;
	$mail_tos[] = (string) ( $atts['to'] ?? '' );
	return true;
};

add_filter( 'pre_wp_mail', $mail_filter, 10, 2 );
delete_transient( 'clasbpro_processing_queue' );
delete_transient( 'clasbpro_scheduled_email_tick' );

try {
	if ( ! function_exists( 'update_field' ) ) {
		$assert( false, 'ACF update_field is available' );
		throw new RuntimeException( 'acf missing' );
	}

	update_field( 'enable_reminder_emails', 1, Constants::OPTIONS_POST_ID );
	update_field( 'enable_post_class_emails', 0, Constants::OPTIONS_POST_ID );
	update_field( 'reminder_offset_amount', 3, Constants::OPTIONS_POST_ID );
	update_field( 'reminder_offset_unit', 'hours', Constants::OPTIONS_POST_ID );
	update_field( 'reminder_email_subject', 'Reminder: {class_name}', Constants::OPTIONS_POST_ID );
	update_field( 'reminder_email_body', '<p>See you at {class_time}</p>', Constants::OPTIONS_POST_ID );
	update_field( 'reminder_email_rule_uuid', wp_generate_uuid4(), Constants::OPTIONS_POST_ID );
	update_field( 'skip_late_reminder_emails', 1, Constants::OPTIONS_POST_ID );

	$class_id = wp_insert_post(
		[
			'post_type'   => CPT::CLASS_PT,
			'post_status' => 'publish',
			'post_title'  => 'Late booking reminder test class',
		]
	);
	$assert( $class_id > 0, 'can insert a class' );
	$created_ids[] = $class_id;
	update_field( 'start_time', '18:00', $class_id );
	update_field( 'duration_minutes', 60, $class_id );
	update_field( 'class_active', 1, $class_id );

	$now   = Helpers::now();
	$start = $now->modify( '+1 hour' );
	$date  = $start->format( 'Y-m-d' );
	$time  = $start->format( 'H:i' );

	$booking_id = wp_insert_post(
		[
			'post_type'   => CPT::BOOKING_PT,
			'post_status' => 'publish',
			'post_title'  => 'Late booking reminder test',
		]
	);
	$assert( $booking_id > 0, 'can insert a booking one hour before class' );
	$created_ids[] = $booking_id;
	update_post_meta( $booking_id, '_clasbpro_class_id', $class_id );
	update_post_meta( $booking_id, '_clasbpro_class_date', $date );
	update_post_meta( $booking_id, '_clasbpro_customer_name', 'Late Booker' );
	update_post_meta( $booking_id, '_clasbpro_customer_email', 'late-booker@example.com' );
	update_post_meta(
		$booking_id,
		'_clasbpro_slot_snapshot',
		wp_json_encode(
			[
				'start_time'        => $time,
				'duration_minutes'  => 60,
				'location'          => 'Test gym',
			]
		)
	);
	Bookings::set_status( $booking_id, Bookings::STATUS_PAID );

	$reminder_rows = static function ( int $id ): array {
		return array_values(
			array_filter(
				Scheduled_Emails::get_rows_for_booking( $id ),
				static fn( array $row ): bool => Scheduled_Emails::TYPE_REMINDER === ( $row['rule_type'] ?? '' )
			)
		);
	};

	$mail_sends = 0;
	Scheduled_Emails::queue_for_booking( $booking_id );
	$rows = $reminder_rows( $booking_id );
	$assert( 1 === count( $rows ), 'late booking queues exactly one reminder row' );
	$assert(
		Scheduled_Emails::STATUS_SKIPPED === (string) ( $rows[0]['status'] ?? '' ),
		'default skip-late switch does not send a reminder booked inside the window'
	);
	$assert(
		Scheduled_Emails::SKIP_LATE === (string) ( $rows[0]['skip_reason'] ?? '' ),
		'skip-late reminder uses the late skip reason'
	);

	update_field( 'skip_late_reminder_emails', 0, Constants::OPTIONS_POST_ID );

	$send_booking_id = wp_insert_post(
		[
			'post_type'   => CPT::BOOKING_PT,
			'post_status' => 'publish',
			'post_title'  => 'Late booking reminder send-now test',
		]
	);
	$assert( $send_booking_id > 0, 'can insert a second late booking with skip-late off' );
	$created_ids[] = $send_booking_id;
	update_post_meta( $send_booking_id, '_clasbpro_class_id', $class_id );
	update_post_meta( $send_booking_id, '_clasbpro_class_date', $date );
	update_post_meta( $send_booking_id, '_clasbpro_customer_name', 'Late Booker Send' );
	update_post_meta( $send_booking_id, '_clasbpro_customer_email', 'late-booker-send@example.com' );
	update_post_meta(
		$send_booking_id,
		'_clasbpro_slot_snapshot',
		wp_json_encode(
			[
				'start_time'       => $time,
				'duration_minutes' => 60,
				'location'         => 'Test gym',
			]
		)
	);
	Bookings::set_status( $send_booking_id, Bookings::STATUS_PAID );

	Scheduled_Emails::queue_for_booking( $send_booking_id );
	$send_rows = $reminder_rows( $send_booking_id );
	$assert( 1 === count( $send_rows ), 'skip-late off queues exactly one reminder row' );

	$row    = $send_rows[0] ?? [];
	$status = (string) ( $row['status'] ?? '' );
	$assert(
		in_array( $status, [ Scheduled_Emails::STATUS_PENDING, Scheduled_Emails::STATUS_SENT ], true ),
		'with skip-late off, reminder is sent or pending when booked inside the window'
	);
	$assert( Scheduled_Emails::STATUS_SKIPPED !== $status, 'with skip-late off, reminder is not skipped while class is upcoming' );

	$start_utc = $start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	$send_at   = (string) ( $row['send_at'] ?? '' );
	$assert( '' !== $send_at && $send_at <= $start_utc, 'reminder send_at is not after class start' );
	$assert( '' !== $send_at && $send_at <= gmdate( 'Y-m-d H:i:s', time() + 60 ), 'reminder is due immediately, not left in the past for a later cron run' );

	if ( Scheduled_Emails::STATUS_SENT === $status ) {
		$sent_at = (string) ( $row['sent_at'] ?? '' );
		$assert( '' !== $sent_at && $sent_at < $start_utc, 'sent reminder went out before class start' );
	}

	$past_start = $now->modify( '-2 hours' );
	$past_booking_id = wp_insert_post(
		[
			'post_type'   => CPT::BOOKING_PT,
			'post_status' => 'publish',
			'post_title'  => 'Overdue reminder after class',
		]
	);
	$assert( $past_booking_id > 0, 'can insert a booking whose class has already ended' );
	$created_ids[] = $past_booking_id;
	update_post_meta( $past_booking_id, '_clasbpro_class_id', $class_id );
	update_post_meta( $past_booking_id, '_clasbpro_class_date', $past_start->format( 'Y-m-d' ) );
	update_post_meta( $past_booking_id, '_clasbpro_customer_name', 'After Class' );
	update_post_meta( $past_booking_id, '_clasbpro_customer_email', 'after-class@example.com' );
	update_post_meta(
		$past_booking_id,
		'_clasbpro_slot_snapshot',
		wp_json_encode(
			[
				'start_time'        => $past_start->format( 'H:i' ),
				'duration_minutes'  => 60,
				'location'          => 'Test gym',
			]
		)
	);
	Bookings::set_status( $past_booking_id, Bookings::STATUS_PAID );

	global $wpdb;
	$table = Scheduled_Emails::table_name();
	$wpdb->insert(
		$table,
		[
			'booking_id'     => $past_booking_id,
			'class_id'       => $class_id,
			'customer_email' => 'after-class@example.com',
			'rule_id'        => wp_generate_uuid4(),
			'rule_type'      => Scheduled_Emails::TYPE_REMINDER,
			'rule_label'     => 'Reminder',
			'send_at'        => gmdate( 'Y-m-d H:i:s', time() - ( 5 * HOUR_IN_SECONDS ) ),
			'status'         => Scheduled_Emails::STATUS_PENDING,
			'skip_reason'    => '',
			'subject_tpl'    => 'Reminder: {class_name}',
			'body_tpl'       => '<p>See you at {class_time}</p>',
			'body_html_mode' => 0,
			'admin_copy'     => 0,
			'created_at'     => gmdate( 'Y-m-d H:i:s' ),
		]
	);
	$overdue_id = (int) $wpdb->insert_id;
	$assert( $overdue_id > 0, 'can insert an overdue pending reminder from a delayed cron run' );

	$mail_sends = 0;
	$mail_tos   = [];
	delete_transient( 'clasbpro_processing_queue' );
	Scheduled_Emails::process_due_queue();

	$overdue_rows = Scheduled_Emails::get_rows_for_booking( $past_booking_id );
	$overdue      = $overdue_rows[0] ?? [];
	$assert(
		Scheduled_Emails::STATUS_SKIPPED === (string) ( $overdue['status'] ?? '' ),
		'a reminder that only became due after class started is skipped, not sent'
	);
	$after_class_mail = array_filter(
		$mail_tos,
		static fn( string $to ): bool => false !== strpos( $to, 'after-class@example.com' )
	);
	$assert( [] === $after_class_mail, 'no reminder mail is dispatched after the class has started' );
	$assert(
		Scheduled_Emails::SKIP_LATE === (string) ( $overdue['skip_reason'] ?? '' ),
		'after-class reminder skip reason is late'
	);
} finally {
	remove_filter( 'pre_wp_mail', $mail_filter, 10 );
	foreach ( $created_ids as $id ) {
		if ( $id > 0 ) {
			global $wpdb;
			$wpdb->delete( Scheduled_Emails::table_name(), [ 'booking_id' => $id ], [ '%d' ] );
			wp_delete_post( $id, true );
		}
	}
	foreach ( $saved_options as $key => $value ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( $key, $value, Constants::OPTIONS_POST_ID );
		}
	}
	delete_transient( 'clasbpro_processing_queue' );
	delete_transient( 'clasbpro_scheduled_email_tick' );
}

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'scheduled emails late booking' );
