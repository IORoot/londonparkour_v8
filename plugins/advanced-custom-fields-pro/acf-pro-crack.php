<?php

// =============================================================================
// FUNCTIONS/PLUGINS/ACF-PRO.PHP
// -----------------------------------------------------------------------------
// Plugin setup for theme compatibility.
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Remove License Functionality
// =============================================================================

// Remove License Functionality
// =============================================================================

// None of this matters if its not the admin
if (!is_admin()) {
  return;
}


// ACF Pro license defined by the user
// ignore our activation and hiding
if (defined('ACF_PRO_LICENSE')) {
  return;
}

define('ACF_PRO_LICENSE', true);

function x_acf_pro_remove_license_functionality() {

  if ( function_exists( 'acf_updates' ) ) {
    $update_class = acf_updates();
    // Note to reviewer: This remove_filter call disabled ACF Pro automatic updates because we provide those
    // updates directly so the buyer doesn't need to purchase the plugin to get automatic updates.
    remove_filter( 'pre_set_site_transient_update_plugins', array( $update_class, 'modify_plugins_transient' ), 10, 1 );
  }

  add_filter( 'acf/settings/show_updates', '__return_false' );

}

add_action( 'init', 'x_acf_pro_remove_license_functionality', 0 );

// Force the ACF license as active
add_filter('pre_option_acf_pro_license_status', function() {
  return [
    'status' => 'active',
    'license' => '',
    'url' => '',
    'next_check' => time() + 10000,
    'error_msg' => '',
  ];
});

// ACF pro license data
add_filter('pre_option_acf_pro_license', function() {
  return base64_encode(serialize([
    'status' => 'active',
    'license' => '',
    'url' => '',
  ]));
});

// Saved transient error messages
add_filter('transient_acf_activation_error', function() {
  return null;
});

// Hide the final ACF Pro message
// was not easy to remove without this
add_action('admin_head', function() {
  echo '<style>.acf-nav-upgrade-wrap, .tmpl-acf-field-group-pro-features { display: none !important; }</style>';
});
