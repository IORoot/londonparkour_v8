<?php
/**
 * Clients — field definition.
 *
 * Repeater-only: logos are text wordmarks in the source, not entities.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'clients',
	'label'      => __( 'Clients', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			array(
				'name'  => 'meta',
				'label' => __( 'Meta', 'londonparkour_v8' ),
				'type'  => 'text',
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'logos',
				'label'        => __( 'Logos', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add logo', 'londonparkour_v8' ),
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
