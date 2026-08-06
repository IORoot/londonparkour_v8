<?php
/**
 * Noir — dark asymmetric split booking layout.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$pack_slug = 'noir';
$hero_url  = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/hero.jpg' );
if ( '' === $hero_url ) {
	$hero_url = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Registry::pack_url( $pack_slug )
		. \IOROOT_STRIPE_BOOKINGS_PRO\Template_Loader::THEME_DIR
		. '/assets/hero.jpg';
}

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div class="cbfs-form cbfs-form--layout-modern cbfs-noir" data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>" data-cbfs-origin="<?php echo esc_attr( $origin ); ?>">
	<div class="cbfs-noir__split">
		<aside class="cbfs-noir__visual">
			<?php if ( $hero_url ) : ?>
				<img class="cbfs-noir__photo" src="<?php echo esc_url( $hero_url ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
			<?php endif; ?>
			<div class="cbfs-noir__visual-overlay">
				<?php if ( $show_heading ) : ?>
					<p class="cbfs-noir__kicker"><?php esc_html_e( 'Evening sessions', 'class-bookings-with-stripe-pro' ); ?></p>
					<h2 class="cbfs-noir__visual-title"><?php echo esc_html( $view->get_title() ); ?></h2>
					<?php if ( $view->get_meta_text() ) : ?>
						<p class="cbfs-noir__visual-meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</aside>

		<div class="cbfs-noir__main">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else : ?>
				<header class="cbfs-noir__header cbfs-noir__header--mobile">
					<?php if ( $show_heading ) : ?>
						<h2 class="cbfs-noir__title"><?php echo esc_html( $view->get_title() ); ?></h2>
						<?php if ( $view->get_meta_text() ) : ?>
							<p class="cbfs-noir__meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</header>

				<form class="cbfs-form__form cbfs-noir__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?>>
					<div class="cbfs-noir__fields">
						<?php $view->render( 'name-field' ); ?>
						<?php $view->render( 'email-field' ); ?>
						<?php $view->render( 'date-field' ); ?>
						<?php $view->render( 'seats-field' ); ?>
						<?php $view->render( 'pack-panel' ); ?>

						<?php $view->render( 'extra-fields' ); ?>

						<div class="cbfs-noir__summary">
							<?php $view->render( 'total-row' ); ?>
						</div>

						<?php $view->render( 'waiver' ); ?>
						<?php $view->render( 'mailchimp-optin' ); ?>
					</div>

					<div class="cbfs-noir__trust">
						<?php require __DIR__ . '/icons/lock.svg.php'; ?>
						<span><?php esc_html_e( 'Secure Stripe checkout', 'class-bookings-with-stripe-pro' ); ?></span>
					</div>

					<div class="cbfs-noir__footer">
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
