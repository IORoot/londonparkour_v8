<?php

if ( ! class_exists( 'AF_Calculated_Admin_Field' ) ) :

	/**
	 * Custom private ACF field used to define the contents of a forms calculated fields.
	 *
	 * @since 1.6.0
	 */
	class AF_Calculated_Admin_Field extends acf_field {

		function __construct() {
			$this->name = 'af_calculated_admin';
			$this->label = _x( 'Calculated admin', 'noun', 'advanced-forms' );
			$this->public = false;
			$this->defaults = array(
				'allow_null' => 0,
				'allow_custom' => 0,
				'field_types' => 'regular',
				'choices' => array(),
				'default_value' => array(),
				'placeholder' => '',
			);

			parent::__construct();
		}

		function render_field( $field ) {
			// Get the calculated fields from the form settings.
			$calculated_fields = $this->get_calculated_fields();
			if ( empty( $calculated_fields ) ) {
				?>
				<div class="notice notice-warning inline notice-alt">
					<p>
						<?php _e( 'There are no calculated fields assigned to this form. Add calculated fields to the ACF field group/s then return here to configure the contents of each calculated field.', 'advanced-forms' ); ?>
						<a href="https://advancedforms.github.io/guides/using-calculated-fields/"
						   target="_blank"><?php _e( 'Learn more about calculated fields', 'advanced-forms' ) ?></a>
					</p>
				</div>
				<?php
				return;
			}

			// Sort fields in order of previously saved value.
			$sorted_fields = $this->sort_calculated_fields( $calculated_fields, $field );

			// Set the value for our custom use repeater. This is passed to the repeater field as it is rendered below.
			// Note that weare uing our `af_render_content` field type to render the contents of each repeater row.
			// Hence, the render_function arg to render content as desired.
			$repeater_value = [];
			foreach ( $sorted_fields as $index => $calculated_field ) {
				$row_num = $index + 1;
				$parent_key = $field['key'];
				$calculated_field['value'] = isset( $field['value'][ $calculated_field['key'] ] ) ? $field['value'][ $calculated_field['key'] ] : false;
				$repeater_value["row-$row_num"] = [
					'field_form_calculated_fields_calc_field_item' => function ( $field ) use ( $calculated_field, $parent_key ) {
						$instructions = '';
						if ( $this->calculated_field_has_hooked_handler( $calculated_field ) ) {
							ob_start();
							?>
							<div class="notice notice-warning inline notice-alt">
								<p>
									<strong><?php _e( 'Note:', 'advanced-forms' ); ?></strong>
									<?php _e( 'This calculated field is being handled by a hooked PHP function which may override this calculation entirely.', 'advanced-forms' ); ?>
								</p>
							</div>
							<?php
							$instructions = ob_get_clean();
						}
						acf_render_field_wrap( [
							'key' => $calculated_field['key'],
							'label' => $calculated_field['label'],
							'name' => $calculated_field['name'],
							'type' => 'wysiwyg',
							'instructions' => $instructions,
							'default_value' => '',
							'tabs' => 'all',
							'toolbar' => 'full',
							'media_upload' => 1,
							'delay' => 0,
							'prefix' => sprintf( 'acf[%s]', $parent_key ),
							'value' => $calculated_field['value'],
						] );
					},
				];
			}

			// If generic (catch all) variant of filter is used, warn the user.
			if ( has_action( 'af/field/calculate_value' ) ) {
				?>
				<div class="notice notice-warning inline notice-alt">
					<p>
						<strong><?php _e( 'Note:', 'advanced-forms' ); ?></strong>
						<?php _e( 'There is a PHP function hooked directly to the <code>af/field/calculate_value</code> hook. This hook is global and executes for all calculated fields which means the field calculations here may be overridden entirely.', 'advanced-forms' ); ?>
					</p>
				</div>
				<?php
			}

			// Render a repeater that uses our special field type for rendering a row.
			acf_render_field_wrap( [
				'key' => $field['key'],
				'label' => '',
				'name' => $field['name'],
				// Using the af[ignore] array in the POST data as we don't need to store this array value. See the
				// repeater row prep above where we've used acf[$key] for sub field data.
				'prefix' => sprintf( 'af[ignore][%s]', $field['key'] ),
				'type' => 'repeater',
				'layout' => 'table',
				'pagination' => 0,
				// Set min & max so rows can't be added or removed.
				'min' => $n_rows = count( $calculated_fields ),
				'max' => $n_rows,
				'sub_fields' => [
					// Note, the render function is set up on the repeater value.
					acf_validate_field( [
						'key' => 'field_form_calculated_fields_calc_field_item',
						'name' => 'item',
						'label' => '',
						'type' => 'af_render_content',
					] ),
				],
				'value' => $repeater_value,
				'wrapper' => [ 'class' => 'af-field-repeater-hide-thead' ],
			] );
		}

		/**
		 * Retrieve all calculated fields for the current form.
		 *
		 * @since 1.6.0
		 */
		private function get_calculated_fields() {
			global $post;

			if ( $post && $form_key = get_post_meta( $post->ID, 'form_key', true ) ) {
				$fields = af_get_form_fields( $form_key );
				return $this->find_calculated_fields( $fields );
			}

			return array();
		}

		/**
		 * Sort the calculated fields in order of previously saved data. This gets the saved data, extracts the keys,
		 * then loops through the calculated fields and sorts them in the order of the saved keys. If a key isn't in the
		 * saved data, the field is added to the end of the array.
		 *
		 * @param array $fields
		 * @param array $field
		 *
		 * @return array|int[]|string[]
		 */
		private function sort_calculated_fields( array $fields, array $field ) {
			global $post;

			$stored = get_field( $field['key'], $post->ID );
			if ( empty( $stored ) ) {
				return $fields;
			}

			$keys = array_flip( array_keys( $stored ) );

			foreach ( $fields as $field ) {
				if ( isset( $keys[ $field['key'] ] ) ) {
					$keys[ $field['key'] ] = $field;
				} else {
					$keys[] = $field;
				}
			}

			return array_values( $keys );
		}

		/**
		 * Recursively find all calculated fields in a list of fields.
		 * Will recurse into all group fields.
		 *
		 * @since 1.6.7
		 */
		private function find_calculated_fields( $fields, $parents = array() ) {
			$calculated_fields = array();

			foreach ( $fields as $field ) {
				if ( 'calculated' == $field['type'] ) {
					// Add group hierarchy to label
					$names = array_merge( $parents, array( $field['label'] ) );
					$field['label'] = join( ' &rarr; ', $names );

					$calculated_fields[] = $field;
				}

				// Recursively search group subfields
				if ( 'group' == $field['type'] ) {
					$new_parents = array_merge( $parents, array( $field['label'] ) );
					$sub_calculated_fields = $this->find_calculated_fields(
						$field['sub_fields'],
						$new_parents
					);

					$calculated_fields = array_merge( $calculated_fields, $sub_calculated_fields );
				}
			}

			return $calculated_fields;
		}

		/**
		 * Format the individual values for the different fields.
		 * Based on the formatting for WYSIWYG fields.
		 *
		 * @since 1.6.0
		 */
		function format_value( $value, $post_id, $field ) {
			if ( empty( $value ) ) {
				return $value;
			}

			foreach ( $value as $i => $calculated_contents ) {
				$formatted_value = apply_filters( 'acf_the_content', $calculated_contents );
				$formatted_value = str_replace( ']]>', ']]&gt;', $formatted_value );
				$value[ $i ] = $formatted_value;
			}

			return $value;
		}

		/**
		 * Check to see if the calculated field is being calculated using a hooked PHP function.
		 *
		 * @param array $calculated_field
		 *
		 * @return bool
		 */
		private function calculated_field_has_hooked_handler( $calculated_field ) {
			return has_action( 'af/field/calculate_value/key=' . $calculated_field['key'] )
			       or has_action( 'af/field/calculate_value/name=' . $calculated_field['name'] );
		}

	}

	acf_register_field_type( new AF_Calculated_Admin_Field() );

endif; // class_exists check