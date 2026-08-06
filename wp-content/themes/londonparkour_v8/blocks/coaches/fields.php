<?php
/**
 * Coaches — field definition.
 *
 * Takes the source control for the roster. The lead coach is its own group:
 * it is a different projection of the same entity (portrait + pull quote, not
 * a thumbnail row), so forcing it through the list would mean one shape trying
 * to serve two.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'coaches',
	'label'      => __( 'Coaches', 'londonparkour_v8' ),
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
			lp_field_note(),
			lp_field_standfirst( array( 'name' => 'intro_text' ) ),
			array(
				'name'       => 'lead_coach',
				'label'      => __( 'Lead coach', 'londonparkour_v8' ),
				'type'       => 'group',
				'sub_fields' => array(
					lp_field_media( array( 'name' => 'image' ) ),
					array(
						'name'  => 'image_alt',
						'label' => __( 'Image alt', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'name',
						'label' => __( 'Name', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					lp_field_eyebrow(
						array(
							'name'  => 'meta',
							'label' => __( 'Meta', 'londonparkour_v8' ),
						)
					),
					lp_field_note(
						array(
							'name'  => 'quote',
							'label' => __( 'Quote', 'londonparkour_v8' ),
						)
					),
				),
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		lp_field_source(
			'lp_coach',
			__( 'Coaches', 'londonparkour_v8' ),
			array(
				lp_field_media( array( 'name' => 'thumb' ) ),
				array(
					'name'  => 'thumb_alt',
					'label' => __( 'Thumb alt', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'name',
					'label' => __( 'Name', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'specialty',
					'label' => __( 'Specialty', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'location',
					'label' => __( 'Location', 'londonparkour_v8' ),
					'type'  => 'text',
				),
			)
		),

		array(
			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'link_action', __( 'Link action', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
