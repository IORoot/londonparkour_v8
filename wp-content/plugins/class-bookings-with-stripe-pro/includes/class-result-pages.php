<?php
/**
 * Auto-create + look up Success / Cancelled / Error pages.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Result_Pages {

	private const SLUGS = [
		'success'   => [
			'slug'      => 'booking-confirmed',
			'title'     => 'Booking Confirmed',
			'option'    => 'success_page',
			'field_key' => 'field_clasbpro_success_page',
			'meta'      => '_clasbpro_result_page_success',
			'content'   => '[clasbpro_booking_status type="success"]',
		],
		'cancelled' => [
			'slug'      => 'booking-cancelled',
			'title'     => 'Booking Cancelled',
			'option'    => 'cancel_page',
			'field_key' => 'field_clasbpro_cancel_page',
			'meta'      => '_clasbpro_result_page_cancel',
			'content'   => '[clasbpro_booking_status type="cancelled"]',
		],
		'error'     => [
			'slug'      => 'booking-error',
			'title'     => 'Booking Error',
			'option'    => 'error_page',
			'field_key' => 'field_clasbpro_error_page',
			'meta'      => '_clasbpro_result_page_error',
			'content'   => '[clasbpro_booking_status type="error"]',
		],
	];

	private const ACF_POST_ID = 'clasbpro_options';

	public static function init(): void {
		// Allow the admin to point at custom pages via ACF settings; otherwise fall back to auto-created.
	}

	/**
	 * Run on activation: ensure pages exist, store IDs in ACF options.
	 */
	public static function on_activate(): void {
		foreach ( self::SLUGS as $key => $cfg ) {
			$page_id = self::ensure_page( $cfg['slug'], $cfg['title'], $cfg['content'], $cfg['meta'] );
			if ( $page_id <= 0 ) {
				continue;
			}
			$option_key   = self::ACF_POST_ID . '_' . $cfg['option'];
			$ref_key      = '_' . $option_key;
			$existing     = get_option( $option_key, 0 );
			if ( ! $existing ) {
				update_option( $option_key, $page_id );
				update_option( $ref_key, $cfg['field_key'] );
			}
		}
	}

	/**
	 * Run on uninstall: remove plugin-created result pages and clear stored page IDs.
	 */
	public static function on_uninstall(): void {
		foreach ( self::SLUGS as $cfg ) {
			self::delete_plugin_pages( $cfg );

			$option_key = self::ACF_POST_ID . '_' . $cfg['option'];
			delete_option( $option_key );
			delete_option( '_' . $option_key );
		}
	}

	/**
	 * @param array{slug: string, title: string, option: string, field_key: string, meta: string, content: string} $cfg
	 */
	private static function delete_plugin_pages( array $cfg ): void {
		$deleted = [];

		$by_meta = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => $cfg['meta'],
					'value' => '1',
				],
			],
		] );

		foreach ( $by_meta as $page_id ) {
			$page_id = (int) $page_id;
			if ( $page_id <= 0 || isset( $deleted[ $page_id ] ) ) {
				continue;
			}
			wp_delete_post( $page_id, true );
			$deleted[ $page_id ] = true;
		}

		$option_id = (int) get_option( self::ACF_POST_ID . '_' . $cfg['option'], 0 );
		if ( $option_id > 0 && ! isset( $deleted[ $option_id ] ) && self::is_plugin_created_page( $option_id, $cfg ) ) {
			wp_delete_post( $option_id, true );
			$deleted[ $option_id ] = true;
		}

		$page = get_page_by_path( $cfg['slug'], OBJECT, 'page' );
		if ( $page instanceof \WP_Post && ! isset( $deleted[ (int) $page->ID ] ) && self::is_plugin_created_page( (int) $page->ID, $cfg ) ) {
			wp_delete_post( (int) $page->ID, true );
		}
	}

	/**
	 * @param array{slug: string, title: string, option: string, field_key: string, meta: string, content: string} $cfg
	 */
	private static function is_plugin_created_page( int $page_id, array $cfg ): bool {
		if ( get_post_meta( $page_id, $cfg['meta'], true ) ) {
			return true;
		}

		$post = get_post( $page_id );
		if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
			return false;
		}

		if ( $cfg['slug'] !== $post->post_name ) {
			return false;
		}

		return str_contains( (string) $post->post_content, $cfg['content'] );
	}

	private static function ensure_page( string $slug, string $title, string $content, string $marker_meta ): int {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing instanceof \WP_Post ) {
			return (int) $existing->ID;
		}

		$page_id = wp_insert_post( [
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => $content,
			'meta_input'   => [
				$marker_meta => 1,
			],
		] );

		return is_wp_error( $page_id ) ? 0 : (int) $page_id;
	}

	public static function success_page_id(): int {
		$id = (int) Helpers::get_option( 'success_page', 0 );
		if ( $id > 0 ) {
			return $id;
		}
		$page = get_page_by_path( self::SLUGS['success']['slug'], OBJECT, 'page' );
		return $page ? (int) $page->ID : 0;
	}

	public static function cancel_page_id(): int {
		$id = (int) Helpers::get_option( 'cancel_page', 0 );
		if ( $id > 0 ) {
			return $id;
		}
		$page = get_page_by_path( self::SLUGS['cancelled']['slug'], OBJECT, 'page' );
		return $page ? (int) $page->ID : 0;
	}

	public static function error_page_id(): int {
		$id = (int) Helpers::get_option( 'error_page', 0 );
		if ( $id > 0 ) {
			return $id;
		}
		$page = get_page_by_path( self::SLUGS['error']['slug'], OBJECT, 'page' );
		return $page ? (int) $page->ID : 0;
	}

	public static function success_url( string $session_token = '{CHECKOUT_SESSION_ID}', string $origin = '', string $status_token = '' ): string {
		$id   = self::success_page_id();
		$base = $id ? get_permalink( $id ) : home_url( '/' );

		// Build with origin/token first so add_query_arg can url-encode safely, then append
		// the Stripe placeholder literally so Stripe can substitute {CHECKOUT_SESSION_ID}.
		$query_args = [];
		if ( $origin ) {
			$query_args['from'] = $origin;
		}
		if ( '' !== $status_token ) {
			$query_args['token'] = $status_token;
		}
		$url = $query_args ? add_query_arg( $query_args, $base ) : $base;
		$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'booking=' . $session_token;
		return $url;
	}

	public static function cancel_url( string $origin = '' ): string {
		$id   = self::cancel_page_id();
		$base = $id ? get_permalink( $id ) : home_url( '/' );
		$args = $origin ? [ 'from' => $origin ] : [];
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	public static function error_url( string $reason, string $message = '', string $origin = '' ): string {
		$id   = self::error_page_id();
		$base = $id ? get_permalink( $id ) : home_url( '/' );
		$args = [ 'reason' => $reason ];
		if ( $message ) {
			$args['msg'] = $message;
		}
		if ( $origin ) {
			$args['from'] = $origin;
		}
		return add_query_arg( $args, $base );
	}
}
