<?php
defined( 'ABSPATH' ) || exit;

$class_id = (int) $view->class_data['id'];
?>
		<div class="cbfs-form__row">
			<label class="cbfs-form__label" for="cbfs-name-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $view->labels['name'] ); ?></label>
			<input class="cbfs-form__input" id="cbfs-name-<?php echo esc_attr( (string) $class_id ); ?>" type="text" name="customer_name" autocomplete="name" required>
		</div>
