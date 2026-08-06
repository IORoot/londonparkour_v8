<?php
/**
 * BreadcrumbRail — dark utility strip: breadcrumb path left, quick action right.
 *
 * Ported from src/stories/Components/BreadcrumbRail/BreadcrumbRail.js.
 *
 * A real <nav aria-label="Breadcrumb"> wrapping an <ol>. The current page
 * carries aria-current="page" and renders as plain text, not a link — colour
 * is never the only signal. Separators are a decorative " / " between real
 * <li> items rather than baked into the label, so a reader announces a clean
 * item list.
 *
 * The right-hand action is the 10px text-link variant (board_compact) with
 * whitespace-nowrap as a call-site modifier — docs/CONSOLIDATION.md §4a.
 *
 * Gutter is px-6 lg:px-16 per the Phase 7 layout contract, shared with
 * page-masthead so the two halves line up on one content edge.
 *
 * @param array  $args['crumbs']     array of array( 'label' => …, 'href' => … );
 *                                   the LAST is the current page.
 * @param array  $args['action']     array( 'label' => …, 'href' => … ). Optional.
 * @param string $args['aria_label'] Default 'Breadcrumb'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_crumbs     = is_array( $args['crumbs'] ?? null ) ? array_values( $args['crumbs'] ) : array();
$lp_action     = is_array( $args['action'] ?? null ) ? $args['action'] : array();
$lp_aria_label = (string) ( $args['aria_label'] ?? 'Breadcrumb' );
$lp_last       = count( $lp_crumbs ) - 1;
?>
<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex items-center justify-between gap-4 bg-neutral border-b border-base-300 px-6 lg:px-16 py-4" data-component="breadcrumb-rail">
	<ol class="flex items-center font-label text-[10px] font-normal uppercase tracking-[1px] text-neutral-content/80 m-0 p-0 list-none">
		<?php foreach ( $lp_crumbs as $lp_i => $lp_crumb ) : ?>
			<li class="inline-flex items-center">
				<?php if ( $lp_i > 0 ) : ?>
					<span aria-hidden="true" class="mx-2 text-neutral-content/50">/</span>
				<?php endif; ?>
				<?php if ( $lp_i === $lp_last ) : ?>
					<span aria-current="page"><?php echo esc_html( (string) ( $lp_crumb['label'] ?? '' ) ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( (string) ( $lp_crumb['href'] ?? '#' ) ); ?>" class="hover:text-primary transition-colors duration-150"><?php echo esc_html( (string) ( $lp_crumb['label'] ?? '' ) ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
	if ( ! empty( $lp_action['label'] ) ) {
		lp_part(
			'elements/text-link',
			array(
				'label'   => $lp_action['label'],
				'href'    => $lp_action['href'] ?? '#',
				'variant' => 'board_compact',
				'class'   => 'whitespace-nowrap',
			)
		);
	}
	?>
</nav>
