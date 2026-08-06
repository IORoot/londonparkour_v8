<?php
/**
 * Coupons purchase list — [clasbpro_coupons] / [clasbpro_coupons id="1,2"].
 *
 * @var array<int, array<string, mixed>> $packs
 * @var bool                             $show_heading
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

$packs_classes = trim( 'cbfs-packs ' . \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::get_wrapper_class() );
?>
<div class="<?php echo esc_attr( $packs_classes ); ?>" data-cbfs-packs="1">
	<?php if ( $show_heading ) : ?>
		<h2 class="cbfs-packs__heading"><?php esc_html_e( 'Coupons', 'class-bookings-with-stripe-pro' ); ?></h2>
	<?php endif; ?>

	<?php if ( empty( $packs ) ) : ?>
		<p class="cbfs-packs__empty"><?php esc_html_e( 'No coupons are available right now.', 'class-bookings-with-stripe-pro' ); ?></p>
	<?php else : ?>
		<ul class="cbfs-packs__list">
			<?php foreach ( $packs as $pack ) : ?>
				<li class="cbfs-packs__item" data-cbfs-pack-id="<?php echo esc_attr( (string) $pack['id'] ); ?>">
					<div class="cbfs-packs__item-body">
						<h3 class="cbfs-packs__title"><?php echo esc_html( (string) $pack['name'] ); ?></h3>
						<?php if ( ! empty( $pack['description'] ) ) : ?>
							<p class="cbfs-packs__description"><?php echo esc_html( (string) $pack['description'] ); ?></p>
						<?php endif; ?>
						<p class="cbfs-packs__meta">
							<?php
							printf(
								/* translators: 1: number of uses, 2: unit price */
								esc_html__( '%1$d classes · for classes priced %2$s', 'class-bookings-with-stripe-pro' ),
								(int) $pack['uses'],
								esc_html( \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::format_price( $pack['unit_price'] ) )
							);
							?>
							<?php if ( (int) $pack['expiry_months'] > 0 ) : ?>
								·
								<?php
								printf(
									/* translators: %d: months until expiry */
									esc_html__( 'expires %d months after purchase', 'class-bookings-with-stripe-pro' ),
									(int) $pack['expiry_months']
								);
								?>
							<?php endif; ?>
						</p>
						<p class="cbfs-packs__price"><?php echo esc_html( \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::format_price( $pack['price'] ) ); ?></p>
					</div>
					<form class="cbfs-packs__form" novalidate>
						<label class="cbfs-packs__label">
							<span><?php esc_html_e( 'Your name', 'class-bookings-with-stripe-pro' ); ?></span>
							<input class="cbfs-packs__input" type="text" name="customer_name" autocomplete="name" required />
						</label>
						<label class="cbfs-packs__label">
							<span><?php esc_html_e( 'Email address', 'class-bookings-with-stripe-pro' ); ?></span>
							<input class="cbfs-packs__input" type="email" name="customer_email" autocomplete="email" required />
						</label>
						<button type="submit" class="cbfs-packs__button">
							<span class="cbfs-packs__button-label"><?php esc_html_e( 'Buy with Stripe', 'class-bookings-with-stripe-pro' ); ?></span>
						</button>
						<p class="cbfs-packs__error" hidden></p>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
