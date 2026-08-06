<?php
/**
 * Statement — field definition.
 *
 * Repeater-only: the principles are copy, not entities, so no source control.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'statement',
	'label'      => __( 'Statement', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_stamp(
				array(
					'name'  => 'since',
					'label' => __( 'Since', 'londonparkour_v8' ),
				)
			),
			lp_field_heading(
				array(
					'name'  => 'statement',
					'label' => __( 'Statement', 'londonparkour_v8' ),
					'type'  => 'textarea',
					'rows'  => 3,
				)
			),
			lp_field_standfirst(
				array(
					'name'  => 'quote',
					'label' => __( 'Quote', 'londonparkour_v8' ),
				)
			),
			array(
				'name'  => 'signature',
				'label' => __( 'Signature', 'londonparkour_v8' ),
				'type'  => 'text',
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'principles',
				'label'        => __( 'Principles', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'row',
				'button_label' => __( 'Add principle', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'         => 'icon_id',
						'label'        => __( 'Glyph', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'A brand glyph id, e.g. "glyph-understanding".', 'londonparkour_v8' ),
					),
					lp_field_eyebrow(
						array(
							'name'  => 'label',
							'label' => __( 'Label', 'londonparkour_v8' ),
						)
					),
					lp_field_note(
						array(
							'name'  => 'body',
							'label' => __( 'Body', 'londonparkour_v8' ),
						)
					),
				),
			),
		),

		lp_field_settings()
	),
);
