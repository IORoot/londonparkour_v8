<?php
/**
 * Session header — pen AKJMB.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$session = function_exists( 'lp_booking_form_session' )
	? lp_booking_form_session( $view )
	: array(
		'when' => '',
		'name' => $view->get_title(),
		'sub'  => $view->get_meta_text(),
	);
?>
		<header class="cbfs-form__hero">
			<?php if ( '' !== $session['when'] ) : ?>
				<p class="cbfs-form__when"><?php echo esc_html( $session['when'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $session['name'] ) : ?>
				<p class="cbfs-form__title"><?php echo esc_html( $session['name'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $session['sub'] ) : ?>
				<p class="cbfs-form__meta"><?php echo esc_html( $session['sub'] ); ?></p>
			<?php endif; ?>
		</header>
