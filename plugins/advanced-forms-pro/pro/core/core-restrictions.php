<?php

class AF_Pro_Core_Restrictions {
  function __construct() {
		add_filter( 'af/form/restriction', array( $this, 'restrict_edit_own_posts' ), 10, 3 );

		add_filter( 'af/form/valid_form', array( $this, 'valid_form' ), 10, 1 );
		add_filter( 'af/form/from_post', array( $this, 'form_from_post' ), 10, 2 );
		add_action( 'af/form/to_post', array( $this, 'form_to_post' ), 10, 2 );
  }

  /**
   * Check if form should be restricted to only allow editing by the post author.
   * 
   * @since 1.8.0
   * 
   */
  function restrict_edit_own_posts( $restriction, $form, $args ) {
		if ( $restriction ) {
			return $restriction;
		}

    if ( ! $form['restrictions']['edit_own_posts'] ) {
      return false;
    }

    if ( ! isset( $args['post'] ) || ! is_numeric( $args['post'] ) ) {
      return false;
    }

    $message = $form['restrictions']['edit_own_posts']['message'];

    // Ensure user is logged in
    $user_id = get_current_user_id();
    if ( 0 === $user_id ) {
      return $message;
    }

    // Ensure user is the author of the post being edited
    $post = get_post( $args['post'] );
    if ( $post->post_author != $user_id ) {
      return $message;
    }

    return false;
  }

	function valid_form( $form ) {
    if ( ! isset( $form['restrictions'] ) ) {
      $form['restrictions'] = array();
    }

    $form['restrictions']['edit_own_posts'] = false;
		
		return $form;
	}
	
	function form_from_post( $form, $post ) {
		$edit_own_posts = get_field( 'field_form_restriction_edit_own_posts', $post->ID );
	
		if ( $edit_own_posts ) {
			$form['restrictions']['edit_own_posts'] = array(
				'message' 		=> get_field( 'field_form_restriction_edit_own_posts_message', $post->ID ),
			);
		}

		return $form;
	}

	function form_to_post( $form, $post ) {
		if ( $edit_own_posts = $form['restrictions']['edit_own_posts'] ) {
			update_field( 'field_form_restriction_edit_own_posts', true, $post->ID );
			update_field( 'field_form_restriction_edit_own_posts_message', $entries['message'], $post->ID );
		}
	}
}

new AF_Pro_Core_Restrictions();