<?php
/**
 * BlogCard — the article card, in a bordered grid form and a large lead form.
 *
 * Ported from src/stories/Components/BlogCard/BlogCard.js.
 *
 * DELIBERATE DEPARTURE: the source hand-rolls its author avatar box, which is
 * byte-identical to byline's `sm` size (docs/CONSOLIDATION.md §4b). §4b asked
 * for this card to compose byline itself, but byline always renders the
 * person's NAME beside the avatar and this card renders that name in its own
 * deliberately different voice (font-body, not font-heading) — composing it
 * printed the name twice. The avatar alone is therefore shared, via
 * parts/elements/avatar-initial.php, and the name/date row stays here.
 * The source does not aria-hide this avatar (byline does), so it passes
 * decorative => false to keep that as-is.
 *
 * The category kicker composes elements/glyph-label.php; both the grid CTA
 * and the lead CTA compose elements/button.php (primary, READ ARTICLE).
 *
 * @param string $args['variant']           grid|lead. Default 'grid'.
 * @param int    $args['image_id']          Attachment id — enables srcset.
 * @param string $args['image_url']         Raw URL fallback.
 * @param string $args['image_alt']         Omit to inherit the attachment's alt.
 * @param string $args['category']
 * @param string $args['category_glyph_id'] Default 'icon-tag'.
 * @param string $args['read_time']         grid only.
 * @param string $args['title']
 * @param string $args['excerpt']
 * @param string $args['author']
 * @param string $args['date']
 * @param string $args['href']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_variants = array(
	'grid' => array(
		'root'        => 'card flex flex-col h-full bg-base-200 border border-base-300 rounded-none overflow-hidden',
		'media'       => 'aspect-[16/9] w-full bg-base-300 overflow-hidden m-0',
		'body'        => 'card-body flex flex-col flex-1 p-0 pt-[18px] px-[16px] pb-[16px] gap-[14px]',
		'read_time'   => 'font-label text-[10px] font-normal uppercase tracking-[0.9px] text-base-content/65',
		'title'       => 'card-title font-heading text-[22px] font-bold tracking-[-0.5px] leading-tight text-base-content',
		'excerpt'     => 'font-body text-[12px] font-normal tracking-[0.1px] leading-[1.6] text-base-content/65',
		'foot_wrap'   => 'mt-auto flex flex-col gap-[14px] border-t border-base-300 pt-[14px]',
		'author_name' => 'font-body text-[12px] font-semibold tracking-[0.1px] text-base-content',
		'author_date' => 'font-body text-[12px] font-normal tracking-[0.1px] text-base-content/65',
		// byline `sm` on this surface produces the source's exact avatar box.
		'avatar_surf' => 'board',
		'kicker_surf' => 'page',
		'kicker_tone' => 'ink',
	),
	'lead' => array(
		'root'        => 'flex flex-col lg:flex-row gap-[40px] lg:gap-[64px]',
		'media'       => 'aspect-[16/9] w-full lg:w-3/5 overflow-hidden m-0 shrink-0',
		'body'        => 'flex flex-col gap-[22px] w-full lg:w-2/5',
		'read_time'   => '',
		'title'       => 'font-heading text-[36px] lg:text-[43px] font-bold tracking-[-1.6px] leading-none text-accent-content',
		'excerpt'     => 'font-body text-[14px] font-normal tracking-[0.15px] leading-[1.65] text-accent-content/70',
		'foot_wrap'   => 'flex items-center gap-3 border-t border-accent-content/15 pt-[22px]',
		'spacer'      => 'max-lg:hidden flex-1 min-h-0',
		'author_name' => 'font-body text-[12px] font-semibold tracking-[0.1px] text-accent-content',
		'author_date' => 'font-body text-[12px] font-normal tracking-[0.1px] text-accent-content/70',
		'avatar_surf' => 'accent',
		'kicker_surf' => 'accent',
		'kicker_tone' => 'muted',
	),
);

$lp_variant_key = (string) ( $args['variant'] ?? 'grid' );
$lp_v           = $lp_variants[ $lp_variant_key ] ?? $lp_variants['grid'];
$lp_is_lead     = 'lead' === $lp_variant_key;

$lp_category  = (string) ( $args['category'] ?? 'PROJECT' );
$lp_read_time = (string) ( $args['read_time'] ?? '3 MIN READ' );
$lp_title     = (string) ( $args['title'] ?? 'Imperial College London' );
$lp_excerpt   = (string) ( $args['excerpt'] ?? 'LondonParkour is teaming up with Imperial College London to bring parkour classes to students every Wednesday.' );
$lp_author    = (string) ( $args['author'] ?? 'Andy Pearson' );
$lp_date      = (string) ( $args['date'] ?? 'Nov 19, 2024' );
$lp_href      = (string) ( $args['href'] ?? '#' );

$lp_photo = array(
	'image_id'  => ! empty( $args['image_id'] ) ? (int) $args['image_id'] : 0,
	'image_url' => (string) ( $args['image_url'] ?? '' ),
	'element'   => 'img',
	'layout'    => 'none',
	'class'     => 'w-full h-full object-cover',
	'size'      => 'lp_wide',
	'sizes'     => $lp_is_lead ? '(min-width: 1024px) 60vw, 100vw' : '(min-width: 1024px) 33vw, 100vw',
);

if ( array_key_exists( 'image_alt', $args ) ) {
	$lp_photo['alt'] = (string) $args['image_alt'];
}

/** Avatar + name + date. The avatar is byline's; the text is this card's own. */
$lp_author_row = static function () use ( $lp_v, $lp_author, $lp_date ) {
	?>
	<div class="flex items-center gap-3">
		<?php
		lp_part(
			'elements/avatar-initial',
			array(
				'name'       => $lp_author,
				'size'       => 'sm',
				'surface'    => $lp_v['avatar_surf'],
				'decorative' => false,
			)
		);
		?>
		<span class="<?php echo esc_attr( $lp_v['author_name'] ); ?>"><?php echo esc_html( $lp_author ); ?></span>
		<span class="<?php echo esc_attr( $lp_v['author_date'] ); ?>" aria-hidden="true">·</span>
		<span class="<?php echo esc_attr( $lp_v['author_date'] ); ?>"><?php echo esc_html( $lp_date ); ?></span>
	</div>
	<?php
};
?>
<article class="<?php echo esc_attr( $lp_v['root'] ); ?>" data-component="blog-card" data-variant="<?php echo esc_attr( $lp_variant_key ); ?>">
	<figure class="<?php echo esc_attr( $lp_v['media'] ); ?>"><?php lp_part( 'components/media-photo', $lp_photo ); ?></figure>
	<div class="<?php echo esc_attr( $lp_v['body'] ); ?>">
		<div class="flex items-center justify-between gap-3">
			<span class="min-w-0">
				<?php
				lp_part(
					'elements/glyph-label',
					array(
						'label'   => $lp_category,
						'icon_id' => $args['category_glyph_id'] ?? 'icon-tag',
						'surface' => $lp_v['kicker_surf'],
						'tone'    => $lp_v['kicker_tone'],
					)
				);
				?>
			</span>
			<?php if ( ! $lp_is_lead ) : ?>
				<span class="<?php echo lp_classes( $lp_v['read_time'], 'shrink-0' ); ?>"><?php echo esc_html( $lp_read_time ); ?></span>
			<?php endif; ?>
		</div>
		<h3 class="<?php echo esc_attr( $lp_v['title'] ); ?>"><?php echo esc_html( $lp_title ); ?></h3>
		<p class="<?php echo esc_attr( $lp_v['excerpt'] ); ?>"><?php echo esc_html( $lp_excerpt ); ?></p>
		<?php if ( $lp_is_lead ) : ?>
			<div class="<?php echo esc_attr( $lp_v['spacer'] ); ?>" aria-hidden="true"></div>
			<div class="<?php echo esc_attr( $lp_v['foot_wrap'] ); ?>"><?php $lp_author_row(); ?></div>
			<div class="grid">
				<?php
				lp_part(
					'elements/button',
					array(
						'variant'          => 'primary',
						'label'            => 'READ ARTICLE',
						'trailing_icon_id' => 'icon-arrow-right',
						'href'             => $lp_href,
					)
				);
				?>
			</div>
		<?php else : ?>
			<div class="<?php echo esc_attr( $lp_v['foot_wrap'] ); ?>">
				<?php $lp_author_row(); ?>
				<div class="grid">
					<?php
					lp_part(
						'elements/button',
						array(
							'variant'          => 'primary',
							'label'            => 'READ ARTICLE',
							'trailing_icon_id' => 'icon-arrow-right',
							'href'             => $lp_href,
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</article>
