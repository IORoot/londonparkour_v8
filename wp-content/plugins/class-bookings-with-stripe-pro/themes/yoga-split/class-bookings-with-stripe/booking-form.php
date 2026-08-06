<?php
/**
 * Example 5 — split-screen yoga booking layout.
 *
 * @var \IOROOT_STRIPE_BOOKINGS\Booking_Form_View|\IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$start = defined( 'CLASBOWPRO_VERSION' ) ? 'clasbpro_booking_template_start' : 'clasbowi_booking_template_start';
$end   = defined( 'CLASBOWPRO_VERSION' ) ? 'clasbpro_booking_template_end' : 'clasbowi_booking_template_end';

do_action( $start, $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern cbfs-ex5" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<div class="cbfs-ex5__split">
		<aside class="cbfs-ex5__visual">
			<img
				class="cbfs-ex5__yoga-photo"
				src="<?php echo esc_url( \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/yoga-hero.jpg' ) ); ?>"
				alt=""
				width="1200"
				height="757"
				loading="lazy"
				decoding="async"
			>
			<div class="cbfs-ex5__visual-overlay">
				<p class="cbfs-ex5__visual-kicker"><?php esc_html_e( 'Mind · Body · Breath', 'class-bookings-with-stripe-pro' ); ?></p>
				<h2 class="cbfs-ex5__visual-title"><?php esc_html_e( 'Book your place on the mat', 'class-bookings-with-stripe-pro' ); ?></h2>
				<p class="cbfs-ex5__visual-lede"><?php esc_html_e( 'Small groups, expert guidance, and a calm space to move.', 'class-bookings-with-stripe-pro' ); ?></p>
			</div>
		</aside>

		<div class="cbfs-ex5__main">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else : ?>
				<header class="cbfs-ex5__header">
					<?php if ( $show_heading ) : ?>
						<h2 class="cbfs-ex5__class-title"><?php echo esc_html( $view->get_title() ); ?></h2>
						<?php if ( $view->get_meta_text() ) : ?>
							<p class="cbfs-ex5__class-meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</header>

				<div class="cbfs-ex5__pay-strip">
					<?php require __DIR__ . '/credit-card.svg.php'; ?>
					<p class="cbfs-ex5__pay-copy"><?php esc_html_e( 'Secure payment — you’ll finish checkout with Stripe', 'class-bookings-with-stripe-pro' ); ?></p>
				</div>

				<form class="cbfs-form__form cbfs-ex5__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?>>
					<div class="cbfs-ex5__fields">
						<?php $view->render( 'name-field' ); ?>
						<?php $view->render( 'email-field' ); ?>
						<?php $view->render( 'date-field' ); ?>
						<?php $view->render( 'seats-field' ); ?>
						<?php $view->render( 'pack-panel' ); ?>
						<?php $view->render( 'extra-fields' ); ?>
						<?php $view->render( 'total-row' ); ?>
						<?php $view->render( 'waiver' ); ?>
						<?php $view->render( 'mailchimp-optin' ); ?>
					</div>
					<div class="cbfs-ex5__footer">
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
