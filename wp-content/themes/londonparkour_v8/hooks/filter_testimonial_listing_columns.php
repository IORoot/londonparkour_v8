<?php
/**
 * Custom columns on the lp_testimonial admin list table.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register testimonial listing columns.
 */
function lp_register_testimonial_listing_columns(): void {
	$post_type = 'lp_testimonial';

	add_filter( "manage_{$post_type}_posts_columns", 'lp_testimonial_listing_columns' );
	add_action( "manage_{$post_type}_posts_custom_column", 'lp_testimonial_listing_column_content', 10, 2 );
	add_filter( "manage_edit-{$post_type}_sortable_columns", 'lp_testimonial_listing_sortable_columns' );
	add_action( 'pre_get_posts', 'lp_testimonial_listing_column_orderby' );
}
add_action( 'admin_init', 'lp_register_testimonial_listing_columns' );

/**
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function lp_testimonial_listing_columns( array $columns ): array {
	$ordered = array();

	if ( isset( $columns['cb'] ) ) {
		$ordered['cb'] = $columns['cb'];
	}

	$ordered['title']       = $columns['title'] ?? __( 'Title', 'londonparkour_v8' );
	$ordered['rating']      = __( 'Rating', 'londonparkour_v8' );
	$ordered['quote']       = __( 'Quote', 'londonparkour_v8' );
	$ordered['reviewed_at'] = __( 'Reviewed', 'londonparkour_v8' );

	if ( isset( $columns['date'] ) ) {
		$ordered['date'] = $columns['date'];
	}

	return $ordered;
}

/**
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function lp_testimonial_listing_column_content( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'rating':
			$rating = (int) lp_testimonial_listing_field( $post_id, 'rating' );
			echo $rating > 0 ? esc_html( (string) $rating ) : '—';
			break;

		case 'quote':
			$quote = lp_testimonial_listing_field( $post_id, 'quote' );
			echo esc_html( lp_testimonial_listing_trim( $quote, 80 ) );
			break;

		case 'reviewed_at':
			$date = lp_testimonial_listing_field( $post_id, 'reviewed_at' );
			echo '' !== $date ? esc_html( $date ) : '—';
			break;
	}
}

/**
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string>
 */
function lp_testimonial_listing_sortable_columns( array $columns ): array {
	$columns['rating']      = 'rating';
	$columns['reviewed_at'] = 'reviewed_at';

	return $columns;
}

/**
 * @param WP_Query $query Main query.
 */
function lp_testimonial_listing_column_orderby( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->get( 'post_type' ) !== 'lp_testimonial' ) {
		return;
	}

	$orderby  = $query->get( 'orderby' );
	$meta_map = array(
		'rating'      => 'rating',
		'reviewed_at' => 'reviewed_at',
	);

	if ( isset( $meta_map[ $orderby ] ) ) {
		$query->set( 'meta_key', $meta_map[ $orderby ] );
		$query->set( 'orderby', 'rating' === $orderby ? 'meta_value_num' : 'meta_value' );
	}
}

/**
 * @param int    $post_id    Post ID.
 * @param string $field_name ACF field name.
 * @return string
 */
function lp_testimonial_listing_field( int $post_id, string $field_name ): string {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $field_name, $post_id );
	} else {
		$value = get_post_meta( $post_id, $field_name, true );
	}

	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}

	return (string) $value;
}

/**
 * @param string $value Raw value.
 * @param int    $limit Character limit.
 * @return string
 */
function lp_testimonial_listing_trim( string $value, int $limit = 80 ): string {
	$value = wp_strip_all_tags( $value );

	if ( '' === $value ) {
		return '—';
	}

	if ( mb_strlen( $value ) <= $limit ) {
		return $value;
	}

	return mb_substr( $value, 0, $limit ) . '…';
}
