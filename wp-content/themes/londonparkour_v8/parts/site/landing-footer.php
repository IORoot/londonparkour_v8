<?php
/**
 * Landing footer — logo, email, privacy, terms, social, copyright.
 *
 * Ported from src/stories/Site/LandingFooter/LandingFooter.js.
 * A phone line is printed only when one is passed.
 *
 * @param string $args['brand_href']
 * @param string $args['brand_label']
 * @param string $args['email']
 * @param string $args['phone']
 * @param string $args['privacy_href']
 * @param string $args['terms_href']
 * @param string $args['copyright']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_focus = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$lp_link  = 'font-body text-[18px] font-normal text-neutral-content hover:text-primary transition-colors duration-150';
$lp_social_link = 'inline-flex items-center justify-center text-neutral-content/50 hover:text-primary transition-colors duration-150';

$lp_brand_href  = (string) ( $args['brand_href'] ?? home_url( '/' ) );
$lp_brand_label = (string) ( $args['brand_label'] ?? 'London Parkour' );
$lp_email       = (string) ( $args['email'] ?? 'contact@londonparkour.com' );
$lp_phone       = trim( (string) ( $args['phone'] ?? '' ) );
$lp_privacy     = (string) ( $args['privacy_href'] ?? home_url( '/legal/' ) );
$lp_terms       = (string) ( $args['terms_href'] ?? home_url( '/legal/' ) );
$lp_copyright   = (string) ( $args['copyright'] ?? '© 2026 LONDONPARKOUR — ALL RIGHTS RESERVED' );
$lp_phone_href  = '' !== $lp_phone ? 'tel:' . preg_replace( '/[^\d+]/', '', $lp_phone ) : '';

$lp_social = array(
	array(
		'platform' => 'Instagram',
		'href'     => 'https://www.instagram.com/london_parkour',
		'icon'     => 'icon-instagram',
	),
	array(
		'platform' => 'YouTube',
		'href'     => 'https://youtube.com/@londonparkour',
		'icon'     => 'icon-youtube',
	),
	array(
		'platform' => 'Facebook',
		'href'     => 'https://www.facebook.com/ldnpk',
		'icon'     => 'icon-facebook',
	),
);
?>
<footer class="w-full bg-neutral" aria-label="<?php esc_attr_e( 'Landing footer', 'londonparkour_v8' ); ?>" data-component="landing-footer">
	<div class="flex flex-col gap-6 px-6 lg:px-16 py-10 lg:py-12">
		<div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
			<a href="<?php echo esc_url( $lp_brand_href ); ?>" class="<?php echo lp_classes( 'inline-flex items-center w-fit text-neutral-content hover:text-primary transition-colors duration-150', $lp_focus ); ?>" aria-label="<?php echo esc_attr( $lp_brand_label ); ?> — <?php esc_attr_e( 'Home', 'londonparkour_v8' ); ?>">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => 177,
						'color_class' => 'text-current',
						'label'       => $lp_brand_label,
					)
				);
				?>
			</a>
			<a href="<?php echo esc_url( 'mailto:' . $lp_email ); ?>" class="<?php echo lp_classes( $lp_link, $lp_focus ); ?>"><?php echo esc_html( $lp_email ); ?></a>
		</div>
		<?php if ( '' !== $lp_phone ) : ?>
			<a href="<?php echo esc_url( $lp_phone_href ); ?>" class="<?php echo lp_classes( $lp_link, $lp_focus ); ?>"><?php echo esc_html( $lp_phone ); ?></a>
		<?php endif; ?>
		<nav aria-label="<?php esc_attr_e( 'Legal', 'londonparkour_v8' ); ?>" class="flex flex-col gap-3 lg:flex-row lg:gap-6">
			<a href="<?php echo esc_url( $lp_privacy ); ?>" class="<?php echo lp_classes( $lp_link, $lp_focus ); ?>"><?php esc_html_e( 'Privacy policy', 'londonparkour_v8' ); ?></a>
			<a href="<?php echo esc_url( $lp_terms ); ?>" class="<?php echo lp_classes( $lp_link, $lp_focus ); ?>"><?php esc_html_e( 'Terms of service', 'londonparkour_v8' ); ?></a>
		</nav>
		<div class="w-full h-px bg-neutral-content/10" aria-hidden="true"></div>
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<p class="font-label text-[12px] font-normal uppercase tracking-[0.5px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_copyright ); ?></p>
			<ul class="flex items-center gap-[18px] m-0 p-0 list-none">
				<?php foreach ( $lp_social as $lp_item ) : ?>
					<li>
						<a href="<?php echo esc_url( $lp_item['href'] ); ?>" class="<?php echo lp_classes( $lp_social_link, $lp_focus ); ?>" aria-label="<?php echo esc_attr( $lp_item['platform'] ); ?>">
							<?php lp_icon( $lp_item['icon'], 'w-[18px] h-[18px]' ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</footer>
