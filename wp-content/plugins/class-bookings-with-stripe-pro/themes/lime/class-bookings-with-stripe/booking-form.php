<?php
/**
 * Lime Checkout — bright two-column booking layout.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$format_price = [ \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::class, 'format_price' ];
$class_name   = $view->get_title();
$unit_price   = (float) ( $class_data['price'] ?? 0 );

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern cbfs-lime" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<?php if ( ! $is_active ) : ?>
		<div class="cbfs-lime__card cbfs-lime__card--solo">
			<?php $view->render( 'notice-inactive' ); ?>
		</div>
	<?php elseif ( $use_external_link && $external_link_url ) : ?>
		<div class="cbfs-lime__card cbfs-lime__card--solo">
			<?php $view->render( 'external-link' ); ?>
		</div>
	<?php elseif ( $use_external_link ) : ?>
		<div class="cbfs-lime__card cbfs-lime__card--solo">
			<?php $view->render( 'notice-invalid-external' ); ?>
		</div>
	<?php elseif ( ! $has_dates ) : ?>
		<div class="cbfs-lime__card cbfs-lime__card--solo">
			<?php $view->render( 'notice-no-dates' ); ?>
		</div>
	<?php else : ?>
		<form class="cbfs-form__form cbfs-lime__layout" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?>>
			<div class="cbfs-lime__main cbfs-lime__card">
				<header class="cbfs-lime__header">
					<h2 class="cbfs-lime__title"><?php esc_html_e( 'Booking', 'class-bookings-with-stripe-pro' ); ?></h2>
					<p class="cbfs-lime__lede"><?php esc_html_e( 'Add your booking details below', 'class-bookings-with-stripe-pro' ); ?></p>
				</header>

				<div class="cbfs-lime__stripe-badge">
					<span class="cbfs-lime__tab-icon"><?php require __DIR__ . '/icons/card.svg.php'; ?></span>
					<?php esc_html_e( 'Stripe checkout', 'class-bookings-with-stripe-pro' ); ?>
				</div>

				<div class="cbfs-lime__fields">
					<div class="cbfs-lime__field-row cbfs-lime__field-row--full">
						<?php $view->render( 'name-field' ); ?>
					</div>
					<div class="cbfs-lime__field-row cbfs-lime__field-row--full">
						<?php $view->render( 'email-field' ); ?>
					</div>
					<div class="cbfs-lime__field-row cbfs-lime__field-row--full">
						<?php $view->render( 'date-field' ); ?>
					</div>

					<?php $view->render( 'extra-fields' ); ?>
					<?php $view->render( 'waiver' ); ?>
					<?php $view->render( 'mailchimp-optin' ); ?>
				</div>

				<div class="cbfs-lime__actions">
					<?php $view->render( 'submit-button' ); ?>
					<?php $view->render( 'form-messages' ); ?>
				</div>
			</div>

			<aside class="cbfs-lime__sidebar">
				<div class="cbfs-lime__card cbfs-lime__card--side">
					<h3 class="cbfs-lime__card-title"><?php esc_html_e( 'Select your session type', 'class-bookings-with-stripe-pro' ); ?></h3>
					<div class="cbfs-lime__session-seats">
						<?php $view->render( 'seats-field' ); ?>
						<?php $view->render( 'pack-panel' ); ?>
					</div>
					<?php if ( $show_heading && $view->get_meta_text() ) : ?>
						<p class="cbfs-lime__session-meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
					<?php endif; ?>
				</div>

				<div class="cbfs-lime__card cbfs-lime__card--side cbfs-lime__card--summary">
					<h3 class="cbfs-lime__card-title"><?php esc_html_e( 'Order summary', 'class-bookings-with-stripe-pro' ); ?></h3>

					<div class="cbfs-lime__summary">
						<div class="cbfs-lime__summary-row">
							<span class="cbfs-lime__summary-label"><?php esc_html_e( 'Plan', 'class-bookings-with-stripe-pro' ); ?></span>
							<span class="cbfs-lime__summary-value"><?php echo esc_html( $class_name ); ?></span>
						</div>
						<?php if ( $unit_price > 0 ) : ?>
							<div class="cbfs-lime__summary-row cbfs-lime__summary-row--muted">
								<span class="cbfs-lime__summary-label"><?php esc_html_e( 'Subtotal', 'class-bookings-with-stripe-pro' ); ?></span>
								<span class="cbfs-lime__summary-value cbfs-lime__subtotal" data-cbfs-unit-price="<?php echo esc_attr( (string) $unit_price ); ?>">
									<?php echo esc_html( $format_price( $unit_price ) ); ?>
								</span>
							</div>
						<?php endif; ?>
						<div class="cbfs-lime__summary-row cbfs-lime__summary-row--muted">
							<span class="cbfs-lime__summary-label"><?php esc_html_e( 'Tax', 'class-bookings-with-stripe-pro' ); ?></span>
							<span class="cbfs-lime__summary-value"><?php echo esc_html( $format_price( 0 ) ); ?></span>
						</div>
						<div class="cbfs-lime__summary-row cbfs-lime__summary-row--total">
							<?php $view->render( 'total-row' ); ?>
						</div>
					</div>
				</div>
			</aside>
		</form>
	<?php endif; ?>
</div>
<?php
do_action( 'clasbpro_booking_template_end', $class_data, $dates );
