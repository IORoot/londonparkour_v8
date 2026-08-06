<?php
defined( 'ABSPATH' ) || exit;

$class_id    = (int) $view->class_data['id'];
$labels      = $view->labels;
$capacity    = max( 1, (int) ( $view->class_data['capacity'] ?? 1 ) );
$months      = max( 1, (int) ( $view->class_data['calendar_months_ahead'] ?? 3 ) );
$unit_price  = (float) ( $view->class_data['price'] ?? 0 );
$slots_id    = 'cbfs-appointment-slots-' . $class_id;
$preset_date = $view->preset_date;
$preset_slot = $view->preset_slot_rule_id;
$cal_shell   = $view->calendar_month_shell();
$cal_offset  = $cal_shell['offset'];
$cal_days    = $cal_shell['days'];
$cal_title   = $cal_shell['title'];
?>
		<div
			class="cbfs-form__row cbfs-appointment-calendar is-loading"
			data-cbfs-appointment-calendar
			aria-busy="true"
			data-cbfs-class-id="<?php echo esc_attr( (string) $class_id ); ?>"
			data-cbfs-today="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"
			<?php echo $preset_date ? 'data-cbfs-preset-date="' . esc_attr( $preset_date ) . '"' : ''; ?>
			<?php echo $preset_slot ? 'data-cbfs-preset-slot-rule-id="' . esc_attr( $preset_slot ) . '"' : ''; ?>
			data-cbfs-months-ahead="<?php echo esc_attr( (string) $months ); ?>"
			data-cbfs-capacity="<?php echo esc_attr( (string) $capacity ); ?>"
			data-cbfs-default-price="<?php echo esc_attr( (string) $unit_price ); ?>"
			data-label-available="<?php echo esc_attr( $labels['appointment_available'] ); ?>"
			data-label-slot-selected="<?php echo esc_attr( $labels['appointment_slot_selected'] ); ?>"
			data-label-booked="<?php echo esc_attr( $labels['appointment_booked'] ); ?>"
			data-label-pick-day="<?php echo esc_attr( $labels['appointment_pick_day'] ); ?>"
			data-label-times-heading="<?php echo esc_attr( $labels['appointment_times_heading'] ); ?>"
			data-label-selected="<?php echo esc_attr( $labels['appointment_selected'] ); ?>"
		>
			<span class="cbfs-form__label" id="cbfs-appointment-label-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $labels['date'] ); ?></span>
			<div class="cbfs-appointment-calendar__layout" aria-labelledby="cbfs-appointment-label-<?php echo esc_attr( (string) $class_id ); ?>">
				<div class="cbfs-appointment-calendar__nav">
					<button type="button" class="cbfs-appointment-calendar__prev" aria-label="<?php esc_attr_e( 'Previous month', 'class-bookings-with-stripe-pro' ); ?>">&larr;</button>
					<div class="cbfs-appointment-calendar__title-group">
						<strong class="cbfs-appointment-calendar__title" data-cbfs-cal-title=""><?php echo esc_html( $cal_title ); ?></strong>
						<span class="cbfs-appointment-calendar__spinner" data-cbfs-cal-spinner aria-hidden="true">
							<span class="cbfs-appointment-calendar__spinner-icon"></span>
						</span>
					</div>
					<button type="button" class="cbfs-appointment-calendar__next" aria-label="<?php esc_attr_e( 'Next month', 'class-bookings-with-stripe-pro' ); ?>">&rarr;</button>
				</div>
				<div class="cbfs-appointment-calendar__weekdays" aria-hidden="true">
					<span><?php esc_html_e( 'Mon', 'class-bookings-with-stripe-pro' ); ?></span>
					<span><?php esc_html_e( 'Tue', 'class-bookings-with-stripe-pro' ); ?></span>
					<span><?php esc_html_e( 'Wed', 'class-bookings-with-stripe-pro' ); ?></span>
					<span><?php esc_html_e( 'Thu', 'class-bookings-with-stripe-pro' ); ?></span>
					<span><?php esc_html_e( 'Fri', 'class-bookings-with-stripe-pro' ); ?></span>
					<span><?php esc_html_e( 'Sat', 'class-bookings-with-stripe-pro' ); ?></span>
					<span><?php esc_html_e( 'Sun', 'class-bookings-with-stripe-pro' ); ?></span>
				</div>
				<div class="cbfs-appointment-calendar__grid" data-cbfs-cal-grid role="group" aria-label="<?php esc_attr_e( 'Appointment calendar days', 'class-bookings-with-stripe-pro' ); ?>">
					<?php for ( $i = 0; $i < $cal_offset; $i++ ) : ?>
						<span class="cbfs-appointment-calendar__day cbfs-appointment-calendar__day--empty" aria-hidden="true"></span>
					<?php endfor; ?>
					<?php for ( $d = 1; $d <= $cal_days; $d++ ) : ?>
						<button type="button" class="cbfs-appointment-calendar__day is-skeleton" disabled tabindex="-1" aria-hidden="true">
							<span class="cbfs-appointment-calendar__day-num"><?php echo esc_html( (string) $d ); ?></span>
						</button>
					<?php endfor; ?>
				</div>
				<div class="cbfs-appointment-calendar__slots" data-cbfs-slots-section>
					<div class="cbfs-appointment-calendar__slots-panel" data-cbfs-slots-panel>
						<p class="cbfs-appointment-calendar__slots-heading" id="<?php echo esc_attr( $slots_id ); ?>" data-cbfs-slots-heading hidden></p>
						<p class="cbfs-appointment-calendar__slots-hint" data-cbfs-slots-hint aria-live="polite"><?php echo esc_html( $labels['appointment_pick_day'] ); ?></p>
						<ul
							class="cbfs-appointment-calendar__slot-list"
							data-cbfs-slot-list
							role="radiogroup"
							aria-labelledby="<?php echo esc_attr( $slots_id ); ?>"
							hidden
						></ul>
					</div>
					<div class="cbfs-appointment-calendar__selection" data-cbfs-selection hidden>
						<span class="cbfs-appointment-calendar__selection-label"><?php echo esc_html( $labels['appointment_selected'] ); ?></span>
						<strong class="cbfs-appointment-calendar__selection-text" data-cbfs-selection-text></strong>
					</div>
				</div>
			</div>
			<input type="hidden" name="class_date" id="cbfs-date-<?php echo esc_attr( (string) $class_id ); ?>" value="">
			<input type="hidden" name="slot_rule_id" value="" data-cbfs-slot-rule-input data-remaining="0">
		</div>
