<?php

namespace EasyWPSMTP\Compatibility\Plugin;

/**
 * WPForms compatibility plugin.
 *
 * @since 2.6.0
 */
class WPForms extends WPFormsLite {

	/**
	 * Get plugin name.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public static function get_name() {

		return 'WPForms';
	}

	/**
	 * Get plugin path.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public static function get_path() {

		return 'wpforms/wpforms.php';
	}
}
