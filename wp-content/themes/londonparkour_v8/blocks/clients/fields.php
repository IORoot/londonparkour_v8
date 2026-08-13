<?php
/**
 * Clients — field definition.
 *
 * Repeater of client logos: label, optional link, optional image. Defaults
 * in the block fill the live white transparent GIF set when the repeater is empty.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'clients',
	'label'      => __( 'Clients', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			array(
				'name'  => 'meta',
				'label' => __( 'Meta', 'londonparkour_v8' ),
				'type'  => 'text',
			),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
			array(
				'name'         => 'logos',
				'label'        => __( 'Logos', 'londonparkour_v8' ),
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add logo', 'londonparkour_v8' ),
				'sub_fields'   => array(
					array(
						'name'  => 'label',
						'label' => __( 'Label', 'londonparkour_v8' ),
						'type'  => 'text',
					),
					array(
						'name'  => 'href',
						'label' => __( 'Link', 'londonparkour_v8' ),
						'type'  => 'url',
					),
					lp_field_media( array( 'name' => 'image', 'label' => __( 'Logo', 'londonparkour_v8' ) ) ),
				),
			),
		),

		lp_field_settings()
	),
);
