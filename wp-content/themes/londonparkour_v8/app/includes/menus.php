<?php
/**
 * Nav menus → the shapes parts/site/nav.php and parts/site/footer.php take.
 *
 * Both partials were ported with their own defaults, so an unassigned menu is
 * not an error state — these helpers return an empty array and the partial
 * falls back to the Storybook's own copy. That keeps a fresh install looking
 * like the design system instead of looking broken.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * The items assigned to a menu location, as a flat list.
 *
 * `has_panel` comes from a menu item's CSS Classes field: add `has-panel` to
 * the one item that opens the Classes drop panel. It is a per-site editorial
 * choice (which section owns the mega panel), not something derivable from a
 * post type — and only the FIRST flagged item is used, so a stray class on a
 * second item cannot produce two triggers.
 *
 * @param string $location Registered nav menu location, e.g. 'primary'.
 * @return array<int, array{label:string, href:string, active:bool, has_panel:bool}>
 */
function lp_menu_links( string $location ): array {
	$items = lp_menu_items( $location );

	if ( ! $items ) {
		return array();
	}

	$links = array();

	foreach ( $items as $item ) {
		// Top level only — nav.php has one row of items.
		if ( (int) $item->menu_item_parent ) {
			continue;
		}

		$links[] = array(
			'label'     => $item->title,
			'href'      => $item->url,
			'active'    => lp_menu_item_is_current( $item ),
			'has_panel' => in_array( 'has-panel', (array) $item->classes, true ),
		);
	}

	return $links;
}

/**
 * The items assigned to a menu location, as heading + links columns.
 *
 * A top-level item becomes a column heading and its children the column's
 * links — the shape footer.php takes. A top-level item with no children is
 * skipped rather than rendered as an empty column.
 *
 * @param string $location Registered nav menu location, e.g. 'footer'.
 * @return array<int, array{heading:string, links:array}>
 */
function lp_menu_columns( string $location ): array {
	$items = lp_menu_items( $location );

	if ( ! $items ) {
		return array();
	}

	$children = array();

	foreach ( $items as $item ) {
		$parent = (int) $item->menu_item_parent;
		if ( $parent ) {
			$children[ $parent ][] = array(
				'label' => $item->title,
				'href'  => $item->url,
			);
		}
	}

	$columns = array();

	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent ) {
			continue;
		}

		$id = (int) $item->ID;

		if ( empty( $children[ $id ] ) ) {
			continue;
		}

		$columns[] = array(
			'heading' => $item->title,
			'links'   => $children[ $id ],
		);
	}

	return $columns;
}

/**
 * Menu objects for a location, or an empty array when none is assigned.
 *
 * @param string $location Registered nav menu location.
 * @return array<int, WP_Post>
 */
function lp_menu_items( string $location ): array {
	$locations = get_nav_menu_locations();
	$menu_id   = (int) ( $locations[ $location ] ?? 0 );

	if ( ! $menu_id ) {
		return array();
	}

	$items = wp_get_nav_menu_items( $menu_id );

	return is_array( $items ) ? $items : array();
}

/**
 * Whether a menu item points at what is currently being viewed.
 *
 * WordPress sets these classes on the item objects during `wp_nav_menu()`; we
 * are not calling that, so read the same signals it does — the object id
 * matching the queried object covers pages and singles, and the ancestor
 * classes cover an archive under its parent.
 *
 * @param WP_Post $item Menu item object.
 * @return bool
 */
function lp_menu_item_is_current( $item ): bool {
	$classes = (array) ( $item->classes ?? array() );

	if ( array_intersect( $classes, array( 'current-menu-item', 'current-menu-ancestor', 'current_page_item' ) ) ) {
		return true;
	}

	$queried = get_queried_object_id();

	return $queried && (int) $item->object_id === (int) $queried;
}
