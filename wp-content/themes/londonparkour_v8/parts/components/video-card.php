<?php
/**
 * VideoCard — two variants of a video thumbnail card.
 *
 * Ported from src/stories/Components/VideoCard/VideoCard.js.
 *
 * `full` is the scrimmed hero card: badge + duration over a play disc, then a
 * body ending in a primary CTA. `compact` is the plain thumbnail card that
 * ends in a foot label + chevron — the same tail as media-card, and it shares
 * that chevron variant byte-for-byte (docs/CONSOLIDATION.md §2b).
 *
 * The play disc is elements/icon-circle.php variant '34' (§2a) — decorative
 * here, since the whole card is the control. Kicker and meta compose
 * elements/glyph-label.php; the badge is elements/badge.php; the CTA is
 * elements/button.php.
 *
 * `full`'s photo is media-photo's `video_full` scrim (absolute fill); the
 * `compact` figure holds a plain w-full/h-full image, so that one uses
 * layout='none' — see media-card for the same distinction.
 *
 * @param string $args['variant']     full|compact. Default 'full'.
 * @param int    $args['image_id']    Attachment id — enables srcset.
 * @param string $args['image_url']   Raw URL fallback.
 * @param string $args['image_alt']   Omit to inherit the attachment's alt.
 * @param string $args['glyph_id']    Optional glyph on the kicker.
 * @param string $args['kicker']
 * @param string $args['meta']
 * @param string $args['title']
 * @param string $args['note']        Optional.
 * @param string $args['duration']    full only.
 * @param string $args['badge_label'] full only.
 * @param string $args['flag']        full only — optional signal word.
 * @param string $args['cta_label']   full only.
 * @param string $args['cta_href']    full only.
 * @param string $args['foot_label']  compact only.
 * @param string $args['href']        compact only — makes the card one <a>.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_compact_root_base        = 'rounded-none bg-transparent overflow-hidden no-underline text-left';
$lp_compact_root_interactive = 'group cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';

$lp_is_compact = 'compact' === ( $args['variant'] ?? 'full' );

$lp_image_id  = ! empty( $args['image_id'] ) ? (int) $args['image_id'] : 0;
$lp_image_url = (string) ( $args['image_url'] ?? '' );
$lp_has_image = $lp_image_id || '' !== $lp_image_url;
$lp_note      = (string) ( $args['note'] ?? '' );
$lp_glyph_id  = (string) ( $args['glyph_id'] ?? '' );

$lp_photo = array(
	'image_id'  => $lp_image_id,
	'image_url' => $lp_image_url,
	'size'      => 'lp_wide',
	'sizes'     => '(min-width: 1024px) 33vw, 100vw',
);

if ( array_key_exists( 'image_alt', $args ) ) {
	$lp_photo['alt'] = (string) $args['image_alt'];
}

/** The kicker/meta pair, identical in both variants. */
$lp_head_row = static function ( $lp_kicker, $lp_meta, $lp_glyph_id ) {
	?>
	<div class="flex items-center justify-between gap-3">
		<span class="min-w-0">
			<?php
			lp_part(
				'elements/glyph-label',
				array(
					'label'   => $lp_kicker,
					'icon_id' => $lp_glyph_id,
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
	<?php
};

if ( $lp_is_compact ) :
	$lp_kicker     = (string) ( $args['kicker'] ?? 'Demo 01 · Cat Leap' );
	$lp_meta       = (string) ( $args['meta'] ?? '01:12' );
	$lp_title      = (string) ( $args['title'] ?? 'Cat Leap at Speed' );
	$lp_foot_label = (string) ( $args['foot_label'] ?? 'Demonstration · 01:12' );
	$lp_href       = (string) ( $args['href'] ?? '' );
	$lp_is_link    = '' !== $lp_href;

	$lp_root = lp_classes( 'card', $lp_compact_root_base, $lp_is_link ? $lp_compact_root_interactive : '' );
	?>
	<?php if ( $lp_is_link ) : ?>
	<a class="<?php echo $lp_root; ?>" data-component="video-card" data-variant="compact" href="<?php echo esc_url( $lp_href ); ?>">
	<?php else : ?>
	<div class="<?php echo $lp_root; ?>" data-component="video-card" data-variant="compact">
	<?php endif; ?>
		<figure class="aspect-[12/5] w-full bg-base-300 overflow-hidden m-0">
			<?php
			lp_part(
				'components/media-photo',
				array_merge(
					$lp_photo,
					array(
						'element' => 'img',
						'layout'  => 'none',
						'class'   => 'w-full h-full object-cover',
					)
				)
			);
			?>
		</figure>
		<div class="card-body p-0 pt-[19px] gap-[13px]">
			<?php $lp_head_row( $lp_kicker, $lp_meta, $lp_glyph_id ); ?>
			<h3 class="card-title font-heading text-[22px] font-medium tracking-[-0.4px] leading-none text-base-content"><?php echo esc_html( $lp_title ); ?></h3>
			<?php if ( '' !== $lp_note ) : ?>
				<p class="font-body text-[12px] font-normal tracking-[0.1px] leading-normal text-base-content/70"><?php echo esc_html( $lp_note ); ?></p>
			<?php endif; ?>
			<div class="flex items-center justify-between gap-3 pt-[15px]">
				<span class="font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-base-content"><?php echo esc_html( $lp_foot_label ); ?></span>
				<?php lp_part( 'elements/chevron', array( 'variant' => $lp_is_link ? 'media_card' : 'media_card_static' ) ); ?>
			</div>
		</div>
	<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
	<?php
	return;
endif;

$lp_kicker      = (string) ( $args['kicker'] ?? 'Vaulting' );
$lp_meta        = (string) ( $args['meta'] ?? '01 · Step-vault' );
$lp_title       = (string) ( $args['title'] ?? 'Two Hands, One-foot.' );
$lp_duration    = (string) ( $args['duration'] ?? '4:12' );
$lp_badge_label = (string) ( $args['badge_label'] ?? 'Lesson' );
$lp_flag        = (string) ( $args['flag'] ?? '' );
$lp_cta_label   = (string) ( $args['cta_label'] ?? 'Watch lesson' );
?>
<article class="card rounded-none bg-base-100 overflow-hidden" data-component="video-card" data-variant="full">
	<figure class="relative aspect-video w-full bg-base-300 overflow-hidden m-0">
		<?php
		if ( $lp_has_image ) {
			lp_part( 'components/media-photo', array_merge( $lp_photo, array( 'scrim' => 'video_full' ) ) );
		} else {
			// No photo, but the scrim is part of the card's design, not the image's.
			?>
			<div class="absolute inset-0 bg-neutral/65" aria-hidden="true"></div>
			<?php
		}
		?>
		<div class="absolute inset-0 flex flex-col justify-between p-[13px]" aria-hidden="true">
			<div class="flex items-center justify-between gap-2">
				<span>
					<?php
					lp_part(
						'elements/badge',
						array(
							'variant' => 'category',
							'label'   => $lp_badge_label,
						)
					);
					?>
				</span>
				<span class="font-label text-[10px] font-semibold tracking-[0.8px] text-neutral-content"><?php echo esc_html( $lp_duration ); ?></span>
			</div>
			<div class="flex items-center justify-between gap-2">
				<?php lp_part( 'elements/icon-circle', array( 'variant' => '34' ) ); ?>
				<?php if ( '' !== $lp_flag ) : ?>
					<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-primary"><?php echo esc_html( $lp_flag ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</figure>
	<div class="card-body p-[16px] pt-[18px] gap-[14px]">
		<?php $lp_head_row( $lp_kicker, $lp_meta, $lp_glyph_id ); ?>
		<h3 class="card-title font-heading text-[22px] font-bold tracking-[-0.5px] leading-none text-base-content"><?php echo esc_html( $lp_title ); ?></h3>
		<?php if ( '' !== $lp_note ) : ?>
			<p class="font-body text-[12px] font-normal tracking-[0.1px] leading-normal text-base-content/70"><?php echo esc_html( $lp_note ); ?></p>
		<?php endif; ?>
		<div class="pt-[14px]">
			<?php
			lp_part(
				'elements/button',
				array(
					'variant'          => 'primary',
					'label'            => $lp_cta_label,
					'href'             => $args['cta_href'] ?? '',
					'trailing_icon_id' => 'icon-arrow-right',
					'aria_label'       => sprintf( 'Watch: %s, %s', $lp_title, $lp_duration ),
				)
			);
			?>
		</div>
	</div>
</article>
