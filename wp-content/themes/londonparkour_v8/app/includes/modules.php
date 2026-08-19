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
 * Layouts whose eyebrow/kicker is `NN — LABEL`. Marquee is not one of them.
 *
 * @return string[]
 */
function lp_numbered_section_layouts(): array {
	return array(
		'hero',
		'classes',
		'pricing',
		'private-coaching',
		'statement',
		'workshop',
		'clients',
		'tutorials',
		'testimonials',
		'locations',
		'coaches',
		'cta',
	);
}

/**
 * Replace a leading `NN —` prefix with the homepage section index.
 *
 * @param string     $label  Existing eyebrow/kicker.
 * @param int|string $number 1-based index, or empty to leave $label.
 * @return string
 */
function lp_section_label( string $label, $number = null ): string {
	if ( null === $number || '' === $number || '' === $label ) {
		return $label;
	}
	if ( ! preg_match( '/^\d{2}(\s+—\s+)(.+)$/u', $label, $m ) ) {
		return $label;
	}
	return str_pad( (string) absint( $number ), 2, '0', STR_PAD_LEFT ) . $m[1] . $m[2];
}

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

	$section_n = 0;
	foreach ( $rows as $index => $row ) {
		$layout = $row['acf_fc_layout'] ?? null;

		if ( ! $layout || ! is_array( $row ) ) {
			continue;
		}

		$slug = str_replace( '_', '-', sanitize_key( (string) $layout ) );
		if ( in_array( $slug, lp_numbered_section_layouts(), true ) ) {
			++$section_n;
			$row['_section_number'] = $section_n;
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
