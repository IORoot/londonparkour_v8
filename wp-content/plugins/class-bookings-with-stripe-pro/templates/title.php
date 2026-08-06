<?php
defined( 'ABSPATH' ) || exit;

$variant = $view->get_variant();
?>
	<?php if ( 'success-paid' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php esc_html_e( 'Booking confirmed', CLASBOWPRO_TEXT_DOMAIN ); ?></h2>
	<?php elseif ( 'success-pending' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php esc_html_e( 'Confirming your booking…', CLASBOWPRO_TEXT_DOMAIN ); ?></h2>
	<?php elseif ( 'success-fallback' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php esc_html_e( 'Thanks for your booking', CLASBOWPRO_TEXT_DOMAIN ); ?></h2>
	<?php elseif ( 'cancelled' === $variant ) : ?>
	<h2 class="cbfs-status__title"><?php esc_html_e( 'Booking cancelled', CLASBOWPRO_TEXT_DOMAIN ); ?></h2>
	<?php else : ?>
	<h2 class="cbfs-status__title"><?php esc_html_e( "We couldn't take your booking", CLASBOWPRO_TEXT_DOMAIN ); ?></h2>
	<?php endif; ?>
