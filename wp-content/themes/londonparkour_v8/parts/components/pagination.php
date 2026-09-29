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
 * @param array  $args['pages']      array of array( 'label' => …, 'href' => …, 'current' => bool ). No href = text, not a dead link.
 * @param array  $args['prev']       array( 'label' => …, 'href' => … ). Empty to omit; label without href = disabled non-link.
 * @param array  $args['next']       Same shape.
 * @param string $args['count']      The count line, pre-formatted by the caller.
 * @param string $args['aria_label'] Default 'Pagination'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings per state. Tailwind v4 scans source text. */
$lp_focus = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-content';
$lp_edge  = 'font-label text-[10px] font-semibold uppercase tracking-[1px] inline-flex items-center min-h-11 transition-colors duration-150 motion-reduce:transition-none';
// Visual box stays 34px; the ::after grows the touch target to ~46px.
$lp_box   = "relative w-[34px] h-[34px] inline-flex items-center justify-center font-label text-[11px] font-semibold tracking-[0.6px] transition-colors duration-150 motion-reduce:transition-none after:absolute after:-inset-[6px] after:content-['']";
$lp_boxes = array(
	'current' => 'bg-primary-content text-primary',
	'other'   => 'bg-transparent text-primary-content hover:bg-primary-content/10',
);
$lp_ends  = array(
	'prev' => 'text-primary-content/70 hover:text-primary-content',
	'next' => 'text-primary-content hover:text-primary-content/70',
);

$lp_pages      = is_array( $args['pages'] ?? null ) ? $args['pages'] : array();
$lp_prev       = is_array( $args['prev'] ?? null ) ? $args['prev'] : array();
$lp_next       = is_array( $args['next'] ?? null ) ? $args['next'] : array();
$lp_count      = (string) ( $args['count'] ?? '' );
$lp_aria_label = (string) ( $args['aria_label'] ?? 'Pagination' );

if ( ! $lp_pages ) {
	return;
}

/** Wrap a leading ← / trailing → in aria-hidden so it is not read aloud. */
$lp_arrow = static function ( $lp_label ) {
	$lp_html = esc_html( $lp_label );
	$lp_html = preg_replace( '/^(←)/u', '<span aria-hidden="true">$1</span>', $lp_html );
	return preg_replace( '/(→)$/u', '<span aria-hidden="true">$1</span>', $lp_html );
};

/** Prev/next: empty → spacer; no href → disabled non-link; else a link. */
$lp_edge_render = static function ( $lp_side, $lp_tone ) use ( $lp_arrow, $lp_edge, $lp_focus ) {
	if ( empty( $lp_side['label'] ) ) {
		echo '<span></span>';
		return;
	}
	if ( empty( $lp_side['href'] ) ) {
		echo '<span aria-disabled="true" class="' . esc_attr( $lp_edge . ' opacity-50' ) . '">' . $lp_arrow( (string) $lp_side['label'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html'd inside $lp_arrow.
		return;
	}
	echo '<a href="' . esc_url( (string) $lp_side['href'] ) . '" class="' . esc_attr( $lp_edge . ' ' . $lp_focus . ' ' . $lp_tone ) . '">' . $lp_arrow( (string) $lp_side['label'] ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html'd inside $lp_arrow.
};
?>
<div class="w-full bg-primary" data-component="pagination">
	<div class="px-6 lg:px-16 pt-[58px] pb-[62px]">
		<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex items-center justify-between gap-4 pt-[26px] border-t border-primary-content/25">
			<?php $lp_edge_render( $lp_prev, $lp_ends['prev'] ); ?>

			<div class="flex items-center gap-[6px]">
				<?php foreach ( $lp_pages as $lp_page ) : ?>
					<?php
					$lp_is_current = ! empty( $lp_page['current'] );
					$lp_label      = (string) ( $lp_page['label'] ?? '' );
					$lp_href       = (string) ( $lp_page['href'] ?? '' );
					if ( $lp_is_current ) :
						?>
						<span aria-current="page" aria-label="<?php echo esc_attr( 'Page ' . $lp_label ); ?>" class="<?php echo lp_classes( $lp_box, $lp_boxes['current'] ); ?>"><?php echo esc_html( $lp_label ); ?></span>
					<?php elseif ( '' === $lp_href ) : ?>
						<span class="<?php echo lp_classes( $lp_box, $lp_boxes['other'] ); ?>"><?php echo esc_html( $lp_label ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $lp_href ); ?>" aria-label="<?php echo esc_attr( 'Page ' . $lp_label ); ?>" class="<?php echo lp_classes( $lp_box, $lp_focus, $lp_boxes['other'] ); ?>"><?php echo esc_html( $lp_label ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php $lp_edge_render( $lp_next, $lp_ends['next'] ); ?>
		</nav>
		<?php if ( '' !== $lp_count ) : ?>
			<p class="mt-5 text-center font-label text-[10px] font-semibold uppercase tracking-[1px] text-primary-content/70 m-0"><?php echo esc_html( $lp_count ); ?></p>
		<?php endif; ?>
	</div>
</div>
