<?php
/**
 * PrivateCoaching — field definition.
 *
 * Repeater-only: the fact rail is this block's own copy, not entities, so
 * there is no source control.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'private_coaching',
	'label'      => __( 'Private coaching', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_heading(
				array(
					'name'  => 'headline',
					'label' => __( 'Headline', 'londonparkour_v8' ),
				)
			),
			lp_field_standfirst(),
			lp_field_media(),
			array(
				'name'  => 'media_alt',
				'label' => __( 'Media alt', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'fare_label',
				'label' => __( 'Fare label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'amount',
				'label' => __( 'Amount', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'unit',
				'label' => __( 'Unit', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_note(
				array(
					'name'  => 'reassure',
					'label' => __( 'Reassurance', 'londonparkour_v8' ),
				)
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
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
			lp_field_action( 'primary_action', __( 'Primary action', 'londonparkour_v8' ) ),
			lp_field_action( 'secondary_action', __( 'Secondary action', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
