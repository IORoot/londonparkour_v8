<?php
/**
 * MediaPhoto — the single image primitive. Every content image goes through it.
 *
 * Consolidated per docs/CONSOLIDATION.md §4c from `photo-scrim-media`
 * (PageMasthead, Hero, VideoCard full, VideoStage, Locations flagship) and
 * `photo-fill-plain` (PrivateCoaching, Coaches lead portrait).
 *
 * DELIBERATE DEVIATION: photo-fill-plain's source writes `h-full w-full` where
 * the scrim family writes `w-full h-full`. Same utilities, same compiled CSS —
 * this part emits one form for both.
 *
 * ── Everything is a parameter ───────────────────────────────────────────────
 * Blocks differ in what they need from an image, so nothing is implicit:
 * element type, layout, crop, breakpoints, priority and arbitrary attributes
 * are all callable. Defaults cover the common case; override any of it.
 *
 *   element  'auto' (default) renders <picture> when `sources` or `formats` are
 *            given, otherwise <img>. Force with 'img', 'picture' or 'figure'
 *            ('figure' wraps and renders `caption` in a <figcaption>).
 *
 *   layout   which base class the <img> gets. 'fill' (default) is the
 *            absolutely-positioned cover used by every ported component and
 *            REQUIRES a positioned ancestor. 'plain' is normal flow — use it
 *            when the image is not in a fixed-ratio box. 'contain' letterboxes.
 *            'none' adds nothing, for a caller that sizes the box itself.
 *
 *   sources  art direction: a different CROP per breakpoint, not merely a
 *            smaller file. array( array( 'media' => '(max-width: 640px)',
 *            'image_id' => 12, 'size' => 'lp_portrait', 'sizes' => '100vw' ) ).
 *
 * There is deliberately NO `formats` arg. A `<source type="image/avif">` has to
 * point at real AVIF bytes; WordPress core generates none, so the only srcset we
 * can build is the original JPEG's — labelling it AVIF makes the browser pick a
 * source it then has to sniff, and no modern format is ever served. If a
 * conversion plugin is added later it rewrites URLs itself, at which point this
 * part needs no change at all.
 *
 * srcset comes from wp_get_attachment_image() when `image_id` is given. Crops
 * are registered in app/setup/theme.php in RATIO-MATCHED FAMILIES — WordPress
 * builds a srcset only from images sharing the reference's aspect ratio, so a
 * lone hard crop yields an EMPTY srcset. Never add an orphan size.
 *
 * `masthead`/`hero` scrims default to eager + fetchpriority=high (they are the
 * LCP element); everything else defaults to lazy. Override per instance.
 *
 * @param int    $args['image_id']      Attachment ID. Preferred — enables srcset.
 * @param string $args['image_url']     Raw URL fallback. No srcset possible.
 * @param string $args['alt']           Omit to inherit the attachment's own alt; pass '' for decorative.
 * @param string $args['element']       auto|img|picture|figure.
 * @param string $args['layout']        fill|plain|contain.
 * @param string $args['scrim']         none|masthead|hero|video_full|video_stage|locations_flagship.
 * @param string $args['size']          Registered size. Default 'lp_wide'.
 * @param string $args['sizes']         `sizes` attribute. Default '100vw'.
 * @param array  $args['sources']       Art direction; emits <picture>.
 * @param string $args['caption']       Rendered in <figcaption> when element=figure.
 * @param string $args['class']         Extra classes on the <img>.
 * @param string $args['wrapper_class'] Classes on the <picture>/<figure>.
 * @param string $args['loading']       lazy|eager.
 * @param string $args['fetchpriority'] auto|high|low.
 * @param string $args['decoding']      async|sync|auto.
 * @param array  $args['attrs']         Arbitrary extra attributes on the <img>.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_scrims = array(
	'masthead'           => 'absolute inset-0 bg-gradient-to-b from-neutral/95 to-neutral/62',
	'hero'               => 'absolute inset-0 bg-neutral/90',
	'video_full'         => 'absolute inset-0 bg-neutral/65',
	'video_stage'        => 'absolute inset-0 bg-secondary/45',
	'locations_flagship' => 'absolute inset-0 bg-neutral/35',
);

$lp_layouts = array(
	'fill'    => 'absolute inset-0 w-full h-full object-cover',
	'plain'   => 'block w-full h-auto',
	'contain' => 'absolute inset-0 w-full h-full object-contain',
	// A fixed box the caller sizes itself (Byline's 26/34/104px avatars) — no
	// base class at all, so `class` is the whole geometry.
	'none'    => '',
);

$lp_image_id = ! empty( $args['image_id'] ) ? (int) $args['image_id'] : 0;
$lp_image_url = (string) ( $args['image_url'] ?? '' );

if ( ! $lp_image_id && '' === $lp_image_url ) {
	return;
}

$lp_scrim_key = (string) ( $args['scrim'] ?? 'none' );
$lp_scrim     = $lp_scrims[ $lp_scrim_key ] ?? '';
$lp_layout    = $lp_layouts[ $args['layout'] ?? 'fill' ] ?? $lp_layouts['fill'];
$lp_size      = (string) ( $args['size'] ?? 'lp_wide' );
$lp_sizes     = (string) ( $args['sizes'] ?? '100vw' );
$lp_alt       = (string) ( $args['alt'] ?? '' );
$lp_caption   = (string) ( $args['caption'] ?? '' );
$lp_sources   = is_array( $args['sources'] ?? null ) ? $args['sources'] : array();

// A decorative image passes alt => '' and must NOT inherit the library's alt,
// so an omitted key and an explicitly empty one are different things.
$lp_has_alt = array_key_exists( 'alt', $args );

$lp_img_class = trim( $lp_layout . ' ' . (string) ( $args['class'] ?? '' ) );

// Above-the-fold treatments are the LCP element — do not lazy-load them.
$lp_is_lcp = in_array( $lp_scrim_key, array( 'masthead', 'hero' ), true );
$lp_loading = (string) ( $args['loading'] ?? ( $lp_is_lcp ? 'eager' : 'lazy' ) );

$lp_element = (string) ( $args['element'] ?? 'auto' );
if ( 'auto' === $lp_element ) {
	$lp_element = $lp_sources ? 'picture' : 'img';
}
// A <figure> still needs a <picture> inside it when there is art direction.
$lp_needs_picture = $lp_sources && 'img' !== $lp_element;

$lp_attr = array_merge(
	array(
		'class'          => $lp_img_class,
		'sizes'          => $lp_sizes,
		'decoding'       => (string) ( $args['decoding'] ?? 'async' ),
		'data-component' => 'media-photo',
	),
	is_array( $args['attrs'] ?? null ) ? $args['attrs'] : array()
);

// 'auto' is the spec default — emitting it on every image is noise, and it
// stops core's own loading-optimisation pass from promoting an image itself.
$lp_fetchpriority = (string) ( $args['fetchpriority'] ?? ( $lp_is_lcp ? 'high' : 'auto' ) );
if ( 'auto' !== $lp_fetchpriority ) {
	$lp_attr['fetchpriority'] = $lp_fetchpriority;
}

if ( $lp_has_alt ) {
	$lp_attr['alt'] = $lp_alt;
}

// loading="eager" is expressed by omitting the attribute; core adds lazy itself.
if ( 'eager' !== $lp_loading ) {
	$lp_attr['loading'] = $lp_loading;
}

/** Emit the <img>, using core when we have an attachment so srcset comes free. */
$lp_img = static function () use ( $lp_image_id, $lp_size, $lp_attr, $lp_image_url, $lp_alt, $lp_img_class, $lp_loading ) {
	if ( $lp_image_id ) {
		echo wp_get_attachment_image( $lp_image_id, $lp_size, false, $lp_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes.
		return;
	}

	$lp_extra = '';
	foreach ( $lp_attr as $lp_k => $lp_v ) {
		if ( in_array( $lp_k, array( 'class', 'alt', 'sizes' ), true ) || '' === $lp_v ) {
			continue;
		}
		$lp_extra .= sprintf( ' %s="%s"', esc_attr( $lp_k ), esc_attr( (string) $lp_v ) );
	}

	printf(
		'<img src="%s" alt="%s" class="%s"%s />',
		esc_url( $lp_image_url ),
		esc_attr( $lp_alt ),
		esc_attr( $lp_img_class ),
		$lp_extra // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per attribute above.
	);
};

/** Emit the art-direction <source> elements, in the order they were given. */
$lp_render_sources = static function () use ( $lp_sources, $lp_image_id, $lp_size, $lp_sizes ) {
	foreach ( $lp_sources as $lp_source ) {
		$lp_sid   = ! empty( $lp_source['image_id'] ) ? (int) $lp_source['image_id'] : $lp_image_id;
		$lp_media = (string) ( $lp_source['media'] ?? '' );

		if ( ! $lp_sid || '' === $lp_media ) {
			continue;
		}

		$lp_ssize = (string) ( $lp_source['size'] ?? $lp_size );
		// A crop with a single generated width has no srcset — fall back to its URL.
		$lp_set = wp_get_attachment_image_srcset( $lp_sid, $lp_ssize )
			?: wp_get_attachment_image_url( $lp_sid, $lp_ssize );

		if ( ! $lp_set ) {
			continue;
		}

		printf(
			'<source media="%s" srcset="%s" sizes="%s"%s />',
			esc_attr( $lp_media ),
			esc_attr( $lp_set ),
			esc_attr( (string) ( $lp_source['sizes'] ?? $lp_sizes ) ),
			! empty( $lp_source['type'] ) ? sprintf( ' type="%s"', esc_attr( (string) $lp_source['type'] ) ) : ''
		);
	}
};

$lp_wrapper_class = (string) ( $args['wrapper_class'] ?? '' );
?>
<?php if ( 'figure' === $lp_element ) : ?>
	<figure<?php echo $lp_wrapper_class ? ' class="' . esc_attr( $lp_wrapper_class ) . '"' : ''; ?>>
		<?php if ( $lp_needs_picture ) : ?>
			<picture><?php $lp_render_sources(); $lp_img(); ?></picture>
		<?php else : ?>
			<?php $lp_img(); ?>
		<?php endif; ?>
		<?php if ( '' !== $lp_caption ) : ?>
			<figcaption class="font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65"><?php echo wp_kses_post( $lp_caption ); ?></figcaption>
		<?php endif; ?>
	</figure>
<?php elseif ( 'picture' === $lp_element ) : ?>
	<picture<?php echo $lp_wrapper_class ? ' class="' . esc_attr( $lp_wrapper_class ) . '"' : ''; ?>><?php $lp_render_sources(); $lp_img(); ?></picture>
<?php else : ?>
	<?php $lp_img(); ?>
<?php endif; ?>
<?php if ( '' !== $lp_scrim ) : ?>
	<div class="<?php echo esc_attr( $lp_scrim ); ?>" aria-hidden="true"></div>
<?php endif; ?>
