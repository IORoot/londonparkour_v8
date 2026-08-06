<?php

namespace EasyWPSMTP\Admin;

use EasyWPSMTP\WP;

/**
 * ActiveLayer education section on the WooCommerce Accounts & Privacy settings tab.
 *
 * Renders a WooCommerce-native card after the account settings block that helps
 * store owners install, activate, and connect the free ActiveLayer anti-spam
 * plugin. Install and activation reuse the shared plugin installer exposed by
 * the easy_wp_smtp_ajax dispatcher (Pages\AboutTab), so no install plumbing is
 * duplicated here.
 *
 * @since 2.15.0
 */
class WooCommerceActiveLayerEducation {

	/**
	 * ActiveLayer plugin basename (folder/file), used for install and activation checks.
	 *
	 * @since 2.15.0
	 */
	const PLUGIN_BASENAME = 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php';

	/**
	 * ActiveLayer WordPress.org download URL. Whitelisted in Pages\AboutTab::get_am_plugins().
	 *
	 * @since 2.15.0
	 */
	const DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/activelayer-anti-spam-spam-protection-for-forms-comments.zip';

	/**
	 * ActiveLayer WordPress.org plugin page (manual fallback).
	 *
	 * @since 2.15.0
	 */
	const WPORG_URL = 'https://wordpress.org/plugins/activelayer-anti-spam-spam-protection-for-forms-comments/';

	/**
	 * ActiveLayer settings page slug, the connect-account CTA target.
	 *
	 * @since 2.15.0
	 */
	const SETTINGS_PAGE = 'activelayer-settings';

	/**
	 * ActiveLayer integrations page slug, the connected-state link target.
	 *
	 * @since 2.15.0
	 */
	const INTEGRATIONS_PAGE = 'activelayer-integrations';

	/**
	 * User meta key persisting the per-user dismissal.
	 *
	 * @since 2.15.0
	 */
	const DISMISS_META = 'easy_wp_smtp_activelayer_wc_education_dismissed';

	/**
	 * Register hooks.
	 *
	 * @since 2.15.0
	 */
	public function hooks() {

		add_action( 'woocommerce_settings_account_registration_options_after', [ $this, 'output_section' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_easy_wp_smtp_activelayer_wc_dismiss', [ $this, 'ajax_dismiss' ] );
	}

	/**
	 * Whether the ActiveLayer plugin is active.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function is_activelayer_active() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( self::PLUGIN_BASENAME );
	}

	/**
	 * Whether the ActiveLayer plugin is installed (present, active or not).
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function is_activelayer_installed() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return array_key_exists( self::PLUGIN_BASENAME, get_plugins() );
	}

	/**
	 * Whether the current user can install plugins on this site.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function can_install() {

		return current_user_can( 'install_plugins' ) && wp_is_file_mod_allowed( 'easy_wp_smtp_can_install' );
	}

	/**
	 * Whether ActiveLayer has a validated API key connected.
	 *
	 * Mirrors ActiveLayer's own validation checks via cheap option reads,
	 * without booting any ActiveLayer classes.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function is_api_key_connected() {

		$settings = get_option( 'activelayer_global_settings', [] );
		$api_key  = '';

		if ( is_array( $settings ) && isset( $settings['api_key'] ) && is_string( $settings['api_key'] ) ) {
			$api_key = trim( $settings['api_key'] );
		}

		if ( empty( $api_key ) ) {
			return false;
		}

		$validation = get_option( 'activelayer_api_key_validated', [] );

		return is_array( $validation ) &&
			! empty( $validation['is_valid'] ) &&
			! empty( $validation['key'] ) &&
			$validation['key'] === $api_key;
	}

	/**
	 * Absolute URL of the ActiveLayer settings page (connect-account CTA).
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	private function get_settings_url() {

		return admin_url( 'admin.php?page=' . self::SETTINGS_PAGE );
	}

	/**
	 * Absolute URL of the ActiveLayer integrations page (connected-state link).
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	private function get_integrations_url() {

		return admin_url( 'admin.php?page=' . self::INTEGRATIONS_PAGE );
	}

	/**
	 * Resolve the render state for the current site and user.
	 *
	 * @since 2.15.0
	 *
	 * @return string One of 'install', 'activate', 'goto-url', 'connect', 'connected', or '' to hide.
	 */
	private function get_state() {

		if ( $this->is_activelayer_active() ) {
			return $this->is_api_key_connected() ? 'connected' : 'connect';
		}

		$is_installed = $this->is_activelayer_installed();

		if ( ! $is_installed && $this->can_install() ) {
			return 'install';
		}

		if ( $is_installed && current_user_can( 'activate_plugins' ) ) {
			return 'activate';
		}

		// Admins who cannot run the installer (file mods locked) can still follow a link.
		if ( current_user_can( 'install_plugins' ) ) {
			return 'goto-url';
		}

		// Users who cannot act at all (e.g. shop managers) get no dead-end card.
		return '';
	}

	/**
	 * Whether the section should be displayed for the current user.
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function should_display() {

		/**
		 * Filters whether the ActiveLayer education section is rendered on the
		 * WooCommerce Accounts & Privacy settings tab.
		 *
		 * @since 2.15.0
		 *
		 * @param bool $should_display Whether the section should be displayed.
		 */
		if ( ! apply_filters( 'easy_wp_smtp_admin_woo_commerce_active_layer_education_should_display', true ) ) { // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			return false;
		}

		if ( get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return false;
		}

		return $this->get_state() !== '';
	}

	/**
	 * Output the section after the WooCommerce account settings block.
	 *
	 * Fires on woocommerce_settings_account_registration_options_after, which
	 * WooCommerce runs after the section's closing table tag, so free-form
	 * markup is safe here.
	 *
	 * @since 2.15.0
	 */
	public function output_section() {

		if ( ! $this->should_display() ) {
			return;
		}

		$state = $this->get_state();
		?>
		<section id="esmtp-activelayer-wc" class="esmtp-activelayer-wc" aria-labelledby="esmtp-activelayer-wc-heading">
			<button type="button" class="esmtp-activelayer-wc__dismiss easy-wp-smtp-activelayer-wc-dismiss" aria-label="<?php echo esc_attr__( 'Dismiss this section', 'easy-wp-smtp' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
			<div class="esmtp-activelayer-wc__icon">
				<img src="<?php echo esc_url( easy_wp_smtp()->assets_url . '/images/education/activelayer.svg' ); ?>" alt="">
			</div>
			<div class="esmtp-activelayer-wc__body">
				<h3 id="esmtp-activelayer-wc-heading" class="esmtp-activelayer-wc__heading">
					<?php echo esc_html__( 'Stop Fake Customer Registrations and Review Spam', 'easy-wp-smtp' ); ?>
				</h3>
				<?php $this->output_state( $state ); ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Output the state-dependent description and call to action.
	 *
	 * @since 2.15.0
	 *
	 * @param string $state Render state.
	 */
	private function output_state( $state ) {

		if ( $state === 'connect' ) {
			?>
			<p class="esmtp-activelayer-wc__description">
				<?php echo esc_html__( 'ActiveLayer is active. Connect your free account to start blocking fake registrations and review spam.', 'easy-wp-smtp' ); ?>
			</p>
			<?php
			if ( current_user_can( 'manage_activelayer' ) ) {
				?>
				<button type="button" class="esmtp-activelayer-wc__cta easy-wp-smtp-activelayer-button" data-action="goto-settings" data-url="<?php echo esc_url( $this->get_settings_url() ); ?>">
					<?php echo esc_html__( 'Connect Your Free Account', 'easy-wp-smtp' ); ?>
				</button>
				<?php
			}

			return;
		}

		if ( $state === 'connected' ) {
			?>
			<p class="esmtp-activelayer-wc__description">
				<?php echo esc_html__( 'ActiveLayer is protecting customer registration and product reviews.', 'easy-wp-smtp' ); ?>
				<?php if ( current_user_can( 'manage_activelayer' ) ) : ?>
					<a href="<?php echo esc_url( $this->get_integrations_url() ); ?>">
						<?php echo esc_html__( 'View ActiveLayer Settings', 'easy-wp-smtp' ); ?>
					</a>
				<?php endif; ?>
			</p>
			<?php

			return;
		}

		if ( $state === 'install' ) {
			$action      = 'install';
			$button_text = esc_html__( 'Install & Activate ActiveLayer', 'easy-wp-smtp' );
			$button_url  = '';
		} elseif ( $state === 'activate' ) {
			$action      = 'activate';
			$button_text = esc_html__( 'Activate ActiveLayer', 'easy-wp-smtp' );
			$button_url  = '';
		} else {
			$action      = 'goto-url';
			$button_text = esc_html__( 'Get ActiveLayer', 'easy-wp-smtp' );
			$button_url  = self::WPORG_URL;
		}
		?>
		<p class="esmtp-activelayer-wc__description">
			<?php echo esc_html__( 'Blocks bot signups and review spam on My Account, checkout, and product reviews. No CAPTCHA anywhere on the path to purchase. Free plugin from WordPress.org, with 1,000 free spam checks and no credit card required.', 'easy-wp-smtp' ); ?>
		</p>
		<button type="button" class="esmtp-activelayer-wc__cta easy-wp-smtp-activelayer-button" data-action="<?php echo esc_attr( $action ); ?>" data-url="<?php echo esc_url( $button_url ); ?>">
			<?php echo esc_html( $button_text ); ?>
		</button>
		<?php
	}

	/**
	 * Enqueue the section assets on the WooCommerce Accounts & Privacy tab only.
	 *
	 * @since 2.15.0
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {

		if ( $hook !== 'woocommerce_page_wc-settings' ) {
			return;
		}

		// Read-only context detection on a GET request; no nonce applies.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( $tab !== 'account' || ! $this->should_display() ) {
			return;
		}

		wp_enqueue_style(
			'easy-wp-smtp-activelayer-wc',
			easy_wp_smtp()->assets_url . '/css/smtp-activelayer-wc' . WP::asset_min() . '.css',
			[],
			EasyWPSMTP_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'easy-wp-smtp-activelayer-wc',
			easy_wp_smtp()->assets_url . '/js/smtp-activelayer-wc' . WP::asset_min() . '.js',
			[ 'jquery' ],
			EasyWPSMTP_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'easy-wp-smtp-activelayer-wc',
			'easy_wp_smtp_activelayer_wc',
			[
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'easy-wp-smtp-admin' ),
				'plugin'          => self::PLUGIN_BASENAME,
				'download_url'    => self::DOWNLOAD_URL,
				'settings_url'    => $this->get_settings_url(),
				'wporg_url'       => self::WPORG_URL,
				'installing'      => esc_html__( 'Installing...', 'easy-wp-smtp' ),
				'activating'      => esc_html__( 'Activating...', 'easy-wp-smtp' ),
				'goto_settings'   => esc_html__( 'Connect Your Free Account', 'easy-wp-smtp' ),
				'get_activelayer' => esc_html__( 'Get ActiveLayer', 'easy-wp-smtp' ),
				'error_install'   => esc_html__( 'Could not install ActiveLayer. Please download it from WordPress.org and install manually.', 'easy-wp-smtp' ),
				'error_activate'  => esc_html__( 'Could not activate ActiveLayer. Please activate it from the Plugins page.', 'easy-wp-smtp' ),
			]
		);
	}

	/**
	 * AJAX: dismiss the section for the current user.
	 *
	 * @since 2.15.0
	 */
	public function ajax_dismiss() {

		if ( check_ajax_referer( 'easy-wp-smtp-admin', 'nonce', false ) === false ) {
			wp_send_json_error( esc_html__( 'Could not dismiss the section. Please reload the page and try again.', 'easy-wp-smtp' ) );
		}

		update_user_meta( get_current_user_id(), self::DISMISS_META, time() );

		wp_send_json_success();
	}
}
