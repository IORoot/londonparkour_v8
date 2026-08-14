<?php
/**
 * Template Name: Docs — FAQ
 *
 * DocsFaq — breadcrumb → masthead → view rail (Wiki / FAQ / Blog) →
 * Flexible Content (section_directory, faq groups, passenger_enquiries) →
 * docs index → onward.
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

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'docs',
			'aria_label' => 'Docs view',
			'tabs'       => lp_docs_view_tabs( 'faq' ),
		)
	);

	lp_render_sections();
	?>

	<div class="w-full bg-base-100" data-component="docs-index" id="docs-index">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[28px]">
			<div class="flex items-end justify-between gap-4">
				<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-base-content">DOCS INDEX</span>
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65">15 PAGES</span>
			</div>
			<div class="h-px w-full bg-base-content" aria-hidden="true"></div>
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-x-16 gap-y-10">
				<?php foreach ( lp_docs_index_groups() as $lp_group ) : ?>
					<div class="flex flex-col">
						<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-base-content/65 pb-3"><?php echo esc_html( $lp_group['heading'] ); ?></span>
						<div class="divide-y divide-base-300">
							<?php foreach ( $lp_group['pages'] as $lp_page ) : ?>
								<?php
								lp_part(
									'components/list-row',
									array(
										'index'   => '',
										'title'   => $lp_page['title'],
										'meta'    => '',
										'marker'  => $lp_page['marker'],
										'href'    => $lp_page['href'],
										'surface' => 'page',
									)
								);
								?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php

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
