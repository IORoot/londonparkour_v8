<?php
/**
 * Dispatch — field definition.
 *
 * Form field markup is hardcoded in the partial to match Dispatch.js
 * (name=EMAIL, underline, page). Editors supply the Mailchimp POST URL and
 * honeypot name from the embed code.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'       => 'dispatch',
	'label'      => __( 'Dispatch', 'londonparkour_v8' ),
	'sub_fields' => array_merge(

		array(
			lp_tab( __( 'Content', 'londonparkour_v8' ) ),
			lp_field_eyebrow(
				array(
					'name'         => 'kicker',
					'label'        => __( 'Kicker', 'londonparkour_v8' ),
					'instructions' => __( 'Product name above the headline, e.g. "The PathFinder".', 'londonparkour_v8' ),
				)
			),
			lp_field_note(),
			lp_field_heading(
				array(
					'name'  => 'headline',
					'label' => __( 'Headline', 'londonparkour_v8' ),
					'type'  => 'textarea',
					'rows'  => 2,
				)
			),
			lp_field_standfirst(),
			lp_field_note(
				array(
					'name'  => 'consent',
					'label' => __( 'Consent', 'londonparkour_v8' ),
				)
			),
			array(
				'name'  => 'privacy_label',
				'label' => __( 'Privacy label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'  => 'privacy_href',
				'label' => __( 'Privacy link', 'londonparkour_v8' ),
				'type'  => 'url',
			),
			array(
				'name'  => 'submit_label',
				'label' => __( 'Submit label', 'londonparkour_v8' ),
				'type'  => 'text',
			),
			array(
				'name'      => 'success_kicker',
				'label'     => __( 'Success kicker', 'londonparkour_v8' ),
				'type'      => 'text',
			),
			array(
				'name'      => 'success_headline',
				'label'     => __( 'Success headline', 'londonparkour_v8' ),
				'type'      => 'text',
			),
			array(
				'name'      => 'success_body',
				'label'     => __( 'Success body', 'londonparkour_v8' ),
				'type'      => 'textarea',
				'rows'      => 2,
				'new_lines' => '',
			),

			lp_tab( __( 'Actions', 'londonparkour_v8' ) ),
			array(
				'name'         => 'form_action',
				'label'        => __( 'Mailchimp form URL', 'londonparkour_v8' ),
				'type'         => 'url',
				'instructions' => __( 'The form action from the Mailchimp embed (subscribe/post?u=…&id=…). Thank-you page: this URL plus ?dispatch=sent.', 'londonparkour_v8' ),
			),
			array(
				'name'         => 'honeypot_name',
				'label'        => __( 'Honeypot field name', 'londonparkour_v8' ),
				'type'         => 'text',
				'instructions' => __( 'The hidden input name from the Mailchimp embed, usually b_{audience}_{list}.', 'londonparkour_v8' ),
			),
		),

		lp_field_settings()
	),
);
