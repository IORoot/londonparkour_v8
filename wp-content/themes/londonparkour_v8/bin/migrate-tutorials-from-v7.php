<?php
/**
 * Migrate all tutorials from v7 → v8.
 *
 * Run: docker exec londonparkour_v8-wordpress-1 php /var/www/html/wp-content/themes/londonparkour_v8/bin/migrate-tutorials-from-v7.php
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

echo "Removing demo lp_tutorial posts...\n";
$demo_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lp_tutorial'" );
foreach ( $demo_ids as $demo_id ) {
	wp_delete_post( (int) $demo_id, true );
}
echo '  Deleted ' . count( $demo_ids ) . " demo posts\n";

$tt_map       = []; // old term_taxonomy_id => new term_taxonomy_id.
$term_id_map  = []; // old term_id => new term_id.
$slug_to_term = []; // taxonomy => slug => term_id.

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
		$term_id = $slug_to_term[ $taxonomy ][ $slug ];
		$term_id_map[ $old_term_id ] = $term_id;
		return $term_id;
	}

	$existing = get_term_by( 'slug', $slug, $taxonomy );
	if ( $existing && ! is_wp_error( $existing ) ) {
		$term_id = (int) $existing->term_id;
	} else {
		$args = [];
		if ( $parent_new_id ) {
			$args['parent'] = $parent_new_id;
		}
		$created = wp_insert_term( $name, $taxonomy, array_merge( $args, [ 'slug' => $slug ] ) );
		if ( is_wp_error( $created ) ) {
			throw new RuntimeException( 'Term insert failed for ' . $slug . ': ' . $created->get_error_message() );
		}
		$term_id = (int) $created['term_id'];
	}

	$slug_to_term[ $taxonomy ][ $slug ] = $term_id;
	$term_id_map[ $old_term_id ]        = $term_id;
	return $term_id;
}

echo "Importing taxonomy terms...\n";

$category_terms = lp_v7_query(
	$v7,
	"SELECT t.term_id, t.name, t.slug, tt.parent, tt.term_taxonomy_id
	 FROM wp_terms t
	 JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
	 WHERE tt.taxonomy = 'tutorial_category'
	 ORDER BY tt.parent ASC, t.term_id ASC"
);

$old_parent_map = [];
foreach ( $category_terms as $row ) {
	$old_parent_map[ (int) $row['term_id'] ] = (int) $row['parent'];
}

// First pass: create terms without parents.
foreach ( $category_terms as $row ) {
	$old_id = (int) $row['term_id'];
	if ( $old_parent_map[ $old_id ] !== 0 ) {
		continue;
	}
	$new_id = lp_ensure_term(
		'tutorial-category',
		$row['name'],
		$row['slug'],
		null,
		$slug_to_term,
		$term_id_map,
		$old_id
	);
	$new_tt   = get_term( $new_id, 'tutorial-category' );
	$tt_map[ (int) $row['term_taxonomy_id'] ] = (int) $new_tt->term_taxonomy_id;
}

// Second pass: children.
$remaining = array_filter(
	$category_terms,
	static fn( $row ) => $old_parent_map[ (int) $row['term_id'] ] !== 0
);
$guard     = 0;
while ( $remaining && $guard < 20 ) {
	$next = [];
	foreach ( $remaining as $row ) {
		$old_id        = (int) $row['term_id'];
		$old_parent_id = $old_parent_map[ $old_id ];
		if ( ! isset( $term_id_map[ $old_parent_id ] ) ) {
			$next[] = $row;
			continue;
		}
		$new_id = lp_ensure_term(
			'tutorial-category',
			$row['name'],
			$row['slug'],
			$term_id_map[ $old_parent_id ],
			$slug_to_term,
			$term_id_map,
			$old_id
		);
		$new_tt   = get_term( $new_id, 'tutorial-category' );
		$tt_map[ (int) $row['term_taxonomy_id'] ] = (int) $new_tt->term_taxonomy_id;
	}
	$remaining = $next;
	++$guard;
}

$syllabus_terms = lp_v7_query(
	$v7,
	"SELECT t.term_id, t.name, t.slug, tt.parent, tt.term_taxonomy_id
	 FROM wp_terms t
	 JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
	 WHERE tt.taxonomy = 'syllabus_category'
	 ORDER BY tt.parent ASC, t.term_id ASC"
);

foreach ( $syllabus_terms as $row ) {
	$old_id   = (int) $row['term_id'];
	$old_tt   = (int) $row['term_taxonomy_id'];
	$parent   = (int) $row['parent'];
	$parent_new = ( $parent && isset( $term_id_map[ $parent ] ) ) ? $term_id_map[ $parent ] : null;

	$new_id = lp_ensure_term(
		'tutorial-category',
		$row['name'],
		$row['slug'],
		$parent_new,
		$slug_to_term,
		$term_id_map,
		$old_id
	);
	$new_tt            = get_term( $new_id, 'tutorial-category' );
	$tt_map[ $old_tt ] = (int) $new_tt->term_taxonomy_id;
}

$tag_terms = lp_v7_query(
	$v7,
	"SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id
	 FROM wp_terms t
	 JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
	 WHERE tt.taxonomy = 'tutorial_tags'"
);

foreach ( $tag_terms as $row ) {
	$old_id = (int) $row['term_id'];
	$new_id = lp_ensure_term(
		'tutorial-tag',
		$row['name'],
		$row['slug'],
		null,
		$slug_to_term,
		$term_id_map,
		$old_id
	);
	$new_tt                              = get_term( $new_id, 'tutorial-tag' );
	$tt_map[ (int) $row['term_taxonomy_id'] ] = (int) $new_tt->term_taxonomy_id;
}

echo '  Terms mapped: ' . count( $term_id_map ) . "\n";

echo "Importing term meta (svg_glyph)...\n";
$term_meta_rows = lp_v7_query(
	$v7,
	"SELECT tm.term_id, tm.meta_key, tm.meta_value
	 FROM wp_termmeta tm
	 JOIN wp_term_taxonomy tt ON tt.term_id = tm.term_id
	 WHERE tt.taxonomy IN ('tutorial_category', 'syllabus_category')"
);
$term_meta_count = 0;
foreach ( $term_meta_rows as $row ) {
	$old_term_id = (int) $row['term_id'];
	if ( ! isset( $term_id_map[ $old_term_id ] ) ) {
		continue;
	}
	update_term_meta( $term_id_map[ $old_term_id ], $row['meta_key'], maybe_unserialize( $row['meta_value'] ) );
	++$term_meta_count;
}
echo "  Term meta rows: {$term_meta_count}\n";

echo "Importing tutorials...\n";
$tutorials = lp_v7_query(
	$v7,
	"SELECT * FROM wp_posts WHERE post_type = 'tutorial' AND post_status = 'publish' ORDER BY ID ASC"
);

$post_map        = [];
$imported        = 0;
$skipped_thumb   = 0;
$rels            = 0;
$meta_rows       = 0;

foreach ( $tutorials as $row ) {
	$old_id = (int) $row['ID'];

	$new_id = wp_insert_post(
		[
			'post_type'              => 'lp_tutorial',
			'post_status'            => $row['post_status'],
			'post_title'             => $row['post_title'],
			'post_name'              => $row['post_name'],
			'post_content'           => $row['post_content'],
			'post_excerpt'           => $row['post_excerpt'],
			'post_author'            => 1,
			'post_date'              => $row['post_date'],
			'post_date_gmt'          => $row['post_date_gmt'],
			'post_modified'          => $row['post_modified'],
			'post_modified_gmt'    => $row['post_modified_gmt'],
			'comment_status'         => $row['comment_status'],
			'ping_status'            => $row['ping_status'],
			'menu_order'             => (int) $row['menu_order'],
			'post_parent'            => 0,
			'post_password'          => $row['post_password'],
			'guid'                   => $v8_url . '/?post_type=lp_tutorial&#038;p=0',
		],
		true
	);

	if ( is_wp_error( $new_id ) ) {
		fwrite( STDERR, "Post failed {$old_id}: " . $new_id->get_error_message() . PHP_EOL );
		continue;
	}

	$new_id = (int) $new_id;
	$post_map[ $old_id ] = $new_id;

	$wpdb->update(
		$wpdb->posts,
		[ 'guid' => $v8_url . '/?post_type=lp_tutorial&p=' . $new_id ],
		[ 'ID' => $new_id ],
		[ '%s' ],
		[ '%d' ]
	);

	$meta = lp_v7_query(
		$v7,
		"SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id = {$old_id}"
	);

	foreach ( $meta as $meta_row ) {
		$key   = $meta_row['meta_key'];
		$value = maybe_unserialize( $meta_row['meta_value'] );

		if ( $key === '_thumbnail_id' ) {
			$thumb_id = (int) $value;
			if ( ! get_post( $thumb_id ) ) {
				++$skipped_thumb;
				continue;
			}
		}

		update_post_meta( $new_id, $key, $value );
		++$meta_rows;
	}

	$relationships = lp_v7_query(
		$v7,
		"SELECT tr.term_taxonomy_id, tr.term_order
		 FROM wp_term_relationships tr
		 JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		 WHERE tr.object_id = {$old_id}
		   AND tt.taxonomy IN ('tutorial_category', 'syllabus_category', 'tutorial_tags')"
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
		++$rels;
	}

	foreach ( $terms_by_tax as $taxonomy => $term_ids ) {
		wp_set_object_terms( $new_id, array_values( array_unique( $term_ids ) ), $taxonomy, true );
	}

	++$imported;
	if ( $imported % 100 === 0 ) {
		echo "  {$imported}...\n";
	}
}

clean_term_cache( [], 'tutorial-category' );
clean_term_cache( [], 'tutorial-tag' );

echo "\nDone\n";
echo "Tutorials imported: {$imported}\n";
echo "Post meta rows: {$meta_rows}\n";
echo "Term relationships: {$rels}\n";
echo "Skipped missing thumbnails: {$skipped_thumb}\n";
echo 'Total lp_tutorial posts: ' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='lp_tutorial'" ) . "\n";

$sample = $wpdb->get_row(
	"SELECT p.ID, p.post_name, pm.meta_value AS thumb
	 FROM {$wpdb->posts} p
	 LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_thumbnail_id'
	 WHERE p.post_type = 'lp_tutorial'
	 ORDER BY p.ID ASC LIMIT 1",
	ARRAY_A
);
if ( $sample ) {
	echo "Sample: ID {$sample['ID']} slug {$sample['post_name']} thumb {$sample['thumb']}\n";
}
