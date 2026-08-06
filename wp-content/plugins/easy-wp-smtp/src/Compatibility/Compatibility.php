<?php

namespace EasyWPSMTP\Compatibility;

use EasyWPSMTP\Compatibility\Plugin\Admin2020;
use EasyWPSMTP\Compatibility\Plugin\Polylang;
use EasyWPSMTP\Compatibility\Plugin\PolylangPro;
use EasyWPSMTP\Compatibility\Plugin\WooCommerce;
use EasyWPSMTP\Compatibility\Plugin\WPForms;
use EasyWPSMTP\Compatibility\Plugin\WPFormsLite;
use EasyWPSMTP\Compatibility\Plugin\WPML;

/**
 * Compatibility.
 * Class for managing compatibility with other plugins.
 *
 * @since 2.1.0
 */
class Compatibility {

	/**
	 * Initialized compatibility plugins.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	protected $plugins = [];

	/**
	 * Initialize class.
	 *
	 * @since 2.1.0
	 */
	public function init() {

		$this->setup_compatibility();
	}

	/**
	 * Setup compatibility plugins.
	 *
	 * @since 2.1.0
	 */
	public function setup_compatibility() {

		$plugins = [
			'admin-2020'   => Admin2020::class,
			'wpforms-lite' => WPFormsLite::class,
			'wpforms'      => WPForms::class,
			'woocommerce'  => WooCommerce::class,
			'wpml'         => WPML::class,
			'polylang'     => Polylang::class,
			'polylang-pro' => PolylangPro::class,
		];

		foreach ( $plugins as $key => $classname ) {
			if ( class_exists( $classname ) && is_callable( [ $classname, 'is_applicable' ] ) ) {
				if ( $classname::is_applicable() ) {
					$this->plugins[ $key ] = new $classname();
				}
			}
		}
	}

	/**
	 * Get compatibility plugin.
	 *
	 * @since 2.1.0
	 *
	 * @param string $key Plugin key.
	 *
	 * @return \EasyWPSMTP\Compatibility\Plugin\PluginAbstract | false
	 */
	public function get_plugin( $key ) {

		return isset( $this->plugins[ $key ] ) ? $this->plugins[ $key ] : false;
	}
}
