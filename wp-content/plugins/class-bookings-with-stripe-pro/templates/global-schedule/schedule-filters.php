<?php
defined( 'ABSPATH' ) || exit;

$labels = $view->labels;
?>
<div class="cbfs-schedule__filters" data-cbfs-schedule-filters role="tablist" aria-label="<?php esc_attr_e( 'Filter classes', 'class-bookings-with-stripe-pro' ); ?>">
	<button type="button" class="cbfs-schedule__filter is-active" data-cbfs-schedule-filter="all" role="tab" aria-selected="true">
		<?php echo esc_html( $labels['all_classes'] ); ?>
	</button>
</div>
