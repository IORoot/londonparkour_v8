<?php
/**
 * PrivateCoaching — field definition.
 *
 * Repeater-only: the fact rail is this block's own copy, not entities,
 * so there is no source control.
 *
 * Two layouts (button_group):
 *   - booking (default) — homepage 05C primary band + REQUEST 1:1 CTA
 *   - offer — raised base-200 two-column offer strip
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

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
					'instructions'   => __( 'Button label. Link is used only when no appointment class is selected.', 'londonparkour_v8' ),
				)
			),
			array(
				'name'           => 'appointment_class',
				'label'          => __( 'Appointment class', 'londonparkour_v8' ),
				'type'           => 'post_object',
				'post_type'      => array( 'clasbpro_class' ),
				'return_format'  => 'id',
				'allow_null'     => 1,
				'ui'             => 1,
				'instructions'   => __( 'When set, the book button opens the shared booking overlay for this clasbpro appointment class.', 'londonparkour_v8' ),
				'lp_conditional' => array( array( array( 'field' => 'layout', 'operator' => '==', 'value' => 'booking' ) ) ),
			),
		),

		lp_field_settings()
	),
);
