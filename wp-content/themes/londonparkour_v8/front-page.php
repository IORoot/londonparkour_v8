<?php
/**
 * Front page — pure ACF Flexible Content.
 *
 * Every section is a layout under blocks/. There is no registry: lp_render_sections()
 * dispatches each row to blocks/{layout}/{layout}.php. See app/includes/modules.php.
 *
 * Nothing was ported into this file. src/stories/Pages/Homepage/Homepage.js is a
 * pure composition of Blocks that already exist here — its only content is the
 * ORDER, which on WordPress is editor data, not markup. That order is recorded
 * here because bin/seed.php (Phase 6) has to reproduce it and it is otherwise
 * only derivable by reading the .pen frame sequence again:
 *
 *   hero → marquee → classes → pricing → private_coaching → statement →
 *   tutorials → clients → testimonials → locations → coaches → cta
 *
 * Nav and footer are NOT rows — they are get_header()/get_footer(), which is
 * also what keeps them outside <main>, the landmark contract the Storybook page
 * states explicitly. GiftCardUpsell is out of scope for this composition.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main">
	<?php lp_render_sections(); ?>
</main>

<?php
get_footer();
