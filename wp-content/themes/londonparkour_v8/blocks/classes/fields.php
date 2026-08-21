<?php
/**
 * Classes — field definition.
 *
 * Takes the source control for the board. A manual row carries the fields
 * components/board-row.php reads for the V3 Classes sell board (thumb + glyph,
 * no Spaces column); a class record supplies the same names where it can
 * (`thumb` from the featured image via lp_resolve_source).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'classes',
	'label'      => __( 'Classes', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_heading(),
			lp_field_note(),
			array(
				'name'  => 'board_title',
				'label' => __( 'Board title', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_stamp(),
			array(
				'name'         => 'foot_note',
				'label'        => __( 'Foot note', 'londonparkour_v8' ),
				'type'         => 'text',
				'instructions' => __( 'Fallback only. When weekly classes exist, the board foot is built from their count and sites (e.g. “3 SITES · 5 CLASSES A WEEK”).', 'londonparkour_v8' ),
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		lp_field_source(
			'clasbpro_class',
			__( 'Sessions', 'londonparkour_v8' ),
			array(
				lp_field_media( array( 'name' => 'thumb' ) ),
				array(
					'name'  => 'thumb_alt',
					'label' => __( 'Thumb alt', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'         => 'glyph_icon_id',
					'label'        => __( 'Glyph icon', 'londonparkour_v8' ),
					'type'         => 'text',
					'instructions' => __( 'An icon id, e.g. "icon-sun".', 'londonparkour_v8' ),
				),
				array(
					'name'  => 'time',
					'label' => __( 'Time', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'date_label',
					'label' => __( 'Date label', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'title',
					'label' => __( 'Title', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'subtitle',
					'label' => __( 'Subtitle', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'location',
					'label' => __( 'Location', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'level',
					'label' => __( 'Level', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'price',
					'label' => __( 'Price', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'price_label',
					'label' => __( 'Price label', 'londonparkour_v8' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'book_label',
					'label' => __( 'Book label', 'londonparkour_v8' ),
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
		),

		lp_field_settings()
	),
);
