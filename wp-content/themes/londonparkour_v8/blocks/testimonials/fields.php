<?php
/**
 * Testimonials — field definition.
 *
 * Repeater-only: quotes are copy, not entities.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'testimonials',
	'label'      => __( 'Testimonials', 'londonparkour_v8' ),
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
				'name'         => 'quotes',
				'label'        => __( 'Quotes', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'block',
				'button_label' => __( 'Add quote', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'         => 'index',
						'label'        => __( 'Index', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'e.g. "01"', 'londonparkour_v8' ),
					),
					array(
						'name'  => 'quote',
						'label' => __( 'Quote', 'londonparkour_v8' ),
						'type'  => 'textarea',
						'rows'  => 3,
					),
					array(
						'name'  => 'attribution',
						'label' => __( 'Attribution', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),
		),

		lp_field_settings()
	),
);
