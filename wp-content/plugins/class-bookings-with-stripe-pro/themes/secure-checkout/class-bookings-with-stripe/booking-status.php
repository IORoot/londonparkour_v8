<?php
/**
 * Secure Checkout — status pages (confirmed / cancelled / error).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 */

defined( 'ABSPATH' ) || exit;

$status_class = $view->get_status_class();
$session_attr = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id );

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="cbfs-status cbfs-status--layout-modern cbfs-ex4 cbfs-status--<?php echo esc_attr( $status_class ); ?>"<?php echo $session_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cbfs-ex4__shell">
		<div class="cbfs-ex4__hero" aria-hidden="true">
			<?php require __DIR__ . '/credit-card.svg.php'; ?>
			<p class="cbfs-ex4__hero-tagline"><?php esc_html_e( 'Secure checkout powered by Stripe', 'class-bookings-with-stripe-pro' ); ?></p>
		</div>
		<div class="cbfs-ex4__panel cbfs-ex4__panel--status">
			<?php include \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_content_path(); ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
