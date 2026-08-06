<?php
defined( 'ABSPATH' ) || exit;

$variant = $view->get_variant();
$coupon  = $view->is_coupon();
$booking = $view->booking;
$purchase = $view->purchase;
?>
	<?php if ( 'success-paid' === $variant && $coupon && $purchase ) : ?>
	<p class="cbfs-status__lede">
		<?php
		printf(
			/* translators: %s: customer name */
			esc_html__( 'Thanks %s — your coupon is ready to use on eligible classes.', CLASBOWPRO_TEXT_DOMAIN ),
			esc_html( $purchase['customer_name'] ?: __( 'there', CLASBOWPRO_TEXT_DOMAIN ) )
		);
		?>
	</p>
	<?php elseif ( 'success-paid' === $variant && $booking ) : ?>
	<p class="cbfs-status__lede">
		<?php
		printf(
			/* translators: %s: customer name */
			esc_html__( 'Thanks %s — we can\'t wait to see you on the mat.', CLASBOWPRO_TEXT_DOMAIN ),
			esc_html( $booking['customer_name'] ?: __( 'there', CLASBOWPRO_TEXT_DOMAIN ) )
		);
		?>
	</p>
	<?php elseif ( 'success-pending' === $variant ) : ?>
	<p class="cbfs-status__lede"><?php esc_html_e( 'Stripe is letting us know about your payment. This usually takes only a few seconds.', CLASBOWPRO_TEXT_DOMAIN ); ?></p>
	<?php elseif ( 'success-fallback' === $variant ) : ?>
	<p class="cbfs-status__lede"><?php echo esc_html( $coupon
		? __( "We've sent your coupon details by email.", CLASBOWPRO_TEXT_DOMAIN )
		: __( "We've sent your confirmation by email.", CLASBOWPRO_TEXT_DOMAIN )
	); ?></p>
	<?php elseif ( 'cancelled' === $variant ) : ?>
	<p class="cbfs-status__lede"><?php esc_html_e( "No problem — you haven't been charged. Whenever you're ready, you can pick up where you left off.", CLASBOWPRO_TEXT_DOMAIN ); ?></p>
	<?php else : ?>
	<p class="cbfs-status__lede"><?php echo esc_html( $view->get_reason_message() ); ?></p>
	<?php endif; ?>
