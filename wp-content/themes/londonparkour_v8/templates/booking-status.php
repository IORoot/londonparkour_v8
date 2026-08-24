<?php
/**
 * Template Name: Booking status
 *
 * Full-bleed shell for `[clasbpro_booking_status]`. Nav/footer via
 * get_header()/get_footer(). The shortcode overlay in
 * class-bookings-with-stripe/booking-status.php paints the Concourse
 * composition inside this one <main>.
 *
 * Applied automatically to slugs booking-confirmed / booking-cancelled /
 * booking-error (plugin Result_Pages) via lp_clasbpro_status_template_include().
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>

<?php
get_footer();
