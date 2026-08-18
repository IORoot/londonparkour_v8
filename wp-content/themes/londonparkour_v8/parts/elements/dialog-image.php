<?php
/**
 * Gallery still — 4:3 tile linking to the full image in a new tab.
 *
 * button.php has no image-tile variant. The invoker lives here so the
 * workshop gallery does not hand-roll an <a> + <img> pair in a template.
 * Hover is Concourse inversion: a primary wash (`group-hover:bg-primary/45`)
 * over the photo, matching EdXU4 Still 01 / Hover in the .pen.
 *
 * @param int    $args['image_id'] Attachment id.
 * @param string $args['alt']      Optional. Falls back to the attachment alt.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_image_id = (int) ( $args['image_id'] ?? 0 );
$lp_href     = $lp_image_id ? wp_get_attachment_image_url( $lp_image_id, 'full' ) : '';
if ( $lp_image_id <= 0 || ! $lp_href ) {
	return;
}

$lp_alt = array_key_exists( 'alt', $args )
	? (string) $args['alt']
	: (string) get_post_meta( $lp_image_id, '_wp_attachment_image_alt', true );
?>
<a
	href="<?php echo esc_url( $lp_href ); ?>"
	target="_blank"
	rel="noopener noreferrer"
	class="relative aspect-[4/3] bg-secondary overflow-hidden w-full block group cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
>
	<?php
	lp_part(
		'components/media-photo',
		array(
			'image_id' => $lp_image_id,
			'layout'   => 'fill',
			'size'     => 'lp_wide',
			'sizes'    => '(min-width: 1024px) 25vw, 50vw',
			'class'    => 'absolute inset-0 w-full h-full object-cover',
			'alt'      => $lp_alt,
		)
	);
	?>
	<span class="absolute inset-0 bg-primary/0 group-hover:bg-primary/45 transition-colors duration-150" aria-hidden="true"></span>
</a>
