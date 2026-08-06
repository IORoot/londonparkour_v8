<?php
defined( 'ABSPATH' ) || exit;

if ( $view->is_coupon() ) {
	$purchase = $view->purchase;
	if ( ! $purchase ) {
		return;
	}
	?>
	<dl class="cbfs-status__details">
		<dt><?php esc_html_e( 'Coupon', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) ( $purchase['pack_name'] ?? '' ) ); ?></dd>
		<?php if ( ! empty( $purchase['code'] ) ) : ?>
		<dt><?php esc_html_e( 'Code', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><code data-cbfs-pack-code-value><?php echo esc_html( (string) $purchase['code'] ); ?></code></dd>
		<?php endif; ?>
		<?php if ( ! empty( $purchase['uses'] ) ) : ?>
		<dt><?php esc_html_e( 'Uses', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) $purchase['uses'] ); ?></dd>
		<?php endif; ?>
		<?php if ( ! empty( $purchase['expires_label'] ) ) : ?>
		<dt><?php esc_html_e( 'Expires', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) $purchase['expires_label'] ); ?></dd>
		<?php endif; ?>
		<dt><?php esc_html_e( 'Customer', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) ( $purchase['customer_name'] ?? '' ) ); ?></dd>
		<dt><?php esc_html_e( 'Email', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) ( $purchase['customer_email'] ?? '' ) ); ?></dd>
		<dt><?php esc_html_e( 'Total', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><?php echo esc_html( (string) ( $purchase['amount_total'] ?? '' ) ); ?></dd>
		<dt><?php esc_html_e( 'Reference', CLASBOWPRO_TEXT_DOMAIN ); ?></dt>
		<dd><code>#<?php echo esc_html( (string) ( $purchase['purchase_id'] ?? '' ) ); ?></code></dd>
	</dl>
	<?php if ( ! empty( $purchase['restore_token'] ) ) : ?>
	<span hidden data-cbfs-pack-restore-token="<?php echo esc_attr( (string) $purchase['restore_token'] ); ?>"></span>
	<?php endif; ?>
	<?php
	return;
}

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
