<?php
/**
 * PageMasthead — the page's H1 band, optionally over a scrimmed photo.
 *
 * Ported from src/stories/Components/PageMasthead/PageMasthead.js.
 *
 * The photo layer and its gradient are media-photo's `masthead` scrim, which
 * also defaults it to eager + fetchpriority=high — this is the LCP element.
 *
 * Gutter is px-6 lg:px-16 per the Phase 7 layout contract, shared with
 * breadcrumb-rail so the two halves line up on one content edge. The top pad
 * changes with the media because the headline has to clear the photo.
 *
 * @param string $args['title']
 * @param string $args['note']       Optional, bottom-anchored under the H1.
 * @param int    $args['media_id']   Attachment id — enables srcset.
 * @param string $args['media_url']  Raw URL fallback.
 * @param string $args['media_alt']  Omit to inherit the attachment's alt.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_pads = array(
	'media' => 'pt-[136px]',
	'plain' => 'pt-[72px]',
);

$lp_media_id  = ! empty( $args['media_id'] ) ? (int) $args['media_id'] : 0;
$lp_media_url = (string) ( $args['media_url'] ?? '' );
$lp_has_media = $lp_media_id || '' !== $lp_media_url;

$lp_title = (string) ( $args['title'] ?? '' );
$lp_note  = (string) ( $args['note'] ?? '' );
$lp_pad   = $lp_has_media ? $lp_pads['media'] : $lp_pads['plain'];

$lp_photo = array(
	'image_id'  => $lp_media_id,
	'image_url' => $lp_media_url,
	'scrim'     => 'masthead',
	'size'      => 'lp_wide_lg',
	'sizes'     => '100vw',
);

if ( array_key_exists( 'media_alt', $args ) ) {
	$lp_photo['alt'] = (string) $args['media_alt'];
}
?>
<div
	class="<?php echo lp_classes( 'relative bg-neutral border-b border-base-300', $lp_pad, 'px-6 lg:px-16 pb-16' ); ?>"
	data-component="page-masthead"
	data-media="<?php echo $lp_has_media ? 'true' : 'false'; ?>"
>
	<?php
	if ( $lp_has_media ) {
		lp_part( 'components/media-photo', $lp_photo );
	}
	?>
	<div class="relative flex flex-col gap-[30px] items-end">
		<h1 class="w-full font-display font-bold text-neutral-content text-[64px] leading-[0.92] tracking-[-3px]"><?php echo esc_html( $lp_title ); ?></h1>
		<?php if ( '' !== $lp_note ) : ?>
			<p class="max-w-[400px] font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/80"><?php echo esc_html( $lp_note ); ?></p>
		<?php endif; ?>
	</div>
</div>
