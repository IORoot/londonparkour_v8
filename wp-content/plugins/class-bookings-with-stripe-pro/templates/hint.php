<?php
defined( 'ABSPATH' ) || exit;

$variant = $view->get_variant();
?>
	<?php if ( 'success-paid' === $variant ) : ?>
	<p class="cbfs-status__hint"><?php esc_html_e( "We've sent a confirmation email — check your inbox.", CLASBOWPRO_TEXT_DOMAIN ); ?></p>
	<?php elseif ( 'error' === $variant ) : ?>
	<p class="cbfs-status__hint"><?php esc_html_e( "Your card has not been charged. If the problem keeps happening, please email us and we'll book you in by hand.", CLASBOWPRO_TEXT_DOMAIN ); ?></p>
	<?php endif; ?>
