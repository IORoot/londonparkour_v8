<?php
	
class AF_Pro_Core_Editing {
	
	function __construct() {
		add_action( 'af/form/submission', array( $this, 'handle_editing_form' ), 10, 3 );
		add_action( 'af/form/args', array( $this, 'evaluate_current' ), 10, 2 );
		
		add_action( 'af/field/prefill_value', array( $this, 'prefill_fields' ), 10, 4 );
		add_action( 'af/form/acf_data', array( $this, 'add_post_id_to_acf' ), 10, 3 );
		add_action( 'af/form/hidden_fields', array( $this, 'add_hidden_post_id_field' ), 10, 2 );

		add_action( 'af/form/validate', array( $this, 'validate_user' ), 10, 2 );

		add_filter( 'af/merge_tags/custom', array( $this, 'add_custom_merge_tags' ), 10, 2 );
		add_filter( 'af/merge_tags/resolve', array( $this, 'resolve_merge_tag' ), 10, 2 );
		
		add_filter( 'af/form/valid_form', array( $this, 'valid_form' ), 10, 1 );
		add_filter( 'af/form/from_post', array( $this, 'form_from_post' ), 10, 2 );
		add_action( 'af/form/to_post', array( $this, 'form_to_post' ), 10, 2 );
	}

	/**
	 * Handle form editing on submit
	 *
	 * @since 1.4.0
	 */
	function handle_editing_form( $form, $fields, $args ) {
		// Create/edit user before post. This ensures user ID is available for assignment as post author.
		if ( $form['editing']['user'] && isset( $args['user'] ) ) {
			$this->handle_user_edit( $form, $args['user'], $args );
		}
		if ( $form['editing']['post'] && isset( $args['post'] ) ) {
			$this->handle_post_edit( $form, $args['post'], $args );
		}
	}

	/**
	 * Evaluate current post or user when post or user argument is set to "current"
	 *
	 * @since 1.6.0
	 *
	 */
	function evaluate_current( $args, $form ) {
		// Default to "new" if no post argument is set
		if ( $form['editing']['post'] && ! isset( $args['post'] ) ) {
			$args['post'] = 'new';
		}

		// Add current post ID if post argument is "current"
		if ( $form['editing']['post'] && isset( $args['post'] ) && 'current' == $args['post'] ) {
			if ( $current_post_id = get_the_ID() ) {
				$args['post'] = $current_post_id;
			}
		}

		// Get post ID from 'post' query parameter if post argument is "param"
		if ( $form['editing']['post'] && isset( $args['post'] ) && 'param' == $args['post'] ) {
			$query_param = 'post';
			$query_param = apply_filters( 'af/form/editing/query_param', $query_param, $form, $args );
			$query_param = apply_filters( 'af/form/editing/query_param/id=' . $form['post_id'], $query_param, $form, $args );
			$query_param = apply_filters( 'af/form/editing/query_param/key=' . $form['key'], $query_param, $form, $args );

			if ( isset( $_GET[ $query_param ] ) && is_numeric( $_GET[ $query_param ] ) ) {
				$args['post'] = $_GET[ $query_param ];
			}
		}

		// Default to "new" if no user argument is set
		if ( $form['editing']['user'] && ! isset( $args['user'] ) ) {
			$args['user'] = 'new';
		}

		// Add current user ID if user argument is "current"
		if ( $form['editing']['user'] && isset( $args['user'] ) && 'current' == $args['user'] ) {
			if ( $current_user_id = get_current_user_id() ) {
				$args['user'] = $current_user_id;
			}
		}

		return $args;
	}
	
	
	/**
	 * Handle form editing of post, either create or edit
	 *
	 * @since 1.4.0
	 *
	 */
	function handle_post_edit( $form, $post_id, $args ) {

		if ( 'new' != $post_id && ! is_numeric( $post_id ) ) {
			return false;
		}
		
		$post_data = array(
			'post_type' => $form['editing']['post']['post_type'],
		);
		
		
		// Get post title from fields
		$post_title = _af_resolve_field_picker_value( $form['editing']['post']['post_title'] );
		if ( false !== $post_title ) {
			$post_data['post_title'] = $post_title;
		}
		
		// Get post content from fields
		$post_content = _af_resolve_field_picker_value( $form['editing']['post']['post_content'] );
		if ( false !== $post_content ) {
			$post_data['post_content'] = $post_content;
		}
		
		
		if ( 'new' == $post_id ) {
			// Get post status for new post
			$post_status_set = isset( $form['editing']['post']['post_status'] );
			$post_status = $post_status_set ? $form['editing']['post']['post_status'] : 'publish';
		
			// Either title, content, or excerpt must be non-empty.
			// Hence content defaults to " ".
			$post_data = wp_parse_args( $post_data, array(
				'post_content' => ' ',
				'post_status' => $post_status,
			));
			
		} else {
			
			$post_data = wp_parse_args( $post_data, array(
				'ID' => $post_id, 
			));
			
		}
		
		
		// Filter post data before insert/update
		$post_data = apply_filters( 'af/form/editing/post_data', $post_data, $form, $args );
		$post_data = apply_filters( 'af/form/editing/post_data/id=' . $form['post_id'], $post_data, $form, $args );
		$post_data = apply_filters( 'af/form/editing/post_data/key='. $form['key'], $post_data, $form, $args );
		
		
		if ( 'new' == $post_id ) {
			$updated_post_id = wp_insert_post( $post_data, true );
		} else {
			$updated_post_id = wp_update_post( $post_data, true );
		}
		
		if ( ! $updated_post_id || is_wp_error( $updated_post_id ) ) {
			return false;
		}
		
		
		$post = get_post( $updated_post_id );
		
		if ( ! $post || is_null( $post ) ) {
			return false;
		}
		
		
		// Transfer custom fields
		$map_all_exists = isset( $form['editing']['post']['map_all_fields'] );
		$map_all_fields = $map_all_exists ? $form['editing']['post']['map_all_fields'] == true : false;
		if ( $map_all_fields ) {
			af_save_all_fields( $post->ID );
		} else if ( $form['editing']['post']['custom_fields'] ) {
			foreach ( $form['editing']['post']['custom_fields'] as $field_key ) {
				af_save_field( $field_key, $post->ID );
			}
		}

		// Save post ID to submission object
		AF()->submission['post'] = $post->ID;
		
		
		// Trigger action after post has been created/updated
		$action = ( 'new' == $post_id ) ? 'af/form/editing/post_created' : 'af/form/editing/post_updated';
		
		do_action( $action, $post, $form, $args );
		do_action( $action . '/id=' . $form['post_id'], $post, $form, $args );
		do_action( $action . '/key=' . $form['key'], $post, $form, $args );
		
		
		return true;
		
	}
	
	
	function handle_user_edit( $form, $user_id, $args ) {
		if ( 'new' != $user_id && ! is_numeric( $user_id ) ) {
			return false;
		}
		
		$user_data = array();

		// Get first name from fields
		if ( $first_name = _af_resolve_field_picker_value( $form['editing']['user']['first_name'] ) ) {
			$user_data['first_name'] = $first_name;
		}
		
		// Get last name from fields
		if ( $last_name = _af_resolve_field_picker_value( $form['editing']['user']['last_name'] ) ) {
			$user_data['last_name'] = $last_name;
		}

		// Get email from fields
		$email_setting = $form['editing']['user']['email'];
		$email = _af_resolve_field_picker_value( $email_setting );
		
		if ( 'new' == $user_id ) {
			if ( $email ) {
				$user_data['user_email'] = $email;
			}

			// Get username from fields
			if ( 'same_as_email' == $form['editing']['user']['username']['field'] ) {
				$user_data['user_login'] = $email;
			} else if ( $username = _af_resolve_field_picker_value( $form['editing']['user']['username'] ) ) {
				$user_data['user_login'] = $username;
			}

			// Generate password or get from fields
			if ( 'generate' == $form['editing']['user']['password']['field'] ) {
				$user_data['user_pass'] = wp_generate_password();
			} else if ( $password = _af_resolve_field_picker_value( $form['editing']['user']['password'] ) ) {
				$user_data['user_pass'] = $password;
			}
		
			$user_data = wp_parse_args( $user_data, array(
				'role' => $form['editing']['user']['role'],
				'user_pass' => wp_generate_password(),
			));
		} else {
			// Only update email if mapped to field
			if ( $email && 'custom' !== $email_setting['field'] ) {
				$user_data['user_email'] = $email;
			}
			
			$user_data['ID'] = $user_id;
		}

		// Filter user data before insert/update
		$user_data = apply_filters( 'af/form/editing/user_data', $user_data, $form, $args );
		$user_data = apply_filters( 'af/form/editing/user_data/id=' . $form['post_id'], $user_data, $form, $args );
		$user_data = apply_filters( 'af/form/editing/user_data/key='. $form['key'], $user_data, $form, $args );
		
		if ( 'new' == $user_id ) {
			$updated_user_id = wp_insert_user( $user_data );
		} else {
			$updated_user_id = wp_update_user( $user_data );
		}
		
		if ( ! $updated_user_id || is_wp_error( $updated_user_id ) ) {
			return false;
		}

		$user = get_user_by( 'id', $updated_user_id );
		
		if ( ! $user || is_null( $user ) ) {
			return false;
		}

		// Transfer custom fields
		$map_all_exists = isset( $form['editing']['user']['map_all_fields'] );
		$map_all_fields = $map_all_exists ? $form['editing']['user']['map_all_fields'] == true : false;
		if ( $map_all_fields ) {
			// Don't save password field if one is set
			$excluded_fields = array();
			if ( isset( $form['editing']['user']['password']['field'] ) && acf_is_field_key( $form['editing']['user']['password']['field'] ) ) {
				$excluded_fields[] = $form['editing']['user']['password']['field'];
			}

			af_save_all_fields( 'user_' . $user->ID, $excluded_fields );
		} else if ( $form['editing']['user']['custom_fields'] ) {
			foreach ( $form['editing']['user']['custom_fields'] as $field_key ) {
				af_save_field( $field_key, 'user_' . $user->ID );
			}
		}

		if ( $form['editing']['user']['send_notification'] && 'new' == $user_id ) {
			wp_new_user_notification( $user->ID, null, 'both' );
		}

		// Save user ID to submission object
		AF()->submission['user'] = $user->ID;

		// Trigger action after post has been created/updated
		$action = ( 'new' == $user_id ) ? 'af/form/editing/user_created' : 'af/form/editing/user_updated';
		
		do_action( $action, $user, $form, $args );
		do_action( $action . '/id=' . $form['post_id'], $user, $form, $args );
		do_action( $action . '/key=' . $form['key'], $user, $form, $args );

		return true;
	}


	/**
	 * Validates that a user can be created from a form submission.
	 * Specifically it will check that email and username are not already in use.
	 *
	 * @since 1.7.0
	 *
	 */ 
	function validate_user( $form, $args ) {
		if ( ! $form['editing']['user'] ) {
			return;
		}

		if ( ! isset( $args['user'] ) || 'new' !== $args['user'] ) {
			return;
		}

		// Ensure email is not already in use.
		$email_setting = $form['editing']['user']['email'];
		if ( acf_is_field_key( $email_setting['field'] ) ) {
			$email = _af_resolve_field_picker_value( $email_setting );

			if ( email_exists( $email ) !== FALSE ) {
				af_add_error( $email_setting['field'], __( 'This email has already been used', 'advanced-forms' ) );
			}
		}

		// Ensure username is not already in use.
		$username_setting = $form['editing']['user']['username'];
		if ( acf_is_field_key( $username_setting['field'] ) ) {
			$username = _af_resolve_field_picker_value( $username_setting );
			
			if ( username_exists( $username ) !== FALSE ) {
				af_add_error( $username_setting['field'], __( 'This username has already been used', 'advanced-forms' ) );
			}
		}
	}
	
	
	/**
	 * Prefill form fields when editing a post or user
	 *
	 * @since 1.4.0
	 *
	 */
	function prefill_fields( $value, $field, $form, $args ) {
		
		// Check if form edits a post
		if ( $form['editing']['post'] && isset( $args['post'] ) && is_numeric( $args['post'] ) ) {
			$post = get_post( $args['post'] );

			if ( ! $post ) {
				return $value;
			}
			
			// Post title
			if ( $form['editing']['post']['post_title']['field'] == $field['key'] ) {
				return $post->post_title;
			}
			
			// Post content
			if ( $form['editing']['post']['post_content']['field'] == $field['key'] ) {
				return $post->post_content;
			}
			
			
			// Field is mapped to itself or all fields are mapped.
			$map_all_exists = isset( $form['editing']['post']['map_all_fields'] );
			$map_all_fields = $map_all_exists ? $form['editing']['post']['map_all_fields'] == true : false;
			
			$is_in_custom_fields = false;
			if ( isset( $form['editing']['post']['custom_fields'] ) && is_array( $form['editing']['post']['custom_fields'] ) ) {
				$is_in_custom_fields = in_array( $field['key'], $form['editing']['post']['custom_fields'] );
			}

			if ( $map_all_fields || $is_in_custom_fields ) {
				return acf_get_value( $args['post'], $field );
			}
		}

		// Check if form edits a user
		if ( $form['editing']['user'] && isset( $args['user'] ) && is_numeric( $args['user'] ) ) {
			$user = get_user_by( 'id', $args['user'] );

			if ( ! $user ) {
				return $value;
			}			

			// Email
			if ( $form['editing']['user']['email']['field'] == $field['key'] ) {
				return $user->user_email;
			}

			// First name
			if ( $form['editing']['user']['first_name']['field'] == $field['key'] ) {
				return $user->user_firstname;
			}

			// Last name
			if ( $form['editing']['user']['last_name']['field'] == $field['key'] ) {
				return $user->user_lastname;
			}
			
			
			// Field is mapped to itself or all fields are mapped.
			$map_all_exists = isset( $form['editing']['user']['map_all_fields'] );
			$map_all_fields = $map_all_exists ? $form['editing']['user']['map_all_fields'] == true : false;

			$is_in_custom_fields = false;
			if ( isset( $form['editing']['user']['custom_fields'] ) && is_array( $form['editing']['user']['custom_fields'] ) ) {
				$is_in_custom_fields = in_array( $field['key'], $form['editing']['user']['custom_fields'] );
			}

			if ( $map_all_fields || $is_in_custom_fields ) {
				return acf_get_value( 'user_' . $args['user'], $field );
			}
			
		}
		
		
		return $value;
		
	}


	/**
	 * Add post ID to ACF form data if editing a post.
	 *
	 * @since 1.7.1
	 *
	 */
	function add_post_id_to_acf( $acf_form_data, $form, $args ) {
		if ( $form['editing']['post'] && isset( $args['post'] ) && is_numeric( $args['post'] ) ) {
			$acf_form_data['post_id'] = $args['post'];
		}
		return $acf_form_data;
	}


	/**
	 * Add post ID to hidden field if editing a post. Used for ACF AJAX requests.
	 *
	 * @since 1.6.0
	 *
	 */
	function add_hidden_post_id_field( $form, $args ) {
		if ( $form['editing']['post'] && isset( $args['post'] ) && is_numeric( $args['post'] ) ) {
			echo sprintf( '<input type="hidden" name="post_id" value="%s">', $args['post'] );
		}
	}


	/**
	 * Add merge tags for post and user ID.
	 *
	 * @since 1.6.0
	 *
	 */
	function add_custom_merge_tags( $tags, $form ) {
		if ( isset( $form['editing']['post'] ) and $form['editing']['post'] ) {
			$tags[] = array(
				'value' => 'post_id',
				'label' => __( 'Post ID', 'advanced-forms' ),
			);

			$tags[] = array(
				'value' => 'post_url',
				'label' => __( 'Post URL', 'advanced-forms' ),
			);
		}

		return $tags;
	}


	/**
	 * Resolve custom merge tags for posts and users.
	 *
	 * @since 1.6.0
	 *
	 */
	function resolve_merge_tag( $output, $tag ) {
		if ( 'post_id' == $tag ) {
			if ( isset( AF()->submission['post'] ) ) {
				return AF()->submission['post'];
			}
		}

		if ( 'post_url' == $tag ) {
			if ( isset( AF()->submission['post'] ) ) {
				return get_permalink( AF()->submission['post'] );
			}	
		}

		return $output;
	}
	
	
	/**
	 * Add the editing fields to the default valid form
	 *
	 * @since 1.4.0
	 *
	 */
	function valid_form( $form ) {
		
		$form['editing'] = array(
			'user' => false,
			'post' => false,
			'term' => false,
		);
		
		return $form;
		
	}
	
	
	/**
	 * Add any editing settings to form object for forms loaded from posts
	 *
	 * @since 1.4.0
	 *
	 */
	function form_from_post( $form, $post ) {
		$form['editing'] = array(
			'user' => false,
			'post' => false,
			'term' => false,
		);
		
		if ( get_field( 'form_editing_posts_enabled', $post->ID ) ) {
			$form['editing']['post'] = array(
				'post_type' => get_field( 'form_editing_post_type', $post->ID ),
				'post_status' => get_field( 'form_editing_post_status', $post->ID ) ?: 'publish',
				'post_title' => get_field( 'form_editing_post_title', $post->ID ),
				'post_content' => get_field( 'form_editing_post_content', $post->ID ),
			);

			$map_all_fields = get_field( 'form_editing_posts_map_all_fields', $post->ID ) ?: false;
			$custom_fields = get_field( 'form_editing_posts_custom_fields', $post->ID ) ?: array();

			$form['editing']['post']['map_all_fields'] = $map_all_fields;
			if ( ! $map_all_fields ) {
				$form['editing']['post']['custom_fields'] = $custom_fields;
			}
		}
		
		if ( get_field( 'form_editing_users_enabled', $post->ID ) ) {
			$form['editing']['user'] = array(
				'role' => get_field( 'form_editing_user_role', $post->ID ),
				'email' => get_field( 'form_editing_user_email', $post->ID ),
				'username' => get_field( 'form_editing_user_username', $post->ID ),
				'first_name' => get_field( 'form_editing_user_first_name', $post->ID ),
				'last_name' => get_field( 'form_editing_user_last_name', $post->ID ),
				'password' => get_field( 'form_editing_user_password', $post->ID ),
				'send_notification' => get_field( 'form_editing_user_send_notification', $post->ID ),
			);

			$map_all_fields = get_field( 'form_editing_users_map_all_fields', $post->ID ) ?: false;
			$custom_fields = get_field( 'form_editing_users_custom_fields', $post->ID ) ?: array();

			$form['editing']['user']['map_all_fields'] = $map_all_fields;
			if ( ! $map_all_fields ) {
				$form['editing']['user']['custom_fields'] = $custom_fields;
			}
		}

		return $form;
	}

	function form_to_post( $form, $post ) {
		$editing_type = '';
		
		if ( $form['editing']['post'] ) {
			$editing_type = 'post';

			update_field( 'field_form_editing_post_type', $form['editing']['post']['post_type'], $post->ID );
			update_field( 'field_form_editing_post_title', $form['editing']['post']['post_title'], $post->ID );
			update_field( 'field_form_editing_post_content', $form['editing']['post']['post_content'], $post->ID );

			if ( isset( $form['editing']['post']['map_all_fields'] ) ) {
				update_field( 'field_form_editing_map_all_fields', $form['editing']['post']['map_all_fields'], $post->ID );
			}

			if ( isset( $form['editing']['post']['custom_fields'] ) ) {
				update_field( 'field_form_editing_custom_fields', $form['editing']['post']['custom_fields'], $post->ID );
			}
		}

		if ( $form['editing']['user'] ) {
			$editing_type = 'user';

			update_field( 'field_form_editing_user_role', $form['editing']['user']['role'], $post->ID );
			update_field( 'field_form_editing_user_email', $form['editing']['user']['email'], $post->ID );
			update_field( 'field_form_editing_user_username', $form['editing']['user']['username'], $post->ID );
			update_field( 'field_form_editing_user_first_name', $form['editing']['user']['first_name'], $post->ID );
			update_field( 'field_form_editing_user_last_name', $form['editing']['user']['last_name'], $post->ID );
			update_field( 'field_form_editing_user_password', $form['editing']['user']['password'], $post->ID );
			update_field( 'field_form_editing_user_send_notification', $form['editing']['user']['send_notification'], $post->ID );

			if ( isset( $form['editing']['user']['map_all_fields'] ) ) {
				update_field( 'field_form_editing_map_all_fields', $form['editing']['user']['map_all_fields'], $post->ID );
			}

			if ( isset( $form['editing']['user']['custom_fields'] ) ) {
				update_field( 'field_form_editing_custom_fields', $form['editing']['user']['custom_fields'], $post->ID );
			}
		}

		update_field( 'field_form_editing_type', $editing_type, $post->ID );
	}
}

return new AF_Pro_Core_Editing();