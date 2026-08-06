<?php
/**
 * Booking status layout — default; gallery themes may override booking-status.php.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$theme_class  = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::get_wrapper_class();
$status_class = $view->get_status_class();
$root_classes = trim( 'cbfs-status cbfs-status--layout-modern ' . $theme_class . ' cbfs-status--' . $status_class );
$session_attr = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id );

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="<?php echo esc_attr( $root_classes ); ?>"<?php echo $session_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php include \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::status_content_path(); ?>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
