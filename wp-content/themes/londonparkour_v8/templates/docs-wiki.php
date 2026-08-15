<?php
/**
 * Wiki article — same docs chrome as the FAQ landing, body swapped for
 * the support post's content.
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js (`article` mode).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_post   = get_post();
$lp_title  = $lp_post instanceof WP_Post ? get_the_title( $lp_post ) : '';
$lp_crumb  = strtoupper( $lp_title );
$lp_active = lp_docs_is_gift_cards( $lp_post ) ? 'gift-cards' : 'wiki';

get_header();
?>

<main id="main">
	<?php
	lp_docs_render_wiki_chrome_start( $lp_crumb, $lp_active, $lp_title );
	?>

	<div class="w-full bg-base-100" data-component="docs-wiki-body">
		<div class="mx-auto w-full max-w-[960px] px-6 lg:px-16 py-scale-2xl flex flex-col gap-[28px]">
			<?php lp_docs_render_markdown_body( $lp_post ); ?>
		</div>
	</div>

	<?php lp_docs_render_wiki_chrome_end(); ?>
</main>

<?php
get_footer();
