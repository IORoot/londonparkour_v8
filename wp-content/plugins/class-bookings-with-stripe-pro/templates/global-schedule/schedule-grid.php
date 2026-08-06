<?php
defined( 'ABSPATH' ) || exit;

$labels = $view->labels;
?>
<div class="cbfs-schedule__grid-wrap is-loading" data-cbfs-schedule-grid-wrap>
	<div class="cbfs-schedule__status" data-cbfs-schedule-status aria-live="polite"></div>
	<div class="cbfs-schedule__week-bar" data-cbfs-schedule-week-bar>
		<button type="button" class="cbfs-schedule__nav-btn cbfs-schedule__nav-btn--prev" data-cbfs-schedule-prev aria-label="<?php echo esc_attr( $labels['prev_week'] ); ?>">
			<?php
			$direction = 'prev';
			include __DIR__ . '/schedule-nav-icon.php';
			?>
		</button>
		<span class="cbfs-schedule__week-label" data-cbfs-schedule-week-label></span>
		<button type="button" class="cbfs-schedule__nav-btn cbfs-schedule__nav-btn--next" data-cbfs-schedule-next aria-label="<?php echo esc_attr( $labels['next_week'] ); ?>">
			<?php
			$direction = 'next';
			include __DIR__ . '/schedule-nav-icon.php';
			?>
		</button>
	</div>
	<div
		class="cbfs-schedule__agenda"
		data-cbfs-schedule-agenda
		role="list"
		aria-label="<?php esc_attr_e( 'Weekly class schedule', 'class-bookings-with-stripe-pro' ); ?>"
	></div>
	<div class="cbfs-schedule__calendar-frame" data-cbfs-schedule-calendar-frame>
		<div class="cbfs-schedule__calendar" data-cbfs-schedule-grid role="grid" aria-label="<?php esc_attr_e( 'Weekly class schedule', 'class-bookings-with-stripe-pro' ); ?>">
			<div class="cbfs-schedule__header-row">
				<div class="cbfs-schedule__corner" aria-hidden="true"></div>
				<div class="cbfs-schedule__day-heads" data-cbfs-schedule-day-heads></div>
			</div>
			<div class="cbfs-schedule__body-row">
				<div class="cbfs-schedule__time-axis" data-cbfs-schedule-time-axis aria-hidden="true"></div>
				<div class="cbfs-schedule__days" data-cbfs-schedule-days></div>
			</div>
		</div>
	</div>
</div>
