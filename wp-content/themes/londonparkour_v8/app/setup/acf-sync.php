<?php
/**
 * Sync theme acf-json/ into the WordPress database for ACF admin UI.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import all local JSON field groups, post types, and taxonomies into the DB.
 *
 * @return array{synced:int,output:string}
 */
function lp_acf_sync_json_to_database(): array {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		ob_start();
		WP_CLI::runcommand( 'acf json sync', array( 'exit_error' => true ) );
		$output = (string) ob_get_clean();

		return array(
			'synced' => substr_count( $output, 'Created ' ) + substr_count( $output, 'Updated ' ),
			'output' => $output,
		);
	}

	return array(
		'synced' => 0,
		'output' => '',
	);
}

/**
 * Summarise ACF items registered from JSON + database.
 *
 * @return array<string, int>
 */
function lp_acf_admin_counts(): array {
	$counts = array(
		'field_groups'        => 0,
		'field_groups_db'     => 0,
		'post_types'          => 0,
		'post_types_db'       => 0,
		'taxonomies'          => 0,
		'taxonomies_db'       => 0,
	);

	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		return $counts;
	}

	$groups = acf_get_field_groups();
	$counts['field_groups']    = count( $groups );
	$counts['field_groups_db'] = count( array_filter( $groups, static fn( $g ) => ( $g['ID'] ?? 0 ) > 0 ) );

	if ( function_exists( 'acf_get_acf_post_types' ) ) {
		$types = acf_get_acf_post_types();
		$counts['post_types']    = count( $types );
		$counts['post_types_db'] = count( array_filter( $types, static fn( $p ) => ( $p['ID'] ?? 0 ) > 0 ) );
	}

	if ( function_exists( 'acf_get_acf_taxonomies' ) ) {
		$taxes = acf_get_acf_taxonomies();
		$counts['taxonomies']    = count( $taxes );
		$counts['taxonomies_db'] = count( array_filter( $taxes, static fn( $t ) => ( $t['ID'] ?? 0 ) > 0 ) );
	}

	return $counts;
}
