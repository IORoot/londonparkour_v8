<?php
/**
 * Media library helpers — sideload once (by basename + file hash).
 *
 * WordPress unique-names a colliding upload (`file-1.jpg`). Combined with
 * YouTube posters that always arrive as `{videoId}.jpg`, that produced a
 * dozen byte-identical attachments. Every import path must go through
 * lp_sideload_image_once() instead of media_handle_sideload() directly.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

const LP_FILE_MD5_META = '_lp_file_md5';

/**
 * Attachment ID that already owns this exact file, or 0.
 *
 * @param string $abs_path Readable file.
 */
function lp_attachment_id_for_file( string $abs_path ): int {
	if ( '' === $abs_path || ! is_readable( $abs_path ) ) {
		return 0;
	}

	$basename = wp_basename( $abs_path );
	$by_name  = lp_attachment_id_for_basename( $basename );
	if ( $by_name ) {
		$existing = get_attached_file( $by_name );
		if ( $existing && is_readable( $existing ) && lp_files_are_identical( $abs_path, $existing ) ) {
			lp_remember_file_md5( $by_name, $existing );
			return $by_name;
		}
	}

	$md5 = md5_file( $abs_path );
	if ( ! is_string( $md5 ) || 32 !== strlen( $md5 ) ) {
		return 0;
	}

	$by_hash = lp_attachment_id_for_md5( $md5 );
	if ( $by_hash ) {
		return $by_hash;
	}

	return 0;
}

/**
 * First attachment whose `_wp_attached_file` basename matches.
 *
 * @param string $basename Filename only.
 */
function lp_attachment_id_for_basename( string $basename ): int {
	global $wpdb;

	$names = array_unique( array( $basename, sanitize_file_name( $basename ) ) );
	foreach ( $names as $name ) {
		if ( '' === $name ) {
			continue;
		}
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = '_wp_attached_file'
				   AND (meta_value = %s OR meta_value LIKE %s)
				 ORDER BY post_id ASC LIMIT 1",
				$name,
				'%/' . $wpdb->esc_like( $name )
			)
		);
		if ( $id ) {
			return (int) $id;
		}
	}

	return 0;
}

/**
 * Attachment ID stored under `_lp_file_md5`, or 0.
 *
 * @param string $md5 32-char hex.
 */
function lp_attachment_id_for_md5( string $md5 ): int {
	global $wpdb;

	$id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			 WHERE meta_key = %s AND meta_value = %s
			 ORDER BY post_id ASC LIMIT 1",
			LP_FILE_MD5_META,
			$md5
		)
	);

	return $id ? (int) $id : 0;
}

/**
 * Persist the original-file hash so later imports can reuse this row.
 *
 * @param int         $attachment_id Attachment post ID.
 * @param string|null $abs_path      Defaults to the attached file.
 */
function lp_remember_file_md5( int $attachment_id, ?string $abs_path = null ): void {
	if ( $attachment_id <= 0 ) {
		return;
	}
	$path = $abs_path ? $abs_path : get_attached_file( $attachment_id );
	if ( ! $path || ! is_readable( $path ) ) {
		return;
	}
	$hash = md5_file( $path );
	if ( is_string( $hash ) && 32 === strlen( $hash ) ) {
		update_post_meta( $attachment_id, LP_FILE_MD5_META, $hash );
	}
}

/**
 * Same bytes, compared by size then md5.
 *
 * @param string $a Path.
 * @param string $b Path.
 */
function lp_files_are_identical( string $a, string $b ): bool {
	$size_a = @filesize( $a ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$size_b = @filesize( $b ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( false === $size_a || false === $size_b || $size_a !== $size_b ) {
		return false;
	}
	$hash_a = md5_file( $a );
	$hash_b = md5_file( $b );
	return is_string( $hash_a ) && $hash_a === $hash_b;
}

/**
 * Sideload an image only when this exact file is not already in the library.
 *
 * @param string               $abs_path Readable source. Copied; the original stays.
 * @param array<string, mixed> $args     Optional post_title / post_excerpt / alt.
 * @return int|\WP_Error Attachment ID.
 */
function lp_sideload_image_once( string $abs_path, array $args = array() ) {
	$existing = lp_attachment_id_for_file( $abs_path );
	if ( $existing ) {
		return $existing;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$basename = wp_basename( $abs_path );
	$tmp      = wp_tempnam( $basename );
	if ( ! $tmp || ! copy( $abs_path, $tmp ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		return new WP_Error( 'lp_sideload_copy', 'Could not stage ' . $basename );
	}

	$post_data = array();
	if ( ! empty( $args['post_title'] ) ) {
		$post_data['post_title'] = (string) $args['post_title'];
	}

	$id = media_handle_sideload(
		array(
			'name'     => $basename,
			'tmp_name' => $tmp,
		),
		0,
		null,
		$post_data
	);

	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return $id;
	}

	if ( ! empty( $args['alt'] ) ) {
		update_post_meta( (int) $id, '_wp_attachment_image_alt', (string) $args['alt'] );
	}

	lp_remember_file_md5( (int) $id );
	return (int) $id;
}

/**
 * Store `_lp_file_md5` whenever WordPress builds attachment metadata.
 *
 * @param array $metadata      Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function lp_remember_file_md5_on_metadata( $metadata, $attachment_id ) {
	lp_remember_file_md5( (int) $attachment_id );
	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'lp_remember_file_md5_on_metadata', 20, 2 );
