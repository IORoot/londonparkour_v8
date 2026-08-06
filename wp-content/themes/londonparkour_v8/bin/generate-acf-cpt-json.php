<?php
/**
 * Thin wrapper — prefer `wp lp acf:build` or `wp lp acf:build --sync`.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'app/setup/acf-cpt-json.php' );

$result = lp_acf_write_cpt_taxonomy_json();

WP_CLI::success(
	sprintf(
		'Wrote %d post type(s) and %d taxonom%s.',
		$result['post_types'],
		$result['taxonomies'],
		1 === $result['taxonomies'] ? 'y' : 'ies'
	)
);
