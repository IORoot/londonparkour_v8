<?php
defined( 'ABSPATH' ) || exit;

$class_id = (int) $view->class_data['id'];
?>
		<div class="cbfs-form__row">
			<label class="cbfs-form__label" for="cbfs-seats-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $view->labels['seats'] ); ?></label>
			<select class="cbfs-form__input cbfs-form__select" id="cbfs-seats-<?php echo esc_attr( (string) $class_id ); ?>" name="seats">
				<?php for ( $i = 1; $i <= max( 1, $view->max_seats_today ); $i++ ) : ?>
					<option value="<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_html( (string) $i ); ?></option>
				<?php endfor; ?>
			</select>
		</div>
