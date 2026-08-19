<?php
/**
 * Coupons purchase list — Concourse chrome.
 *
 * Gift-card face (pen ZSzpV) sits above each pack form. Field names and
 * `.cbfs-packs__form` / `.cbfs-packs__button` markers stay for clasbpro JS.
 *
 * @var array<int, array<string, mixed>> $packs
 * @var bool                             $show_heading
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$packs_classes = trim( 'cbfs-packs cbfs-packs--concourse ' . \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::get_wrapper_class() );
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
					<div class="cbfs-packs__gift-wrap">
						<?php lp_clasbpro_pack_gift_card( $pack ); ?>
					</div>
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
					</div>
					<form class="cbfs-packs__form" novalidate>
						<div class="cbfs-packs__fields">
							<label class="cbfs-packs__label">
								<span><?php esc_html_e( 'Your name', 'class-bookings-with-stripe-pro' ); ?></span>
								<input class="cbfs-packs__input" type="text" name="customer_name" autocomplete="name" required />
							</label>
							<label class="cbfs-packs__label">
								<span><?php esc_html_e( 'Email address', 'class-bookings-with-stripe-pro' ); ?></span>
								<input class="cbfs-packs__input" type="email" name="customer_email" autocomplete="email" required />
							</label>
							<p class="cbfs-packs__error" hidden></p>
						</div>
						<button type="submit" class="cbfs-packs__button">
							<span class="cbfs-packs__button-label">
								<?php
								$lp_pack_pay = function_exists( 'lp_clasbpro_pack_price_display' )
									? lp_clasbpro_pack_price_display( (float) ( $pack['price'] ?? 0 ) )
									: '';
								echo esc_html(
									'' !== $lp_pack_pay
										? sprintf(
											/* translators: %s: formatted price */
											__( 'CONFIRM AND PAY %s', 'londonparkour_v8' ),
											$lp_pack_pay
										)
										: __( 'CONFIRM AND PAY', 'londonparkour_v8' )
								);
								?>
							</span>
							<span class="cbfs-packs__button-icon" aria-hidden="true">
								<?php
								if ( function_exists( 'lp_icon' ) ) {
									lp_icon( 'icon-arrow-right', 'cbfs-form__button-svg' );
								}
								?>
							</span>
						</button>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
