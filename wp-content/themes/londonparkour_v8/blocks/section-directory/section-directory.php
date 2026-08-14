<?php
/**
 * Section Directory — DocsFaq three-column index (`N2osq` / `dIS8Z`).
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js
 * (`data-component="docs-faq-section-directory"`). Equal-width cells with a
 * kicker, title and meta — not stacked ListRows. Hover fills primary with
 * neutral ink, same invert as View Rail — Docs.
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

$lp_cell_first = 'group flex-1 flex flex-col gap-3 pt-[34px] pb-[36px] pr-[44px] pl-0 no-underline text-left hover:bg-primary';
$lp_cell_rest  = 'group flex-1 flex flex-col gap-3 pt-[34px] pb-[36px] px-[44px] border-l border-base-300 no-underline text-left hover:bg-primary';
?>
<section class="<?php echo lp_classes( 'w-full bg-base-100 border-b border-base-300', $lp_spacing ); ?>" data-component="docs-faq-section-directory"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="px-6 lg:px-16">
		<div class="flex flex-col lg:flex-row">
			<?php foreach ( $lp_rows as $lp_i => $lp_row ) : ?>
				<?php
				$lp_href  = (string) ( $lp_row['href'] ?? '' );
				$lp_class = 0 === $lp_i ? $lp_cell_first : $lp_cell_rest;
				$lp_tag   = '' !== $lp_href ? 'a' : 'div';
				?>
				<<?php echo $lp_tag; ?>
					class="<?php echo esc_attr( $lp_class ); ?>"
					<?php if ( '' !== $lp_href ) : ?>href="<?php echo esc_url( $lp_href ); ?>"<?php endif; ?>
				>
					<?php
					lp_part(
						'elements/glyph-label',
						array(
							'label'   => (string) ( $lp_row['index'] ?? '' ),
							'icon_id' => (string) ( $lp_row['icon'] ?? '' ),
							'surface' => 'page',
							'tone'    => 'muted',
							'class'   => 'group-hover:text-neutral',
						)
					);
					?>
					<span class="font-heading text-[24px] font-medium tracking-[-0.6px] text-base-content group-hover:text-neutral"><?php echo esc_html( (string) ( $lp_row['title'] ?? '' ) ); ?></span>
					<span class="font-body text-[11px] font-normal tracking-[0.15px] leading-[1.55] text-base-content/65 group-hover:text-neutral"><?php echo esc_html( (string) ( $lp_row['meta'] ?? '' ) ); ?></span>
				</<?php echo $lp_tag; ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
