<?php
/**
 * Yoga Split — status pages (confirmed / cancelled / error).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 */

defined( 'ABSPATH' ) || exit;

$status_class = $view->get_status_class();
$session_attr = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id );

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="cbfs-status cbfs-status--layout-modern cbfs-ex5 cbfs-status--<?php echo esc_attr( $status_class ); ?>"<?php echo $session_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cbfs-ex5__split">
		<aside class="cbfs-ex5__visual">
			<img
				class="cbfs-ex5__yoga-photo"
				src="<?php echo esc_url( \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/yoga-hero.jpg' ) ); ?>"
				alt=""
				width="1200"
				height="757"
				loading="lazy"
				decoding="async"
			>
			<div class="cbfs-ex5__visual-overlay">
				<p class="cbfs-ex5__visual-kicker"><?php esc_html_e( 'Mind · Body · Breath', 'class-bookings-with-stripe-pro' ); ?></p>
				<h2 class="cbfs-ex5__visual-title"><?php esc_html_e( 'Booking update', 'class-bookings-with-stripe-pro' ); ?></h2>
			</div>
		</aside>
		<div class="cbfs-ex5__main cbfs-ex5__main--status">
			<?php include \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_content_path(); ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
