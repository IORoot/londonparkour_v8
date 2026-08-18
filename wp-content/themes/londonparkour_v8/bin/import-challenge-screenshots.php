<?php
/**
 * Import challenge screenshots into RML "New Tutorials" and attach to tutorials.
 *
 * Run: bin/wp eval-file bin/import-challenge-screenshots.php
 *
 * @package londonparkour_v8
 */

@set_time_limit( 0 );
wp_raise_memory_limit( 'image' );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$source_root = WP_CONTENT_DIR . '/uploads/_import-challenge-screenshots';
$rml_folder  = 321;

if ( ! is_dir( $source_root ) ) {
	fwrite( STDERR, "Staging directory missing: {$source_root}\n" );
	exit( 1 );
}

/**
 * @return array{by_rel: array<string,string>, by_base: array<string,string[]>}
 */
function lp_index_pngs( string $root ): array {
	$by_rel  = array();
	$by_base = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $iterator as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$name = $file->getFilename();
		if ( $name[0] === '.' || ! preg_match( '/\.png$/i', $name ) ) {
			continue;
		}
		$abs = $file->getPathname();
		$rel = ltrim( str_replace( '\\', '/', substr( $abs, strlen( $root ) ) ), '/' );
		$by_rel[ $rel ] = $abs;
		$by_base[ $name ][] = $rel;
	}
	return array(
		'by_rel'  => $by_rel,
		'by_base' => $by_base,
	);
}

function lp_resolve_screenshot_rel( string $acf_rel, array $index ): ?string {
	$acf_rel = str_replace( '\\', '/', $acf_rel );
	if ( isset( $index['by_rel'][ $acf_rel ] ) ) {
		return $acf_rel;
	}

	$base = basename( $acf_rel );
	if ( isset( $index['by_base'][ $base ] ) && count( $index['by_base'][ $base ] ) === 1 ) {
		return $index['by_base'][ $base ][0];
	}

	$candidates = array();
	$candidates[] = str_replace( 'Climbing-Dynos/', 'Climbing-Dyno/', $acf_rel );
	$candidates[] = str_replace( 'Traversing-10', 'Traversing_10', $acf_rel );
	$candidates[] = str_replace(
		'Tutorial-jumping-precisions-3-block-to-block',
		'Tutorial-Jumping-Precisions_3-block-to-block',
		$acf_rel
	);

	if ( str_contains( $acf_rel, 'Crawling-QMBasics' ) ) {
		$rewritten = str_replace( 'Crawling-QMBasics/', 'Crawling-QM/', $acf_rel );
		$rewritten = preg_replace( '/Tutorial-Crawling-QM-Basics-(\d+)-/', 'Tutorial-Crawling-QM_$1-', $rewritten );
		$candidates[] = $rewritten;
	}

	if ( $base === 'Tutorial-Climbing-Arm_Jump_1-Standing-Arm-Jump_3_4.png' ) {
		$candidates[] = 'Climbing-ArmJump/screenshots/Tutorial-Climbing-Arm_Jump_1-Standing-Arm-Jump_1-3_4..png';
	}

	foreach ( $candidates as $candidate ) {
		if ( is_string( $candidate ) && isset( $index['by_rel'][ $candidate ] ) ) {
			return $candidate;
		}
	}

	return null;
}

function lp_find_attachment_by_basename( string $basename ): int {
	global $wpdb;

	$names = array_unique( array( $basename, sanitize_file_name( $basename ) ) );
	foreach ( $names as $name ) {
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = '_wp_attached_file'
				   AND (meta_value = %s OR meta_value LIKE %s)
				 ORDER BY post_id DESC LIMIT 1",
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

function lp_filename_title( string $basename ): string {
	return (string) preg_replace( '/\.[^.]+$/', '', $basename );
}

function lp_ensure_in_rml_folder( int $attachment_id, int $folder_id ): void {
	if ( ! function_exists( 'wp_rml_move' ) ) {
		fwrite( STDERR, "Real Media Library API missing\n" );
		exit( 1 );
	}
	$current = function_exists( 'wp_attachment_folder' ) ? (int) wp_attachment_folder( $attachment_id ) : 0;
	if ( $current === $folder_id ) {
		return;
	}
	$result = wp_rml_move( $folder_id, array( $attachment_id ), true );
	if ( $result !== true ) {
		$msg = is_array( $result ) ? implode( '; ', $result ) : 'unknown move error';
		fwrite( STDERR, "RML move failed for {$attachment_id}: {$msg}\n" );
	}
}

function lp_import_png( string $abs_path, string $title, string $alt, int $rml_folder ): int {
	$id = lp_sideload_image_once(
		$abs_path,
		array(
			'post_title' => $title,
			'alt'        => $alt,
		)
	);
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( basename( $abs_path ) . ': ' . $id->get_error_message() );
	}

	lp_ensure_in_rml_folder( (int) $id, $rml_folder );

	return (int) $id;
}

echo "Indexing staging PNGs...\n";
$index = lp_index_pngs( $source_root );
echo '  files=' . count( $index['by_rel'] ) . "\n";

echo "Uploading into RML folder {$rml_folder}...\n";
$rel_to_id   = array();
$uploaded    = 0;
$reused      = 0;
$upload_fail = 0;

foreach ( $index['by_rel'] as $rel => $abs ) {
	$basename = basename( $abs );
	$title    = lp_filename_title( $basename );
	$before   = lp_find_attachment_by_basename( $basename );
	try {
		$id = lp_import_png( $abs, $title, $title, $rml_folder );
		$rel_to_id[ $rel ] = $id;
		if ( $before ) {
			++$reused;
		} else {
			++$uploaded;
		}
	} catch ( Throwable $e ) {
		++$upload_fail;
		fwrite( STDERR, 'UPLOAD FAIL ' . $rel . ': ' . $e->getMessage() . "\n" );
	}
	$total = $uploaded + $reused;
	if ( $total % 25 === 0 ) {
		echo "  {$total}...\n";
	}
}

echo "upload_new={$uploaded} reused={$reused} fail={$upload_fail}\n";

$term = get_term_by( 'slug', 'challenge', 'tutorial-tag' );
if ( ! $term ) {
	fwrite( STDERR, "Challenge tag not found\n" );
	exit( 1 );
}

$query = new WP_Query(
	array(
		'post_type'      => 'lp_tutorial',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'tutorial-tag',
				'field'    => 'term_id',
				'terms'    => $term->term_id,
			),
		),
		'no_found_rows'  => true,
	)
);

echo 'Attaching to Challenge tutorials (' . count( $query->posts ) . ")...\n";

$featured      = 0;
$portrait      = 0;
$skipped_empty = 0;
$miss_land     = 0;
$miss_port     = 0;
$fields_fixed  = 0;

foreach ( $query->posts as $post_id ) {
	$post_id  = (int) $post_id;
	$title    = get_the_title( $post_id );
	$land_acf = trim( (string) get_post_meta( $post_id, 'landscape_thumnbail_filename', true ) );
	$port_acf = trim( (string) get_post_meta( $post_id, 'portrait_thumnbail_filename', true ) );
	$land_original = str_replace( '\\', '/', $land_acf );
	$port_original = str_replace( '\\', '/', $port_acf );

	if ( $land_acf === '' && $port_acf === '' ) {
		++$skipped_empty;
		continue;
	}

	if (
		str_contains( $land_acf, 'speed_step-7-jump-step' )
		&& strcasecmp( $title, 'Alternating Down' ) === 0
	) {
		$land_acf = 'Vaulting-SpeedStep/screenshots/Tutorial-vaulting-speed_step-9-alternating-down_16_9.png';
		$port_acf = 'Vaulting-SpeedStep/screenshots/Tutorial-vaulting-speed_step-9-alternating-down_3_4.png';
	}

	$land_rel = $land_acf !== '' ? lp_resolve_screenshot_rel( $land_acf, $index ) : null;
	$port_rel = $port_acf !== '' ? lp_resolve_screenshot_rel( $port_acf, $index ) : null;

	if ( $land_rel && isset( $rel_to_id[ $land_rel ] ) ) {
		$land_id = $rel_to_id[ $land_rel ];
		set_post_thumbnail( $post_id, $land_id );
		update_post_meta( $land_id, '_wp_attachment_image_alt', $title );
		++$featured;
		if ( $land_rel !== $land_original ) {
			update_field( 'landscape_thumnbail_filename', $land_rel, $post_id );
			++$fields_fixed;
		}
	} elseif ( $land_acf !== '' ) {
		++$miss_land;
		echo "MISS landscape {$post_id} {$title}: {$land_acf}\n";
	}

	if ( $port_rel && isset( $rel_to_id[ $port_rel ] ) ) {
		$port_id = $rel_to_id[ $port_rel ];
		update_field( '3_4_image', $port_id, $post_id );
		update_post_meta( $port_id, '_wp_attachment_image_alt', $title );
		++$portrait;
		if ( $port_rel !== $port_original ) {
			update_field( 'portrait_thumnbail_filename', $port_rel, $post_id );
			++$fields_fixed;
		}
	} elseif ( $port_acf !== '' ) {
		++$miss_port;
		echo "MISS portrait {$post_id} {$title}: {$port_acf}\n";
	}
}

$rml_count = function_exists( 'wp_rml_get_attachments' ) ? count( (array) wp_rml_get_attachments( $rml_folder ) ) : 0;

echo "\nDone\n";
echo "featured_set={$featured}\n";
echo "portrait_set={$portrait}\n";
echo "skipped_no_filenames={$skipped_empty}\n";
echo "miss_land={$miss_land}\n";
echo "miss_port={$miss_port}\n";
echo "filename_fields_updated={$fields_fixed}\n";
echo "rml_folder_count={$rml_count}\n";
