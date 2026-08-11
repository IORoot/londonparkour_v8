<?php
/**
 * Pricing — field definition.
 *
 * Storybook props (`Pricing.js`): coupon-sale rail (`kicker` / `subkicker` /
 * `axis`), comparison rows, and pack tiers. Repeater-only: two lists, the left
 * rail (`row_labels`) and the columns (`tiers`). Each tier's `values` repeater
 * is keyed by `row_key` so the cells follow the rail when it is reordered.
 *
 * The tier CTA is a plain Link field, not the action group: its button style
 * is derived from `highlight` rather than being a second control that has to
 * agree with the first.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'pricing',
	'label'      => __( 'Pricing', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_heading(),
			lp_field_note(),
			array(
				'name'         => 'kicker',
				'label'        => __( 'Kicker', 'londonparkour_v8' ),
				'type'         => 'text',
				'instructions' => __( 'Left-rail head, e.g. "COUPON SALE".', 'londonparkour_v8' ),
			),
			array(
				'name'         => 'subkicker',
				'label'        => __( 'Subkicker', 'londonparkour_v8' ),
				'type'         => 'text',
				'instructions' => __( 'Left-rail second line under the kicker, e.g. "WHAT YOU GET".', 'londonparkour_v8' ),
			),
			array(
				'name'         => 'axis',
				'label'        => __( 'Axis', 'londonparkour_v8' ),
				'type'         => 'text',
				'instructions' => __( 'First comparison rail row, e.g. "PRICE PER CLASS".', 'londonparkour_v8' ),
			),
			lp_field_stamp(
				array(
					'name'  => 'notice',
					'label' => __( 'Notice', 'londonparkour_v8' ),
				)
			),
			array(
				'name'       => 'guarantee',
				'label'      => __( 'How it works', 'londonparkour_v8' ),
				'type'       => 'group',
				'sub_fields' => array(
					lp_field_eyebrow(
						array(
							'name'  => 'kicker',
							'label' => __( 'Kicker', 'londonparkour_v8' ),
						)
					),
					lp_field_note(
						array(
							'name'  => 'copy',
							'label' => __( 'Copy', 'londonparkour_v8' ),
						)
					),
				),
			),
			array(
				'name'  => 'sites_lead',
				'label' => __( 'Sites lead', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'sites_list',
				'label' => __( 'Sites list', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'kit_note',
				'label' => __( 'Kit note', 'londonparkour_v8' ),
				'type'  => 'text',
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'row_labels',
				'label'        => __( 'Comparison rows', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add row', 'londonparkour_v8' ),
				'instructions' => __( 'The left rail under the axis. Defaults: sessions, saving.', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'         => 'row_key',
						'label'        => __( 'Key', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'Lowercase, no spaces, e.g. "sessions" or "saving".', 'londonparkour_v8' ),
					),
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),
			array(
				'name'         => 'tiers',
				'label'        => __( 'Tiers', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'block',
				'button_label' => __( 'Add tier', 'londonparkour_v8' ),
				'instructions' => __( 'Coupon packs: DROP-IN / 5-PACK / 10-PACK.', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'         => 'glyph_icon_id',
						'label'        => __( 'Glyph', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'Sprite id from glyphs.svg, e.g. "glyph-step".', 'londonparkour_v8' ),
					),
					lp_field_eyebrow(
						array(
							'name'         => 'badge',
							'label'        => __( 'Badge', 'londonparkour_v8' ),
							'instructions' => __( 'e.g. "MOST POPULAR" or "BEST VALUE". Rendered after an em dash.', 'londonparkour_v8' ),
						)
					),
					array(
						'name'  => 'price',
						'label' => __( 'Price', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'unit',
						'label' => __( 'Unit', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					lp_field_note(
						array(
							'name'  => 'description',
							'label' => __( 'Description', 'londonparkour_v8' ),
						)
					),
					array(
						'name'         => 'work_out_value',
						'label'        => __( 'Price per class', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'The worked-out rate on the axis row, e.g. "£13.00".', 'londonparkour_v8' ),
					),
					array(
						'name'  => 'work_out_unit',
						'label' => __( 'Price per class unit', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'         => 'highlight',
						'label'        => __( 'Highlight', 'londonparkour_v8' ),
						'type'         => 'true_false',
						'instructions' => __( 'Washes the column and makes its button solid.', 'londonparkour_v8' ),
					),
					array(
						'name'          => 'cta',
						'label'         => __( 'Link', 'londonparkour_v8' ),
						'type'          => 'link',
						'return_format' => 'array',
						'instructions'  => __( 'Button label (link text). URL is ignored when a coupon pack is set — the drawer opens instead.', 'londonparkour_v8' ),
					),
					array(
						'name'          => 'pack',
						'label'         => __( 'Coupon pack', 'londonparkour_v8' ),
						'type'          => 'post_object',
						'post_type'     => array( 'clasbpro_pack' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'ui'            => 1,
						'instructions'  => __( 'Opens the buy drawer for this clasbpro coupon (DROP-IN / 5-PACK / 10-PACK).', 'londonparkour_v8' ),
					),
					array(
						'name'         => 'values',
						'label'        => __( 'Values', 'londonparkour_v8' ),
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => __( 'Add value', 'londonparkour_v8' ),
						'sub_fields'   => array(
							array(
								'name'         => 'row_key',
								'label'        => __( 'Key', 'londonparkour_v8' ),
								'type'         => 'text',
								'instructions' => __( 'Must match a comparison row key.', 'londonparkour_v8' ),
							),
							array(
								'name'  => 'value',
								'label' => __( 'Value', 'londonparkour_v8' ),
								'type'  => 'text',
							),
						),
					),
				),
			),
		),

		lp_field_settings()
	),
);
