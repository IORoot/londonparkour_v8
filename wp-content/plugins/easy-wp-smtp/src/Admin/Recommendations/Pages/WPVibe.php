<?php

namespace EasyWPSMTP\Admin\Recommendations\Pages;

use EasyWPSMTP\Admin\Recommendations\PageAbstract;

/**
 * WPVibe recommended-plugin landing page.
 *
 * @since 2.15.0
 */
class WPVibe extends PageAbstract {

	/**
	 * Admin menu page slug.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	public const SLUG = 'easy-wp-smtp-recommended-wpvibe';

	/**
	 * Configuration.
	 *
	 * @since 2.15.0
	 *
	 * @var array
	 */
	protected $config = [
		'lite_plugin'       => 'vibe-ai/vibe-ai.php',
		'lite_wporg_url'    => 'https://wordpress.org/plugins/vibe-ai/',
		'lite_download_url' => 'https://downloads.wordpress.org/plugin/vibe-ai.zip',
		'pro_plugin'        => '',
		'wpvibe_addon'      => '',
		'wpvibe_addon_page' => 'https://wpvibe.ai/docs',
		'wpvibe_onboarding' => 'admin.php?page=vibe-ai',
	];

	/**
	 * Get the plugin name for use in IDs, CSS classes, and config keys.
	 *
	 * @since 2.15.0
	 *
	 * @return string Plugin name.
	 */
	protected static function get_plugin_name(): string {

		return 'wpvibe'; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading title text.
	 *
	 * @since 2.15.0
	 *
	 * @return string Heading title.
	 */
	protected function get_heading_title(): string {

		return esc_html__( 'Your AI Just Learned WordPress', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading alt text for logo.
	 *
	 * @since 2.15.0
	 *
	 * @return string Heading alt text.
	 */
	protected function get_heading_alt_text(): string {

		return esc_attr__( 'Easy WP SMTP ♥ WPVibe', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get heading description strings.
	 *
	 * @since 2.15.0
	 *
	 * @return array Array of description strings.
	 */
	protected function get_heading_strings(): array {

		return [ // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
			esc_html__( 'WPVibe connects your AI assistant straight to your WordPress site, so you can create posts, manage plugins, and run tasks just by asking. Built-in guardrails keep your site safe.', 'easy-wp-smtp' ),
			esc_html__( 'Manage WordPress through conversation, not clicking.', 'easy-wp-smtp' ),
		];
	}

	/**
	 * Get screenshot features list.
	 *
	 * @since 2.15.0
	 *
	 * @return array Array of feature strings.
	 */
	protected function get_screenshot_features(): array {

		return [ // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
			esc_html__( 'Works with Claude, ChatGPT, Cursor, and any MCP client.', 'easy-wp-smtp' ),
			esc_html__( 'Create and edit posts, pages, media, and users through conversation.', 'easy-wp-smtp' ),
			esc_html__( 'Run 40+ WP-CLI commands without leaving your AI chat.', 'easy-wp-smtp' ),
			esc_html__( 'One-click setup in about a minute.', 'easy-wp-smtp' ),
		];
	}

	/**
	 * Get screenshot alt text.
	 *
	 * @since 2.15.0
	 *
	 * @return string Alt text for screenshot image.
	 */
	protected function get_screenshot_alt_text(): string {

		return esc_attr__( 'WPVibe screenshot', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Generate and output step 'Result' section HTML.
	 *
	 * @since 2.15.0
	 *
	 * @noinspection HtmlUnknownTarget
	 */
	protected function output_section_step_result(): void {

		$step = $this->get_data_step_result();

		if ( empty( $step ) ) {
			return;
		}

		printf(
			'<section class="step step-result %1$s">
				<aside class="num">
					<img src="%2$s" alt="%3$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%4$s</h2>
					<p>%5$s</p>
					<button class="button %6$s" data-url="%7$s">%8$s</button>
				</div>
			</section>',
			esc_attr( $step['section_class'] ),
			esc_url( easy_wp_smtp()->assets_url . '/images/recommendations/' . $step['icon'] ),
			esc_attr__( 'Step 3', 'easy-wp-smtp' ),
			esc_html__( 'See What Your AI Can Do', 'easy-wp-smtp' ),
			esc_html__( 'WPVibe is free to use. Explore the docs to see every ability your AI assistant now has for your WordPress site.', 'easy-wp-smtp' ),
			esc_attr( $step['button_class'] ),
			esc_url( $step['button_url'] ),
			esc_html( $step['button_text'] )
		);
	}

	/**
	 * Step 'Result' data.
	 *
	 * @since 2.15.0
	 *
	 * @return array Step data.
	 */
	protected function get_data_step_result(): array {

		$step = [];

		$step['icon']          = 'plugin-page/step-3.svg';
		$step['section_class'] = $this->output_data['plugin_setup'] ? '' : 'grey';
		$step['button_text']   = esc_html__( 'View Docs', 'easy-wp-smtp' );
		$step['button_class']  = $this->output_data['plugin_setup'] ? 'button-primary' : 'grey disabled';
		$step['button_url']    = $this->config['wpvibe_addon_page'];

		return $step;
	}

	/**
	 * Whether the plugin is finished setup or not.
	 *
	 * @since 2.15.0
	 *
	 * @return bool True if the plugin is finished setup.
	 */
	protected function is_plugin_finished_setup(): bool {

		return $this->is_plugin_configured(); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether a plugin is configured or not.
	 *
	 * @since 2.15.0
	 *
	 * @return bool True if plugin is configured properly.
	 */
	protected function is_plugin_configured(): bool {

		if ( ! $this->is_plugin_activated() ) {
			return false;
		}

		return (int) get_option( 'wpvibe_last_active', 0 ) > 0;
	}

	/**
	 * Whether a plugin is active or not.
	 *
	 * @since 2.15.0
	 *
	 * @return bool True if plugin is active.
	 */
	protected function is_plugin_activated(): bool {

		return is_plugin_active( $this->config['lite_plugin'] ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether a plugin is available (class/function exists).
	 *
	 * @since 2.15.0
	 *
	 * @return bool True if plugin is available.
	 */
	protected function is_plugin_available(): bool {

		return defined( 'WPVIBE_VERSION' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Whether pro version is active.
	 *
	 * @since 2.15.0
	 *
	 * @return bool True if pro version is active.
	 */
	protected function is_pro_active(): bool {

		return false; // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the install step.
	 *
	 * @since 2.15.0
	 *
	 * @return string Install step heading.
	 */
	protected function get_install_heading(): string {

		return esc_html__( 'Install & Activate WPVibe', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the install step.
	 *
	 * @since 2.15.0
	 *
	 * @return string Install step description.
	 */
	protected function get_install_description(): string {

		return esc_html__( 'Install WPVibe from the WordPress.org plugin repository.', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the plugin title.
	 *
	 * @since 2.15.0
	 *
	 * @return string Plugin title.
	 */
	protected function get_plugin_title(): string {

		return esc_html__( 'WPVibe', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the install button text.
	 *
	 * @since 2.15.0
	 *
	 * @return string Install button text.
	 */
	protected function get_install_button_text(): string {

		return esc_html__( 'Install WPVibe', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a plugin is installed and activated.
	 *
	 * @since 2.15.0
	 *
	 * @return string Installed & activated text.
	 */
	protected function get_installed_activated_text(): string {

		return esc_html__( 'WPVibe Installed & Activated', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the activate button text.
	 *
	 * @since 2.15.0
	 *
	 * @return string Activate button text.
	 */
	protected function get_activate_text(): string {

		return esc_html__( 'Activate WPVibe', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the heading for the setup step.
	 *
	 * @since 2.15.0
	 *
	 * @return string Setup step heading.
	 */
	protected function get_setup_heading(): string {

		return esc_html__( 'Connect Your AI Assistant', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the description for the setup step.
	 *
	 * @since 2.15.0
	 *
	 * @return string Setup step description.
	 */
	protected function get_setup_description(): string {

		return esc_html__( 'Open WPVibe and authorize your AI client in one click.', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the setup button text.
	 *
	 * @since 2.15.0
	 *
	 * @return string Setup button text.
	 */
	protected function get_setup_button_text(): string {

		return esc_html__( 'Open WPVibe', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when setup is completed.
	 *
	 * @since 2.15.0
	 *
	 * @return string Setup completed text.
	 */
	protected function get_setup_completed_text(): string {

		return esc_html__( 'Setup Complete', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}

	/**
	 * Get the text when a pro-version is installed and activated.
	 *
	 * @since 2.15.0
	 *
	 * @return string Pro installed and activated text.
	 */
	protected function get_pro_installed_activated_text(): string {

		return esc_html__( 'WPVibe Installed & Activated', 'easy-wp-smtp' ); // phpcs:ignore WPForms.Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
	}
}
