<?php
/**
 * Import resolved Google Takeout spots as lp_location (kind=spot).
 *
 * Usage:
 *   bin/wp lp import-spots --file=../../../GOOGLETakeout/resolved-spots.json
 *   bin/wp lp import-spots --file=... --dry-run
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * @param array $args Positional args.
 * @param array $assoc_args Flags.
 */
function lp_cli_import_spots( $args, $assoc_args ) {
	if ( ! function_exists( 'update_field' ) ) {
		WP_CLI::error( 'ACF is not active.' );
	}

	$file = (string) ( $assoc_args['file'] ?? '' );
	if ( '' === $file ) {
		WP_CLI::error( 'Pass --file=/path/to/resolved-spots.json' );
	}

	$path = $file;
	if ( ! lp_path_is_absolute( $path ) ) {
		$candidates = array(
			getcwd() . '/' . $file,
			get_theme_file_path( $file ),
			dirname( get_stylesheet_directory(), 3 ) . '/' . ltrim( $file, '/' ),
		);
		foreach ( $candidates as $candidate ) {
			if ( file_exists( $candidate ) ) {
				$path = $candidate;
				break;
			}
		}
	}

	if ( ! file_exists( $path ) ) {
		WP_CLI::error( "File not found: {$file}" );
	}

	$json = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $json ) || empty( $json['spots'] ) || ! is_array( $json['spots'] ) ) {
		WP_CLI::error( 'JSON must contain a spots array.' );
	}

	$dry = ! empty( $assoc_args['dry-run'] );
	$created = 0;
	$updated = 0;
	$skipped = 0;

	foreach ( $json['spots'] as $spot ) {
		$lat = isset( $spot['lat'] ) ? (string) $spot['lat'] : '';
		$lon = isset( $spot['lon'] ) ? (string) $spot['lon'] : '';
		$name = trim( (string) ( $spot['name'] ?? $spot['title'] ?? '' ) );
		if ( '' === $lat || '' === $lon || '' === $name ) {
			++$skipped;
			continue;
		}

		$slug = sanitize_title( (string) ( $spot['slug'] ?? $name ) );
		if ( '' === $slug ) {
			$slug = 'spot-' . substr( md5( $lat . ',' . $lon ), 0, 8 );
		}

		$existing = get_posts(
			array(
				'post_type'              => 'lp_location',
				'post_status'            => 'any',
				'name'                   => $slug,
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		// Also match an existing spot at the same coordinates.
		if ( ! $existing ) {
			$candidates = get_posts(
				array(
					'post_type'      => 'lp_location',
					'post_status'    => 'publish',
					'posts_per_page' => 50,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'AND',
						array(
							'key'   => 'latitude',
							'value' => $lat,
						),
						array(
							'key'   => 'longitude',
							'value' => $lon,
						),
					),
				)
			);
			foreach ( $candidates as $cand ) {
				if ( 'spot' === lp_location_kind( (int) $cand->ID ) ) {
					$existing = array( (int) $cand->ID );
					break;
				}
			}
		}

		$excerpt_parts = array_filter(
			array(
				trim( (string) ( $spot['note'] ?? '' ) ),
				trim( (string) ( $spot['tags'] ?? '' ) ),
				isset( $spot['lists'] ) ? 'Lists: ' . implode( ', ', (array) $spot['lists'] ) : '',
			)
		);
		$excerpt = implode( ' · ', $excerpt_parts );

		$streetview = trim( (string) ( $spot['streetview'] ?? '' ) );
		if ( '' === $streetview ) {
			$streetview = sprintf(
				'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=%s,%s',
				rawurlencode( $lat ),
				rawurlencode( $lon )
			);
		}

		if ( $dry ) {
			WP_CLI::log( sprintf( '  dry-run %s  %s,%s', $name, $lat, $lon ) );
			++$created;
			continue;
		}

		$postarr = array(
			'post_type'    => 'lp_location',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_name'    => $slug,
			'post_excerpt' => $excerpt,
		);

		if ( $existing ) {
			$postarr['ID'] = (int) $existing[0];
			$id            = wp_update_post( $postarr, true );
			$action        = 'updated';
		} else {
			$id     = wp_insert_post( $postarr, true );
			$action = 'created';
		}

		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( $name . ': ' . $id->get_error_message() );
			++$skipped;
			continue;
		}

		update_field( 'location_kind', 'spot', $id );
		update_field( 'latitude', $lat, $id );
		update_field( 'longitude', $lon, $id );
		update_field( 'streetview', $streetview, $id );

		WP_CLI::log( sprintf( '  %s #%d %s (%s,%s)', $action, $id, $name, $lat, $lon ) );
		if ( 'created' === $action ) {
			++$created;
		} else {
			++$updated;
		}
	}

	WP_CLI::success(
		sprintf(
			'Spots import finished — created %d, updated %d, skipped %d%s',
			$created,
			$updated,
			$skipped,
			$dry ? ' (dry-run)' : ''
		)
	);

	if ( ! empty( $json['unresolved'] ) ) {
		WP_CLI::warning( sprintf( '%d places still need manual coords:', count( $json['unresolved'] ) ) );
		foreach ( $json['unresolved'] as $row ) {
			WP_CLI::log( '  - ' . ( $row['title'] ?? '' ) . ' | ' . ( $row['url'] ?? '' ) );
		}
	}
}

/**
 * @param string $path Path.
 * @return bool
 */
function lp_path_is_absolute( string $path ): bool {
	return (bool) preg_match( '#^(/|[a-zA-Z]:\\\\|\\\\)#', $path );
}

WP_CLI::add_command( 'lp import-spots', 'lp_cli_import_spots' );
