<?php

class AF_Pro_Core_Gutenberg {
	function __construct() {
		add_filter( 'af/form/gutenberg/args', array( $this, 'args'), 10, 2 );
		add_filter( 'af/form/gutenberg/fields', array( $this, 'add_editing_block_settings' ), 10, 1 );
		add_filter( 'acf/prepare_field/name=af_block_post_query_message', array( $this, 'populate_post_query_message' ), 10, 1 );
	}

	function args( $args, $form ) {
		if ( $form['editing']['post'] ) {
			$post_editing = get_field( 'af_block_post_editing' );
			switch ( $post_editing ) {
				case 'new':
					$args['post'] = 'new';
					break;
				case 'current':
					$args['post'] = 'current';
					break;
				case 'specific':
					$args['post'] = get_field( 'af_block_post_to_edit' );
					break;
				case 'query':
					$args['post'] = 'query';
					break;
			}
		}

		if ( $form['editing']['user'] ) {
			$user_editing = get_field( 'af_block_user_editing' );
			switch ( $user_editing ) {
				case 'new':
					$args['user'] = 'new';
					break;
				case 'current':
					$args['user'] = 'current';
					break;
				case 'specific':
					$args['specific'] = get_field( 'af_block_user_to_edit' );
					break;
			}
		}

		return $args;
	}

	function add_editing_block_settings( $fields ) {
		$fields[] = array(
			'key' => 'field_af_block_tab_editing',
			'label' => 'Editing',
			'name' => 'af_block_tab_editing',
			'type' => 'tab',
		);

		$fields[] = array(
			'key' => 'field_af_block_post_editing',
			'label' => 'Post editing',
			'name' => 'af_block_post_editing',
			'type' => 'radio',
			'choices' => array(
				'new' => 'Create new post',
				'current' => 'Edit current post',
				'specific' => 'Edit specific post', 
				'query' => 'Edit post from query parameter', 
			),
			'wrapper' => array(
				'width' => '50',
			),
		);

		$fields[] = array(
			'key' => 'field_af_block_post_to_edit',
			'label' => 'Post to edit',
			'name' => 'af_block_post_to_edit',
			'type' => 'post_object',
			'required' => 1,
			'ui' => 1,
			'return_format' => 'id',
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_af_block_post_editing',
						'operator' => '==',
						'value' => 'specific',
					),
				),
			),
			'wrapper' => array(
				'width' => '50',
			),
		);

		$fields[] = array(
			'key' => 'field_af_block_post_query_message',
			'label' => 'Edit post from query parameter',
			'name' => 'af_block_post_query_message',
			'message' => '',
			'type' => 'message',
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_af_block_post_editing',
						'operator' => '==',
						'value' => 'query',
					),
				),
			),
			'wrapper' => array(
				'width' => '50',
			),
		);

		$fields[] = array(
			'key' => 'field_af_block_post_editing_disabled_message',
			'label' => 'Post editing',
			'name' => 'af_block_post_editing_disabled_message',
			'type' => 'message',
			'message' => 'Post editing is not enabled for this form. You can enable it in the <a class="edit-form-link">form settings</a>.',
		);

		$fields[] = array(
			'key' => 'field_af_block_post_editing_divider',
			'label' => '',
			'name' => 'af_block_post_editing_divider',
			'type' => 'divider',
		);

		$fields[] = array(
			'key' => 'field_af_block_user_editing',
			'label' => 'User editing',
			'name' => 'af_block_user_editing',
			'type' => 'radio',
			'return_format' => 'id',
			'choices' => array(
				'new' => 'Register new user',
				'current' => 'Edit current user',
				'specific' => 'Edit specific user', 
			),
			'wrapper' => array(
				'width' => '50',
			),
		);

		$fields[] = array(
			'key' => 'field_af_block_user_to_edit',
			'label' => 'User to edit',
			'name' => 'af_block_user_to_edit',
			'type' => 'user',
			'required' => 1,
			'ui' => 1,
			'return_format' => 'id',
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_af_block_user_editing',
						'operator' => '==',
						'value' => 'specific',
					),
				),
			),
			'wrapper' => array(
				'width' => '50',
			),
		);

		$fields[] = array(
			'key' => 'field_af_block_user_editing_disabled_message',
			'label' => 'User editing',
			'name' => 'af_block_user_editing_disabled_message',
			'type' => 'message',
			'message' => 'User editing is not enabled for this form. You can enable it in the <a class="edit-form-link">form settings</a>.',
		);

		return $fields;
	}

	function populate_post_query_message( $field ) {
		global $post;

		$message = __( 'Specify the post ID to edit using the <code>post</code> query parameter in your URL', 'advanced-form' );
		if ( $post ) {
			$base_url = sprintf( '%s/example', home_url() );
			$example_url = add_query_arg( 'post', 'POST_ID', $base_url );
			$field['message'] = sprintf( '%s:<br><br><code>%s</code>', $message, $example_url );
		} else {
			$field['message'] = $message;
		}

		$field['message'] .= '<br><br>It\'s recommended to enable the "Only allow users to edit their own posts" restriction in the <a class="edit-form-link">form settings</a>.';

		return $field;
	}
}

new AF_Pro_Core_Gutenberg();