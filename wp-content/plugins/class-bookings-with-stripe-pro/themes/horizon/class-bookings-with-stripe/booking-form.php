<?php
/**
 * Horizon — split-screen booking layout (dark brand panel + light form).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern cbfs-horizon" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<div class="cbfs-horizon__shell">
		<aside class="cbfs-horizon__brand" aria-hidden="false">
			<div class="cbfs-horizon__brand-inner">
				<div class="cbfs-horizon__logo" aria-hidden="true">
					<span class="cbfs-horizon__logo-mark"></span>
					<span class="cbfs-horizon__logo-text"><?php esc_html_e( 'Horizon', 'class-bookings-with-stripe-pro' ); ?></span>
				</div>
				<?php if ( $show_heading ) : ?>
					<p class="cbfs-horizon__welcome"><?php esc_html_e( 'Welcome to', 'class-bookings-with-stripe-pro' ); ?></p>
					<h2 class="cbfs-horizon__brand-title"><?php echo esc_html( $view->get_title() ); ?></h2>
					<?php if ( $view->get_meta_text() ) : ?>
						<p class="cbfs-horizon__brand-meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
				<div class="cbfs-horizon__scene" aria-hidden="true">
					<?php require __DIR__ . '/illustration.svg.php'; ?>
				</div>
			</div>
		</aside>

		<div class="cbfs-horizon__panel">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else : ?>
				<header class="cbfs-horizon__header">
					<h2 class="cbfs-horizon__title"><?php esc_html_e( 'Book a class appointment', 'class-bookings-with-stripe-pro' ); ?></h2>
					<p class="cbfs-horizon__lede"><?php esc_html_e( 'Choose your date, confirm your details, and pay securely in a few steps.', 'class-bookings-with-stripe-pro' ); ?></p>
				</header>

				<ul class="cbfs-horizon__steps" aria-label="<?php esc_attr_e( 'Booking steps', 'class-bookings-with-stripe-pro' ); ?>">
					<li class="cbfs-horizon__step is-active">
						<span class="cbfs-horizon__step-icon" aria-hidden="true"><?php require __DIR__ . '/icons/calendar.svg.php'; ?></span>
						<span class="cbfs-horizon__step-label"><?php esc_html_e( 'Pick a date', 'class-bookings-with-stripe-pro' ); ?></span>
					</li>
					<li class="cbfs-horizon__step">
						<span class="cbfs-horizon__step-icon" aria-hidden="true"><?php require __DIR__ . '/icons/users.svg.php'; ?></span>
						<span class="cbfs-horizon__step-label"><?php esc_html_e( 'Your party', 'class-bookings-with-stripe-pro' ); ?></span>
					</li>
					<li class="cbfs-horizon__step">
						<span class="cbfs-horizon__step-icon" aria-hidden="true"><?php require __DIR__ . '/icons/card.svg.php'; ?></span>
						<span class="cbfs-horizon__step-label"><?php esc_html_e( 'Pay securely', 'class-bookings-with-stripe-pro' ); ?></span>
					</li>
				</ul>

				<form class="cbfs-form__form cbfs-horizon__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?> data-cbfs-appointments="<?php echo $view->is_appointments ? '1' : '0'; ?>">
					<div class="cbfs-horizon__grid cbfs-horizon__grid--2">
						<?php $view->render( 'name-field' ); ?>
						<?php $view->render( 'email-field' ); ?>
					</div>

					<?php $view->render( 'date-field' ); ?>
					<?php $view->render( 'seats-field' ); ?>
					<?php $view->render( 'pack-panel' ); ?>

					<?php $view->render( 'extra-fields' ); ?>

					<div class="cbfs-horizon__total">
						<?php $view->render( 'total-row' ); ?>
					</div>

					<?php $view->render( 'waiver' ); ?>
					<?php $view->render( 'mailchimp-optin' ); ?>

					<div class="cbfs-horizon__actions">
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
