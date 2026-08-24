<?php
/**
 * Main plugin singleton. Wires all components together.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		Secrets::init();
		CPT::init();
		ACF_Fields::init();
		Appointment_Admin::init();
		Bookings::init();
		Packs::init();
		Extra_Fields::init();
		REST::init();
		Shortcode::init();
		Result_Pages::init();
		Reports::init();
		Theme_Loader::init();
		Theme_Preview::init();
		Themes::init();
		Elementor_Integration::init();
		Emails::init();
		Class_Email_Overrides::init();
		Email_Body_Editor::init();
		Scheduled_Emails::init();

		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_form_select_style' ], 999 );
	}

	public function register_assets(): void {
		wp_register_style(
			'clasbpro',
			CLASBOWPRO_URL . 'assets/cbfs-booking.css',
			[],
			CLASBOWPRO_VERSION
		);
		$booking_js_path = CLASBOWPRO_DIR . 'assets/cbfs-booking.js';
		wp_register_script(
			'clasbpro',
			CLASBOWPRO_URL . 'assets/cbfs-booking.js',
			[],
			is_readable( $booking_js_path ) ? (string) filemtime( $booking_js_path ) : CLASBOWPRO_VERSION,
			true
		);
		$select_css_path = CLASBOWPRO_DIR . 'assets/cbfs-form-select.css';
		wp_register_style(
			'clasbpro-form-select',
			CLASBOWPRO_URL . 'assets/cbfs-form-select.css',
			[ 'clasbpro' ],
			is_readable( $select_css_path ) ? (string) filemtime( $select_css_path ) : CLASBOWPRO_VERSION
		);
		$appointment_css_path = CLASBOWPRO_DIR . 'assets/cbfs-appointment-calendar.css';
		$appointment_js_path  = CLASBOWPRO_DIR . 'assets/cbfs-appointment-calendar.js';
		$core_js_path         = CLASBOWPRO_DIR . 'assets/cbfs-calendar-core.js';
		$class_cal_js_path    = CLASBOWPRO_DIR . 'assets/cbfs-class-date-calendar.js';
		wp_register_style(
			'clasbpro-appointment-calendar',
			CLASBOWPRO_URL . 'assets/cbfs-appointment-calendar.css',
			[ 'clasbpro' ],
			is_readable( $appointment_css_path ) ? (string) filemtime( $appointment_css_path ) : CLASBOWPRO_VERSION
		);
		wp_register_script(
			'clasbpro-calendar-core',
			CLASBOWPRO_URL . 'assets/cbfs-calendar-core.js',
			[ 'clasbpro' ],
			is_readable( $core_js_path ) ? (string) filemtime( $core_js_path ) : CLASBOWPRO_VERSION,
			true
		);
		wp_register_script(
			'clasbpro-appointment-calendar',
			CLASBOWPRO_URL . 'assets/cbfs-appointment-calendar.js',
			[ 'clasbpro', 'clasbpro-calendar-core' ],
			is_readable( $appointment_js_path ) ? (string) filemtime( $appointment_js_path ) : CLASBOWPRO_VERSION,
			true
		);
		wp_register_script(
			'clasbpro-class-date-calendar',
			CLASBOWPRO_URL . 'assets/cbfs-class-date-calendar.js',
			[ 'clasbpro', 'clasbpro-calendar-core' ],
			is_readable( $class_cal_js_path ) ? (string) filemtime( $class_cal_js_path ) : CLASBOWPRO_VERSION,
			true
		);
		$schedule_css_path = CLASBOWPRO_DIR . 'assets/cbfs-global-schedule.css';
		$schedule_js_path  = CLASBOWPRO_DIR . 'assets/cbfs-global-schedule.js';
		wp_register_style(
			'clasbpro-global-schedule',
			CLASBOWPRO_URL . 'assets/cbfs-global-schedule.css',
			[ 'clasbpro' ],
			is_readable( $schedule_css_path ) ? (string) filemtime( $schedule_css_path ) : CLASBOWPRO_VERSION
		);
		wp_register_script(
			'clasbpro-global-schedule',
			CLASBOWPRO_URL . 'assets/cbfs-global-schedule.js',
			[ 'clasbpro' ],
			is_readable( $schedule_js_path ) ? (string) filemtime( $schedule_js_path ) : CLASBOWPRO_VERSION,
			true
		);
		$packs_css_path = CLASBOWPRO_DIR . 'assets/cbfs-packs.css';
		$packs_js_path  = CLASBOWPRO_DIR . 'assets/cbfs-packs.js';
		wp_register_style(
			'clasbpro-packs',
			CLASBOWPRO_URL . 'assets/cbfs-packs.css',
			[ 'clasbpro' ],
			is_readable( $packs_css_path ) ? (string) filemtime( $packs_css_path ) : CLASBOWPRO_VERSION
		);
		wp_register_script(
			'clasbpro-packs',
			CLASBOWPRO_URL . 'assets/cbfs-packs.js',
			[ 'clasbpro' ],
			is_readable( $packs_js_path ) ? (string) filemtime( $packs_js_path ) : CLASBOWPRO_VERSION,
			true
		);
		wp_localize_script(
			'clasbpro',
			'CLASBOWPRO',
			[
				'rest_url' => esc_url_raw( rest_url( CLASBOWPRO_REST_NS . '/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'currency' => Helpers::currency_format_config(),
			]
		);

		// Enqueue globally so forms injected later (e.g. Elementor off-canvas AJAX content)
		// still have booking handlers attached.
		wp_enqueue_style( 'clasbpro' );
		wp_enqueue_style( 'clasbpro-packs' );
		wp_enqueue_script( 'clasbpro' );
	}

	/**
	 * Load select chevron styles after theme packs (priority 999).
	 */
	public function enqueue_form_select_style(): void {
		if ( wp_style_is( 'clasbpro-theme-pack', 'registered' ) ) {
			$style = wp_styles()->registered['clasbpro-form-select'] ?? null;
			if ( $style instanceof \_WP_Dependency && ! in_array( 'clasbpro-theme-pack', $style->deps, true ) ) {
				$style->deps[] = 'clasbpro-theme-pack';
			}
		}

		wp_enqueue_style( 'clasbpro-form-select' );
	}
}
