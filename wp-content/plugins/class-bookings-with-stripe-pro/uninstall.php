<?php
/**
 * Fired when the plugin is uninstalled via Plugins → Delete.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-result-pages.php';
require_once __DIR__ . '/includes/class-theme-preview.php';

\IOROOT_STRIPE_BOOKINGS_PRO\Result_Pages::on_uninstall();
\IOROOT_STRIPE_BOOKINGS_PRO\Theme_Preview::on_uninstall();
