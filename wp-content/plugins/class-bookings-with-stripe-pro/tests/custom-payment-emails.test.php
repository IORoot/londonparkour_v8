<?php
/**
 * /pay/ custom payments use the same email settings as other Clasbpro products.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/custom-payment-emails.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Bookings;
use IOROOT_STRIPE_BOOKINGS_PRO\Constants;
use IOROOT_STRIPE_BOOKINGS_PRO\Custom_Payments;
use IOROOT_STRIPE_BOOKINGS_PRO\Emails;
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

$acf = (string) file_get_contents( CLASBOWPRO_DIR . 'includes/class-acf-fields.php' );
$js  = (string) file_get_contents( CLASBOWPRO_DIR . 'assets/cbfs-booking-admin-settings.js' );

$assert( false !== strpos( $acf, 'customer_custom_email_subject' ), 'settings include a customer pay email subject' );
$assert( false !== strpos( $acf, 'admin_custom_email_subject' ), 'settings include an admin pay email subject' );
$assert( false !== strpos( $acf, 'customer_custom_email_body_html' ), 'customer pay email has an HTML body field' );
$assert( false !== strpos( $acf, 'enable_custom_followup_emails' ), 'settings include a pay thank-you follow-up switch' );
$assert( false !== strpos( $js, 'field_clasbpro_email_subtab_customer_custom' ), 'email subtabs map the customer pay section' );
$assert( false !== strpos( $js, 'field_clasbpro_email_subtab_admin_custom' ), 'email subtabs map the admin pay section' );

$option_keys = [
	'customer_custom_email_subject',
	'customer_custom_email_body',
	'customer_custom_email_body_editor_mode',
	'admin_custom_email_subject',
	'admin_custom_email_body',
	'admin_custom_email_body_editor_mode',
	'admin_email',
	'enable_custom_followup_emails',
	'custom_followup_offset_amount',
	'custom_followup_offset_unit',
	'custom_followup_email_subject',
	'custom_followup_email_body',
	'custom_followup_email_rule_uuid',
];
$saved = [];
foreach ( $option_keys as $key ) {
	$saved[ $key ] = function_exists( 'get_field' ) ? get_field( $key, Constants::OPTIONS_POST_ID ) : null;
}

$mail_subjects = [];
$mail_bodies   = [];
$mail_filter   = static function ( $null, $atts ) use ( &$mail_subjects, &$mail_bodies ) {
	$mail_subjects[] = (string) ( $atts['subject'] ?? '' );
	$mail_bodies[]   = (string) ( $atts['message'] ?? '' );
	return true;
};
add_filter( 'pre_wp_mail', $mail_filter, 10, 2 );

$booking_id = 0;

try {
	if ( ! function_exists( 'update_field' ) ) {
		$assert( false, 'ACF update_field is available' );
		throw new RuntimeException( 'acf missing' );
	}

	update_field( 'customer_custom_email_subject', 'Pay received {amount_total}', Constants::OPTIONS_POST_ID );
	update_field( 'customer_custom_email_body', 'Thanks {customer_name} for {amount_total}.', Constants::OPTIONS_POST_ID );
	update_field( 'customer_custom_email_body_editor_mode', 'visual', Constants::OPTIONS_POST_ID );
	update_field( 'admin_custom_email_subject', 'Pay in {amount_total} from {customer_name}', Constants::OPTIONS_POST_ID );
	update_field( 'admin_custom_email_body', '{customer_email} paid {amount_total}.', Constants::OPTIONS_POST_ID );
	update_field( 'admin_custom_email_body_editor_mode', 'visual', Constants::OPTIONS_POST_ID );
	update_field( 'admin_email', 'admin-pay-test@example.com', Constants::OPTIONS_POST_ID );
	update_field( 'enable_custom_followup_emails', 1, Constants::OPTIONS_POST_ID );
	update_field( 'custom_followup_offset_amount', 2, Constants::OPTIONS_POST_ID );
	update_field( 'custom_followup_offset_unit', 'hours', Constants::OPTIONS_POST_ID );
	update_field( 'custom_followup_email_subject', 'Thank you {customer_name}', Constants::OPTIONS_POST_ID );
	update_field( 'custom_followup_email_body', '<p>Thanks again {customer_name}.</p>', Constants::OPTIONS_POST_ID );
	update_field( 'custom_followup_email_rule_uuid', wp_generate_uuid4(), Constants::OPTIONS_POST_ID );

	$booking_id = Custom_Payments::create_pending( 'Pay Tester', 'pay.tester@example.com', 4500 );
	$assert( ! is_wp_error( $booking_id ) && (int) $booking_id > 0, 'can create a pending custom payment' );
	$booking_id = (int) $booking_id;
	Bookings::set_status( $booking_id, Bookings::STATUS_PAID );

	$tags = Emails::build_merge_tags( $booking_id );
	$assert( is_array( $tags ), 'custom payments have merge tags without a class' );
	$assert( isset( $tags['{amount_total}'] ) && false !== strpos( (string) $tags['{amount_total}'], '45' ), 'custom payment merge tags include the amount' );

	Emails::send_for_custom_payment( $booking_id );
	$joined_subjects = implode( "\n", $mail_subjects );
	$joined_bodies   = implode( "\n", $mail_bodies );
	$assert( false !== strpos( $joined_subjects, 'Pay received' ), 'customer pay email uses the settings subject' );
	$assert( false !== strpos( $joined_bodies, 'Pay Tester' ), 'customer pay email substitutes merge tags' );
	$assert( false !== strpos( $joined_subjects, 'Pay in' ), 'admin pay email uses the settings subject' );

	Scheduled_Emails::queue_for_custom_payment( $booking_id );
	$rows = array_values(
		array_filter(
			Scheduled_Emails::get_rows_for_booking( $booking_id ),
			static function ( array $row ): bool {
				return Scheduled_Emails::TYPE_CUSTOM_FOLLOWUP === ( $row['rule_type'] ?? '' );
			}
		)
	);
	$assert( 1 === count( $rows ), 'paid custom payment queues one thank-you follow-up' );
	$assert( Scheduled_Emails::STATUS_PENDING === ( $rows[0]['status'] ?? '' ), 'thank-you follow-up is pending' );
	$assert( ! empty( $rows[0]['send_at'] ), 'thank-you follow-up has a send time after payment' );
} finally {
	remove_filter( 'pre_wp_mail', $mail_filter, 10 );
	if ( $booking_id > 0 ) {
		Scheduled_Emails::cancel_for_booking( $booking_id );
		wp_delete_post( $booking_id, true );
	}
	foreach ( $saved as $key => $value ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( $key, $value, Constants::OPTIONS_POST_ID );
		}
	}
}

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'custom payment emails' );
