<?php
/**
 * Example 4 — “Secure checkout” layout with credit-card hero.
 *
 * @var \IOROOT_STRIPE_BOOKINGS\Booking_Form_View|\IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$start = defined( 'CLASBOWPRO_VERSION' ) ? 'clasbpro_booking_template_start' : 'clasbowi_booking_template_start';
$end   = defined( 'CLASBOWPRO_VERSION' ) ? 'clasbpro_booking_template_end' : 'clasbowi_booking_template_end';
$format_price = defined( 'CLASBOWPRO_VERSION' )
	? [ \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::class, 'format_price' ]
	: [ \IOROOT_STRIPE_BOOKINGS\Helpers::class, 'format_price' ];

do_action( $start, $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern cbfs-ex4" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<div class="cbfs-ex4__shell">
		<div class="cbfs-ex4__hero" aria-hidden="true">
			<?php require __DIR__ . '/credit-card.svg.php'; ?>
			<p class="cbfs-ex4__hero-tagline"><?php esc_html_e( 'Secure checkout powered by Stripe', 'class-bookings-with-stripe-pro' ); ?></p>
		</div>

		<div class="cbfs-ex4__panel">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else : ?>
				<header class="cbfs-ex4__summary">
					<?php if ( $show_heading ) : ?>
						<h2 class="cbfs-ex4__title"><?php echo esc_html( $view->get_title() ); ?></h2>
						<?php if ( $view->get_meta_text() ) : ?>
							<p class="cbfs-ex4__meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( ! empty( $class_data['price'] ) ) : ?>
						<p class="cbfs-ex4__price">
							<span class="cbfs-ex4__price-label"><?php esc_html_e( 'From', 'class-bookings-with-stripe-pro' ); ?></span>
							<span class="cbfs-ex4__price-value"><?php echo esc_html( $format_price( (float) $class_data['price'] ) ); ?></span>
						</p>
					<?php endif; ?>
				</header>

				<form class="cbfs-form__form cbfs-ex4__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?>>
					<div class="cbfs-ex4__fields">
						<section class="cbfs-ex4__section" aria-labelledby="cbfs-ex4-details-heading">
							<h3 id="cbfs-ex4-details-heading" class="cbfs-ex4__section-title"><?php esc_html_e( 'Your details', 'class-bookings-with-stripe-pro' ); ?></h3>
							<?php $view->render( 'name-field' ); ?>
							<?php $view->render( 'email-field' ); ?>
						</section>

						<section class="cbfs-ex4__section" aria-labelledby="cbfs-ex4-booking-heading">
							<h3 id="cbfs-ex4-booking-heading" class="cbfs-ex4__section-title"><?php esc_html_e( 'Booking', 'class-bookings-with-stripe-pro' ); ?></h3>
							<?php $view->render( 'date-field' ); ?>
							<?php $view->render( 'seats-field' ); ?>
							<?php $view->render( 'pack-panel' ); ?>
							<?php $view->render( 'extra-fields' ); ?>
							<?php $view->render( 'total-row' ); ?>
						</section>

						<?php $view->render( 'waiver' ); ?>
						<?php $view->render( 'mailchimp-optin' ); ?>

						<div class="cbfs-ex4__actions">
							<?php $view->render( 'submit-button' ); ?>
							<?php $view->render( 'form-messages' ); ?>
						</div>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
do_action( $end, $class_data, $dates );
