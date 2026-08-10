<?php
/**
 * Document head and the opening of the page.
 *
 * The site nav is a sibling of <main>, never inside it — the landmark contract
 * carried over from the Storybook is: exactly one <main> per page, nav and
 * footer outside it, the page <h1> inside it.
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
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-4 focus:left-4 focus:bg-primary focus:text-primary-content focus:px-4 focus:py-2 focus:font-label focus:text-[11px] focus:font-semibold focus:uppercase focus:tracking-[0.9px]" href="#main">
	<?php esc_html_e( 'Skip to content', 'londonparkour_v8' ); ?>
</a>

<?php
/*
 * The nav's own defaults are the Storybook's copy, so anything not passed here
 * still renders the design. `links` is the one thing WordPress genuinely owns:
 * an unassigned Primary menu yields an empty array and the partial falls back.
 */
lp_part(
	'site/nav',
	array(
		'brand'     => get_bloginfo( 'name' ),
		'home_href' => home_url( '/' ),
		'links'     => lp_menu_links( 'primary' ),
		// Homepage Hero embeds Nav with a transparent fill over the photo
		// (`T1cC4` / `DsXnG`). Other pages keep the opaque `bg-neutral` bar.
		'over_hero' => is_front_page(),
	)
);
?>
