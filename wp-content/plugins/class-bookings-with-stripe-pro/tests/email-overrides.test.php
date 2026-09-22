<?php
/**
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/email-overrides.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Class_Email_Overrides;
use IOROOT_STRIPE_BOOKINGS_PRO\CPT;
use IOROOT_STRIPE_BOOKINGS_PRO\Email_Body_Editor;
use IOROOT_STRIPE_BOOKINGS_PRO\Slot_Rules;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$rule = Slot_Rules::sanitize_rule(
	[
		'type'              => 'one_off',
		'specific_date'     => '2026-10-01',
		'start_time'        => '10:00',
		'duration_minutes'  => 60,
		'admin_email'       => 'slot-coach@example.com',
	]
);
$assert( is_array( $rule ), 'one-off slot rule sanitizes' );
$assert(
	is_array( $rule ) && 'slot-coach@example.com' === ( $rule['admin_email'] ?? null ),
	'sanitize_rule stores a valid slot admin email override'
);

$invalid = Slot_Rules::sanitize_rule(
	[
		'type'             => 'one_off',
		'specific_date'    => '2026-10-01',
		'start_time'       => '10:00',
		'duration_minutes' => 60,
		'admin_email'      => 'not-an-email',
	]
);
$assert(
	is_array( $invalid ) && '' === ( $invalid['admin_email'] ?? 'missing' ),
	'sanitize_rule drops an invalid slot admin email'
);

$assert(
	'slot@example.com' === Class_Email_Overrides::first_valid_email(
		'slot@example.com',
		'class@example.com',
		'global@example.com',
		'site@example.com'
	),
	'slot email wins recipient priority'
);
$assert(
	'class@example.com' === Class_Email_Overrides::first_valid_email(
		'',
		'class@example.com',
		'global@example.com',
		'site@example.com'
	),
	'class email is used when slot is blank'
);
$assert(
	'global@example.com' === Class_Email_Overrides::first_valid_email(
		'not-valid',
		'',
		'global@example.com',
		'site@example.com'
	),
	'invalid slot email falls through to global'
);

$class_id = wp_insert_post(
	[
		'post_type'   => CPT::CLASS_PT,
		'post_status' => 'draft',
		'post_title'  => 'Email override test ' . wp_generate_uuid4(),
	],
	true
);
$assert( ! is_wp_error( $class_id ) && $class_id > 0, 'test class post created' );

if ( ! is_wp_error( $class_id ) && $class_id > 0 && function_exists( 'update_field' ) ) {
	$marker     = '<!--CLASBPRO_RAW_COPY_' . wp_generate_password( 8, false, false ) . '-->';
	$html       = '<!DOCTYPE html><html><body>' . $marker . '</body></html>';
	$prev_mode = get_field( 'admin_email_body_editor_mode', 'clasbpro_options' );
	$prev_html = get_field( 'admin_email_body_html', 'clasbpro_options' );

	$write_option_html = static function ( string $value ): void {
		Email_Body_Editor::stash_html( 'field_clasbpro_admin_email_body_html', $value );
		update_field( 'admin_email_body_html', $value, 'clasbpro_options' );
	};

	try {
		update_field( 'admin_email_body_editor_mode', Email_Body_Editor::MODE_RAW, 'clasbpro_options' );
		$write_option_html( $html );

		Slot_Rules::save_rules(
			(int) $class_id,
			[
				[
					'type'             => 'one_off',
					'specific_date'    => '2026-10-01',
					'start_time'       => '10:00',
					'duration_minutes' => 60,
					'admin_email'      => 'slot-coach@example.com',
				],
			]
		);
		$saved_rules = Slot_Rules::get_rules( (int) $class_id );
		$slot_id     = (string) ( $saved_rules[0]['id'] ?? '' );
		$assert(
			'' !== $slot_id
			&& 'slot-coach@example.com' === Class_Email_Overrides::resolve_admin_recipient( (int) $class_id, $slot_id ),
			'new-booking admin recipient uses the slot override first'
		);
		$without_slot = Class_Email_Overrides::resolve_admin_recipient( (int) $class_id );
		$assert(
			'slot-coach@example.com' !== $without_slot,
			'class/global admin recipient ignores the slot override when no slot is passed'
		);

		update_field( Class_Email_Overrides::mode_field_name( 'admin' ), Class_Email_Overrides::MODE_CUSTOM, $class_id );
		update_field( 'class_email_admin_subject', 'Custom subject for this class', $class_id );
		delete_post_meta( $class_id, '_clasbpro_class_email_init_admin' );

		Class_Email_Overrides::maybe_prefill_after_save( $class_id );

		$copied_mode = (string) get_field( 'class_email_admin_body_editor_mode', $class_id );
		$copied_html = (string) get_field( 'class_email_admin_body_html', $class_id );
		$subject     = (string) get_field( 'class_email_admin_subject', $class_id );

		$assert(
			Email_Body_Editor::MODE_RAW === $copied_mode,
			'first custom save copies global Raw HTML editor mode'
		);
		$assert(
			str_contains( $copied_html, $marker ),
			'first custom save copies global Raw HTML even when a custom subject is set'
		);
		$assert(
			'Custom subject for this class' === $subject,
			'first custom save keeps the custom subject'
		);
	} finally {
		update_field( 'admin_email_body_editor_mode', $prev_mode, 'clasbpro_options' );
		$write_option_html( (string) $prev_html );
		wp_delete_post( (int) $class_id, true );
	}
}

if ( $fails > 0 ) {
	\WP_CLI::error( sprintf( '%d assertion(s) failed.', $fails ) );
}

\WP_CLI::success( 'email override tests passed' );
