<?php
/**
 * Coaches — field definition.
 *
 * Two layouts (`grid` | `lead`), matching Storybook Blocks/Coaches:
 *   - grid — Homepage V8 four-up bio cards
 *   - lead — head-coach profile + roster list
 *
 * The lead coach is its own group (portrait + pull quote), not a list row.
 * Manual source rows carry the union of both projections; each layout reads
 * the fields it needs.
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
			array(
				'name'          => 'layout',
				'label'         => __( 'Layout', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'grid' => __( 'Grid', 'londonparkour_v8' ),
					'lead' => __( 'Lead', 'londonparkour_v8' ),
				),
				'default_value' => 'grid',
			),
			lp_field_eyebrow(),
			array(
				'name'           => 'meta',
				'label'          => __( 'Meta', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'grid' ) ) ),
			),
			lp_field_heading(
				array(
					'name'  => 'headline',
					'label' => __( 'Headline', 'londonparkour_v8' ),
					'type'  => 'textarea',
					'rows'  => 2,
				)
			),
			lp_field_standfirst(
				array(
					'name'           => 'lead',
					'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'grid' ) ) ),
				)
			),
			array(
				'name'           => 'footnote',
				'label'          => __( 'Footnote', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'grid' ) ) ),
			),
			lp_field_note(
				array(
					'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'lead' ) ) ),
				)
			),
			lp_field_standfirst(
				array(
					'name'           => 'intro_text',
					'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'lead' ) ) ),
				)
			),
			array(
				'name'           => 'lead_coach',
				'label'          => __( 'Lead coach', 'londonparkour_v8' ),
				'type'           => 'group',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'lead' ) ) ),
				'sub_fields'     => array(
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
				array(
					'name'  => 'index',
					'label' => __( 'Index', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'tag',
					'label' => __( 'Tag', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'name',
					'label' => __( 'Name', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'role',
					'label' => __( 'Role', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'      => 'bio',
					'label'     => __( 'Bio', 'londonparkour_v8' ),
					'type'      => 'textarea',
					'rows'      => 3,
					'new_lines' => '',
				),
				lp_field_media( array( 'name' => 'photo' ) ),
				lp_field_media( array( 'name' => 'thumb' ) ),
				array(
					'name'  => 'thumb_alt',
					'label' => __( 'Thumb alt', 'londonparkour_v8' ),
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
