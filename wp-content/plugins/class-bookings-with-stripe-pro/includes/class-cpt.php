<?php
/**
 * Custom post types: class and booking entities.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class CPT {

	public const CLASS_PT         = 'clasbpro_class';
	public const BOOKING_PT       = 'clasbpro_booking';
	public const PACK_PT          = 'clasbpro_pack';
	/** Must stay ≤ 20 chars (wp_posts.post_type). */
	public const PACK_PURCHASE_PT = 'clasbpro_pack_ord';

	public static function init(): void {
		add_action( 'init', [ self::class, 'register' ] );
		add_action( 'admin_menu', [ self::class, 'remove_redundant_add_new_submenus' ], 999 );
		add_filter( 'manage_' . self::CLASS_PT . '_posts_columns', [ self::class, 'class_columns' ] );
		add_action( 'manage_' . self::CLASS_PT . '_posts_custom_column', [ self::class, 'class_column_value' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_class_list_styles' ] );
		add_action( 'post_submitbox_misc_actions', [ self::class, 'render_class_id_in_submitbox' ] );

		add_filter( 'manage_' . self::BOOKING_PT . '_posts_columns', [ self::class, 'booking_columns' ] );
		add_action( 'manage_' . self::BOOKING_PT . '_posts_custom_column', [ self::class, 'booking_column_value' ], 10, 2 );
		add_filter( 'manage_edit-' . self::BOOKING_PT . '_sortable_columns', [ self::class, 'booking_sortable' ] );
		add_action( 'pre_get_posts', [ self::class, 'filter_bookings_by_customer_email' ] );
		add_action( 'admin_notices', [ self::class, 'bookings_customer_email_filter_notice' ] );
	}

	/**
	 * Drop the duplicate “Add Class” sidebar link; use All Classes → Add New instead.
	 */
	public static function remove_redundant_add_new_submenus(): void {
		remove_submenu_page(
			'edit.php?post_type=' . self::CLASS_PT,
			'post-new.php?post_type=' . self::CLASS_PT
		);
	}

	public static function register(): void {
		register_post_type(
			self::CLASS_PT,
			[
				'labels'       => [
					'name'               => __( 'Classes', 'class-bookings-with-stripe-pro' ),
					'singular_name'      => __( 'Class', 'class-bookings-with-stripe-pro' ),
					'add_new_item'       => __( 'Add Class', 'class-bookings-with-stripe-pro' ),
					'edit_item'          => __( 'Edit Class', 'class-bookings-with-stripe-pro' ),
					'new_item'           => __( 'New Class', 'class-bookings-with-stripe-pro' ),
					'view_item'          => __( 'View Class', 'class-bookings-with-stripe-pro' ),
					'search_items'       => __( 'Search Classes', 'class-bookings-with-stripe-pro' ),
					'menu_name'          => __( 'Stripe Class Pro', 'class-bookings-with-stripe-pro' ),
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-calendar-alt',
				'menu_position' => 26,
				'supports'     => [ 'title' ],
				'has_archive'  => false,
				'rewrite'      => false,
				'show_in_rest' => false,
			]
		);

		register_post_type(
			self::BOOKING_PT,
			[
				'labels'       => [
					'name'               => __( 'Bookings', 'class-bookings-with-stripe-pro' ),
					'singular_name'      => __( 'Booking', 'class-bookings-with-stripe-pro' ),
					'edit_item'          => __( 'Edit Booking', 'class-bookings-with-stripe-pro' ),
					'view_item'          => __( 'View Booking', 'class-bookings-with-stripe-pro' ),
					'search_items'       => __( 'Search Bookings', 'class-bookings-with-stripe-pro' ),
					'menu_name'          => __( 'Bookings', 'class-bookings-with-stripe-pro' ),
				],
				'public'       => false,
				'show_ui'      => true,
				// Nest under Classes so bookings + reports live in one menu.
				'show_in_menu' => 'edit.php?post_type=' . self::CLASS_PT,
				'capabilities' => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap' => true,
				'supports'     => [ 'title' ],
				'has_archive'  => false,
				'rewrite'      => false,
				'show_in_rest' => false,
			]
		);

		register_post_type(
			self::PACK_PT,
			[
				'labels'       => [
					'name'          => __( 'Coupons', 'class-bookings-with-stripe-pro' ),
					'singular_name' => __( 'Coupon', 'class-bookings-with-stripe-pro' ),
					'add_new_item'  => __( 'Add Coupon', 'class-bookings-with-stripe-pro' ),
					'edit_item'     => __( 'Edit Coupon', 'class-bookings-with-stripe-pro' ),
					'new_item'      => __( 'New Coupon', 'class-bookings-with-stripe-pro' ),
					'search_items'  => __( 'Search Coupons', 'class-bookings-with-stripe-pro' ),
					'menu_name'     => __( 'Coupons', 'class-bookings-with-stripe-pro' ),
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'edit.php?post_type=' . self::CLASS_PT,
				'supports'     => [ 'title' ],
				'has_archive'  => false,
				'rewrite'      => false,
				'show_in_rest' => false,
			]
		);

		register_post_type(
			self::PACK_PURCHASE_PT,
			[
				'labels'       => [
					'name'          => __( 'Coupon purchases', 'class-bookings-with-stripe-pro' ),
					'singular_name' => __( 'Coupon purchase', 'class-bookings-with-stripe-pro' ),
					'edit_item'     => __( 'Edit Coupon purchase', 'class-bookings-with-stripe-pro' ),
					'search_items'  => __( 'Search Coupon purchases', 'class-bookings-with-stripe-pro' ),
					'menu_name'     => __( 'Coupon purchases', 'class-bookings-with-stripe-pro' ),
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'edit.php?post_type=' . self::CLASS_PT,
				'capabilities' => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap' => true,
				'supports'     => [ 'title' ],
				'has_archive'  => false,
				'rewrite'      => false,
				'show_in_rest' => false,
			]
		);
	}

	public static function class_columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'cb' === $key ) {
				$new['clasbpro_image'] = __( 'Image', 'class-bookings-with-stripe-pro' );
			}
			if ( 'title' === $key ) {
				$new['clasbpro_id']       = __( 'Class ID', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_location'] = __( 'Location', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_when']     = __( 'When', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_type']     = __( 'Type', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_price']    = __( 'Price', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_capacity'] = __( 'Capacity', 'class-bookings-with-stripe-pro' );
				$new['clasbpro_status']   = __( 'Status', 'class-bookings-with-stripe-pro' );
			}
		}
		return $new;
	}

	/**
	 * Narrow image columns on the Classes and Bookings list screens.
	 */
	public static function enqueue_class_list_styles( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$types  = [ self::CLASS_PT, self::BOOKING_PT ];
		$match  = $screen && in_array( $screen->post_type, $types, true );
		if ( ! $match && isset( $_GET['post_type'] ) && in_array( sanitize_key( wp_unslash( (string) $_GET['post_type'] ) ), $types, true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$match = true;
		}
		if ( ! $match ) {
			return;
		}
		wp_add_inline_style(
			'wp-admin',
			'.column-clasbpro_image{width:52px;text-align:center;}' .
			'.column-clasbpro_image img{display:block;margin:0 auto;border-radius:4px;}' .
			'.clasbpro-class-list__no-image{display:inline-block;width:44px;height:44px;background:#f0f0f1;border-radius:4px;vertical-align:middle;}' .
			'.clasbpro-class-type-pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;line-height:1.3;font-weight:600;}' .
			'.clasbpro-class-type-pill--class{background:#e7f3ff;color:#1d4f8f;}' .
			'.clasbpro-class-type-pill--event{background:#efe8ff;color:#5a37a0;}' .
			'.clasbpro-class-type-pill--external{background:#e8f7ef;color:#1d6d43;}'
		);
	}

	private static function render_class_image_column( int $class_id ): void {
		$class  = Helpers::get_class_data( $class_id );
		$img_id = $class && ! empty( $class['image_id'] ) ? (int) $class['image_id'] : 0;
		if ( $img_id && wp_attachment_is_image( $img_id ) ) {
			$alt = trim( (string) get_post_meta( $img_id, '_wp_attachment_image_alt', true ) );
			if ( '' === $alt ) {
				$alt = get_the_title( $class_id );
			}
			echo wp_get_attachment_image(
				$img_id,
				[ 48, 48 ],
				true,
				[
					'style' => 'width:44px;height:44px;object-fit:cover;',
					'alt'   => $alt,
				]
			);
		} else {
			echo '<span class="cbfs-class-list__no-image" aria-hidden="true"></span>';
		}
	}

	public static function class_column_value( string $column, int $post_id ): void {
		if ( 'clasbpro_image' === $column ) {
			self::render_class_image_column( $post_id );
			return;
		}

		$class = Helpers::get_class_data( $post_id );
		if ( ! $class ) {
			return;
		}
		switch ( $column ) {
			case 'clasbpro_id':
				echo '<code>#' . esc_html( (string) $post_id ) . '</code>';
				break;
			case 'clasbpro_location':
				echo esc_html( $class['location'] );
				break;
			case 'clasbpro_when':
				$day  = ! empty( $class['is_one_off_event'] )
					? Helpers::format_date_range( (string) ( $class['start_date'] ?? '' ), (string) ( $class['end_date'] ?? '' ) )
					: ( $class['day_of_week'] ? ucfirst( $class['day_of_week'] ) : '' );
				$time = $class['start_time'] ? Helpers::format_time( $class['start_time'] ) : '';
				$dur  = $class['duration'] ? sprintf( ' (%d min)', $class['duration'] ) : '';
				echo esc_html( trim( "$day $time$dur" ) );
				break;
			case 'clasbpro_type':
				if ( ! empty( $class['use_external_link'] ) ) {
					echo '<span class="cbfs-class-type-pill cbfs-class-type-pill--external">' . esc_html__( 'External link', 'class-bookings-with-stripe-pro' ) . '</span>';
				} elseif ( ! empty( $class['is_one_off_event'] ) ) {
					echo '<span class="cbfs-class-type-pill cbfs-class-type-pill--event">' . esc_html__( 'One-off event', 'class-bookings-with-stripe-pro' ) . '</span>';
				} else {
					echo '<span class="cbfs-class-type-pill cbfs-class-type-pill--class">' . esc_html__( 'Class', 'class-bookings-with-stripe-pro' ) . '</span>';
				}
				break;
			case 'clasbpro_price':
				echo esc_html( Helpers::format_price( $class['price'] ) );
				break;
			case 'clasbpro_capacity':
				echo esc_html( (string) $class['capacity'] );
				break;
			case 'clasbpro_status':
				if ( ! $class['class_active'] ) {
					echo '<strong style="color:#b00;">' . esc_html__( 'Cancelled', 'class-bookings-with-stripe-pro' ) . '</strong>';
				} else {
					echo esc_html__( 'Active', 'class-bookings-with-stripe-pro' );
				}
				break;
		}
	}

	/**
	 * Show a copy-friendly Class ID row on the class edit screen.
	 */
	public static function render_class_id_in_submitbox(): void {
		$post = get_post();
		if ( ! $post || self::CLASS_PT !== $post->post_type ) {
			return;
		}
		$shortcode = sprintf( '[clasbpro_booking class_id="%d"]', (int) $post->ID );
		echo '<div class="misc-pub-section misc-pub-cbfs-class-id">';
		echo '<span>' . esc_html__( 'Class ID:', 'class-bookings-with-stripe-pro' ) . ' <code>#' . esc_html( (string) $post->ID ) . '</code></span>';
		echo '</div>';
		echo '<div class="misc-pub-section misc-pub-cbfs-shortcode">';
		echo '<span>' . esc_html__( 'Shortcode:', 'class-bookings-with-stripe-pro' ) . '</span><br>';
		echo '<code style="display:inline-block; margin-top:6px; user-select:all;">' . esc_html( $shortcode ) . '</code>';
		echo '<p style="margin:6px 0 0; color:#646970; font-size:12px;">' . esc_html__( 'Copy and paste this into any page, post, or Elementor shortcode widget.', 'class-bookings-with-stripe-pro' ) . '</p>';
		echo '</div>';
	}

	public static function booking_columns( array $columns ): array {
		unset( $columns['date'] );
		$new = [
			'cb'           => $columns['cb'] ?? '<input type="checkbox" />',
			'clasbpro_image'     => __( 'Image', 'class-bookings-with-stripe-pro' ),
			'title'        => __( 'Booking', 'class-bookings-with-stripe-pro' ),
			'clasbpro_class'     => __( 'Class', 'class-bookings-with-stripe-pro' ),
			'clasbpro_date'      => __( 'Class date', 'class-bookings-with-stripe-pro' ),
			'clasbpro_customer'  => __( 'Customer', 'class-bookings-with-stripe-pro' ),
			'clasbpro_seats'     => __( 'Seats', 'class-bookings-with-stripe-pro' ),
			'clasbpro_amount'    => __( 'Amount', 'class-bookings-with-stripe-pro' ),
			'clasbpro_pack'      => __( 'Coupon', 'class-bookings-with-stripe-pro' ),
			'clasbpro_status'    => __( 'Status', 'class-bookings-with-stripe-pro' ),
			'clasbpro_stripe'    => __( 'Stripe ID', 'class-bookings-with-stripe-pro' ),
			'clasbpro_created'   => __( 'Created', 'class-bookings-with-stripe-pro' ),
		];
		return $new;
	}

	public static function booking_column_value( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'clasbpro_image':
				$class_id = (int) get_post_meta( $post_id, '_clasbpro_class_id', true );
				if ( $class_id ) {
					self::render_class_image_column( $class_id );
				} else {
					echo '<span class="cbfs-class-list__no-image" aria-hidden="true"></span>';
				}
				break;
			case 'clasbpro_class':
				$class_id = (int) get_post_meta( $post_id, '_clasbpro_class_id', true );
				if ( $class_id ) {
					echo '<a href="' . esc_url( get_edit_post_link( $class_id ) ) . '">' . esc_html( get_the_title( $class_id ) ) . '</a>';
				}
				break;
			case 'clasbpro_date':
				$date = (string) get_post_meta( $post_id, '_clasbpro_class_date', true );
				echo esc_html( Helpers::format_date( $date ) );
				break;
			case 'clasbpro_customer':
				$name  = (string) get_post_meta( $post_id, '_clasbpro_customer_name', true );
				$email = (string) get_post_meta( $post_id, '_clasbpro_customer_email', true );
				echo esc_html( $name );
				if ( $email ) {
					echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
				}
				break;
			case 'clasbpro_seats':
				echo esc_html( (string) (int) get_post_meta( $post_id, '_clasbpro_seats', true ) );
				break;
			case 'clasbpro_amount':
				$pence = (int) get_post_meta( $post_id, '_clasbpro_amount_total', true );
				echo esc_html( Helpers::format_stripe_amount( $pence ) );
				break;
			case 'clasbpro_pack':
				$promo = (string) get_post_meta( $post_id, '_clasbpro_pack_promo_id', true );
				if ( $promo ) {
					echo '<span title="' . esc_attr( $promo ) . '">' . esc_html__( 'Yes', 'class-bookings-with-stripe-pro' ) . '</span>';
				} else {
					echo '—';
				}
				break;
			case 'clasbpro_status':
				$status = (string) get_post_meta( $post_id, '_clasbpro_status', true );
				$colors = [
					'paid'     => '#0a7e1a',
					'pending'  => '#a86b00',
					'expired'  => '#888',
					'refunded' => '#b00',
				];
				$color = $colors[ $status ] ?? '#444';
				echo '<strong style="color:' . esc_attr( $color ) . ';">' . esc_html( ucfirst( $status ?: 'unknown' ) ) . '</strong>';
				break;
			case 'clasbpro_stripe':
				$id = (string) get_post_meta( $post_id, '_clasbpro_stripe_session_id', true );
				if ( $id ) {
					echo '<code style="font-size:11px;">' . esc_html( substr( $id, 0, 18 ) . '…' ) . '</code>';
				}
				break;
			case 'clasbpro_created':
				$post = get_post( $post_id );
				if ( $post ) {
					echo esc_html( get_the_date( 'Y-m-d H:i', $post ) );
				}
				break;
		}
	}

	public static function booking_sortable( array $columns ): array {
		$columns['clasbpro_date']    = '_clasbpro_class_date';
		$columns['clasbpro_status']  = '_clasbpro_status';
		$columns['clasbpro_created'] = 'date';
		return $columns;
	}

	/**
	 * Bookings list URL, optionally filtered to one customer email.
	 */
	public static function bookings_list_url( string $customer_email = '' ): string {
		$args = [
			'post_type' => self::BOOKING_PT,
		];
		$email = sanitize_email( $customer_email );
		if ( '' !== $email ) {
			$args['clasbpro_customer_email'] = $email;
		}
		return admin_url( 'edit.php?' . http_build_query( $args ) );
	}

	/**
	 * Filter the bookings admin list when linked from customer reports.
	 */
	public static function filter_bookings_by_customer_email( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( self::BOOKING_PT !== (string) $query->get( 'post_type' ) ) {
			return;
		}
		$email = self::requested_customer_email_filter();
		if ( '' === $email ) {
			return;
		}

		$meta_query   = $query->get( 'meta_query' );
		$meta_query   = is_array( $meta_query ) ? $meta_query : [];
		$meta_query[] = [
			'key'     => '_clasbpro_customer_email',
			'value'   => $email,
			'compare' => 'LIKE',
		];
		$query->set( 'meta_query', $meta_query );
	}

	public static function bookings_customer_email_filter_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-' . self::BOOKING_PT !== $screen->id ) {
			return;
		}
		$email = self::requested_customer_email_filter();
		if ( '' === $email ) {
			return;
		}

		$clear_url = self::bookings_list_url();
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: customer email address, 2: clear-filter link */
					esc_html__( 'Showing bookings for %1$s. %2$s', 'class-bookings-with-stripe-pro' ),
					'<strong>' . esc_html( $email ) . '</strong>',
					'<a href="' . esc_url( $clear_url ) . '">' . esc_html__( 'Show all bookings', 'class-bookings-with-stripe-pro' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	private static function requested_customer_email_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin list filter.
		if ( ! isset( $_GET['clasbpro_customer_email'] ) ) {
			return '';
		}
		return sanitize_email( (string) wp_unslash( $_GET['clasbpro_customer_email'] ) );
	}
}
