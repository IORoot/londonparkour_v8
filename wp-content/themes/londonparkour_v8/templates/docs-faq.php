<?php
/**
 * Template Name: Docs — FAQ
 *
 * Wiki landing — breadcrumb → masthead → Wiki/Blog/Gift Cards switcher →
 * docs index → grouped FAQ (static Q+A) → passenger enquiries → onward.
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js (`J8MoSB`).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_faq       = lp_docs_find_support( array( 'frequently-asked-questions', 'faq' ) );
$lp_faq_title = $lp_faq instanceof WP_Post ? get_the_title( $lp_faq ) : 'Frequently Asked Questions';

get_header();
?>

<main id="main">
	<?php
	lp_docs_render_wiki_chrome_start( 'FREQUENTLY ASKED QUESTIONS', 'wiki', $lp_faq_title );

	$lp_faq_row = array( 'mode' => 'groups' );
	if ( function_exists( 'get_field' ) ) {
		$lp_sections = get_field( 'page_sections' );
		if ( is_array( $lp_sections ) ) {
			foreach ( $lp_sections as $lp_row ) {
				if ( is_array( $lp_row ) && 'faq' === ( $lp_row['acf_fc_layout'] ?? '' ) ) {
					$lp_faq_row = $lp_row;
					break;
				}
			}
		}
	}
	lp_render_block( 'faq', $lp_faq_row );

	lp_docs_render_wiki_chrome_end();
	?>
</main>

<?php
get_footer();
