<?php
/**
 * Tutorial 11 — fullscreen form with image background.
 *
 * Swap assets/background.jpg or change the path in style.css.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$bg_url = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::asset_url( 'assets/background.jpg' );
if ( '' === $bg_url ) {
	$slug = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::get_active_gallery_slug();
	if ( '' === $slug ) {
		$slug = 'tutorial-11-fullscreen-bg';
	}
	$bg_url = \IOROOT_STRIPE_BOOKINGS_PRO\Theme_Registry::pack_url( $slug )
		. \IOROOT_STRIPE_BOOKINGS_PRO\Template_Loader::THEME_DIR
		. '/assets/background.jpg';
}

do_action( 'clasbpro_booking_template_start', $class_data, $dates );
?>
<div
	class="cbfs-form cbfs-form--layout-modern cbfs-tut11"
	data-cbfs-class-id="<?php echo esc_attr( (string) $class_data['id'] ); ?>"
	data-cbfs-origin="<?php echo esc_attr( $origin ); ?>"
>
	<div
		class="cbfs-tut11__backdrop"
		aria-hidden="true"
		<?php if ( $bg_url ) : ?>
			style="background-image: url('<?php echo esc_url( $bg_url ); ?>')"
		<?php endif; ?>
	>
		<?php if ( $bg_url ) : ?>
			<img
				class="cbfs-tut11__backdrop-img"
				src="<?php echo esc_url( $bg_url ); ?>"
				alt=""
				width="1200"
				height="757"
				loading="eager"
				decoding="async"
			>
		<?php endif; ?>
	</div>

	<div class="cbfs-form__surface cbfs-tut11__panel">
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
do_action( 'clasbpro_booking_template_end', $class_data, $dates );
