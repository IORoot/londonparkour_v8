<?php
/**
 * Wellspring — centered card booking layout.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern cbfs-wellspring" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<div class="cbfs-wellspring__canvas">
		<div class="cbfs-wellspring__card">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else : ?>
				<header class="cbfs-wellspring__header">
					<h2 class="cbfs-wellspring__title"><?php esc_html_e( 'Book appointment', 'class-bookings-with-stripe-pro' ); ?></h2>
					<?php if ( $show_heading && $view->get_title() ) : ?>
						<p class="cbfs-wellspring__subtitle">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: class name */
									__( 'Trusted booking for %s.', 'class-bookings-with-stripe-pro' ),
									$view->get_title()
								)
							);
							?>
						</p>
					<?php else : ?>
						<p class="cbfs-wellspring__subtitle"><?php esc_html_e( 'Trusted care for your next session — simple scheduling, secure checkout.', 'class-bookings-with-stripe-pro' ); ?></p>
					<?php endif; ?>
				</header>

				<ul class="cbfs-wellspring__highlights" aria-label="<?php esc_attr_e( 'Booking highlights', 'class-bookings-with-stripe-pro' ); ?>">
					<li class="cbfs-wellspring__highlight">
						<span class="cbfs-wellspring__highlight-icon" aria-hidden="true"><?php require __DIR__ . '/icons/calendar.svg.php'; ?></span>
						<span class="cbfs-wellspring__highlight-label"><?php esc_html_e( 'Flexible dates', 'class-bookings-with-stripe-pro' ); ?></span>
					</li>
					<li class="cbfs-wellspring__highlight">
						<span class="cbfs-wellspring__highlight-icon" aria-hidden="true"><?php require __DIR__ . '/icons/users.svg.php'; ?></span>
						<span class="cbfs-wellspring__highlight-label"><?php esc_html_e( 'Group friendly', 'class-bookings-with-stripe-pro' ); ?></span>
					</li>
					<li class="cbfs-wellspring__highlight">
						<span class="cbfs-wellspring__highlight-icon" aria-hidden="true"><?php require __DIR__ . '/icons/shield.svg.php'; ?></span>
						<span class="cbfs-wellspring__highlight-label"><?php esc_html_e( 'Secure pay', 'class-bookings-with-stripe-pro' ); ?></span>
					</li>
				</ul>

				<form class="cbfs-form__form cbfs-wellspring__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?> data-cbfs-appointments="<?php echo $view->is_appointments ? '1' : '0'; ?>">
					<div class="cbfs-wellspring__grid cbfs-wellspring__grid--2">
						<?php $view->render( 'name-field' ); ?>
						<?php $view->render( 'email-field' ); ?>
					</div>

					<?php $view->render( 'date-field' ); ?>
					<?php $view->render( 'seats-field' ); ?>
					<?php $view->render( 'pack-panel' ); ?>

					<?php $view->render( 'extra-fields' ); ?>

					<div class="cbfs-wellspring__total">
						<?php $view->render( 'total-row' ); ?>
					</div>

					<?php $view->render( 'waiver' ); ?>
					<?php $view->render( 'mailchimp-optin' ); ?>

					<div class="cbfs-wellspring__actions">
						<?php $view->render( 'submit-button' ); ?>
						<?php $view->render( 'form-messages' ); ?>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
do_action( 'clasbpro_booking_template_end', $class_data, $dates );
