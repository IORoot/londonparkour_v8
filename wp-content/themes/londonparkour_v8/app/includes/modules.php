<?php
/**
 * Flexible Content dispatcher.
 *
 * A "block" is a folder under blocks/ holding its markup and its field
 * definition. Adding one means creating the folder and running
 * `wp lp acf:build` — there is no registry to edit.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render every layout of a Flexible Content field, in order.
 *
 * @param string   $field Field name. Defaults to the site-wide sections field.
 * @param int|null $post_id Post to read from. Defaults to the current post.
 */
function lp_render_sections( string $field = 'page_sections', $post_id = null ): void {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$rows = get_field( $field, $post_id );

	if ( ! is_array( $rows ) ) {
		return;
	}

	foreach ( $rows as $index => $row ) {
		$layout = $row['acf_fc_layout'] ?? null;

		if ( ! $layout || ! is_array( $row ) ) {
			continue;
		}

		lp_render_block( $layout, $row, $index );
	}
}

/**
 * Render a single block by its layout name.
 *
 * @param string $layout Layout name, matching the folder under blocks/.
 * @param array  $data   The row's field values, exposed to the partial as $args.
 * @param int    $index  Position in the section list, for anchor fallbacks.
 */
function lp_render_block( string $layout, array $data = array(), int $index = 0 ): void {
	// Layout names come from ACF, but treat them as untrusted path input anyway.
	$slug = sanitize_key( str_replace( '_', '-', $layout ) );
	$file = get_theme_file_path( "blocks/{$slug}/{$slug}.php" );

	if ( ! is_readable( $file ) ) {
		if ( WP_DEBUG ) {
			printf(
				'<!-- londonparkour: no partial for block "%s" -->',
				esc_html( $slug )
			);
		}
		return;
	}

	$data['_index'] = $index;
	$data['_slug']  = $slug;

	get_template_part( "blocks/{$slug}/{$slug}", null, $data );
}
