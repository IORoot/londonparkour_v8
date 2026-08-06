<?php
/**
 * Pages.
 *
 * Two shapes, one template. A page built from blocks has rows in the
 * `page_sections` Flexible Content field; a prose page (Legal, and anything an
 * editor writes long-form) has post content. Either may be empty, and a page
 * with both renders sections first, then prose.
 *
 * lp_render_sections() returns early when the field holds no array, so it is
 * safe to call unconditionally. The rows check below exists only to decide
 * whether the prose wrapper is emitted at all — an empty prose <div> on a pure
 * block page would introduce stray vertical rhythm.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lp_sections     = function_exists( 'get_field' ) ? get_field( 'page_sections' ) : null;
$lp_has_sections = is_array( $lp_sections ) && $lp_sections;
?>

<main id="main">
	<?php
	if ( $lp_has_sections ) {
		lp_render_sections();
	}

	while ( have_posts() ) :
		the_post();

		if ( '' === trim( get_the_content() ) ) {
			continue;
		}
		?>
		<div class="mx-auto w-full max-w-[720px] px-6 md:px-16 py-[64px]">
			<div <?php londonparkour_v8_content_class(); ?>>
				<?php the_content(); ?>
			</div>
		</div>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
