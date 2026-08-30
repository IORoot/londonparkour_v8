<?php
/**
 * SearchResultRow — index, category badge, title + snippet, meta, chevron.
 *
 * Ported from src/stories/Components/SearchResultRow/SearchResultRow.js.
 *
 * The category badge is elements/badge.php (variant `category`); the trailing
 * chevron is elements/chevron.php variant `search_result_row` — the one
 * documented 13px outlier in that atom (docs/CONSOLIDATION.md §2b).
 *
 * @param string $args['index']    Default '01'.
 * @param string $args['category'] Badge label.
 * @param string $args['title']
 * @param string $args['snippet']
 * @param string $args['meta']
 * @param string $args['href']     Renders the row as one focusable <a>.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_root = 'group flex items-center gap-6 py-[22px] border-b border-base-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-accent';

$lp_index    = (string) ( $args['index'] ?? '01' );
$lp_category = (string) ( $args['category'] ?? 'CLASS' );
$lp_title    = (string) ( $args['title'] ?? 'Beginners Parkour — Vauxhall' );
$lp_snippet  = (string) ( $args['snippet'] ?? 'Our entry-level session. Sixty minutes, capped at twelve. No experience needed.' );
$lp_meta     = (string) ( $args['meta'] ?? 'CLASSES · £15' );
$lp_href     = (string) ( $args['href'] ?? '' );
$lp_is_link  = '' !== $lp_href;
?>
<?php if ( $lp_is_link ) : ?>
<a href="<?php echo esc_url( $lp_href ); ?>" class="<?php echo esc_attr( $lp_root ); ?>" data-component="search-result-row">
<?php else : ?>
<div class="<?php echo esc_attr( $lp_root ); ?>" data-component="search-result-row">
<?php endif; ?>
	<span class="w-[28px] shrink-0 font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_index ); ?></span>
	<span class="hidden sm:flex w-[112px] shrink-0">
		<?php
		lp_part(
			'elements/badge',
			array(
				'variant' => 'category',
				'label'   => $lp_category,
			)
		);
		?>
	</span>
	<span class="flex-1 min-w-0 flex flex-col gap-[6px]">
		<span class="font-heading text-[19px] font-medium tracking-[-0.3px] text-base-content group-hover:text-accent transition-colors duration-150"><?php echo esc_html( $lp_title ); ?></span>
		<span class="font-body text-[12px] font-normal tracking-[0.15px] leading-[1.55] text-base-content/65"><?php echo esc_html( $lp_snippet ); ?></span>
	</span>
	<span class="hidden md:block w-[190px] shrink-0 text-right font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_meta ); ?></span>
	<?php lp_part( 'elements/chevron', array( 'variant' => 'search_result_row' ) ); ?>
<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
