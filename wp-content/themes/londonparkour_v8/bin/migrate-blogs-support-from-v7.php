<?php
/**
 * Migrate blogs and support posts from v7 → v8.
 *
 * Run: docker exec londonparkour_v8-wordpress-1 php /var/www/html/wp-content/themes/londonparkour_v8/bin/migrate-blogs-support-from-v7.php
 *
 * @package londonparkour_v8
 */

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

@set_time_limit( 0 );

$v7 = new mysqli(
	'host.docker.internal',
	'root',
	'nl175PjdyZm4pmCKeyER',
	'live_londonparkourV7_com',
	4306
);

if ( $v7->connect_error ) {
	fwrite( STDERR, 'v7 DB connection failed: ' . $v7->connect_error . PHP_EOL );
	exit( 1 );
}

$v7->set_charset( 'utf8mb4' );

global $wpdb;

$v8_url = 'http://localhost:8102';

function lp_v7_query( mysqli $v7, string $sql ): array {
	$result = $v7->query( $sql );
	if ( ! $result ) {
		throw new RuntimeException( $v7->error );
	}
	$rows = [];
	while ( $row = $result->fetch_assoc() ) {
		$rows[] = $row;
	}
	$result->free();
	return $rows;
}

function lp_ensure_term(
	string $taxonomy,
	string $name,
	string $slug,
	?int $parent_new_id,
	array &$slug_to_term,
	array &$term_id_map,
	int $old_term_id
): int {
	if ( isset( $slug_to_term[ $taxonomy ][ $slug ] ) ) {
		$term_id                     = $slug_to_term[ $taxonomy ][ $slug ];
		$term_id_map[ $old_term_id ] = $term_id;
		return $term_id;
	}

	$existing = get_term_by( 'slug', $slug, $taxonomy );
	if ( $existing && ! is_wp_error( $existing ) ) {
		$term_id = (int) $existing->term_id;
	} else {
		$args    = [ 'slug' => $slug ];
		$created = wp_insert_term( $name, $taxonomy, $parent_new_id ? array_merge( $args, [ 'parent' => $parent_new_id ] ) : $args );
		if ( is_wp_error( $created ) ) {
			throw new RuntimeException( 'Term insert failed for ' . $slug . ': ' . $created->get_error_message() );
		}
		$term_id = (int) $created['term_id'];
	}

	$slug_to_term[ $taxonomy ][ $slug ] = $term_id;
	$term_id_map[ $old_term_id ]        = $term_id;

	return $term_id;
}

function lp_import_hierarchical_terms(
	mysqli $v7,
	string $v7_taxonomy,
	string $v8_taxonomy,
	array &$tt_map,
	array &$term_id_map,
	array &$slug_to_term
): int {
	$terms = lp_v7_query(
		$v7,
		"SELECT t.term_id, t.name, t.slug, tt.parent, tt.term_taxonomy_id
		 FROM wp_terms t
		 JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
		 WHERE tt.taxonomy = '{$v7_taxonomy}'
		 ORDER BY tt.parent ASC, t.term_id ASC"
	);

	$old_parent_map = [];
	foreach ( $terms as $row ) {
		$old_parent_map[ (int) $row['term_id'] ] = (int) $row['parent'];
	}

	foreach ( $terms as $row ) {
		if ( $old_parent_map[ (int) $row['term_id'] ] !== 0 ) {
			continue;
		}
		$new_id = lp_ensure_term( $v8_taxonomy, $row['name'], $row['slug'], null, $slug_to_term, $term_id_map, (int) $row['term_id'] );
		$new_tt = get_term( $new_id, $v8_taxonomy );
		$tt_map[ (int) $row['term_taxonomy_id'] ] = (int) $new_tt->term_taxonomy_id;
	}

	$remaining = array_filter( $terms, static fn( $row ) => $old_parent_map[ (int) $row['term_id'] ] !== 0 );
	for ( $guard = 0; $remaining && $guard < 20; ++$guard ) {
		$next = [];
		foreach ( $remaining as $row ) {
			$old_id        = (int) $row['term_id'];
			$old_parent_id = $old_parent_map[ $old_id ];
			if ( ! isset( $term_id_map[ $old_parent_id ] ) ) {
				$next[] = $row;
				continue;
			}
			$new_id = lp_ensure_term( $v8_taxonomy, $row['name'], $row['slug'], $term_id_map[ $old_parent_id ], $slug_to_term, $term_id_map, $old_id );
			$new_tt = get_term( $new_id, $v8_taxonomy );
			$tt_map[ (int) $row['term_taxonomy_id'] ] = (int) $new_tt->term_taxonomy_id;
		}
		$remaining = $next;
	}

	return count( $terms );
}

function lp_import_flat_terms(
	mysqli $v7,
	string $v7_taxonomy,
	string $v8_taxonomy,
	array &$tt_map,
	array &$term_id_map,
	array &$slug_to_term
): int {
	$terms = lp_v7_query(
		$v7,
		"SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id
		 FROM wp_terms t
		 JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
		 WHERE tt.taxonomy = '{$v7_taxonomy}'"
	);

	foreach ( $terms as $row ) {
		$new_id = lp_ensure_term( $v8_taxonomy, $row['name'], $row['slug'], null, $slug_to_term, $term_id_map, (int) $row['term_id'] );
		$new_tt = get_term( $new_id, $v8_taxonomy );
		$tt_map[ (int) $row['term_taxonomy_id'] ] = (int) $new_tt->term_taxonomy_id;
	}

	return count( $terms );
}

function lp_import_posts(
	mysqli $v7,
	string $v7_post_type,
	string $v8_post_type,
	array $v7_taxonomies,
	array &$tt_map,
	string $v8_url
): array {
	global $wpdb;

	$stats = [
		'imported'       => 0,
		'meta_rows'      => 0,
		'rels'           => 0,
		'skipped_thumb'  => 0,
	];

	$tax_in = "'" . implode( "','", array_map( 'esc_sql', $v7_taxonomies ) ) . "'";
	$posts  = lp_v7_query(
		$v7,
		"SELECT * FROM wp_posts WHERE post_type = '{$v7_post_type}' AND post_status = 'publish' ORDER BY ID ASC"
	);

	foreach ( $posts as $row ) {
		$old_id = (int) $row['ID'];

		$new_id = wp_insert_post(
			[
				'post_type'           => $v8_post_type,
				'post_status'         => $row['post_status'],
				'post_title'          => $row['post_title'],
				'post_name'           => $row['post_name'],
				'post_content'        => $row['post_content'],
				'post_excerpt'        => $row['post_excerpt'],
				'post_author'         => 1,
				'post_date'           => $row['post_date'],
				'post_date_gmt'       => $row['post_date_gmt'],
				'post_modified'       => $row['post_modified'],
				'post_modified_gmt' => $row['post_modified_gmt'],
				'comment_status'      => $row['comment_status'],
				'ping_status'         => $row['ping_status'],
				'menu_order'          => (int) $row['menu_order'],
				'post_parent'         => 0,
				'post_password'       => $row['post_password'],
			],
			true
		);

		if ( is_wp_error( $new_id ) ) {
			fwrite( STDERR, "Post failed {$old_id}: " . $new_id->get_error_message() . PHP_EOL );
			continue;
		}

		$new_id = (int) $new_id;
		$wpdb->update(
			$wpdb->posts,
			[ 'guid' => $v8_url . '/?post_type=' . $v8_post_type . '&p=' . $new_id ],
			[ 'ID' => $new_id ],
			[ '%s' ],
			[ '%d' ]
		);

		foreach ( lp_v7_query( $v7, "SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id = {$old_id}" ) as $meta_row ) {
			$key   = $meta_row['meta_key'];
			$value = maybe_unserialize( $meta_row['meta_value'] );

			if ( $key === '_thumbnail_id' && ! get_post( (int) $value ) ) {
				++$stats['skipped_thumb'];
				continue;
			}

			update_post_meta( $new_id, $key, $value );
			++$stats['meta_rows'];
		}

		$relationships = lp_v7_query(
			$v7,
			"SELECT tr.term_taxonomy_id
			 FROM wp_term_relationships tr
			 JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 WHERE tr.object_id = {$old_id} AND tt.taxonomy IN ({$tax_in})"
		);

		$terms_by_tax = [];
		foreach ( $relationships as $rel ) {
			$old_tt = (int) $rel['term_taxonomy_id'];
			if ( ! isset( $tt_map[ $old_tt ] ) ) {
				continue;
			}
			$term = get_term_by( 'term_taxonomy_id', $tt_map[ $old_tt ] );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$terms_by_tax[ $term->taxonomy ][] = (int) $term->term_id;
			++$stats['rels'];
		}

		foreach ( $terms_by_tax as $taxonomy => $term_ids ) {
			wp_set_object_terms( $new_id, array_values( array_unique( $term_ids ) ), $taxonomy, true );
		}

		++$stats['imported'];
	}

	return $stats;
}

$tt_map       = [];
$term_id_map  = [];
$slug_to_term = [];

echo "=== Blog taxonomies ===\n";
echo '  blog_category: ' . lp_import_hierarchical_terms( $v7, 'blog_category', 'blog-category', $tt_map, $term_id_map, $slug_to_term ) . " terms\n";
echo '  blog_tags: ' . lp_import_flat_terms( $v7, 'blog_tags', 'blog-tag', $tt_map, $term_id_map, $slug_to_term ) . " terms\n";

echo "=== Support taxonomies ===\n";
echo '  support_category: ' . lp_import_hierarchical_terms( $v7, 'support_category', 'support-category', $tt_map, $term_id_map, $slug_to_term ) . " terms\n";

echo "=== Term meta ===\n";
$term_meta_count = 0;
foreach (
	lp_v7_query(
		$v7,
		"SELECT tm.term_id, tm.meta_key, tm.meta_value
		 FROM wp_termmeta tm
		 JOIN wp_term_taxonomy tt ON tt.term_id = tm.term_id
		 WHERE tt.taxonomy IN ('blog_category', 'blog_tags', 'support_category')"
	) as $row
) {
	$old_term_id = (int) $row['term_id'];
	if ( ! isset( $term_id_map[ $old_term_id ] ) ) {
		continue;
	}
	update_term_meta( $term_id_map[ $old_term_id ], $row['meta_key'], maybe_unserialize( $row['meta_value'] ) );
	++$term_meta_count;
}
echo "  Rows: {$term_meta_count}\n";

echo "=== Blog posts ===\n";
$blog_stats = lp_import_posts( $v7, 'blog', 'blog', [ 'blog_category', 'blog_tags' ], $tt_map, $v8_url );
print_r( $blog_stats );

echo "=== Support posts ===\n";
$support_stats = lp_import_posts( $v7, 'support', 'support', [ 'support_category' ], $tt_map, $v8_url );
print_r( $support_stats );

echo "=== Removing demo WordPress posts ===\n";
$demo_posts = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post'" );
foreach ( $demo_posts as $demo_id ) {
	wp_delete_post( (int) $demo_id, true );
}
echo '  Deleted ' . count( $demo_posts ) . " demo posts\n";

clean_term_cache( [], 'blog-category' );
clean_term_cache( [], 'blog-tag' );
clean_term_cache( [], 'support-category' );

echo "\nTotals\n";
echo 'blog: ' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='blog'" ) . "\n";
echo 'support: ' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='support'" ) . "\n";
echo 'post (should be 0): ' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post'" ) . "\n";
