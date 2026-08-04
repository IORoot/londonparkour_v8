<?php

namespace EasyWPSMTP\Compatibility\Plugin;

/**
 * WooCommerce compatibility plugin.
 *
 * @since 2.6.0
 */
class WooCommerce extends PluginAbstract {

	/**
	 * Get plugin name.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public static function get_name() {

		return 'WooCommerce';
	}

	/**
	 * Get plugin path.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public static function get_path() {

		return 'woocommerce/woocommerce.php';
	}

	/**
	 * Execute on init action.
	 *
	 * @since 2.6.0
	 */
	public function load() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		if ( easy_wp_smtp()->get_queue()->is_enabled() ) {
			add_filter( 'woocommerce_defer_transactional_emails', '__return_false', PHP_INT_MAX );
		}
	}
}
