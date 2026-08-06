<?php
defined( 'ABSPATH' ) || exit;
?>
		<div class="cbfs-form__row cbfs-form__row--total">
			<span class="cbfs-form__total-label"><?php echo esc_html( $view->labels['total'] ); ?></span>
			<span class="cbfs-form__total" data-cbfs-unit-price="<?php echo esc_attr( (string) $view->class_data['price'] ); ?>">
				<?php echo esc_html( \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::format_price( (float) $view->class_data['price'] ) ); ?>
			</span>
		</div>
