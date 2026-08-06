<?php
/**
 * Atelier — status pages (confirmed / cancelled / error).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 */

defined( 'ABSPATH' ) || exit;

$pack_slug = 'atelier';
$hero_url  = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/hero.jpg' );
if ( '' === $hero_url ) {
	$hero_url = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Registry::pack_url( $pack_slug )
		. \IOROOT_STRIPE_BOOKINGS_PRO\Template_Loader::THEME_DIR
		. '/assets/hero.jpg';
}

$status_class = $view->get_status_class();
$session_attr = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id );
$style_attr   = $hero_url ? ' style="--cbfs-atelier-hero: url(\'' . esc_url( $hero_url ) . '\')"' : '';

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="cbfs-status cbfs-status--layout-modern cbfs-atelier cbfs-status--<?php echo esc_attr( $status_class ); ?>"<?php echo $session_attr . $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="cbfs-atelier__backdrop" aria-hidden="true"></div>
	<div class="cbfs-atelier__stage">
		<div class="cbfs-atelier__card cbfs-atelier__card--status">
			<?php include \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_content_path(); ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
