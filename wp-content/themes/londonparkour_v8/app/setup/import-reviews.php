<?php
/**
 * Import Google reviews JSON as lp_testimonial posts.
 *
 * Usage:
 *   bin/wp lp import-reviews
 *   bin/wp lp import-reviews --file=bin/data/reviews/reviews.json
 *   bin/wp lp import-reviews --dry-run
 *
 * Upsert by Google review_id. Missing posts are left alone. Google fields
 * overwrite. `quote` is written only when the CPT field is empty.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * @param array $args       Positional args.
 * @param array $assoc_args Flags.
 */
function lp_cli_import_reviews( $args, $assoc_args ) {
	if ( ! function_exists( 'update_field' ) ) {
		WP_CLI::error( 'ACF is not active.' );
	}

	if ( ! post_type_exists( 'lp_testimonial' ) ) {
		WP_CLI::error( 'Post type lp_testimonial is not registered. Run bin/wp lp acf:build --sync.' );
	}

	$file = (string) ( $assoc_args['file'] ?? 'bin/data/reviews/reviews.json' );
	$path = lp_reviews_resolve_path( $file );
	if ( ! $path ) {
		WP_CLI::error( "File not found: {$file}" );
	}

	$json = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $json ) || empty( $json['reviews'] ) || ! is_array( $json['reviews'] ) ) {
		WP_CLI::error( 'JSON must contain a reviews array.' );
	}

	$dry     = ! empty( $assoc_args['dry-run'] );
	$created = 0;
	$updated = 0;
	$skipped = 0;
	$quoted  = 0;

	foreach ( $json['reviews'] as $row ) {
		if ( ! is_array( $row ) ) {
			++$skipped;
			continue;
		}

		$review_id = trim( (string) ( $row['review_id'] ?? '' ) );
		$author    = trim( (string) ( $row['author'] ?? '' ) );
		if ( '' === $review_id || '' === $author ) {
			++$skipped;
			continue;
		}

		$incoming = lp_reviews_incoming_fields( $row );
		$existing_id = lp_reviews_find_id( $review_id );

		if ( $dry ) {
			$action = $existing_id ? 'update' : 'create';
			WP_CLI::log( sprintf( '  dry-run %s  %s  %s', $action, $author, $review_id ) );
			if ( $existing_id ) {
				++$updated;
			} else {
				++$created;
			}
			continue;
		}

		$postarr = array(
			'post_type'    => 'lp_testimonial',
			'post_status'  => 'publish',
			'post_title'   => $incoming['author'],
			'post_content' => $incoming['text'],
			'post_name'    => lp_reviews_slug( $review_id ),
		);

		if ( $existing_id ) {
			$same_google = lp_reviews_google_unchanged( $existing_id, $incoming );
			$quote_filled = lp_reviews_maybe_fill_quote( $existing_id, $row['quote'] ?? null );
			if ( $quote_filled ) {
				++$quoted;
			}

			if ( $same_google ) {
				if ( $quote_filled ) {
					WP_CLI::log( sprintf( '  quote #%d %s', $existing_id, $author ) );
					++$updated;
				} else {
					++$skipped;
				}
				continue;
			}

			$postarr['ID'] = $existing_id;
			$id            = wp_update_post( $postarr, true );
			$action        = 'updated';
		} else {
			$id     = wp_insert_post( $postarr, true );
			$action = 'created';
		}

		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( $author . ': ' . $id->get_error_message() );
			++$skipped;
			continue;
		}

		lp_reviews_write_google_fields( (int) $id, $incoming );

		if ( 'created' === $action ) {
			if ( lp_reviews_maybe_fill_quote( (int) $id, $row['quote'] ?? null ) ) {
				++$quoted;
			}
			++$created;
		} else {
			++$updated;
		}

		WP_CLI::log( sprintf( '  %s #%d %s', $action, $id, $author ) );
	}

	WP_CLI::success(
		sprintf(
			'Reviews import finished — created %d, updated %d, skipped %d, quotes filled %d%s',
			$created,
			$updated,
			$skipped,
			$quoted,
			$dry ? ' (dry-run)' : ''
		)
	);
}

/**
 * @param string $file Path from --file.
 * @return string|null Absolute readable path.
 */
function lp_reviews_resolve_path( string $file ): ?string {
	if ( function_exists( 'lp_path_is_absolute' ) && lp_path_is_absolute( $file ) && is_readable( $file ) ) {
		return $file;
	}

	$candidates = array(
		$file,
		getcwd() . '/' . ltrim( $file, '/' ),
		get_theme_file_path( $file ),
		get_theme_file_path( 'bin/data/reviews/reviews.json' ),
	);

	foreach ( $candidates as $candidate ) {
		if ( is_string( $candidate ) && is_readable( $candidate ) ) {
			return $candidate;
		}
	}

	return null;
}

/**
 * @param string $review_id Google review id.
 * @return int Post ID or 0.
 */
function lp_reviews_find_id( string $review_id ): int {
	$found = get_posts(
		array(
			'post_type'              => 'lp_testimonial',
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_key'               => 'review_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'             => $review_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	return $found ? (int) $found[0] : 0;
}

/**
 * @param string $review_id Google review id.
 * @return string
 */
function lp_reviews_slug( string $review_id ): string {
	return 'review-' . substr( md5( $review_id ), 0, 16 );
}

/**
 * @param array $row JSON review row.
 * @return array{author:string,text:string,review_id:string,rating:int,reviewed_at:string,author_photo_url:string,author_profile_url:string,author_id:string,location_label:string,business_name:string,rating_only:bool,owner_reply:string,owner_reply_date:string}
 */
function lp_reviews_incoming_fields( array $row ): array {
	$text = $row['text'] ?? '';
	if ( ! is_string( $text ) ) {
		$text = '';
	}

	return array(
		'author'             => trim( (string) ( $row['author'] ?? '' ) ),
		'text'               => $text,
		'review_id'          => trim( (string) ( $row['review_id'] ?? '' ) ),
		'rating'             => (int) ( $row['rating'] ?? 0 ),
		'reviewed_at'        => trim( (string) ( $row['date'] ?? '' ) ),
		'author_photo_url'   => trim( (string) ( $row['author_photo_url'] ?? '' ) ),
		'author_profile_url' => trim( (string) ( $row['author_profile_url'] ?? '' ) ),
		'author_id'          => trim( (string) ( $row['author_id'] ?? '' ) ),
		'location_label'     => trim( (string) ( $row['location_label'] ?? '' ) ),
		'business_name'      => trim( (string) ( $row['business_name'] ?? '' ) ),
		'rating_only'        => ! empty( $row['rating_only'] ),
		'owner_reply'        => is_string( $row['owner_reply'] ?? null ) ? $row['owner_reply'] : '',
		'owner_reply_date'   => trim( (string) ( $row['owner_reply_date'] ?? '' ) ),
	);
}

/**
 * @param int   $post_id  Existing post.
 * @param array $incoming Incoming Google fields.
 * @return bool True when nothing Google-owned has changed.
 */
function lp_reviews_google_unchanged( int $post_id, array $incoming ): bool {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return false;
	}

	if ( $post->post_title !== $incoming['author'] ) {
		return false;
	}
	if ( $post->post_content !== $incoming['text'] ) {
		return false;
	}

	$map = array(
		'review_id'          => $incoming['review_id'],
		'rating'             => $incoming['rating'],
		'reviewed_at'        => $incoming['reviewed_at'],
		'author_photo_url'   => $incoming['author_photo_url'],
		'author_profile_url' => $incoming['author_profile_url'],
		'author_id'          => $incoming['author_id'],
		'location_label'     => $incoming['location_label'],
		'business_name'      => $incoming['business_name'],
		'rating_only'        => $incoming['rating_only'],
		'owner_reply'        => $incoming['owner_reply'],
		'owner_reply_date'   => $incoming['owner_reply_date'],
	);

	foreach ( $map as $name => $want ) {
		$have = get_field( $name, $post_id );
		if ( 'rating' === $name ) {
			$have = (int) $have;
			$want = (int) $want;
		} elseif ( 'rating_only' === $name ) {
			$have = (bool) $have;
			$want = (bool) $want;
		} else {
			$have = is_string( $have ) ? $have : (string) $have;
			$want = (string) $want;
		}
		if ( $have !== $want ) {
			return false;
		}
	}

	return true;
}

/**
 * @param int   $post_id  Post ID.
 * @param array $incoming Incoming Google fields.
 */
function lp_reviews_write_google_fields( int $post_id, array $incoming ): void {
	update_field( 'review_id', $incoming['review_id'], $post_id );
	update_field( 'rating', $incoming['rating'], $post_id );
	update_field( 'reviewed_at', $incoming['reviewed_at'], $post_id );
	update_field( 'author_photo_url', $incoming['author_photo_url'], $post_id );
	update_field( 'author_profile_url', $incoming['author_profile_url'], $post_id );
	update_field( 'author_id', $incoming['author_id'], $post_id );
	update_field( 'location_label', $incoming['location_label'], $post_id );
	update_field( 'business_name', $incoming['business_name'], $post_id );
	update_field( 'rating_only', $incoming['rating_only'] ? 1 : 0, $post_id );
	update_field( 'owner_reply', $incoming['owner_reply'], $post_id );
	update_field( 'owner_reply_date', $incoming['owner_reply_date'], $post_id );
}

/**
 * Fill quote only when the CPT field is empty. Never overwrite or null.
 *
 * @param int   $post_id Post ID.
 * @param mixed $quote   Incoming quote.
 * @return bool Whether a quote was written.
 */
function lp_reviews_maybe_fill_quote( int $post_id, $quote ): bool {
	if ( ! is_string( $quote ) || '' === trim( $quote ) ) {
		return false;
	}

	$current = get_field( 'quote', $post_id );
	if ( is_string( $current ) && '' !== trim( $current ) ) {
		return false;
	}

	update_field( 'quote', $quote, $post_id );
	return true;
}

WP_CLI::add_command( 'lp import-reviews', 'lp_cli_import_reviews' );
