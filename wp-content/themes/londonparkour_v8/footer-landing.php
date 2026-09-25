<?php
/**
 * Landing footer and the close of the document.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_phone = function_exists( 'lp_seo_option_field' ) ? lp_seo_option_field( 'seo_org_phone' ) : '';
$lp_phone = is_string( $lp_phone ) ? $lp_phone : '';

lp_part(
	'site/landing-footer',
	array(
		'brand_href'   => home_url( '/' ),
		'brand_label'  => get_bloginfo( 'name' ),
		'email'        => 'contact@londonparkour.com',
		'phone'        => $lp_phone,
		'privacy_href' => lp_landing_legal_url( array( 'privacy', 'privacy-policy' ) ),
		'terms_href'   => lp_landing_legal_url( array( 'terms', 'terms-of-service', 'terms-of-use' ) ),
	)
);

wp_footer();
?>
</body>
</html>
