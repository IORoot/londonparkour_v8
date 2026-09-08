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

/**
 * ACF image field → attachment ID. Editors return an int; some field configs return an array.
 *
 * @param mixed $value Field value.
 */
function lp_acf_attachment_id( $value ): int {
	if ( is_array( $value ) ) {
		return absint( $value['ID'] ?? $value['id'] ?? 0 );
	}

	return absint( $value );
}

/**
 * First-slide / media attachment from the opening Hero row, or null.
 *
 * Only the first Flexible Content row counts — a hero further down the page is
 * not the LCP element, and preloading it would steal bandwidth from whatever is.
 *
 * @return array{id:int,size:string,sizes:string}|null
 */
function lp_lcp_from_opening_hero(): ?array {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	$post_id = 0;
	if ( is_front_page() ) {
		$post_id = (int) get_option( 'page_on_front' );
	} elseif ( is_singular() ) {
		$post_id = (int) get_queried_object_id();
	}

	if ( $post_id < 1 ) {
		return null;
	}

	$sections = get_field( 'page_sections', $post_id );
	if ( ! is_array( $sections ) || ! isset( $sections[0] ) || ! is_array( $sections[0] ) ) {
		return null;
	}

	$row    = $sections[0];
	$layout = str_replace( '_', '-', (string) ( $row['acf_fc_layout'] ?? '' ) );
	if ( 'hero' !== $layout ) {
		return null;
	}

	$attachment_id = 0;
	$slides        = $row['media_slides'] ?? array();
	if ( is_array( $slides ) ) {
		foreach ( $slides as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}
			$attachment_id = lp_acf_attachment_id( $slide['image'] ?? 0 );
			if ( $attachment_id ) {
				break;
			}
		}
	}

	if ( ! $attachment_id ) {
		$attachment_id = lp_acf_attachment_id( $row['media'] ?? 0 );
	}

	return lp_lcp_attachment( $attachment_id, 'lp_wide_lg', '100vw' );
}

/**
 * @return array{id:int,size:string,sizes:string}|null
 */
function lp_lcp_attachment( int $attachment_id, string $size, string $sizes ): ?array {
	if ( $attachment_id < 1 ) {
		return null;
	}

	return array(
		'id'    => $attachment_id,
		'size'  => $size,
		'sizes' => $sizes,
	);
}

/**
 * The LCP photo for this request — same attachment, size and `sizes` that
 * media-photo will emit — or null when the opening band is not a photo.
 *
 * @return array{id:int,size:string,sizes:string}|null
 */
function lp_lcp_image(): ?array {
	$lcp = null;

	if ( ! is_admin() && ! is_feed() && ! is_embed() && ! wp_is_json_request() ) {
		$lcp = lp_lcp_from_opening_hero();

		if ( ! $lcp && is_page_template( 'templates/private-coaching.php' ) ) {
			$lcp = lp_lcp_attachment( (int) get_post_thumbnail_id(), 'lp_portrait_lg', '(min-width: 1024px) 50vw, 100vw' );
		}

		if ( ! $lcp && is_page_template( 'templates/about.php' ) ) {
			$lcp = lp_lcp_attachment( (int) get_post_thumbnail_id(), 'lp_wide_lg', '100vw' );
		}

		if ( ! $lcp && is_singular( 'post' ) ) {
			$lcp = lp_lcp_attachment( (int) get_post_thumbnail_id(), 'lp_wide_lg', '100vw' );
		}

		if ( ! $lcp && ( is_page_template( array( 'templates/classes-agenda.php', 'templates/classes-map.php' ) ) || is_singular( 'lp_location' ) ) ) {
			$lcp = lp_lcp_attachment( (int) get_post_thumbnail_id(), 'lp_wide_lg', '100vw' );
		}
	}

	return apply_filters( 'lp_lcp_image', $lcp );
}

/**
 * Early-discover the LCP image. fetchpriority="high" only helps once the
 * parser reaches the <img>; a matching preload starts the fetch in <head>.
 *
 * @param array<int, array<string, string>> $resources Preload descriptors.
 * @return array<int, array<string, string>>
 */
function lp_preload_lcp_image( array $resources ): array {
	$lcp = lp_lcp_image();
	if ( ! $lcp ) {
		return $resources;
	}

	$src = wp_get_attachment_image_src( $lcp['id'], $lcp['size'] );
	if ( ! is_array( $src ) || empty( $src[0] ) ) {
		return $resources;
	}

	$item = array(
		'href'          => (string) $src[0],
		'as'            => 'image',
		'fetchpriority' => 'high',
	);

	$srcset = wp_get_attachment_image_srcset( $lcp['id'], $lcp['size'] );
	if ( is_string( $srcset ) && '' !== $srcset ) {
		$item['imagesrcset'] = $srcset;
		$item['imagesizes']  = $lcp['sizes'];
	}

	$resources[] = $item;

	return $resources;
}
add_filter( 'wp_preload_resources', 'lp_preload_lcp_image' );
