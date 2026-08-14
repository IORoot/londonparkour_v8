<?php
/**
 * SVG featured-image / media previews.
 *
 * WordPress never generates raster thumbnails for SVG, so the featured-image
 * metabox, media modal and list-table thumbs collapse to a blank square.
 * Serve the original file with real dimensions instead.
 *
 * Does not allow SVG uploads — that is a separate, security-sensitive gate.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param int $attachment_id Attachment ID.
 * @return bool
 */
function lp_attachment_is_svg( int $attachment_id ): bool {
	return 'image/svg+xml' === get_post_mime_type( $attachment_id );
}

/**
 * Width / height from attachment meta, else the SVG viewBox / attributes.
 *
 * @param int $attachment_id Attachment ID.
 * @return array{0:int,1:int}
 */
function lp_svg_attachment_dimensions( int $attachment_id ): array {
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
		return array( (int) $meta['width'], (int) $meta['height'] );
	}

	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! is_readable( $file ) ) {
		return array( 150, 150 );
	}

	$svg = file_get_contents( $file, false, null, 0, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local attachment, first 8KB only.
	if ( ! is_string( $svg ) ) {
		return array( 150, 150 );
	}

	if ( preg_match( '/viewBox=["\']\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)/i', $svg, $match ) ) {
		$width  = (int) round( (float) $match[1] );
		$height = (int) round( (float) $match[2] );
		if ( $width > 0 && $height > 0 ) {
			return array( $width, $height );
		}
	}

	if (
		preg_match( '/\bwidth=["\']([\d.]+)/i', $svg, $width_match )
		&& preg_match( '/\bheight=["\']([\d.]+)/i', $svg, $height_match )
	) {
		$width  = (int) round( (float) $width_match[1] );
		$height = (int) round( (float) $height_match[1] );
		if ( $width > 0 && $height > 0 ) {
			return array( $width, $height );
		}
	}

	return array( 150, 150 );
}

/**
 * Featured image / wp_get_attachment_image() for SVG: always the original file.
 *
 * @param array|false  $image          Image data, or false.
 * @param int          $attachment_id  Attachment ID.
 * @param string|int[] $size           Requested size.
 * @param bool         $icon           Whether the image should be treated as an icon.
 * @return array|false
 */
function lp_svg_attachment_image_src( $image, $attachment_id, $size, $icon ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP filter signature.
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id < 1 || ! lp_attachment_is_svg( $attachment_id ) ) {
		return $image;
	}

	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) {
		return $image;
	}

	$dims = lp_svg_attachment_dimensions( $attachment_id );

	return array( $url, $dims[0], $dims[1], false );
}
add_filter( 'wp_get_attachment_image_src', 'lp_svg_attachment_image_src', 10, 4 );

/**
 * Media modal / featured-image setter needs sizes.full or the preview stays blank.
 *
 * @param array   $response   Attachment data for JS.
 * @param WP_Post $attachment Attachment post.
 * @return array
 */
function lp_svg_prepare_attachment_for_js( array $response, WP_Post $attachment ): array {
	if ( 'image/svg+xml' !== ( $response['mime'] ?? '' ) ) {
		return $response;
	}

	$url  = $response['url'] ?? wp_get_attachment_url( $attachment->ID );
	$dims = lp_svg_attachment_dimensions( (int) $attachment->ID );

	$response['width']  = $dims[0];
	$response['height'] = $dims[1];
	$response['sizes']  = array(
		'full'      => array(
			'url'         => $url,
			'width'       => $dims[0],
			'height'      => $dims[1],
			'orientation' => $dims[0] >= $dims[1] ? 'landscape' : 'portrait',
		),
		'thumbnail' => array(
			'url'         => $url,
			'width'       => $dims[0],
			'height'      => $dims[1],
			'orientation' => $dims[0] >= $dims[1] ? 'landscape' : 'portrait',
		),
		'medium'    => array(
			'url'         => $url,
			'width'       => $dims[0],
			'height'      => $dims[1],
			'orientation' => $dims[0] >= $dims[1] ? 'landscape' : 'portrait',
		),
	);

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'lp_svg_prepare_attachment_for_js', 10, 2 );

/**
 * Give SVG previews a box to draw into. Zero-size img tags stay invisible.
 */
function lp_svg_admin_preview_styles(): void {
	echo '<style id="lp-svg-admin-preview">
		#postimagediv img[src$=".svg"],
		#postimagediv img[src*=".svg?"],
		.media-icon img[src$=".svg"],
		.attachment-preview img[src$=".svg"],
		.media-frame img[src$=".svg"],
		td.featured_image img[src$=".svg"] {
			width: 100%;
			height: auto;
			max-width: 100%;
			min-height: 80px;
			background: #fff;
		}
	</style>';
}
add_action( 'admin_head', 'lp_svg_admin_preview_styles' );
