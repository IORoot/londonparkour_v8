<?php

class AF_Pro_Admin_Restrictions {
  function __construct() {
    add_filter( 'af/form/restriction_settings_fields', array( $this, 'add_settings_fields' ), 10, 1 );
  }

  function add_settings_fields( $field_group ) {
		$field_group['fields'][] = array(
			'key' => 'field_form_restriction_edit_own_posts',
			'label' => __( 'Only allow users to edit their own posts', 'advanced-forms' ),
			'name' => 'form_restriction_edit_own_posts',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'placeholder' => '',
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'ui' => true,
			'default_value' => 0,
		);

		$field_group['fields'][] = array (
			'key' => 'field_form_restriction_edit_own_posts_message',
			'label' => __( 'Message if user is not author of post', 'advanced-forms' ),
			'name' => 'form_restriction_edit_own_posts_message',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_form_restriction_edit_own_posts',
						'operator' => '==',
						'value' => '1',
					),
				),
			),
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
      'rows' => 4,
			'default_value' => '',
		);

    return $field_group;
  }
}

new AF_Pro_Admin_Restrictions();