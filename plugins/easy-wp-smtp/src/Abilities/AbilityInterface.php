<?php

namespace EasyWPSMTP\Abilities;

use WP_Error; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement

/**
 * Contract every Easy WP SMTP ability implements.
 *
 * Concrete abilities are instantiated by {@see AbilityRegistrar} and registered
 * against the WordPress Abilities API on the `wp_abilities_api_init` hook.
 *
 * @since 2.15.0
 */
interface AbilityInterface {

	/**
	 * Get the ability slug, without the namespace prefix.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Get the human-readable label.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Get the human-readable description.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public function get_description();

	/**
	 * Get the JSON Schema describing accepted input.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	public function get_input_schema();

	/**
	 * Get the JSON Schema describing the response.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	public function get_output_schema();

	/**
	 * Execute the ability with validated input.
	 *
	 * @since 2.15.0
	 *
	 * @param mixed $input Input data validated against the input schema.
	 *
	 * @return array|WP_Error
	 */
	public function execute( $input );

	/**
	 * Permission gate.
	 *
	 * @since 2.15.0
	 *
	 * @return true|WP_Error True when allowed, WP_Error otherwise.
	 */
	public function check_permission();

	/**
	 * Annotation flags surfaced to MCP / abilities consumers.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	public function get_annotations();

	/**
	 * Whether the ability is exposed via the REST API.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	public function show_in_rest();

	/**
	 * Whether the ability is publicly listed for MCP clients.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	public function is_mcp_public();
}
