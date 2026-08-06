<?php
/**
 * Lime Checkout — fonts and labels.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'clasbpro-lime-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			[],
			null
		);
	},
	15
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! wp_script_is( 'clasbpro', 'enqueued' ) ) {
			return;
		}

		wp_add_inline_script(
			'clasbpro',
			"(function(){function s(f){if(!f||!f.classList.contains('cbfs-lime__layout'))return;var sub=f.querySelector('.cbfs-lime__subtotal'),tot=f.querySelector('.cbfs-form__total');if(sub&&tot)sub.textContent=tot.textContent;}document.querySelectorAll('form.cbfs-lime__layout').forEach(function(f){s(f);f.addEventListener('change',function(){s(f);});});})();"
		);
	},
	30
);

add_filter(
	'clasbpro_booking_labels',
	static function ( array $labels ): array {
		$labels['name']          = __( 'Name on booking', 'class-bookings-with-stripe-pro' );
		$labels['email']         = __( 'Email address', 'class-bookings-with-stripe-pro' );
		$labels['date']          = __( 'Session date', 'class-bookings-with-stripe-pro' );
		$labels['seats']         = __( 'Places', 'class-bookings-with-stripe-pro' );
		$labels['total']         = __( 'Total', 'class-bookings-with-stripe-pro' );
		$labels['book_button']   = __( 'Book and pay', 'class-bookings-with-stripe-pro' );
		$labels['redirect_hint'] = __( 'You will complete payment securely with Stripe.', 'class-bookings-with-stripe-pro' );
		return $labels;
	}
);
