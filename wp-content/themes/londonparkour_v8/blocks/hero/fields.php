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
				'name'         => 'media_slides',
				'label'        => __( 'Media slides (Ken Burns)', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'block',
				'button_label' => __( 'Add slide', 'londonparkour_v8' ),
				'instructions' => __( 'Optional slideshow. When set, these replace the single Media image for the hero background. Leave empty to use Media alone (still Ken Burns zoom).', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'          => 'image',
						'label'         => __( 'Image', 'londonparkour_v8' ),
						'type'          => 'image',
						'return_format' => 'id',
						'preview_size'  => 'medium',
						'required'      => 1,
					),
					array(
						'name'          => 'duration',
						'label'         => __( 'Duration (seconds)', 'londonparkour_v8' ),
						'type'          => 'number',
						'default_value' => 8,
						'step'          => 0.1,
						'min'           => 1,
					),
					array(
						'name'          => 'fade',
						'label'         => __( 'Fade (seconds)', 'londonparkour_v8' ),
						'type'          => 'number',
						'default_value' => 1.2,
						'step'          => 0.1,
						'min'           => 0,
					),
					array(
						'name'          => 'zoom',
						'label'         => __( 'Zoom', 'londonparkour_v8' ),
						'type'          => 'button_group',
						'choices'       => array(
							'in'  => __( 'In', 'londonparkour_v8' ),
							'out' => __( 'Out', 'londonparkour_v8' ),
						),
						'default_value' => 'in',
					),
					array(
						'name'          => 'scale',
						'label'         => __( 'Scale', 'londonparkour_v8' ),
						'type'          => 'number',
						'default_value' => 1.12,
						'step'          => 0.01,
						'min'           => 1,
					),
					array(
						'name'          => 'origin',
						'label'         => __( 'Origin', 'londonparkour_v8' ),
						'type'          => 'text',
						'default_value' => '50% 50%',
						'instructions'  => __( 'CSS transform-origin, e.g. "30% 40%".', 'londonparkour_v8' ),
					),
					array(
						'name'         => 'coordinates',
						'label'        => __( 'Coordinates', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'Top-right stamp for this slide. Re-decodes when the slide becomes active. Falls back to the Hero Coordinates field if empty.', 'londonparkour_v8' ),
					),
					array(
						'name'         => 'link',
						'label'        => __( 'Coordinates link', 'londonparkour_v8' ),
						'type'         => 'url',
						'instructions' => __( 'Opens in a new tab when the coordinates stamp is clicked. Updates with each slide.', 'londonparkour_v8' ),
					),
				),
			),
			array(
				'name'          => 'coordinates',
				'label'         => __( 'Coordinates', 'londonparkour_v8' ),
				'type'          => 'text',
				'instructions'  => __( 'Fallback top-right stamp when a slide omits its own coordinates (e.g. "N 51.5074° / W 0.1278°").', 'londonparkour_v8' ),
			),
			array(
				'name'         => 'coordinates_link',
				'label'        => __( 'Coordinates link', 'londonparkour_v8' ),
				'type'         => 'url',
				'instructions' => __( 'Fallback map/link for the coordinates stamp when a slide omits its own link. Opens in a new tab.', 'londonparkour_v8' ),
			),
			array(
				'name'          => 'board_style',
				'label'         => __( 'Board style', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'next'     => __( 'Next class', 'londonparkour_v8' ),
					'sessions' => __( 'Next sessions', 'londonparkour_v8' ),
				),
				'default_value' => 'next',
				'instructions'  => __( 'Next class pulls the soonest upcoming clasbpro session automatically. Next sessions uses the Items source below.', 'londonparkour_v8' ),
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
