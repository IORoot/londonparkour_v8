<?php
/**
 * Template Name: Contact
 *
 * Contact — breadcrumb → masthead → Flexible Content (enquiries, other_ways,
 * faq) → onward. Nav/footer via get_header()/get_footer().
 *
 * Ported from src/stories/Pages/Contact/Contact.js (`C3rSg`). Chrome stays in
 * the template (Legal pattern); content bands are shared page_sections layouts.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array( 'label' => 'CONTACT' ),
			),
			'action' => array(
				'label' => 'FIND A SITE ↗',
				'href'  => home_url( '/classes-map/' ),
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => "Let's talk movement.",
			'note'  => 'Questions about classes, private coaching, gift cards or partnerships — we reply within 36H.',
		)
	);

	lp_render_sections();

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← FAQ',
				'label'   => 'Common passenger questions',
				'href'    => lp_docs_url(),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Start with the beginners board',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
