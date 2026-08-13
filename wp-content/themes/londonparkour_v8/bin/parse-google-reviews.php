#!/usr/bin/env php
<?php
/**
 * Parse a Google Business Profile reviews-panel HTML dump into reviews.json.
 *
 * Does not need WordPress. Existing quotes in reviews.json are kept by
 * review_id. The parser never invents a quote.
 *
 * Usage (from themes/londonparkour_v8):
 *   php bin/parse-google-reviews.php
 *   php bin/parse-google-reviews.php --html=bin/data/reviews/reviews.html --json=bin/data/reviews/reviews.json
 *
 * @package londonparkour_v8
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only.\n" );
	exit( 1 );
}

$opts = array(
	'html' => null,
	'json' => null,
);

foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( ! str_starts_with( $arg, '--' ) || ! str_contains( $arg, '=' ) ) {
		fwrite( STDERR, "Unknown argument: {$arg}\n" );
		exit( 1 );
	}
	[ $key, $value ] = explode( '=', substr( $arg, 2 ), 2 );
	if ( ! array_key_exists( $key, $opts ) ) {
		fwrite( STDERR, "Unknown flag: --{$key}\n" );
		exit( 1 );
	}
	$opts[ $key ] = $value;
}

$root = dirname( __DIR__ );
$html_path = $opts['html'] ?: $root . '/bin/data/reviews/reviews.html';
$json_path = $opts['json'] ?: $root . '/bin/data/reviews/reviews.json';

if ( ! is_readable( $html_path ) ) {
	fwrite( STDERR, "HTML not found: {$html_path}\n" );
	exit( 1 );
}

$html = (string) file_get_contents( $html_path );
$existing = array();
if ( is_readable( $json_path ) ) {
	$prev = json_decode( (string) file_get_contents( $json_path ), true );
	if ( is_array( $prev ) && ! empty( $prev['reviews'] ) && is_array( $prev['reviews'] ) ) {
		foreach ( $prev['reviews'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = (string) ( $row['review_id'] ?? '' );
			if ( '' !== $id ) {
				$existing[ $id ] = $row;
			}
		}
	}
}

$parts = preg_split( '/(?=<div class="DsOcnf")/', $html );
$cards = array();
foreach ( $parts as $part ) {
	if ( str_starts_with( $part, '<div class="DsOcnf"' ) ) {
		$cards[] = $part;
	}
}

$reviews = array();
$index   = 0;
foreach ( $cards as $card ) {
	++$index;
	$review_id = lp_reviews_grab( '/data-review-id="([^"]+)"/', $card );
	if ( null === $review_id ) {
		$review_id = lp_reviews_grab( '/key="([^"]+)"/', $card );
	}
	if ( null === $review_id || '' === $review_id ) {
		fwrite( STDERR, "Skipping card {$index}: no review_id\n" );
		continue;
	}

	$author  = lp_reviews_grab( '/class="LH5kS"[^>]*>([^<]+)/', $card );
	$profile = lp_reviews_grab( '/href="(https:\/\/www\.google\.com\/maps\/contrib\/[^"]+)"/', $card );
	$author_id = null;
	if ( is_string( $profile ) && preg_match( '#/contrib/(\d+)/#', $profile, $m ) ) {
		$author_id = $m[1];
	}

	$avatars = array();
	if ( preg_match_all( '/<img class="GHfmfc" src="([^"]+)"/', $card, $am ) ) {
		foreach ( $am[1] as $src ) {
			$avatars[] = html_entity_decode( $src, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
	}

	$full = lp_reviews_grab( '/jsname="QUIPvd"[^>]*>(.*?)<\/span>/s', $card );
	$placeholder = lp_reviews_grab( '/class="amKOJf kIqwof"[^>]*>([^<]+)/', $card );
	$rating_only = null !== $placeholder;
	$text        = $rating_only ? null : $full;

	$filled = preg_match_all( '/class="DPvwYc evnChe MOLvNc"/', $card );

	$quote = null;
	$prev  = $existing[ $review_id ] ?? null;
	if ( is_array( $prev ) ) {
		$kept = $prev['quote'] ?? null;
		if ( is_string( $kept ) && '' !== trim( $kept ) ) {
			$quote = $kept;
		}
	}

	$reviews[] = array(
		'index'               => $index,
		'review_id'           => $review_id,
		'author'              => $author,
		'author_id'           => $author_id,
		'author_profile_url'  => $profile,
		'author_photo_url'    => $avatars[0] ?? null,
		'rating'              => (int) $filled,
		'max_rating'          => 5,
		'date'                => lp_reviews_grab( '/class="zWmYWd NhZJzb"[^>]*>([^<]+)/', $card ),
		'location_label'      => lp_reviews_grab( '/class="ijHgsc"[^>]*>([^<]+)/', $card ),
		'business_name'       => lp_reviews_grab( '/class="rRqL5e"[^>]*>([^<]+)/', $card ),
		'rating_only'         => $rating_only,
		'text'                => $text,
		'quote'               => $quote,
		'was_truncated_in_ui' => str_contains( $card, 'jsname="an9Zef"' ),
		'owner_reply'         => lp_reviews_grab( '/class="DT6Wnd"[^>]*>(.*?)<\/div>/s', $card ),
		'owner_reply_date'    => lp_reviews_grab( '/class="zWmYWd Gjqk4b"[^>]*>([^<]+)/', $card ),
	);
}

$payload = array(
	'source'            => basename( $html_path ),
	'source_ui'         => 'Google Business Profile reviews panel',
	'count'             => count( $reviews ),
	'with_text'         => count( array_filter( $reviews, static fn( $r ) => ! empty( $r['text'] ) ) ),
	'rating_only'       => count( array_filter( $reviews, static fn( $r ) => ! empty( $r['rating_only'] ) ) ),
	'owner_replies'     => count( array_filter( $reviews, static fn( $r ) => ! empty( $r['owner_reply'] ) ) ),
	'rating_histogram'  => array(
		'1' => count( array_filter( $reviews, static fn( $r ) => 1 === (int) $r['rating'] ) ),
		'2' => count( array_filter( $reviews, static fn( $r ) => 2 === (int) $r['rating'] ) ),
		'3' => count( array_filter( $reviews, static fn( $r ) => 3 === (int) $r['rating'] ) ),
		'4' => count( array_filter( $reviews, static fn( $r ) => 4 === (int) $r['rating'] ) ),
		'5' => count( array_filter( $reviews, static fn( $r ) => 5 === (int) $r['rating'] ) ),
	),
	'reviews'           => $reviews,
);

$dir = dirname( $json_path );
if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, true ) && ! is_dir( $dir ) ) {
	fwrite( STDERR, "Could not create {$dir}\n" );
	exit( 1 );
}

$json = json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
if ( false === $json ) {
	fwrite( STDERR, "JSON encode failed\n" );
	exit( 1 );
}

file_put_contents( $json_path, $json . "\n" );

$kept_quotes = count( array_filter( $reviews, static fn( $r ) => is_string( $r['quote'] ) && '' !== $r['quote'] ) );
fwrite( STDOUT, sprintf(
	"Wrote %d reviews (%d with text, %d rating-only, %d quotes kept) to %s\n",
	count( $reviews ),
	$payload['with_text'],
	$payload['rating_only'],
	$kept_quotes,
	$json_path
) );

/**
 * @param string $pattern Regex with one capture.
 * @param string $html    Haystack.
 * @return string|null
 */
function lp_reviews_grab( string $pattern, string $html ): ?string {
	if ( ! preg_match( $pattern, $html, $m ) ) {
		return null;
	}
	$value = html_entity_decode( trim( $m[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return '' === $value ? null : $value;
}
