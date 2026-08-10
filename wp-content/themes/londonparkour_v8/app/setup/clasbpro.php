<?php
/**
 * Clasbpro integration — CPT surface, taxonomy attach, booking drawer shell.
 *
 * Clasbpro owns `clasbpro_class`. The theme makes it publicly queryable at
 * `/classes/`, attaches `lp_level`, and mounts the shared booking drawer.
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
	$args['has_archive']        = true;
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
	$flag = 'lp_clasbpro_rewrite_v1';
	if ( get_option( $flag ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( $flag, 1, true );
}
add_action( 'init', 'lp_clasbpro_maybe_flush_rewrites', 99 );

/**
 * Localise the booking drawer REST endpoint onto the main bundle.
 */
function lp_clasbpro_localize_booking(): void {
	if ( ! defined( 'CLASBOWPRO_REST_NS' ) ) {
		return;
	}

	wp_localize_script(
		'londonparkour',
		'lpBooking',
		array(
			'restUrl' => esc_url_raw( rest_url( CLASBOWPRO_REST_NS . '/schedule-booking-form' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lp_clasbpro_localize_booking', 20 );

/**
 * Shared right-panel booking drawer (clasbpro form injected by JS).
 */
function lp_clasbpro_booking_drawer(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<el-dialog id="lp-booking-drawer" data-component="booking-drawer">
		<dialog class="fixed inset-0 z-50 m-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent p-0 backdrop:bg-neutral/70">
			<button type="button" command="close" commandfor="lp-booking-drawer" class="fixed inset-0 z-0 cursor-default bg-transparent" aria-label="<?php esc_attr_e( 'Close booking', 'londonparkour_v8' ); ?>"></button>
			<el-dialog-panel class="fixed inset-y-0 right-0 z-10 flex h-full w-full max-w-md flex-col overflow-y-auto bg-neutral border-l border-neutral-content/10 shadow-xl">
				<div class="flex items-center justify-between border-b border-neutral-content/18 px-[22px] py-[16px]">
					<span class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary"><?php esc_html_e( 'Book a session', 'londonparkour_v8' ); ?></span>
					<button type="button" command="close" commandfor="lp-booking-drawer" class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-neutral-content/50 hover:text-neutral-content">
						<?php esc_html_e( 'Close', 'londonparkour_v8' ); ?>
					</button>
				</div>
				<div class="flex-1 px-[22px] py-[20px]" data-lp-booking-mount>
					<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50"><?php esc_html_e( 'Loading…', 'londonparkour_v8' ); ?></p>
				</div>
			</el-dialog-panel>
		</dialog>
	</el-dialog>
	<?php
}
add_action( 'wp_footer', 'lp_clasbpro_booking_drawer', 5 );
