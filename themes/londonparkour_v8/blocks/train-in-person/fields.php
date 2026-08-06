<?php
/**
 * TrainInPerson — field definition.
 *
 * REFERENCE IMPLEMENTATION. Every block's fields.php follows this shape:
 *   - Return name, label and sub_fields.
 *   - Build sub_fields from the shared helpers in app/setup/acf-fields.php so
 *     labels stay identical across every block.
 *   - Tabs in the canonical order: Content, Items, Actions, Settings. Omit any
 *     tab the block has no fields for; never reorder them.
 *   - Never set 'key' — `wp lp acf:build` derives keys from the field path.
 *   - Cross-field references use 'lp_conditional' naming a SIBLING field, which
 *     the build resolves to a real key.
 *
 * This block's list is Locations, so it takes the standard three-way source
 * control. Six blocks do; four are repeater-only. See the plan's source matrix.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'train_in_person',
	'label'      => __( 'Train In Person', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_stamp(),
			lp_field_note(),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		// The standard source control. Manual rows mirror what a location
		// record contributes, so both branches project identically.
		lp_field_source(
			'lp_location',
			__( 'Sites', 'londonparkour_v8' ),
			array(
				lp_field_eyebrow(
					array(
						'name'         => 'tag',
						'label'        => __( 'Tag', 'londonparkour_v8' ),
						'instructions' => __( 'e.g. "FLAGSHIP INDOOR SITE"', 'londonparkour_v8' ),
					)
				),
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
			)
		),

		array(
			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'primary_action', __( 'Primary action', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
