<?php
/**
 * Dynamic extra booking-form fields backed by ACF field groups.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Extra_Fields {

	public static function init(): void {
		// ACF may not be loaded yet when this plugin initializes.
		// Register once ACF boots so location rules reliably exist.
		add_action( 'acf/init', [ self::class, 'register_acf_hooks' ] );
		if ( function_exists( 'acf_get_field_groups' ) ) {
			self::register_acf_hooks();
		}
	}

	public static function register_acf_hooks(): void {
		add_filter( 'acf/location/rule_types', [ self::class, 'register_location_rule_type' ] );
		add_filter( 'acf/location/rule_values/clasbpro_form_class_id', [ self::class, 'location_rule_values' ], 10, 2 );
		add_filter( 'acf/location/rule_values/type=clasbpro_form_class_id', [ self::class, 'location_rule_values' ], 10, 2 );
		add_filter( 'acf/location/rule_match/clasbpro_form_class_id', [ self::class, 'location_rule_match' ], 10, 4 );
		add_filter( 'acf/location/rule_match/type=clasbpro_form_class_id', [ self::class, 'location_rule_match' ], 10, 4 );
		add_filter( 'acf/location/match_rule/type=clasbpro_form_class_id', [ self::class, 'location_rule_match' ], 10, 4 );
	}

	public static function register_location_rule_type( array $types ): array {
		$label = __( 'Booking form class ID', 'class-bookings-with-stripe-pro' );
		$types['Class Bookings with Stripe']['clasbpro_form_class_id'] = $label;
		// Legacy ACF location group label (existing field groups may reference this).
		$types['Stripe Class Bookings']['clasbpro_form_class_id'] = $label;
		return $types;
	}

	public static function location_rule_values( array $values, array $rule ): array {
		unset( $rule );
		$posts = get_posts(
			[
				'post_type'      => CPT::CLASS_PT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
		$values = [];
		foreach ( $posts as $post ) {
			$values[ (string) $post->ID ] = sprintf( '#%d - %s', (int) $post->ID, $post->post_title );
		}
		return $values;
	}

	public static function location_rule_match( bool $match, array $rule, array $screen, array $field_group ): bool {
		unset( $match, $field_group );
		$class_id = (int) ( $screen['clasbpro_form_class_id'] ?? 0 );
		$rule_val = (int) ( $rule['value'] ?? 0 );
		$result   = ( $class_id > 0 && $rule_val > 0 && $class_id === $rule_val );
		if ( '!=' === ( $rule['operator'] ?? '==' ) ) {
			return ! $result;
		}
		return $result;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_fields_for_class( int $class_id ): array {
		if ( $class_id <= 0 || ! function_exists( 'acf_get_field_groups' ) ) {
			return [];
		}

		$groups = acf_get_field_groups(
			[
				'clasbpro_form_class_id' => $class_id,
			]
		);
		if ( empty( $groups ) ) {
			return [];
		}

		$fields = [];
		foreach ( $groups as $group ) {
			$group_fields = acf_get_fields( $group ) ?: [];
			foreach ( $group_fields as $field ) {
				$type = (string) ( $field['type'] ?? '' );
				$key  = (string) ( $field['key'] ?? '' );
				if ( '' === $key || in_array( $type, [ 'tab', 'message', 'accordion', 'clone', 'group', 'repeater', 'flexible_content' ], true ) ) {
					continue;
				}
				$fields[] = $field;
			}
		}
		return $fields;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 */
	public static function render_fields( array $fields ): void {
		foreach ( $fields as $field ) {
			$key      = (string) ( $field['key'] ?? '' );
			$name     = 'extra_fields[' . $key . ']';
			$id       = 'cbfs-extra-' . sanitize_html_class( $key );
			$type     = (string) ( $field['type'] ?? 'text' );
			$label    = (string) ( $field['label'] ?? '' );
			$required = ! empty( $field['required'] );
			$star     = $required ? ' *' : '';

			echo '<div class="cbfs-form__row">';
			echo '<label class="cbfs-form__label" for="' . esc_attr( $id ) . '">' . esc_html( $label . $star ) . '</label>';
			self::render_input_for_field( $field, $id, $name, $required );
			echo '</div>';
		}
	}

	/**
	 * @param array<string, mixed> $field
	 */
	private static function render_input_for_field( array $field, string $id, string $name, bool $required ): void {
		$type = (string) ( $field['type'] ?? 'text' );
		$placeholder = isset( $field['placeholder'] ) ? (string) $field['placeholder'] : '';

		if ( 'textarea' === $type ) {
			echo '<textarea class="cbfs-form__input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"';
			self::render_required_attrs( $required );
			echo ' placeholder="' . esc_attr( $placeholder ) . '"></textarea>';
			return;
		}

		if ( in_array( $type, [ 'select', 'radio' ], true ) ) {
			$choices = (array) ( $field['choices'] ?? [] );
			echo '<select class="cbfs-form__input cbfs-form__select" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"';
			self::render_required_attrs( $required );
			echo '>';
			if ( empty( $field['required'] ) ) {
				echo '<option value="">' . esc_html__( 'Select an option', 'class-bookings-with-stripe-pro' ) . '</option>';
			}
			foreach ( $choices as $value => $choice_label ) {
				echo '<option value="' . esc_attr( (string) $value ) . '">' . esc_html( (string) $choice_label ) . '</option>';
			}
			echo '</select>';
			return;
		}

		if ( 'true_false' === $type ) {
			echo '<label class="cbfs-form__check">';
			echo '<input class="cbfs-form__check-input" id="' . esc_attr( $id ) . '" type="checkbox" name="' . esc_attr( $name ) . '" value="1"';
			self::render_required_attrs( $required );
			echo '>';
			echo '<span class="cbfs-form__check-label">' . esc_html( (string) ( $field['message'] ?? '' ) ) . '</span>';
			echo '</label>';
			return;
		}

		$html_type = 'text';
		if ( 'email' === $type ) {
			$html_type = 'email';
		} elseif ( 'number' === $type ) {
			$html_type = 'number';
		} elseif ( 'url' === $type ) {
			$html_type = 'url';
		}
		echo '<input class="cbfs-form__input" id="' . esc_attr( $id ) . '" type="' . esc_attr( $html_type ) . '" name="' . esc_attr( $name ) . '"';
		self::render_required_attrs( $required );
		echo ' placeholder="' . esc_attr( $placeholder ) . '">';
	}

	private static function render_required_attrs( bool $required ): void {
		if ( ! $required ) {
			return;
		}
		echo ' required="required" data-cbfs-required="1"';
	}

	/**
	 * @param array<string, mixed> $submitted
	 * @return array<string, string|int>|\WP_Error
	 */
	public static function validate_submission( int $class_id, array $submitted ) {
		$fields = self::get_fields_for_class( $class_id );
		if ( empty( $fields ) ) {
			return [];
		}

		$clean = [];
		foreach ( $fields as $field ) {
			$key   = (string) ( $field['key'] ?? '' );
			$type  = (string) ( $field['type'] ?? 'text' );
			$label = (string) ( $field['label'] ?? __( 'Field', 'class-bookings-with-stripe-pro' ) );
			$val   = $submitted[ $key ] ?? '';

			if ( 'true_false' === $type ) {
				$val = rest_sanitize_boolean( $val ) ? 1 : 0;
			} else {
				$val = is_scalar( $val ) ? (string) $val : '';
				$val = trim( $val );
			}

			if ( ! empty( $field['required'] ) ) {
				$missing = ( 'true_false' === $type ) ? ( 1 !== (int) $val ) : ( '' === (string) $val );
				if ( $missing ) {
					return new \WP_Error(
						'clasbpro_required',
						sprintf(
							/* translators: %s: field label */
							__( 'Please complete: %s.', 'class-bookings-with-stripe-pro' ),
							$label
						),
						[ 'field' => 'extra_fields[' . $key . ']' ]
					);
				}
			}

			if ( 'email' === $type && '' !== (string) $val ) {
				$val = sanitize_email( (string) $val );
				if ( ! is_email( (string) $val ) ) {
					return new \WP_Error(
						'clasbpro_validation',
						sprintf(
							/* translators: %s: extra field label */
							__( '%s must be a valid email.', 'class-bookings-with-stripe-pro' ),
							$label
						),
						[ 'field' => 'extra_fields[' . $key . ']' ]
					);
				}
			} elseif ( 'number' === $type && '' !== (string) $val ) {
				$val = is_numeric( $val ) ? (string) $val : '';
				if ( '' === $val ) {
					return new \WP_Error(
						'clasbpro_validation',
						sprintf(
							/* translators: %s: extra field label */
							__( '%s must be a number.', 'class-bookings-with-stripe-pro' ),
							$label
						),
						[ 'field' => 'extra_fields[' . $key . ']' ]
					);
				}
			} elseif ( 'url' === $type && '' !== (string) $val ) {
				$val = esc_url_raw( (string) $val );
			} elseif ( 'textarea' === $type ) {
				$val = sanitize_textarea_field( (string) $val );
			} else {
				$val = sanitize_text_field( (string) $val );
			}

			$clean[ $key ] = $val;
		}

		return $clean;
	}

	/**
	 * Build merge tags for submitted extra fields.
	 *
	 * @return array<string, string>
	 */
	public static function build_merge_tags( int $class_id, string $extra_fields_json ): array {
		$rows = self::display_rows( $class_id, $extra_fields_json );
		$tags = [];
		foreach ( $rows as $row ) {
			$key = (string) $row['key'];
			$val = (string) $row['value'];
			$tags[ '{' . $key . '}' ] = $val;
			$tags[ '{acf:' . $key . '}' ] = $val;
			if ( ! empty( $row['name'] ) ) {
				$tags[ '{' . (string) $row['name'] . '}' ] = $val;
			}
		}
		$lines = array_map(
			static fn( array $row ): string => (string) $row['label'] . ': ' . (string) $row['value'],
			$rows
		);
		$tags['{extra_fields}'] = implode( "\n", $lines );
		return $tags;
	}

	/**
	 * @return array<int, array{key:string,name:string,label:string,value:string}>
	 */
	public static function display_rows( int $class_id, string $extra_fields_json ): array {
		$values = json_decode( $extra_fields_json, true );
		if ( ! is_array( $values ) || empty( $values ) ) {
			return [];
		}
		$defs = self::field_definitions_by_key( $class_id );
		$rows = [];
		foreach ( $values as $key => $raw ) {
			$field = $defs[ (string) $key ] ?? null;
			if ( ! is_array( $field ) ) {
				continue;
			}
			$label = (string) ( $field['label'] ?? $key );
			$name  = (string) ( $field['name'] ?? '' );
			$type  = (string) ( $field['type'] ?? 'text' );
			$value = self::format_value_for_output( $raw, $type, $field );
			if ( '' === $value ) {
				continue;
			}
			$rows[] = [
				'key'   => (string) $key,
				'name'  => $name,
				'label' => $label,
				'value' => $value,
			];
		}
		return $rows;
	}

	/**
	 * @return array<string, array<string,mixed>>
	 */
	private static function field_definitions_by_key( int $class_id ): array {
		$map = [];
		foreach ( self::get_fields_for_class( $class_id ) as $field ) {
			$key = (string) ( $field['key'] ?? '' );
			if ( '' !== $key ) {
				$map[ $key ] = $field;
			}
		}
		return $map;
	}

	/**
	 * @param mixed $raw
	 * @param array<string,mixed> $field
	 */
	private static function format_value_for_output( $raw, string $type, array $field ): string {
		if ( 'true_false' === $type ) {
			return rest_sanitize_boolean( $raw ) ? __( 'Yes', 'class-bookings-with-stripe-pro' ) : __( 'No', 'class-bookings-with-stripe-pro' );
		}
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( '' === $value ) {
			return '';
		}
		if ( in_array( $type, [ 'select', 'radio' ], true ) ) {
			$choices = (array) ( $field['choices'] ?? [] );
			if ( isset( $choices[ $value ] ) ) {
				return (string) $choices[ $value ];
			}
		}
		return $value;
	}
}
