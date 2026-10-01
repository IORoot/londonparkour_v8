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
<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="flex items-center justify-between gap-4 flex-wrap bg-neutral border-b border-neutral-content/20 px-6 lg:px-16 py-4" data-component="breadcrumb-rail">
	<ol role="list" class="flex flex-wrap items-center font-label text-fix--2 font-normal uppercase tracking-[1px] text-neutral-content/80 m-0 p-0 list-none min-w-0">
		<?php foreach ( $lp_crumbs as $lp_i => $lp_crumb ) : ?>
		<li class="<?php echo $lp_i === $lp_last ? 'inline-flex items-center min-w-0' : 'inline-flex items-center shrink-0'; ?>">
			<?php if ( $lp_i > 0 ) : ?>
				<span aria-hidden="true" class="mx-2 text-neutral-content/50">/</span>
			<?php endif; ?>
			<?php if ( $lp_i === $lp_last ) : ?>
				<span aria-current="page"><?php echo esc_html( (string) ( $lp_crumb['label'] ?? '' ) ); ?></span>
			<?php elseif ( ! empty( $lp_crumb['href'] ) ) : ?>
				<a href="<?php echo esc_url( (string) $lp_crumb['href'] ); ?>" class="py-2 hover:text-primary transition-colors duration-150 shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"><?php echo esc_html( (string) ( $lp_crumb['label'] ?? '' ) ); ?></a>
			<?php else : ?>
				<span class="shrink-0"><?php echo esc_html( (string) ( $lp_crumb['label'] ?? '' ) ); ?></span>
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
				'class'   => 'py-2 whitespace-nowrap focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
			)
		);
	}
	?>
</nav>
