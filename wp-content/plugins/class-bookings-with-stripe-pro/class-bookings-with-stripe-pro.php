<?php
/**
 * Plugin Name: Class Bookings with Stripe Pro
 * Description: Class Bookings with Stripe Pro — Stripe Checkout for classes. ACF-driven class types, capacity-aware date dropdowns, customer + admin emails, Elementor widget and shortcode.
 * Plugin URI: https://ioroot.com
 * Version: 1.0.0
 * Author: IORoot.com
 * Text Domain: class-bookings-with-stripe-pro
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

defined( 'ABSPATH' ) || exit;

define( 'CLASBOWPRO_VERSION', '1.0.0' );
define( 'CLASBOWPRO_TEXT_DOMAIN', 'class-bookings-with-stripe-pro' );
define( 'CLASBOWPRO_FILE', __FILE__ );
define( 'CLASBOWPRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLASBOWPRO_URL', plugin_dir_url( __FILE__ ) );
define( 'CLASBOWPRO_REST_NS', 'clasbpro/v1' );
define( 'CLASBOWPRO_HOLD_SECONDS', 30 * MINUTE_IN_SECONDS );

require_once CLASBOWPRO_DIR . 'includes/class-constants.php';
require_once CLASBOWPRO_DIR . 'includes/class-secrets.php';
require_once CLASBOWPRO_DIR . 'includes/class-migration.php';
require_once CLASBOWPRO_DIR . 'includes/class-acf-dependency.php';
\IOROOT_STRIPE_BOOKINGS_PRO\ACF_Dependency::init();
require_once CLASBOWPRO_DIR . 'vendor/stripe/stripe-php/init.php';
require_once CLASBOWPRO_DIR . 'includes/helpers.php';
require_once CLASBOWPRO_DIR . 'includes/class-cpt.php';
require_once CLASBOWPRO_DIR . 'includes/class-acf-fields.php';
require_once CLASBOWPRO_DIR . 'includes/class-slot-rules.php';
require_once CLASBOWPRO_DIR . 'includes/class-appointment-admin.php';
require_once CLASBOWPRO_DIR . 'includes/class-bookings.php';
require_once CLASBOWPRO_DIR . 'includes/class-packs.php';
require_once CLASBOWPRO_DIR . 'includes/class-schedule-calendar.php';
require_once CLASBOWPRO_DIR . 'includes/class-extra-fields.php';
require_once CLASBOWPRO_DIR . 'includes/class-stripe-service.php';
require_once CLASBOWPRO_DIR . 'includes/class-mailchimp.php';
require_once CLASBOWPRO_DIR . 'includes/class-scheduled-emails.php';
require_once CLASBOWPRO_DIR . 'includes/class-booking-email-status.php';
require_once CLASBOWPRO_DIR . 'includes/class-merge-tags.php';
require_once CLASBOWPRO_DIR . 'includes/class-emails.php';
require_once CLASBOWPRO_DIR . 'includes/class-class-email-overrides.php';
require_once CLASBOWPRO_DIR . 'includes/class-email-body-editor.php';
require_once CLASBOWPRO_DIR . 'includes/class-rest.php';
require_once CLASBOWPRO_DIR . 'includes/views/class-template-loader.php';
require_once CLASBOWPRO_DIR . 'includes/views/class-abstract-view.php';
require_once CLASBOWPRO_DIR . 'includes/views/class-booking-form-view.php';
require_once CLASBOWPRO_DIR . 'includes/views/class-booking-status-view.php';
require_once CLASBOWPRO_DIR . 'includes/views/class-global-schedule-view.php';
require_once CLASBOWPRO_DIR . 'includes/class-shortcode.php';
require_once CLASBOWPRO_DIR . 'includes/class-result-pages.php';
require_once CLASBOWPRO_DIR . 'includes/class-reports.php';
require_once CLASBOWPRO_DIR . 'includes/class-theme-registry.php';
require_once CLASBOWPRO_DIR . 'includes/class-theme-loader.php';
require_once CLASBOWPRO_DIR . 'includes/class-theme-installer.php';
require_once CLASBOWPRO_DIR . 'includes/class-theme-preview.php';
require_once CLASBOWPRO_DIR . 'includes/class-themes.php';
require_once CLASBOWPRO_DIR . 'includes/class-elementor.php';
require_once CLASBOWPRO_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, [ 'IOROOT_STRIPE_BOOKINGS_PRO\\Migration', 'maybe_run' ] );
register_activation_hook( __FILE__, [ 'IOROOT_STRIPE_BOOKINGS_PRO\\Result_Pages', 'on_activate' ] );
register_activation_hook( __FILE__, [ 'IOROOT_STRIPE_BOOKINGS_PRO\\Theme_Preview', 'on_activate' ] );
register_activation_hook( __FILE__, [ 'IOROOT_STRIPE_BOOKINGS_PRO\\Bookings', 'on_activate' ] );
register_deactivation_hook( __FILE__, [ 'IOROOT_STRIPE_BOOKINGS_PRO\\Bookings', 'on_deactivate' ] );

add_action( 'plugins_loaded', static function () {
	\IOROOT_STRIPE_BOOKINGS_PRO\Migration::maybe_run();
	\IOROOT_STRIPE_BOOKINGS_PRO\Plugin::instance()->init();
}, 5 );

add_action( 'admin_notices', static function () {
	if ( ! current_user_can( 'activate_plugins' ) || ! defined( 'CLASBOWI_VERSION' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>';
	echo esc_html__(
		'Class Bookings with Stripe Pro and the free version are both active. Deactivate one to avoid duplicate shortcodes and split booking data.',
		'class-bookings-with-stripe-pro'
	);
	echo '</p></div>';
} );
