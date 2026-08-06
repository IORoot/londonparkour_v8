<?php
defined( 'ABSPATH' ) || exit;

$class_id        = (int) $view->class_data['id'];
$waiver_input_id = 'cbfs-waiver-' . $class_id;
$waiver_desc_id  = 'cbfs-waiver-desc-' . $class_id;
$waiver_html     = \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::waiver_label_kses(
	(string) apply_filters( 'clasbowi_waiver_label_html', $view->labels['waiver_label'], $view->class_data )
);
?>
		<div class="cbfs-form__row cbfs-form__row--check cbfs-form__row--waiver">
			<div class="cbfs-form__waiver" data-cbfs-waiver-group>
				<input
					class="cbfs-form__check-input"
					type="checkbox"
					name="waiver_accepted"
					id="<?php echo esc_attr( $waiver_input_id ); ?>"
					value="1"
					required
					aria-label="<?php echo esc_attr__( 'Accept the waiver', CLASBOWPRO_TEXT_DOMAIN ); ?>"
					aria-describedby="<?php echo esc_attr( $waiver_desc_id ); ?>"
				>
				<div class="cbfs-form__waiver-body" id="<?php echo esc_attr( $waiver_desc_id ); ?>" data-cbfs-waiver-label>
					<?php echo $waiver_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- waiver_label_kses(). ?>
				</div>
			</div>
			<?php if ( $view->waiver_page_url ) : ?>
				<p class="cbfs-form__waiver-page-link">
					<a class="cbfs-form__link" href="<?php echo esc_url( $view->waiver_page_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( apply_filters( 'clasbowi_waiver_page_link_text', $view->labels['waiver_page_link_text'], $view->class_data ) ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
