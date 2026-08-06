<?php
/**
 * Noir — status pages (confirmed / cancelled / error).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 */

defined( 'ABSPATH' ) || exit;

$pack_slug = 'noir';
$hero_url  = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/hero.jpg' );
if ( '' === $hero_url ) {
	$hero_url = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Registry::pack_url( $pack_slug )
		. \IOROOT_STRIPE_BOOKINGS_PRO\Template_Loader::THEME_DIR
		. '/assets/hero.jpg';
}

$status_class = $view->get_status_class();
$session_attr = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id );

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="cbfs-status cbfs-status--layout-modern cbfs-noir cbfs-status--<?php echo esc_attr( $status_class ); ?>"<?php echo $session_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cbfs-noir__split">
		<aside class="cbfs-noir__visual">
			<?php if ( $hero_url ) : ?>
				<img class="cbfs-noir__photo" src="<?php echo esc_url( $hero_url ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
			<?php endif; ?>
			<div class="cbfs-noir__visual-overlay">
				<p class="cbfs-noir__kicker"><?php esc_html_e( 'Booking update', 'class-bookings-with-stripe-pro' ); ?></p>
				<h2 class="cbfs-noir__visual-title"><?php esc_html_e( 'Your reservation', 'class-bookings-with-stripe-pro' ); ?></h2>
			</div>
		</aside>
		<div class="cbfs-noir__main cbfs-noir__main--status">
			<?php include \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_content_path(); ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
