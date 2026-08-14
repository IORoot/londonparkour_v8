<?php
/**
 * Custom columns on the lp_tutorial admin list table.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register tutorial listing columns.
 */
function lp_register_tutorial_listing_columns(): void {
	$post_type = 'lp_tutorial';

	add_filter( "manage_{$post_type}_posts_columns", 'lp_tutorial_listing_columns' );
	add_action( "manage_{$post_type}_posts_custom_column", 'lp_tutorial_listing_column_content', 10, 2 );
	add_filter( "manage_edit-{$post_type}_sortable_columns", 'lp_tutorial_listing_sortable_columns' );
	add_action( 'pre_get_posts', 'lp_tutorial_listing_column_orderby' );
	add_action( 'admin_head', 'lp_tutorial_listing_column_styles' );
}
add_action( 'admin_init', 'lp_register_tutorial_listing_columns' );

/**
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function lp_tutorial_listing_columns( array $columns ): array {
	$ordered = array();

	if ( isset( $columns['cb'] ) ) {
		$ordered['cb'] = $columns['cb'];
	}

	$ordered['featured_image']      = __( 'Featured Image', 'londonparkour_v8' );
	$ordered['title']               = $columns['title'] ?? __( 'Title', 'londonparkour_v8' );
	$ordered['tutorial_order']      = __( 'Order', 'londonparkour_v8' );
	$ordered['taxonomy-lp_series']  = $columns['taxonomy-lp_series'] ?? __( 'Series', 'londonparkour_v8' );
	$ordered['tutorial_category']   = __( 'Tutorial Category', 'londonparkour_v8' );
	$ordered['tutorial_tags']       = __( 'Tags', 'londonparkour_v8' );
	$ordered['youtube_id']          = __( 'YouTube ID', 'londonparkour_v8' );

	if ( isset( $columns['date'] ) ) {
		$ordered['date'] = $columns['date'];
	}

	return $ordered;
}

/**
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function lp_tutorial_listing_column_content( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'featured_image':
			if ( has_post_thumbnail( $post_id ) ) {
				$thumb_id  = get_post_thumbnail_id( $post_id );
				$thumb_url = wp_get_attachment_image_url( $thumb_id, 'thumbnail' );
				if ( $thumb_url ) {
					printf(
						'<img src="%s" alt="" data-id="%d" />',
						esc_url( $thumb_url ),
						(int) $thumb_id
					);
				}
			} else {
				echo '—';
			}
			break;

		case 'tutorial_order':
			$label = lp_tutorial_order_label( $post_id );
			echo '' !== $label ? esc_html( $label ) : '—';
			break;

		case 'tutorial_category':
			$terms = get_the_terms( $post_id, 'tutorial-category' );
			echo $terms && ! is_wp_error( $terms )
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '—';
			break;

		case 'tutorial_tags':
			$terms = get_the_terms( $post_id, 'tutorial-tag' );
			echo $terms && ! is_wp_error( $terms )
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '—';
			break;

		case 'youtube_id':
			echo esc_html( lp_tutorial_listing_trim( lp_tutorial_listing_field( $post_id, 'video_id' ) ) );
			break;
	}
}

/**
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string>
 */
function lp_tutorial_listing_sortable_columns( array $columns ): array {
	$columns['youtube_id']     = 'youtube_id';
	$columns['tutorial_order'] = 'tutorial_order';

	return $columns;
}

/**
 * @param WP_Query $query Main query.
 */
function lp_tutorial_listing_column_orderby( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->get( 'post_type' ) !== 'lp_tutorial' ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	$meta_map = array(
		'youtube_id' => 'video_id',
	);

	if ( isset( $meta_map[ $orderby ] ) ) {
		$query->set( 'meta_key', $meta_map[ $orderby ] );
		$query->set( 'orderby', 'meta_value' );
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- admin list table sort.
	$requested = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' !== $requested && 'tutorial_order' !== $requested ) {
		return;
	}

	$query->set( 'lp_natural_order', true );
}

/**
 * Admin styles for the custom columns.
 */
function lp_tutorial_listing_column_styles(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || $screen->post_type !== 'lp_tutorial' || $screen->base !== 'edit' ) {
		return;
	}

	echo '<style>
		.column-featured_image { width: 90px; }
		.column-taxonomy-lp_series,
		.column-tutorial_category,
		.column-tutorial_tags { width: 12%; }
		.column-tutorial_order { width: 72px; text-align: center; }
		.column-youtube_id { width: 14%; }
		td.featured_image.column-featured_image img {
			max-width: 72px;
			height: auto;
			border-radius: 4px;
		}
	</style>';
}

/**
 * @param int    $post_id   Post ID.
 * @param string $field_key ACF/meta field name.
 * @return string
 */
function lp_tutorial_listing_field( int $post_id, string $field_key ): string {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $field_key, $post_id );
	} else {
		$value = get_post_meta( $post_id, $field_key, true );
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
function lp_tutorial_listing_trim( string $value, int $limit = 100 ): string {
	$value = wp_strip_all_tags( $value );

	if ( $value === '' ) {
		return '—';
	}

	if ( mb_strlen( $value ) <= $limit ) {
		return $value;
	}

	return mb_substr( $value, 0, $limit ) . '…';
}
