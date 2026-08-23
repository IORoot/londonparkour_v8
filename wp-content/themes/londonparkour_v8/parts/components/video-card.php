<?php
/**
 * VideoCard — two variants of a video thumbnail card.
 *
 * Ported from src/stories/Components/VideoCard/VideoCard.js.
 *
 * `full` is the raised index card (`bg-base-200` + `border-base-300`): badge +
 * duration over a play disc, then a body ending in a primary CTA under a
 * hairline. `compact` is the plain thumbnail card that
 * ends in a foot label + chevron — the same tail as media-card, and it shares
 * that chevron variant byte-for-byte (docs/CONSOLIDATION.md §2b). `lesson` is
 * the dark series-detail card (`lmIjE` / `QvJmB`): secondary fill, 16:9 media,
 * paper index, square play, WATCH foot.
 *
 * The play disc is elements/icon-circle.php variant '34' (§2a) — decorative
 * here, since the whole card is the control. Compact media also carries that
 * disc, centred, so the two demonstration stills read as videos (OsOLg had
 * none; the overlay is the same atom as `full`, not a new control). Kicker
 * and meta compose elements/glyph-label.php; the badge is elements/badge.php;
 * the CTA is elements/button.php.
 *
 * `full`'s photo is media-photo's `video_full` scrim (absolute fill); the
 * `compact` figure holds a plain w-full/h-full image, so that one uses
 * layout='none' — see media-card for the same distinction.
 *
 * @param string $args['variant']     full|compact|lesson. Default 'full'.
 * @param int    $args['image_id']    Attachment id — enables srcset.
 * @param string $args['image_url']   Raw URL fallback.
 * @param string $args['image_alt']   Omit to inherit the attachment's alt.
 * @param string $args['glyph_id']    Optional glyph on the kicker.
 * @param string $args['kicker']
 * @param string $args['meta']
 * @param string $args['title']
 * @param string $args['note']        Optional.
 * @param string $args['duration']    full and lesson.
 * @param string $args['badge_label'] full only.
 * @param string $args['flag']        full only — optional signal word.
 * @param string $args['cta_label']   full only.
 * @param string $args['cta_href']    full only.
 * @param string $args['foot_label']  compact only.
 * @param string $args['href']        compact and lesson — makes the card one <a>.
 * @param string $args['index']       lesson only — paper index, e.g. "01".
 * @param string $args['coach']       lesson only.
 * @param string $args['status']      lesson only. Default WATCH.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_compact_root_base        = 'rounded-none bg-transparent overflow-hidden no-underline text-left group hover:bg-primary';
$lp_compact_root_interactive = 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';

$lp_is_compact = 'compact' === ( $args['variant'] ?? 'full' );
$lp_is_lesson  = 'lesson' === ( $args['variant'] ?? 'full' );

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
					'class'   => 'group-hover:text-neutral',
				)
			);
			?>
		</span>
		<?php if ( '' !== (string) $lp_meta ) : ?>
		<span class="shrink-0">
			<?php
			lp_part(
				'elements/glyph-label',
				array(
					'label'   => $lp_meta,
					'surface' => 'page',
					'tone'    => 'muted',
					'class'   => 'group-hover:text-neutral',
				)
			);
			?>
		</span>
		<?php endif; ?>
	</div>
	<?php
};

if ( $lp_is_lesson ) :
	$lp_kicker   = (string) ( $args['kicker'] ?? 'VAULTING' );
	$lp_title    = (string) ( $args['title'] ?? 'Two Hands, One-foot.' );
	$lp_coach    = (string) ( $args['coach'] ?? '' );
	$lp_duration = (string) ( $args['duration'] ?? '' );
	$lp_status   = (string) ( $args['status'] ?? 'WATCH' );
	$lp_index    = (string) ( $args['index'] ?? '01' );
	$lp_href     = (string) ( $args['href'] ?? '' );
	$lp_is_link  = '' !== $lp_href;
	if ( '' === $lp_status ) {
		$lp_status = 'WATCH';
	}

	$lp_root = lp_classes(
		'flex flex-col w-full bg-secondary border border-neutral-content/10 no-underline text-left overflow-hidden group hover:bg-primary',
		$lp_is_link ? 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary' : ''
	);
	?>
	<?php if ( $lp_is_link ) : ?>
	<a class="<?php echo $lp_root; ?>" data-component="video-card" data-variant="lesson" href="<?php echo esc_url( $lp_href ); ?>">
	<?php else : ?>
	<div class="<?php echo $lp_root; ?>" data-component="video-card" data-variant="lesson">
	<?php endif; ?>
		<div class="relative aspect-[16/9] w-full bg-neutral overflow-hidden border-b border-neutral-content/10">
			<?php
			if ( $lp_has_image ) {
				lp_part(
					'components/media-photo',
					array_merge(
						$lp_photo,
						array(
							'layout' => 'fill',
							'size'   => 'lp_wide',
							'sizes'  => '248px',
						)
					)
				);
			}
			?>
			<?php if ( '' !== $lp_index ) : ?>
			<span class="absolute top-3 left-3">
				<?php
				lp_part(
					'elements/badge',
					array(
						'variant' => 'paper',
						'label'   => $lp_index,
					)
				);
				?>
			</span>
			<?php endif; ?>
			<span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-primary text-primary-content grid place-items-center font-label text-[12px] font-bold" aria-hidden="true">▶</span>
		</div>
		<div class="flex flex-col gap-2.5 px-4 pt-4 pb-3.5">
			<?php if ( '' !== $lp_kicker || '' !== $lp_glyph_id ) : ?>
				<div class="flex items-center gap-2">
					<?php if ( '' !== $lp_glyph_id ) : ?>
						<span class="w-4 h-4 grid place-items-center shrink-0 text-primary group-hover:text-neutral" aria-hidden="true"><?php lp_icon( $lp_glyph_id, 'w-3 h-3' ); ?></span>
					<?php endif; ?>
					<?php if ( '' !== $lp_kicker ) : ?>
						<span class="font-label text-[9px] font-bold tracking-[1.1px] uppercase text-neutral-content/50 group-hover:text-neutral"><?php echo esc_html( $lp_kicker ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<h3 class="font-heading text-[18px] font-semibold tracking-[-0.4px] leading-[1.15] text-neutral-content m-0 group-hover:text-neutral"><?php echo esc_html( $lp_title ); ?></h3>
			<?php if ( '' !== $lp_coach ) : ?>
				<p class="font-label text-[11px] font-normal tracking-[0.2px] text-neutral-content/70 m-0 group-hover:text-neutral"><?php echo esc_html( $lp_coach ); ?></p>
			<?php endif; ?>
			<div class="flex items-center justify-between gap-3 border-t border-neutral-content/10 pt-2.5">
				<?php if ( '' !== $lp_duration ) : ?>
					<span class="font-label text-[10px] font-semibold tracking-[0.6px] text-neutral-content/50 group-hover:text-neutral"><?php echo esc_html( $lp_duration ); ?></span>
				<?php else : ?>
					<span></span>
				<?php endif; ?>
				<span class="font-label text-[10px] font-bold tracking-[0.8px] uppercase text-neutral-content/50 group-hover:text-neutral"><?php echo esc_html( $lp_status ); ?></span>
			</div>
		</div>
	<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
	<?php
	return;
endif;

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
		<figure class="relative aspect-[12/5] w-full bg-base-300 overflow-hidden m-0">
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
			<span class="absolute inset-0 grid place-items-center pointer-events-none" aria-hidden="true">
				<?php lp_part( 'elements/icon-circle', array( 'variant' => '34' ) ); ?>
			</span>
		</figure>
		<div class="card-body p-0 pt-[19px] gap-[13px]">
			<?php $lp_head_row( $lp_kicker, $lp_meta, $lp_glyph_id ); ?>
			<h3 class="card-title font-heading text-[22px] font-medium tracking-[-0.4px] leading-none text-base-content group-hover:text-neutral"><?php echo esc_html( $lp_title ); ?></h3>
			<?php if ( '' !== $lp_note ) : ?>
				<p class="font-body text-[12px] font-normal tracking-[0.1px] leading-normal text-base-content/70 group-hover:text-neutral"><?php echo esc_html( $lp_note ); ?></p>
			<?php endif; ?>
			<div class="flex items-center justify-between gap-3 pt-[15px]">
				<span class="font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-base-content group-hover:text-neutral"><?php echo esc_html( $lp_foot_label ); ?></span>
				<?php lp_part( 'elements/chevron', array( 'variant' => 'media_card_static', 'class' => 'group-hover:text-neutral' ) ); ?>
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
<article class="card rounded-none bg-base-200 border border-base-300 overflow-hidden group hover:bg-primary" data-component="video-card" data-variant="full">
	<figure class="relative aspect-video w-full bg-base-300 overflow-hidden m-0">
		<?php
		if ( $lp_has_image ) {
			lp_part(
				'components/media-photo',
				array_merge(
					$lp_photo,
					array(
						'scrim' => 'video_full',
						'sizes' => '(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw',
					)
				)
			);
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
		<h3 class="card-title font-heading text-[22px] font-bold tracking-[-0.5px] leading-none text-base-content group-hover:text-neutral"><?php echo esc_html( $lp_title ); ?></h3>
		<?php if ( '' !== $lp_note ) : ?>
			<p class="font-body text-[12px] font-normal tracking-[0.1px] leading-normal text-base-content/65 group-hover:text-neutral"><?php echo esc_html( $lp_note ); ?></p>
		<?php endif; ?>
		<div class="pt-[14px] border-t border-base-300">
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
