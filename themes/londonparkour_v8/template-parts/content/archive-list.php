<?php
/**
 * The generic post-list body, shared by archive.php and index.php.
 *
 * **No Storybook source.** Neither a generic archive nor the last-resort
 * fallback is a designed page, so this composes designed pieces rather than
 * porting a layout: breadcrumb-rail and page-masthead as every ported page
 * uses them, then BlogIndex's "Recent" treatment for the list — the same
 * three-column blog-card grid on bg-base-100 at the same gaps — and the
 * pagination band. Nothing here is a new shape.
 *
 * It owns the single <main> so neither caller can forget it or emit two.
 *
 * archive.php serves category, tag, date and author archives; index.php is
 * WordPress's last resort. B4 and B5 add archive-lp_class.php,
 * archive-lp_tutorial.php and taxonomy-lp_series.php, which take precedence
 * over this and are real ports of real designs.
 *
 * The archive title comes from get_the_archive_title() through this theme's
 * own filter in inc/template-functions.php ("Category Archives: Foo"), with
 * its <span> stripped — page-masthead takes plain text. Where there is no
 * archive at all the site name stands in, so no headline is invented.
 *
 * An empty result set renders the masthead and nothing below it. The design
 * system has no zero-results state — SearchResults.js records that explicitly
 * — and inventing one is what the Port Brief forbids.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_title = is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : get_bloginfo( 'name' );
$lp_note  = is_archive() ? wp_strip_all_tags( get_the_archive_description() ) : '';

$lp_cards = array();
while ( have_posts() ) {
	the_post();
	$lp_cards[] = lp_post_card_args( get_post() );
}
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
				array( 'label' => strtoupper( $lp_title ) ),
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => $lp_title,
			'note'  => $lp_note,
		)
	);
	?>

	<?php if ( $lp_cards ) : ?>
		<div class="w-full bg-base-100" data-component="archive-list">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[36px]">
				<div class="grid grid-cols-1 md:grid-cols-3 gap-[24px]">
					<?php foreach ( $lp_cards as $lp_card ) : ?>
						<?php
						lp_part(
							'components/blog-card',
							array(
								'variant'   => 'grid',
								'image_id'  => $lp_card['image_id'],
								'category'  => $lp_card['category'],
								'read_time' => $lp_card['read_time'],
								'title'     => $lp_card['title'],
								'excerpt'   => $lp_card['excerpt'],
								'author'    => $lp_card['author'],
								'date'      => $lp_card['date'],
								'href'      => $lp_card['href'],
							)
						);
						?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php
		$lp_pagination = lp_pagination_args( null, 'POSTS' );
		if ( $lp_pagination ) {
			lp_part( 'components/pagination', $lp_pagination );
		}
		?>
	<?php endif; ?>
</main>
