<?php
defined( 'ABSPATH' ) || exit;

$booking = $view->booking;
if ( ! $booking ) {
	return;
}
?>
	<dl class="cbfs-status__details">
		<dt><?php esc_html_e( 'Class', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( $booking['class_name'] ); ?></dd>
		<dt><?php esc_html_e( 'When', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( $booking['class_date'] . ' · ' . $booking['class_time'] ); ?></dd>
		<dt><?php esc_html_e( 'Where', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( $booking['location'] ); ?></dd>
		<dt><?php esc_html_e( 'Seats', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) $booking['seats'] ); ?></dd>
		<dt><?php esc_html_e( 'Total', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( $booking['amount_total'] ); ?></dd>
		<dt><?php esc_html_e( 'Reference', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><code>#<?php echo esc_html( (string) $booking['booking_id'] ); ?></code></dd>
		<?php if ( ! empty( $booking['extra_fields'] ) && is_array( $booking['extra_fields'] ) ) : ?>
			<?php foreach ( $booking['extra_fields'] as $extra ) : ?>
				<dt><?php echo esc_html( (string) ( $extra['label'] ?? '' ) ); ?></dt>
				<dd><?php echo esc_html( (string) ( $extra['value'] ?? '' ) ); ?></dd>
			<?php endforeach; ?>
		<?php endif; ?>
	</dl>
