<?php
/**
 * Byline — initial avatar (or photo) + name + secondary line.
 *
 * Ported from src/stories/Components/Byline/Byline.js.
 *
 * `size` is one axis carrying avatar box + name weight/size + secondary
 * treatment together, because they always co-occur in the source — never an
 * md avatar with sm typography. `surface` is the colour axis, the same idiom
 * glyph-label and status use.
 *
 * The avatar is decorative — an initial standing in for a photo, not content —
 * so it is aria-hidden and `name` is the only thing carrying identity to
 * assistive tech. Never render an avatar-only byline.
 *
 * DELIBERATE DEPARTURE: the source takes `photo` as a raw URL and writes a bare
 * <img>. Here a photo goes through parts/components/media-photo.php with an
 * attachment id (Port Brief rule 3b) so it gets srcset for free; `photo_url`
 * stays as a fallback for a URL with no attachment behind it. The avatar box is
 * a fixed size the caller owns, so it uses media-photo's layout='none'.
 *
 * The initial avatar itself lives in parts/elements/avatar-initial.php, shared
 * with blog-card — see that file for why blog-card composes the avatar rather
 * than this whole component (docs/CONSOLIDATION.md §4b's suggestion, which
 * would have printed the author's name twice).
 *
 * @param string $args['name']      Default 'Andy Pearson'.
 * @param string $args['secondary'] Role or date line; omit to render none.
 * @param string $args['bio']       Optional third line (size lg / "Your Coach").
 * @param string $args['size']      md|sm|lg. Default 'md'.
 * @param string $args['surface']   page|board|accent. Default 'page'.
 * @param int    $args['photo_id']  Attachment id — renders a photo avatar.
 * @param string $args['photo_url'] Raw URL fallback when there is no attachment.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'page'   => array(
		'name'      => 'text-base-content',
		'secondary' => 'text-base-content/65',
	),
	'board'  => array(
		'name'      => 'text-neutral-content',
		'secondary' => 'text-neutral-content/50',
	),
	'accent' => array(
		'name'      => 'text-accent-content',
		'secondary' => 'text-accent-content/70',
	),
);

$lp_sizes = array(
	'md' => array(
		'gap'         => 'gap-[14px]',
		'avatar_box'  => 'w-[34px] h-[34px]',
		'name'        => 'font-heading text-[16px] font-medium tracking-[-0.2px]',
		'secondary'   => 'font-label text-[10px] font-normal tracking-[0.9px] uppercase',
		'bio'         => 'font-body text-[11px] font-normal leading-[1.5] tracking-[0.1px]',
		'crop'        => 'lp_thumb',
		'sizes_attr'  => '34px',
		'align'       => 'items-center',
	),
	'sm' => array(
		'gap'         => 'gap-[12px]',
		'avatar_box'  => 'w-[26px] h-[26px]',
		'name'        => 'font-heading text-[12px] font-semibold tracking-[0.1px]',
		'secondary'   => 'font-label text-[12px] font-normal tracking-[0.1px]',
		'bio'         => 'font-body text-[10px] font-normal leading-[1.4] tracking-[0.1px]',
		'crop'        => 'lp_thumb',
		'sizes_attr'  => '26px',
		'align'       => 'items-center',
	),
	// "Your Coach" (Classes/Class Detail) — a 104×126 portrait, not a square.
	// Bio is short prose (first two sentences at the call site), so size/leading
	// read as body copy rather than a 11px caption.
	'lg' => array(
		'gap'         => 'gap-[20px]',
		'avatar_box'  => 'w-[104px] h-[126px]',
		'name'        => 'font-heading text-[26px] font-semibold tracking-[-0.8px]',
		'secondary'   => 'font-label text-[10px] font-normal tracking-[1px] uppercase',
		'bio'         => 'font-body text-[14px] font-normal leading-[1.65] tracking-[0.1px] max-w-[36rem]',
		'crop'        => 'lp_portrait_sm',
		'sizes_attr'  => '104px',
		'align'       => 'items-start',
	),
);

$lp_size_key    = (string) ( $args['size'] ?? 'md' );
$lp_surface_key = (string) ( $args['surface'] ?? 'page' );
$lp_size = $lp_sizes[ $lp_size_key ] ?? $lp_sizes['md'];
$lp_surf = $lp_surfaces[ $lp_surface_key ] ?? $lp_surfaces['page'];

$lp_name      = (string) ( $args['name'] ?? 'Andy Pearson' );
$lp_secondary = (string) ( $args['secondary'] ?? 'HEAD COACH' );
$lp_bio       = (string) ( $args['bio'] ?? '' );
$lp_photo_id  = ! empty( $args['photo_id'] ) ? (int) $args['photo_id'] : 0;
$lp_photo_url = (string) ( $args['photo_url'] ?? '' );
$lp_has_photo = $lp_photo_id || '' !== $lp_photo_url;

// The source tightens the text column only when a bio is present, so every
// existing two-line call site still renders byte-identically.
$lp_column_gap = '' !== $lp_bio ? 'gap-[9px]' : 'gap-[2px]';
?>
<div class="<?php echo lp_classes( 'flex', $lp_size['align'], $lp_size['gap'] ); ?>" data-component="byline">
	<?php if ( $lp_has_photo ) : ?>
		<?php /* daisyUI sizes via a nested box — classes on the photo alone expand. */ ?>
		<div class="avatar shrink-0" aria-hidden="true">
			<div class="<?php echo esc_attr( $lp_size['avatar_box'] . ' shrink-0 overflow-hidden' ); ?>">
				<?php
				lp_part(
					'components/media-photo',
					array(
						'image_id'  => $lp_photo_id,
						'image_url' => $lp_photo_url,
						'alt'       => '',
						'layout'    => 'none',
						'class'     => 'w-full h-full object-cover',
						'size'      => $lp_size['crop'],
						'sizes'     => $lp_size['sizes_attr'],
					)
				);
				?>
			</div>
		</div>
	<?php else : ?>
		<?php
		lp_part(
			'elements/avatar-initial',
			array(
				'name'    => $lp_name,
				'size'    => $lp_size_key,
				'surface' => $lp_surface_key,
			)
		);
		?>
	<?php endif; ?>
	<div class="<?php echo lp_classes( 'flex flex-col', $lp_column_gap ); ?>">
		<span class="<?php echo lp_classes( $lp_size['name'], $lp_surf['name'] ); ?>"><?php echo esc_html( $lp_name ); ?></span>
		<?php if ( '' !== $lp_secondary ) : ?>
			<span class="<?php echo lp_classes( $lp_size['secondary'], $lp_surf['secondary'] ); ?>"><?php echo esc_html( $lp_secondary ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $lp_bio ) : ?>
			<p class="<?php echo lp_classes( $lp_size['bio'], $lp_surf['secondary'], 'm-0' ); ?>"><?php echo esc_html( $lp_bio ); ?></p>
		<?php endif; ?>
	</div>
</div>
