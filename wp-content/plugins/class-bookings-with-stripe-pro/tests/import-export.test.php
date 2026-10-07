<?php
/**
 * Clasbpro Tools import/export snapshot.
 *
 * Run from the repo:
 *   themes/londonparkour_v8/bin/wp eval-file wp-content/plugins/class-bookings-with-stripe-pro/tests/import-export.test.php
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

use IOROOT_STRIPE_BOOKINGS_PRO\Bookings;
use IOROOT_STRIPE_BOOKINGS_PRO\Constants;
use IOROOT_STRIPE_BOOKINGS_PRO\CPT;
use IOROOT_STRIPE_BOOKINGS_PRO\Secrets;
use IOROOT_STRIPE_BOOKINGS_PRO\Snapshot;
use IOROOT_STRIPE_BOOKINGS_PRO\Tools;

$fails = 0;

$assert = static function ( bool $ok, string $message ) use ( &$fails ): void {
	if ( $ok ) {
		\WP_CLI::log( 'OK  ' . $message );
		return;
	}
	++$fails;
	\WP_CLI::warning( 'FAIL  ' . $message );
};

$assert( class_exists( Snapshot::class ), 'Snapshot class is loadable' );
$assert( class_exists( Tools::class ), 'Tools class is loadable' );
$assert( 'clasbpro-tools' === Tools::MENU_SLUG, 'Tools uses the clasbpro-tools menu slug' );

$rewritten = Snapshot::rewrite_site_urls(
	'See https://old.example.test/pay and https://www.old.example.test/x',
	'https://old.example.test',
	'https://new.example.test'
);
$assert(
	false !== strpos( $rewritten, 'https://new.example.test/pay' )
		&& false !== strpos( $rewritten, 'https://new.example.test/x' )
		&& false === strpos( $rewritten, 'old.example.test' ),
	'rewrite_site_urls replaces the source home URL and www variant'
);
$assert(
	'https://pay.stripe.com/receipts/abc' === Snapshot::rewrite_site_urls(
		'https://pay.stripe.com/receipts/abc',
		'https://old.example.test',
		'https://new.example.test'
	),
	'rewrite_site_urls does not touch Stripe receipt URLs'
);

$marker          = 'clasbpro-ie-' . wp_generate_password( 8, false, false );
$class_title     = $marker . ' class';
$booking_title   = $marker . ' booking';
$page_title      = $marker . ' page';
$email_subject   = $marker . ' subject {class_name}';
$zip             = '';
$backup_zip      = '';
$class_id        = 0;
$booking_id      = 0;
$page_id         = 0;
$saved_subject   = function_exists( 'get_field' ) ? get_field( 'customer_email_subject', Constants::OPTIONS_POST_ID ) : null;
$secret_before   = (string) get_option( Constants::OPTIONS_POST_ID . '_stripe_secret_key_test', '' );

try {
	if ( ! function_exists( 'update_field' ) ) {
		$assert( false, 'ACF update_field is available' );
		throw new RuntimeException( 'acf missing' );
	}

	$class_id = wp_insert_post(
		[
			'post_type'   => CPT::CLASS_PT,
			'post_status' => 'draft',
			'post_title'  => $class_title,
		]
	);
	$assert( $class_id > 0, 'can insert a draft class' );
	update_field( 'class_active', 1, $class_id );

	$booking_id = wp_insert_post(
		[
			'post_type'   => CPT::BOOKING_PT,
			'post_status' => 'publish',
			'post_title'  => $booking_title,
		]
	);
	$assert( $booking_id > 0, 'can insert a booking' );
	update_post_meta( $booking_id, '_clasbpro_class_id', $class_id );
	update_post_meta( $booking_id, '_clasbpro_customer_name', 'IE Tester' );
	update_post_meta( $booking_id, '_clasbpro_customer_email', 'ie.tester@example.com' );
	Bookings::set_status( $booking_id, Bookings::STATUS_PAID );

	$page_id = wp_insert_post(
		[
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $page_title,
			'post_content' => '[clasbpro_booking class_id="' . $class_id . '"]',
		]
	);
	$assert( $page_id > 0, 'can insert a page with a booking shortcode' );

	update_field( 'customer_email_subject', $email_subject, Constants::OPTIONS_POST_ID );

	$zip = Snapshot::export_to_temp_zip();
	$assert( is_string( $zip ) && is_readable( $zip ), 'export writes a zip file' );

	$inspect = Snapshot::inspect( $zip );
	$assert( is_array( $inspect ), 'inspect returns an array' );
	$assert( ! empty( $inspect['counts']['classes'] ), 'inspect counts at least one class' );
	$assert( ! empty( $inspect['counts']['bookings'] ), 'inspect counts at least one booking' );
	$assert( empty( $inspect['includes_secrets'] ), 'inspect reports secrets are excluded' );
	$assert( isset( $inspect['url_rewrite']['from'], $inspect['url_rewrite']['to'] ), 'inspect includes the URL rewrite pair' );

	$zip_file = new ZipArchive();
	$assert( true === $zip_file->open( $zip ), 'export zip opens' );
	$raw = $zip_file->getFromName( 'snapshot.json' );
	$zip_file->close();
	$data = json_decode( (string) $raw, true );
	$assert( is_array( $data ), 'snapshot.json is valid JSON' );
	$option_blob = wp_json_encode( $data['options'] ?? [] );
	foreach ( Secrets::field_names() as $secret_field ) {
		$assert(
			false === strpos( (string) $option_blob, $secret_field )
				|| false === strpos( (string) $option_blob, 'sk_test' ),
			'export options omit secret field ' . $secret_field
		);
	}

	update_field( 'customer_email_subject', 'CHANGED AFTER EXPORT', Constants::OPTIONS_POST_ID );

	$result = Snapshot::import_from_zip(
		$zip,
		[
			'author_id'    => get_current_user_id(),
			'force_backup' => true,
			'confirm'      => true,
		]
	);
	$assert( ! is_wp_error( $result ), 'import succeeds' );
	$assert( is_array( $result ) && ! empty( $result['backup'] ) && is_readable( (string) $result['backup'] ), 'import writes a pre-import backup zip' );
	$backup_zip = is_array( $result ) ? (string) ( $result['backup'] ?? '' ) : '';

	$imported_class_posts = get_posts(
		[
			'post_type'      => CPT::CLASS_PT,
			'post_status'    => 'any',
			'title'          => $class_title,
			'posts_per_page' => 1,
		]
	);
	$imported_class = $imported_class_posts[0] ?? null;
	$assert( $imported_class instanceof WP_Post, 'imported class exists by title' );
	$assert(
		$imported_class instanceof WP_Post && (int) $imported_class->ID !== (int) $class_id,
		'imported class has a new post ID'
	);
	$assert(
		$imported_class instanceof WP_Post && 'draft' === $imported_class->post_status,
		'imported class keeps draft status'
	);

	$imported_booking_posts = get_posts(
		[
			'post_type'      => CPT::BOOKING_PT,
			'post_status'    => 'any',
			'title'          => $booking_title,
			'posts_per_page' => 1,
		]
	);
	$imported_booking = $imported_booking_posts[0] ?? null;
	$assert( $imported_booking instanceof WP_Post, 'imported booking exists by title' );
	if ( $imported_class instanceof WP_Post && $imported_booking instanceof WP_Post ) {
		$mapped_class = (int) get_post_meta( (int) $imported_booking->ID, '_clasbpro_class_id', true );
		$assert( $mapped_class === (int) $imported_class->ID, 'imported booking class_id is remapped' );
		$assert( $mapped_class !== (int) $class_id, 'imported booking does not keep the source class ID' );
	}

	$restored_subject = (string) get_field( 'customer_email_subject', Constants::OPTIONS_POST_ID );
	$assert( $email_subject === $restored_subject, 'import restores email settings from the snapshot' );

	$secret_after = (string) get_option( Constants::OPTIONS_POST_ID . '_stripe_secret_key_test', '' );
	$assert( $secret_before === $secret_after, 'import does not overwrite destination Stripe secrets' );

	$imported_page_posts = get_posts(
		[
			'post_type'      => 'page',
			'post_status'    => 'any',
			'title'          => $page_title,
			'posts_per_page' => 1,
		]
	);
	$imported_page = $imported_page_posts[0] ?? null;
	$assert( $imported_page instanceof WP_Post, 'shortcode page still exists' );
	if ( $imported_class instanceof WP_Post && $imported_page instanceof WP_Post ) {
		$assert(
			false !== strpos( (string) $imported_page->post_content, 'class_id="' . (int) $imported_class->ID . '"' ),
			'existing shortcode class_id is rewritten to the new ID'
		);
		$assert(
			false === strpos( (string) $imported_page->post_content, 'class_id="' . (int) $class_id . '"' ),
			'existing shortcode no longer uses the source class ID'
		);
	}
} finally {
	if ( $zip && is_readable( $zip ) ) {
		wp_delete_file( $zip );
	}
	if ( $backup_zip && is_readable( $backup_zip ) ) {
		wp_delete_file( $backup_zip );
	}
	foreach ( [ CPT::CLASS_PT, CPT::BOOKING_PT, 'page' ] as $pt ) {
		$found = get_posts(
			[
				'post_type'              => $pt,
				'post_status'            => 'any',
				'posts_per_page'         => 20,
				's'                      => $marker,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);
		foreach ( $found as $id ) {
			wp_delete_post( (int) $id, true );
		}
	}
	if ( function_exists( 'update_field' ) ) {
		update_field( 'customer_email_subject', $saved_subject, Constants::OPTIONS_POST_ID );
	}
}

if ( $fails > 0 ) {
	\WP_CLI::error( $fails . ' assertion(s) failed' );
}
\WP_CLI::success( 'import export' );
