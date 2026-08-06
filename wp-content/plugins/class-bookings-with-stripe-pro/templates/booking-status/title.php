<?php
defined( 'ABSPATH' ) || exit;

$variant = $view->get_variant();
$coupon  = $view->is_coupon();
?>
	<?php if ( 'success-paid' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php echo esc_html( $coupon ? __( 'Coupon purchased', CLASBOWPRO_TEXT_DOMAIN ) : __( 'Booking confirmed', CLASBOWPRO_TEXT_DOMAIN ) ); ?></h2>
	<?php elseif ( 'success-pending' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php echo esc_html( $coupon ? __( 'Confirming your coupon…', CLASBOWPRO_TEXT_DOMAIN ) : __( 'Confirming your booking…', CLASBOWPRO_TEXT_DOMAIN ) ); ?></h2>
	<?php elseif ( 'success-fallback' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php echo esc_html( $coupon ? __( 'Thanks for your purchase', CLASBOWPRO_TEXT_DOMAIN ) : __( 'Thanks for your booking', CLASBOWPRO_TEXT_DOMAIN ) ); ?></h2>
	<?php elseif ( 'cancelled' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php echo esc_html( $coupon ? __( 'Purchase cancelled', CLASBOWPRO_TEXT_DOMAIN ) : __( 'Booking cancelled', CLASBOWPRO_TEXT_DOMAIN ) ); ?></h2>
	<?php else : ?>
	<h2 class="cbfs-status__title"><?php echo esc_html( $coupon ? __( "We couldn't complete your purchase", CLASBOWPRO_TEXT_DOMAIN ) : __( "We couldn't take your booking", CLASBOWPRO_TEXT_DOMAIN ) ); ?></h2>
	<?php endif; ?>
