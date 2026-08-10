<?php
/**
 * Section Directory — field definition.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'section_directory',
	'label'      => __( 'Section Directory', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'rows',
				'label'        => __( 'Rows', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'block',
				'button_label' => __( 'Add row', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'  => 'index',
						'label' => __( 'Index', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'title',
						'label' => __( 'Title', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'meta',
						'label' => __( 'Meta', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'         => 'icon',
						'label'        => __( 'Icon', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'An icon id, e.g. "icon-academic-cap".', 'londonparkour_v8' ),
					),
					array(
						'name'  => 'href',
						'label' => __( 'Link', 'londonparkour_v8' ),
						'type'  => 'url',
					),
				),
			),
		),

		lp_field_settings()
	),
);
