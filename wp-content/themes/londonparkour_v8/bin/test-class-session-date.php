<?php
/**
 * Agenda session links must carry the occurrence date so class-detail
 * books that sitting, not the next available one.
 *
 * Run: bin/wp eval-file bin/test-class-session-date.php
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_fail = 0;

$lp_assert = static function ( bool $ok, string $msg ) use ( &$lp_fail ): void {
	if ( $ok ) {
		WP_CLI::log( "ok  $msg" );
		return;
	}
	++$lp_fail;
	WP_CLI::warning( "FAIL $msg" );
};

$sessions = array(
	array( 'date' => '2026-10-03', 'time' => '10:30' ),
	array( 'date' => '2026-10-10', 'time' => '10:30' ),
	array( 'date' => '2026-10-17', 'time' => '10:30' ),
);

$lp_assert( '2026-10-10' === lp_class_ymd( '2026-10-10' ), 'ymd accepts a calendar date' );
$lp_assert( '' === lp_class_ymd( '10/10/2026' ), 'ymd rejects a slashed date' );
$lp_assert( '' === lp_class_ymd( '2026-13-40' ), 'ymd rejects an impossible date' );

$picked = lp_class_find_session( $sessions, '2026-10-10' );
$lp_assert( is_array( $picked ) && '2026-10-10' === $picked['date'], 'find_session returns the requested sitting' );
$lp_assert( null === lp_class_find_session( $sessions, '2026-10-04' ), 'find_session returns null when the date is not a sitting' );

$focus = lp_class_pick_session( $sessions, '2026-10-10' );
$lp_assert( is_array( $focus ) && '2026-10-10' === $focus['date'], 'pick_session prefers the requested date over the next available' );

$fallback = lp_class_pick_session( $sessions, '' );
$lp_assert( is_array( $fallback ) && '2026-10-03' === $fallback['date'], 'pick_session falls back to the next available with no date' );

$url = lp_class_url_for_date( 'https://londonparkour.com/classes/adult-beginners-outdoor/', '2026-10-10' );
$lp_assert(
	false !== strpos( $url, 'date=2026-10-10' ),
	'url_for_date appends the occurrence as ?date='
);

$week = lp_agenda_week( 1 );
$dated = 0;
$bare  = 0;
foreach ( (array) ( $week['days'] ?? array() ) as $day ) {
	$iso = (string) ( $day['iso'] ?? '' );
	foreach ( (array) ( $day['sessions'] ?? array() ) as $row ) {
		if ( ! empty( $row['past'] ) || '' === (string) ( $row['href'] ?? '' ) ) {
			continue;
		}
		$href = (string) $row['href'];
		if ( false !== strpos( $href, 'date=' . rawurlencode( $iso ) ) || false !== strpos( $href, 'date=' . $iso ) ) {
			++$dated;
		} else {
			++$bare;
			WP_CLI::log( 'bare href: ' . $href . ' (expected date=' . $iso . ')' );
		}
	}
}

$lp_assert( $dated > 0, 'next-week agenda cards include dated class hrefs' );
$lp_assert( 0 === $bare, 'no future agenda card uses a date-less class permalink' );

if ( $lp_fail > 0 ) {
	WP_CLI::error( sprintf( '%d assertion(s) failed', $lp_fail ), false );
	exit( 1 );
}

WP_CLI::success( 'class session date contract' );
