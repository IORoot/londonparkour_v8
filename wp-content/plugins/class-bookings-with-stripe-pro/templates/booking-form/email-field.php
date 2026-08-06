<?php
defined( 'ABSPATH' ) || exit;

$class_id = (int) $view->class_data['id'];
?>
		<div class="cbfs-form__row">
			<label class="cbfs-form__label" for="cbfs-email-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $view->labels['email'] ); ?></label>
			<input class="cbfs-form__input" id="cbfs-email-<?php echo esc_attr( (string) $class_id ); ?>" type="email" name="customer_email" autocomplete="email" required>
		</div>
