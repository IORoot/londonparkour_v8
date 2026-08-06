<?php
defined( 'ABSPATH' ) || exit;

$class_id    = (int) $view->class_data['id'];
$labels      = $view->labels;
$months      = max( 1, (int) ( $view->class_data['calendar_months_ahead'] ?? 3 ) );
$preset_date = $view->preset_date;
$cal_shell   = $view->calendar_month_shell();
$cal_offset  = $cal_shell['offset'];
$cal_days    = $cal_shell['days'];
$cal_title   = $cal_shell['title'];
?>
		<div
			class="cbfs-form__row cbfs-appointment-calendar cbfs-class-date-calendar is-loading"
			data-cbfs-class-date-calendar
			aria-busy="true"
			data-cbfs-class-id="<?php echo esc_attr( (string) $class_id ); ?>"
			data-cbfs-today="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"
			<?php echo $preset_date ? 'data-cbfs-preset-date="' . esc_attr( $preset_date ) . '"' : ''; ?>
			data-cbfs-months-ahead="<?php echo esc_attr( (string) $months ); ?>"
			data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"
			data-label-pick-day="<?php echo esc_attr( $labels['class_date_pick_day'] ); ?>"
			data-label-month-empty="<?php echo esc_attr( $labels['class_date_month_empty'] ); ?>"
			data-label-full="<?php echo esc_attr( $labels['class_date_full'] ); ?>"
			data-label-cancelled="<?php echo esc_attr( $labels['class_date_cancelled'] ); ?>"
			data-label-selected="<?php echo esc_attr( $labels['class_date_selected'] ); ?>"
		>
			<span class="cbfs-form__label" id="cbfs-class-date-label-<?php echo esc_attr( (string) $class_id ); ?>"><?php echo esc_html( $labels['date'] ); ?></span>
			<div class="cbfs-appointment-calendar__layout" aria-labelledby="cbfs-class-date-label-<?php echo esc_attr( (string) $class_id ); ?>">
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
				<div class="cbfs-appointment-calendar__grid" data-cbfs-cal-grid role="group" aria-label="<?php esc_attr_e( 'Class date calendar', 'class-bookings-with-stripe-pro' ); ?>">
					<?php for ( $i = 0; $i < $cal_offset; $i++ ) : ?>
						<span class="cbfs-appointment-calendar__day cbfs-appointment-calendar__day--empty" aria-hidden="true"></span>
					<?php endfor; ?>
					<?php for ( $d = 1; $d <= $cal_days; $d++ ) : ?>
						<button type="button" class="cbfs-appointment-calendar__day is-skeleton" disabled tabindex="-1" aria-hidden="true">
							<span class="cbfs-appointment-calendar__day-num"><?php echo esc_html( (string) $d ); ?></span>
						</button>
					<?php endfor; ?>
				</div>
				<div class="cbfs-appointment-calendar__slots cbfs-class-date-calendar__footer" data-cbfs-selection-panel>
					<p class="cbfs-appointment-calendar__slots-hint" data-cbfs-month-hint aria-live="polite"><?php echo esc_html( $labels['class_date_pick_day'] ); ?></p>
					<div class="cbfs-appointment-calendar__selection" data-cbfs-selection hidden>
						<span class="cbfs-appointment-calendar__selection-label"><?php echo esc_html( $labels['class_date_selected'] ); ?></span>
						<strong class="cbfs-appointment-calendar__selection-text" data-cbfs-selection-text></strong>
					</div>
				</div>
			</div>
			<input type="hidden" name="class_date" id="cbfs-date-<?php echo esc_attr( (string) $class_id ); ?>" value="">
		</div>
