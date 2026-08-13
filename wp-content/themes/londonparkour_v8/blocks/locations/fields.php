<?php
/**
 * Locations — field definition.
 *
 * Takes the source control for the site list. The flagship is its own group:
 * it is a different projection of the same entity (a photo card, not a text
 * row), so forcing it through the list would mean one shape serving two.
 *
 * Manual site rows mirror what a location record contributes — `title` is the
 * record's own post title — so both branches project identically.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'locations',
	'label'      => __( 'Locations', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_heading(),
			lp_field_note(),
			array(
				'name'       => 'flagship',
				'label'      => __( 'Flagship site', 'londonparkour_v8' ),
				'type'       => 'group',
				'sub_fields' => array(
					lp_field_eyebrow(
						array(
							'name'         => 'tag',
							'label'        => __( 'Tag', 'londonparkour_v8' ),
							'instructions' => __( 'e.g. "FLAGSHIP INDOOR SITE"', 'londonparkour_v8' ),
						)
					),
					array(
						'name'  => 'name',
						'label' => __( 'Name', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'meta',
						'label' => __( 'Meta', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					lp_field_media( array( 'name' => 'image' ) ),
					array(
						'name'  => 'image_alt',
						'label' => __( 'Image alt', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'          => 'link',
						'label'         => __( 'Link', 'londonparkour_v8' ),
						'type'          => 'link',
						'return_format' => 'array',
					),
				),
			),
			array(
				'name'  => 'tagline',
				'label' => __( 'Tagline', 'londonparkour_v8' ),
				'type'  => 'text',
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		lp_field_source(
			'lp_location',
			__( 'Sites', 'londonparkour_v8' ),
			array(
				array(
					'name'  => 'title',
					'label' => __( 'Name', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'meta',
					'label' => __( 'Meta', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'type',
					'label' => __( 'Type', 'londonparkour_v8' ),
					'type'  => 'text',
				),
			)
		),

		lp_field_settings()
	),
);
