<?php
/**
 * Pagination — the yellow band: previous, page boxes, next, and a count line.
 *
 * Ported from src/stories/Search/SearchResults/SearchResults.js `initPagination`
 * (`l6bk8` wrapping `bWhir`). Promoted to a shared part rather than left inline
 * in search.php because three templates need it — search.php plus the archive
 * body that archive.php and index.php both include. It is the design system's
 * ONLY pagination shape; there is no second one to reconcile it with.
 *
 * `data-component` is `pagination`, not the source's `search-pagination`: the
 * markup is no longer search-specific. That is the one departure from the
 * source's DOM.
 *
 * Ground is `$play-yellow` → bg-primary with the primary-content family, per
 * the Storybook's docs/phase7/surface-axis.md (not re-derived). The source's
 * `#141310A8` muted text solves to primary-content at ~66% →
 * text-primary-content/70, the matrix's muted role on a fill.
 *
 * The design draws previous, boxes and next as three justify-between children
 * and gives previous/next no disabled state. On page one there is no previous
 * page, so that slot renders as an empty <span>: the row keeps its alignment
 * and no undesigned control gets invented.
 *
 * Callers build their args with lp_pagination_args() in app/includes/content.php
 * rather than assembling this by hand.
 *
 * @param array  $args['pages']      array of array( 'label' => …, 'href' => …, 'current' => bool ).
 * @param array  $args['prev']       array( 'label' => …, 'href' => … ). Empty to omit.
 * @param array  $args['next']       Same shape.
 * @param string $args['count']      The count line, pre-formatted by the caller.
 * @param string $args['aria_label'] Default 'Pagination'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings per state. Tailwind v4 scans source text. */
$lp_boxes = array(
	'current' => 'bg-primary-content text-primary',
	'other'   => 'bg-transparent text-primary-content hover:bg-primary-content/10',
);
$lp_ends  = array(
	'prev' => 'font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary-content/70 hover:text-primary-content transition-colors duration-150',
	'next' => 'font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary-content hover:text-primary-content/70 transition-colors duration-150',
);

$lp_pages      = is_array( $args['pages'] ?? null ) ? $args['pages'] : array();
$lp_prev       = is_array( $args['prev'] ?? null ) ? $args['prev'] : array();
$lp_next       = is_array( $args['next'] ?? null ) ? $args['next'] : array();
$lp_count      = (string) ( $args['count'] ?? '' );
$lp_aria_label = (string) ( $args['aria_label'] ?? 'Pagination' );

if ( ! $lp_pages ) {
	return;
}
?>
<div class="w-full bg-primary" data-component="pagination">
	<div class="px-6 lg:px-16 pt-[58px] pb-[62px]">
		<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex items-center justify-between gap-4 pt-[26px] border-t border-primary-content/25">
			<?php if ( ! empty( $lp_prev['label'] ) ) : ?>
				<a href="<?php echo esc_url( (string) ( $lp_prev['href'] ?? '#' ) ); ?>" class="<?php echo esc_attr( $lp_ends['prev'] ); ?>"><?php echo esc_html( (string) $lp_prev['label'] ); ?></a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

			<div class="flex items-center gap-[6px]">
				<?php foreach ( $lp_pages as $lp_page ) : ?>
					<?php $lp_is_current = ! empty( $lp_page['current'] ); ?>
					<a href="<?php echo esc_url( (string) ( $lp_page['href'] ?? '#' ) ); ?>"
						<?php echo $lp_is_current ? 'aria-current="page"' : ''; ?>
						class="<?php echo lp_classes( 'w-[34px] h-[34px] inline-flex items-center justify-center font-label text-[11px] font-semibold tracking-[0.6px]', $lp_boxes[ $lp_is_current ? 'current' : 'other' ], 'transition-colors duration-150' ); ?>"><?php echo esc_html( (string) ( $lp_page['label'] ?? '' ) ); ?></a>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $lp_next['label'] ) ) : ?>
				<a href="<?php echo esc_url( (string) ( $lp_next['href'] ?? '#' ) ); ?>" class="<?php echo esc_attr( $lp_ends['next'] ); ?>"><?php echo esc_html( (string) $lp_next['label'] ); ?></a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
		</nav>
		<?php if ( '' !== $lp_count ) : ?>
			<p class="mt-5 text-center font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary-content/70 m-0"><?php echo esc_html( $lp_count ); ?></p>
		<?php endif; ?>
	</div>
</div>
