<?php
/**
 * Passenger Enquiries — field definition.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'passenger_enquiries',
	'label'      => __( 'Passenger Enquiries', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(
				array(
					'name'  => 'kicker',
					'label' => __( 'Kicker', 'londonparkour_v8' ),
				)
			),
			array(
				'name'  => 'live_label',
				'label' => __( 'Live label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_note(),
			array(
				'name'         => 'facts',
				'label'        => __( 'Facts', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add fact', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'value',
						'label' => __( 'Value', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),

			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'cta', __( 'CTA', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
