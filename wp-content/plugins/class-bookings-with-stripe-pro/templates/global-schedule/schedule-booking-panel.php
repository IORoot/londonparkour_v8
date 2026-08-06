<?php
defined( 'ABSPATH' ) || exit;

$labels = $view->labels;
?>
<div class="cbfs-schedule__panel" data-cbfs-schedule-panel hidden>
	<div class="cbfs-schedule__backdrop" data-cbfs-schedule-close tabindex="-1" aria-hidden="true"></div>
	<div class="cbfs-schedule__sheet" role="dialog" aria-modal="true" aria-labelledby="cbfs-schedule-panel-title">
		<div class="cbfs-schedule__sheet-header">
			<h2 class="cbfs-schedule__sheet-title" id="cbfs-schedule-panel-title" data-cbfs-schedule-panel-title></h2>
			<button type="button" class="cbfs-schedule__sheet-close" data-cbfs-schedule-close aria-label="<?php echo esc_attr( $labels['close'] ); ?>">&times;</button>
		</div>
		<div class="cbfs-schedule__sheet-body" data-cbfs-schedule-panel-body>
			<div class="cbfs-schedule__panel-loading" data-cbfs-schedule-panel-loading hidden><?php echo esc_html( $labels['loading'] ); ?></div>
		</div>
	</div>
</div>
