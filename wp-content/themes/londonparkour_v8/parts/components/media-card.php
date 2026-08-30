<?php
/**
 * MediaCard — image over kicker/meta, title, optional note, foot + chevron.
 *
 * Ported from src/stories/Components/MediaCard/MediaCard.js.
 *
 * Kicker and meta compose elements/glyph-label rather than re-declaring its
 * type values; the trailing chevron is elements/chevron.php (variant
 * `media_card`, or `media_card_static` when the card is not a link — the
 * source writes the hover half behind an isLink ternary).
 *
 * The <figure> is emitted here, not by media-photo, because the source renders
 * the empty fixed-aspect box even with no image. The <img> inside is plain
 * `w-full h-full object-cover` — NOT media-photo's absolute `fill` — so it
 * uses layout='none' and carries the geometry as `class`.
 *
 * @param int    $args['image_id']  Attachment id — enables srcset.
 * @param string $args['image_url'] Raw URL fallback.
 * @param string $args['image_alt'] Omit to inherit the attachment's alt.
 * @param string $args['aspect']    wide|tall. Default 'wide'.
 * @param string $args['glyph_id']  Optional glyph on the kicker.
 * @param string $args['kicker']
 * @param string $args['meta']
 * @param string $args['title']
 * @param string $args['note']      Optional.
 * @param string $args['foot']
 * @param string $args['href']      Renders the card as one focusable <a>.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_aspects = array(
	'wide' => 'aspect-video',
	'tall' => 'aspect-[3/2]',
);

$lp_root_base        = 'rounded-none bg-transparent overflow-hidden no-underline text-left';
$lp_root_interactive = 'group cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';

$lp_aspect_key = (string) ( $args['aspect'] ?? 'wide' );
$lp_aspect     = $lp_aspects[ $lp_aspect_key ] ?? $lp_aspects['wide'];

$lp_href    = (string) ( $args['href'] ?? '' );
$lp_is_link = '' !== $lp_href;

$lp_image_id  = ! empty( $args['image_id'] ) ? (int) $args['image_id'] : 0;
$lp_image_url = (string) ( $args['image_url'] ?? '' );
$lp_kicker    = (string) ( $args['kicker'] ?? 'Adults (15+)' );
$lp_meta      = (string) ( $args['meta'] ?? 'Vauxhall' );
$lp_title     = (string) ( $args['title'] ?? 'Outdoor Class' );
$lp_note      = (string) ( $args['note'] ?? '' );
$lp_foot      = (string) ( $args['foot'] ?? '£15 · 90 min' );

$lp_root = lp_classes( 'card', $lp_root_base, $lp_is_link ? $lp_root_interactive : '' );

$lp_photo = array(
	'image_id'  => $lp_image_id,
	'image_url' => $lp_image_url,
	'element'   => 'img',
	'layout'    => 'none',
	'class'     => 'w-full h-full object-cover',
	'size'      => 'lp_wide',
	'sizes'     => '(min-width: 1024px) 33vw, 100vw',
);

if ( array_key_exists( 'image_alt', $args ) ) {
	$lp_photo['alt'] = (string) $args['image_alt'];
}
?>
<?php if ( $lp_is_link ) : ?>
<a class="<?php echo $lp_root; ?>" data-component="media-card" data-aspect="<?php echo esc_attr( $lp_aspect_key ); ?>" href="<?php echo esc_url( $lp_href ); ?>">
<?php else : ?>
<div class="<?php echo $lp_root; ?>" data-component="media-card" data-aspect="<?php echo esc_attr( $lp_aspect_key ); ?>">
<?php endif; ?>
	<figure class="<?php echo lp_classes( $lp_aspect, 'w-full bg-base-300 overflow-hidden m-0' ); ?>">
		<?php lp_part( 'components/media-photo', $lp_photo ); ?>
	</figure>
	<div class="card-body p-0 pt-[19px] gap-[13px]">
		<div class="flex items-center justify-between gap-3">
			<span class="min-w-0">
				<?php
				lp_part(
					'elements/glyph-label',
					array(
						'label'   => $lp_kicker,
						'icon_id' => $args['glyph_id'] ?? '',
						'surface' => 'page',
						'tone'    => 'ink',
					)
				);
				?>
			</span>
			<span class="shrink-0">
				<?php
				lp_part(
					'elements/glyph-label',
					array(
						'label'   => $lp_meta,
						'surface' => 'page',
						'tone'    => 'muted',
					)
				);
				?>
			</span>
		</div>
		<h3 class="card-title font-heading text-[22px] font-medium tracking-[-0.4px] leading-none text-base-content"><?php echo esc_html( $lp_title ); ?></h3>
		<?php if ( '' !== $lp_note ) : ?>
			<p class="font-body text-[12px] font-normal tracking-[0.1px] leading-normal text-base-content/70"><?php echo esc_html( $lp_note ); ?></p>
		<?php endif; ?>
		<div class="flex items-center justify-between gap-3 pt-[15px]">
			<span class="font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-base-content"><?php echo esc_html( $lp_foot ); ?></span>
			<?php lp_part( 'elements/chevron', array( 'variant' => $lp_is_link ? 'media_card' : 'media_card_static' ) ); ?>
		</div>
	</div>
<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
