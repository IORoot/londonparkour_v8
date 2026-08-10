<?php
/**
 * Other Ways — field definition.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'other_ways',
	'label'      => __( 'Other Ways', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			array(
				'name'  => 'meta_left',
				'label' => __( 'Meta left', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'meta_right',
				'label' => __( 'Meta right', 'londonparkour_v8' ),
				'type'  => 'text',
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'columns',
				'label'        => __( 'Columns', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'block',
				'button_label' => __( 'Add column', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'         => 'icon_id',
						'label'        => __( 'Icon', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'An icon id, e.g. "icon-map-pin".', 'londonparkour_v8' ),
					),
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'      => 'value',
						'label'     => __( 'Value', 'londonparkour_v8' ),
						'type'      => 'textarea',
						'rows'      => 3,
						'new_lines' => 'br',
					),
					array(
						'name'  => 'link_label',
						'label' => __( 'Link label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'link_href',
						'label' => __( 'Link URL', 'londonparkour_v8' ),
						'type'  => 'url',
					),
					lp_field_note(
						array(
							'instructions' => __( 'Shown when no link label is set.', 'londonparkour_v8' ),
						)
					),
				),
			),
		),

		lp_field_settings()
	),
);
