<?php
/**
 * Inventory of ACF JSON that `wp lp acf:build` preserves but does not generate.
 *
 * v7-imported field groups, post types, and taxonomies live here as committed
 * JSON. PHP generation only overwrites group_lp_* and post_type_lp* / taxonomy_lp*.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field group keys generated from PHP on every build.
 *
 * @return string[]
 */
function lp_acf_generated_field_group_keys(): array {
	$keys = array( 'group_lp_page_sections' );

	$extra = get_theme_file_path( 'app/setup/acf-groups.php' );
	if ( is_readable( $extra ) ) {
		$groups = require $extra;
		if ( is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				if ( ! empty( $group['key'] ) ) {
					$keys[] = $group['key'];
				}
			}
		}
	}

	return array_values( array_unique( $keys ) );
}

/**
 * Post type JSON keys generated from cpt.php on every build.
 *
 * @return string[]
 */
function lp_acf_generated_post_type_keys(): array {
	return array(
		'post_type_lp0101class',
		'post_type_lp0102coach',
		'post_type_lp0103location',
		'post_type_lp0104tutorial',
	);
}

/**
 * Taxonomy JSON keys generated from cpt.php on every build.
 *
 * @return string[]
 */
function lp_acf_generated_taxonomy_keys(): array {
	return array(
		'taxonomy_lp0101level',
		'taxonomy_lp0102series',
	);
}

/**
 * v7-imported / manually maintained ACF JSON keys (not overwritten by build).
 *
 * @return array{field_groups:string[],post_types:string[],taxonomies:string[]}
 */
function lp_acf_preserved_json_manifest(): array {
	return array(
		'field_groups' => array(
			'group_6195097cc6251',
			'group_6721ddb5b7f7a',
			'group_6728969e00be0',
			'group_6a74386dea976',
			'group_6731e316da5d8',
			'group_65757b1197f4c',
			'group_65757b98a52d1',
		),
		'post_types'   => array(
			'post_type_6a743920c7e6b',
			'post_type_6a743979a7ead',
			'post_type_6a7439c818063',
		),
		'taxonomies'   => array(
			'taxonomy_6a7437b836cd9',
			'taxonomy_6a7437f050205',
			'taxonomy_6a7438ee91553',
			'taxonomy_6a743906a7304',
			'taxonomy_6a7439aa4fdc3',
		),
	);
}

/**
 * Verify every manifest entry exists on disk.
 *
 * @return string[] Problems.
 */
function lp_acf_validate_manifest(): array {
	$dir      = get_theme_file_path( 'acf-json' );
	$problems = array();

	foreach ( lp_acf_preserved_json_manifest() as $type => $keys ) {
		foreach ( $keys as $key ) {
			$file = $dir . '/' . $key . '.json';
			if ( ! is_readable( $file ) ) {
				$problems[] = sprintf( 'missing preserved %s: %s.json', $type, $key );
			}
		}
	}

	return $problems;
}

/**
 * Count JSON files in acf-json by prefix.
 *
 * @return array{field_groups:int,post_types:int,taxonomies:int}
 */
function lp_acf_json_inventory(): array {
	$dir   = get_theme_file_path( 'acf-json' );
	$count = array(
		'field_groups' => 0,
		'post_types'   => 0,
		'taxonomies'   => 0,
	);

	foreach ( glob( $dir . '/*.json' ) ?: array() as $file ) {
		$name = basename( $file, '.json' );
		if ( str_starts_with( $name, 'group_' ) ) {
			++$count['field_groups'];
		} elseif ( str_starts_with( $name, 'post_type_' ) ) {
			++$count['post_types'];
		} elseif ( str_starts_with( $name, 'taxonomy_' ) ) {
			++$count['taxonomies'];
		}
	}

	return $count;
}
