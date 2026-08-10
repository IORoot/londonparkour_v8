<?php
/**
 * Enquiries — field definition.
 *
 * Form fields are hardcoded in the partial to match Contact.js FIELD_DEFS.
 * The reach aside is an Actions group.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'enquiries',
	'label'      => __( 'Enquiries', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			array(
				'name'  => 'title',
				'label' => __( 'Title', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_standfirst(
				array(
					'name'  => 'lead',
					'label' => __( 'Standfirst', 'londonparkour_v8' ),
				)
			),
			lp_field_note(),
			array(
				'name'      => 'success_message',
				'label'     => __( 'Success message', 'londonparkour_v8' ),
				'type'      => 'textarea',
				'rows'      => 2,
				'new_lines' => '',
			),
			array(
				'name'      => 'error_message',
				'label'     => __( 'Error message', 'londonparkour_v8' ),
				'type'      => 'textarea',
				'rows'      => 2,
				'new_lines' => '',
			),

			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			array(
				'name'       => 'reach',
				'label'      => __( 'Reach panel', 'londonparkour_v8' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'name'  => 'title',
						'label' => __( 'Title', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'         => 'spots_left',
						'label'        => __( 'Status', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'e.g. "OPEN"', 'londonparkour_v8' ),
					),
					array(
						'name'         => 'rows',
						'label'        => __( 'Rows', 'londonparkour_v8' ),
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => __( 'Add row', 'londonparkour_v8' ),
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
					array(
						'name'  => 'cta_label',
						'label' => __( 'CTA label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'cta_href',
						'label' => __( 'CTA link', 'londonparkour_v8' ),
						'type'  => 'url',
					),
					lp_field_note(),
				),
			),
		),

		lp_field_settings()
	),
);
