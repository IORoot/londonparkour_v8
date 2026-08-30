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
 * @param string $args['note']           Optional, bottom-anchored under the H1.
 * @param string $args['glyph_svg']      Optional inline SVG (clasbpro Card icon).
 * @param string $args['glyph_icon_id']  Optional sprite id when glyph_svg is empty.
 * @param int    $args['media_id']       Attachment id — enables srcset.
 * @param string $args['media_url']      Raw URL fallback.
 * @param string $args['media_alt']      Omit to inherit the attachment's alt.
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

$lp_title_sizes = array(
	'default' => 'w-full font-display font-bold text-neutral-content text-[64px] leading-[0.92] tracking-[-3px] [text-box:normal]',
	'error'   => 'w-full font-display font-bold text-neutral-content text-[57px] leading-[0.92] tracking-[-3px] [text-box:normal]',
);
$lp_title_sizes_glyph = array(
	'default' => 'w-full flex items-start gap-4 font-display font-bold text-neutral-content text-[64px] leading-[0.92] tracking-[-3px] [text-box:normal]',
	'error'   => 'w-full flex items-start gap-4 font-display font-bold text-neutral-content text-[57px] leading-[0.92] tracking-[-3px] [text-box:normal]',
);
$lp_title_scale = $args['title_scale'] ?? 'default';
$lp_glyph_svg   = (string) ( $args['glyph_svg'] ?? '' );
$lp_glyph_icon  = (string) ( $args['glyph_icon_id'] ?? '' );
$lp_has_glyph   = '' !== $lp_glyph_svg || '' !== $lp_glyph_icon;
$lp_title_class = $lp_has_glyph
	? ( $lp_title_sizes_glyph[ $lp_title_scale ] ?? $lp_title_sizes_glyph['default'] )
	: ( $lp_title_sizes[ $lp_title_scale ] ?? $lp_title_sizes['default'] );

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
	class="<?php echo lp_classes( 'relative bg-neutral border-b border-neutral-content/20', $lp_pad, 'px-6 lg:px-16 pb-16' ); ?>"
	data-component="page-masthead"
	data-media="<?php echo $lp_has_media ? 'true' : 'false'; ?>"
>
	<?php
	if ( $lp_has_media ) {
		lp_part( 'components/media-photo', $lp_photo );
	}
	?>
	<div class="relative flex flex-col gap-[30px] items-end">
		<h1 class="<?php echo esc_attr( $lp_title_class ); ?>">
			<?php if ( $lp_has_glyph ) : ?>
				<span class="inline-flex w-14 h-14 shrink-0 text-neutral-content" aria-hidden="true">
					<?php
					if ( '' !== $lp_glyph_svg && function_exists( 'lp_inline_svg' ) ) {
						lp_inline_svg( $lp_glyph_svg, 'w-14 h-14' );
					} else {
						lp_icon( $lp_glyph_icon, 'w-14 h-14' );
					}
					?>
				</span>
				<span class="min-w-0"><?php echo esc_html( $lp_title ); ?></span>
			<?php else : ?>
				<?php echo esc_html( $lp_title ); ?>
			<?php endif; ?>
		</h1>
		<?php if ( '' !== $lp_note ) : ?>
			<p class="max-w-[400px] font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/80"><?php echo esc_html( $lp_note ); ?></p>
		<?php endif; ?>
	</div>
</div>
