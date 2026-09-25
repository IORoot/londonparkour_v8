<?php
/**
 * Document head and the landing header.
 *
 * The landing nav is a sibling of <main>, never inside it.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php lp_gtm_print_head(); ?>
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php lp_gtm_print_noscript(); ?>
<?php wp_body_open(); ?>

<a class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-4 focus:left-4 focus:bg-primary focus:text-primary-content focus:px-4 focus:py-2 focus:font-label focus:text-[11px] focus:font-semibold focus:uppercase focus:tracking-[0.9px]" href="#main">
	<?php esc_html_e( 'Skip to content', 'londonparkour_v8' ); ?>
</a>

<?php
lp_part(
	'site/landing-nav',
	array(
		'brand'        => get_bloginfo( 'name' ),
		'home_href'    => home_url( '/' ),
		'classes_href' => lp_classes_page_url( 'classes' ),
		'private_href' => home_url( '/private-coaching/' ),
	)
);
?>
