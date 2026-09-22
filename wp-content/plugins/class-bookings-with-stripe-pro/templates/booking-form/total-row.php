<?php
defined( 'ABSPATH' ) || exit;

$party_prices = [];
if ( ! empty( $view->class_data['is_appointments'] ) && ! empty( $view->class_data['party_prices'] ) && is_array( $view->class_data['party_prices'] ) ) {
	foreach ( $view->class_data['party_prices'] as $seats => $unit ) {
		$party_prices[ (string) (int) $seats ] = (float) $unit;
	}
}
$party_json = ! empty( $party_prices ) ? wp_json_encode( $party_prices ) : '';
?>
		<div class="cbfs-form__row cbfs-form__row--total">
			<span class="cbfs-form__total-label"><?php echo esc_html( $view->labels['total'] ); ?></span>
			<span
				class="cbfs-form__total"
				data-cbfs-unit-price="<?php echo esc_attr( (string) $view->class_data['price'] ); ?>"
				<?php if ( '' !== $party_json ) : ?>
					data-cbfs-party-prices="<?php echo esc_attr( $party_json ); ?>"
					data-cbfs-each-label="<?php echo esc_attr( __( 'each', 'class-bookings-with-stripe-pro' ) ); ?>"
				<?php endif; ?>
			>
				<?php echo esc_html( \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::format_price( (float) $view->class_data['price'] ) ); ?>
			</span>
		</div>
