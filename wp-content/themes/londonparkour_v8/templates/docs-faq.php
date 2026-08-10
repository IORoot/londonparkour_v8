<?php
/**
 * Template Name: Docs — FAQ
 *
 * DocsFaq — breadcrumb → masthead → Flexible Content (section_directory, faq
 * groups, passenger_enquiries) → onward.
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js (`J8MoSB`). Chrome in the
 * template; content bands are shared page_sections layouts.
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
				array(
					'label' => 'DOCS',
					'href'  => home_url( '/docs-faq/' ),
				),
				array( 'label' => 'FREQUENTLY ASKED QUESTIONS' ),
			),
			'action' => array(
				'label' => 'ALL DOCS ↗',
				'href'  => home_url( '/docs-faq/' ),
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'Questions, answered.',
			'note'  => 'Guides, FAQs and stories from LondonParkour. Start with answers to common questions — or switch to Blog for news and projects.',
		)
	);

	lp_render_sections();

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CONTACT',
				'label'   => 'Send us a message',
				'href'    => home_url( '/contact/' ),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Or just turn up in trainers',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
	?>
</main>

<?php
get_footer();
