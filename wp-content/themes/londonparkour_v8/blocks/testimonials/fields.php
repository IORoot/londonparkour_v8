<?php
/**
 * Testimonials — field definition.
 *
 * CPT-backed quote board: Latest / Random / Choose among 5-star
 * testimonials that have a quote. SEE ALL replaces the old (03) meta.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'testimonials',
	'label'      => __( 'Testimonials', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(),

			lp_tab( __( 'Items', 'londonparkour_v8' ) ),
		),

		lp_field_testimonial_source(),

		array(
			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			lp_field_action( 'see_all_action', __( 'See all reviews', 'londonparkour_v8' ) ),
			lp_field_action( 'review_action', __( 'Google review', 'londonparkour_v8' ) ),
		),

		lp_field_settings()
	),
);
