<?php
/**
 * PrivateCoaching — field definition.
 *
 * Repeater-only: the fact rail and booking grid are this block's own copy,
 * not entities, so there is no source control.
 *
 * Two layouts (button_group):
 *   - booking (default) — homepage 05C primary band + week-slot panel
 *   - offer — raised base-200 two-column offer strip
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_slot_choices = array(
	'available' => __( 'Available', 'londonparkour_v8' ),
	'taken'     => __( 'Taken', 'londonparkour_v8' ),
	'closed'    => __( 'Closed', 'londonparkour_v8' ),
	'selected'  => __( 'Selected', 'londonparkour_v8' ),
);

$lp_slot_day = static function ( string $name, string $label ) use ( $lp_slot_choices ): array {
	return array(
		'name'          => $name,
		'label'         => $label,
		'type'          => 'select',
		'choices'       => $lp_slot_choices,
		'default_value' => 'closed',
	);
};

return array(
	'name'       => 'private_coaching',
	'label'      => __( 'Private coaching', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			array(
				'name'          => 'layout',
				'label'         => __( 'Layout', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'booking' => __( 'Booking', 'londonparkour_v8' ),
					'offer'   => __( 'Offer', 'londonparkour_v8' ),
				),
				'default_value' => 'booking',
			),
			lp_field_eyebrow(),
			array(
				'name'           => 'meta',
				'label'          => __( 'Meta', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			lp_field_heading(
				array(
					'name'  => 'headline',
					'label' => __( 'Headline', 'londonparkour_v8' ),
					'type'  => 'textarea',
					'rows'  => 2,
				)
			),
			lp_field_standfirst(),
			lp_field_media(),
			array(
				'name'  => 'media_alt',
				'label' => __( 'Media alt', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'           => 'caption_kicker',
				'label'          => __( 'Caption kicker', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'caption',
				'label'          => __( 'Caption', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'media_position',
				'label'          => __( 'Media position', 'londonparkour_v8' ),
				'type'           => 'button_group',
				'choices'        => array(
					'start' => __( 'Start', 'londonparkour_v8' ),
					'end'   => __( 'End', 'londonparkour_v8' ),
				),
				'default_value'  => 'start',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'  => 'fare_label',
				'label' => __( 'Fare label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'amount',
				'label' => __( 'Amount', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'unit',
				'label' => __( 'Unit', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			lp_field_note(
				array(
					'name'  => 'reassure',
					'label' => __( 'Reassurance', 'londonparkour_v8' ),
				)
			),
			array(
				'name'           => 'panel_kicker',
				'label'          => __( 'Panel kicker', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'panel_title',
				'label'          => __( 'Panel title', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'panel_lead',
				'label'          => __( 'Panel lead', 'londonparkour_v8' ),
				'type'           => 'textarea',
				'rows'           => 2,
				'new_lines'      => '',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'week_range',
				'label'          => __( 'Week range', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'selected_label',
				'label'          => __( 'Selected label', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
			array(
				'name'           => 'selected_value',
				'label'          => __( 'Selected value', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
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
				'name'           => 'days',
				'label'          => __( 'Days', 'londonparkour_v8' ),
				'type'           => 'repeater',
				'layout'         => 'table',
				'button_label'   => __( 'Add day', 'londonparkour_v8' ),
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
				'sub_fields'     => array(
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'date',
						'label' => __( 'Date', 'londonparkour_v8' ),
						'type'  => 'text',
					),
				),
			),
			array(
				'name'           => 'rows',
				'label'          => __( 'Rows', 'londonparkour_v8' ),
				'type'           => 'repeater',
				'layout'         => 'table',
				'button_label'   => __( 'Add row', 'londonparkour_v8' ),
				'instructions'   => __( 'Booking week grid — one row per time slot.', 'londonparkour_v8' ),
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
				'sub_fields'     => array(
					array(
						'name'  => 'time',
						'label' => __( 'Time', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					$lp_slot_day( 'mon', __( 'Mon', 'londonparkour_v8' ) ),
					$lp_slot_day( 'tue', __( 'Tue', 'londonparkour_v8' ) ),
					$lp_slot_day( 'wed', __( 'Wed', 'londonparkour_v8' ) ),
					$lp_slot_day( 'thu', __( 'Thu', 'londonparkour_v8' ) ),
					$lp_slot_day( 'fri', __( 'Fri', 'londonparkour_v8' ) ),
					$lp_slot_day( 'sat', __( 'Sat', 'londonparkour_v8' ) ),
					$lp_slot_day( 'sun', __( 'Sun', 'londonparkour_v8' ) ),
				),
			),

			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action(
				'primary_action',
				__( 'Primary action', 'londonparkour_v8' ),
				array(
					'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'offer' ) ) ),
				)
			),
			lp_field_action(
				'secondary_action',
				__( 'Secondary action', 'londonparkour_v8' ),
				array(
					'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'offer' ) ) ),
				)
			),
			lp_field_action(
				'book_action',
				__( 'Book action', 'londonparkour_v8' ),
				array(
					'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
				)
			),
		),

		lp_field_settings()
	),
);
