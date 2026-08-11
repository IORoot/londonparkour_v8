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

	/*
	 * Vite emits CSS imported by the JS entry (e.g. Leaflet) as a sibling
	 * file listed on the manifest entry's `css` array. main.css alone does
	 * not include it — enqueue those sheets or the map tiles render blank.
	 */
	$lp_manifest_path = get_theme_file_path( 'assets/dist/.vite/manifest.json' );
	if ( is_readable( $lp_manifest_path ) ) {
		$lp_manifest = json_decode( (string) file_get_contents( $lp_manifest_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$lp_app_css  = is_array( $lp_manifest['assets/js/app.js']['css'] ?? null )
			? $lp_manifest['assets/js/app.js']['css']
			: array();
		foreach ( $lp_app_css as $lp_i => $lp_css_file ) {
			$lp_disk = get_theme_file_path( 'assets/dist/' . $lp_css_file );
			$lp_bust = is_readable( $lp_disk ) ? '?v=' . substr( md5( (string) filemtime( $lp_disk ) ), 0, 8 ) : '';
			wp_enqueue_style(
				'londonparkour-app-' . (string) $lp_i,
				get_theme_file_uri( 'assets/dist/' . $lp_css_file ) . $lp_bust,
				array( 'londonparkour' ),
				null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			);
		}
	}

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

/**
 * Emit sanitized inline SVG markup (e.g. clasbpro Card icon).
 *
 * Prefer lp_icon() for sprite symbols. Use this only when the design needs
 * editor-supplied SVG (calendar card icons) that is not in the sprite.
 *
 * @param string $markup  Raw SVG string; sanitized before output.
 * @param string $classes Classes for the root <svg>. Whole literal string.
 */
function lp_inline_svg( string $markup, string $classes = 'w-7 h-7' ): void {
	if ( class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Helpers' ) ) {
		$markup = \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::sanitize_calendar_icon_svg( $markup );
	} else {
		$markup = trim( $markup );
		if ( '' === $markup || ! preg_match( '/<svg\b/i', $markup ) ) {
			return;
		}
		$markup = (string) wp_kses(
			$markup,
			array(
				'svg'      => array(
					'xmlns'       => true,
					'viewbox'     => true,
					'viewBox'     => true,
					'width'       => true,
					'height'      => true,
					'fill'        => true,
					'stroke'      => true,
					'class'       => true,
					'aria-hidden' => true,
					'role'        => true,
					'focusable'   => true,
				),
				'path'     => array(
					'd'               => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'class'           => true,
				),
				'circle'   => array(
					'cx'           => true,
					'cy'           => true,
					'r'            => true,
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
					'class'        => true,
				),
				'g'        => array(
					'fill'   => true,
					'stroke' => true,
					'class'  => true,
				),
				'line'     => array(
					'x1'           => true,
					'y1'           => true,
					'x2'           => true,
					'y2'           => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'polyline' => array(
					'points'       => true,
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
				'polygon'  => array(
					'points'       => true,
					'fill'         => true,
					'stroke'       => true,
					'stroke-width' => true,
				),
			)
		);
	}

	if ( '' === trim( $markup ) || ! preg_match( '/<svg\b/i', $markup ) ) {
		return;
	}

	$classes = trim( $classes );
	$markup  = (string) preg_replace_callback(
		'/<svg\b([^>]*)>/i',
		static function ( array $matches ) use ( $classes ): string {
			$attrs = $matches[1];
			$attrs = (string) preg_replace( '/\sclass=("|\')[^"\']*\1/i', '', $attrs );
			if ( ! preg_match( '/\saria-hidden=/i', $attrs ) ) {
				$attrs .= ' aria-hidden="true"';
			}
			return '<svg class="' . esc_attr( $classes ) . '"' . $attrs . '>';
		},
		$markup,
		1
	);

	echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized via Helpers::sanitize_calendar_icon_svg / wp_kses above.
}

/**
 * Extract an 11-char YouTube video id from a watch / short / embed URL (or a bare id).
 *
 * Used by Class Detail's WATCH THE CLASS dialog (`DialogVideo.js` needs
 * data-video-id, not a full URL). Returns '' when the input is empty or not a
 * recognisable YouTube target — callers hide the control in that case.
 *
 * @param string $url Full YouTube URL or bare video id.
 * @return string
 */
function lp_youtube_id_from_url( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}

	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $url ) ) {
		return $url;
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return '';
	}

	$host = strtolower( (string) ( $parts['host'] ?? '' ) );
	$path = (string) ( $parts['path'] ?? '' );

	if ( str_contains( $host, 'youtu.be' ) ) {
		$id = trim( $path, '/' );
		return preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ? $id : '';
	}

	if ( str_contains( $host, 'youtube.com' ) || str_contains( $host, 'youtube-nocookie.com' ) ) {
		parse_str( (string) ( $parts['query'] ?? '' ), $query );
		$v = (string) ( $query['v'] ?? '' );
		if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $v ) ) {
			return $v;
		}
		if ( preg_match( '#/(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})#', $path, $matches ) ) {
			return $matches[1];
		}
	}

	return '';
}
