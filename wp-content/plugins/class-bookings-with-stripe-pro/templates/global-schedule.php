<?php
/**
 * Global schedule layout — week time grid.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Global_Schedule_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

$schedule_classes = trim( 'cbfs-schedule alignwide ' . \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::get_wrapper_class() );
?>
<div
	class="<?php echo esc_attr( $schedule_classes ); ?>"
	data-cbfs-global-schedule
	data-cbfs-week="<?php echo esc_attr( $week_monday ); ?>"
	data-cbfs-class-ids="<?php echo esc_attr( implode( ',', array_map( 'strval', $class_ids ) ) ); ?>"
	data-cbfs-weeks-ahead="<?php echo esc_attr( (string) $weeks_ahead ); ?>"
	data-label-loading="<?php echo esc_attr( $labels['loading'] ); ?>"
	data-label-empty="<?php echo esc_attr( $labels['empty'] ); ?>"
	data-label-empty-filtered="<?php echo esc_attr( $labels['empty_filtered'] ); ?>"
	data-label-today="<?php echo esc_attr( $labels['today'] ); ?>"
	data-label-tomorrow="<?php echo esc_attr( $labels['tomorrow'] ); ?>"
	data-label-cancelled="<?php echo esc_attr( $labels['cancelled'] ); ?>"
	data-label-full="<?php echo esc_attr( $labels['full'] ); ?>"
	data-label-class-full="<?php echo esc_attr( $labels['class_full'] ); ?>"
	data-label-spots="<?php echo esc_attr( $labels['spots_left'] ); ?>"
	data-label-book="<?php echo esc_attr( $labels['book'] ); ?>"
	data-label-close="<?php echo esc_attr( $labels['close'] ); ?>"
	aria-busy="true"
>
	<?php $view->render( 'schedule-filters' ); ?>
	<?php $view->render( 'schedule-grid' ); ?>
	<?php $view->render( 'schedule-booking-panel' ); ?>
</div>
