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
 * Sprite id for a primary-nav label.
 *
 * @param string $label Menu item title.
 * @return string glyph-* id, or empty when the label is not one of the four.
 */
function lp_nav_icon_id_from_label( string $label ): string {
	$map = array(
		'classes'   => 'glyph-vaulting',
		'tutorials' => 'glyph-jumping',
		'docs'      => 'glyph-rolling',
		'contact'   => 'glyph-plyometrics',
	);

	return $map[ strtolower( $label ) ] ?? '';
}

/**
 * Movement glyph for a drop-panel row, from its name then family meta.
 *
 * @param string $name  Row label.
 * @param string $meta  Optional family / location meta.
 * @param int    $index Fallback cycle index.
 * @return string glyph-* id.
 */
function lp_nav_row_glyph( string $name, string $meta = '', int $index = 0 ): string {
	$key = strtolower( $name );
	$map = array(
		'by series'   => 'glyph-chaining',
		'by category' => 'glyph-understanding',
		'by tutorial' => 'glyph-jumping',
		'wiki'        => 'glyph-rolling',
		'blog'        => 'glyph-spirit',
		'class map'   => 'glyph-traverse',
		'agenda'      => 'glyph-flowing',
		'kids'        => 'glyph-jumping',
		'teen'        => 'glyph-climbing',
		'youth'       => 'glyph-climbing',
		'women'       => 'glyph-spirit',
		'family'      => 'glyph-teamwork',
		'beginner'    => 'glyph-step',
		'advanced'    => 'glyph-chaining',
		'sunrise'     => 'glyph-ascent',
		'open gym'    => 'glyph-strengthening',
		'evening'     => 'glyph-flowing',
		'private'     => 'glyph-holistic',
		'map'         => 'glyph-traverse',
		'north'       => 'glyph-wall-run',
		'southbank'   => 'glyph-passing',
		'outdoor'     => 'glyph-vaulting',
	);

	foreach ( $map as $needle => $glyph ) {
		if ( str_contains( $key, $needle ) ) {
			return $glyph;
		}
	}

	$family = lp_nav_family_glyph( $meta );
	if ( '' !== $family ) {
		return $family;
	}

	$cycle = array(
		'glyph-vaulting',
		'glyph-jumping',
		'glyph-climbing',
		'glyph-rolling',
		'glyph-balancing',
		'glyph-flowing',
		'glyph-step',
		'glyph-strengthening',
	);

	return $cycle[ $index % count( $cycle ) ];
}

/**
 * Glyph for a tutorial movement family name.
 *
 * @param string $meta Family label, e.g. VAULTING.
 * @return string glyph-* id, or empty.
 */
function lp_nav_family_glyph( string $meta ): string {
	$map = array(
		'vaulting'      => 'glyph-vaulting',
		'climbing'      => 'glyph-climbing',
		'jumping'       => 'glyph-jumping',
		'rolling'       => 'glyph-rolling',
		'balancing'     => 'glyph-balancing',
		'swinging'      => 'glyph-swinging',
		'crawling'      => 'glyph-crawling',
		'passing'       => 'glyph-passing',
		'spinning'      => 'glyph-spinning',
		'strengthening' => 'glyph-strengthening',
		'flowing'       => 'glyph-flowing',
	);

	return $map[ strtolower( $meta ) ] ?? '';
}

/**
 * Stamp glyph_id onto each panel row.
 *
 * @param array<int, array<string, mixed>> $rows Rows with name/meta.
 * @return array<int, array<string, mixed>>
 */
function lp_nav_with_glyphs( array $rows ): array {
	$out = array();
	foreach ( array_values( $rows ) as $i => $row ) {
		$glyph = (string) ( $row['glyph_id'] ?? '' );
		if ( '' === $glyph ) {
			$glyph = lp_nav_row_glyph(
				(string) ( $row['name'] ?? '' ),
				(string) ( $row['meta'] ?? '' ),
				$i
			);
		}
		$row['glyph_id'] = $glyph;
		$out[]           = $row;
	}
	return $out;
}

/**
 * Drop-panel key for a primary-nav item.
 *
 * CSS classes win (`has-panel`, `has-panel-tutorials`, `has-panel-docs`) so
 * an editor can re-assign a panel. Labels Classes / Tutorials / Docs then
 * auto-detect, matching the V7 nav even when the seeded classes field is stale.
 * Contact has no panel.
 *
 * @param string   $label   Menu item title.
 * @param string[] $classes Menu item CSS classes.
 * @return string classes|tutorials|docs, or empty.
 */
function lp_nav_panel_from_item( string $label, array $classes ): string {
	if ( in_array( 'has-panel-tutorials', $classes, true ) ) {
		return 'tutorials';
	}
	if ( in_array( 'has-panel-docs', $classes, true ) ) {
		return 'docs';
	}
	if ( in_array( 'has-panel', $classes, true ) ) {
		return 'classes';
	}

	$map = array(
		'classes'   => 'classes',
		'tutorials' => 'tutorials',
		'docs'      => 'docs',
	);

	return $map[ strtolower( $label ) ] ?? '';
}

/**
 * The items assigned to a menu location, as a flat list.
 *
 * `panel` comes from a menu item's CSS Classes field (`has-panel`,
 * `has-panel-tutorials`, `has-panel-docs`) or, if none is set, from the
 * item's title (Classes / Tutorials / Docs). Contact has no panel.
 *
 * @param string $location Registered nav menu location, e.g. 'primary'.
 * @return array<int, array{label:string, href:string, active:bool, panel:string, icon_id:string, has_panel:bool}>
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

		$label = (string) $item->title;
		$panel = lp_nav_panel_from_item( $label, (array) $item->classes );

		$links[] = array(
			'label'     => $label,
			'href'      => $item->url,
			'active'    => lp_menu_item_is_current( $item ),
			'panel'     => $panel,
			'icon_id'   => lp_nav_icon_id_from_label( $label ),
			'has_panel' => '' !== $panel,
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

/**
 * Default primary-nav links — Storybook copy with WordPress routes.
 *
 * Classes goes to the agenda (`/classes/`). Tutorials goes to the series
 * listing (`/tutorials/series/`). Find a class (the CTA) goes to the agenda.
 * Drop-panel feet still point at the listings archives.
 *
 * @return array<int, array{label:string, href:string, active:bool, panel:string, icon_id:string}>
 */
function lp_nav_default_links(): array {
	$contact = get_page_by_path( 'contact' );

	return array(
		array(
			'label'   => 'Classes',
			'href'    => function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' ),
			'active'  => false,
			'panel'   => 'classes',
			'icon_id' => 'glyph-vaulting',
		),
		array(
			'label'   => 'Tutorials',
			'href'    => function_exists( 'lp_tutorials_series_url' ) ? lp_tutorials_series_url() : home_url( '/tutorials/series/' ),
			'active'  => false,
			'panel'   => 'tutorials',
			'icon_id' => 'glyph-jumping',
		),
		array(
			'label'   => 'Docs',
			'href'    => function_exists( 'lp_docs_url' ) ? lp_docs_url() : home_url( '/docs/' ),
			'active'  => false,
			'panel'   => 'docs',
			'icon_id' => 'glyph-rolling',
		),
		array(
			'label'   => 'Contact',
			'href'    => $contact ? (string) get_permalink( $contact ) : home_url( '/contact/' ),
			'active'  => false,
			'panel'   => '',
			'icon_id' => 'glyph-plyometrics',
		),
	);
}

/**
 * The three drop panels, filled from live CPTs when they exist.
 *
 * @return array{classes:array, tutorials:array, docs:array}
 */
function lp_nav_drop_panels(): array {
	return array(
		'classes'   => lp_nav_classes_panel(),
		'tutorials' => lp_nav_tutorials_panel(),
		'docs'      => lp_nav_docs_panel(),
	);
}

/**
 * True when a class type belongs in FIND as Private 1:1, not ALL CLASSES.
 *
 * @param string $name Class title.
 * @return bool
 */
function lp_nav_is_private_class( string $name ): bool {
	return str_contains( strtolower( $name ), 'private' );
}

/**
 * Classes drop panel — Agenda / Map / Private 1:1, then every class type.
 *
 * @return array{columns:array, all_label:string, all_href:string, alt_label:string, alt_href:string}
 */
function lp_nav_classes_panel(): array {
	$listings = function_exists( 'lp_classes_listings_url' ) ? lp_classes_listings_url() : home_url( '/all-classes/' );
	$map      = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes-map' ) : home_url( '/classes-map/' );
	$agenda   = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' );
	$sites    = function_exists( 'lp_locations_by_kind' ) ? count( lp_locations_by_kind( 'site' ) ) : 6;
	$private  = home_url( '/#private-coaching' );
	foreach ( array( 'private-coaching', 'private-tuition' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			$private = (string) get_permalink( $page );
			break;
		}
	}

	$agenda_meta = '18 SESSIONS';
	$map_meta    = sprintf( '%d SITES', $sites ?: 6 );
	if ( function_exists( 'lp_classes_view_tabs' ) ) {
		$tabs = lp_classes_view_tabs();
		if ( isset( $tabs[0]['meta'], $tabs[0]['href'] ) ) {
			$agenda_meta = (string) $tabs[0]['meta'];
			$agenda      = (string) $tabs[0]['href'];
		}
		if ( isset( $tabs[1]['meta'], $tabs[1]['href'] ) ) {
			$map_meta = (string) $tabs[1]['meta'];
			$map      = (string) $tabs[1]['href'];
		}
	}

	$find = lp_nav_with_glyphs(
		array(
			array(
				'name' => 'Agenda',
				'meta' => $agenda_meta,
				'href' => $agenda,
			),
			array(
				'name' => 'Map',
				'meta' => $map_meta,
				'href' => $map,
			),
			array(
				'name' => 'Private 1:1',
				'meta' => 'ANY SITE',
				'href' => $private,
			),
		)
	);

	$rows = array();

	if ( function_exists( 'lp_class_post_type' ) && function_exists( 'lp_class_active_meta_query' ) ) {
		$posts = get_posts(
			lp_class_active_meta_query(
				array(
					'post_type'        => lp_class_post_type(),
					'post_status'      => 'publish',
					'posts_per_page'   => 50,
					'orderby'          => 'title',
					'order'            => 'ASC',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				)
			)
		);
		if ( function_exists( 'lp_class_dedupe_by_title' ) ) {
			$posts = lp_class_dedupe_by_title( $posts );
		}
		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$title = get_the_title( $post );
			if ( lp_nav_is_private_class( $title ) ) {
				continue;
			}
			$location = function_exists( 'lp_class_location_id' ) ? lp_class_location_id( (int) $post->ID ) : 0;
			$rows[]   = array(
				'name' => $title,
				'meta' => $location ? strtoupper( get_the_title( $location ) ) : '',
				'href' => (string) get_permalink( $post ),
			);
		}
	}

	if ( ! $rows ) {
		$rows = array(
			array(
				'name' => 'Beginners Parkour',
				'meta' => 'VAUXHALL',
				'href' => home_url( '/classes/beginners-parkour/' ),
			),
			array(
				'name' => 'Outdoor Class — Vauxhall',
				'meta' => 'VAUXHALL',
				'href' => home_url( '/classes/outdoor-class-vauxhall/' ),
			),
			array(
				'name' => 'Evening Outdoor Class',
				'meta' => 'SOUTHBANK',
				'href' => home_url( '/classes/evening-outdoor-class/' ),
			),
			array(
				'name' => 'Outdoor Class — Southbank',
				'meta' => 'SOUTHBANK',
				'href' => home_url( '/classes/outdoor-class-southbank/' ),
			),
			array(
				'name' => 'Outdoor Class North',
				'meta' => 'WEMBLEY PARK',
				'href' => home_url( '/classes/outdoor-class-north/' ),
			),
			array(
				'name' => 'Kids Class West (6–9s)',
				'meta' => 'VAUXHALL',
				'href' => home_url( '/classes/kids-class-west-6-9s/' ),
			),
			array(
				'name' => 'Teens Class West (10–14s)',
				'meta' => 'VAUXHALL',
				'href' => home_url( '/classes/teens-class-west-10-14s/' ),
			),
			array(
				'name' => 'Sunrise Session',
				'meta' => 'PECKHAM RYE',
				'href' => home_url( '/classes/sunrise-session/' ),
			),
			array(
				'name' => 'Kids Parkour 5–11',
				'meta' => 'HACKNEY MARSHES',
				'href' => home_url( '/classes/kids-parkour-5-11/' ),
			),
			array(
				'name' => 'Open Gym',
				'meta' => 'STRATFORD EAST',
				'href' => home_url( '/classes/open-gym/' ),
			),
			array(
				'name' => "Women's Session",
				'meta' => 'SOUTHBANK',
				'href' => home_url( '/classes/womens-session/' ),
			),
			array(
				'name' => 'Advanced Movement',
				'meta' => 'VAUXHALL',
				'href' => home_url( '/classes/advanced-movement/' ),
			),
			array(
				'name' => 'Family Session',
				'meta' => 'WEMBLEY PARK',
				'href' => home_url( '/classes/family-session/' ),
			),
		);
	}

	$rows = lp_nav_with_glyphs( $rows );

	$columns = array(
		array(
			'title' => 'FIND',
			'note'  => '3',
			'rows'  => $find,
		),
		array(
			'title' => 'ALL CLASSES',
			'note'  => sprintf( '%02d–%02d', 1, count( $rows ) ),
			'rows'  => $rows,
		),
	);

	return array(
		'columns'   => $columns,
		'all_label' => 'ALL CLASSES →',
		'all_href'  => $listings,
		'alt_label' => 'OPEN THE MAP ↗',
		'alt_href'  => $map,
	);
}

/**
 * Parent tutorial-category name, uppercased — the panel's meta slot.
 *
 * @param WP_Post $post Tutorial.
 * @return string
 */
function lp_nav_tutorial_family( WP_Post $post ): string {
	$terms = get_the_terms( $post, 'tutorial-category' );
	if ( ! is_array( $terms ) ) {
		return '';
	}
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term && ! $term->parent ) {
			return strtoupper( $term->name );
		}
	}
	return $terms && $terms[0] instanceof WP_Term ? strtoupper( $terms[0]->name ) : '';
}

/**
 * Tutorials drop panel — browse, newest three series, newest three tutorials.
 *
 * @return array{columns:array, all_label:string, all_href:string, alt_label:string, alt_href:string}
 */
function lp_nav_tutorials_panel(): array {
	$archive = (string) ( get_post_type_archive_link( 'lp_tutorial' ) ?: home_url( '/tutorials/' ) );
	$series  = function_exists( 'lp_tutorials_series_url' ) ? lp_tutorials_series_url() : home_url( '/tutorials/series/' );
	$category = function_exists( 'lp_tutorials_category_url' ) ? lp_tutorials_category_url() : home_url( '/tutorials/category/' );

	$series_count   = function_exists( 'lp_series_terms_nonempty' ) ? count( lp_series_terms_nonempty() ) : 12;
	$category_count = function_exists( 'lp_tutorials_category_count' ) ? lp_tutorials_category_count() : 11;
	$tutorial_count = function_exists( 'lp_tutorials_published_count' ) ? lp_tutorials_published_count() : 840;

	$newest = get_posts(
		array(
			'post_type'        => 'lp_tutorial',
			'post_status'      => 'publish',
			'posts_per_page'   => 3,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);

	$newest_rows = array();
	foreach ( $newest as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$family = lp_nav_tutorial_family( $post );
		$glyph  = '';
		if ( function_exists( 'lp_tutorial_category_glyph' ) ) {
			$terms  = get_the_terms( $post, 'tutorial-category' );
			$terms  = is_array( $terms ) ? $terms : array();
			$child  = null;
			$parent = null;
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term && $term->parent ) {
					$child = $term;
					break;
				}
			}
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term && ! $term->parent ) {
					$parent = $term;
					break;
				}
			}
			$glyph = lp_tutorial_category_glyph( $child ?: $parent );
		}
		$newest_rows[] = array(
			'name'     => get_the_title( $post ),
			'meta'     => $family,
			'href'     => (string) get_permalink( $post ),
			'glyph_id' => $glyph,
		);
	}

	if ( ! $newest_rows ) {
		$newest_rows = array(
			array(
				'name' => 'Slow and High',
				'meta' => 'VAULTING',
				'href' => home_url( '/tutorials/slow-and-high/' ),
			),
			array(
				'name' => 'How to Cat Leap',
				'meta' => 'CLIMBING',
				'href' => home_url( '/tutorials/how-to-cat-leap/' ),
			),
			array(
				'name' => 'Two Hands, One-foot.',
				'meta' => 'VAULTING',
				'href' => home_url( '/tutorials/two-hands-one-foot/' ),
			),
		);
	}

	$series_rows = array();
	$terms       = function_exists( 'lp_series_terms_nonempty' ) ? lp_series_terms_nonempty() : array();
	if ( $terms ) {
		usort(
			$terms,
			static function ( $a, $b ) {
				$id_a = $a instanceof WP_Term ? (int) $a->term_id : 0;
				$id_b = $b instanceof WP_Term ? (int) $b->term_id : 0;
				return $id_b <=> $id_a;
			}
		);
		foreach ( array_slice( $terms, 0, 3 ) as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$link  = get_term_link( $term );
			$count = function_exists( 'lp_series_published_count' ) ? lp_series_published_count( (int) $term->term_id ) : 0;
			$series_rows[] = array(
				'name' => $term->name,
				'meta' => sprintf( '%d EPISODES', $count ?: 3 ),
				'href' => is_wp_error( $link ) ? $series : (string) $link,
			);
		}
	}

	if ( ! $series_rows ) {
		$series_rows = array(
			array(
				'name' => 'Flow Combinations',
				'meta' => '9 EPISODES',
				'href' => home_url( '/tutorials/flow/' ),
			),
			array(
				'name' => 'Strength Conditioning',
				'meta' => '6 EPISODES',
				'href' => home_url( '/tutorials/strength/' ),
			),
			array(
				'name' => 'Kids Curriculum',
				'meta' => '3 EPISODES',
				'href' => home_url( '/tutorials/kids/' ),
			),
		);
	}

	return array(
		'columns'   => array(
			array(
				'title' => 'BROWSE',
				'note'  => '3',
				'rows'  => lp_nav_with_glyphs(
					array(
						array(
							'name' => 'By series',
							'meta' => sprintf( '%d SERIES', $series_count ?: 12 ),
							'href' => $series,
						),
						array(
							'name' => 'By category',
							'meta' => sprintf( '%d CATEGORIES', $category_count ?: 11 ),
							'href' => $category,
						),
						array(
							'name' => 'By tutorial',
							'meta' => sprintf( '%d VIDEOS', $tutorial_count ?: 840 ),
							'href' => $archive,
						),
					)
				),
			),
			array(
				'title' => 'NEWEST SERIES',
				'note'  => (string) count( $series_rows ),
				'rows'  => lp_nav_with_glyphs( $series_rows ),
			),
			array(
				'title' => 'NEWEST TUTORIALS',
				'note'  => (string) count( $newest_rows ),
				'rows'  => lp_nav_with_glyphs( $newest_rows ),
			),
		),
		'all_label' => 'ALL TUTORIALS →',
		'all_href'  => $archive,
		'alt_label' => 'ALL SERIES ↗',
		'alt_href'  => $series,
	);
}

/**
 * Docs drop panel — wiki and blog as two columns.
 *
 * @return array{columns:array, all_label:string, all_href:string, alt_label:string, alt_href:string}
 */
function lp_nav_docs_panel(): array {
	$wiki  = function_exists( 'lp_docs_url' ) ? lp_docs_url() : home_url( '/docs/' );
	$blog  = function_exists( 'lp_docs_blog_url' ) ? lp_docs_blog_url() : home_url( '/blog/' );
	$pages = function_exists( 'lp_docs_support_count' ) ? lp_docs_support_count() : 15;
	$stories = function_exists( 'lp_docs_story_count' ) ? lp_docs_story_count() : 12;

	return array(
		'columns'   => array(
			array(
				'title' => 'WIKI',
				'note'  => sprintf( '%d PAGES', $pages ?: 15 ),
				'rows'  => lp_nav_with_glyphs(
					array(
						array(
							'name' => 'Wiki',
							'meta' => sprintf( '%d PAGES', $pages ?: 15 ),
							'href' => $wiki,
						),
					)
				),
			),
			array(
				'title' => 'BLOG',
				'note'  => sprintf( '%d STORIES', $stories ?: 12 ),
				'rows'  => lp_nav_with_glyphs(
					array(
						array(
							'name' => 'Blog',
							'meta' => sprintf( '%d STORIES', $stories ?: 12 ),
							'href' => $blog,
						),
					)
				),
			),
		),
		'all_label' => 'WIKI →',
		'all_href'  => $wiki,
		'alt_label' => 'BLOG ↗',
		'alt_href'  => $blog,
	);
}
