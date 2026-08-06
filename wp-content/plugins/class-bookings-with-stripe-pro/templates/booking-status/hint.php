<?php
defined( 'ABSPATH' ) || exit;

$variant = $view->get_variant();
$coupon  = $view->is_coupon();
?>
	<?php if ( 'success-paid' === $variant ) : ?>
	<p class="cbfs-status__hint"><?php echo esc_html( $coupon
		? __( "We've emailed your coupon code and a restore link — check your inbox.", CLASBOWPRO_TEXT_DOMAIN )
		: __( "We've sent a confirmation email — check your inbox.", CLASBOWPRO_TEXT_DOMAIN )
	); ?></p>
	<?php elseif ( 'error' === $variant ) : ?>
	<p class="cbfs-status__hint"><?php echo esc_html( $coupon
		? __( "Your card has not been charged. If the problem keeps happening, please email us and we'll sort it out.", CLASBOWPRO_TEXT_DOMAIN )
		: __( "Your card has not been charged. If the problem keeps happening, please email us and we'll book you in by hand.", CLASBOWPRO_TEXT_DOMAIN )
	); ?></p>
	<?php endif; ?>
