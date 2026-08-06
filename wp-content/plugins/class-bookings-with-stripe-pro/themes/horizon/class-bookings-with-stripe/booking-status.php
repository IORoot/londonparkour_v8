<?php
/**
 * Horizon — status pages (confirmed / cancelled / error).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 */

defined( 'ABSPATH' ) || exit;

$status_class = $view->get_status_class();
$session_attr = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id );

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="cbfs-status cbfs-status--layout-modern cbfs-horizon cbfs-status--<?php echo esc_attr( $status_class ); ?>"<?php echo $session_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cbfs-horizon__shell">
		<aside class="cbfs-horizon__brand" aria-hidden="false">
			<div class="cbfs-horizon__brand-inner">
				<div class="cbfs-horizon__logo" aria-hidden="true">
					<span class="cbfs-horizon__logo-mark"></span>
					<span class="cbfs-horizon__logo-text"><?php esc_html_e( 'Horizon', 'class-bookings-with-stripe-pro' ); ?></span>
				</div>
				<p class="cbfs-horizon__welcome"><?php esc_html_e( 'Booking update', 'class-bookings-with-stripe-pro' ); ?></p>
				<h2 class="cbfs-horizon__brand-title"><?php esc_html_e( 'Your session', 'class-bookings-with-stripe-pro' ); ?></h2>
				<div class="cbfs-horizon__scene" aria-hidden="true">
					<?php require __DIR__ . '/illustration.svg.php'; ?>
				</div>
			</div>
		</aside>
		<div class="cbfs-horizon__panel cbfs-horizon__panel--status">
			<?php include \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_content_path(); ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
