<?php
/**
 * Landing page — fixed sequence, short chrome.
 *
 * Hero, statement, testimonials, FAQ, closing CTA. Words come from the
 * landing form. Testimonials are the latest real five-star quotes.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header( 'landing' );
?>
<main id="main">
	<?php
	while ( have_posts() ) :
		the_post();
		lp_landing_render( (int) get_the_ID() );
	endwhile;
	?>
</main>
<?php
get_footer( 'landing' );
