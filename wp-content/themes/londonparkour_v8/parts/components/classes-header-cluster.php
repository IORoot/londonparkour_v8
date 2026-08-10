<?php
/**
 * ClassesHeaderCluster — the opening of every Classes view page.
 *
 * Ported from src/stories/Pages/ClassesAgenda/ClassesHeaderCluster.js, which is
 * a shared local module in the Storybook rather than a Storybook entry. It is
 * promoted to a real shared part here because three templates open with it —
 * ClassesAgenda, ClassesListings and ClassesMap — which is the coordinated
 * promotion PORT-BRIEF rule 3a reserves for the coordinator. ClassDetail
 * deliberately does NOT use it: it has no view rail or filter grid at all, and
 * the source confirms that against its own height sum.
 *
 * The cluster owns no markup of its own beyond a wrapper. It is four
 * already-ported parts in order — breadcrumb-rail, page-masthead, view-rail,
 * filter-grid — and the value it carries is that the three tabs and the three
 * filter cells are defined once instead of three times.
 *
 * **It does not render the nav**, matching the source. That was a landmark bug
 * there and would be one here: <nav> belongs outside <main> while the
 * masthead's <h1> belongs inside it. Each template calls get_header() itself
 * and places this cluster inside its own <main>.
 *
 * Two departures from the source, both because this runs on a real site:
 *
 * 1. **The tabs are links.** The source renders ViewTab as <button role="tab">
 *    and exposes cross-page navigation as an `onTabSelect` callback, saying
 *    explicitly that it has no opinion on how a host app routes. On WordPress
 *    the answer is a URL, so view-tab.php's link form is used and view-rail
 *    drops role="tablist" — see both files' docblocks.
 * 2. **The tab metas are counted, not fixed.** `18 SESSIONS` / `13 CLASS
 *    TYPES` / `6 SITES` are static in the source. lp_classes_view_tabs() in
 *    app/includes/content.php counts them, so they cannot go stale the first
 *    time an editor adds a class.
 *
 * @param array  $args['crumbs']       breadcrumb-rail crumbs.
 * @param array  $args['action']       breadcrumb-rail's right-hand action.
 * @param array  $args['masthead']     title, note, media_id — page-masthead's args.
 * @param string $args['active']       agenda|map. Default 'agenda'.
 * @param bool   $args['show_filter']  Default true. Agenda passes false — pen
 *                                     disables Filter Grid on GdUt4 (QvQ6x).
 * @param array  $args['tabs']         Override the counted tabs entirely.
 * @param array  $args['cells']        Override the filter cells entirely.
 * @param string $args['filter_action'] Form target. Omit to render the grid inert.
 * @param array  $args['filter_values'] Current filter values, keyed by field name.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_active      = (string) ( $args['active'] ?? 'agenda' );
$lp_masthead    = is_array( $args['masthead'] ?? null ) ? $args['masthead'] : array();
$lp_tabs        = is_array( $args['tabs'] ?? null ) ? $args['tabs'] : lp_classes_view_tabs( $lp_active );
$lp_values      = is_array( $args['filter_values'] ?? null ) ? $args['filter_values'] : array();
$lp_cells       = is_array( $args['cells'] ?? null ) ? $args['cells'] : lp_class_filter_cells( $lp_values );
$lp_show_filter = ! isset( $args['show_filter'] ) || (bool) $args['show_filter'];
?>
<div data-component="classes-header-cluster">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => is_array( $args['crumbs'] ?? null ) ? $args['crumbs'] : array(),
			'action' => is_array( $args['action'] ?? null ) ? $args['action'] : array(),
		)
	);

	lp_part( 'components/page-masthead', $lp_masthead );

	lp_part(
		'components/view-rail',
		array(
			'context'    => 'classes',
			'tabs'       => $lp_tabs,
			'aria_label' => 'Classes views',
		)
	);

	if ( $lp_show_filter ) {
		lp_part(
			'components/filter-grid',
			array(
				'cells'  => $lp_cells,
				'action' => (string) ( $args['filter_action'] ?? '' ),
				'submit' => 'Apply class filters',
			)
		);
	}
	?>
</div>
