<?php
/**
 * Submit CTA — pen hoH6b (Button / Primary / Default).
 *
 * Keeps .cbfs-form__button + .cbfs-form__button-label + .cbfs-form__spinner
 * for clasbpro JS. Stripe logo is omitted; trailing arrow matches the design.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_pay_label  = function_exists( 'lp_booking_form_pay_label' )
	? lp_booking_form_pay_label( $view->class_data )
	: (string) ( $view->labels['book_button'] ?? '' );
$lp_pack_label = __( 'Book with coupon', 'londonparkour_v8' );
?>
		<button
			type="submit"
			class="cbfs-form__button"
			data-cbfs-pay-label="<?php echo esc_attr( $lp_pay_label ); ?>"
			data-cbfs-pack-label="<?php echo esc_attr( $lp_pack_label ); ?>"
		>
			<span class="cbfs-form__button-label"><?php echo esc_html( $lp_pay_label ); ?></span>
			<span class="cbfs-form__button-icon" aria-hidden="true">
				<?php
				if ( function_exists( 'lp_icon' ) ) {
					lp_icon( 'icon-arrow-right', 'cbfs-form__button-svg' );
				}
				?>
			</span>
			<span class="cbfs-form__spinner" aria-hidden="true"></span>
		</button>
