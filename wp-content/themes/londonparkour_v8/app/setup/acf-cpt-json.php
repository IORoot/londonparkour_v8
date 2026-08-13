<?php
/**
 * Generate ACF post type + taxonomy JSON from app/setup/cpt.php.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stable ACF keys for theme-owned post types.
 *
 * @return array<string, string>
 */
function lp_acf_post_type_keys(): array {
	return array(
		'lp_coach'    => 'post_type_lp0102coach',
		'lp_location' => 'post_type_lp0103location',
		'lp_tutorial' => 'post_type_lp0104tutorial',
		'lp_testimonial' => 'post_type_lp0105testimonial',
	);
}

/**
 * Stable ACF keys for theme-owned taxonomies.
 *
 * @return array<string, string>
 */
function lp_acf_taxonomy_keys(): array {
	return array(
		'lp_level'  => 'taxonomy_lp0101level',
		'lp_series' => 'taxonomy_lp0102series',
	);
}

/**
 * Write lp_* post type and taxonomy definitions to acf-json/.
 *
 * @return array{post_types:int,taxonomies:int}
 */
function lp_acf_write_cpt_taxonomy_json(): array {
	$dir        = get_theme_file_path( 'acf-json' );
	$type_keys  = lp_acf_post_type_keys();
	$tax_keys   = lp_acf_taxonomy_keys();
	$now        = time();
	$post_types = 0;
	$taxonomies = 0;

	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	foreach ( lp_post_types() as $name => $type ) {
		$key = $type_keys[ $name ] ?? null;
		if ( ! $key ) {
			continue;
		}

		$labels     = lp_labels( $type['singular'], $type['plural'] );
		$acf_labels = $labels;
		$acf_labels['featured_image']        = '';
		$acf_labels['set_featured_image']    = '';
		$acf_labels['remove_featured_image'] = '';
		$acf_labels['use_featured_image']    = '';

		$public             = $type['public'] ?? true;
		$publicly_queryable = $type['publicly_queryable'] ?? $public;
		$exclude_search     = $type['exclude_from_search'] ?? ! $public;
		$has_archive        = $type['has_archive'] ?? $public;
		$show_in_nav_menus  = $type['show_in_nav_menus'] ?? $public;

		$rewrite = $has_archive || $publicly_queryable
			? array(
				'permalink_rewrite' => 'custom_permalink',
				'slug'              => $type['slug'],
				'with_front'        => '0',
				'feeds'             => '0',
				'pages'             => '1',
			)
			: array(
				'permalink_rewrite' => 'no_permalink',
				'slug'              => $type['slug'],
				'with_front'        => '0',
				'feeds'             => '0',
				'pages'             => '0',
			);

		$data = array(
			'key'                      => $key,
			'title'                    => $type['plural'],
			'menu_order'               => 0,
			'active'                   => true,
			'post_type'                => $name,
			'advanced_configuration'   => true,
			'import_source'            => '',
			'import_date'              => '',
			'allow_ai_access'          => false,
			'ai_description'           => '',
			'labels'                   => $acf_labels,
			'description'              => '',
			'public'                   => $public,
			'hierarchical'             => false,
			'exclude_from_search'      => $exclude_search,
			'publicly_queryable'       => $publicly_queryable,
			'show_ui'                  => true,
			'show_in_menu'             => true,
			'admin_menu_parent'        => '',
			'show_in_admin_bar'        => true,
			'show_in_nav_menus'        => $show_in_nav_menus,
			'show_in_rest'             => false,
			'rest_base'                => '',
			'rest_namespace'           => 'wp/v2',
			'rest_controller_class'    => 'WP_REST_Posts_Controller',
			'menu_position'            => '',
			'menu_icon'                => array(
				'type'  => 'dashicons',
				'value' => $type['icon'],
			),
			'rename_capabilities'      => false,
			'singular_capability_name' => 'post',
			'plural_capability_name'   => 'posts',
			'supports'                 => $type['supports'],
			'taxonomies'               => $type['taxes'],
			'has_archive'              => $has_archive,
			'has_archive_slug'         => '',
			'rewrite'                  => $rewrite,
			'query_var'                => $publicly_queryable ? 'post_type_key' : 0,
			'query_var_name'           => '',
			'can_export'               => true,
			'delete_with_user'         => false,
			'register_meta_box_cb'     => '',
			'enter_title_here'         => '',
			'modified'                 => $now,
		);

		lp_acf_write_json_file( $dir . '/' . $key . '.json', $data );
		++$post_types;
	}

	foreach ( lp_taxonomies() as $name => $tax ) {
		$key = $tax_keys[ $name ] ?? null;
		if ( ! $key ) {
			continue;
		}

		$labels = lp_labels( $tax['singular'], $tax['plural'] );

		$data = array(
			'key'                    => $key,
			'title'                  => $tax['plural'],
			'menu_order'             => 0,
			'active'                 => true,
			'taxonomy'               => $name,
			'object_type'            => $tax['post_types'],
			'advanced_configuration' => true,
			'import_source'          => '',
			'import_date'            => '',
			'labels'                 => array_merge(
				$labels,
				array(
					'update_item'       => sprintf( 'Update %s', $tax['singular'] ),
					'add_new_item'      => sprintf( 'Add New %s', $tax['singular'] ),
					'new_item_name'     => sprintf( 'New %s Name', $tax['singular'] ),
					'parent_item'       => $tax['hierarchical'] ? sprintf( 'Parent %s', $tax['singular'] ) : '',
					'parent_item_colon' => $tax['hierarchical'] ? sprintf( 'Parent %s:', $tax['singular'] ) : '',
					'search_items'      => sprintf( 'Search %s', $tax['plural'] ),
					'most_used'         => '',
					'not_found'         => sprintf( 'No %s found', strtolower( $tax['plural'] ) ),
					'no_terms'          => sprintf( 'No %s', strtolower( $tax['plural'] ) ),
					'back_to_items'     => sprintf( '← Go to %s', strtolower( $tax['plural'] ) ),
				)
			),
			'description'            => '',
			'capabilities'           => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
			'public'                 => true,
			'publicly_queryable'     => true,
			'hierarchical'           => $tax['hierarchical'],
			'show_ui'                => true,
			'show_in_menu'           => true,
			'show_in_nav_menus'      => true,
			'show_in_rest'           => true,
			'rest_base'              => '',
			'rest_namespace'         => 'wp/v2',
			'rest_controller_class'  => 'WP_REST_Terms_Controller',
			'show_tagcloud'          => true,
			'show_in_quick_edit'     => true,
			'show_admin_column'      => true,
			'rewrite'                => array(
				'permalink_rewrite'    => 'custom_permalink',
				'slug'                 => $tax['slug'],
				'with_front'           => '0',
				'rewrite_hierarchical' => '0',
			),
			'query_var'              => 'post_type_key',
			'query_var_name'         => '',
			'default_term'           => array( 'default_term_enabled' => '0' ),
			'sort'                   => 0,
			'meta_box'               => 'default',
			'meta_box_cb'            => '',
			'meta_box_sanitize_cb'   => '',
			'allow_ai_access'        => false,
			'ai_description'         => '',
			'modified'               => $now,
		);

		lp_acf_write_json_file( $dir . '/' . $key . '.json', $data );
		++$taxonomies;
	}

	return array(
		'post_types' => $post_types,
		'taxonomies' => $taxonomies,
	);
}

/**
 * @param string $path File path.
 * @param array  $data Data to encode.
 */
function lp_acf_write_json_file( string $path, array $data ): void {
	$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	file_put_contents( $path, $json . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}
