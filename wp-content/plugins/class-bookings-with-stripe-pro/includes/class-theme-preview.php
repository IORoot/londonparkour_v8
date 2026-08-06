<?php
/**
 * Front-end preview page for form theme packs.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Theme_Preview {

	private const PAGE_SLUG = 'clasbpro-theme-preview';
	private const PAGE_META = '_clasbpro_theme_preview_page';

	/**
	 * `default` or a published class post ID string.
	 */
	private static string $preview_class_source = 'default';

	public static function init(): void {
		add_shortcode( 'clasbpro_theme_preview', [ self::class, 'render_shortcode' ] );
		add_filter( 'body_class', [ self::class, 'filter_body_class' ] );
		add_filter( 'show_admin_bar', [ self::class, 'filter_show_admin_bar' ] );
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_embed_styles' ], 999 );
		add_action( 'init', [ self::class, 'capture_preview_class' ], 6 );
	}

	public static function capture_preview_class(): void {
		if ( ! Theme_Loader::is_preview_active() ) {
			return;
		}

		$raw = isset( $_GET['clasbpro_preview_class'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( (string) $_GET['clasbpro_preview_class'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 'default';

		self::$preview_class_source = self::normalise_class_source( $raw );
	}

	public static function get_preview_class_source(): string {
		return self::$preview_class_source;
	}

	/**
	 * @return array<int, array{id: string, name: string}>
	 */
	public static function list_preview_classes(): array {
		$options = [
			[
				'id'   => 'default',
				'name' => __( 'Default sample class', 'class-bookings-with-stripe-pro' ),
			],
		];

		$posts = get_posts( [
			'post_type'      => CPT::CLASS_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$options[] = [
				'id'   => (string) $post->ID,
				'name' => $post->post_title,
			];
		}

		return $options;
	}

	public static function normalise_class_source( string $raw ): string {
		if ( 'default' === $raw || '' === $raw ) {
			return 'default';
		}

		$class_id = absint( $raw );
		if ( $class_id <= 0 ) {
			return 'default';
		}

		$post = get_post( $class_id );
		if ( ! $post instanceof \WP_Post || CPT::CLASS_PT !== $post->post_type || 'publish' !== $post->post_status ) {
			return 'default';
		}

		return (string) $class_id;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function default_class_data(): array {
		return [
			'id'                   => 0,
			'name'                 => __( 'Sample Yoga Class', 'class-bookings-with-stripe-pro' ),
			'description'          => __( 'A recurring Saturday morning class for theme previews.', 'class-bookings-with-stripe-pro' ),
			'image_id'             => 0,
			'use_external_link'    => false,
			'external_link_url'    => '',
			'location'             => __( 'Studio 1', 'class-bookings-with-stripe-pro' ),
			'schedule_type'        => 'recurring',
			'is_one_off_event'     => false,
			'day_of_week'          => 'saturday',
			'start_date'           => '',
			'end_date'             => '',
			'start_time'           => '10:00',
			'duration'             => 60,
			'price'                => 25.0,
			'capacity'             => 12,
			'show_seats_remaining' => true,
			'upcoming_dates_count' => 3,
			'class_active'         => true,
			'cancelled_dates'      => [],
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function class_data_for_source( string $source ): ?array {
		$source = self::normalise_class_source( $source );

		if ( 'default' === $source ) {
			return self::default_class_data();
		}

		return Helpers::get_class_data( (int) $source );
	}

	public static function render_booking_form( string $class_source ): string {
		$class_data = self::class_data_for_source( $class_source );
		if ( ! $class_data ) {
			return '<div class="cbfs-form cbfs-form--error">' . esc_html__( 'Preview class not found.', 'class-bookings-with-stripe-pro' ) . '</div>';
		}

		$class_data = apply_filters( 'clasbpro_booking_class_data', $class_data, [ 'preview' => '1' ] );

		wp_enqueue_style( 'clasbpro' );
		wp_enqueue_script( 'clasbpro' );

		$dates_count = ! empty( $class_data['is_one_off_event'] )
			? 1
			: Helpers::class_upcoming_dates_count( $class_data );
		$dates       = Bookings::next_available_dates( $class_data, $dates_count );
		$max_seats   = $dates ? max( 0, (int) $dates[0]['remaining'] ) : 0;

		$template_args = apply_filters(
			'clasbpro_booking_template_args',
			[
				'class_data'      => $class_data,
				'dates'           => $dates,
				'show_heading'    => true,
				'max_seats_today' => $max_seats,
				'atts'            => [ 'heading' => '1', 'preview' => '1' ],
			],
			[ 'heading' => '1', 'preview' => '1' ]
		);

		return Booking_Form_View::render_html( $template_args );
	}

	/**
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public static function filter_body_class( array $classes ): array {
		if ( self::is_embed_request() ) {
			$classes[] = 'clasbpro-preview-embed';
		}
		return $classes;
	}

	public static function filter_show_admin_bar( bool $show ): bool {
		if ( self::is_embed_request() ) {
			return false;
		}
		return $show;
	}

	public static function is_embed_request(): bool {
		return Theme_Loader::is_preview_active()
			&& ! empty( $_GET['clasbpro_preview_embed'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public static function enqueue_embed_styles(): void {
		if ( ! self::is_embed_request() ) {
			return;
		}

		wp_register_style( 'clasbpro-preview-embed', false, [], CLASBOWPRO_VERSION );
		wp_enqueue_style( 'clasbpro-preview-embed' );
		wp_add_inline_style(
			'clasbpro-preview-embed',
			'html{margin-top:0!important}body.clasbpro-preview-embed{margin:0;padding:16px;background:#fff;overflow-x:hidden}'
			. '#wpadminbar,body.clasbpro-preview-embed .site-header,body.clasbpro-preview-embed .site-footer,'
			. 'body.clasbpro-preview-embed .wp-block-template-part,body.clasbpro-preview-embed .wp-block-post-title,'
			. 'body.clasbpro-preview-embed nav,body.clasbpro-preview-embed .entry-header,'
			. 'body.clasbpro-preview-embed .wp-site-blocks>header,body.clasbpro-preview-embed .wp-site-blocks>footer{display:none!important}'
		);
	}

	public static function on_activate(): void {
		self::ensure_page();
	}

	/**
	 * Run on uninstall: remove the auto-created theme preview page.
	 */
	public static function on_uninstall(): void {
		$posts = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => self::PAGE_META,
					'value' => '1',
				],
			],
		] );

		foreach ( $posts as $page_id ) {
			wp_delete_post( (int) $page_id, true );
		}

		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( ! $page instanceof \WP_Post ) {
			return;
		}

		if ( get_post_meta( $page->ID, self::PAGE_META, true ) ) {
			wp_delete_post( (int) $page->ID, true );
			return;
		}

		if ( str_contains( (string) $page->post_content, '[clasbpro_theme_preview]' ) && self::PAGE_SLUG === $page->post_name ) {
			wp_delete_post( (int) $page->ID, true );
		}
	}

	public static function ensure_page(): int {
		$existing = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		if ( $existing instanceof \WP_Post ) {
			return (int) $existing->ID;
		}

		$page_id = wp_insert_post( [
			'post_title'   => __( 'Booking Form Theme Preview', 'class-bookings-with-stripe-pro' ),
			'post_name'    => self::PAGE_SLUG,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[clasbpro_theme_preview]',
			'meta_input'   => [
				self::PAGE_META => 1,
			],
		] );

		return is_wp_error( $page_id ) ? 0 : (int) $page_id;
	}

	public static function page_id(): int {
		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );
		return $page ? (int) $page->ID : 0;
	}

	/**
	 * @deprecated Use list_preview_classes() or class_data_for_source().
	 */
	public static function preview_class_id(): int {
		$posts = get_posts( [
			'post_type'      => CPT::CLASS_PT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );

		return ! empty( $posts[0] ) ? (int) $posts[0] : 0;
	}

	public static function preview_url( string $theme_slug, bool $embed = false, string $class_source = 'default' ): string {
		$page_id = self::page_id();
		if ( $page_id <= 0 ) {
			$page_id = self::ensure_page();
		}

		$base = $page_id > 0 ? get_permalink( $page_id ) : home_url( '/' );
		$slug = sanitize_key( $theme_slug );

		$args = [
			'clasbpro_theme_preview' => $slug,
			'clasbpro_preview_class' => self::normalise_class_source( $class_source ),
			'_wpnonce'               => wp_create_nonce( 'clasbpro_theme_preview_' . $slug ),
		];

		if ( $embed ) {
			$args['clasbpro_preview_embed'] = '1';
		}

		return add_query_arg( $args, $base );
	}

	/**
	 * @param array<string, string> $atts
	 */
	public static function render_shortcode( array $atts = [] ): string {
		if ( ! Theme_Loader::is_preview_active() ) {
			return '<p class="clasbpro-theme-preview-notice">' . esc_html__( 'Select a theme from the Themes screen and open Live preview.', 'class-bookings-with-stripe-pro' ) . '</p>';
		}

		$html = self::render_booking_form( self::get_preview_class_source() );

		if ( self::is_embed_request() ) {
			return $html;
		}

		$slug  = Theme_Loader::get_preview_slug();
		$theme = Theme_Registry::get( $slug );
		$name  = $theme['name'] ?? $slug;

		$banner = sprintf(
			'<div class="clasbpro-theme-preview-banner" style="padding:12px 16px;margin:0 0 24px;background:#f0f6fc;border:1px solid #c3d9ed;border-radius:8px;font-size:14px;"><strong>%s</strong> — %s</div>',
			esc_html__( 'Theme preview', 'class-bookings-with-stripe-pro' ),
			esc_html( (string) $name )
		);

		return $banner . $html;
	}
}
