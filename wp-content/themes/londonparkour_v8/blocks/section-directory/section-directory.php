<?php
/**
 * Section Directory — DocsFaq section index rows.
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js (`data-component="docs-faq-section-directory"`).
 *
 * @param array $args['rows'] Rows of index, title, meta, icon, href.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_rows = array(
	array(
		'index' => 'SECTION A',
		'title' => 'Classes',
		'meta'  => '05 questions · running, lateness, weather, refunds',
		'icon'  => 'icon-academic-cap',
		'href'  => '#classes',
	),
	array(
		'index' => 'SECTION B',
		'title' => 'Private Sessions',
		'meta'  => '03 questions · pricing, parties, what you get',
		'icon'  => 'icon-user-group',
		'href'  => '#private-sessions',
	),
	array(
		'index' => 'SECTION C',
		'title' => 'Gift Cards',
		'meta'  => '02 questions · buying, redeeming, expiry',
		'icon'  => 'icon-tag',
		'href'  => '#gift-cards',
	),
);

$lp_rows = array();
foreach ( is_array( $args['rows'] ?? null ) ? $args['rows'] : array() as $lp_row ) {
	if ( is_array( $lp_row ) && ! empty( $lp_row['title'] ) ) {
		$lp_rows[] = $lp_row;
	}
}
if ( ! $lp_rows ) {
	$lp_rows = $lp_default_rows;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full bg-base-100', $lp_spacing ); ?>" data-component="docs-faq-section-directory"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="px-6 lg:px-16 py-2">
		<div class="divide-y divide-base-300">
			<?php foreach ( $lp_rows as $lp_row ) : ?>
				<?php
				lp_part(
					'components/list-row',
					array(
						'index'   => (string) ( $lp_row['index'] ?? '' ),
						'title'   => (string) ( $lp_row['title'] ?? '' ),
						'meta'    => (string) ( $lp_row['meta'] ?? '' ),
						'icon'    => (string) ( $lp_row['icon'] ?? '' ),
						'href'    => (string) ( $lp_row['href'] ?? '' ),
						'surface' => 'page',
					)
				);
				?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
