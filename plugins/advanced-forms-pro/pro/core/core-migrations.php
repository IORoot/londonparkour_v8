<?php

class AF_Pro_Core_Migrations {
	function __construct() {
		add_filter( 'af/migrations', array( $this, 'migrations' ), 10, 1 );
	}

	function migrations( $migrations ) {
		$migrations[] = array(
			'version' => '1.9.0',
			'name' => 'Replace editing radio buttons with toggles',
			'apply' => function() {
				foreach ( af_get_forms() as $form ) {
					$post_id = $form['post_id'];
					if ( !$post_id ) {
						continue;
					}

					$old_setting = get_post_meta( $post_id, 'form_editing_type', true );

					if ( 'post' === $old_setting ) {
						// Set new post editing toggle
						update_field( 'form_editing_posts_enabled', true, $post_id );
					} else if ( 'user' === $old_setting ) {
						// Set new user editing toggle
						update_field( 'field_form_editing_users_enabled', true, $post_id );
					}

					// Transfer setting from old field mapping fields to new
					if ( metadata_exists( 'post', $post_id, 'form_editing_map_all_fields' ) ) {
						$map_all_fields_old = get_post_meta( $post_id, 'form_editing_map_all_fields', true );
						update_field( 'field_form_editing_posts_map_all_fields', $map_all_fields_old, $post_id );
						update_field( 'field_form_editing_users_map_all_fields', $map_all_fields_old, $post_id );
					}

					if ( metadata_exists( 'post', $post_id, 'form_editing_custom_fields' ) ) {
						$custom_fields_old = get_post_meta( $post_id, 'form_editing_custom_fields', true );
						update_field( 'field_form_editing_posts_custom_fields', $custom_fields_old, $post_id );
						update_field( 'field_form_editing_users_custom_fields', $custom_fields_old, $post_id );
					}
				}
			},
		);

		return $migrations;
	}
}

new AF_Pro_Core_Migrations();