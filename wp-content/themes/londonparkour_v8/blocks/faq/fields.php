<?php
/**
 * FAQ — field definition (shared Contact flat + DocsFaq grouped).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'faq',
	'label'      => __( 'FAQ', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			array(
				'name'          => 'mode',
				'label'         => __( 'Mode', 'londonparkour_v8' ),
				'type'          => 'button_group',
				'choices'       => array(
					'flat'   => __( 'Flat', 'londonparkour_v8' ),
					'groups' => __( 'Groups', 'londonparkour_v8' ),
				),
				'default_value' => 'flat',
			),
			array(
				'name'           => 'meta_left',
				'label'          => __( 'Meta left', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'mode', 'operator' => '==', 'value' => 'flat' ) ) ),
			),
			array(
				'name'           => 'meta_right',
				'label'          => __( 'Meta right', 'londonparkour_v8' ),
				'type'           => 'text',
				'lp_conditional' => array( array( array( 'field' => 'mode', 'operator' => '==', 'value' => 'flat' ) ) ),
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'           => 'items',
				'label'          => __( 'Items', 'londonparkour_v8' ),
				'type'           => 'repeater',
				'layout'         => 'block',
				'button_label'   => __( 'Add item', 'londonparkour_v8' ),
				'lp_conditional' => array( array( array( 'field' => 'mode', 'operator' => '==', 'value' => 'flat' ) ) ),
				'sub_fields'     => array(
					array(
						'name'  => 'question',
						'label' => __( 'Question', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'      => 'answer',
						'label'     => __( 'Answer', 'londonparkour_v8' ),
						'type'      => 'textarea',
						'rows'      => 4,
						'new_lines' => '',
					),
					array(
						'name'          => 'default_open',
						'label'         => __( 'Open by default', 'londonparkour_v8' ),
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
					),
				),
			),
			array(
				'name'           => 'still_stuck',
				'label'          => __( 'Still stuck', 'londonparkour_v8' ),
				'type'           => 'group',
				'layout'         => 'block',
				'lp_conditional' => array( array( array( 'field' => 'mode', 'operator' => '==', 'value' => 'flat' ) ) ),
				'sub_fields'     => array(
					array(
						'name'  => 'title',
						'label' => __( 'Title', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'      => 'body',
						'label'     => __( 'Body', 'londonparkour_v8' ),
						'type'      => 'textarea',
						'rows'      => 3,
						'new_lines' => '',
					),
					array(
						'name'  => 'email',
						'label' => __( 'Email', 'londonparkour_v8' ),
						'type'  => 'email',
					),
				),
			),
			array(
				'name'           => 'groups',
				'label'          => __( 'Groups', 'londonparkour_v8' ),
				'type'           => 'repeater',
				'layout'         => 'block',
				'button_label'   => __( 'Add group', 'londonparkour_v8' ),
				'lp_conditional' => array( array( array( 'field' => 'mode', 'operator' => '==', 'value' => 'groups' ) ) ),
				'sub_fields'     => array(
					array(
						'name'  => 'id',
						'label' => __( 'Anchor ID', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'entries',
						'label' => __( 'Entries', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'         => 'icon',
						'label'        => __( 'Icon', 'londonparkour_v8' ),
						'type'         => 'text',
						'instructions' => __( 'An icon id, e.g. "icon-academic-cap".', 'londonparkour_v8' ),
					),
					array(
						'name'         => 'items',
						'label'        => __( 'Items', 'londonparkour_v8' ),
						'type'         => 'repeater',
						'layout'       => 'block',
						'button_label' => __( 'Add question', 'londonparkour_v8' ),
						'sub_fields'   => array(
							array(
								'name'  => 'question',
								'label' => __( 'Question', 'londonparkour_v8' ),
								'type'  => 'text',
							),
							array(
								'name'      => 'answer',
								'label'     => __( 'Answer', 'londonparkour_v8' ),
								'type'      => 'textarea',
								'rows'      => 4,
								'new_lines' => '',
							),
						),
					),
				),
			),
		),

		lp_field_settings()
	),
);
