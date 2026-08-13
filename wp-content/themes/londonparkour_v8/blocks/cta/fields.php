<?php
/**
 * CTA — field definition.
 *
 * Takes the source control with multiple => false: the session panel shows ONE
 * class, not a list. The manual row mirrors what a class record contributes so
 * both branches project identically.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'cta',
	'label'      => __( 'CTA', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(
				array(
					'name'  => 'kicker',
					'label' => __( 'Kicker', 'londonparkour_v8' ),
				)
			),
			lp_field_stamp(
				array(
					'name'  => 'coordinates',
					'label' => __( 'Coordinates', 'londonparkour_v8' ),
				)
			),
			lp_field_heading(
				array(
					'name'  => 'headline',
					'label' => __( 'Headline', 'londonparkour_v8' ),
					'type'  => 'textarea',
					'rows'  => 2,
				)
			),
			lp_field_standfirst( array( 'name' => 'subhead' ) ),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		lp_field_source(
			'clasbpro_class',
			__( 'Session', 'londonparkour_v8' ),
			array(
				lp_field_eyebrow(
					array(
						'name'         => 'kicker',
						'label'        => __( 'Kicker', 'londonparkour_v8' ),
						'instructions' => __( 'Unused when a live next session exists. Fallback: "NEXT SESSION".', 'londonparkour_v8' ),
					)
				),
				array(
					'name'  => 'when',
					'label' => __( 'When', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'meta',
					'label' => __( 'Meta', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'foot_label',
					'label' => __( 'Foot label', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'foot_value',
					'label' => __( 'Foot value', 'londonparkour_v8' ),
					'type'  => 'text',
				),
			),
			array(
				'multiple' => false,
				'taxonomy' => 'lp_level',
			)
		),

		array(
			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'primary_action', __( 'Primary action', 'londonparkour_v8' ) ),
			lp_field_action( 'alt_action', __( 'Alternate action', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
