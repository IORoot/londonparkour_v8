<?php
/**
 * Tutorials — field definition.
 *
 * Featured panel is its own group (different projection than a shelf tile),
 * same split Locations/Coaches use. The shelf takes the term source control
 * against lp_series.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'tutorials',
	'label'      => __( 'Tutorials', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			array(
				'name'  => 'meta',
				'label' => __( 'Meta', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'kicker',
				'label' => __( 'Kicker', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_heading(
				array(
					'name'  => 'title',
					'label' => __( 'Title', 'londonparkour_v8' ),
				)
			),
			lp_field_note(),
			array(
				'name'       => 'featured',
				'label'      => __( 'Featured series', 'londonparkour_v8' ),
				'type'       => 'group',
				'sub_fields' => array(
					array(
						'name'  => 'tag',
						'label' => __( 'Tag', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'series',
						'label' => __( 'Series label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'title',
						'label' => __( 'Title', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'logline',
						'label' => __( 'Logline', 'londonparkour_v8' ),
						'type'  => 'textarea',
						'rows'  => 3,
					),
					array(
						'name'  => 'meta',
						'label' => __( 'Meta', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'cta_label',
						'label' => __( 'CTA label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'          => 'href',
						'label'         => __( 'Link', 'londonparkour_v8' ),
						'type'          => 'url',
					),
					lp_field_media( array( 'name' => 'poster' ) ),
					array(
						'name'  => 'poster_alt',
						'label' => __( 'Poster alt', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),
			array(
				'name'  => 'shelf_label',
				'label' => __( 'Shelf label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_action( 'shelf_cta', __( 'Shelf CTA', 'londonparkour_v8' ) ),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		lp_field_term_source(
			'lp_series',
			__( 'Series', 'londonparkour_v8' ),
			array(
				array(
					'name'  => 'tag',
					'label' => __( 'Tag', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'title',
					'label' => __( 'Title', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'         => 'episodes',
					'label'        => __( 'Episodes', 'londonparkour_v8' ),
					'type'         => 'text',
					'instructions' => __( 'e.g. "6 EPS"', 'londonparkour_v8' ),
				),
				array(
					'name'          => 'href',
					'label'         => __( 'Link', 'londonparkour_v8' ),
					'type'          => 'url',
				),
				lp_field_media( array( 'name' => 'poster' ) ),
				array(
					'name'  => 'poster_alt',
					'label' => __( 'Poster alt', 'londonparkour_v8' ),
					'type'  => 'text',
				),
			)
		),

		lp_field_settings()
	),
);
