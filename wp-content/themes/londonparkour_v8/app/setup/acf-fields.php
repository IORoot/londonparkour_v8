<?php
/**
 * The shared ACF field vocabulary.
 *
 * Every block's fields.php is built from these helpers, so an editor sees the
 * same labels, in the same order, in every block. Changing a label site-wide is
 * a one-line edit here.
 *
 * Helpers deliberately return fields WITHOUT copy defaults. A block's default
 * copy lives in ONE place — the `$args['x'] ?? '…'` fallback in its partial,
 * taken from the source component's own `init()` defaults. Repeating it as an
 * ACF `default_value` gives the same string two homes that drift apart, and it
 * pre-fills every new block with copy an editor then has to delete. Control
 * defaults are different and DO belong here: a `button_group`, `select` or
 * `number` with no default renders as an unset control, so `source`,
 * `source_limit`, `spacing_*` and Marquee's direction/speed keep theirs.
 *
 * Helpers deliberately return fields WITHOUT keys. ACF requires globally unique
 * keys, and these helpers are reused across a dozen layouts — so keys are
 * assigned deterministically from each field's path by lp_acf_assign_keys()
 * when `wp lp acf:build` runs. Cross-field references use 'lp_conditional',
 * which names a SIBLING field and is resolved to a real key at the same time.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Structure
 * ---------------------------------------------------------------------- */

/**
 * A tab. Every block uses the same four, in this order, omitting any it has no
 * fields for: Content, Items, Actions, Settings.
 *
 * @param string $label Tab label.
 * @return array
 */
function lp_tab( string $label ): array {
	return array(
		'name'        => 'tab_' . sanitize_key( $label ),
		'label'       => $label,
		'type'        => 'tab',
		'placement'   => 'top',
		'endpoint'    => 0,
	);
}

/* -------------------------------------------------------------------------
 * Content fields — one definition each, used everywhere
 * ---------------------------------------------------------------------- */

/**
 * Small uppercase label above a heading. The Storybook calls this `eyebrow` in
 * most blocks and `kicker` in CTA; here it is always Eyebrow.
 *
 * @param array $overrides Field overrides, e.g. array( 'name' => 'kicker' ).
 * @return array
 */
function lp_field_eyebrow( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'         => 'eyebrow',
			'label'        => __( 'Eyebrow', 'londonparkour_v8' ),
			'type'         => 'text',
			'instructions' => __( 'Short uppercase label above the heading.', 'londonparkour_v8' ),
		),
		$overrides
	);
}

/**
 * The section heading. `headline` / `title` in the Storybook.
 *
 * @param array $overrides Field overrides.
 * @return array
 */
function lp_field_heading( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'  => 'heading',
			'label' => __( 'Heading', 'londonparkour_v8' ),
			'type'  => 'text',
		),
		$overrides
	);
}

/**
 * Intro sentence under the heading. `lead` / `subhead` / `introText`.
 *
 * @param array $overrides Field overrides.
 * @return array
 */
function lp_field_standfirst( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'      => 'standfirst',
			'label'     => __( 'Standfirst', 'londonparkour_v8' ),
			'type'      => 'textarea',
			'rows'      => 3,
			'new_lines' => '',
		),
		$overrides
	);
}

/**
 * Rich body copy. Uses the same prose classes as the front end.
 *
 * @param array $overrides Field overrides.
 * @return array
 */
function lp_field_body( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'         => 'body',
			'label'        => __( 'Body', 'londonparkour_v8' ),
			'type'         => 'wysiwyg',
			'tabs'         => 'visual',
			'toolbar'      => 'basic',
			'media_upload' => 0,
		),
		$overrides
	);
}

/**
 * Small secondary line — the muted note most sections carry at the foot.
 *
 * @param array $overrides Field overrides.
 * @return array
 */
function lp_field_note( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'      => 'note',
			'label'     => __( 'Note', 'londonparkour_v8' ),
			'type'      => 'textarea',
			'rows'      => 2,
			'new_lines' => '',
		),
		$overrides
	);
}

/**
 * Right-aligned status line — "UPDATED 09:12 · THU 30 JUL", "FIVE SITES ACROSS LONDON".
 *
 * @param array $overrides Field overrides.
 * @return array
 */
function lp_field_stamp( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'  => 'stamp',
			'label' => __( 'Stamp', 'londonparkour_v8' ),
			'type'  => 'text',
		),
		$overrides
	);
}

/**
 * An image.
 *
 * @param array $overrides Field overrides, e.g. array( 'name' => 'portrait' ).
 * @return array
 */
function lp_field_media( array $overrides = array() ): array {
	return array_merge(
		array(
			'name'          => 'media',
			'label'         => __( 'Media', 'londonparkour_v8' ),
			'type'          => 'image',
			'return_format' => 'id',
			'preview_size'  => 'medium',
			'library'       => 'all',
		),
		$overrides
	);
}

/**
 * A call to action.
 *
 * Every CTA in this theme is an ACF **Link** field
 * (https://www.advancedcustomfields.com/resources/link/). The Link field already
 * carries title, url and target, so there is deliberately NO separate label
 * text field — the link's own title IS the button label. One control, one place
 * to edit, and internal links keep working when a permalink changes.
 *
 * There is deliberately no style control either. Button variants are fixed by
 * the design and hardcoded in each block, exactly as the Storybook has them; a
 * `style` button_group shipped here for one phase with no block reading it,
 * which is a control an editor can move that changes nothing. Add one back only
 * when a design actually offers the choice. See PORT-FINDINGS §13.
 *
 * @param string $name  Field name, e.g. 'primary_action'.
 * @param string $label Field label, e.g. 'Primary action'.
 * @param array  $overrides Group-level overrides.
 * @return array
 */
function lp_field_action( string $name = 'primary_action', string $label = '', array $overrides = array() ): array {
	return array_merge(
		array(
			'name'       => $name,
			'label'      => $label ?: __( 'Primary action', 'londonparkour_v8' ),
			'type'       => 'group',
			'layout'     => 'block',
			'sub_fields' => array(
				array(
					'name'          => 'link',
					'label'         => __( 'Link', 'londonparkour_v8' ),
					'type'          => 'link',
					'return_format' => 'array',
					'instructions'  => __( 'The link text is the button label.', 'londonparkour_v8' ),
				),
			),
		),
		$overrides
	);
}

/**
 * The Settings tab — identical in every block.
 *
 * @return array The tab plus its fields.
 */
function lp_field_settings(): array {
	return array(
		lp_tab( __( 'Settings', 'londonparkour_v8' ) ),
		array(
			'name'         => 'anchor',
			'label'        => __( 'Anchor ID', 'londonparkour_v8' ),
			'type'         => 'text',
			'instructions' => __( 'Optional. Lets a link jump to this section.', 'londonparkour_v8' ),
		),
		array(
			'name'          => 'spacing_top',
			'label'         => __( 'Space above', 'londonparkour_v8' ),
			'type'          => 'select',
			'choices'       => lp_spacing_choices(),
			'default_value' => 'default',
		),
		array(
			'name'          => 'spacing_bottom',
			'label'         => __( 'Space below', 'londonparkour_v8' ),
			'type'          => 'select',
			'choices'       => lp_spacing_choices(),
			'default_value' => 'default',
		),
	);
}

/**
 * Spacing scale options, matching the Utopia fluid scale in assets/css/_vars/spacing.css.
 *
 * @return array<string, string>
 */
function lp_spacing_choices(): array {
	return array(
		'default' => __( 'Default', 'londonparkour_v8' ),
		'none'    => __( 'None', 'londonparkour_v8' ),
		's'       => __( 'Small', 'londonparkour_v8' ),
		'm'       => __( 'Medium', 'londonparkour_v8' ),
		'l'       => __( 'Large', 'londonparkour_v8' ),
		'xl'      => __( 'Extra large', 'londonparkour_v8' ),
	);
}

/* -------------------------------------------------------------------------
 * The source control — identical in all six list-backed blocks
 * ---------------------------------------------------------------------- */

/**
 * Where a block's list of items comes from.
 *
 * Always three options, always in this order, always named `source`. A block
 * never branches on this itself — it calls lp_resolve_source().
 *
 * @param string $post_type       CPT to pull from, e.g. 'lp_class'.
 * @param string $label           Human label for the list, e.g. 'Sessions'.
 * @param array  $manual_subfields Fields for a hand-typed row.
 * @param array  $opts            'multiple' => bool (default true), 'taxonomy' => string|null.
 * @return array Fields to splice into a layout.
 */
function lp_field_source( string $post_type, string $label, array $manual_subfields, array $opts = array() ): array {
	$multiple = $opts['multiple'] ?? true;
	$taxonomy = $opts['taxonomy'] ?? null;

	$fields = array(
		array(
			'name'          => 'source',
			'label'         => __( 'Source', 'londonparkour_v8' ),
			'type'          => 'button_group',
			'instructions'  => sprintf(
				/* translators: %s: the list name, e.g. "Sessions". */
				__( 'Where %s come from.', 'londonparkour_v8' ),
				strtolower( $label )
			),
			'choices'       => array(
				'latest' => __( 'Latest', 'londonparkour_v8' ),
				'choose' => __( 'Choose', 'londonparkour_v8' ),
				'manual' => __( 'Manual', 'londonparkour_v8' ),
			),
			'default_value' => 'latest',
		),
		array(
			'name'           => 'source_limit',
			'label'          => __( 'How many', 'londonparkour_v8' ),
			'type'           => 'number',
			'default_value'  => $multiple ? 4 : 1,
			'min'            => 1,
			'max'            => 24,
			'lp_conditional' => array( array( array( 'field' => 'source', 'operator' => '==', 'value' => 'latest' ) ) ),
		),
	);

	if ( $taxonomy ) {
		$fields[] = array(
			'name'           => 'source_terms',
			'label'          => __( 'Filter by', 'londonparkour_v8' ),
			'type'           => 'taxonomy',
			'taxonomy'       => $taxonomy,
			'field_type'     => 'multi_select',
			'add_term'       => 0,
			'save_terms'     => 0,
			'load_terms'     => 0,
			'return_format'  => 'id',
			'lp_conditional' => array( array( array( 'field' => 'source', 'operator' => '==', 'value' => 'latest' ) ) ),
		);
	}

	$fields[] = array(
		'name'           => 'source_items',
		'label'          => $label,
		'type'           => $multiple ? 'relationship' : 'post_object',
		'post_type'      => array( $post_type ),
		'return_format'  => 'id',
		'filters'        => array( 'search' ),
		'lp_conditional' => array( array( array( 'field' => 'source', 'operator' => '==', 'value' => 'choose' ) ) ),
	);

	$fields[] = array(
		'name'           => 'source_manual',
		'label'          => $label,
		'type'           => 'repeater',
		'layout'         => 'block',
		'button_label'   => sprintf(
			/* translators: %s: singular item name. */
			__( 'Add %s', 'londonparkour_v8' ),
			strtolower( rtrim( $label, 's' ) )
		),
		'max'            => $multiple ? 0 : 1,
		'sub_fields'     => $manual_subfields,
		'lp_conditional' => array( array( array( 'field' => 'source', 'operator' => '==', 'value' => 'manual' ) ) ),
	);

	return $fields;
}

/**
 * Resolve a block's source control to a flat list of items.
 *
 * Blocks call this and then project the result into their own shape — the same
 * entity appears differently in different places (a Hero session row shows four
 * fields, the Classes board eleven), so there is no single view-model.
 *
 * Every item is a flat associative array. Records contribute their ACF fields
 * plus `id`, `title`, `url` and `thumb`; manual rows contribute their subfields
 * with `id` null. A block reads $item['time'], $item['title'] either way.
 *
 * With `'expand' => 'sessions'`, each lp_class record is expanded into one item
 * per row of its `sessions` repeater — the class's own fields merged with that
 * session's, session values winning. `source_limit` then counts SESSIONS, not
 * classes, because the boards that ask for this show one row per time-slot and
 * have a fixed slot count. cpt.php names this function as the seam for exactly
 * this: a session is a time-slot of a class, not a fifth post type.
 *
 * @param array  $args      The block's field values (the whole $args array).
 * @param string $post_type CPT to query when source is 'latest' or 'choose'.
 * @param array  $opts      'expand' => 'sessions' to flatten class time-slots;
 *                          'exclude_flag' => a true_false field name whose set
 *                          records are left out (the entity already featured
 *                          above the list).
 * @return array<int, array>
 */
function lp_resolve_source( array $args, string $post_type, array $opts = array() ): array {
	$source = $args['source'] ?? 'latest';

	if ( 'manual' === $source ) {
		$rows = $args['source_manual'] ?? array();
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_map(
			static function ( $row ) {
				$row['id'] = null;
				return $row;
			},
			$rows
		);
	}

	$ids = array();

	if ( 'choose' === $source ) {
		$chosen = $args['source_items'] ?? array();
		$ids    = array_filter( array_map( 'intval', (array) $chosen ) );
	} else {
		$query_args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			// When expanding, the limit counts sessions, so we cannot know how
			// many classes to fetch — take them all and slice after expansion.
			// ponytail: 100 is a backstop, not a page size; raise it if a client
			// ever runs more than a hundred concurrently published classes.
			'posts_per_page'         => isset( $opts['expand'] )
				? 100
				: max( 1, (int) ( $args['source_limit'] ?? 4 ) ),
			'orderby'                => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		$terms = array_filter( array_map( 'intval', (array) ( $args['source_terms'] ?? array() ) ) );
		if ( $terms ) {
			$taxonomy = lp_source_taxonomy_for( $post_type );
			if ( $taxonomy ) {
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $terms,
					),
				);
			}
		}

		// A block that features one record separately (Locations' flagship,
		// Coaches' lead) must not list it again underneath. The manual fixtures
		// avoid this by hand — the author simply did not retype the flagship
		// into the list — and a query cannot infer that. See PORT-FINDINGS §13.
		if ( ! empty( $opts['exclude_flag'] ) ) {
			$flag = (string) $opts['exclude_flag'];

			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => $flag,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => $flag,
					'value'   => '1',
					'compare' => '!=',
				),
			);
		}

		$ids = get_posts( $query_args );
	}

	$items = array();

	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( ! $id ) {
			continue;
		}

		$fields = function_exists( 'get_fields' ) ? get_fields( $id ) : array();
		$fields = is_array( $fields ) ? $fields : array();
		$fields = lp_flatten_references( $fields, $post_type );

		// The Classes board shows a level per row; it lives on the taxonomy, not
		// in a field, so it is attached here rather than in the block.
		$level_tax = lp_source_taxonomy_for( $post_type );
		if ( $level_tax && ! isset( $fields['level'] ) ) {
			$assigned        = get_the_terms( $id, $level_tax );
			$fields['level'] = is_array( $assigned ) && $assigned ? $assigned[0]->name : '';
		}

		$items[] = array_merge(
			$fields,
			array(
				'id'    => $id,
				'title' => get_the_title( $id ),
				'url'   => get_permalink( $id ),
				'thumb' => get_post_thumbnail_id( $id ) ?: null,
			)
		);
	}

	if ( 'sessions' === ( $opts['expand'] ?? '' ) ) {
		$items = lp_expand_sessions( $items, max( 1, (int) ( $args['source_limit'] ?? 4 ) ) );
	}

	return $items;
}

/**
 * Expand class records into one item per session.
 *
 * A class with two sessions becomes two rows carrying the same title, price and
 * location. A class with none contributes nothing — an unscheduled class is not
 * a time-slot and does not belong on a timetable.
 *
 * @param array<int,array> $items Resolved class records.
 * @param int              $limit How many SESSIONS to return.
 * @return array<int,array>
 */
function lp_expand_sessions( array $items, int $limit ): array {
	$rows = array();

	foreach ( $items as $item ) {
		$sessions = $item['sessions'] ?? array();

		if ( ! is_array( $sessions ) || ! $sessions ) {
			continue;
		}

		unset( $item['sessions'] );

		foreach ( $sessions as $session ) {
			if ( ! is_array( $session ) ) {
				continue;
			}

			$rows[] = array_merge( $item, $session );

			if ( count( $rows ) >= $limit ) {
				return $rows;
			}
		}
	}

	return $rows;
}

/**
 * The filter taxonomy a given post type uses in its source control.
 *
 * @param string $post_type Post type name.
 * @return string|null
 */
function lp_source_taxonomy_for( string $post_type ): ?string {
	$map = array(
		'lp_class'    => 'lp_level',
		'lp_tutorial' => 'lp_level',
	);

	return $map[ $post_type ] ?? null;
}

/**
 * Reference fields on a source record, and how to flatten them.
 *
 * A block projection does `(string) $item['location']`, so a post_object field
 * returning an ID renders as a number. These fields are replaced by their post
 * title(s) before the item reaches a block.
 *
 * This is a map rather than a lookup through get_field_object() because that
 * would cost a query per field per record, and because the set is small and
 * changes only when a CPT gains a reference field. It sits beside
 * lp_source_taxonomy_for(), which is the same shape for the same reason.
 *
 * @param string $post_type Post type name.
 * @return array<string,string> Field name => 'title' (one) or 'titles' (many).
 */
function lp_source_reference_fields( string $post_type ): array {
	$map = array(
		'lp_class'    => array( 'location' => 'title', 'coaches' => 'titles' ),
		'lp_coach'    => array( 'location' => 'title' ),
		'lp_tutorial' => array( 'coaches' => 'titles' ),
	);

	return $map[ $post_type ] ?? array();
}

/**
 * Replace reference IDs with titles on one resolved item.
 *
 * @param array  $fields    The record's ACF fields.
 * @param string $post_type Post type the record belongs to.
 * @return array
 */
function lp_flatten_references( array $fields, string $post_type ): array {
	foreach ( lp_source_reference_fields( $post_type ) as $name => $mode ) {
		if ( ! isset( $fields[ $name ] ) ) {
			continue;
		}

		$ids = array_filter( array_map( 'intval', (array) $fields[ $name ] ) );

		if ( ! $ids ) {
			$fields[ $name ] = '';
			continue;
		}

		$titles = array_map( 'get_the_title', $ids );

		$fields[ $name ] = 'titles' === $mode ? implode( ', ', $titles ) : (string) $titles[0];
	}

	return $fields;
}

/* -------------------------------------------------------------------------
 * Render-time helpers shared by every block
 * ---------------------------------------------------------------------- */

/**
 * Section spacing classes for a block's Settings values.
 *
 * Returns whole literal class strings from a lookup — never built by
 * concatenation, because Tailwind v4 text-scans source.
 *
 * @param array $args Block args.
 * @return string
 */
function lp_section_spacing( array $args ): string {
	$top = array(
		'default' => '',
		'none'    => 'pt-0',
		's'       => 'pt-scale-s',
		'm'       => 'pt-scale-m',
		'l'       => 'pt-scale-l',
		'xl'      => 'pt-scale-xl',
	);

	$bottom = array(
		'default' => '',
		'none'    => 'pb-0',
		's'       => 'pb-scale-s',
		'm'       => 'pb-scale-m',
		'l'       => 'pb-scale-l',
		'xl'      => 'pb-scale-xl',
	);

	$classes = array(
		$top[ $args['spacing_top'] ?? 'default' ] ?? '',
		$bottom[ $args['spacing_bottom'] ?? 'default' ] ?? '',
	);

	return trim( implode( ' ', array_filter( $classes ) ) );
}

/**
 * A block's anchor id, if the editor set one.
 *
 * @param array $args Block args.
 * @return string Ready to interpolate, e.g. ' id="pricing"'.
 */
function lp_section_anchor( array $args ): string {
	$anchor = sanitize_title( (string) ( $args['anchor'] ?? '' ) );

	return $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '';
}

/**
 * Normalise an ACF action group to label + href + target + style.
 *
 * The label comes from the ACF Link field's own title — see lp_field_action().
 * Accepts a bare Link field value too, so a CTA declared as a plain `link`
 * field (rather than the action group) still resolves.
 *
 * Returns null when there is nothing to render, so a block can simply do
 * `if ( $cta )`.
 *
 * @param mixed $action The action group value, or a bare Link field value.
 * @return array{label:string, href:string, target:string}|null
 */
function lp_action( $action ): ?array {
	if ( ! is_array( $action ) ) {
		return null;
	}

	// A bare Link field has url/title/target at the top level; the action group
	// nests it under 'link'.
	$link = isset( $action['link'] ) && is_array( $action['link'] ) ? $action['link'] : $action;

	$href  = (string) ( $link['url'] ?? '' );
	$label = (string) ( $link['title'] ?? '' );

	if ( '' === $href && '' === $label ) {
		return null;
	}

	return array(
		'label'  => $label,
		'href'   => $href,
		'target' => (string) ( $link['target'] ?? '' ),
	);
}
