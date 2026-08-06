<?php
/**
 * Maison — full-width editorial booking card (no image backdrop).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div
	class="cbfs-form cbfs-form--layout-modern cbfs-maison"
	data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>"
	data-cbfs-origin="<?php echo esc_attr( $origin ); ?>"
>
	<div class="cbfs-maison__stage">
		<?php if ( ! $is_active ) : ?>
			<div class="cbfs-maison__card">
				<?php $view->render( 'notice-inactive' ); ?>
			</div>
		<?php elseif ( $use_external_link && $external_link_url ) : ?>
			<div class="cbfs-maison__card">
				<?php $view->render( 'external-link' ); ?>
			</div>
		<?php elseif ( $use_external_link ) : ?>
			<div class="cbfs-maison__card">
				<?php $view->render( 'notice-invalid-external' ); ?>
			</div>
		<?php elseif ( ! $has_dates ) : ?>
			<div class="cbfs-maison__card">
				<?php $view->render( 'notice-no-dates' ); ?>
			</div>
		<?php else : ?>
			<div class="cbfs-maison__card">
				<?php if ( $show_heading ) : ?>
					<header class="cbfs-maison__header">
						<p class="cbfs-maison__eyebrow"><?php esc_html_e( 'Workshop booking', 'class-bookings-with-stripe-pro' ); ?></p>
						<h2 class="cbfs-maison__title"><?php echo esc_html( $view->get_title() ); ?></h2>
						<?php if ( $view->get_meta_text() ) : ?>
							<p class="cbfs-maison__meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
						<?php endif; ?>
						<div class="cbfs-maison__rule" aria-hidden="true"></div>
					</header>
				<?php endif; ?>

				<form class="cbfs-form__form cbfs-maison__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?> data-cbfs-appointments="<?php echo $view->is_appointments ? '1' : '0'; ?>"<?php echo $view->preset_date ? ' data-cbfs-preset-date="' . esc_attr( $view->preset_date ) . '"' : ''; ?><?php echo $view->preset_slot_rule_id ? ' data-cbfs-preset-slot-rule-id="' . esc_attr( $view->preset_slot_rule_id ) . '"' : ''; ?>>
					<div class="cbfs-maison__fields">
						<div class="cbfs-maison__field cbfs-maison__field--name">
							<?php $view->render( 'name-field' ); ?>
						</div>
						<div class="cbfs-maison__field cbfs-maison__field--email">
							<?php $view->render( 'email-field' ); ?>
						</div>
						<div class="cbfs-maison__field cbfs-maison__field--date">
							<?php $view->render( 'date-field' ); ?>
						</div>
						<div class="cbfs-maison__field cbfs-maison__field--seats">
							<?php $view->render( 'seats-field' ); ?>
							<?php $view->render( 'pack-panel' ); ?>
						</div>
						<?php $view->render( 'extra-fields' ); ?>
						<?php $view->render( 'total-row' ); ?>
						<?php $view->render( 'waiver' ); ?>
						<?php $view->render( 'mailchimp-optin' ); ?>
					</div>

					<div class="cbfs-maison__footer">
						<?php $view->render( 'submit-button' ); ?>
						<?php $view->render( 'form-messages' ); ?>
					</div>
				</form>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
do_action( 'clasbpro_booking_template_end', $class_data, $dates );
