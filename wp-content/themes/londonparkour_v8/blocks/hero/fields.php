<?php
/**
 * Hero — field definition.
 *
 * Homepage default is board_style=featured (single featured class board).
 * Sessions board is the master alternate (board_style=sessions) and keeps
 * the CPT source control.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'hero',
	'label'      => __( 'Hero', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_heading(
				array(
					'name'  => 'headline',
					'label' => __( 'Headline', 'londonparkour_v8' ),
					'type'  => 'textarea',
					'rows'  => 2,
					'instructions' => __( 'Use a line break for a second line (as in the design).', 'londonparkour_v8' ),
				)
			),
			lp_field_standfirst( array( 'name' => 'lead' ) ),
			lp_field_media(),
			array(
				'name'          => 'coordinates',
				'label'         => __( 'Coordinates', 'londonparkour_v8' ),
				'type'          => 'text',
				'instructions'  => __( 'Top-right stamp, e.g. "N 51.5074° / W 0.1278°".', 'londonparkour_v8' ),
			),
			array(
				'name'          => 'board_style',
				'label'         => __( 'Board style', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'featured' => __( 'Featured class', 'londonparkour_v8' ),
					'sessions' => __( 'Next sessions', 'londonparkour_v8' ),
				),
				'default_value' => 'featured',
			),
			array(
				'name'           => 'featured_class',
				'label'          => __( 'Featured class', 'londonparkour_v8' ),
				'type'           => 'group',
				'layout'         => 'block',
				'lp_conditional' => array( array( array( 'field' => 'board_style', 'operator' => '==', 'value' => 'featured' ) ) ),
				'sub_fields'     => array(
					array(
						'name'  => 'title',
						'label' => __( 'Board title', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					lp_field_stamp(
						array(
							'name'  => 'stamp',
							'label' => __( 'Stamp', 'londonparkour_v8' ),
						)
					),
					array(
						'name'  => 'time',
						'label' => __( 'Time', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'when',
						'label' => __( 'When', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'name',
						'label' => __( 'Class name', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'meta',
						'label' => __( 'Meta', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'spaces',
						'label' => __( 'Spaces', 'londonparkour_v8' ),
						'type'  => 'text',
					),
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
					array(
						'name'  => 'foot_label',
						'label' => __( 'Foot label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'foot_href',
						'label' => __( 'Foot link', 'londonparkour_v8' ),
						'type'  => 'url',
					),
					array(
						'name'  => 'foot_meta',
						'label' => __( 'Foot meta', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),
			array(
				'name'           => 'board_title',
				'label'          => __( 'Board title', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'board_style', 'operator' => '==', 'value' => 'sessions' ) ) ),
			),
			lp_field_stamp(
				array(
					'name'           => 'board_stamp',
					'label'          => __( 'Board stamp', 'londonparkour_v8' ),
					'lp_conditional' => array( array( array( 'field' => 'board_style', 'operator' => '==', 'value' => 'sessions' ) ) ),
				)
			),
			array(
				'name'           => 'board_foot_label',
				'label'          => __( 'Board foot label', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'board_style', 'operator' => '==', 'value' => 'sessions' ) ) ),
			),
			array(
				'name'           => 'board_foot_href',
				'label'          => __( 'Board foot link', 'londonparkour_v8' ),
				'type'           => 'url',
				'lp_conditional' => array( array( array( 'field' => 'board_style', 'operator' => '==', 'value' => 'sessions' ) ) ),
			),
			array(
				'name'           => 'board_foot_count',
				'label'          => __( 'Board foot count', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'board_style', 'operator' => '==', 'value' => 'sessions' ) ) ),
			),
			array(
				'name'  => 'scroll_label',
				'label' => __( 'Scroll label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'rating',
				'label' => __( 'Rating', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'         => 'trust',
				'label'        => __( 'Trust marks', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add trust mark', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		// Sessions source only meaningful for sessions board; still present so
		// editors can switch styles without losing data.
		lp_field_source(
			'clasbpro_class',
			__( 'Sessions', 'londonparkour_v8' ),
			array(
				array(
					'name'  => 'time',
					'label' => __( 'Time', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'day',
					'label' => __( 'Day', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'title',
					'label' => __( 'Title', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'location',
					'label' => __( 'Location', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'spaces',
					'label' => __( 'Spaces', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'sold_out',
					'label' => __( 'Sold out', 'londonparkour_v8' ),
					'type'  => 'true_false',
				),
			),
			array( 'taxonomy' => 'lp_level' )
		),

		array(
			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'primary_action', __( 'Primary action', 'londonparkour_v8' ) ),
			lp_field_action( 'secondary_action', __( 'Secondary action', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
