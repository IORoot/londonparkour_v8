<?php
/**
 * Booking steps — pen G4HzU. Visual only; SESSION is the active overlay step.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Form_View $view
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_steps = array(
	array(
		'index'  => '01',
		'label'  => __( 'SESSION', 'londonparkour_v8' ),
		'active' => true,
	),
	array(
		'index'  => '02',
		'label'  => __( 'DETAILS', 'londonparkour_v8' ),
		'active' => false,
	),
	array(
		'index'  => '03',
		'label'  => __( 'PAY', 'londonparkour_v8' ),
		'active' => false,
	),
);
?>
		<ol class="cbfs-form__steps" role="list" aria-label="<?php esc_attr_e( 'Booking steps', 'londonparkour_v8' ); ?>">
			<?php foreach ( $lp_steps as $lp_step ) : ?>
				<li class="cbfs-form__step<?php echo $lp_step['active'] ? ' is-active' : ''; ?>">
					<span class="cbfs-form__step-index"><?php echo esc_html( $lp_step['index'] ); ?></span>
					<span class="cbfs-form__step-label"><?php echo esc_html( $lp_step['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
