<?php
/**
 * SiteFooter — the fixed dark band closing every page: wordmark + tagline,
 * three link columns, hairline, copyright and social row.
 *
 * Ported from src/stories/Site/SiteFooter/SiteFooter.js.
 *
 * Fixed dark band (`bg-neutral`), so every content colour is `neutral-content`
 * flavoured, never `base-content` — the two collapse onto one hex in both light
 * themes.
 *
 * RESOLVED HERE: the source vendors three brand `<svg>`s inline because "no
 * icon set already carries brand marks". That was true — neither `icons.svg`
 * (328 symbols) nor `glyphs.svg` (67) had Instagram, YouTube or Facebook. The
 * fix is not an exemption from the no-raw-`<svg>` rule but the missing symbols:
 * `icon-instagram`, `icon-youtube` and `icon-facebook` were added to
 * `assets/img/icons.svg` from that same .pen-extracted path geometry, so these
 * go through `lp_icon()` like every other mark. See PORT-FINDINGS §10.
 *
 * Link-column items are deliberately NOT `elements/nav-link.php`: the design
 * renders them as 18px normal-weight body text, not nav-link's 12px/600
 * uppercase label voice. Forcing nav-link here would invent a variant the spec
 * does not have.
 *
 * The heading id is per-instance so each column's `<ul>` can be
 * `aria-labelledby` its own `<h2>`; two footers on one page would otherwise
 * collide.
 *
 * @param string $args['brand_href']
 * @param string $args['brand_label']
 * @param int    $args['logo_width']
 * @param string $args['tagline']
 * @param array  $args['columns']    Rows of heading + links[label/href].
 * @param array  $args['social']     Rows of platform/href.
 * @param string $args['copyright']
 * @param string $args['instance_id']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_focus       = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$lp_link_class  = 'font-body text-[18px] font-normal text-neutral-content hover:text-primary transition-colors duration-150';
$lp_social_link = 'inline-flex items-center justify-center text-neutral-content/50 hover:text-primary transition-colors duration-150';

// Brand marks now live in the sprite — see the docblock.
$lp_social_icons = array(
	'Instagram' => 'icon-instagram',
	'YouTube'   => 'icon-youtube',
	'Facebook'  => 'icon-facebook',
);

$lp_default_columns = array(
	array(
		'heading' => 'Practice',
		'links'   => array(
			array(
				'label' => 'Classes',
				'href'  => '/classes',
			),
			array(
				'label' => 'Private Tuition',
				'href'  => '/private-tuition',
			),
			array(
				'label' => 'Workshops',
				'href'  => '/workshops/',
			),
			array(
				'label' => 'Tutorials',
				'href'  => '/tutorials',
			),
			array(
				'label' => 'Timetable',
				'href'  => '/timetable',
			),
		),
	),
	array(
		'heading' => 'Explore',
		'links'   => array(
			array(
				'label' => 'Community',
				'href'  => '/community',
			),
			array(
				'label' => 'Blog',
				'href'  => '/blog',
			),
			array(
				'label' => 'Class Maps',
				'href'  => '/classes-map',
			),
			array(
				'label' => 'Gift Cards',
				'href'  => '/gift-cards',
			),
		),
	),
	array(
		'heading' => 'Studio',
		'links'   => array(
			array(
				'label' => 'About',
				'href'  => '/about',
			),
			array(
				'label' => 'Coaches',
				'href'  => '/coaches',
			),
			array(
				'label' => 'Docs',
				'href'  => '/docs',
			),
			array(
				'label' => 'Contact',
				'href'  => '/contact',
			),
		),
	),
);

$lp_default_social = array(
	array(
		'platform' => 'Instagram',
		'href'     => 'https://instagram.com/londonparkour',
	),
	array(
		'platform' => 'YouTube',
		'href'     => 'https://youtube.com/@londonparkour',
	),
	array(
		'platform' => 'Facebook',
		'href'     => 'https://facebook.com/londonparkour',
	),
);

$lp_brand_href  = (string) ( $args['brand_href'] ?? '/' );
$lp_brand_label = (string) ( $args['brand_label'] ?? 'London Parkour' );
$lp_logo_width  = (int) ( $args['logo_width'] ?? 210 );
$lp_tagline     = (string) ( $args['tagline'] ?? 'Natural movement, coached across London since 2005. All ages, all levels — from first steps to advanced flow.' );
$lp_copyright   = (string) ( $args['copyright'] ?? '© 2026 LONDONPARKOUR — ALL RIGHTS RESERVED' );
$lp_instance    = sanitize_html_class( (string) ( $args['instance_id'] ?? 'site-footer' ) );

$lp_columns = is_array( $args['columns'] ?? null ) && $args['columns'] ? $args['columns'] : $lp_default_columns;
$lp_social  = is_array( $args['social'] ?? null ) && $args['social'] ? $args['social'] : $lp_default_social;
?>
<footer class="w-full bg-neutral" aria-label="<?php esc_attr_e( 'Site footer', 'londonparkour_v8' ); ?>" data-component="site-footer" id="<?php echo esc_attr( $lp_instance ); ?>">
	<div class="flex flex-col gap-10 lg:gap-[52px] px-6 lg:px-16 py-10 lg:py-[72px]">

		<div class="flex flex-col lg:flex-row lg:items-start justify-between gap-10 lg:gap-[64px]">
			<div class="flex flex-col gap-[20px] w-full lg:w-[320px] lg:shrink-0">
				<a href="<?php echo esc_url( $lp_brand_href ); ?>" class="<?php echo lp_classes( 'inline-flex items-center w-fit text-neutral-content hover:text-primary transition-colors duration-150', $lp_focus ); ?>" aria-label="<?php echo esc_attr( $lp_brand_label ); ?> — <?php esc_attr_e( 'Home', 'londonparkour_v8' ); ?>">
					<?php
					lp_part(
						'brand/logo',
						array(
							'width'       => $lp_logo_width,
							'color_class' => 'text-current',
							'label'       => $lp_brand_label,
						)
					);
					?>
				</a>
				<p class="font-body text-[14px] leading-[1.6] text-neutral-content/50 m-0"><?php echo esc_html( $lp_tagline ); ?></p>
			</div>

			<nav aria-label="<?php esc_attr_e( 'Footer', 'londonparkour_v8' ); ?>" class="flex flex-col sm:flex-row gap-10 lg:gap-[72px]">
				<?php
				foreach ( $lp_columns as $lp_i => $lp_column ) :
					$lp_heading_id = $lp_instance . '-col-' . (int) $lp_i;
					?>
					<div class="flex flex-col gap-[16px]">
						<h2 id="<?php echo esc_attr( $lp_heading_id ); ?>" class="font-label text-[12px] font-normal uppercase tracking-[1px] text-neutral-content/50 m-0"><?php echo esc_html( (string) ( $lp_column['heading'] ?? '' ) ); ?></h2>
						<ul class="flex flex-col gap-[16px] m-0 p-0 list-none" aria-labelledby="<?php echo esc_attr( $lp_heading_id ); ?>">
							<?php foreach ( is_array( $lp_column['links'] ?? null ) ? $lp_column['links'] : array() as $lp_link ) : ?>
								<li>
									<a href="<?php echo esc_url( (string) ( $lp_link['href'] ?? '#' ) ); ?>" class="<?php echo lp_classes( $lp_link_class, $lp_focus ); ?>"><?php echo esc_html( (string) ( $lp_link['label'] ?? '' ) ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</nav>
		</div>

		<div class="flex flex-col gap-[22px]">
			<div class="w-full h-px bg-neutral-content/10" aria-hidden="true"></div>
			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
				<p class="font-label text-[12px] font-normal uppercase tracking-[0.5px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_copyright ); ?></p>
				<ul class="flex items-center gap-[18px] m-0 p-0 list-none">
					<?php
					foreach ( $lp_social as $lp_item ) :
						$lp_platform = (string) ( $lp_item['platform'] ?? '' );
						$lp_icon_id  = $lp_social_icons[ $lp_platform ] ?? reset( $lp_social_icons );
						?>
						<li>
							<a href="<?php echo esc_url( (string) ( $lp_item['href'] ?? '#' ) ); ?>" class="<?php echo lp_classes( $lp_social_link, $lp_focus ); ?>" aria-label="<?php echo esc_attr( $lp_platform ); ?>"><?php lp_icon( $lp_icon_id, 'w-[18px] h-[18px]' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

	</div>
</footer>
