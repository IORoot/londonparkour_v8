<?php
/**
 * Hero — field definition.
 *
 * Takes the source control for the board. A manual row mirrors what a class
 * record contributes, so both branches project identically.
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
				)
			),
			lp_field_standfirst( array( 'name' => 'lead' ) ),
			lp_field_media(),
			array(
				'name'  => 'board_title',
				'label' => __( 'Board title', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_stamp(
				array(
					'name'  => 'board_stamp',
					'label' => __( 'Board stamp', 'londonparkour_v8' ),
				)
			),
			array(
				'name'  => 'board_foot_label',
				'label' => __( 'Board foot label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'board_foot_href',
				'label' => __( 'Board foot link', 'londonparkour_v8' ),
				'type'  => 'url',
			),
			array(
				'name'  => 'board_foot_count',
				'label' => __( 'Board foot count', 'londonparkour_v8' ),
				'type'  => 'text',
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

		lp_field_source(
			'lp_class',
			__( 'Sessions', 'londonparkour_v8' ),
			array(
				array(
					'name'  => 'time',
					'label' => __( 'Time', 'londonparkour_v8' ),
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
			)
		),

		array(
			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'primary_action', __( 'Primary action', 'londonparkour_v8' ) ),
			lp_field_action( 'secondary_action', __( 'Secondary action', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
