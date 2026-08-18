<?php
/**
 * Workshop — field definition.
 *
 * Minimal: only an optional background image override and an optional eyebrow
 * override. All other content (title, date, location, description, spots,
 * CTA) is sourced live from the next upcoming clasbpro_class one-off event —
 * there are no manual fallback fields.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'workshop',
	'label'      => __( 'Workshop Spotlight', 'londonparkour_v8' ),
	'sub_fields' => array_merge(
		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),
			lp_field_media(
				array(
					'name'         => 'workshop_image',
					'label'        => __( 'Background image override', 'londonparkour_v8' ),
					'instructions' => __( 'Optional. Falls back to the upcoming workshop\'s featured image. Landscape photos with a clear sky or open space work best — the bottom third is covered by the content band.', 'londonparkour_v8' ),
					'allow_null'   => 1,
				)
			),
		),
		lp_field_settings()
	),
);
