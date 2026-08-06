<?php
/**
 * Booking form layout — HTML structure lives here; components render field fragments only.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View layout; variables extracted from $view.

do_action( 'clasbowi_booking_template_start', $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<div class="cbfs-form__surface">
		<?php if ( $show_heading ) : ?>
			<?php $view->render( 'hero' ); ?>
		<?php endif; ?>

		<div class="cbfs-form__body">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else :
				$preset_date = $view->preset_date;
				$preset_slot = $view->preset_slot_rule_id;
				?>
				<form class="cbfs-form__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?> data-cbfs-appointments="<?php echo $view->is_appointments ? '1' : '0'; ?>"<?php echo $preset_date ? ' data-cbfs-preset-date="' . esc_attr( $preset_date ) . '"' : ''; ?><?php echo $preset_slot ? ' data-cbfs-preset-slot-rule-id="' . esc_attr( $preset_slot ) . '"' : ''; ?>>
					<div class="cbfs-form__card">
						<div class="cbfs-form__grid cbfs-form__grid--2">
							<?php $view->render( 'name-field' ); ?>
							<?php $view->render( 'email-field' ); ?>
						</div>

						<?php $view->render( 'date-field' ); ?>
						<?php $view->render( 'seats-field' ); ?>
						<?php $view->render( 'pack-panel' ); ?>
						<?php $view->render( 'extra-fields' ); ?>
						<?php $view->render( 'total-row' ); ?>
						<?php $view->render( 'waiver' ); ?>
						<?php $view->render( 'mailchimp-optin' ); ?>
						<?php $view->render( 'submit-button' ); ?>
						<?php $view->render( 'form-messages' ); ?>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbowi_booking_template_end', $class_data, $dates );
