<?php
/**
 * Asset loading and the markup-reuse layer.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a logical source path to its built filename via the Vite manifest.
 *
 * @param string $logical_path Path as given to Vite's rollup input, e.g. 'assets/js/app.js'.
 * @return string The hashed filename relative to assets/dist/.
 */
function lp_vite_asset( string $logical_path ): string {
	static $manifest = null;

	if ( null === $manifest ) {
		$manifest_path = get_theme_file_path( 'assets/dist/.vite/manifest.json' );
		$manifest      = array();

		if ( is_readable( $manifest_path ) ) {
			$decoded = json_decode( (string) file_get_contents( $manifest_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $decoded ) ) {
				$manifest = $decoded;
			}
		}
	}

	$key = ltrim( $logical_path, '/' );

	// Both entry points are declared in vite.config.js, so each has its own
	// `file`. Falling back to the basename keeps the site rendering (unhashed,
	// probably 404ing) rather than fatal-ing if the build is missing.
	return $manifest[ $key ]['file'] ?? basename( $key );
}

/**
 * Public URL for a built asset, with an mtime cache-bust.
 *
 * @param string $logical_path Logical source path.
 * @return string
 */
function lp_asset_url( string $logical_path ): string {
	$file = lp_vite_asset( $logical_path );
	$disk = get_theme_file_path( 'assets/dist/' . $file );
	$bust = is_readable( $disk ) ? '?v=' . substr( md5( (string) filemtime( $disk ) ), 0, 8 ) : '';

	return get_theme_file_uri( 'assets/dist/' . $file ) . $bust;
}

/**
 * Enqueue the built stylesheet and the ES module bundle.
 */
function lp_enqueue_assets(): void {
	wp_enqueue_style( 'londonparkour', lp_asset_url( 'assets/css/main.css' ), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_script( 'londonparkour', lp_asset_url( 'assets/js/app.js' ), array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lp_enqueue_assets' );

/**
 * The bundle is an ES module — Vite emits `import`/`export` syntax.
 *
 * @param string $tag    The script tag.
 * @param string $handle Script handle.
 * @return string
 */
function lp_script_as_module( string $tag, string $handle ): string {
	if ( 'londonparkour' !== $handle ) {
		return $tag;
	}

	return str_replace( '<script ', '<script type="module" ', $tag );
}
add_filter( 'script_loader_tag', 'lp_script_as_module', 10, 2 );

/**
 * Render a shared markup partial.
 *
 * This is the ONLY way a block or template should emit a design-system element.
 * Buttons, rules, badges, icons and every ported component live in exactly one
 * file under parts/ — nothing else may retype their markup. See the reuse audit
 * in bin/audit-reuse.sh, which fails the build if that rule is broken.
 *
 * @param string $slug Path under parts/, without extension. e.g. 'elements/button'.
 * @param array  $args Arguments exposed to the partial as $args.
 */
function lp_part( string $slug, array $args = array() ): void {
	get_template_part( 'parts/' . $slug, null, $args );
}

/**
 * Capture a partial's output instead of echoing it.
 *
 * Needed where markup must be nested into an attribute or concatenated — rare,
 * but better than a second copy of the markup.
 *
 * @param string $slug Path under parts/.
 * @param array  $args Arguments.
 * @return string
 */
function lp_part_html( string $slug, array $args = array() ): string {
	ob_start();
	lp_part( $slug, $args );
	return (string) ob_get_clean();
}

/**
 * Join class strings, dropping empties, and escape for an attribute.
 *
 * Every argument must be a WHOLE literal class string — this joins them, it
 * does not build them. Tailwind v4 scans source text, so a class assembled from
 * fragments is never seen by the scanner. Pick whole strings from a lookup
 * array (see parts/elements/button.php) and pass them here.
 *
 * @param string ...$classes Class strings, any of which may be empty.
 * @return string
 */
function lp_classes( string ...$classes ): string {
	return esc_attr( implode( ' ', array_filter( array_map( 'trim', $classes ) ) ) );
}

/**
 * Emit an SVG icon from one of the two external sprites.
 *
 * Replaces the Storybook's createIcon() from assets/js/utils/svgHelper.js.
 * The sprites are external files so the browser caches them once rather than
 * inlining 415 KB into every page.
 *
 * @param string $id      Symbol id, with or without its prefix. e.g. 'arrow-right' or 'icon-arrow-right'.
 * @param string $classes Classes for the <svg> element. Must be literal strings.
 * @param array  $attrs   Extra attributes, e.g. array( 'aria-label' => 'Menu' ).
 */
function lp_icon( string $id, string $classes = 'w-6 h-6', array $attrs = array() ): void {
	$is_glyph = str_starts_with( $id, 'glyph-' );
	$prefix   = $is_glyph ? '' : ( str_starts_with( $id, 'icon-' ) ? '' : 'icon-' );
	$symbol   = $prefix . $id;
	$sprite   = get_theme_file_uri( $is_glyph ? 'assets/img/glyphs.svg' : 'assets/img/icons.svg' );

	// Decorative unless the caller supplied an accessible name.
	$has_name = isset( $attrs['aria-label'] ) || isset( $attrs['aria-labelledby'] );
	$attrs    = array_merge(
		$has_name ? array( 'role' => 'img' ) : array( 'aria-hidden' => 'true' ),
		$attrs
	);

	$rendered = '';
	foreach ( $attrs as $key => $value ) {
		$rendered .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	printf(
		'<svg class="%s"%s><use href="%s#%s"></use></svg>',
		esc_attr( $classes ),
		$rendered, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per attribute above.
		esc_url( $sprite ),
		esc_attr( $symbol )
	);
}
