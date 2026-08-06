<?php
/**
 * Single-column layout — email first, one full-width field per row.
 *
 * @var \IOROOT_STRIPE_BOOKINGS\Booking_Form_View|\IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$start = defined( 'CLASBOWPRO_VERSION' ) ? 'clasbpro_booking_template_start' : 'clasbowi_booking_template_start';
$end   = defined( 'CLASBOWPRO_VERSION' ) ? 'clasbpro_booking_template_end' : 'clasbowi_booking_template_end';

do_action( $start, $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern twentytwentyfive-example3-layout" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
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
			<?php else : ?>
				<form class="cbfs-form__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?>>
					<div class="cbfs-form__card">
						<?php $view->render( 'email-field' ); ?>
						<?php $view->render( 'name-field' ); ?>
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
do_action( $end, $class_data, $dates );
