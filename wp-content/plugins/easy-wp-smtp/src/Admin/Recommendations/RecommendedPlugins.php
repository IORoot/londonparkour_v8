<?php

namespace EasyWPSMTP\Admin\Recommendations;

use EasyWPSMTP\Admin\Area;
use EasyWPSMTP\Admin\PluginsInstallSkin;
use EasyWPSMTP\Helpers\Helpers;
use Plugin_Upgrader;

/**
 * Recommended-plugins rotating menu: catalog, rotation state, sidebar item.
 *
 * Surfaces one sister AM product at a time, advancing through the priority
 * list as each is adopted (a product is "adopted" 7 days after its plugin is
 * first activated). Once every product is adopted the item is hidden.
 *
 * @since 2.15.0
 */
class RecommendedPlugins {

	/**
	 * First-activation timestamps keyed by product slug.
	 *
	 * @since 2.15.0
	 */
	const ACTIVATED_OPTION = 'easy_wp_smtp_recommended_plugins_activated';

	/**
	 * A product is rotated past this long after its plugin was first activated.
	 *
	 * @since 2.15.0
	 */
	const ADOPTED_AFTER = 7 * DAY_IN_SECONDS;

	/**
	 * Register hooks for the recommended-plugins menu.
	 *
	 * @since 2.15.0
	 */
	public function hooks() {

		add_action( 'admin_init', [ $this, 'register_pages' ] );
		add_action( 'activated_plugin', [ $this, 'record_activation' ], 10, 2 );
	}

	/**
	 * Instantiate every product landing page so each can register its own
	 * AJAX and asset hooks.
	 *
	 * Runs on `admin_init` rather than synchronously: the catalog labels are
	 * translated, and building it before `init` triggers a textdomain-too-early
	 * notice on Pro, where the email log boots the admin during `plugins_loaded`.
	 *
	 * @since 2.15.0
	 */
	public function register_pages() {

		foreach ( $this->get_products() as $product ) {
			if ( class_exists( $product['page_class'] ) ) {
				new $product['page_class']();
			}
		}
	}

	/**
	 * Register the rotating submenu item for the currently surfaced product.
	 *
	 * Called by Area during its menu build so the item lands in the right
	 * position; does nothing once every product is adopted.
	 *
	 * @since 2.15.0
	 *
	 * @param string $access_capability Capability required to view the page.
	 */
	public function add_submenu_item( $access_capability ) {

		$product = $this->get_current_product();

		if ( $product === null ) {
			return;
		}

		add_submenu_page(
			Area::SLUG,
			$product['label'],
			$product['label'],
			$access_capability,
			$product['page_class']::SLUG,
			[ $this, 'render_current_page' ]
		);

		$short_slug = substr( $product['page_class']::SLUG, strlen( Area::SLUG . '-' ) );

		if ( ! in_array( $short_slug, Area::$pages_registered, true ) ) {
			Area::$pages_registered[] = $short_slug;
		}
	}

	/**
	 * Render the landing page for the currently surfaced product.
	 *
	 * @since 2.15.0
	 */
	public function render_current_page() {

		$product = $this->get_current_product();

		if ( $product === null ) {
			return;
		}

		$page = new $product['page_class']();

		// The id is required: all `esmtp:` Tailwind utilities are build-scoped
		// under `#easy-wp-smtp`, so the landing markup must live beneath it.
		echo '<div class="wrap easy-wp-smtp-page" id="easy-wp-smtp">';
		$page->output();
		echo '</div>';
	}

	/**
	 * Ordered product catalog.
	 *
	 * @since 2.15.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_products() {

		return [
			[
				'slug'              => 'wpconsent',
				'name'              => 'WPConsent',
				'label'             => esc_html__( 'Privacy Compliance', 'easy-wp-smtp' ),
				'page_class'        => Pages\WPConsent::class,
				'plugin'            => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
				'plugin_pro'        => 'wpconsent-premium/wpconsent-premium.php',
				'activation_option' => 'wpconsent_activated',
			],
			[
				'slug'              => 'activelayer',
				'name'              => 'ActiveLayer',
				'label'             => esc_html__( 'Spam Protection', 'easy-wp-smtp' ),
				'page_class'        => Pages\ActiveLayer::class,
				'plugin'            => 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php',
				'activation_option' => '', // No activation-time option exposed by the plugin.
			],
			[
				'slug'              => 'duplicator',
				'name'              => 'Duplicator',
				'label'             => esc_html__( 'Backups', 'easy-wp-smtp' ),
				'page_class'        => Pages\Duplicator::class,
				'plugin'            => 'duplicator/duplicator.php',
				'plugin_pro'        => 'duplicator-pro/duplicator-pro.php',
				'activation_option' => 'duplicator_install_info', // migration reads ['time'].
			],
			[
				'slug'              => 'wpvibe',
				'name'              => 'WPVibe',
				'label'             => esc_html__( 'AI MCP', 'easy-wp-smtp' ),
				'page_class'        => Pages\WPVibe::class,
				'plugin'            => 'vibe-ai/vibe-ai.php',
				'activation_option' => '', // No activation-time option exposed by the plugin.
			],
			[
				'slug'              => 'universally',
				'name'              => 'Universally',
				'label'             => esc_html__( 'Translations', 'easy-wp-smtp' ),
				'page_class'        => Pages\Universally::class,
				'plugin'            => 'universally-language-translation-multilingual-tool/universally.php',
				'activation_option' => '', // No activation-time option exposed by the plugin.
			],
			[
				'slug'              => 'wpcode',
				'name'              => 'WPCode',
				'label'             => esc_html__( 'Code Snippets', 'easy-wp-smtp' ),
				'page_class'        => Pages\WPCode::class,
				'plugin'            => 'insert-headers-and-footers/ihaf.php',
				'plugin_pro'        => 'wpcode-premium/wpcode.php',
				'activation_option' => 'ihaf_activated', // migration reads ['wpcode'].
			],
		];
	}

	/**
	 * Map of catalog plugin files (lite + pro) to product slug.
	 *
	 * @since 2.15.0
	 *
	 * @return array<string, string>
	 */
	private function get_plugin_slug_map() {

		$map = [];

		foreach ( $this->get_products() as $product ) {
			if ( ! empty( $product['plugin'] ) ) {
				$map[ $product['plugin'] ] = $product['slug'];
			}
			if ( ! empty( $product['plugin_pro'] ) ) {
				$map[ $product['plugin_pro'] ] = $product['slug'];
			}
		}

		return $map;
	}

	/**
	 * Record first activation time of a catalog plugin (write-once).
	 *
	 * @since 2.15.0
	 *
	 * @param string $plugin       Plugin file relative to the plugins dir.
	 * @param bool   $network_wide Whether activation is network-wide.
	 */
	public function record_activation( $plugin, $network_wide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$slug = $this->get_plugin_slug_map()[ $plugin ] ?? '';

		if ( $slug === '' ) {
			return;
		}

		$activated = (array) get_option( self::ACTIVATED_OPTION, [] );

		if ( isset( $activated[ $slug ] ) ) {
			return;
		}

		$activated[ $slug ] = time();

		update_option( self::ACTIVATED_OPTION, $activated );
	}

	/**
	 * The product to surface right now, or null once every product is adopted.
	 *
	 * @since 2.15.0
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_current_product() {

		$products  = $this->get_products();
		$activated = (array) get_option( self::ACTIVATED_OPTION, [] );
		$now       = time();

		foreach ( $products as $product ) {
			$timestamp = isset( $activated[ $product['slug'] ] ) ? (int) $activated[ $product['slug'] ] : 0;

			if ( $timestamp === 0 || ( $now - $timestamp ) < self::ADOPTED_AFTER ) {
				return $product;
			}
		}

		// Everything adopted: surface nothing. The standard "About Us" submenu
		// remains as the trailing item.
		return null;
	}

	/**
	 * Plugins installable via the AJAX install/activate handlers.
	 *
	 * A dedicated, self-contained list (the install action's allow-list) in the
	 * same shape as WP Mail SMTP's AboutTab::get_am_plugins(): each entry carries
	 * the Lite `path` + download `url`, plus the `pro` basename where one exists.
	 * This is intentionally separate from the rotating recommendation catalog
	 * ({@see self::get_products()}); it covers only the plugins ESMTP offers to
	 * install across its surfaces (AI MCP tab, Test Email pro tip, Pro plugins
	 * list, recommendation pages).
	 *
	 * @since 2.15.0
	 *
	 * @return array<string, array> Keyed by slug.
	 */
	private function get_install_plugins() {

		return [
			'wpconsent'   => [
				'name' => 'WPConsent',
				'path' => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
				'url'  => 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip',
				'pro'  => [
					'path' => 'wpconsent-premium/wpconsent-premium.php',
				],
			],
			'activelayer' => [
				'name' => 'ActiveLayer',
				'path' => 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php',
				'url'  => 'https://downloads.wordpress.org/plugin/activelayer-anti-spam-spam-protection-for-forms-comments.zip',
			],
			'duplicator'  => [
				'name' => 'Duplicator',
				'path' => 'duplicator/duplicator.php',
				'url'  => 'https://downloads.wordpress.org/plugin/duplicator.zip',
				'pro'  => [
					'path' => 'duplicator-pro/duplicator-pro.php',
				],
			],
			'wpvibe'      => [
				'name' => 'WPVibe',
				'path' => 'vibe-ai/vibe-ai.php',
				'url'  => 'https://downloads.wordpress.org/plugin/vibe-ai.zip',
			],
			'universally' => [
				'name' => 'Universally',
				'path' => 'universally-language-translation-multilingual-tool/universally.php',
				'url'  => 'https://downloads.wordpress.org/plugin/universally-language-translation-multilingual-tool.zip',
			],
			'wpcode'      => [
				'name' => 'WPCode',
				'path' => 'insert-headers-and-footers/ihaf.php',
				'url'  => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
				'pro'  => [
					'path' => 'wpcode-premium/wpcode.php',
				],
			],
		];
	}

	/**
	 * AJAX handler: install (and silently activate) a curated recommended plugin.
	 *
	 * Capability and nonce are already verified by {@see Area::process_ajax()};
	 * the `install_plugins` re-check below guards against any future caller that
	 * reaches this method through a different path.
	 *
	 * The posted `plugin` is a download URL. It is matched verbatim against the
	 * `url` values in {@see self::get_install_plugins()} — only a URL the list
	 * already declares is ever handed to the upgrader, so client input can never
	 * point the installer at an arbitrary source.
	 *
	 * Response (consumed by `app.pluginInstall` in smtp-admin.js):
	 * - success: `{ msg, is_activated, basename }`
	 * - error:   string message surfaced via `extractAjaxError`.
	 *
	 * @since 2.15.0
	 */
	public function ajax_plugin_install() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$error = esc_html__( 'Could not install the plugin.', 'easy-wp-smtp' );

		// Check for permissions.
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( $error );
		}

		if ( empty( $_POST['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error();
		}

		$plugin_url = esc_url_raw( wp_unslash( $_POST['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! in_array( $plugin_url, wp_list_pluck( array_values( $this->get_install_plugins() ), 'url' ), true ) ) {
			wp_send_json_error( esc_html__( 'Could not install the plugin. Plugin is not whitelisted.', 'easy-wp-smtp' ) );
		}

		// Prepare variables.
		$url = esc_url_raw(
			add_query_arg(
				[
					'page' => Area::SLUG,
				],
				admin_url( 'admin.php' )
			)
		);

		/*
		 * The `request_filesystem_credentials` function will output a credentials form in case of failure.
		 * We don't want that, since it will break AJAX response. So just hide output with a buffer.
		 */
		ob_start();
		// phpcs:ignore WPForms.Formatting.EmptyLineAfterAssigmentVariables.AddEmptyLine
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		ob_end_clean();

		// Check for file system permissions.
		if ( $creds === false ) {
			wp_send_json_error( $error );
		}

		if ( ! WP_Filesystem( $creds ) ) {
			wp_send_json_error( $error );
		}

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

		// Import the plugin upgrader.
		Helpers::include_plugin_upgrader();

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( new PluginsInstallSkin() );

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			wp_send_json_error( $error );
		}

		$installer->install( $plugin_url );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		if ( $installer->plugin_info() ) {

			$plugin_basename = $installer->plugin_info();

			// Activate the plugin silently.
			$activated = activate_plugin( $plugin_basename );

			if ( ! is_wp_error( $activated ) ) {
				wp_send_json_success(
					[
						'msg'          => esc_html__( 'Plugin installed & activated.', 'easy-wp-smtp' ),
						'is_activated' => true,
						'basename'     => $plugin_basename,
					]
				);
			} else {
				wp_send_json_success(
					[
						'msg'          => esc_html__( 'Plugin installed.', 'easy-wp-smtp' ),
						'is_activated' => false,
						'basename'     => $plugin_basename,
					]
				);
			}
		}

		wp_send_json_error( $error );
	}

	/**
	 * AJAX handler: activate an already-installed curated recommended plugin.
	 *
	 * Capability and nonce are already verified by {@see Area::process_ajax()};
	 * the `activate_plugins` re-check below guards against any future caller that
	 * reaches this method through a different path.
	 *
	 * The posted `plugin` is a plugin basename. It is matched against the Lite +
	 * Pro `path` values in {@see self::get_install_plugins()} — only a basename
	 * the list already declares can be activated.
	 *
	 * Response: success message string, or an error string.
	 *
	 * @since 2.15.0
	 */
	public function ajax_plugin_activate() {

		$error = esc_html__( 'Could not activate the plugin. Please activate it from the Plugins page.', 'easy-wp-smtp' );

		// Check for permissions.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( $error );
		}

		if ( empty( $_POST['plugin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( $error );
		}

		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['plugin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$whitelisted_plugins = [];

		foreach ( $this->get_install_plugins() as $item ) {
			if ( ! empty( $item['path'] ) ) {
				$whitelisted_plugins[] = $item['path'];
			}

			if ( ! empty( $item['pro']['path'] ) ) {
				$whitelisted_plugins[] = $item['pro']['path'];
			}
		}

		if ( ! in_array( $plugin_slug, $whitelisted_plugins, true ) ) {
			wp_send_json_error( esc_html__( 'Could not activate the plugin. Plugin is not whitelisted.', 'easy-wp-smtp' ) );
		}

		$activate = activate_plugins( $plugin_slug );

		if ( ! is_wp_error( $activate ) ) {
			wp_send_json_success( esc_html__( 'Plugin activated.', 'easy-wp-smtp' ) );
		}

		wp_send_json_error( $error );
	}
}
