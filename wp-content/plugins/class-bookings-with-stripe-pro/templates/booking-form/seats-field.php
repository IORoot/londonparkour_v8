<?php
defined( 'ABSPATH' ) || exit;

$class_id     = (int) $view->class_data['id'];
$party_prices = [];
if ( ! empty( $view->class_data['is_appointments'] ) && ! empty( $view->class_data['party_prices'] ) && is_array( $view->class_data['party_prices'] ) ) {
	$party_prices = $view->class_data['party_prices'];
}
$max_seats = max( 1, $view->max_seats_today );
?>
		<div class="cbfs-form__row">
			<label class="cbfs-form__label" for="cbfs-seats-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $view->labels['seats'] ); ?></label>
			<select class="cbfs-form__input cbfs-form__select" id="cbfs-seats-<?php echo esc_attr( (string) $class_id ); ?>" name="seats">
				<?php for ( $i = 1; $i <= $max_seats; $i++ ) : ?>
					<?php
					if ( ! empty( $party_prices ) && ! array_key_exists( $i, $party_prices ) ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_html( (string) $i ); ?></option>
				<?php endfor; ?>
			</select>
		</div>
