<?php
/**
 * One-shot: write logline / tags / level / chip / lesson layout onto live
 * lp_series terms. Taught series use grid; the 2025 Tutorial Library and
 * 2020 Demonstrations stay on category shelves.
 *
 * Run: bin/wp eval-file wp-content/themes/londonparkour_v8/bin/populate-series-copy.php
 *
 * @package londonparkour_v8
 */

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'ACF is not active.' );
}

$lp_copy = array(
	'2025-tutorial-library'   => array(
		'layout' => 'categories',
	),
	'2024-precisions'         => array(
		'logline'      => 'Takeoff, hinge, and a quiet landing. Twenty-nine lessons on small targets — from foot position to active vs passive landings.',
		'tags'         => 'PRECISION, JUMPING',
		'series_label' => 'INTERMEDIATE',
		'tag'          => 'NEW',
		'layout'       => 'grid',
	),
	'2022-step-vaults'        => array(
		'logline'      => 'The cleanest way over a wall, rebuilt. Basics, faults, and variations that turn a messy hop into a sharp, repeatable vault.',
		'tags'         => 'VAULTING, STEP-VAULT',
		'series_label' => 'BEGINNER',
		'tag'          => 'START HERE',
		'layout'       => 'grid',
	),
	'2022-qm'                 => array(
		'logline'      => 'Stay low, stay smooth. Eight quadrupedal drills — small steps, no swing, and distance without gloves.',
		'tags'         => 'QM, FUNDAMENTALS',
		'series_label' => 'ALL LEVELS',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2020-step-vaults'        => array(
		'logline'      => 'Hand first, hips high, foot through. Seven lessons that lock the step vault before you add speed.',
		'tags'         => 'VAULTING, STEP-VAULT',
		'series_label' => 'BEGINNER',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2020-landings'           => array(
		'logline'      => 'Land small. Stay quiet. Ankles, knees, and hips that make a precision feel like it was always going to stick.',
		'tags'         => 'PRECISION, LANDING',
		'series_label' => 'INTERMEDIATE',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2020-demonstrations'     => array(
		'logline'      => 'The move library. Two hundred clips to watch, copy, and bring to class — not a taught line, a reference board.',
		'tags'         => 'LIBRARY, DEMOS',
		'series_label' => 'ALL LEVELS',
		'tag'          => 'LIBRARY',
		'layout'       => 'categories',
	),
	'2019-turn-vaults'        => array(
		'logline'      => 'Contact, turn, and leave. Twenty-one lessons from a step-and-turn to height, no thumbs, and a normal bar.',
		'tags'         => 'VAULTING, TURN-VAULT',
		'series_label' => 'INTERMEDIATE',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2019-pullups-muscleups'  => array(
		'logline'      => 'The hang that makes every climb easier. Programming, progressions, and the strength work under the muscle-up.',
		'tags'         => 'STRENGTH, PREP',
		'series_label' => 'ALL LEVELS',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2019-climbups'           => array(
		'logline'      => 'Get over the wall you can already hold. Negatives, corkscrews, one-leg, and the full climbup.',
		'tags'         => 'CLIMB, CLIMBUP',
		'series_label' => 'INTERMEDIATE',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2019-cat-pass'           => array(
		'logline'      => 'Two feet up and over. Split-foot and two-step entries that turn a cat pass into a line, not a scramble.',
		'tags'         => 'VAULTING, CAT-PASS',
		'series_label' => 'INTERMEDIATE',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2019-cat-leaps'          => array(
		'logline'      => 'Foot, arm, and the gap. Ten lessons from a small jump to momentum — land the cat, do not hope it.',
		'tags'         => 'CLIMB, ARM-JUMP',
		'series_label' => 'INTERMEDIATE',
		'tag'          => '',
		'layout'       => 'grid',
	),
	'2019-balancing-series'   => array(
		'logline'      => 'Rail, foot, and a quiet head. Walking, turning, crouch, and sideways — balance you can still use at speed.',
		'tags'         => 'BALANCE, RAIL',
		'series_label' => 'BEGINNER',
		'tag'          => '',
		'layout'       => 'grid',
	),
);

$updated = 0;
foreach ( $lp_copy as $slug => $fields ) {
	$term = get_term_by( 'slug', $slug, 'lp_series' );
	if ( ! ( $term instanceof WP_Term ) ) {
		WP_CLI::warning( "Missing series {$slug}" );
		continue;
	}

	$id = 'term_' . (int) $term->term_id;
	foreach ( $fields as $name => $value ) {
		update_field( $name, $value, $id );
	}
	++$updated;
	WP_CLI::log( "  {$slug} (#{$term->term_id})" );
}

WP_CLI::success( "Updated {$updated} series terms." );
