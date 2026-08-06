<?php
/**
 * Summit — immersive full-bleed alpine booking.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$pack_slug = 'summit';
$hero_url  = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/hero.jpg' );
if ( '' === $hero_url ) {
	$hero_url = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Registry::pack_url( $pack_slug )
		. \IOROOT_STRIPE_BOOKINGS_PRO\Template_Loader::THEME_DIR
		. '/assets/hero.jpg';
}

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div
	class="cbfs-form cbfs-form--layout-modern cbfs-summit"
	data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>"
	data-cbfs-origin="<?php echo esc_attr( $origin ); ?>"
	<?php if ( $hero_url ) : ?>
		style="--cbfs-summit-hero: url('<?php echo esc_url( $hero_url ); ?>')"
	<?php endif; ?>
>
	<div class="cbfs-summit__backdrop" aria-hidden="true">
		<?php if ( $hero_url ) : ?>
			<img class="cbfs-summit__backdrop-img" src="<?php echo esc_url( $hero_url ); ?>" alt="" width="1600" height="1067" loading="eager" decoding="async">
		<?php endif; ?>
	</div>

	<div class="cbfs-summit__panel-wrap">
		<div class="cbfs-summit__glass">
			<?php if ( ! $is_active ) : ?>
				<?php $view->render( 'notice-inactive' ); ?>
			<?php elseif ( $use_external_link && $external_link_url ) : ?>
				<?php $view->render( 'external-link' ); ?>
			<?php elseif ( $use_external_link ) : ?>
				<?php $view->render( 'notice-invalid-external' ); ?>
			<?php elseif ( ! $has_dates ) : ?>
				<?php $view->render( 'notice-no-dates' ); ?>
			<?php else : ?>
				<?php if ( $show_heading ) : ?>
					<header class="cbfs-summit__header">
						<p class="cbfs-summit__kicker"><?php esc_html_e( 'Adventure booking', 'class-bookings-with-stripe-pro' ); ?></p>
						<h2 class="cbfs-summit__title"><?php echo esc_html( $view->get_title() ); ?></h2>
						<?php if ( $view->get_meta_text() ) : ?>
							<p class="cbfs-summit__meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>

				<form class="cbfs-form__form cbfs-summit__form" novalidate data-cbfs-show-seats-remaining="<?php echo $view->show_seats_remaining ? '1' : '0'; ?>"<?php echo $view->is_one_off_fixed_date ? ' data-cbfs-one-off-date="1"' : ''; ?>>
					<section class="cbfs-summit__section" aria-labelledby="cbfs-summit-guest-heading">
						<h3 id="cbfs-summit-guest-heading" class="cbfs-summit__section-title"><?php esc_html_e( 'Guest details', 'class-bookings-with-stripe-pro' ); ?></h3>
						<div class="cbfs-summit__grid">
							<?php $view->render( 'name-field' ); ?>
							<?php $view->render( 'email-field' ); ?>
						</div>
					</section>

					<section class="cbfs-summit__section" aria-labelledby="cbfs-summit-session-heading">
						<h3 id="cbfs-summit-session-heading" class="cbfs-summit__section-title"><?php esc_html_e( 'Session details', 'class-bookings-with-stripe-pro' ); ?></h3>
						<?php $view->render( 'date-field' ); ?>
						<?php $view->render( 'seats-field' ); ?>
						<?php $view->render( 'pack-panel' ); ?>
						<?php $view->render( 'extra-fields' ); ?>
						<?php $view->render( 'total-row' ); ?>
					</section>

					<?php $view->render( 'waiver' ); ?>
					<?php $view->render( 'mailchimp-optin' ); ?>

					<div class="cbfs-summit__trust">
						<?php require __DIR__ . '/icons/shield.svg.php'; ?>
						<span><?php esc_html_e( 'Encrypted checkout', 'class-bookings-with-stripe-pro' ); ?></span>
					</div>

					<div class="cbfs-summit__footer">
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
