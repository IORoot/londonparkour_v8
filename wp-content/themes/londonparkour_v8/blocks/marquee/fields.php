<?php
/**
 * Marquee — field definition.
 *
 * Repeater-only: no source control (the plan's matrix lists this block, and
 * Statement, PrivateCoaching and Pricing, as repeater-only).
 *
 * `direction` and `speed` sit under Content, not Settings — lp_field_settings()
 * is identical in every block by design and is not extended per-block.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'marquee',
	'label'      => __( 'Marquee', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			array(
				'name'          => 'direction',
				'label'         => __( 'Direction', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'left'  => __( 'Left', 'londonparkour_v8' ),
					'right' => __( 'Right', 'londonparkour_v8' ),
				),
				'default_value' => 'left',
			),
			array(
				'name'          => 'speed',
				'label'         => __( 'Speed', 'londonparkour_v8' ),
				'type'          => 'number',
				'instructions'  => __( 'Pixels per second. Default 60.', 'londonparkour_v8' ),
				'default_value' => 60,
				'min'           => 10,
				'max'           => 300,
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'items',
				'label'        => __( 'Items', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add item', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),
		),

		lp_field_settings()
	),
);
