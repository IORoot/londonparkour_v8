<?php

namespace EasyWPSMTP\Compatibility\Plugin;

/**
 * Polylang compatibility plugin.
 *
 * @since 2.12.0
 */
class PolylangPro extends Polylang {

	/**
	 * Get plugin name.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public static function get_name() {

		return 'Polylang Pro';
	}

	/**
	 * Get plugin path.
	 *
	 * @since 2.12.0
	 *
	 * @return string
	 */
	public static function get_path() {

		return 'polylang-pro/polylang.php';
	}
}

