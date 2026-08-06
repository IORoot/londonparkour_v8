<?php
defined( 'ABSPATH' ) || exit;

if ( ! empty( $view->is_appointments ) ) {
	$view->render( 'appointment-calendar' );
	return;
}

if ( ! empty( $view->uses_date_calendar ) ) {
	$view->render( 'class-date-calendar' );
	return;
}

$class_id      = (int) $view->class_data['id'];
$primary_date  = $view->primary_date;
$labels        = $view->labels;
$dates         = $view->dates;
$preset_date   = $view->preset_date;
?>
		<div class="cbfs-form__row">
			<?php if ( $view->is_one_off_fixed_date && $primary_date ) : ?>
				<span class="cbfs-form__label" id="cbfs-date-label-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $labels['event_date'] ); ?></span>
				<?php if ( ! empty( $primary_date['cancelled'] ) ) : ?>
					<p class="cbfs-form__date-fixed cbfs-form__date-fixed--cancelled" aria-labelledby="cbfs-date-label-<?php echo esc_attr( (string) $class_id ); ?>">
						<?php
						echo esc_html__( 'Cancelled — ', CLASBOWPRO_TEXT_DOMAIN );
						echo esc_html( (string) $primary_date['label'] );
						?>
					</p>
				<?php else : ?>
					<p class="cbfs-form__date-fixed" data-cbfs-date-display aria-labelledby="cbfs-date-label-<?php echo esc_attr( (string) $class_id ); ?>">
						<?php
						echo esc_html( (string) $primary_date['label'] );
						if ( $view->show_seats_remaining ) {
							echo ' · ';
							echo esc_html(
								sprintf(
									/* translators: %d: seats remaining */
									_n( '%d seat left', '%d seats left', (int) $primary_date['remaining'], CLASBOWPRO_TEXT_DOMAIN ),
									(int) $primary_date['remaining']
								)
							);
						}
						?>
					</p>
					<input
						type="hidden"
						id="cbfs-date-<?php echo esc_attr( (string) $class_id ); ?>"
						name="class_date"
						value="<?php echo esc_attr( (string) $primary_date['date'] ); ?>"
						data-remaining="<?php echo esc_attr( (string) (int) $primary_date['remaining'] ); ?>"
						data-cancelled="0"
					>
				<?php endif; ?>
			<?php else : ?>
				<label class="cbfs-form__label" for="cbfs-date-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $labels['date'] ); ?></label>
				<select class="cbfs-form__input cbfs-form__select" id="cbfs-date-<?php echo esc_attr( (string) $class_id ); ?>" name="class_date" data-cbfs-dates="<?php echo esc_attr( wp_json_encode( $dates ) ); ?>">
					<?php foreach ( $dates as $d ) : ?>
						<?php
						$is_cancelled = ! empty( $d['cancelled'] );
						$is_selected  = ! $is_cancelled && $preset_date && (string) $d['date'] === $preset_date;
						?>
						<option value="<?php echo esc_attr( $d['date'] ); ?>" data-remaining="<?php echo esc_attr( (string) $d['remaining'] ); ?>" data-cancelled="<?php echo $is_cancelled ? '1' : '0'; ?>"<?php echo $is_cancelled ? ' disabled class="cbfs-form__option--cancelled"' : ''; ?><?php echo $is_selected ? ' selected' : ''; ?>>
							<?php
							if ( $is_cancelled ) {
								echo esc_html__( 'Cancelled — ', CLASBOWPRO_TEXT_DOMAIN );
								echo esc_html( $d['label'] );
							} else {
								echo esc_html( $d['label'] );
								if ( $view->show_seats_remaining ) {
									echo ' · ';
									echo esc_html(
										sprintf(
											/* translators: %d: seats remaining */
											_n( '%d seat left', '%d seats left', $d['remaining'], CLASBOWPRO_TEXT_DOMAIN ),
											$d['remaining']
										)
									);
								}
							}
							?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
		</div>
