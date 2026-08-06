<?php
/**
 * Site footer and the close of the document.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

lp_part(
	'site/footer',
	array(
		'brand_href'  => home_url( '/' ),
		'brand_label' => get_bloginfo( 'name' ),
		'columns'     => lp_menu_columns( 'footer' ),
	)
);

wp_footer();
?>
</body>
</html>
