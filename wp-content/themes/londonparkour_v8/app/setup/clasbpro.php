<?php
/**
 * Clasbpro integration — CPT surface, taxonomy attach, booking drawer shell.
 *
 * Clasbpro owns `clasbpro_class`. Group-class singles live at `/classes/{slug}`;
 * appointment (1:1) products 301 to `/private-coaching/`. The listings archive
 * is `/all-classes/` so `/classes/` can be the Agenda page.
 * Attaches `lp_level` and mounts the shared booking drawer.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class CPT slug — clasbpro's, never a theme-owned shadow.
 */
function lp_class_post_type(): string {
	return 'clasbpro_class';
}

/**
 * Public archive + rewrite + editor supports on clasbpro's class CPT.
 *
 * @param array  $args      register_post_type args.
 * @param string $post_type Post type name.
 * @return array
 */
function lp_clasbpro_class_args( array $args, string $post_type ): array {
	if ( 'clasbpro_class' !== $post_type ) {
		return $args;
	}

	$args['public']             = true;
	$args['publicly_queryable'] = true;
	$args['show_ui']            = true;
	// Archive slug must not be `classes` — that path is the Agenda page.
	$args['has_archive']        = 'all-classes';
	$args['rewrite']            = array(
		'slug'       => 'classes',
		'with_front' => false,
	);
	$args['supports']           = array_values(
		array_unique(
			array_merge(
				(array) ( $args['supports'] ?? array( 'title' ) ),
				array( 'editor', 'thumbnail', 'excerpt' )
			)
		)
	);

	return $args;
}
add_filter( 'register_post_type_args', 'lp_clasbpro_class_args', 10, 2 );

/**
 * Attach lp_level to clasbpro_class (taxonomy JSON also lists it after rebuild).
 */
function lp_clasbpro_register_level_taxonomy(): void {
	if ( ! taxonomy_exists( 'lp_level' ) || ! post_type_exists( 'clasbpro_class' ) ) {
		return;
	}
	register_taxonomy_for_object_type( 'lp_level', 'clasbpro_class' );
}
add_action( 'init', 'lp_clasbpro_register_level_taxonomy', 20 );

/**
 * Flush rewrites once after the theme starts exposing clasbpro_class publicly.
 */
function lp_clasbpro_maybe_flush_rewrites(): void {
	$flag = 'lp_clasbpro_rewrite_v2';
	if ( get_option( $flag ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( $flag, 1, true );
}
add_action( 'init', 'lp_clasbpro_maybe_flush_rewrites', 99 );

/**
 * Prefer theme-owned clasbpro form chrome when the Concourse pack is present.
 */
function lp_clasbpro_enable_theme_forms(): void {
	if ( ! class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader' ) ) {
		return;
	}

	$pack = get_stylesheet_directory() . '/class-bookings-with-stripe/style.css';
	if ( ! is_readable( $pack ) ) {
		return;
	}

	$flag = 'lp_clasbpro_theme_forms_v1';
	if ( get_option( $flag ) ) {
		return;
	}

	\IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::update_settings(
		array(
			'theme_source' => 'theme',
		)
	);
	update_option( $flag, 1, true );
}
add_action( 'init', 'lp_clasbpro_enable_theme_forms', 30 );

/**
 * Localise the panel drawer REST endpoint onto the main bundle.
 */
function lp_clasbpro_localize_booking(): void {
	if ( ! defined( 'CLASBOWPRO_REST_NS' ) ) {
		return;
	}

	// Drawer injects shortcode HTML after load — assets must already be present.
	wp_enqueue_style( 'clasbpro' );
	wp_enqueue_style( 'clasbpro-packs' );
	wp_enqueue_script( 'clasbpro' );
	wp_enqueue_script( 'clasbpro-packs' );
	if ( wp_style_is( 'clasbpro-appointment-calendar', 'registered' ) ) {
		wp_enqueue_style( 'clasbpro-appointment-calendar' );
	}
	if ( wp_script_is( 'clasbpro-calendar-core', 'registered' ) ) {
		wp_enqueue_script( 'clasbpro-calendar-core' );
	}
	if ( wp_script_is( 'clasbpro-appointment-calendar', 'registered' ) ) {
		wp_enqueue_script( 'clasbpro-appointment-calendar' );
	}
	if ( wp_script_is( 'clasbpro-class-date-calendar', 'registered' ) ) {
		wp_enqueue_script( 'clasbpro-class-date-calendar' );
	}
	if ( class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader' ) ) {
		\IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader::enqueue_theme_style();
	}

	wp_localize_script(
		'londonparkour',
		'lpBooking',
		array(
			'panelFormUrl' => esc_url_raw( rest_url( CLASBOWPRO_REST_NS . '/panel-form' ) ),
			'restUrl'      => esc_url_raw( rest_url( CLASBOWPRO_REST_NS . '/schedule-booking-form' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'labels'       => array(
				'booking' => __( 'Book a session', 'londonparkour_v8' ),
				'coupon'  => __( 'Buy a coupon', 'londonparkour_v8' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lp_clasbpro_localize_booking', 20 );

/**
 * Shared right-panel drawer (clasbpro shortcode HTML injected by JS).
 */
function lp_clasbpro_booking_drawer(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<el-dialog data-component="booking-drawer">
		<dialog id="lp-booking-drawer" aria-label="<?php esc_attr_e( 'Booking panel', 'londonparkour_v8' ); ?>" class="fixed inset-0 z-50 m-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent p-0 backdrop:bg-neutral/70">
			<button type="button" command="close" commandfor="lp-booking-drawer" class="fixed inset-0 z-0 cursor-default bg-transparent" aria-label="<?php esc_attr_e( 'Close panel', 'londonparkour_v8' ); ?>"></button>
			<el-dialog-panel class="fixed inset-y-0 right-0 z-10 flex h-full w-full max-w-md flex-col overflow-y-auto bg-secondary border-l border-neutral-content/10 shadow-xl">
				<div class="flex items-center justify-between border-b border-neutral-content/18 px-[22px] py-[16px]">
					<span class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary" data-lp-drawer-title><?php esc_html_e( 'Book a session', 'londonparkour_v8' ); ?></span>
					<button type="button" command="close" commandfor="lp-booking-drawer" class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-neutral-content/50 hover:text-neutral-content">
						<?php esc_html_e( 'Close', 'londonparkour_v8' ); ?>
					</button>
				</div>
				<div class="flex-1 min-h-0" data-lp-booking-mount>
					<p class="px-[28px] py-[20px] font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50"><?php esc_html_e( 'Loading…', 'londonparkour_v8' ); ?></p>
				</div>
			</el-dialog-panel>
		</dialog>
	</el-dialog>
	<?php
}
add_action( 'wp_footer', 'lp_clasbpro_booking_drawer', 5 );

/**
 * Coerce a clasbpro money field (pence int, float, or "£12.00") to a float major unit.
 *
 * @param mixed $raw Raw amount.
 */
function lp_clasbpro_money_to_float( $raw ): float {
	if ( is_int( $raw ) || is_float( $raw ) ) {
		$n = (float) $raw;
		// Heuristic: stripe pence stored as int > 1000-ish still works as major if already major.
		return $n;
	}
	$s = preg_replace( '/[^0-9.]/', '', (string) $raw );
	return '' === $s ? 0.0 : (float) $s;
}

/**
 * GA4 purchase marker on clasbpro success status renders.
 *
 * @param array<string,mixed> $template_args Status view args.
 * @param string              $layout_path   Unused.
 */
function lp_clasbpro_purchase_marker( array $template_args, string $layout_path = '' ): void {
	unset( $layout_path );

	$type = (string) ( $template_args['type'] ?? '' );
	if ( 'success' !== $type ) {
		return;
	}

	$kind      = (string) ( $template_args['kind'] ?? 'booking' );
	$booking   = isset( $template_args['booking'] ) && is_array( $template_args['booking'] ) ? $template_args['booking'] : null;
	$purchase  = isset( $template_args['purchase'] ) && is_array( $template_args['purchase'] ) ? $template_args['purchase'] : null;
	$session   = (string) ( $template_args['session_id'] ?? '' );
	$currency  = 'GBP';
	$value     = 0.0;
	$txn       = '';
	$items     = array();

	if ( 'coupon' === $kind && $purchase ) {
		$txn     = $session ? $session : ( 'pack-' . (int) ( $purchase['purchase_id'] ?? $template_args['purchase_id'] ?? 0 ) );
		$value   = lp_clasbpro_money_to_float( $purchase['amount_total'] ?? 0 );
		$pack_id = (int) ( $purchase['pack_id'] ?? 0 );
		$items[] = array(
			'item_id'       => 'pack:' . $pack_id,
			'item_name'     => (string) ( $purchase['pack_name'] ?? 'Coupon' ),
			'item_category' => 'coupon',
			'price'         => $value,
			'quantity'      => 1,
		);
	} elseif ( $booking ) {
		$booking_id = (int) ( $booking['booking_id'] ?? 0 );
		$txn        = $session ? $session : ( 'booking-' . $booking_id );
		$value      = lp_clasbpro_money_to_float( $booking['amount_total'] ?? 0 );
		$class_id   = $booking_id ? (int) get_post_meta( $booking_id, '_clasbpro_class_id', true ) : 0;
		$items[]    = array(
			'item_id'       => 'class:' . $class_id,
			'item_name'     => (string) ( $booking['class_name'] ?? 'Class' ),
			'item_category' => 'class',
			'price'         => $value,
			'quantity'      => max( 1, (int) ( $booking['seats'] ?? 1 ) ),
		);
	} else {
		return;
	}

	if ( '' === $txn ) {
		return;
	}

	printf(
		'<div hidden data-lp-purchase="%1$s" data-lp-purchase-value="%2$s" data-lp-purchase-currency="%3$s" data-lp-purchase-items="%4$s"></div>',
		esc_attr( $txn ),
		esc_attr( (string) $value ),
		esc_attr( $currency ),
		esc_attr( wp_json_encode( $items ) )
	);
}
add_action( 'clasbpro_after_render_status_template', 'lp_clasbpro_purchase_marker', 10, 2 );

/**
 * Full-bleed template for Stripe return pages so the shortcode is not crushed
 * inside page.php's 720px prose well.
 *
 * @param string $template Located template.
 */
function lp_clasbpro_status_template_include( string $template ): string {
	if ( ! is_page( array( 'booking-confirmed', 'booking-cancelled', 'booking-error' ) ) ) {
		return $template;
	}

	$lp_file = get_theme_file_path( 'templates/booking-status.php' );
	return is_readable( $lp_file ) ? $lp_file : $template;
}
add_filter( 'template_include', 'lp_clasbpro_status_template_include' );

/**
 * Keep appointment (1:1) booking products out of the class sitemap.
 *
 * @param array  $args      WP_Query args for the sitemap.
 * @param string $post_type Post type being listed.
 * @return array
 */
function lp_clasbpro_sitemap_query_args( array $args, string $post_type ): array {
	if ( $post_type !== lp_class_post_type() || ! function_exists( 'lp_class_is_appointment' ) ) {
		return $args;
	}

	$exclude = array();
	$ids     = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		if ( lp_class_is_appointment( (int) $id ) ) {
			$exclude[] = (int) $id;
		}
	}
	if ( ! $exclude ) {
		return $args;
	}

	$existing           = array_map( 'intval', (array) ( $args['post__not_in'] ?? array() ) );
	$args['post__not_in'] = array_values( array_unique( array_merge( $existing, $exclude ) ) );

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'lp_clasbpro_sitemap_query_args', 10, 2 );
