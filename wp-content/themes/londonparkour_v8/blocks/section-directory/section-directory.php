<?php
/**
 * Section Directory — DocsFaq three-column index (`N2osq` / `dIS8Z`).
 *
 * Ported from src/stories/Pages/DocsFaq/DocsFaq.js
 * (`data-component="docs-faq-section-directory"`). Equal-width cells with a
 * kicker, title and meta — not stacked ListRows. One row at every breakpoint;
 * SECTION A/B/C kickers hide below `lg` so Wiki / Blog / Gift Cards fit.
 * Hover fills primary with
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
		'title' => 'Wiki',
		'meta'  => '15 pages',
		'icon'  => 'icon-book-open',
		'href'  => '/docs',
	),
	array(
		'index' => 'SECTION B',
		'title' => 'Blog',
		'meta'  => '12 stories',
		'icon'  => 'icon-newspaper',
		'href'  => '/blog',
	),
	array(
		'index' => 'SECTION C',
		'title' => 'Gift Cards',
		'meta'  => 'buying, redeeming, expiry',
		'icon'  => 'icon-tag',
		'href'  => '/docs/gift-cards',
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

$lp_cell = array(
	'first'         => 'group flex-1 min-w-0 flex flex-col gap-1 lg:gap-3 pt-4 pb-4 px-3 lg:pt-[34px] lg:pb-[36px] lg:pr-[44px] lg:pl-16 no-underline text-left hover:bg-primary',
	'rest'          => 'group flex-1 min-w-0 flex flex-col gap-1 lg:gap-3 pt-4 pb-4 px-3 lg:pt-[34px] lg:pb-[36px] lg:px-[44px] border-l border-base-300 no-underline text-left hover:bg-primary',
	'last'          => 'group flex-1 min-w-0 flex flex-col gap-1 lg:gap-3 pt-4 pb-4 px-3 lg:pt-[34px] lg:pb-[36px] lg:pl-[44px] lg:pr-16 border-l border-base-300 no-underline text-left hover:bg-primary',
	'first_current' => 'flex-1 min-w-0 flex flex-col gap-1 lg:gap-3 pt-4 pb-4 px-3 lg:pt-[34px] lg:pb-[36px] lg:pr-[44px] lg:pl-16 no-underline text-left bg-primary',
	'rest_current'  => 'flex-1 min-w-0 flex flex-col gap-1 lg:gap-3 pt-4 pb-4 px-3 lg:pt-[34px] lg:pb-[36px] lg:px-[44px] border-l border-base-300 no-underline text-left bg-primary',
	'last_current'  => 'flex-1 min-w-0 flex flex-col gap-1 lg:gap-3 pt-4 pb-4 px-3 lg:pt-[34px] lg:pb-[36px] lg:pl-[44px] lg:pr-16 border-l border-base-300 no-underline text-left bg-primary',
);
$lp_title_class = array(
	'default' => 'font-heading text-[16px] lg:text-[24px] font-medium tracking-[-0.6px] leading-tight text-base-content group-hover:text-neutral',
	'current' => 'font-heading text-[16px] lg:text-[24px] font-medium tracking-[-0.6px] leading-tight text-neutral',
);
$lp_meta_class = array(
	'default' => 'font-body text-[11px] font-normal tracking-[0.15px] leading-[1.55] text-base-content/65 group-hover:text-neutral',
	'current' => 'font-body text-[11px] font-normal tracking-[0.15px] leading-[1.55] text-neutral',
);
?>
<section class="<?php echo lp_classes( 'w-full bg-base-100 border-b border-base-300', $lp_spacing ); ?>" data-component="docs-faq-section-directory"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex">
		<?php foreach ( $lp_rows as $lp_i => $lp_row ) : ?>
			<?php
			$lp_href    = (string) ( $lp_row['href'] ?? '' );
			$lp_current = ! empty( $lp_row['current'] );
			$lp_last    = ( count( $lp_rows ) - 1 ) === $lp_i;
			if ( 0 === $lp_i ) {
				$lp_key = $lp_current ? 'first_current' : 'first';
			} elseif ( $lp_last ) {
				$lp_key = $lp_current ? 'last_current' : 'last';
			} else {
				$lp_key = $lp_current ? 'rest_current' : 'rest';
			}
			$lp_tone = $lp_current ? 'current' : 'default';
			$lp_tag  = '' !== $lp_href ? 'a' : 'div';
			?>
				<<?php echo $lp_tag; ?>
					class="<?php echo esc_attr( $lp_cell[ $lp_key ] ); ?>"
					<?php if ( '' !== $lp_href ) : ?>href="<?php echo esc_url( $lp_href ); ?>"<?php endif; ?>
					<?php echo $lp_current ? ' aria-current="page"' : ''; ?>
				>
					<span class="max-lg:hidden">
					<?php
					lp_part(
						'elements/glyph-label',
						array(
							'label'   => (string) ( $lp_row['index'] ?? '' ),
							'icon_id' => (string) ( $lp_row['icon'] ?? '' ),
							'surface' => $lp_current ? 'fill' : 'page',
							'tone'    => 'muted',
							'class'   => $lp_current ? '' : 'group-hover:text-neutral',
						)
					);
					?>
					</span>
					<span class="<?php echo esc_attr( $lp_title_class[ $lp_tone ] ); ?>"><?php echo esc_html( (string) ( $lp_row['title'] ?? '' ) ); ?></span>
					<span class="<?php echo esc_attr( $lp_meta_class[ $lp_tone ] ); ?>"><?php echo esc_html( (string) ( $lp_row['meta'] ?? '' ) ); ?></span>
				</<?php echo $lp_tag; ?>>
			<?php endforeach; ?>
	</div>
</section>
