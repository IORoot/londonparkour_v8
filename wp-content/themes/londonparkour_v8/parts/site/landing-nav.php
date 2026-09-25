<?php
/**
 * Landing header — logo, Classes, Private 1:1.
 *
 * Ported from src/stories/Site/LandingNav/LandingNav.js.
 * On a phone the two links sit in a short menu and Book stays in the bar.
 *
 * @param string $args['brand']
 * @param string $args['home_href']
 * @param string $args['classes_href']
 * @param string $args['private_href']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_focus       = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
$lp_focus_inset = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';

$lp_brand    = (string) ( $args['brand'] ?? 'London Parkour' );
$lp_home     = (string) ( $args['home_href'] ?? home_url( '/' ) );
$lp_classes  = (string) ( $args['classes_href'] ?? '/classes/' );
$lp_private  = (string) ( $args['private_href'] ?? '/private-coaching/' );
$lp_menu_id  = 'landing-menu';
?>
<header data-component="landing-nav" class="sticky top-0 z-50 bg-neutral">
	<nav aria-label="<?php esc_attr_e( 'Landing', 'londonparkour_v8' ); ?>">
		<div class="hidden lg:grid lg:grid-cols-3 items-stretch border-b border-neutral-content/10 h-[76px]">
			<a href="<?php echo esc_url( $lp_home ); ?>" aria-label="<?php echo esc_attr( $lp_brand ); ?>" class="<?php echo lp_classes( 'flex items-center pl-[64px] text-neutral-content hover:text-primary transition-colors duration-150', $lp_focus ); ?>">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => 177,
						'color_class' => 'text-current',
						'label'       => $lp_brand,
					)
				);
				?>
			</a>
			<div class="flex items-center justify-center gap-[28px]">
				<?php
				lp_part(
					'elements/nav-link',
					array(
						'label'   => 'CLASSES',
						'href'    => $lp_classes,
						'icon_id' => 'glyph-vaulting',
					)
				);
				lp_part(
					'elements/nav-link',
					array(
						'label'   => 'PRIVATE 1:1',
						'href'    => $lp_private,
						'icon_id' => 'glyph-holistic',
					)
				);
				?>
			</div>
			<div class="flex items-stretch justify-end"></div>
		</div>

		<div class="flex lg:hidden items-stretch justify-between h-[60px] border-b border-neutral-content/10 pl-[20px]">
			<a href="<?php echo esc_url( $lp_home ); ?>" aria-label="<?php echo esc_attr( $lp_brand ); ?>" class="<?php echo lp_classes( 'flex items-center text-neutral-content hover:text-primary transition-colors duration-150', $lp_focus ); ?>">
				<?php
				lp_part(
					'brand/logo',
					array(
						'width'       => 133,
						'color_class' => 'text-current',
						'label'       => $lp_brand,
					)
				);
				?>
			</a>
			<div class="flex items-stretch">
				<button type="button" command="show-modal" commandfor="<?php echo esc_attr( $lp_menu_id ); ?>" aria-haspopup="dialog" aria-label="<?php esc_attr_e( 'Open menu', 'londonparkour_v8' ); ?>"
					class="<?php echo lp_classes( 'inline-flex items-center justify-center w-[60px] border-l border-neutral-content/15 text-neutral-content hover:bg-primary hover:text-neutral transition-colors duration-150', $lp_focus_inset ); ?>">
					<?php lp_icon( 'icon-bars-3', 'w-[20px] h-[20px]' ); ?>
				</button>
			</div>
		</div>
	</nav>
</header>

<el-dialog>
	<dialog id="<?php echo esc_attr( $lp_menu_id ); ?>" aria-label="<?php esc_attr_e( 'Menu', 'londonparkour_v8' ); ?>" class="m-0 p-0 backdrop:bg-neutral/60 lg:hidden">
		<div tabindex="0" class="fixed inset-0 focus:outline-0">
			<el-dialog-panel class="fixed inset-y-0 right-0 z-50 flex h-full w-full max-w-sm flex-col overflow-y-auto bg-neutral p-[24px]">
				<div class="flex items-center justify-between">
					<span class="flex items-center text-neutral-content">
						<?php
						lp_part(
							'brand/logo',
							array(
								'width'       => 133,
								'color_class' => 'text-neutral-content',
								'label'       => $lp_brand,
							)
						);
						?>
					</span>
					<button type="button" command="close" commandfor="<?php echo esc_attr( $lp_menu_id ); ?>" aria-label="<?php esc_attr_e( 'Close menu', 'londonparkour_v8' ); ?>"
						class="<?php echo lp_classes( 'inline-flex items-center justify-center w-[40px] h-[40px] text-neutral-content hover:bg-primary hover:text-neutral transition-colors duration-150', $lp_focus ); ?>">
						<?php lp_icon( 'icon-x-mark', 'w-[20px] h-[20px]' ); ?>
					</button>
				</div>
				<nav aria-label="<?php esc_attr_e( 'Landing', 'londonparkour_v8' ); ?>" class="mt-[32px] flex flex-col gap-[16px]">
					<?php
					lp_part(
						'elements/nav-link',
						array(
							'label'   => 'CLASSES',
							'href'    => $lp_classes,
							'icon_id' => 'glyph-vaulting',
						)
					);
					lp_part(
						'elements/nav-link',
						array(
							'label'   => 'PRIVATE 1:1',
							'href'    => $lp_private,
							'icon_id' => 'glyph-holistic',
						)
					);
					?>
				</nav>
			</el-dialog-panel>
		</div>
	</dialog>
</el-dialog>
