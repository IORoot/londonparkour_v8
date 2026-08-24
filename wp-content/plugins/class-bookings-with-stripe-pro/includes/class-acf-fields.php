<?php
/**
 * Register ACF field groups and the options page.
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class ACF_Fields {

	private const SETTINGS_MENU_SLUG = 'clasbowi-settings';
	private const SETTINGS_POST_ID   = 'clasbpro_options';

	/** @var array<string, mixed> */
	private static array $settings_form_acf_stash = [];

	public static function init(): void {
		add_action( 'acf/init', [ self::class, 'register_options_page' ] );
		add_action( 'acf/include_fields', [ self::class, 'register_field_groups' ] );
		add_filter( 'acf/load_field/key=field_clasbpro_b_summary', [ self::class, 'filter_booking_summary_field_format' ], 5 );
		add_filter( 'acf/load_field/key=field_clasbpro_b_summary', [ self::class, 'populate_booking_summary_field' ], 10 );
		add_filter( 'acf/load_field/key=field_clasbpro_pp_summary', [ self::class, 'filter_booking_summary_field_format' ], 5 );
		add_filter( 'acf/load_field/key=field_clasbpro_pp_summary', [ self::class, 'populate_pack_purchase_summary_field' ], 10 );
		add_action( 'acf/render_field/key=field_clasbpro_cancelled_dates_fallback', [ self::class, 'render_cancelled_dates_quick_add' ] );
		add_filter( 'acf/load_value/name=schedule_classes', [ self::class, 'load_schedule_classes_value' ], 10, 3 );
		add_filter( 'acf/update_value/name=schedule_classes', [ self::class, 'update_schedule_classes_value' ], 10, 3 );
		add_filter( 'acf/fields/relationship/query/key=field_clasbpro_schedule_classes', [ self::class, 'schedule_classes_relationship_query' ], 10, 3 );
		add_filter( 'acf/fields/relationship/result/key=field_clasbpro_schedule_classes', [ self::class, 'schedule_classes_relationship_result' ], 10, 4 );

		$settings_screen_hook = CPT::CLASS_PT . '_page_' . self::SETTINGS_MENU_SLUG;
		add_action( 'load-' . $settings_screen_hook, [ self::class, 'on_load_booking_settings_screen' ] );

		add_action( 'admin_enqueue_scripts', [ self::class, 'maybe_enqueue_stripe_class_edit_admin' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'maybe_enqueue_booking_edit_admin' ] );
		add_action( 'admin_notices', [ self::class, 'scheduled_email_admin_notices' ] );
		add_action( 'acf/render_field/key=field_clasbpro_email_subtabs_nav', [ self::class, 'render_email_subtabs_nav' ] );
		add_action( 'acf/render_field/key=field_clasbpro_class_email_subtabs_nav', [ self::class, 'render_class_email_subtabs_nav' ] );
		add_action( 'acf/render_field/key=field_clasbpro_help_subtabs_nav', [ self::class, 'render_help_subtabs_nav' ] );
		add_filter( 'acf/load_value/name=schedule_type', [ self::class, 'load_schedule_type_value' ], 10, 3 );
		add_filter( 'acf/update_value/name=schedule_type', [ self::class, 'update_schedule_type_value' ], 10, 3 );
		add_filter( 'acf/load_field/key=field_clasbpro_price', [ self::class, 'filter_price_field_for_currency' ] );
		add_filter( 'acf/load_field/key=field_clasbpro_stripe_currency', [ self::class, 'load_stripe_currency_field' ] );
		add_filter( 'acf/load_value/name=stripe_currency', [ self::class, 'load_stripe_currency_value' ], 10, 3 );
		add_filter( 'acf/pre_save_post', [ self::class, 'persist_stripe_currency_pre_save' ], 0, 2 );
		add_filter( 'acf/pre_save_post', [ self::class, 'stash_settings_form_post' ], -1, 2 );
		add_filter( 'acf/form/allowed_field_keys', [ self::class, 'allow_settings_form_field_keys' ], 10, 2 );
		add_filter( 'acf/update_value/name=stripe_currency', [ self::class, 'detect_stripe_currency_change' ], 5, 3 );
		add_filter( 'acf/update_value/name=stripe_currency', [ self::class, 'persist_stripe_currency_value' ], 20, 3 );
		add_filter( 'acf/update_value/name=calendar_icon', [ self::class, 'sanitize_calendar_icon_value' ], 10, 3 );
		add_action( 'acf/save_post', [ self::class, 'persist_stripe_currency_from_request' ], 5 );
		add_action( 'acf/pre_save_post', [ self::class, 'persist_schedule_classes_pre_save' ], 0, 2 );
		add_action( 'acf/save_post', [ self::class, 'persist_schedule_classes_from_request' ], 5 );
		add_action( 'acf/submit_form', [ self::class, 'persist_schedule_classes_on_submit_form' ], 5, 2 );
		add_action( 'acf/submit_form', [ self::class, 'persist_settings_form_on_submit' ], 99, 2 );

		// ACF Free has no built-in options pages, so provide a native admin fallback.
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			add_action( 'admin_menu', [ self::class, 'register_native_settings_page' ], 20 );
		}
	}

	public static function register_options_page(): void {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}
		acf_add_options_page(
			[
				'page_title' => __( 'Settings', 'class-bookings-with-stripe-pro' ),
				'menu_title' => __( 'Settings', 'class-bookings-with-stripe-pro' ),
				'menu_slug'  => self::SETTINGS_MENU_SLUG,
				'parent_slug' => 'edit.php?post_type=' . CPT::CLASS_PT,
				'capability' => 'manage_options',
				'post_id'    => self::SETTINGS_POST_ID,
				'autoload'   => true,
			]
		);
	}

	public static function register_native_settings_page(): void {
		$hook = add_submenu_page(
			'edit.php?post_type=' . CPT::CLASS_PT,
			__( 'Settings', 'class-bookings-with-stripe-pro' ),
			__( 'Settings', 'class-bookings-with-stripe-pro' ),
			'manage_options',
			self::SETTINGS_MENU_SLUG,
			[ self::class, 'render_native_settings_page' ]
		);

		if ( $hook ) {
			add_action( 'load-' . $hook, [ self::class, 'on_load_native_settings_screen' ] );
		}
	}

	public static function on_load_native_settings_screen(): void {
		if ( function_exists( 'acf_form_head' ) ) {
			acf_form_head();
		}
		self::enqueue_booking_settings_admin_assets();
	}

	public static function render_native_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Settings', 'class-bookings-with-stripe-pro' ) . '</h1>';

		if ( ! function_exists( 'acf_form' ) ) {
			echo '<p>' . esc_html__( 'ACF form renderer is unavailable. Please activate Advanced Custom Fields.', 'class-bookings-with-stripe-pro' ) . '</p>';
			echo '</div>';
			return;
		}

		self::output_settings_intro_panel( 'native' );

		acf_form(
			[
				'post_id'               => self::SETTINGS_POST_ID,
				'field_groups'          => [ 'group_clasbpro_settings' ],
				'form_attributes'       => [ 'class' => 'acf-form clasbpro-settings-form' ],
				'html_submit_button'    => '<input type="submit" class="button button-primary button-large" value="%s" />',
				'html_submit_spinner'   => '<span class="spinner"></span>',
				'updated_message'       => __( 'Settings saved.', 'class-bookings-with-stripe-pro' ),
				'submit_value'          => __( 'Save Settings', 'class-bookings-with-stripe-pro' ),
				'instruction_placement' => 'field',
				'label_placement'       => 'top',
				'field_el'              => 'div',
				'kses'                  => true,
			]
		);

		echo '</div>';
	}

	/**
	 * Settings screen: body class (reliable CSS scope), assets, intro meta box.
	 *
	 * Runs on load-{screen} so we never depend on get_current_screen() inside acf/input/admin_head.
	 */
	public static function on_load_booking_settings_screen(): void {
		self::enqueue_booking_settings_admin_assets();

		if ( function_exists( 'acf_add_options_page' ) ) {
			add_meta_box(
				'clasbpro-settings-intro',
				'',
				[ self::class, 'render_settings_intro_metabox' ],
				'acf_options_page',
				'normal',
				'high'
			);
		}
	}

	/**
	 * Enqueue Settings screen styles and scripts (ACF options + native fallback).
	 */
	public static function enqueue_booking_settings_admin_assets(): void {
		add_filter( 'admin_body_class', [ self::class, 'filter_booking_settings_body_class' ] );

		wp_enqueue_style( 'dashicons' );
		$code_editor_settings = wp_enqueue_code_editor(
			[
				'type'       => 'text/html',
				'codemirror' => [
					'indentUnit' => 2,
					'tabSize'    => 2,
				],
			]
		);
		$settings_deps = [];
		if ( false !== $code_editor_settings ) {
			wp_enqueue_script( 'code-editor' );
			wp_enqueue_style( 'code-editor' );
			$settings_deps[] = 'code-editor';
		}
		$settings_css = CLASBOWPRO_DIR . 'assets/cbfs-booking-admin-settings.css';
		$settings_js  = CLASBOWPRO_DIR . 'assets/cbfs-booking-admin-settings.js';
		$settings_ver = is_readable( $settings_css ) ? (string) filemtime( $settings_css ) : CLASBOWPRO_VERSION;

		wp_enqueue_style(
			'clasbowi-admin-settings',
			CLASBOWPRO_URL . 'assets/cbfs-booking-admin-settings.css',
			[],
			$settings_ver
		);
		wp_add_inline_style(
			'clasbowi-admin-settings',
			'.clasbpro-booking-settings.clasbpro-emails-tab-active.clasbpro-email-subtabs-ready.clasbpro-email-view-reminders .clasbpro-scheduled-email-controls-row.clasbpro-email-section-reminders .acf-field.acf-hidden,'
			. '.clasbpro-booking-settings.clasbpro-emails-tab-active.clasbpro-email-subtabs-ready.clasbpro-email-view-post-class .clasbpro-scheduled-email-controls-row.clasbpro-email-section-post-class .acf-field.acf-hidden,'
			. '.clasbpro-class-edit.clasbpro-class-emails-active.clasbpro-email-subtabs-ready.clasbpro-email-view-reminders .clasbpro-scheduled-email-controls-row.clasbpro-email-section-reminders .acf-field.acf-hidden,'
			. '.clasbpro-class-edit.clasbpro-class-emails-active.clasbpro-email-subtabs-ready.clasbpro-email-view-post-class .clasbpro-scheduled-email-controls-row.clasbpro-email-section-post-class .acf-field.acf-hidden{display:flex!important}'
			. '.clasbpro-booking-settings.clasbpro-stripe-tab-active .clasbpro-stripe-keys-panel .acf-field.acf-hidden,.clasbpro-booking-settings.clasbpro-stripe-tab-active .clasbpro-stripe-keys-panel>.acf-field[hidden]{display:block!important}.clasbpro-booking-settings.clasbpro-stripe-tab-active .clasbpro-stripe-keys-panel>.acf-field{display:block}'
		);
		wp_enqueue_script(
			'clasbowi-admin-settings',
			CLASBOWPRO_URL . 'assets/cbfs-booking-admin-settings.js',
			$settings_deps,
			is_readable( $settings_js ) ? (string) filemtime( $settings_js ) : $settings_ver,
			true
		);
		if ( false !== $code_editor_settings ) {
			wp_add_inline_script(
				'clasbowi-admin-settings',
				'window.clasbproEmailCodeEditor = ' . wp_json_encode( [ 'cmSettings' => $code_editor_settings ] ) . ';',
				'before'
			);
		}
	}

	/**
	 * @param string $classes Space-prefixed admin body classes.
	 */
	public static function filter_booking_settings_body_class( string $classes ): string {
		return $classes . ' clasbpro-booking-settings';
	}

	/**
	 * Class add/edit screen: same admin design language as Settings.
	 */
	public static function maybe_enqueue_stripe_class_edit_admin(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::CLASS_PT !== $screen->post_type ) {
			return;
		}
		if ( ! in_array( $screen->base, [ 'post', 'post-new' ], true ) ) {
			return;
		}

		add_filter( 'admin_body_class', [ self::class, 'filter_stripe_class_edit_body_class' ] );

		wp_enqueue_style( 'dashicons' );
		$settings_css = CLASBOWPRO_DIR . 'assets/cbfs-booking-admin-settings.css';
		wp_enqueue_style(
			'clasbowi-admin-settings',
			CLASBOWPRO_URL . 'assets/cbfs-booking-admin-settings.css',
			[],
			is_readable( $settings_css ) ? (string) filemtime( $settings_css ) : CLASBOWPRO_VERSION
		);
		$rules_css = CLASBOWPRO_DIR . 'assets/cbfs-scheduled-email-rules.css';
		wp_enqueue_style(
			'clasbpro-scheduled-email-rules',
			CLASBOWPRO_URL . 'assets/cbfs-scheduled-email-rules.css',
			[],
			is_readable( $rules_css ) ? (string) filemtime( $rules_css ) : CLASBOWPRO_VERSION
		);
		wp_enqueue_script(
			'clasbowi-cancelled-dates',
			CLASBOWPRO_URL . 'assets/cbfs-booking-cancelled-dates.js',
			[],
			CLASBOWPRO_VERSION,
			true
		);
		wp_enqueue_script(
			'clasbowi-class-metabox',
			CLASBOWPRO_URL . 'assets/cbfs-booking-class-metabox.js',
			[ 'jquery' ],
			CLASBOWPRO_VERSION,
			true
		);

		$code_editor_settings = wp_enqueue_code_editor(
			[
				'type'       => 'text/html',
				'codemirror' => [
					'indentUnit' => 2,
					'tabSize'    => 2,
				],
			]
		);
		$class_emails_deps = [];
		if ( false !== $code_editor_settings ) {
			wp_enqueue_script( 'code-editor' );
			wp_enqueue_style( 'code-editor' );
			$class_emails_deps[] = 'code-editor';
		}
		$class_emails_js = CLASBOWPRO_DIR . 'assets/cbfs-booking-class-emails.js';
		wp_enqueue_script(
			'clasbowi-class-emails',
			CLASBOWPRO_URL . 'assets/cbfs-booking-class-emails.js',
			$class_emails_deps,
			is_readable( $class_emails_js ) ? (string) filemtime( $class_emails_js ) : CLASBOWPRO_VERSION,
			true
		);
		if ( false !== $code_editor_settings ) {
			wp_add_inline_script(
				'clasbowi-class-emails',
				'window.clasbproEmailCodeEditor = ' . wp_json_encode( [ 'cmSettings' => $code_editor_settings ] ) . ';',
				'before'
			);
		}
	}

	/**
	 * @param string $classes Space-prefixed admin body classes.
	 */
	public static function filter_stripe_class_edit_body_class( string $classes ): string {
		return $classes . ' clasbpro-class-edit';
	}

	/**
	 * Booking / coupon purchase edit screens: admin summary card styles.
	 */
	public static function maybe_enqueue_booking_edit_admin(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->post_type, [ CPT::BOOKING_PT, CPT::PACK_PURCHASE_PT ], true ) ) {
			return;
		}
		if ( ! in_array( $screen->base, [ 'post', 'post-new' ], true ) ) {
			return;
		}

		add_filter( 'admin_body_class', [ self::class, 'filter_booking_edit_body_class' ] );

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'clasbowi-admin-settings',
			CLASBOWPRO_URL . 'assets/cbfs-booking-admin-settings.css',
			[],
			CLASBOWPRO_VERSION
		);
	}

	/**
	 * @param string $classes Space-prefixed admin body classes.
	 */
	public static function filter_booking_edit_body_class( string $classes ): string {
		return $classes . ' clasbpro-booking-edit';
	}

	/**
	 * Path to the admin settings intro template (theme override, then plugin default).
	 */
	public static function get_settings_intro_template_path(): string {
		$relative = 'admin-settings-intro.php';
		$theme    = locate_template(
			[
				'class-bookings-with-stripe/' . $relative
			],
			false,
			false
		);
		$path = $theme ? (string) $theme : CLASBOWPRO_DIR . 'templates/' . $relative;

		/**
		 * Filter the path to the Settings intro template.
		 *
		 * @param string $path Absolute filesystem path.
		 */
		return (string) apply_filters( 'clasbpro_settings_intro_template_path', $path );
	}

	/**
	 * @param mixed $post Post object (unused on options screen).
	 * @param mixed $args Meta box args.
	 */
	public static function render_settings_intro_metabox( $post = null, $args = null ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		self::output_settings_intro_panel( 'metabox' );
	}

	/**
	 * Output the intro panel from the template file.
	 *
	 * @param 'metabox'|'native' $context Where the panel is rendered.
	 */
	public static function output_settings_intro_panel( string $context = 'native' ): void {
		$path = self::get_settings_intro_template_path();
		if ( ! is_readable( $path ) ) {
			return;
		}

		if ( 'native' === $context ) {
			echo '<div id="clasbpro-settings-intro-native" class="postbox clasbpro-settings-intro-postbox">';
			echo '<div class="inside">';
		}

		include $path;

		if ( 'native' === $context ) {
			echo '</div></div>';
		}
	}

	/**
	 * Render one-click upcoming date links under the ACF Free cancelled-dates textarea.
	 *
	 * @param array<string,mixed> $field
	 */
	public static function render_cancelled_dates_quick_add( array $field ): void {
		$post_id = 0;
		if ( isset( $field['post_id'] ) && is_numeric( $field['post_id'] ) ) {
			$post_id = (int) $field['post_id'];
		}
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}
		if ( $post_id <= 0 || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return;
		}

		$class_data = Helpers::get_class_data( $post_id );
		if ( ! $class_data || empty( $class_data['start_time'] ) ) {
			return;
		}

		$dates = ! empty( $class_data['is_one_off_event'] )
			? Helpers::date_range_occurrences( (string) $class_data['start_date'], (string) $class_data['end_date'], (string) $class_data['start_time'], 5, [] )
			: Helpers::next_weekday_occurrences(
				(string) $class_data['day_of_week'],
				(string) $class_data['start_time'],
				5,
				[],
				Helpers::normalise_date_string( (string) ( $class_data['start_date'] ?? '' ) ),
				Helpers::normalise_date_string( (string) ( $class_data['end_date'] ?? '' ) )
			);
		if ( empty( $dates ) ) {
			return;
		}

		$field_key = isset( $field['key'] ) ? (string) $field['key'] : '';
		if ( '' === $field_key ) {
			return;
		}

		echo '<div class="clasbpro-cancelled-dates-helper">';
		echo '<strong>' . esc_html__( 'Quick add upcoming dates:', 'class-bookings-with-stripe-pro' ) . '</strong> ';
		foreach ( $dates as $date ) {
			echo '<a href="#" class="button button-secondary button-small clasbpro-add-cancelled-date" data-field-key="' . esc_attr( $field_key ) . '" data-date="' . esc_attr( $date ) . '">' . esc_html( Helpers::format_date( (string) $date ) ) . '</a>';
		}
		echo '<p class="description">' . esc_html__( 'Click a date to append it to the cancelled dates textarea (one per line).', 'class-bookings-with-stripe-pro' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Map legacy use_external_link toggle to the external_link schedule type in admin.
	 *
	 * @param mixed $value   Stored schedule type.
	 * @param mixed $post_id Class post ID.
	 */
	public static function load_schedule_type_value( $value, $post_id, $field ) {
		unset( $field );
		$post_id = is_numeric( $post_id ) ? (int) $post_id : 0;
		if ( $post_id <= 0 || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return $value;
		}
		if ( 'external_link' === (string) $value ) {
			return $value;
		}
		$legacy = get_post_meta( $post_id, 'use_external_link', true );
		if ( in_array( $legacy, [ '1', 1, true ], true ) ) {
			return 'external_link';
		}
		return $value;
	}

	/**
	 * Drop legacy use_external_link meta once schedule type is saved.
	 *
	 * @param mixed $value   New schedule type.
	 * @param mixed $post_id Class post ID.
	 */
	public static function update_schedule_type_value( $value, $post_id, $field ) {
		unset( $field );
		$post_id = is_numeric( $post_id ) ? (int) $post_id : 0;
		if ( $post_id <= 0 || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return $value;
		}
		delete_post_meta( $post_id, 'use_external_link' );
		return $value;
	}

	/**
	 * Sanitize inline SVG markup saved for global calendar card icons.
	 *
	 * @param mixed $value   Raw field value.
	 * @param mixed $post_id Class post ID.
	 */
	public static function sanitize_calendar_icon_value( $value, $post_id, $field ) {
		unset( $field );
		$post_id = is_numeric( $post_id ) ? (int) $post_id : 0;
		if ( $post_id <= 0 || CPT::CLASS_PT !== get_post_type( $post_id ) ) {
			return $value;
		}
		return Helpers::sanitize_calendar_icon_svg( (string) $value );
	}

	public static function register_field_groups(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$cancelled_dates_field = [
			'key'          => 'field_clasbpro_cancelled_dates_fallback',
			'label'        => __( 'Cancelled dates', 'class-bookings-with-stripe-pro' ),
			'name'         => 'cancelled_dates_fallback',
			'type'         => 'textarea',
			'rows'         => 4,
			'new_lines'    => '',
			'instructions' => __( 'Enter one date per line (YYYY-MM-DD). Optional reason after "|" is allowed, e.g. 2026-12-24|Holiday.', 'class-bookings-with-stripe-pro' ),
			'conditional_logic' => [
				[
					[
						'field'    => 'field_clasbpro_schedule_type',
						'operator' => '!=',
						'value'    => 'external_link',
					],
				],
			],
		];

		$external_link_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'external_link',
				],
			],
		];

		$internal_booking_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '!=',
					'value'    => 'external_link',
				],
			],
		];

		$recurring_booking_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'recurring',
				],
			],
		];

		$one_off_booking_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'one_off',
				],
			],
		];

		$appointments_booking_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'appointments',
				],
			],
		];

		$standard_schedule_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'recurring',
				],
			],
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'one_off',
				],
			],
		];

		$cancelled_dates_display_field = [
			'key'           => 'field_clasbpro_cancelled_dates_display',
			'label'         => __( 'Cancelled dates display', 'class-bookings-with-stripe-pro' ),
			'name'          => 'cancelled_dates_display',
			'type'          => 'select',
			'choices'       => [
				'show' => __( 'Show as “Cancelled”', 'class-bookings-with-stripe-pro' ),
				'hide' => __( 'Hide', 'class-bookings-with-stripe-pro' ),
			],
			'default_value' => 'show',
			'allow_null'    => 0,
			'instructions'  => __( 'Hide removes cancelled dates from the booking dropdown and calendars (they look like any other unbookable day).', 'class-bookings-with-stripe-pro' ),
			'conditional_logic' => [
				[
					[
						'field'    => 'field_clasbpro_schedule_type',
						'operator' => '!=',
						'value'    => 'external_link',
					],
				],
			],
		];

		$calendar_months_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'appointments',
				],
			],
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'recurring',
				],
				[
					'field'    => 'field_clasbpro_date_selection_mode',
					'operator' => '==',
					'value'    => 'calendar',
				],
			],
		];

		$dropdown_dates_count_condition = [
			[
				[
					'field'    => 'field_clasbpro_schedule_type',
					'operator' => '==',
					'value'    => 'recurring',
				],
				[
					'field'    => 'field_clasbpro_date_selection_mode',
					'operator' => '!=',
					'value'    => 'calendar',
				],
			],
		];

		// --- Yoga class fields ---
		acf_add_local_field_group(
			[
				'key'      => 'group_clasbpro_class',
				'title'    => __( 'Class details', 'class-bookings-with-stripe-pro' ),
				'fields'   => [
					[
						'key'           => 'field_clasbpro_schedule_type',
						'label'         => __( 'Schedule type', 'class-bookings-with-stripe-pro' ),
						'name'          => 'schedule_type',
						'type'          => 'button_group',
						'wrapper'       => [
							'width' => '75',
						],
						'choices'       => [
							'recurring'     => __( 'Weekly class', 'class-bookings-with-stripe-pro' ),
							'one_off'       => __( 'One-off event', 'class-bookings-with-stripe-pro' ),
							'appointments'  => __( 'Appointments', 'class-bookings-with-stripe-pro' ),
							'external_link' => __( 'External link', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'recurring',
						'allow_null'    => 0,
						'required'      => 1,
						'instructions'  => __( 'Weekly class, one-off event, appointments, or a single button linking to an external booking page.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_class_active',
						'label'         => __( 'Class is active (bookable)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'class_active',
						'type'          => 'true_false',
						'default_value' => 1,
						'ui'            => 1,
						'instructions'  => __( 'Toggle off to suspend all bookings for this class.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '25',
						],
					],
					[
						'key'               => 'field_clasbpro_external_link_url',
						'label'             => __( 'External booking URL', 'class-bookings-with-stripe-pro' ),
						'name'              => 'external_link_url',
						'type'              => 'url',
						'instructions'      => __( 'For example: ClassFor, Eventbrite, or any custom external destination.', 'class-bookings-with-stripe-pro' ),
						'placeholder'       => 'https://',
						'conditional_logic' => $external_link_condition,
					],
					[
						'key'               => 'field_clasbpro_start_date',
						'label'             => __( 'Start date', 'class-bookings-with-stripe-pro' ),
						'name'              => 'start_date',
						'type'              => 'date_picker',
						'display_format'    => 'Y-m-d',
						'return_format'     => 'Y-m-d',
						'first_day'         => 1,
						'required'          => 0,
						'instructions'      => __( 'First bookable date. (optional) — first date this class runs (leave blank for no start limit).', 'class-bookings-with-stripe-pro' ),
						'wrapper'           => [
							'width' => '25',
						],
						'conditional_logic' => $standard_schedule_condition,
					],
					[
						'key'               => 'field_clasbpro_end_date',
						'label'             => __( 'End date', 'class-bookings-with-stripe-pro' ),
						'name'              => 'end_date',
						'type'              => 'date_picker',
						'display_format'    => 'Y-m-d',
						'return_format'     => 'Y-m-d',
						'first_day'         => 1,
						'required'          => 0,
						'instructions'      => __( 'Last bookable date. Weekly: (optional) — last date this class runs.', 'class-bookings-with-stripe-pro' ),
						'wrapper'           => [
							'width' => '25',
						],
						'conditional_logic' => $standard_schedule_condition,
					],
					[
						'key'           => 'field_clasbpro_day',
						'label'         => __( 'Day of week', 'class-bookings-with-stripe-pro' ),
						'name'          => 'day_of_week',
						'type'          => 'select',
						'choices'       => [
							'monday'    => __( 'Monday', 'class-bookings-with-stripe-pro' ),
							'tuesday'   => __( 'Tuesday', 'class-bookings-with-stripe-pro' ),
							'wednesday' => __( 'Wednesday', 'class-bookings-with-stripe-pro' ),
							'thursday'  => __( 'Thursday', 'class-bookings-with-stripe-pro' ),
							'friday'    => __( 'Friday', 'class-bookings-with-stripe-pro' ),
							'saturday'  => __( 'Saturday', 'class-bookings-with-stripe-pro' ),
							'sunday'    => __( 'Sunday', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'sunday',
						'instructions'   => __( 'Repeating day.', 'class-bookings-with-stripe-pro' ),
						'allow_null'    => 0,
						'required'      => 1,
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $recurring_booking_condition,
					],
					[
						'key'           => 'field_clasbpro_start_time',
						'label'         => __( 'Start time', 'class-bookings-with-stripe-pro' ),
						'name'          => 'start_time',
						'type'          => 'time_picker',
						'display_format' => 'H:i',
						'return_format'  => 'H:i',
						'instructions'   => __( '24-hour format, e.g. 10:15.', 'class-bookings-with-stripe-pro' ),
						'required'       => 1,
						'wrapper'        => [
							'width' => '20',
						],
						'conditional_logic' => $standard_schedule_condition,
					],
					[
						'key'           => 'field_clasbpro_duration',
						'label'         => __( 'Duration (minutes)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'duration_minutes',
						'type'          => 'number',
						'instructions'   => __( 'In minutes.', 'class-bookings-with-stripe-pro' ),
						'default_value' => 45,
						'min'           => 1,
						'required'      => 1,
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $standard_schedule_condition,
					],
					[
						'key'           => 'field_clasbpro_price',
						'label'         => __( 'Price', 'class-bookings-with-stripe-pro' ),
						'name'          => 'price_gbp',
						'type'          => 'number',
						'instructions'   => __( 'Single seat cost.', 'class-bookings-with-stripe-pro' ),
						'default_value' => 15,
						'min'           => 0,
						'step'          => 0.01,
						'required'      => 1,
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $internal_booking_condition,
					],
					[
						'key'           => 'field_clasbpro_capacity',
						'label'         => __( 'Capacity (max attendees)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'capacity',
						'type'          => 'number',
						'default_value' => 20,
						'min'           => 1,
						'required'      => 1,
						'instructions'  => __( 'Maximum people per booking (per slot for appointments).', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $internal_booking_condition,
					],
					[
						'key'           => 'field_clasbpro_show_seats_remaining',
						'label'         => __( 'Show seats remaining', 'class-bookings-with-stripe-pro' ),
						'name'          => 'show_seats_remaining',
						'type'          => 'true_false',
						'default_value' => 1,
						'ui'            => 1,
						'instructions'  => __( 'When enabled, each date includes the number of seats left.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $standard_schedule_condition,
					],
					[
						'key'           => 'field_clasbpro_date_selection_mode',
						'label'         => __( 'Date selection', 'class-bookings-with-stripe-pro' ),
						'name'          => 'date_selection_mode',
						'type'          => 'button_group',
						'choices'       => [
							'dropdown' => __( 'Dropdown', 'class-bookings-with-stripe-pro' ),
							'calendar' => __( 'Calendar', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'dropdown',
						'allow_null'    => 0,
						'instructions'  => __( 'List or month calendar on the booking form.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $recurring_booking_condition,
					],
					[
						'key'           => 'field_clasbpro_calendar_months_ahead',
						'label'         => __( 'Calendar months ahead', 'class-bookings-with-stripe-pro' ),
						'name'          => 'calendar_months_ahead',
						'type'          => 'number',
						'default_value' => 3,
						'min'           => 1,
						'max'           => 12,
						'step'          => 1,
						'instructions'  => __( 'How many months customers can browse ahead.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $calendar_months_condition,
					],
					[
						'key'           => 'field_clasbpro_class_upcoming_dates_count',
						'label'         => __( 'Dates in dropdown', 'class-bookings-with-stripe-pro' ),
						'name'          => 'upcoming_dates_count',
						'type'          => 'number',
						'default_value' => 3,
						'min'           => 1,
						'step'          => 1,
						'instructions'  => __( 'How many upcoming dates appear in the booking form.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '20',
						],
						'conditional_logic' => $dropdown_dates_count_condition,
					],
					[
						'key'           => 'field_clasbpro_minimum_lead_time_hours',
						'label'         => __( 'Minimum lead time (hours)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'minimum_lead_time_hours',
						'type'          => 'number',
						'default_value' => 0,
						'min'           => 0,
						'step'          => 1,
						'instructions'  => __( 'Slots inside this window are hidden. 0 = book until start time.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'width' => '25',
						],
						'conditional_logic' => $appointments_booking_condition,
					],
					[
						'key'               => 'field_clasbpro_appointment_slot_rules',
						'label'             => __( 'Availability slots', 'class-bookings-with-stripe-pro' ),
						'name'              => '_clasbpro_appointment_slot_rules',
						'type'              => 'message',
						'message'           => '',
						'new_lines'         => '',
						'esc_html'          => 0,
						'instructions'      => __( 'Add one row per availability window.', 'class-bookings-with-stripe-pro' ),
						'conditional_logic' => $appointments_booking_condition,
					],
					[
						'key'           => 'field_clasbpro_location',
						'label'         => __( 'Location description', 'class-bookings-with-stripe-pro' ),
						'name'          => 'location',
						'type'          => 'text',
						'instructions'  => __( 'Optional, e.g. "Orpington Studio".', 'class-bookings-with-stripe-pro' ),
						'required'      => 0,
						'conditional_logic' => $standard_schedule_condition,
					],
					[
						'key'           => 'field_clasbpro_description',
						'label'         => __( 'Description', 'class-bookings-with-stripe-pro' ),
						'name'          => 'description',
						'type'          => 'wysiwyg',
						'instructions'  => __( 'Shown on the booking form and in confirmation emails.', 'class-bookings-with-stripe-pro' ),
						'tabs'          => 'visual',
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'conditional_logic' => $internal_booking_condition,
					],
					$cancelled_dates_field,
					$cancelled_dates_display_field,
				],
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CPT::CLASS_PT,
						],
					],
				],
				'menu_order' => 0,
				'position'   => 'normal',
			]
		);

		// --- Class listing image (sidebar, own metabox) ---
		acf_add_local_field_group(
			[
				'key'             => 'group_clasbpro_class_sidebar_image',
				'title'           => __( 'Listing image', 'class-bookings-with-stripe-pro' ),
				'fields'          => [
					[
						'key'           => 'field_clasbpro_class_image',
						'label'         => __( 'Image', 'class-bookings-with-stripe-pro' ),
						'name'          => 'class_image',
						'type'          => 'image',
						'return_format' => 'id',
						'preview_size'  => 'medium',
						'library'       => 'all',
						'instructions'  => __( 'Optional. Shown in the Classes list table. Can also appear on global calendar cards when enabled below.', 'class-bookings-with-stripe-pro' ),
					],
				],
				'location'        => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CPT::CLASS_PT,
						],
					],
				],
				'menu_order'      => 0,
				'position'        => 'side',
				'style'           => 'default',
				'label_placement' => 'top',
			]
		);

		// --- Global calendar appearance (sidebar) ---
		acf_add_local_field_group(
			[
				'key'             => 'group_clasbpro_class_global_calendar',
				'title'           => __( 'Global calendar', 'class-bookings-with-stripe-pro' ),
				'fields'          => [
					[
						'key'           => 'field_clasbpro_calendar_color',
						'label'         => __( 'Card colour', 'class-bookings-with-stripe-pro' ),
						'name'          => 'calendar_color',
						'type'          => 'color_picker',
						'instructions'  => __( 'Background colour for this class on the global schedule calendar. Leave empty for an automatic palette.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_calendar_icon',
						'label'         => __( 'Card icon (SVG)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'calendar_icon',
						'type'          => 'textarea',
						'rows'          => 6,
						'new_lines'     => '',
						'instructions'  => __( 'Optional SVG markup shown in the top-left corner of each calendar card. Leave empty to use the default icon for this schedule type.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_calendar_show_image',
						'label'         => __( 'Show listing image on cards', 'class-bookings-with-stripe-pro' ),
						'name'          => 'calendar_show_image',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
						'instructions'  => __( 'When enabled, the listing image appears above the text on each calendar card.', 'class-bookings-with-stripe-pro' ),
					],
				],
				'location'        => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CPT::CLASS_PT,
						],
					],
				],
				'menu_order'      => 1,
				'position'        => 'side',
				'style'           => 'default',
				'label_placement' => 'top',
			]
		);

		// --- Class email overrides (main column, below Class details) ---
		acf_add_local_field_group(
			[
				'key'        => 'group_clasbpro_class_emails',
				'title'      => __( 'Class emails', 'class-bookings-with-stripe-pro' ),
				'fields'     => self::class_email_field_definitions( $internal_booking_condition ),
				'location'   => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CPT::CLASS_PT,
						],
					],
				],
				'menu_order' => 1,
				'position'   => 'normal',
			]
		);

		// --- Booking detail (read-only-ish) ---
		acf_add_local_field_group(
			[
				'key'      => 'group_clasbpro_booking',
				'title'    => __( 'Booking details', 'class-bookings-with-stripe-pro' ),
				'fields'   => [
					[
						'key'       => 'field_clasbpro_b_summary',
						'label'     => __( 'Summary', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_summary',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
					],
				],
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CPT::BOOKING_PT,
						],
					],
				],
			]
		);

		// --- Coupon purchase detail (read-only-ish) ---
		acf_add_local_field_group(
			[
				'key'      => 'group_clasbpro_pack_purchase',
				'title'    => __( 'Coupon purchase details', 'class-bookings-with-stripe-pro' ),
				'fields'   => [
					[
						'key'       => 'field_clasbpro_pp_summary',
						'label'     => __( 'Summary', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_pack_purchase_summary',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
					],
				],
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CPT::PACK_PURCHASE_PT,
						],
					],
				],
			]
		);

		// --- Settings options page ---
		acf_add_local_field_group(
			[
				'key'      => 'group_clasbpro_settings',
				'title'    => __( 'Settings', 'class-bookings-with-stripe-pro' ),
				'fields'   => [
					[
						'key'   => 'field_clasbpro_tab_stripe',
						'label' => __( 'Stripe', 'class-bookings-with-stripe-pro' ),
						'type'  => 'tab',
					],
					[
						'key'           => 'field_clasbpro_stripe_mode',
						'label'         => __( 'Mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'stripe_mode',
						'type'          => 'select',
						'choices'       => [
							'test' => __( 'Test', 'class-bookings-with-stripe-pro' ),
							'live' => __( 'Live', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'test',
						'allow_null'    => 0,
						'instructions'  => __( 'Choose which API keys are used for checkout. Test mode uses sandbox keys; live mode charges real cards.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [
							'class' => 'clasbpro-stripe-mode-field',
						],
					],
					[
						'key'           => 'field_clasbpro_stripe_currency',
						'label'         => __( 'Currency', 'class-bookings-with-stripe-pro' ),
						'name'          => 'stripe_currency',
						'type'          => 'select',
						'choices'       => self::stripe_currency_choices(),
						'default_value' => 'gbp',
						'allow_null'    => 0,
						'instructions'  => __( 'Used for Stripe Checkout and all price displays. Changing currency does not convert existing class prices.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_stripe_item_title_template',
						'label'         => __( 'Stripe item title template', 'class-bookings-with-stripe-pro' ),
						'name'          => 'stripe_item_title_template',
						'type'          => 'text',
						'default_value' => '{class_name} — {class_date}, {class_time}',
						'instructions'  => __( 'Supports placeholders: {class_name}, {class_date}, {class_time}, {location}, {seats}, {customer_name}, {booking_id}.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'       => 'field_clasbpro_stripe_test_keys_banner',
						'label'     => '',
						'name'      => '_clasbpro_stripe_test_keys_banner',
						'type'      => 'message',
						'message'   => self::stripe_test_keys_banner_message(),
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [
							'class' => 'clasbpro-stripe-keys-banner-field',
						],
					],
					[
						'key'          => 'field_clasbpro_pub_test',
						'label'        => __( 'Publishable key (test)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'stripe_pub_key_test',
						'type'         => 'text',
						'instructions' => __( 'Starts with pk_test_. This key is public and used in the booking form.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [
							'width' => '50',
							'class' => 'clasbpro-stripe-key-field clasbpro-stripe-key-field--test',
						],
					],
					[
						'key'          => 'field_clasbpro_secret_test',
						'label'        => __( 'Secret key (test)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'stripe_secret_key_test',
						'type'         => 'password',
						'instructions' => __( 'Shown as dots when a key is saved. Paste a new key to replace it, or clear the field to remove it.', 'class-bookings-with-stripe-pro' ),
						'wrapper' => [
							'width' => '50',
							'class' => 'clasbpro-stripe-key-field clasbpro-stripe-key-field--test',
						],
					],
					[
						'key'       => 'field_clasbpro_stripe_live_keys_banner',
						'label'     => '',
						'name'      => '_clasbpro_stripe_live_keys_banner',
						'type'      => 'message',
						'message'   => self::stripe_live_keys_banner_message(),
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [
							'class' => 'clasbpro-stripe-keys-banner-field',
						],
					],
					[
						'key'          => 'field_clasbpro_pub_live',
						'label'        => __( 'Publishable key (live)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'stripe_pub_key_live',
						'type'         => 'text',
						'instructions' => __( 'Starts with pk_live_. This key is public and used in the booking form.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [
							'width' => '50',
							'class' => 'clasbpro-stripe-key-field clasbpro-stripe-key-field--live',
						],
					],
					[
						'key'          => 'field_clasbpro_secret_live',
						'label'        => __( 'Secret key (live)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'stripe_secret_key_live',
						'type'         => 'password',
						'instructions' => __( 'Shown as dots when a key is saved. Paste a new key to replace it, or clear the field to remove it.', 'class-bookings-with-stripe-pro' ),
						'wrapper' => [
							'width' => '50',
							'class' => 'clasbpro-stripe-key-field clasbpro-stripe-key-field--live',
						],
					],
					[
						'key'          => 'field_clasbpro_webhook_secret',
						'label'        => __( 'Webhook signing secret', 'class-bookings-with-stripe-pro' ),
						'name'         => 'stripe_webhook_secret',
						'type'         => 'password',
						'instructions' => __( 'Paste the signing secret from Stripe after you add the webhook endpoint (see Help → Stripe webhooks for full steps). Shown as dots when saved; clear the field to remove it.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [
							'width' => '50',
							'class' => 'clasbpro-stripe-webhook-secret',
						],
					],
					[
						'key'       => 'field_clasbpro_webhook_url',
						'label'     => __( 'Webhook endpoint URL', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_webhook_url_display',
						'type'      => 'message',
						'message'   => self::webhook_url_message(),
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [
							'width' => '50',
							'class' => 'clasbpro-stripe-webhook-url',
						],
					],
					[
						'key'   => 'field_clasbpro_tab_emails',
						'label' => __( 'Emails', 'class-bookings-with-stripe-pro' ),
						'type'  => 'tab',
					],
					[
						'key'       => 'field_clasbpro_email_subtabs_nav',
						'label'     => '',
						'name'      => '_clasbpro_email_subtabs_nav',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-subtabs-nav-field' ],
					],
					[
						'key'          => 'field_clasbpro_admin_email',
						'label'        => __( 'Admin notification email', 'class-bookings-with-stripe-pro' ),
						'name'         => 'admin_email',
						'type'         => 'email',
						'instructions' => __( 'Where instant booking and coupon purchase notifications are sent. Defaults to the WordPress admin email.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin' ],
					],
					[
						'key'           => 'field_clasbpro_admin_subject',
						'label'         => __( 'Admin email subject', 'class-bookings-with-stripe-pro' ),
						'name'          => 'admin_email_subject',
						'type'          => 'text',
						'default_value' => 'New booking: {customer_name} for {class_name} on {class_date}',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin' ],
					],
					[
						'key'           => 'field_clasbpro_admin_body',
						'label'         => __( 'Admin email body', 'class-bookings-with-stripe-pro' ),
						'name'          => 'admin_email_body',
						'type'          => 'wysiwyg',
						'tabs'          => 'visual',
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'default_value' => self::default_admin_email(),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin clasbpro-email-body-visual-field' ],
					],
					[
						'key'          => 'field_clasbpro_admin_email_body_html',
						'label'        => __( 'Admin email body (HTML)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'admin_email_body_html',
						'type'         => 'textarea',
						'rows'         => 16,
						'new_lines'    => '',
						'instructions' => __( 'Body fragment only — merge tags like {customer_name} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin clasbpro-email-body-html-field' ],
					],
					[
						'key'           => 'field_clasbpro_admin_email_body_editor_mode',
						'label'         => __( 'Admin email editor mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'admin_email_body_editor_mode',
						'type'          => 'select',
						'choices'       => [
							'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
							'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'visual',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin clasbpro-email-body-mode-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_tab_intro_admin',
						'label'     => '',
						'name'      => '_clasbpro_email_tab_intro_admin',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin clasbpro-email-tab-intro-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_test_send_admin',
						'label'     => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_email_test_send_admin',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin clasbpro-email-tab-test-field' ],
					],
					[
						'key'           => 'field_clasbpro_cust_subject',
						'label'         => __( 'Customer email subject', 'class-bookings-with-stripe-pro' ),
						'name'          => 'customer_email_subject',
						'type'          => 'text',
						'default_value' => 'Your booking is confirmed: {class_name} on {class_date}',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer' ],
					],
					[
						'key'           => 'field_clasbpro_cust_body',
						'label'         => __( 'Customer email body', 'class-bookings-with-stripe-pro' ),
						'name'          => 'customer_email_body',
						'type'          => 'wysiwyg',
						'tabs'          => 'visual',
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'default_value' => self::default_customer_email(),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer clasbpro-email-body-visual-field' ],
					],
					[
						'key'          => 'field_clasbpro_customer_email_body_html',
						'label'        => __( 'Customer email body (HTML)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'customer_email_body_html',
						'type'         => 'textarea',
						'rows'         => 16,
						'new_lines'    => '',
						'instructions' => __( 'Body fragment only — merge tags like {customer_name} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer clasbpro-email-body-html-field' ],
					],
					[
						'key'           => 'field_clasbpro_customer_email_body_editor_mode',
						'label'         => __( 'Customer email editor mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'customer_email_body_editor_mode',
						'type'          => 'select',
						'choices'       => [
							'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
							'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'visual',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer clasbpro-email-body-mode-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_tab_intro_customer',
						'label'     => '',
						'name'      => '_clasbpro_email_tab_intro_customer',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer clasbpro-email-tab-intro-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_test_send_customer',
						'label'     => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_email_test_send_customer',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer clasbpro-email-tab-test-field' ],
					],
					[
						'key'           => 'field_clasbpro_admin_coupon_subject',
						'label'         => __( 'Admin coupon email subject', 'class-bookings-with-stripe-pro' ),
						'name'          => 'admin_coupon_email_subject',
						'type'          => 'text',
						'default_value' => 'New coupon purchase: {pack_name}',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin-coupon' ],
					],
					[
						'key'           => 'field_clasbpro_admin_coupon_body',
						'label'         => __( 'Admin coupon email body', 'class-bookings-with-stripe-pro' ),
						'name'          => 'admin_coupon_email_body',
						'type'          => 'wysiwyg',
						'tabs'          => 'visual',
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'default_value' => self::default_admin_coupon_email(),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin-coupon clasbpro-email-body-visual-field' ],
					],
					[
						'key'          => 'field_clasbpro_admin_coupon_email_body_html',
						'label'        => __( 'Admin coupon email body (HTML)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'admin_coupon_email_body_html',
						'type'         => 'textarea',
						'rows'         => 16,
						'new_lines'    => '',
						'instructions' => __( 'Body fragment only — merge tags like {customer_name} and {pack_code} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin-coupon clasbpro-email-body-html-field' ],
					],
					[
						'key'           => 'field_clasbpro_admin_coupon_email_body_editor_mode',
						'label'         => __( 'Admin coupon email editor mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'admin_coupon_email_body_editor_mode',
						'type'          => 'select',
						'choices'       => [
							'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
							'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'visual',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin-coupon clasbpro-email-body-mode-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_tab_intro_admin_coupon',
						'label'     => '',
						'name'      => '_clasbpro_email_tab_intro_admin_coupon',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin-coupon clasbpro-email-tab-intro-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_test_send_admin_coupon',
						'label'     => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_email_test_send_admin_coupon',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin-coupon clasbpro-email-tab-test-field' ],
					],
					[
						'key'           => 'field_clasbpro_cust_coupon_subject',
						'label'         => __( 'Customer coupon email subject', 'class-bookings-with-stripe-pro' ),
						'name'          => 'customer_coupon_email_subject',
						'type'          => 'text',
						'default_value' => 'Your coupon: {pack_name}',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer-coupon' ],
					],
					[
						'key'           => 'field_clasbpro_cust_coupon_body',
						'label'         => __( 'Customer coupon email body', 'class-bookings-with-stripe-pro' ),
						'name'          => 'customer_coupon_email_body',
						'type'          => 'wysiwyg',
						'tabs'          => 'visual',
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'default_value' => self::default_customer_coupon_email(),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer-coupon clasbpro-email-body-visual-field' ],
					],
					[
						'key'          => 'field_clasbpro_customer_coupon_email_body_html',
						'label'        => __( 'Customer coupon email body (HTML)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'customer_coupon_email_body_html',
						'type'         => 'textarea',
						'rows'         => 16,
						'new_lines'    => '',
						'instructions' => __( 'Body fragment only — merge tags like {customer_name} and {pack_code} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer-coupon clasbpro-email-body-html-field' ],
					],
					[
						'key'           => 'field_clasbpro_customer_coupon_email_body_editor_mode',
						'label'         => __( 'Customer coupon email editor mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'customer_coupon_email_body_editor_mode',
						'type'          => 'select',
						'choices'       => [
							'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
							'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'visual',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer-coupon clasbpro-email-body-mode-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_tab_intro_customer_coupon',
						'label'     => '',
						'name'      => '_clasbpro_email_tab_intro_customer_coupon',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer-coupon clasbpro-email-tab-intro-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_test_send_customer_coupon',
						'label'     => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_email_test_send_customer_coupon',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-customer-coupon clasbpro-email-tab-test-field' ],
					],
					[
						'key'           => 'field_clasbpro_enable_reminder_emails',
						'label'         => __( 'Enabled', 'class-bookings-with-stripe-pro' ),
						'name'          => 'enable_reminder_emails',
						'type'          => 'true_false',
						'default_value' => 1,
						'ui'            => 1,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_reminder_offset_amount',
						'label'         => __( 'Send', 'class-bookings-with-stripe-pro' ),
						'name'          => 'reminder_offset_amount',
						'type'          => 'number',
						'min'           => 1,
						'step'          => 1,
						'default_value' => 24,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_reminder_offset_unit',
						'label'         => __( 'Before class', 'class-bookings-with-stripe-pro' ),
						'name'          => 'reminder_offset_unit',
						'type'          => 'select',
						'choices'       => [
							'minutes' => __( 'Minutes', 'class-bookings-with-stripe-pro' ),
							'hours'   => __( 'Hours', 'class-bookings-with-stripe-pro' ),
							'days'    => __( 'Days', 'class-bookings-with-stripe-pro' ),
							'weeks'   => __( 'Weeks', 'class-bookings-with-stripe-pro' ),
							'months'  => __( 'Months', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'hours',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_reminder_admin_copy',
						'label'         => __( 'Admin copy', 'class-bookings-with-stripe-pro' ),
						'name'          => 'reminder_admin_copy',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_reminder_email_subject',
						'label'         => __( 'Reminder subject', 'class-bookings-with-stripe-pro' ),
						'name'          => 'reminder_email_subject',
						'type'          => 'text',
						'default_value' => __( 'Reminder: {class_name} on {class_date}', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-content' ],
					],
					[
						'key'           => 'field_clasbpro_reminder_email_body',
						'label'         => __( 'Reminder body', 'class-bookings-with-stripe-pro' ),
						'name'          => 'reminder_email_body',
						'type'          => 'wysiwyg',
						'default_value' => Scheduled_Emails::default_reminder_rule_body(),
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-content clasbpro-email-body-visual-field' ],
					],
					[
						'key'          => 'field_clasbpro_reminder_email_body_html',
						'label'        => __( 'Reminder body (HTML)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'reminder_email_body_html',
						'type'         => 'textarea',
						'rows'         => 16,
						'new_lines'    => '',
						'instructions' => __( 'Body fragment only — merge tags like {customer_name} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-content clasbpro-email-body-html-field' ],
					],
					[
						'key'           => 'field_clasbpro_reminder_email_body_editor_mode',
						'label'         => __( 'Reminder editor mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'reminder_email_body_editor_mode',
						'type'          => 'select',
						'choices'       => [
							'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
							'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'visual',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-scheduled-email-content clasbpro-email-body-mode-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_tab_intro_reminder',
						'label'     => '',
						'name'      => '_clasbpro_email_tab_intro_reminder',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-email-tab-intro-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_test_send_reminder',
						'label'     => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_email_test_send_reminder',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-reminders clasbpro-email-tab-test-field' ],
					],
					[
						'key'           => 'field_clasbpro_enable_post_class_emails',
						'label'         => __( 'Enabled', 'class-bookings-with-stripe-pro' ),
						'name'          => 'enable_post_class_emails',
						'type'          => 'true_false',
						'default_value' => 1,
						'ui'            => 1,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_post_class_offset_amount',
						'label'         => __( 'Send', 'class-bookings-with-stripe-pro' ),
						'name'          => 'post_class_offset_amount',
						'type'          => 'number',
						'min'           => 1,
						'step'          => 1,
						'default_value' => 3,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_post_class_offset_unit',
						'label'         => __( 'After class', 'class-bookings-with-stripe-pro' ),
						'name'          => 'post_class_offset_unit',
						'type'          => 'select',
						'choices'       => [
							'minutes' => __( 'Minutes', 'class-bookings-with-stripe-pro' ),
							'hours'   => __( 'Hours', 'class-bookings-with-stripe-pro' ),
							'days'    => __( 'Days', 'class-bookings-with-stripe-pro' ),
							'weeks'   => __( 'Weeks', 'class-bookings-with-stripe-pro' ),
							'months'  => __( 'Months', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'hours',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_post_class_admin_copy',
						'label'         => __( 'Admin copy', 'class-bookings-with-stripe-pro' ),
						'name'          => 'post_class_admin_copy',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-controls' ],
					],
					[
						'key'           => 'field_clasbpro_post_class_email_subject',
						'label'         => __( 'Post-class subject', 'class-bookings-with-stripe-pro' ),
						'name'          => 'post_class_email_subject',
						'type'          => 'text',
						'default_value' => __( 'How was {class_name}?', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-content' ],
					],
					[
						'key'           => 'field_clasbpro_post_class_email_body',
						'label'         => __( 'Post-class body', 'class-bookings-with-stripe-pro' ),
						'name'          => 'post_class_email_body',
						'type'          => 'wysiwyg',
						'default_value' => Scheduled_Emails::default_post_class_rule_body(),
						'toolbar'       => 'basic',
						'media_upload'  => 0,
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-content clasbpro-email-body-visual-field' ],
					],
					[
						'key'          => 'field_clasbpro_post_class_email_body_html',
						'label'        => __( 'Post-class body (HTML)', 'class-bookings-with-stripe-pro' ),
						'name'         => 'post_class_email_body_html',
						'type'         => 'textarea',
						'rows'         => 16,
						'new_lines'    => '',
						'instructions' => __( 'Body fragment only — merge tags like {customer_name} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
						'wrapper'      => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-content clasbpro-email-body-html-field' ],
					],
					[
						'key'           => 'field_clasbpro_post_class_email_body_editor_mode',
						'label'         => __( 'Post-class editor mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'post_class_email_body_editor_mode',
						'type'          => 'select',
						'choices'       => [
							'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
							'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
						],
						'default_value' => 'visual',
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-scheduled-email-content clasbpro-email-body-mode-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_tab_intro_post_class',
						'label'     => '',
						'name'      => '_clasbpro_email_tab_intro_post_class',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-email-tab-intro-field' ],
					],
					[
						'key'       => 'field_clasbpro_email_test_send_post_class',
						'label'     => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_email_test_send_post_class',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-post-class clasbpro-email-tab-test-field' ],
					],
					[
						'key'           => 'field_clasbpro_email_local_test_mode',
						'label'         => __( 'Local test mode', 'class-bookings-with-stripe-pro' ),
						'name'          => 'email_local_test_mode',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
						'instructions'  => __( 'When enabled, every booking email from this plugin is redirected to your test address instead of the real customer or admin. Intended recipients are shown inside the message. Turn off before going live.', 'class-bookings-with-stripe-pro' ),
						'wrapper'       => [ 'class' => 'clasbpro-email-section clasbpro-email-section-extras clasbpro-email-test-mode-field' ],
					],
					[
						'key'               => 'field_clasbpro_email_test_recipient',
						'label'             => __( 'Test recipient email', 'class-bookings-with-stripe-pro' ),
						'name'              => 'email_test_recipient',
						'type'              => 'email',
						'instructions'      => __( 'Where test and redirected emails are delivered. Defaults to the admin notification email, then the WordPress site admin email.', 'class-bookings-with-stripe-pro' ),
						'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-extras clasbpro-email-test-recipient-field' ],
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_email_local_test_mode',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'       => 'field_clasbpro_scheduled_email_tools',
						'label'     => __( 'Backfill & test', 'class-bookings-with-stripe-pro' ),
						'name'      => '_clasbpro_scheduled_email_tools',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-email-section clasbpro-email-section-extras' ],
					],
					[
						'key'   => 'field_clasbpro_tab_pages',
						'label' => __( 'Form extras', 'class-bookings-with-stripe-pro' ),
						'type'  => 'tab',
					],
					[
						'key'     => 'field_clasbpro_form_extras_note',
						'label'   => __( 'Using ACF fields in booking forms', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_form_extras_note',
						'type'    => 'message',
						'message' => sprintf(
							'%s<br><br><strong>%s</strong><br><code>%s</code>',
							esc_html__( 'You can add custom ACF fields to a booking form by creating a new ACF Field Group and setting its Location Rule to "Class Bookings with Stripe → Booking form class ID". Then choose the class ID the field group should appear on.', 'class-bookings-with-stripe-pro' ),
							esc_html__( 'Tip:', 'class-bookings-with-stripe-pro' ),
							esc_html__( 'Supported field types include text, email, number, url, textarea, select, radio and true/false.', 'class-bookings-with-stripe-pro' )
						),
					],
					[
						'key'           => 'field_clasbpro_enable_waiver',
						'label'         => __( 'Require waiver acceptance', 'class-bookings-with-stripe-pro' ),
						'name'          => 'enable_waiver',
						'type'          => 'true_false',
						'default_value' => 0,
						'ui'            => 1,
						'instructions'  => __( 'Adds a required checkbox to the booking form. Customers must accept before payment.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'               => 'field_clasbpro_waiver_label',
						'label'             => __( 'Waiver checkbox label', 'class-bookings-with-stripe-pro' ),
						'name'              => 'waiver_label',
						'type'              => 'wysiwyg',
						'default_value'     => __( 'I confirm I have read and accept the class waiver and participate at my own risk.', 'class-bookings-with-stripe-pro' ),
						'tabs'              => 'visual',
						'toolbar'           => 'basic',
						'media_upload'      => 0,
						'instructions'        => __( 'HTML is allowed (links, lists, emphasis). Shown next to the waiver checkbox on the booking form.', 'class-bookings-with-stripe-pro' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_enable_waiver',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'               => 'field_clasbpro_waiver_page_url',
						'label'             => __( 'Waiver page URL', 'class-bookings-with-stripe-pro' ),
						'name'              => 'waiver_page_url',
						'type'              => 'url',
						'placeholder'       => 'https://',
						'instructions'      => __( 'Optional full waiver policy page. Shown as a separate link below the checkbox label.', 'class-bookings-with-stripe-pro' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_enable_waiver',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'           => 'field_clasbpro_enable_mailchimp_optin',
						'label'         => __( 'Show Mailchimp opt-in checkbox', 'class-bookings-with-stripe-pro' ),
						'name'          => 'enable_mailchimp_optin',
						'type'          => 'true_false',
						'default_value' => 0,
						'ui'            => 1,
						'instructions'  => __( 'Adds an optional mailing-list consent checkbox on the booking form.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'               => 'field_clasbpro_mailchimp_optin_label',
						'label'             => __( 'Mailchimp opt-in label', 'class-bookings-with-stripe-pro' ),
						'name'              => 'mailchimp_optin_label',
						'type'              => 'textarea',
						'default_value'     => __( 'Yes, I would like to join the mailing list for class updates and news.', 'class-bookings-with-stripe-pro' ),
						'rows'              => 3,
						'new_lines'         => '',
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_enable_mailchimp_optin',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'               => 'field_clasbpro_mailchimp_api_key',
						'label'             => __( 'Mailchimp API key', 'class-bookings-with-stripe-pro' ),
						'name'              => 'mailchimp_api_key',
						'type'              => 'password',
						'instructions'      => __( 'From Mailchimp account settings. Format typically ends with datacenter suffix, e.g. us6.', 'class-bookings-with-stripe-pro' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_enable_mailchimp_optin',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'               => 'field_clasbpro_mailchimp_audience_id',
						'label'             => __( 'Mailchimp Audience ID', 'class-bookings-with-stripe-pro' ),
						'name'              => 'mailchimp_audience_id',
						'type'              => 'text',
						'instructions'      => __( 'The Audience/List ID to subscribe customers into.', 'class-bookings-with-stripe-pro' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_enable_mailchimp_optin',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'               => 'field_clasbpro_mailchimp_double_optin',
						'label'             => __( 'Mailchimp double opt-in', 'class-bookings-with-stripe-pro' ),
						'name'              => 'mailchimp_double_optin',
						'type'              => 'true_false',
						'default_value'     => 1,
						'ui'                => 1,
						'instructions'      => __( 'If enabled, contacts are added as pending and must confirm by email.', 'class-bookings-with-stripe-pro' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_clasbpro_enable_mailchimp_optin',
									'operator' => '==',
									'value'    => '1',
								],
							],
						],
					],
					[
						'key'   => 'field_clasbpro_tab_pages_2',
						'label' => __( 'Result pages', 'class-bookings-with-stripe-pro' ),
						'type'  => 'tab',
					],
					[
						'key'           => 'field_clasbpro_success_page',
						'label'         => __( 'Booking Confirmed page', 'class-bookings-with-stripe-pro' ),
						'name'          => 'success_page',
						'type'          => 'post_object',
						'post_type'     => [ 'page' ],
						'return_format' => 'id',
						'allow_null'    => 1,
						'instructions'  => __( 'Customer is redirected here after a successful Stripe payment. Auto-created on activation.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_cancel_page',
						'label'         => __( 'Booking Cancelled page', 'class-bookings-with-stripe-pro' ),
						'name'          => 'cancel_page',
						'type'          => 'post_object',
						'post_type'     => [ 'page' ],
						'return_format' => 'id',
						'allow_null'    => 1,
						'instructions'  => __( 'Customer is redirected here if they abandon Stripe Checkout or cancel payment. Auto-created on activation.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_error_page',
						'label'         => __( 'Booking Error page', 'class-bookings-with-stripe-pro' ),
						'name'          => 'error_page',
						'type'          => 'post_object',
						'post_type'     => [ 'page' ],
						'return_format' => 'id',
						'allow_null'    => 1,
						'instructions'  => __( 'Customer is redirected here when checkout or booking fails (for example validation or payment errors). Auto-created on activation.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_schedule_classes',
						'label'         => __( 'Schedule calendar classes', 'class-bookings-with-stripe-pro' ),
						'name'          => 'schedule_classes',
						'type'          => 'relationship',
						'post_type'     => [ Constants::CPT_CLASS ],
						'filters'       => [ 'search' ],
						'return_format' => 'id',
						'min'           => 0,
						'max'           => 0,
						'instructions'  => __( 'Classes shown on the [clasbpro_schedule] shortcode. Inactive classes are hidden on the calendar front end.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_schedule_weeks_ahead',
						'label'         => __( 'Schedule weeks ahead', 'class-bookings-with-stripe-pro' ),
						'name'          => 'schedule_weeks_ahead',
						'type'          => 'number',
						'default_value' => 8,
						'min'           => 1,
						'max'           => 52,
						'step'          => 1,
						'instructions'  => __( 'How many weeks ahead visitors can browse on the schedule calendar.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'   => 'field_clasbpro_tab_help',
						'label' => __( 'Help', 'class-bookings-with-stripe-pro' ),
						'type'  => 'tab',
					],
					[
						'key'       => 'field_clasbpro_help_subtabs_nav',
						'label'     => '',
						'name'      => '_clasbpro_help_subtabs_nav',
						'type'      => 'message',
						'message'   => '',
						'new_lines' => '',
						'esc_html'  => 0,
						'wrapper'   => [ 'class' => 'clasbpro-help-subtabs-nav-field' ],
					],
					[
						'key'     => 'field_clasbpro_help_intro',
						'label'   => __( 'Overview', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_help_intro',
						'type'    => 'message',
						'message' => self::help_intro_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-setup' ],
					],
					[
						'key'     => 'field_clasbpro_help_stripe_keys',
						'label'   => __( 'Stripe API keys', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_help_stripe_keys',
						'type'    => 'message',
						'message' => self::help_stripe_keys_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-setup' ],
					],
					[
						'key'     => 'field_clasbpro_help_webhooks',
						'label'   => __( 'Stripe webhooks', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_help_webhooks',
						'type'    => 'message',
						'message' => self::help_webhooks_detail_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-setup' ],
					],
					[
						'key'     => 'field_clasbpro_help_email_smtp',
						'label'   => __( 'Email & WP Mail SMTP', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_help_email_smtp',
						'type'    => 'message',
						'message' => self::help_email_smtp_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-setup' ],
					],
					[
						'key'     => 'field_clasbpro_help_next_steps',
						'label'   => __( 'Classes & publishing', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_help_next_steps',
						'type'    => 'message',
						'message' => self::help_next_steps_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-setup' ],
					],
					[
						'key'     => 'field_clasbpro_help_shortcodes',
						'label'   => '',
						'name'    => '_clasbpro_help_shortcodes',
						'type'    => 'message',
						'message' => self::help_shortcodes_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-shortcodes' ],
					],
					[
						'key'     => 'field_clasbpro_dev_webhooks',
						'label'   => __( 'Webhooks and payment state', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_dev_webhooks',
						'type'    => 'message',
						'message' => self::developer_webhooks_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-developer' ],
					],
					[
						'key'     => 'field_clasbpro_dev_templates',
						'label'   => __( 'Template overrides', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_dev_templates',
						'type'    => 'message',
						'message' => self::developer_templates_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-developer' ],
					],
					[
						'key'     => 'field_clasbpro_dev_hooks',
						'label'   => __( 'Hooks and extension points', 'class-bookings-with-stripe-pro' ),
						'name'    => '_clasbpro_dev_hooks',
						'type'    => 'message',
						'message' => self::developer_hooks_message(),
						'wrapper' => [ 'class' => 'clasbpro-help-section clasbpro-help-section-developer' ],
					],
				],
				'location' => [
					[
						[
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::SETTINGS_MENU_SLUG,
						],
					],
				],
			]
		);

		acf_add_local_field_group(
			[
				'key'      => 'group_clasbpro_pack',
				'title'    => __( 'Coupon details', 'class-bookings-with-stripe-pro' ),
				'fields'   => [
					[
						'key'           => 'field_clasbpro_pack_active',
						'label'         => __( 'Coupon is active (for sale)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'pack_active',
						'type'          => 'true_false',
						'default_value' => 1,
						'ui'            => 1,
					],
					[
						'key'           => 'field_clasbpro_pack_price',
						'label'         => __( 'Coupon purchase price', 'class-bookings-with-stripe-pro' ),
						'name'          => 'pack_price',
						'type'          => 'number',
						'step'          => '0.01',
						'min'           => 0,
						'required'      => 1,
						'instructions'  => __( 'What the customer pays for this coupon (e.g. 40 for a 4-class coupon).', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_pack_uses',
						'label'         => __( 'Number of class uses', 'class-bookings-with-stripe-pro' ),
						'name'          => 'pack_uses',
						'type'          => 'number',
						'default_value' => 4,
						'min'           => 1,
						'step'          => 1,
						'required'      => 1,
						'instructions'  => __( 'How many single-seat bookings this coupon can redeem.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_pack_unit_price',
						'label'         => __( 'Eligible class unit price', 'class-bookings-with-stripe-pro' ),
						'name'          => 'pack_unit_price',
						'type'          => 'number',
						'step'          => '0.01',
						'min'           => 0,
						'required'      => 1,
						'instructions'  => __( 'Coupon can only redeem classes currently priced at this amount (e.g. 10).', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_pack_expiry_months',
						'label'         => __( 'Expires after (months)', 'class-bookings-with-stripe-pro' ),
						'name'          => 'pack_expiry_months',
						'type'          => 'number',
						'default_value' => 6,
						'min'           => 0,
						'step'          => 1,
						'instructions'  => __( 'Counted from purchase. Use 0 for no expiry.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'           => 'field_clasbpro_pack_classes',
						'label'         => __( 'Eligible classes', 'class-bookings-with-stripe-pro' ),
						'name'          => 'pack_classes',
						'type'          => 'relationship',
						'post_type'     => [ Constants::CPT_CLASS ],
						'filters'       => [ 'search' ],
						'return_format' => 'id',
						'min'           => 1,
						'max'           => 0,
						'required'      => 1,
						'instructions'  => __( 'Classes this coupon can book. Each class price should match the unit price above.', 'class-bookings-with-stripe-pro' ),
					],
					[
						'key'          => 'field_clasbpro_pack_description',
						'label'        => __( 'Description', 'class-bookings-with-stripe-pro' ),
						'name'         => 'pack_description',
						'type'         => 'textarea',
						'rows'         => 3,
						'instructions' => __( 'Shown on the coupon purchase list.', 'class-bookings-with-stripe-pro' ),
					],
				],
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => Constants::CPT_PACK,
						],
					],
				],
			]
		);
	}

	private static function webhook_url_message(): string {
		$url = rest_url( CLASBOWPRO_REST_NS . '/stripe-webhook' );
		return sprintf(
			'<div class="clasbpro-stripe-webhook-url__inner">'
			. '<code class="clasbpro-stripe-webhook-url__code">%1$s</code>'
			. '<p class="clasbpro-stripe-webhook-url__description">%2$s</p>'
			. '</div>',
			esc_html( $url ),
			esc_html__( 'Add this URL to Stripe → Developers → Webhooks. Listen for events: checkout.session.completed, checkout.session.expired, checkout.session.async_payment_failed.', 'class-bookings-with-stripe-pro' )
		);
	}

	private static function stripe_test_keys_banner_message(): string {
		return sprintf(
			'<div class="clasbpro-stripe-keys-banner__inner">'
			. '<span class="clasbpro-stripe-keys-banner__badge clasbpro-stripe-keys-banner__badge--test">%1$s</span>'
			. '<div class="clasbpro-stripe-keys-banner__text">'
			. '<strong class="clasbpro-stripe-keys-banner__title">%2$s</strong>'
			. '<p class="clasbpro-stripe-keys-banner__description">%3$s</p>'
			. '</div></div>',
			esc_html__( 'Test', 'class-bookings-with-stripe-pro' ),
			esc_html__( 'Test API keys', 'class-bookings-with-stripe-pro' ),
			esc_html__( 'Use keys from Stripe Dashboard → Developers → API keys while test mode is on. No real charges are made.', 'class-bookings-with-stripe-pro' )
		);
	}

	private static function stripe_live_keys_banner_message(): string {
		return sprintf(
			'<div class="clasbpro-stripe-keys-banner__inner">'
			. '<span class="clasbpro-stripe-keys-banner__badge clasbpro-stripe-keys-banner__badge--live">%1$s</span>'
			. '<div class="clasbpro-stripe-keys-banner__text">'
			. '<strong class="clasbpro-stripe-keys-banner__title">%2$s</strong>'
			. '<p class="clasbpro-stripe-keys-banner__description">%3$s</p>'
			. '</div></div>',
			esc_html__( 'Live', 'class-bookings-with-stripe-pro' ),
			esc_html__( 'Live API keys', 'class-bookings-with-stripe-pro' ),
			esc_html__( 'Real payments are processed. Only switch to live mode when you are ready to accept bookings.', 'class-bookings-with-stripe-pro' )
		);
	}

	/**
	 * @param array<int, array<int, array<string, string>>> $not_external_condition
	 * @return array<int, array<string, mixed>>
	 */
	private static function class_email_field_definitions( array $not_external_condition ): array {
		$mode_choices = [
			Class_Email_Overrides::MODE_GLOBAL => __( 'Use global default', 'class-bookings-with-stripe-pro' ),
			Class_Email_Overrides::MODE_CUSTOM => __( 'Custom for this class', 'class-bookings-with-stripe-pro' ),
		];

		$fields = [
			[
				'key'               => 'field_clasbpro_class_email_subtabs_nav',
				'label'             => '',
				'name'              => '_clasbpro_class_email_subtabs_nav',
				'type'              => 'message',
				'message'           => '',
				'new_lines'         => '',
				'esc_html'          => 0,
				'wrapper'           => [ 'class' => 'clasbpro-email-subtabs-nav-field clasbpro-class-email-subtabs-nav-field' ],
				'conditional_logic' => $not_external_condition,
			],
		];

		$types = [
			'admin' => [
				'section' => 'admin',
				'label'   => __( 'Admin email', 'class-bookings-with-stripe-pro' ),
			],
			'customer' => [
				'section' => 'customer',
				'label'   => __( 'Customer email', 'class-bookings-with-stripe-pro' ),
			],
			'reminder' => [
				'section' => 'reminders',
				'label'   => __( 'Scheduled reminders', 'class-bookings-with-stripe-pro' ),
				'global_send' => 'send_reminder_emails',
			],
			'post_class' => [
				'section' => 'post-class',
				'label'   => __( 'Scheduled post-class', 'class-bookings-with-stripe-pro' ),
				'global_send' => 'send_post_class_emails',
			],
		];

		foreach ( $types as $type => $cfg ) {
			$section     = (string) $cfg['section'];
			$mode_key    = 'field_clasbpro_class_email_' . $type . '_mode';
			$global_cond = [
				array_merge(
					$not_external_condition[0] ?? [],
					[
						[
							'field'    => $mode_key,
							'operator' => '==',
							'value'    => Class_Email_Overrides::MODE_GLOBAL,
						],
					]
				),
			];
			$custom_cond = [
				array_merge(
					$not_external_condition[0] ?? [],
					[
						[
							'field'    => $mode_key,
							'operator' => '==',
							'value'    => Class_Email_Overrides::MODE_CUSTOM,
						],
					]
				),
			];

			$fields[] = [
				'key'               => $mode_key,
				'label'             => (string) $cfg['label'],
				'name'              => Class_Email_Overrides::mode_field_name( $type ),
				'type'              => 'button_group',
				'choices'           => $mode_choices,
				'default_value'     => Class_Email_Overrides::MODE_GLOBAL,
				'allow_null'        => 0,
				'layout'            => 'horizontal',
				'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-class-email-mode-field' ],
				'conditional_logic' => $not_external_condition,
			];

			if ( ! empty( $cfg['global_send'] ) ) {
				$fields[] = [
					'key'               => 'field_clasbpro_send_' . ( 'reminder' === $type ? 'reminder' : 'post_class' ) . '_emails',
					'label'             => __( 'Send for this class', 'class-bookings-with-stripe-pro' ),
					'name'              => (string) $cfg['global_send'],
					'type'              => 'true_false',
					'default_value'     => 1,
					'ui'                => 1,
					'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-class-email-global-send-field' ],
					'conditional_logic' => $global_cond,
				];
			}

			$fields[] = [
				'key'               => 'field_clasbpro_class_email_' . $type . '_enabled',
				'label'             => __( 'Enabled', 'class-bookings-with-stripe-pro' ),
				'name'              => 'class_email_' . $type . '_enabled',
				'type'              => 'true_false',
				'default_value'     => 1,
				'ui'                => 1,
				'wrapper'           => [
					'class' => 'clasbpro-email-section clasbpro-email-section-' . $section
						. ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ? ' clasbpro-scheduled-email-controls' : '' ),
				],
				'conditional_logic' => $custom_cond,
			];

			if ( 'admin' === $type ) {
				$fields[] = [
					'key'               => 'field_clasbpro_class_email_admin_recipient',
					'label'             => __( 'Admin notification email', 'class-bookings-with-stripe-pro' ),
					'name'              => 'class_email_admin_recipient',
					'type'              => 'email',
					'instructions'      => __( 'Optional. Leave blank to use the global admin email from Settings → Emails.', 'class-bookings-with-stripe-pro' ),
					'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-admin' ],
					'conditional_logic' => $custom_cond,
				];
			}

			if ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ) {
				$fields[] = [
					'key'               => 'field_clasbpro_class_email_' . $type . '_offset_amount',
					'label'             => __( 'Send', 'class-bookings-with-stripe-pro' ),
					'name'              => 'class_email_' . $type . '_offset_amount',
					'type'              => 'number',
					'min'               => 1,
					'step'              => 1,
					'default_value'     => Scheduled_Emails::TYPE_REMINDER === $type ? 24 : 3,
					'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-scheduled-email-controls' ],
					'conditional_logic' => $custom_cond,
				];
				$fields[] = [
					'key'               => 'field_clasbpro_class_email_' . $type . '_offset_unit',
					'label'             => Scheduled_Emails::TYPE_REMINDER === $type
						? __( 'Before class', 'class-bookings-with-stripe-pro' )
						: __( 'After class', 'class-bookings-with-stripe-pro' ),
					'name'              => 'class_email_' . $type . '_offset_unit',
					'type'              => 'select',
					'choices'           => [
						'minutes' => __( 'Minutes', 'class-bookings-with-stripe-pro' ),
						'hours'   => __( 'Hours', 'class-bookings-with-stripe-pro' ),
						'days'    => __( 'Days', 'class-bookings-with-stripe-pro' ),
						'weeks'   => __( 'Weeks', 'class-bookings-with-stripe-pro' ),
						'months'  => __( 'Months', 'class-bookings-with-stripe-pro' ),
					],
					'default_value'     => 'hours',
					'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-scheduled-email-controls' ],
					'conditional_logic' => $custom_cond,
				];
				$fields[] = [
					'key'               => 'field_clasbpro_class_email_' . $type . '_admin_copy',
					'label'             => __( 'Admin copy', 'class-bookings-with-stripe-pro' ),
					'name'              => 'class_email_' . $type . '_admin_copy',
					'type'              => 'true_false',
					'ui'                => 1,
					'default_value'     => 0,
					'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-scheduled-email-controls' ],
					'conditional_logic' => $custom_cond,
				];
			}

			$fields[] = [
				'key'               => 'field_clasbpro_class_email_' . $type . '_subject',
				'label'             => self::class_email_subject_label( $type ),
				'name'              => 'class_email_' . $type . '_subject',
				'type'              => 'text',
				'wrapper'           => [
					'class' => 'clasbpro-email-section clasbpro-email-section-' . $section
						. ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ? ' clasbpro-scheduled-email-content' : '' ),
				],
				'conditional_logic' => $custom_cond,
			];

			$default_body = Emails::default_body_template( $type );
			$fields[]     = [
				'key'               => 'field_clasbpro_class_email_' . $type . '_body',
				'label'             => self::class_email_body_label( $type ),
				'name'              => 'class_email_' . $type . '_body',
				'type'              => 'wysiwyg',
				'tabs'              => 'visual',
				'toolbar'           => 'basic',
				'media_upload'      => 0,
				'default_value'     => $default_body,
				'wrapper'           => [
					'class' => 'clasbpro-email-section clasbpro-email-section-' . $section
						. ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ? ' clasbpro-scheduled-email-content' : '' )
						. ' clasbpro-email-body-visual-field',
				],
				'conditional_logic' => $custom_cond,
			];
			$fields[]     = [
				'key'               => 'field_clasbpro_class_email_' . $type . '_body_html',
				'label'             => self::class_email_body_label( $type ) . ' (HTML)',
				'name'              => 'class_email_' . $type . '_body_html',
				'type'              => 'textarea',
				'rows'              => 16,
				'new_lines'         => '',
				'instructions'      => __( 'Body fragment only — merge tags like {customer_name} are supported. The plugin wraps this in the standard email layout when sending.', 'class-bookings-with-stripe-pro' ),
				'wrapper'           => [
					'class' => 'clasbpro-email-section clasbpro-email-section-' . $section
						. ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ? ' clasbpro-scheduled-email-content' : '' )
						. ' clasbpro-email-body-html-field',
				],
				'conditional_logic' => $custom_cond,
			];
			$fields[]     = [
				'key'               => 'field_clasbpro_class_email_' . $type . '_body_editor_mode',
				'label'             => self::class_email_editor_mode_label( $type ),
				'name'              => 'class_email_' . $type . '_body_editor_mode',
				'type'              => 'select',
				'choices'           => [
					'visual' => __( 'Visual', 'class-bookings-with-stripe-pro' ),
					'html'   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
				],
				'default_value'     => 'visual',
				'wrapper'           => [
					'class' => 'clasbpro-email-section clasbpro-email-section-' . $section
						. ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ? ' clasbpro-scheduled-email-content' : '' )
						. ' clasbpro-email-body-mode-field',
				],
				'conditional_logic' => $custom_cond,
			];

			if ( in_array( $type, Class_Email_Overrides::SCHEDULED_TYPES, true ) ) {
				$fields[] = [
					'key'               => 'field_clasbpro_class_email_' . $type . '_rule_uuid',
					'label'             => '',
					'name'              => 'class_email_' . $type . '_rule_uuid',
					'type'              => 'text',
					'wrapper'           => [ 'class' => 'clasbpro-email-rule-uuid-field' ],
					'conditional_logic' => $custom_cond,
				];
			}

			$fields[] = [
				'key'               => 'field_clasbpro_class_email_tab_intro_' . $type,
				'label'             => '',
				'name'              => '_clasbpro_class_email_tab_intro_' . $type,
				'type'              => 'message',
				'message'           => '',
				'new_lines'         => '',
				'esc_html'          => 0,
				'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-email-tab-intro-field' ],
				'conditional_logic' => $custom_cond,
			];
			$fields[] = [
				'key'               => 'field_clasbpro_class_email_test_send_' . $type,
				'label'             => __( 'Send test email', 'class-bookings-with-stripe-pro' ),
				'name'              => '_clasbpro_class_email_test_send_' . $type,
				'type'              => 'message',
				'message'           => '',
				'new_lines'         => '',
				'esc_html'          => 0,
				'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-email-tab-test-field' ],
				'conditional_logic' => $custom_cond,
			];
			$fields[] = [
				'key'               => 'field_clasbpro_class_email_reset_' . $type,
				'label'             => __( 'Reset', 'class-bookings-with-stripe-pro' ),
				'name'              => '_clasbpro_class_email_reset_' . $type,
				'type'              => 'message',
				'message'           => '',
				'new_lines'         => '',
				'esc_html'          => 0,
				'wrapper'           => [ 'class' => 'clasbpro-email-section clasbpro-email-section-' . $section . ' clasbpro-class-email-reset-field' ],
				'conditional_logic' => $custom_cond,
			];
		}

		return $fields;
	}

	private static function class_email_subject_label( string $type ): string {
		$labels = [
			'admin'      => __( 'Admin email subject', 'class-bookings-with-stripe-pro' ),
			'customer'   => __( 'Customer email subject', 'class-bookings-with-stripe-pro' ),
			'reminder'   => __( 'Reminder subject', 'class-bookings-with-stripe-pro' ),
			'post_class' => __( 'Post-class subject', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $type ] ?? __( 'Email subject', 'class-bookings-with-stripe-pro' );
	}

	private static function class_email_body_label( string $type ): string {
		$labels = [
			'admin'      => __( 'Admin email body', 'class-bookings-with-stripe-pro' ),
			'customer'   => __( 'Customer email body', 'class-bookings-with-stripe-pro' ),
			'reminder'   => __( 'Reminder body', 'class-bookings-with-stripe-pro' ),
			'post_class' => __( 'Post-class body', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $type ] ?? __( 'Email body', 'class-bookings-with-stripe-pro' );
	}

	private static function class_email_editor_mode_label( string $type ): string {
		$labels = [
			'admin'      => __( 'Admin email editor mode', 'class-bookings-with-stripe-pro' ),
			'customer'   => __( 'Customer email editor mode', 'class-bookings-with-stripe-pro' ),
			'reminder'   => __( 'Reminder editor mode', 'class-bookings-with-stripe-pro' ),
			'post_class' => __( 'Post-class editor mode', 'class-bookings-with-stripe-pro' ),
		];

		return $labels[ $type ] ?? __( 'Email editor mode', 'class-bookings-with-stripe-pro' );
	}

	/**
	 * @param array<string, mixed> $field
	 */
	public static function render_class_email_subtabs_nav( array $field ): void {
		$tabs         = [
			'admin'      => __( 'Admin email', 'class-bookings-with-stripe-pro' ),
			'customer'   => __( 'Customer email', 'class-bookings-with-stripe-pro' ),
			'reminders'  => __( 'Scheduled reminders', 'class-bookings-with-stripe-pro' ),
			'post-class' => __( 'Scheduled post-class', 'class-bookings-with-stripe-pro' ),
		];
		$descriptions = Emails::get_email_section_descriptions();
		unset( $descriptions['extras'] );
		?>
		<div class="clasbpro-email-subtabs-shell">
			<nav class="clasbpro-email-subtabs" aria-label="<?php esc_attr_e( 'Class email sections', 'class-bookings-with-stripe-pro' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<button
						type="button"
						class="clasbpro-email-subtabs__btn<?php echo 'admin' === $slug ? ' is-active' : ''; ?>"
						data-clasbpro-email-section="<?php echo esc_attr( $slug ); ?>"
						data-clasbpro-description="<?php echo esc_attr( $descriptions[ $slug ] ?? '' ); ?>"
					><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</nav>
			<p class="clasbpro-email-subtabs-description description" aria-live="polite">
				<?php echo esc_html( Emails::get_email_section_description( 'admin' ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $field
	 */
	public static function render_email_subtabs_nav( array $field ): void {
		$tabs         = [
			'admin'            => __( 'Admin email', 'class-bookings-with-stripe-pro' ),
			'customer'         => __( 'Customer email', 'class-bookings-with-stripe-pro' ),
			'admin-coupon'     => __( 'Admin coupon email', 'class-bookings-with-stripe-pro' ),
			'customer-coupon'  => __( 'Customer coupon email', 'class-bookings-with-stripe-pro' ),
			'reminders'        => __( 'Scheduled reminders', 'class-bookings-with-stripe-pro' ),
			'post-class'       => __( 'Scheduled post-class', 'class-bookings-with-stripe-pro' ),
			'extras'           => __( 'Extras', 'class-bookings-with-stripe-pro' ),
		];
		$descriptions = Emails::get_email_section_descriptions();
		?>
		<div class="clasbpro-email-subtabs-shell">
			<nav class="clasbpro-email-subtabs" aria-label="<?php esc_attr_e( 'Email settings sections', 'class-bookings-with-stripe-pro' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<button
						type="button"
						class="clasbpro-email-subtabs__btn<?php echo 'admin' === $slug ? ' is-active' : ''; ?>"
						data-clasbpro-email-section="<?php echo esc_attr( $slug ); ?>"
						data-clasbpro-hash="field_clasbpro_email_subtab_<?php echo esc_attr( str_replace( '-', '_', $slug ) ); ?>"
						data-clasbpro-description="<?php echo esc_attr( $descriptions[ $slug ] ?? '' ); ?>"
					><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</nav>
			<p class="clasbpro-email-subtabs-description description" aria-live="polite">
				<?php echo esc_html( Emails::get_email_section_description( 'admin' ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_help_section_descriptions(): array {
		return [
			'setup'      => __( 'Stripe keys, webhooks, reliable email delivery, and publishing your first classes.', 'class-bookings-with-stripe-pro' ),
			'shortcodes' => __( 'Place booking forms, result pages, and the schedule calendar on your site.', 'class-bookings-with-stripe-pro' ),
			'developer'  => __( 'REST routes, webhooks, template overrides, and hooks for custom builds.', 'class-bookings-with-stripe-pro' ),
		];
	}

	/**
	 * @param array<string, mixed> $field
	 */
	public static function render_help_subtabs_nav( array $field ): void {
		unset( $field );
		$tabs         = [
			'setup'      => __( 'Setup', 'class-bookings-with-stripe-pro' ),
			'shortcodes' => __( 'Shortcodes', 'class-bookings-with-stripe-pro' ),
			'developer'  => __( 'Developer', 'class-bookings-with-stripe-pro' ),
		];
		$descriptions = self::get_help_section_descriptions();
		?>
		<div class="clasbpro-help-subtabs-shell">
			<nav class="clasbpro-help-subtabs" aria-label="<?php esc_attr_e( 'Help sections', 'class-bookings-with-stripe-pro' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<button
						type="button"
						class="clasbpro-help-subtabs__btn<?php echo 'setup' === $slug ? ' is-active' : ''; ?>"
						data-clasbpro-help-section="<?php echo esc_attr( $slug ); ?>"
						data-clasbpro-hash="field_clasbpro_help_subtab_<?php echo esc_attr( $slug ); ?>"
						data-clasbpro-description="<?php echo esc_attr( $descriptions[ $slug ] ?? '' ); ?>"
					><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</nav>
			<p class="clasbpro-help-subtabs-description description" aria-live="polite">
				<?php echo esc_html( $descriptions['setup'] ?? '' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * @param int|string $post_id
	 */
	private static function is_settings_options_post_id( $post_id ): bool {
		return in_array( (string) $post_id, [ self::SETTINGS_POST_ID, 'options' ], true );
	}

	/**
	 * Native acf_form() settings screen (ACF Free fallback).
	 */
	private static function is_settings_acf_form_submit(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked by ACF before save handlers run.
		return isset( $_POST['_acf_form'] );
	}

	/**
	 * @param int|string $post_id
	 * @param array      $form
	 * @return int|string
	 */
	public static function stash_settings_form_post( $post_id, $form ) {
		unset( $form );
		self::$settings_form_acf_stash = [];

		if ( ! self::is_settings_acf_form_submit() || ! self::is_settings_options_post_id( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return $post_id;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked by ACF before save handlers run.
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) {
			return $post_id;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		self::$settings_form_acf_stash = wp_unslash( $_POST['acf'] );

		return $post_id;
	}

	/**
	 * @param int|string $post_id
	 */
	public static function persist_settings_form_from_stash( $post_id ): void {
		if ( empty( self::$settings_form_acf_stash ) ) {
			return;
		}

		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			$post_id = self::SETTINGS_POST_ID;
		}

		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			self::$settings_form_acf_stash = [];
			return;
		}

		if ( ! function_exists( 'acf_get_field' ) || ! function_exists( 'update_field' ) ) {
			self::$settings_form_acf_stash = [];
			return;
		}

		$stash = self::$settings_form_acf_stash;
		self::$settings_form_acf_stash = [];

		foreach ( $stash as $field_key => $value ) {
			if ( ! is_string( $field_key ) || '' === $field_key ) {
				continue;
			}

			$field = acf_get_field( $field_key );
			if ( ! is_array( $field ) ) {
				continue;
			}

			$type = (string) ( $field['type'] ?? '' );
			if ( in_array( $type, [ 'tab', 'message', 'accordion' ], true ) ) {
				continue;
			}

			$name = (string) ( $field['name'] ?? '' );
			if ( '' === $name || str_starts_with( $name, '_' ) ) {
				continue;
			}

			update_field( $field_key, $value, $post_id );
		}
	}

	/**
	 * @param array      $form
	 * @param int|string $post_id
	 */
	public static function persist_settings_form_on_submit( array $form, $post_id ): void {
		unset( $post_id );
		$target_id = (string) ( $form['post_id'] ?? self::SETTINGS_POST_ID );
		self::persist_settings_form_from_stash( $target_id );
	}

	/**
	 * @return list<string>
	 */
	private static function settings_field_keys(): array {
		if ( ! function_exists( 'acf_get_fields' ) ) {
			return [];
		}

		$fields = acf_get_fields( 'group_clasbpro_settings' );
		if ( ! is_array( $fields ) ) {
			return [];
		}

		return self::flatten_acf_field_keys( $fields );
	}

	/**
	 * @param array<int, array<string, mixed>> $fields
	 * @return list<string>
	 */
	private static function flatten_acf_field_keys( array $fields ): array {
		$keys = [];
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( ! empty( $field['key'] ) && is_string( $field['key'] ) ) {
				$keys[] = $field['key'];
			}
			if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				$keys = array_merge( $keys, self::flatten_acf_field_keys( $field['sub_fields'] ) );
			}
		}
		return $keys;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function load_stripe_currency_field( array $field ): array {
		$field['choices'] = self::stripe_currency_choices();
		return $field;
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @return mixed
	 */
	public static function load_stripe_currency_value( $value, $post_id, $field ) {
		unset( $field );
		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			return $value;
		}

		$stored = get_option( Constants::STRIPE_CURRENCY_OPTION, '' );
		if ( is_string( $stored ) && '' !== $stored && array_key_exists( $stored, Helpers::stripe_currencies() ) ) {
			return $stored;
		}

		return $value;
	}

	/**
	 * Read submitted currency before ACF 6.8+ strips undeclared $_POST['acf'] keys.
	 *
	 * @param int|string $post_id
	 * @param array      $form
	 * @return int|string
	 */
	public static function persist_stripe_currency_pre_save( $post_id, $form ) {
		unset( $form );
		if ( ! self::is_settings_options_post_id( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return $post_id;
		}

		$code = self::stripe_currency_from_post();
		if ( '' !== $code ) {
			Helpers::save_stripe_currency( $code );
		}

		return $post_id;
	}

	/**
	 * Ensure the currency field survives ACF front-end form save whitelisting.
	 *
	 * @param array<int, string> $keys
	 * @param array              $form
	 * @return array<int, string>
	 */
	public static function allow_settings_form_field_keys( array $keys, array $form ): array {
		$post_id = (string) ( $form['post_id'] ?? '' );
		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			return $keys;
		}

		$keys = array_merge( $keys, self::settings_field_keys() );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked by ACF before save handlers run.
		if ( ! empty( $_POST['acf'] ) && is_array( $_POST['acf'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			foreach ( array_keys( wp_unslash( $_POST['acf'] ) ) as $submitted_key ) {
				if ( is_string( $submitted_key ) && '' !== $submitted_key ) {
					$keys[] = $submitted_key;
				}
			}
		}

		$keys = array_filter( (array) $keys, 'is_scalar' );
		return array_values( array_unique( array_filter( array_map( 'strval', $keys ) ) ) );
	}

	/**
	 * @return string Lowercase currency code or empty string.
	 */
	private static function stripe_currency_from_post(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$acf = wp_unslash( $_POST['acf'] );

		if ( isset( $acf['field_clasbpro_stripe_currency'] ) ) {
			$code = strtolower( sanitize_key( (string) $acf['field_clasbpro_stripe_currency'] ) );
			return array_key_exists( $code, Helpers::stripe_currencies() ) ? $code : '';
		}

		if ( function_exists( 'acf_get_field' ) ) {
			foreach ( $acf as $field_key => $raw ) {
				if ( ! is_string( $field_key ) ) {
					continue;
				}
				$field = acf_get_field( $field_key );
				if ( is_array( $field ) && 'stripe_currency' === ( $field['name'] ?? '' ) ) {
					$code = strtolower( sanitize_key( (string) $raw ) );
					return array_key_exists( $code, Helpers::stripe_currencies() ) ? $code : '';
				}
			}
		}

		return '';
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @return mixed
	 */
	public static function persist_stripe_currency_value( $value, $post_id, $field ) {
		unset( $field );
		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			return $value;
		}

		$code = strtolower( sanitize_key( (string) $value ) );
		if ( '' !== $code ) {
			Helpers::save_stripe_currency( $code );
		}

		return $value;
	}

	/**
	 * Fallback when ACF does not process the select (e.g. synced DB field groups).
	 *
	 * @param int|string $post_id
	 */
	public static function persist_stripe_currency_from_request( $post_id ): void {
		if ( ! self::is_settings_options_post_id( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$code = self::stripe_currency_from_post();
		if ( '' !== $code ) {
			Helpers::save_stripe_currency( $code );
		}
	}

	/**
	 * @return array<int, int>
	 */
	public static function get_saved_schedule_class_ids(): array {
		return self::read_schedule_class_ids_option();
	}

	/**
	 * @return array<int, int>
	 */
	private static function read_schedule_class_ids_option(): array {
		$stored = get_option( Constants::SCHEDULE_CLASS_IDS_OPTION, null );
		if ( is_array( $stored ) ) {
			return Helpers::normalize_schedule_class_ids( $stored );
		}

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( 'schedule_classes', self::SETTINGS_POST_ID, false );
			if ( is_array( $value ) && ! empty( $value ) ) {
				return Helpers::normalize_schedule_class_ids( $value );
			}
		}

		return [];
	}

	private static function save_schedule_class_ids( array $ids ): void {
		update_option( Constants::SCHEDULE_CLASS_IDS_OPTION, Helpers::normalize_schedule_class_ids( $ids ), false );
	}

	/**
	 * @return array<int, int>|null Null when the relationship field was not submitted.
	 */
	private static function schedule_classes_from_post(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by ACF before save handlers run.
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$acf = wp_unslash( $_POST['acf'] );

		if ( array_key_exists( 'field_clasbpro_schedule_classes', $acf ) ) {
			$raw = $acf['field_clasbpro_schedule_classes'];
			return Helpers::normalize_schedule_class_ids( is_array( $raw ) ? $raw : [] );
		}

		if ( function_exists( 'acf_get_field' ) ) {
			foreach ( $acf as $field_key => $raw ) {
				if ( ! is_string( $field_key ) ) {
					continue;
				}
				$field = acf_get_field( $field_key );
				if ( is_array( $field ) && 'schedule_classes' === ( $field['name'] ?? '' ) ) {
					return Helpers::normalize_schedule_class_ids( is_array( $raw ) ? $raw : [] );
				}
			}
		}

		return null;
	}

	/**
	 * @param int|string $post_id
	 * @param array      $form
	 * @return int|string
	 */
	public static function persist_schedule_classes_pre_save( $post_id, $form ) {
		unset( $form );
		if ( ! self::is_settings_options_post_id( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return $post_id;
		}

		$ids = self::schedule_classes_from_post();
		if ( null !== $ids ) {
			self::save_schedule_class_ids( $ids );
		}

		return $post_id;
	}

	/**
	 * @param int|string $post_id
	 */
	public static function persist_schedule_classes_from_request( $post_id ): void {
		if ( ! self::is_settings_options_post_id( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$ids = self::schedule_classes_from_post();
		if ( null !== $ids ) {
			self::save_schedule_class_ids( $ids );
		}
	}

	/**
	 * @param array      $form
	 * @param int|string $post_id
	 */
	public static function persist_schedule_classes_on_submit_form( array $form, $post_id ): void {
		unset( $form );
		self::persist_schedule_classes_from_request( $post_id );
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @return mixed
	 */
	public static function load_schedule_classes_value( $value, $post_id, $field ) {
		unset( $field );
		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			return $value;
		}

		$stored = get_option( Constants::SCHEDULE_CLASS_IDS_OPTION, null );
		if ( is_array( $stored ) ) {
			return self::read_schedule_class_ids_option();
		}

		if ( is_array( $value ) && ! empty( $value ) ) {
			$ids = Helpers::normalize_schedule_class_ids( $value );
			self::save_schedule_class_ids( $ids );
			return $ids;
		}

		return [];
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @return mixed
	 */
	public static function update_schedule_classes_value( $value, $post_id, $field ) {
		unset( $field );
		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			return $value;
		}

		$ids = Helpers::normalize_schedule_class_ids( $value );
		self::save_schedule_class_ids( $ids );

		return $ids;
	}

	/**
	 * @param array<string, mixed> $args
	 * @param array<string, mixed> $field
	 * @param mixed                $post_id
	 * @return array<string, mixed>
	 */
	public static function schedule_classes_relationship_query( array $args, array $field, $post_id ): array {
		unset( $field, $post_id );
		$args['post_status'] = [ 'publish', 'draft', 'pending', 'private' ];
		$args['orderby']     = 'title';
		$args['order']       = 'ASC';
		return $args;
	}

	/**
	 * @param mixed                $text
	 * @param mixed                $post
	 * @param array<string, mixed> $field
	 * @param mixed                $post_id
	 */
	public static function schedule_classes_relationship_result( $text, $post, array $field, $post_id ): string {
		unset( $field, $post_id );
		if ( ! $post instanceof \WP_Post || CPT::CLASS_PT !== $post->post_type ) {
			return is_string( $text ) ? $text : '';
		}
		$active = function_exists( 'get_field' ) ? (bool) get_field( 'class_active', $post->ID ) : true;
		if ( ! $active ) {
			$text = is_string( $text ) ? $text : '';
			$text .= ' (' . __( 'inactive', 'class-bookings-with-stripe-pro' ) . ')';
		}
		return is_string( $text ) ? $text : '';
	}

	/**
	 * @return array<string, string>
	 */
	private static function stripe_currency_choices(): array {
		$choices = [];
		foreach ( Helpers::stripe_currencies() as $code => $config ) {
			$choices[ $code ] = (string) $config['label'];
		}
		return $choices;
	}

	/**
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function filter_price_field_for_currency( array $field ): array {
		$field['label'] = Helpers::price_field_label();
		$decimals       = (int) ( Helpers::currency_config()['decimals'] ?? 2 );
		$field['step']  = 0 === $decimals ? 1 : 0.01;
		return $field;
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @return mixed
	 */
	public static function detect_stripe_currency_change( $value, $post_id, $field ) {
		unset( $field );
		if ( ! self::is_settings_options_post_id( $post_id ) ) {
			return $value;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return $value;
		}

		$old = Helpers::currency();
		$new = strtolower( trim( (string) $value ) );
		if ( '' === $new || $old === $new ) {
			return $value;
		}

		$currencies = Helpers::stripe_currencies();
		if ( ! array_key_exists( $new, $currencies ) ) {
			return $value;
		}

		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			set_transient( 'clasbpro_currency_changed_' . $user_id, $new, MINUTE_IN_SECONDS );
		}

		return $value;
	}

	public static function stripe_currency_changed_admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || CPT::CLASS_PT . '_page_' . self::SETTINGS_MENU_SLUG !== $screen->id ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$new_code = get_transient( 'clasbpro_currency_changed_' . $user_id );
		if ( ! is_string( $new_code ) || '' === $new_code ) {
			return;
		}

		delete_transient( 'clasbpro_currency_changed_' . $user_id );

		$config = Helpers::currency_config( $new_code );
		$label  = (string) ( $config['label'] ?? strtoupper( $new_code ) );

		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo esc_html(
			sprintf(
				/* translators: %s: currency label, e.g. US Dollar ($) */
				__( 'Currency updated to %s. Existing class prices were not converted — please review them before taking live bookings.', 'class-bookings-with-stripe-pro' ),
				$label
			)
		);
		echo '</p></div>';
	}

	public static function scheduled_email_admin_notices(): void {
		self::stripe_currency_changed_admin_notice();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$on_class_edit = isset( $_GET['post'] ) && CPT::CLASS_PT === get_post_type( (int) $_GET['post'] );
		$can_see       = $on_class_edit ? current_user_can( 'edit_posts' ) : current_user_can( 'manage_options' );
		if ( ! $can_see ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( current_user_can( 'manage_options' ) && isset( $_GET['clasbpro_backfill_done'] ) ) {
			$count = (int) $_GET['clasbpro_backfill_done'];
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html(
				sprintf(
					/* translators: %d: number of bookings queued */
					_n(
						'Scheduled emails queued for %d upcoming booking.',
						'Scheduled emails queued for %d upcoming bookings.',
						$count,
						'class-bookings-with-stripe-pro'
					),
					$count
				)
			);
			echo '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( current_user_can( 'manage_options' ) && ! empty( $_GET['clasbpro_booking_test_sent'] ) ) {
			$type = sanitize_key( (string) $_GET['clasbpro_booking_test_sent'] );
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html( Emails::test_sent_notice_message( $type ) );
			echo '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( current_user_can( 'manage_options' ) && ! empty( $_GET['clasbpro_booking_test_failed'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo esc_html__( 'The test email could not be sent. Check your test recipient address and that WordPress can send mail.', 'class-bookings-with-stripe-pro' );
			echo '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['clasbpro_class_email_test_sent'] ) ) {
			$type = sanitize_key( (string) $_GET['clasbpro_class_email_test_sent'] );
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html( Emails::test_sent_notice_message( $type ) );
			echo '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['clasbpro_class_email_test_failed'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo esc_html__( 'The test email could not be sent. Check your test recipient address and that WordPress can send mail.', 'class-bookings-with-stripe-pro' );
			echo '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['clasbpro_class_email_reset'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'Class email content was reset to the current global defaults.', 'class-bookings-with-stripe-pro' );
			echo '</p></div>';
		}
	}

	private static function default_customer_email(): string {
		return "Hi {customer_name},\n\nThanks for booking — we can't wait to see you!\n\nYour booking:\n• Class: {class_name}\n• When: {class_date} at {class_time}\n• Where: {location}\n• Duration: {duration} minutes\n• Seats: {seats}\n• Total paid: {amount_total}\n\nBooking reference: {booking_id}\n\nIf you need to cancel or change anything, just reply to this email.\n\nNamaste,\nSoulful Yoga";
	}

	private static function default_admin_email(): string {
		return "New booking received.\n\n• Customer: {customer_name} <{customer_email}>\n• Class: {class_name}\n• When: {class_date} at {class_time}\n• Where: {location}\n• Seats: {seats}\n• Total: {amount_total}\n• Booking reference: {booking_id}";
	}

	private static function default_customer_coupon_email(): string {
		return "Hi {customer_name},\n\nThanks for purchasing {pack_name}.\n\nYour coupon code: {pack_code}\nUses included: {pack_uses}\nTotal paid: {amount_total}\n\nRestore this coupon on a device:\n{restore_url}\n\nOn a class booking page, choose “Use coupon” (1 seat). Enter the code if this browser doesn’t recognise you yet.\n\nReference: {purchase_id}";
	}

	private static function default_admin_coupon_email(): string {
		return "New coupon purchase.\n\n• Customer: {customer_name} <{customer_email}>\n• Coupon: {pack_name}\n• Uses: {pack_uses}\n• Total: {amount_total}\n• Reference: {purchase_id}";
	}

	/**
	 * Message fields default to wpautop(), which wraps our hero markup in &lt;p&gt; and breaks the layout.
	 *
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function filter_booking_summary_field_format( array $field ): array {
		$field['new_lines'] = '';
		$field['esc_html']  = false;
		return $field;
	}

	/**
	 * Inject a read-only booking summary into the booking CPT edit screen.
	 *
	 * @param array<string,mixed> $field
	 * @return array<string,mixed>
	 */
	public static function populate_booking_summary_field( array $field ): array {
		$post_id = get_the_ID();
		if ( ! $post_id || CPT::BOOKING_PT !== get_post_type( $post_id ) ) {
			return $field;
		}

		$meta        = Bookings::get_meta( (int) $post_id );
		$class_id    = (int) $meta['class_id'];
		$class_title = $class_id ? get_the_title( $class_id ) : __( 'Unknown class', 'class-bookings-with-stripe-pro' );
		$class_data  = $class_id ? Helpers::get_class_data( $class_id ) : null;
		$display     = Bookings::get_booking_display_context( (int) $post_id, $class_data );
		$status_raw  = (string) ( $meta['status'] ?: '' );
		$status_slug = sanitize_html_class( $status_raw ?: 'unknown' );

		if ( Bookings::STATUS_PAID === $status_raw ) {
			$status_label = __( 'Paid', 'class-bookings-with-stripe-pro' );
		} elseif ( Bookings::STATUS_PENDING === $status_raw ) {
			$status_label = __( 'Pending', 'class-bookings-with-stripe-pro' );
		} elseif ( Bookings::STATUS_EXPIRED === $status_raw ) {
			$status_label = __( 'Expired', 'class-bookings-with-stripe-pro' );
		} elseif ( Bookings::STATUS_REFUNDED === $status_raw ) {
			$status_label = __( 'Refunded', 'class-bookings-with-stripe-pro' );
		} elseif ( '' !== $status_raw ) {
			$status_label = ucfirst( $status_raw );
		} else {
			$status_label = __( 'Unknown', 'class-bookings-with-stripe-pro' );
		}

		$class_edit = $class_id && current_user_can( 'edit_post', $class_id )
			? get_edit_post_link( $class_id, 'raw' )
			: '';
		$mailto     = (string) $meta['customer_email'] !== ''
			? 'mailto:' . sanitize_email( (string) $meta['customer_email'] )
			: '';

		ob_start();
		?>
		<div class="cbfs-admin-summary cbfs-admin-summary--modern">
			<header class="cbfs-admin-summary__hero">
				<div class="cbfs-admin-summary__hero-main">
					<p class="cbfs-admin-summary__kicker"><?php esc_html_e( 'Booking reference', 'class-bookings-with-stripe-pro' ); ?></p>
					<p class="cbfs-admin-summary__id">#<?php echo esc_html( (string) (int) $post_id ); ?></p>
				</div><span class="cbfs-admin-summary__status cbfs-admin-summary__status--<?php echo esc_attr( $status_slug ); ?>"><?php echo esc_html( $status_label ); ?></span>
			</header>

			<div class="cbfs-admin-summary__kv">
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Class', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value">
						<?php if ( $class_edit ) : ?>
							<a href="<?php echo esc_url( $class_edit ); ?>"><?php echo esc_html( (string) $class_title ); ?></a>
						<?php else : ?>
							<?php echo esc_html( (string) $class_title ); ?>
						<?php endif; ?>
					</div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'When', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value">
						<?php
						$date_fmt = Helpers::format_date( (string) $meta['class_date'] );
						$time_fmt = ! empty( $display['start_time'] )
							? Helpers::format_time( (string) $display['start_time'] )
							: '';
						if ( '' !== $time_fmt ) {
							echo esc_html(
								sprintf(
									/* translators: 1: formatted date, 2: formatted time */
									__( '%1$s at %2$s', 'class-bookings-with-stripe-pro' ),
									$date_fmt,
									$time_fmt
								)
							);
						} else {
							echo esc_html( $date_fmt );
						}
						?>
					</div>
				</div>
				<?php if ( ! empty( $display['location'] ) ) : ?>
					<div class="cbfs-admin-summary__kv-row">
						<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Where', 'class-bookings-with-stripe-pro' ); ?></span>
						<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( (string) $display['location'] ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $display['label'] ) ) : ?>
					<div class="cbfs-admin-summary__kv-row">
						<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Slot', 'class-bookings-with-stripe-pro' ); ?></span>
						<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( (string) $display['label'] ); ?></div>
					</div>
				<?php endif; ?>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Seats', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( (string) (int) $meta['seats'] ); ?></div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Total', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value cbfs-admin-summary__amount"><?php echo esc_html( Helpers::format_stripe_amount( (int) $meta['amount_total_pence'] ) ); ?></div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Customer', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( (string) $meta['customer_name'] ); ?></div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Email', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value">
						<?php if ( $mailto ) : ?>
							<a href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( (string) $meta['customer_email'] ); ?></a>
						<?php else : ?>
							—
						<?php endif; ?>
					</div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Waiver', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( ! empty( $meta['waiver_accepted'] ) ? __( 'Accepted', 'class-bookings-with-stripe-pro' ) : __( 'Not recorded', 'class-bookings-with-stripe-pro' ) ); ?></div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Mailing list', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( ! empty( $meta['mailchimp_opt_in'] ) ? __( 'Opted in', 'class-bookings-with-stripe-pro' ) : __( 'No', 'class-bookings-with-stripe-pro' ) ); ?></div>
				</div>
			</div>

			<div class="cbfs-admin-summary__stripe">
				<h4 class="cbfs-admin-summary__stripe-title"><?php esc_html_e( 'Stripe', 'class-bookings-with-stripe-pro' ); ?></h4>
				<div class="cbfs-admin-summary__mono-block">
					<span class="cbfs-admin-summary__mono-label"><?php esc_html_e( 'Session', 'class-bookings-with-stripe-pro' ); ?></span>
					<code class="cbfs-admin-summary__code"><?php echo esc_html( (string) $meta['stripe_session_id'] ) ?: '—'; ?></code>
				</div>
				<div class="cbfs-admin-summary__mono-block">
					<span class="cbfs-admin-summary__mono-label"><?php esc_html_e( 'Payment intent', 'class-bookings-with-stripe-pro' ); ?></span>
					<code class="cbfs-admin-summary__code"><?php echo esc_html( (string) $meta['stripe_payment_intent'] ) ?: '—'; ?></code>
				</div>
			</div>

			<?php
			$extra_rows = Extra_Fields::display_rows( $class_id, (string) ( $meta['extra_fields_json'] ?? '' ) );
			if ( ! empty( $extra_rows ) ) :
				?>
				<div class="cbfs-admin-summary__extras">
					<h4 class="cbfs-admin-summary__extras-title"><?php esc_html_e( 'Additional fields', 'class-bookings-with-stripe-pro' ); ?></h4>
					<table class="cbfs-admin-summary__table">
						<tbody>
						<?php foreach ( $extra_rows as $row ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></th>
								<td><?php echo esc_html( (string) ( $row['value'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<?php echo Booking_Email_Status::render_booking_panel( (int) $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		$field['message'] = (string) ob_get_clean();
		return $field;
	}

	/**
	 * Inject a read-only coupon purchase summary into the purchase CPT edit screen.
	 *
	 * @param array<string,mixed> $field
	 * @return array<string,mixed>
	 */
	public static function populate_pack_purchase_summary_field( array $field ): array {
		$post_id = get_the_ID();
		if ( ! $post_id || CPT::PACK_PURCHASE_PT !== get_post_type( $post_id ) ) {
			return $field;
		}

		$purchase_id = (int) $post_id;
		$pack_id     = (int) get_post_meta( $purchase_id, '_clasbpro_pack_id', true );
		$pack        = $pack_id ? Packs::get_pack_data( $pack_id ) : null;
		$pack_title  = $pack['name'] ?? ( $pack_id ? get_the_title( $pack_id ) : __( 'Unknown coupon', 'class-bookings-with-stripe-pro' ) );
		$status_raw  = Packs::get_purchase_status( $purchase_id );
		$status_slug = sanitize_html_class( $status_raw ?: 'unknown' );

		if ( Packs::STATUS_PAID === $status_raw ) {
			$status_label = __( 'Paid', 'class-bookings-with-stripe-pro' );
		} elseif ( Packs::STATUS_PENDING === $status_raw ) {
			$status_label = __( 'Pending', 'class-bookings-with-stripe-pro' );
		} elseif ( Packs::STATUS_EXPIRED === $status_raw ) {
			$status_label = __( 'Expired', 'class-bookings-with-stripe-pro' );
		} elseif ( '' !== $status_raw ) {
			$status_label = ucfirst( $status_raw );
		} else {
			$status_label = __( 'Unknown', 'class-bookings-with-stripe-pro' );
		}

		$customer_name  = (string) get_post_meta( $purchase_id, '_clasbpro_customer_name', true );
		$customer_email = (string) get_post_meta( $purchase_id, '_clasbpro_customer_email', true );
		$amount_total   = (int) get_post_meta( $purchase_id, '_clasbpro_amount_total', true );
		$uses           = (int) get_post_meta( $purchase_id, '_clasbpro_pack_uses', true );
		if ( $uses <= 0 && $pack ) {
			$uses = (int) $pack['uses'];
		}
		$expires_at   = (int) get_post_meta( $purchase_id, '_clasbpro_pack_expires_at', true );
		$created_gmt  = (string) get_post_meta( $purchase_id, '_clasbpro_created_gmt', true );
		$session_id   = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_session_id', true );
		$payment_intent = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_payment_intent', true );
		$promo_id     = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_promo_id', true );
		$promo_code   = '' !== $promo_id ? Stripe_Service::retrieve_promotion_code_string( $promo_id ) : '';

		$pack_edit = $pack_id && current_user_can( 'edit_post', $pack_id )
			? get_edit_post_link( $pack_id, 'raw' )
			: '';
		$mailto = '' !== $customer_email ? 'mailto:' . sanitize_email( $customer_email ) : '';

		$class_ids = $pack['class_ids'] ?? [];
		$unit_price = $pack ? Helpers::format_price( (float) $pack['unit_price'] ) : '';

		ob_start();
		?>
		<div class="cbfs-admin-summary cbfs-admin-summary--modern">
			<header class="cbfs-admin-summary__hero">
				<div class="cbfs-admin-summary__hero-main">
					<p class="cbfs-admin-summary__kicker"><?php esc_html_e( 'Purchase reference', 'class-bookings-with-stripe-pro' ); ?></p>
					<p class="cbfs-admin-summary__id">#<?php echo esc_html( (string) $purchase_id ); ?></p>
				</div><span class="cbfs-admin-summary__status cbfs-admin-summary__status--<?php echo esc_attr( $status_slug ); ?>"><?php echo esc_html( $status_label ); ?></span>
			</header>

			<div class="cbfs-admin-summary__kv">
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Coupon', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value">
						<?php if ( $pack_edit ) : ?>
							<a href="<?php echo esc_url( $pack_edit ); ?>"><?php echo esc_html( (string) $pack_title ); ?></a>
						<?php else : ?>
							<?php echo esc_html( (string) $pack_title ); ?>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( ! empty( $pack['description'] ) ) : ?>
					<div class="cbfs-admin-summary__kv-row">
						<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Description', 'class-bookings-with-stripe-pro' ); ?></span>
						<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( (string) $pack['description'] ); ?></div>
					</div>
				<?php endif; ?>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Uses', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value">
						<?php
						$usages      = Packs::get_purchase_usages( $purchase_id );
						$used_count  = count( $usages );
						$uses_total  = max( 0, $uses );
						if ( $uses_total > 0 ) {
							echo esc_html(
								sprintf(
									/* translators: 1: used count, 2: total uses */
									__( '%1$d of %2$d used', 'class-bookings-with-stripe-pro' ),
									$used_count,
									$uses_total
								)
							);
						} else {
							echo esc_html( (string) $used_count );
						}
						?>
					</div>
				</div>
				<?php if ( '' !== $unit_price ) : ?>
					<div class="cbfs-admin-summary__kv-row">
						<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Class unit price', 'class-bookings-with-stripe-pro' ); ?></span>
						<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( $unit_price ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $expires_at > 0 ) : ?>
					<div class="cbfs-admin-summary__kv-row">
						<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Expires', 'class-bookings-with-stripe-pro' ); ?></span>
						<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( Helpers::format_date( gmdate( 'Y-m-d', $expires_at ) ) ); ?></div>
					</div>
				<?php endif; ?>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Total', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value cbfs-admin-summary__amount"><?php echo esc_html( Helpers::format_stripe_amount( $amount_total ) ); ?></div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Customer', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value"><?php echo esc_html( $customer_name ?: '—' ); ?></div>
				</div>
				<div class="cbfs-admin-summary__kv-row">
					<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Email', 'class-bookings-with-stripe-pro' ); ?></span>
					<div class="cbfs-admin-summary__kv-value">
						<?php if ( $mailto ) : ?>
							<a href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $customer_email ); ?></a>
						<?php else : ?>
							—
						<?php endif; ?>
					</div>
				</div>
				<?php if ( '' !== $created_gmt ) : ?>
					<div class="cbfs-admin-summary__kv-row">
						<span class="cbfs-admin-summary__kv-label"><?php esc_html_e( 'Created', 'class-bookings-with-stripe-pro' ); ?></span>
						<div class="cbfs-admin-summary__kv-value">
							<?php
							echo esc_html(
								wp_date(
									get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
									strtotime( $created_gmt . ' UTC' )
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $class_ids ) ) : ?>
				<div class="cbfs-admin-summary__extras">
					<h4 class="cbfs-admin-summary__extras-title"><?php esc_html_e( 'Eligible classes', 'class-bookings-with-stripe-pro' ); ?></h4>
					<table class="cbfs-admin-summary__table">
						<tbody>
						<?php foreach ( $class_ids as $class_id ) : ?>
							<?php
							$class_id    = (int) $class_id;
							$class_title = $class_id ? get_the_title( $class_id ) : '';
							$class_edit  = $class_id && current_user_can( 'edit_post', $class_id )
								? get_edit_post_link( $class_id, 'raw' )
								: '';
							?>
							<tr>
								<th scope="row"><?php echo esc_html( '#' . $class_id ); ?></th>
								<td>
									<?php if ( $class_edit ) : ?>
										<a href="<?php echo esc_url( $class_edit ); ?>"><?php echo esc_html( $class_title ?: __( '(untitled)', 'class-bookings-with-stripe-pro' ) ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $class_title ?: '—' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<div class="cbfs-admin-summary__stripe">
				<h4 class="cbfs-admin-summary__stripe-title"><?php esc_html_e( 'Stripe', 'class-bookings-with-stripe-pro' ); ?></h4>
				<div class="cbfs-admin-summary__mono-block">
					<span class="cbfs-admin-summary__mono-label"><?php esc_html_e( 'Session', 'class-bookings-with-stripe-pro' ); ?></span>
					<code class="cbfs-admin-summary__code"><?php echo esc_html( $session_id ) ?: '—'; ?></code>
				</div>
				<div class="cbfs-admin-summary__mono-block">
					<span class="cbfs-admin-summary__mono-label"><?php esc_html_e( 'Payment intent', 'class-bookings-with-stripe-pro' ); ?></span>
					<code class="cbfs-admin-summary__code"><?php echo esc_html( $payment_intent ) ?: '—'; ?></code>
				</div>
				<div class="cbfs-admin-summary__mono-block">
					<span class="cbfs-admin-summary__mono-label"><?php esc_html_e( 'Promotion code ID', 'class-bookings-with-stripe-pro' ); ?></span>
					<code class="cbfs-admin-summary__code"><?php echo esc_html( $promo_id ) ?: '—'; ?></code>
				</div>
				<?php if ( '' !== $promo_code ) : ?>
					<div class="cbfs-admin-summary__mono-block">
						<span class="cbfs-admin-summary__mono-label"><?php esc_html_e( 'Promotion code', 'class-bookings-with-stripe-pro' ); ?></span>
						<code class="cbfs-admin-summary__code"><?php echo esc_html( $promo_code ); ?></code>
					</div>
				<?php endif; ?>
			</div>

			<?php
			if ( ! isset( $usages ) ) {
				$usages = Packs::get_purchase_usages( $purchase_id );
			}
			?>
			<div class="cbfs-admin-summary__extras cbfs-admin-summary__usages">
				<h4 class="cbfs-admin-summary__extras-title"><?php esc_html_e( 'Usage', 'class-bookings-with-stripe-pro' ); ?></h4>
				<?php if ( empty( $usages ) ) : ?>
					<p class="cbfs-admin-summary__empty"><?php esc_html_e( 'This coupon has not been used on any bookings yet.', 'class-bookings-with-stripe-pro' ); ?></p>
				<?php else : ?>
					<table class="cbfs-admin-summary__table cbfs-admin-summary__table--usages">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Class', 'class-bookings-with-stripe-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'When', 'class-bookings-with-stripe-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'class-bookings-with-stripe-pro' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Booking', 'class-bookings-with-stripe-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $usages as $usage ) : ?>
							<?php
							$when = trim(
								(string) ( $usage['class_date'] ?? '' )
								. ( ! empty( $usage['class_time'] ) ? ' · ' . $usage['class_time'] : '' )
							);
							$status_label = '' !== (string) ( $usage['status'] ?? '' )
								? ucfirst( (string) $usage['status'] )
								: '—';
							?>
							<tr>
								<td>
									<?php echo esc_html( (string) ( $usage['class_name'] ?? '—' ) ); ?>
									<?php if ( ! empty( $usage['location'] ) ) : ?>
										<br /><span class="cbfs-admin-summary__muted"><?php echo esc_html( (string) $usage['location'] ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $when !== '' ? $when : '—' ); ?></td>
								<td><?php echo esc_html( $status_label ); ?></td>
								<td>
									<?php if ( ! empty( $usage['edit_url'] ) ) : ?>
										<a href="<?php echo esc_url( (string) $usage['edit_url'] ); ?>">#<?php echo esc_html( (string) ( $usage['booking_id'] ?? '' ) ); ?></a>
									<?php else : ?>
										#<?php echo esc_html( (string) ( $usage['booking_id'] ?? '' ) ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<?php echo Booking_Email_Status::render_purchase_panel( $purchase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		$field['message'] = (string) ob_get_clean();
		return $field;
	}

	private static function help_asset_path( string $filename ): string {
		return CLASBOWPRO_DIR . 'assets/help/' . ltrim( $filename, '/' );
	}

	private static function help_asset_url( string $filename ): string {
		return CLASBOWPRO_URL . 'assets/help/' . ltrim( $filename, '/' );
	}

	private static function help_asset_exists( string $filename ): bool {
		return is_readable( self::help_asset_path( $filename ) );
	}

	private static function help_doc_image_link( string $filename, string $alt = '' ): string {
		if ( '' === $filename || ! self::help_asset_exists( $filename ) ) {
			return '';
		}

		$url = self::help_asset_url( $filename );

		return sprintf(
			'<a class="clasbpro-help-doc-row__thumb" href="%1$s" target="_blank" rel="noopener noreferrer" title="%2$s">'
			. '<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" class="clasbpro-help-doc-row__img" />'
			. '</a>',
			esc_url( $url ),
			esc_attr( $alt )
		);
	}

	private static function help_doc_media( ?string $filename, string $alt = '' ): string {
		$thumb = $filename ? self::help_doc_image_link( $filename, $alt ) : '';
		if ( '' === $thumb ) {
			return '<div class="clasbpro-help-doc-row__media" aria-hidden="true"></div>';
		}

		return '<div class="clasbpro-help-doc-row__media">' . $thumb . '</div>';
	}

	/**
	 * @param array<int, array{file: string, alt?: string}> $images
	 */
	private static function help_doc_media_images( array $images ): string {
		$markup = '';

		foreach ( $images as $image ) {
			$file = (string) ( $image['file'] ?? '' );
			$alt  = (string) ( $image['alt'] ?? '' );
			$markup .= self::help_doc_image_link( $file, $alt );
		}

		if ( '' === $markup ) {
			return '<div class="clasbpro-help-doc-row__media" aria-hidden="true"></div>';
		}

		return '<div class="clasbpro-help-doc-row__media">' . $markup . '</div>';
	}

	/**
	 * @param string|null                               $image_filename Single image filename.
	 * @param string|array<int, array{file: string, alt?: string}>|null $images_or_alt
	 */
	private static function help_doc_row( string $content, ?string $image_filename = null, $images_or_alt = '' ): string {
		if ( is_array( $images_or_alt ) ) {
			$has_image = false;
			foreach ( $images_or_alt as $image ) {
				$file = (string) ( $image['file'] ?? '' );
				if ( '' !== $file && self::help_asset_exists( $file ) ) {
					$has_image = true;
					break;
				}
			}
			$media = self::help_doc_media_images( $images_or_alt );
		} else {
			$has_image = $image_filename && self::help_asset_exists( $image_filename );
			$media     = self::help_doc_media( $image_filename, (string) $images_or_alt );
		}

		$classes = 'clasbpro-help-doc-row' . ( $has_image ? ' clasbpro-help-doc-row--has-image' : '' );

		return sprintf(
			'<div class="%1$s"><div class="clasbpro-help-doc-row__content">%2$s</div>%3$s</div>',
			esc_attr( $classes ),
			$content,
			$media
		);
	}

	private static function help_doc_code_block( string $code, string $label = '' ): string {
		$label_html = '';
		if ( '' !== $label ) {
			$label_html = '<p class="clasbpro-help-doc-row__code-label">' . esc_html( $label ) . '</p>';
		}

		return $label_html
			. '<pre class="clasbowi-doc__pre clasbpro-help-doc-row__code"><code>'
			. esc_html( $code )
			. '</code></pre>';
	}

	/**
	 * @param array<int, array{code: string, label?: string}> $blocks
	 */
	private static function help_doc_code_media( array $blocks ): string {
		$markup = '';

		foreach ( $blocks as $block ) {
			$code = (string) ( $block['code'] ?? '' );
			if ( '' === $code ) {
				continue;
			}
			$markup .= self::help_doc_code_block( $code, (string) ( $block['label'] ?? '' ) );
		}

		if ( '' === $markup ) {
			return '<div class="clasbpro-help-doc-row__media" aria-hidden="true"></div>';
		}

		return '<div class="clasbpro-help-doc-row__media clasbpro-help-doc-row__media--code">' . $markup . '</div>';
	}

	/**
	 * @param array<int, array{code: string, label?: string}> $code_blocks
	 */
	private static function help_doc_row_code( string $content, array $code_blocks ): string {
		$media    = self::help_doc_code_media( $code_blocks );
		$has_code = str_contains( $media, 'clasbpro-help-doc-row__media--code' );
		$classes  = 'clasbpro-help-doc-row' . ( $has_code ? ' clasbpro-help-doc-row--has-code' : '' );

		return sprintf(
			'<div class="%1$s"><div class="clasbpro-help-doc-row__content">%2$s</div>%3$s</div>',
			esc_attr( $classes ),
			$content,
			$media
		);
	}

	private static function help_subtab_url( string $subtab ): string {
		return admin_url(
			'edit.php?post_type=' . CPT::CLASS_PT . '&page=' . self::SETTINGS_MENU_SLUG . '#clasbpro-tab-field_clasbpro_help_subtab_' . sanitize_key( $subtab )
		);
	}

	private static function developer_webhooks_message(): string {
		$home        = home_url( '/' );
		$rest_base   = rest_url( CLASBOWPRO_REST_NS );
		$webhook_url = rest_url( CLASBOWPRO_REST_NS . '/stripe-webhook' );
		$checkout    = rest_url( CLASBOWPRO_REST_NS . '/checkout' );

		$ex_stripe_cli = 'stripe listen --forward-to ' . $webhook_url;
		$ex_curl       = 'curl -i -X POST ' . $checkout . " \\\n  -H 'Content-Type: application/json' \\\n  -d '{}'";

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Payment flow', 'class-bookings-with-stripe-pro' ); ?></h3>
	<ol class="clasbowi-doc__ol">
		<li><?php esc_html_e( 'The booking form POSTs to the REST checkout route with customer and class details.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'The plugin creates a pending booking (soft hold) and a Stripe Checkout Session, then returns a redirect URL.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'After payment, Stripe sends webhook events to your site; the plugin marks the booking paid and sends emails.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ol>
</div>
		<?php
		$rows = self::help_doc_row( (string) ob_get_clean(), null );

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'REST routes (namespace clasbowi/v1)', 'class-bookings-with-stripe-pro' ); ?></h3>
	<table class="clasbowi-doc__table">
		<thead><tr><th><?php esc_html_e( 'Method', 'class-bookings-with-stripe-pro' ); ?></th><th><?php esc_html_e( 'Path', 'class-bookings-with-stripe-pro' ); ?></th><th><?php esc_html_e( 'Role', 'class-bookings-with-stripe-pro' ); ?></th></tr></thead>
		<tbody>
			<tr><td>POST</td><td><code>/checkout</code></td><td><?php esc_html_e( 'Create session (browser / frontend).', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td>POST</td><td><code>/stripe-webhook</code></td><td><?php esc_html_e( 'Stripe-signed events only.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td>GET</td><td><code>/booking-status</code></td><td><?php esc_html_e( 'Poll booking state after redirect. Requires session ID and the per-booking status token from the success URL (or manage_options).', 'class-bookings-with-stripe-pro' ); ?></td></tr>
		</tbody>
	</table>
	<p class="clasbowi-doc__note"><?php esc_html_e( 'Full base URL (copy-friendly):', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => __( 'REST API base', 'class-bookings-with-stripe-pro' ),
					'code'  => untrailingslashit( $rest_base ),
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Webhook endpoint', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><strong><?php esc_html_e( 'Required event types:', 'class-bookings-with-stripe-pro' ); ?></strong>
		<code>checkout.session.completed</code>,
		<code>checkout.session.expired</code>,
		<code>checkout.session.async_payment_failed</code>
	</p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => __( 'Webhook URL', 'class-bookings-with-stripe-pro' ),
					'code'  => $webhook_url,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Local testing with Stripe CLI', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Forward Stripe webhook traffic to your local or tunnelled WordPress URL:', 'class-bookings-with-stripe-pro' ); ?></p>
	<p class="clasbowi-doc__muted"><?php esc_html_e( 'Use the signing secret the CLI prints (starts with whsec_) in this plugin’s Webhook signing secret field while testing, or create a separate test endpoint in the Dashboard.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => __( 'Stripe CLI', 'class-bookings-with-stripe-pro' ),
					'code'  => $ex_stripe_cli,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Example: probing checkout (expect validation errors without a full body)', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__muted"><?php esc_html_e( 'A real request is issued by the plugin’s JavaScript with class ID, date, seats, nonce, etc. Use this only to verify the route responds on your host.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => __( 'curl', 'class-bookings-with-stripe-pro' ),
					'code'  => $ex_curl,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Site URL dependency', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Stripe return URLs and webhook targets are built from your WordPress site URL. Ensure Settings → General has the correct address for the environment you are testing.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => __( 'Site URL', 'class-bookings-with-stripe-pro' ),
					'code'  => $home,
				],
			]
		);

		return $rows;
	}

	private static function developer_templates_message(): string {
		$filter_layout = <<<'PHP'
add_filter( 'clasbpro_template_path', function ( $path, $relative, $context ) {
	if ( 'booking' === $context && 'booking-form.php' === $relative ) {
		return get_stylesheet_directory() . '/class-bookings-with-stripe/booking-form.php';
	}
	return $path;
}, 10, 3 );
PHP;
		$filter_component = <<<'PHP'
add_filter( 'clasbpro_component_path', function ( $path, $layout, $slug ) {
	if ( 'booking-form' === $layout && 'email-field' === $slug ) {
		return get_stylesheet_directory() . '/class-bookings-with-stripe/booking-form/email-field.php';
	}
	return $path;
}, 10, 3 );
PHP;
		$layout_example = <<<'PHP'
// booking-form.php — layout HTML with components embedded:
<form class="cbfs-form__form">
	<div class="cbfs-form__grid cbfs-form__grid--2">
		<?php $view->render( 'name-field' ); ?>
		<?php $view->render( 'email-field' ); ?>
	</div>
	<?php $view->render( 'date-field' ); ?>
</form>
PHP;

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Component-based templates', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Layout files contain the HTML structure. Components render a single field or content block only — place $view->render() calls where you want each piece in the layout.', 'class-bookings-with-stripe-pro' ); ?></p>
	<ul class="clasbowi-doc__ul">
		<li><code>class-bookings-with-stripe/booking-form.php</code> — <?php esc_html_e( 'Form layout HTML (grid, card, form tags).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><code>class-bookings-with-stripe/booking-form/{slug}.php</code> — <?php esc_html_e( 'Components: name-field, email-field, submit-button, waiver, etc.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><code>class-bookings-with-stripe/booking-status.php</code> — <?php esc_html_e( 'Status layout HTML.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><code>class-bookings-with-stripe/booking-status/{slug}.php</code> — <?php esc_html_e( 'Status components: title, lede, details-list, hint, etc.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><code>class-bookings-with-stripe/assets/components/booking-form/{slug}.css</code> — <?php esc_html_e( 'Optional per-component CSS (auto-enqueued).', 'class-bookings-with-stripe-pro' ); ?></li>
	</ul>
</div>
		<?php
		$rows = self::help_doc_row( (string) ob_get_clean(), null );

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Layout example', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Embed component renders inside the layout file:', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => __( 'booking-form.php', 'class-bookings-with-stripe-pro' ),
					'code'  => $layout_example,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Filter: clasbpro_template_path', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Override the layout file path. Arguments: $path, $relative, $context (booking | status).', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => 'clasbpro_template_path',
					'code'  => $filter_layout,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Filter: clasbpro_component_path', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Override a single component partial. Arguments: $path, $layout (booking-form | booking-status), $slug.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => 'clasbpro_component_path',
					'code'  => $filter_component,
				],
			]
		);

		return $rows;
	}

	private static function developer_hooks_message(): string {
		$ex_filter_html = <<<'PHP'
add_filter( 'clasbpro_booking_html', function ( $html, $template_args, $template_path ) {
	// Inspect: $template_args['class_data'], ['dates'], ['atts'].
	return $html;
}, 10, 3 );
PHP;
		$ex_filter_labels = <<<'PHP'
add_filter( 'clasbpro_booking_labels', function ( $labels, $class_data, $dates ) {
	$labels['book_button'] = __( 'Pay securely', 'your-textdomain' );
	return $labels;
}, 10, 3 );
PHP;
		$ex_action = <<<'PHP'
add_action( 'clasbpro_booking_template_start', function ( $class_data, $dates ) {
	// Runs once before the form layout renders.
}, 10, 2 );
PHP;

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Filters (modify data or output)', 'class-bookings-with-stripe-pro' ); ?></h3>
	<table class="clasbowi-doc__table">
		<thead><tr><th><?php esc_html_e( 'Hook', 'class-bookings-with-stripe-pro' ); ?></th><th><?php esc_html_e( 'Typical use', 'class-bookings-with-stripe-pro' ); ?></th></tr></thead>
		<tbody>
			<tr><td><code>clasbpro_booking_template_args</code></td><td><?php esc_html_e( 'Adjust $class_data, $dates, or shortcode $atts before the template loads.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_status_template_args</code></td><td><?php esc_html_e( 'Same for booking status / result pages.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_booking_html</code></td><td><?php esc_html_e( 'Replace or wrap final booking form HTML.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_status_html</code></td><td><?php esc_html_e( 'Replace or wrap status page HTML.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_booking_labels</code></td><td><?php esc_html_e( 'Change button copy, hints, field labels (3rd param: $dates).', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_booking_title</code></td><td><?php esc_html_e( 'Filter heading text; receives title string + $class_data.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_component_path</code></td><td><?php esc_html_e( 'Point a component slug at a custom PHP partial.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_template_path</code></td><td><?php esc_html_e( 'Override layout template path (booking-form.php, booking-status.php).', 'class-bookings-with-stripe-pro' ); ?></td></tr>
		</tbody>
	</table>
</div>
		<?php
		$rows = self::help_doc_row( (string) ob_get_clean(), null );

		ob_start();
		?>
<div class="clasbowi-doc">
	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Example: wrap booking HTML', 'class-bookings-with-stripe-pro' ); ?></h4>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => 'clasbpro_booking_html',
					'code'  => $ex_filter_html,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Example: rename the pay button', 'class-bookings-with-stripe-pro' ); ?></h4>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => 'clasbpro_booking_labels',
					'code'  => $ex_filter_labels,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Actions (layout boundaries)', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><code>clasbpro_booking_template_start</code>, <code>clasbpro_booking_template_end</code>, <code>clasbpro_status_template_start</code>, <code>clasbpro_status_template_end</code> — <?php esc_html_e( 'fire once around each screen layout. For field order or structure, override booking-form.php; for a single field, override its component file.', 'class-bookings-with-stripe-pro' ); ?></p>
	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Example: run code before the form layout', 'class-bookings-with-stripe-pro' ); ?></h4>
</div>
		<?php
		$rows .= self::help_doc_row_code(
			(string) ob_get_clean(),
			[
				[
					'label' => 'clasbpro_booking_template_start',
					'code'  => $ex_action,
				],
			]
		);

		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'ACF fields on the booking form', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Create a Field Group in ACF and set the location rule to “Class Bookings with Stripe → Booking form class ID”, then pick the Class post ID. Supported types include text, email, number, textarea, select, radio, true/false.', 'class-bookings-with-stripe-pro' ); ?></p>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Email merge tags in templates', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'See the Emails tab for the full list. For ACF extras on the form:', 'class-bookings-with-stripe-pro' ); ?> <code>{acf:field_xxxxx}</code>, <code>{field_xxxxx}</code>, <?php esc_html_e( 'or', 'class-bookings-with-stripe-pro' ); ?> <code>{extra_fields}</code> <?php esc_html_e( 'for a summary block.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$rows .= self::help_doc_row( (string) ob_get_clean(), null );

		return $rows;
	}

	private static function help_intro_message(): string {
		$developer_link = self::help_subtab_url( 'developer' );
		$content        = self::help_plugin_meta_message()
			. '<div class="clasbpro-doc"><p class="clasbpro-doc__lead">'
			. esc_html__( 'Use the Setup sections below for Stripe keys, webhooks, reliable email delivery, then publish your classes.', 'class-bookings-with-stripe-pro' )
			. ' '
			. wp_kses_post(
				sprintf(
					/* translators: %s: URL to the Developer help sub-tab */
					__( 'The <a href="%s">Developer</a> section documents REST routes, hooks, and theme overrides for custom builds.', 'class-bookings-with-stripe-pro' ),
					esc_url( $developer_link )
				)
			)
			. '</p></div>';

		return self::help_doc_row( $content, null );
	}

	private static function help_stripe_keys_message(): string {
		$dash_test = 'https://dashboard.stripe.com/test/apikeys';
		$dash_live = 'https://dashboard.stripe.com/apikeys';

		ob_start();
		?>
<div class="clasbowi-doc">
	<p class="clasbowi-doc__lead"><?php esc_html_e( 'Stripe has separate keys for test and live mode. This plugin’s “Mode” setting (Stripe tab) decides which pair is used for Checkout and API calls.', 'class-bookings-with-stripe-pro' ); ?></p>
	<ol class="clasbowi-doc__ol clasbowi-doc__ol--spaced">
		<li>
			<strong><?php esc_html_e( 'Open the Stripe Dashboard', 'class-bookings-with-stripe-pro' ); ?></strong>
			— <?php esc_html_e( 'Log in at stripe.com and ensure you are in the correct account.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Turn on Test mode', 'class-bookings-with-stripe-pro' ); ?></strong>
			— <?php esc_html_e( 'Use the “Test mode” toggle in the Dashboard while developing. Test card numbers (e.g. 4242…) only work when test mode is on.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Go to Developers → API keys', 'class-bookings-with-stripe-pro' ); ?></strong>
			— <?php esc_html_e( 'Test:', 'class-bookings-with-stripe-pro' ); ?>
			<a href="<?php echo esc_url( $dash_test ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dash_test ); ?></a>.
			<?php esc_html_e( 'Live:', 'class-bookings-with-stripe-pro' ); ?>
			<a href="<?php echo esc_url( $dash_live ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $dash_live ); ?></a>.
		</li>
		<li>
			<strong><?php esc_html_e( 'Copy Publishable key and Secret key', 'class-bookings-with-stripe-pro' ); ?></strong>
			— <?php esc_html_e( 'Publishable keys start with pk_test_ or pk_live_. Secret keys start with sk_test_ or sk_live_. Never expose the secret key in frontend code or public repos.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Paste into WordPress', 'class-bookings-with-stripe-pro' ); ?></strong>
			— <?php esc_html_e( 'Stripe tab → “Publishable key (test)” + “Secret key (test)” (or the live fields). Set “Mode” to Test while developing.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Save settings', 'class-bookings-with-stripe-pro' ); ?></strong>
			— <?php esc_html_e( 'Click Update / Save on this options page. Switch to Live mode only when you are ready for real charges and you have pasted live keys.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
	</ol>
	<p class="clasbowi-doc__note"><?php esc_html_e( 'If Checkout fails with an authentication error, double-check that the mode matches the keys (test keys only with Mode = Test).', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		return self::help_doc_row(
			(string) ob_get_clean(),
			'setup-stripe-keys.png',
			__( 'Stripe Dashboard API keys screen', 'class-bookings-with-stripe-pro' )
		);
	}

	private static function help_webhooks_detail_message(): string {
		$webhook_url = rest_url( CLASBOWPRO_REST_NS . '/stripe-webhook' );

		ob_start();
		?>
<div class="clasbowi-doc">
	<p class="clasbowi-doc__lead"><?php esc_html_e( 'Webhooks let Stripe notify WordPress when payment succeeds, sessions expire, or async payment fails. Without a working webhook and signing secret, bookings may stay “pending” after payment.', 'class-bookings-with-stripe-pro' ); ?></p>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Your endpoint URL (exact)', 'class-bookings-with-stripe-pro' ); ?></h3>
	<pre class="clasbowi-doc__pre"><code><?php echo esc_html( $webhook_url ); ?></code></pre>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Troubleshooting', 'class-bookings-with-stripe-pro' ); ?></h3>
	<ul class="clasbowi-doc__ul">
		<li>
			<strong><?php esc_html_e( 'Apache HTML 404 on /wp-json/…', 'class-bookings-with-stripe-pro' ); ?></strong>
			<?php esc_html_e( 'If curl or Stripe gets an HTML “Not Found” page from Apache (not JSON from WordPress), the request never reached WordPress. Common causes:', 'class-bookings-with-stripe-pro' ); ?>
			<ul class="clasbowi-doc__ul">
				<li><?php esc_html_e( 'Permalinks are set to Plain, or rewrite rules were never saved — go to Settings → Permalinks, choose something other than Plain (e.g. Post name), and click Save Changes once. That refreshes rules so /wp-json/… is routed to WordPress.', 'class-bookings-with-stripe-pro' ); ?></li>
				<li><?php esc_html_e( 'Missing or ignored .htaccess rewrites in Docker — ensure the web server allows overrides (AllowOverride) for the document root so WordPress can write rewrite rules.', 'class-bookings-with-stripe-pro' ); ?></li>
			</ul>
			<?php esc_html_e( 'Always paste the URL shown above: rest_url() matches your permalink structure (pretty /wp-json/… vs ?rest_route=… on Plain).', 'class-bookings-with-stripe-pro' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( '400 JSON { "error": "invalid_signature" }', 'class-bookings-with-stripe-pro' ); ?></strong>
			<?php esc_html_e( 'The route is working; Stripe must send a signed payload. A manual curl with an empty body will fail signature verification. Use Stripe CLI forward or a real Dashboard test event.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
		<li>
			<strong><?php esc_html_e( 'Wrong host in redirects or Link headers', 'class-bookings-with-stripe-pro' ); ?></strong>
			<?php esc_html_e( 'Settings → General → WordPress Address and Site Address should match the URL Stripe and browsers use (public hostname, tunnel URL, or IP:port). Mismatches break checkout return URLs and can confuse which webhook URL to register.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
	</ul>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Steps in Stripe Dashboard', 'class-bookings-with-stripe-pro' ); ?></h3>
	<ol class="clasbowi-doc__ol clasbowi-doc__ol--spaced">
		<li><?php esc_html_e( 'Open Developers → Webhooks.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Click “Add endpoint” (or “+ Add” depending on UI).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Endpoint URL: paste the URL above (must be publicly reachable over HTTPS in production).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li>
			<?php esc_html_e( 'Under “Events to send”, choose these event types (or an equivalent custom selection that includes them):', 'class-bookings-with-stripe-pro' ); ?>
			<ul class="clasbowi-doc__ul">
				<li><code>checkout.session.completed</code></li>
				<li><code>checkout.session.expired</code></li>
				<li><code>checkout.session.async_payment_failed</code></li>
			</ul>
		</li>
		<li><?php esc_html_e( 'Save the endpoint. Open it and click “Reveal” under Signing secret — copy the whsec_… value.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'In WordPress → this page → Stripe tab → “Webhook signing secret”, paste that value and save.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ol>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Test vs live webhooks', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Stripe keeps separate webhook configurations for test and live. Create endpoints in both if you use both modes, each with its own signing secret, and paste the matching secret when you switch Mode in this plugin.', 'class-bookings-with-stripe-pro' ); ?></p>

	<?php echo wp_kses_post( self::help_webhooks_localhost_message() ); ?>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Verify delivery', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'In Stripe → Webhooks → your endpoint → “Attempts” / logs, you should see 2xx responses after a test payment. If you see 403 or signature errors, the signing secret in WordPress does not match that endpoint.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		return self::help_doc_row(
			(string) ob_get_clean(),
			'setup-stripe-webhooks.png',
			__( 'Stripe Dashboard webhooks screen', 'class-bookings-with-stripe-pro' )
		);
	}

	/**
	 * Help tab: localhost / tunnel guidance for Stripe webhooks (HTML fragment).
	 */
	private static function help_webhooks_localhost_message(): string {
		$webhook_full         = rest_url( CLASBOWPRO_REST_NS . '/stripe-webhook' );
		$webhook_path_parsed  = wp_parse_url( $webhook_full, PHP_URL_PATH );
		$webhook_path_example = is_string( $webhook_path_parsed ) && '' !== $webhook_path_parsed
			? $webhook_path_parsed
			: '/wp-json/' . CLASBOWPRO_REST_NS . '/stripe-webhook';

		ob_start();
		?>
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Localhost', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'For local development, Stripe’s servers cannot reach http://localhost or http://127.0.0.1 on your machine. You need a publicly reachable HTTPS URL that forwards traffic to the WordPress site that loads this plugin.', 'class-bookings-with-stripe-pro' ); ?></p>

	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Tunnels (recommended)', 'class-bookings-with-stripe-pro' ); ?></h4>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'A tunnel gives you a temporary public hostname with HTTPS. Point your Stripe webhook endpoint at the tunnel URL plus the same REST path WordPress uses (see “Your endpoint URL” above).', 'class-bookings-with-stripe-pro' ); ?></p>
	<ul class="clasbowi-doc__ul">
		<li>
			<strong>ngrok</strong> —
			<?php esc_html_e( 'Install ngrok, then forward the port where you can open WordPress in a browser (Docker example below uses 8101).', 'class-bookings-with-stripe-pro' ); ?>
			<pre class="clasbowi-doc__pre"><code>ngrok http 8101</code></pre>
			<?php
			printf(
				'<p class="clasbowi-doc__muted">%s</p>',
				sprintf(
					/* translators: %s: REST webhook path (e.g. /wp-json/clasbowi/v1/stripe-webhook) */
					esc_html__( 'ngrok prints an https://….ngrok-free.app (or similar) URL. In Stripe, set the endpoint to that origin plus your webhook path, e.g. https://abc123.ngrok-free.app%s', 'class-bookings-with-stripe-pro' ),
					esc_html( $webhook_path_example )
				)
			);
			?>
		</li>
		<li>
			<strong>Cloudflare Tunnel (cloudflared)</strong> —
			<?php esc_html_e( 'Useful for longer-lived dev URLs and teams.', 'class-bookings-with-stripe-pro' ); ?>
			<pre class="clasbowi-doc__pre"><code>cloudflared tunnel --url http://localhost:8101</code></pre>
		</li>
		<li>
			<strong>localtunnel</strong> —
			<pre class="clasbowi-doc__pre"><code>npx localtunnel --port 8101</code></pre>
		</li>
		<li>
			<strong>nip.io / sslip.io</strong> —
			<?php esc_html_e( 'These map a hostname to an IP address (e.g. 127.0.0.1.nip.io). They help WordPress see a stable hostname, but Stripe’s dashboard still expects a proper HTTPS endpoint. Use them together with a reverse proxy or TLS terminator, or prefer ngrok / Cloudflare Tunnel for webhooks.', 'class-bookings-with-stripe-pro' ); ?>
		</li>
	</ul>
	<p class="clasbowi-doc__note"><?php esc_html_e( 'Also see Help → Developer → “Local testing with Stripe CLI”: stripe listen forwards webhooks without exposing WordPress, which is ideal for verifying handler code.', 'class-bookings-with-stripe-pro' ); ?></p>

	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Port forwarding and “which port?”', 'class-bookings-with-stripe-pro' ); ?></h4>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Your tunnel must target the host port that actually reaches WordPress—not necessarily port 80 inside a container.', 'class-bookings-with-stripe-pro' ); ?></p>
	<ul class="clasbowi-doc__ul">
		<li><?php esc_html_e( 'Native PHP / local server: if the site runs at http://localhost:8080, run ngrok (or similar) against 8080.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Docker Desktop: if compose maps 8101 on your Mac to port 80 in the container (e.g. "8101:80"), tunnel to 8101 on the host.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Home/office router: only needed if you intentionally expose a machine to the internet without a tunnel. Stripe will hit your public IP: ensure the router forwards the chosen external port to your dev PC’s LAN IP and that a web server answers there.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ul>
	<pre class="clasbowi-doc__pre"><code># Example docker-compose port publish (host:container)
ports:
  - "8101:80"</code></pre>

	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Docker: publish and reach the right interface', 'class-bookings-with-stripe-pro' ); ?></h4>
	<ul class="clasbowi-doc__ul">
		<li><?php esc_html_e( 'Publish ports explicitly. Without a ports: mapping, nothing on the host can reach the container’s web server.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Bind to all interfaces when you need access from another device or a tunnel helper: use 0.0.0.0 in the mapping (Docker defaults this for host ports in many setups).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'If a second process (tunnel, reverse proxy) runs in another container and must reach WordPress on the host, Docker Desktop often provides host.docker.internal as the host gateway.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ul>
	<pre class="clasbowi-doc__pre"><code>docker compose ps
curl -I http://127.0.0.1:8101/wp-json/</code></pre>

	<h4 class="clasbowi-doc__h4"><?php esc_html_e( 'Firewalls (host and container)', 'class-bookings-with-stripe-pro' ); ?></h4>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'Tunnels usually work outbound-only (your machine initiates to ngrok), so home routers are fine—but corporate networks may block tunnel domains or non-standard TLS.', 'class-bookings-with-stripe-pro' ); ?></p>
	<ul class="clasbowi-doc__ul">
		<li><?php esc_html_e( 'macOS: System Settings → Network → Firewall — allow incoming for your local web server or Docker if you expose ports directly.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Windows: Windows Security → Firewall & network protection — allow the app (e.g. Docker Desktop) or the inbound port.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Linux (ufw): allow the published host port, e.g.', 'class-bookings-with-stripe-pro' ); ?> <code>sudo ufw allow 8101/tcp</code> <?php esc_html_e( 'then reload rules.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Cloud VPS: add a security group / firewall rule allowing HTTPS (443) from the internet to the instance running WordPress (or to the tunnel endpoint if the tunnel runs on the server).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Container-only firewalls (iptables/nftables inside a custom image) are rare on dev images but can block traffic; test with curl from the host into the published port first.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ul>
	<p class="clasbowi-doc__muted"><?php esc_html_e( 'After the tunnel is up, confirm WordPress “Site Address (URL)” in Settings → General matches what customers use (often the tunnel URL while testing), so rest_url() and Stripe return URLs stay consistent.', 'class-bookings-with-stripe-pro' ); ?></p>
		<?php
		return (string) ob_get_clean();
	}

	private static function help_email_smtp_message(): string {
		ob_start();
		?>
<div class="clasbowi-doc">
	<p class="clasbowi-doc__lead"><?php esc_html_e( 'This plugin sends booking emails through WordPress’s standard wp_mail() function (see Emails tab). On many hosts, PHP mail is unreliable: messages bounce, land in spam, or never leave the server.', 'class-bookings-with-stripe-pro' ); ?></p>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Why use WP Mail SMTP (or similar)?', 'class-bookings-with-stripe-pro' ); ?></h3>
	<ul class="clasbowi-doc__ul">
		<li><?php esc_html_e( 'Deliverability: send through a real SMTP provider (Google Workspace, SendGrid, Mailgun, Amazon SES, Postmark, etc.) with proper SPF/DKIM.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Reliability: avoids the host’s default mail() limits and silent failures.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Debugging: popular plugins log errors and offer “send test email” so you can confirm configuration before customers book.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ul>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'A widely used free option is “WP Mail SMTP” (WPForms). Other SMTP plugins work too if they hook wp_mail the same way.', 'class-bookings-with-stripe-pro' ); ?></p>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Recommended setup outline', 'class-bookings-with-stripe-pro' ); ?></h3>
	<ol class="clasbowi-doc__ol clasbowi-doc__ol--spaced">
		<li><?php esc_html_e( 'Install and activate WP Mail SMTP (or your preferred SMTP plugin).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Complete the wizard: choose your mailer (e.g. SendGrid API, Other SMTP, Google).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Set the From Email to an address your provider authorizes (often your domain).', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Send a test email from the SMTP plugin and confirm it arrives in your inbox.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Customer and admin booking emails use WordPress wp_mail() automatically; set subjects and bodies on the Emails tab.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ol>
</div>
		<?php
		return self::help_doc_row(
			(string) ob_get_clean(),
			'setup-wp-mail-smtp.png',
			__( 'WP Mail SMTP plugin settings screen', 'class-bookings-with-stripe-pro' )
		);
	}

	private static function help_next_steps_message(): string {
		$shortcodes_link = self::help_subtab_url( 'shortcodes' );
		ob_start();
		?>
<div class="clasbowi-doc">
	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Quick checklist', 'class-bookings-with-stripe-pro' ); ?></h3>
	<ol class="clasbowi-doc__ol">
		<li><?php esc_html_e( 'Create Classes (menu on the left) with schedule, price, and capacity.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Assign result pages under Result pages if the defaults are not suitable.', 'class-bookings-with-stripe-pro' ); ?></li>
		<li><?php esc_html_e( 'Place the Elementor “Class Booking with Stripe” widget or a shortcode on a page.', 'class-bookings-with-stripe-pro' ); ?></li>
	</ol>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Shortcodes & widgets', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p">
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: URL to the Shortcodes help sub-tab */
				__( 'See the <a href="%s">Shortcodes</a> section for booking forms, result pages, and the schedule calendar.', 'class-bookings-with-stripe-pro' ),
				esc_url( $shortcodes_link )
			)
		);
		?>
	</p>

	<h3 class="clasbowi-doc__h"><?php esc_html_e( 'Elementor: current post field', 'class-bookings-with-stripe-pro' ); ?></h3>
	<p class="clasbowi-doc__p"><?php esc_html_e( 'For loops or class cards, add an ACF (or meta) field on your content post with the internal name', 'class-bookings-with-stripe-pro' ); ?> <code>clasbpro_class_stripe_id</code> <?php esc_html_e( '(Class ID). Point the widget at “Current post field”.', 'class-bookings-with-stripe-pro' ); ?></p>
	<p class="clasbowi-doc__muted"><?php esc_html_e( 'Legacy field name clasbpro_class_stripe_id is still read if present.', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		return self::help_doc_row( (string) ob_get_clean(), null );
	}

	private static function help_shortcodes_message(): string {
		$booking_tag  = Constants::SHORTCODE_BOOKING;
		$status_tag   = Constants::SHORTCODE_STATUS;
		$schedule_tag = Constants::SHORTCODE_SCHEDULE;
		$packs_tag    = Constants::SHORTCODE_PACKS;

		$legacy_note = '<p class="clasbpro-doc__note">'
			. esc_html__( 'Older shortcode names from earlier releases still work, but prefer the clasbpro_* tags documented below.', 'class-bookings-with-stripe-pro' )
			. '</p>';

		ob_start();
		?>
<div class="clasbpro-doc">
	<h3 class="clasbpro-doc__h"><?php echo esc_html( '[' . $booking_tag . ']' ); ?></h3>
	<p class="clasbpro-doc__lead"><?php esc_html_e( 'Renders the booking form for a single class. Use on any page or post.', 'class-bookings-with-stripe-pro' ); ?></p>
	<pre class="clasbpro-doc__pre"><code>[<?php echo esc_html( $booking_tag ); ?> class_id="123"]</code></pre>
	<table class="clasbpro-doc__table">
		<thead><tr><th><?php esc_html_e( 'Attribute', 'class-bookings-with-stripe-pro' ); ?></th><th><?php esc_html_e( 'Description', 'class-bookings-with-stripe-pro' ); ?></th></tr></thead>
		<tbody>
			<tr><td><code>class_id</code></td><td><?php esc_html_e( 'Numeric Class post ID (recommended).', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>class_slug</code></td><td><?php esc_html_e( 'Class post slug instead of ID.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>clasbpro_class_stripe_id</code></td><td><?php esc_html_e( 'Alias for class_id (Elementor / legacy meta field name).', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>heading</code></td><td><?php esc_html_e( '1 (default) shows the class title; 0 hides it.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>preset_date</code></td><td><?php esc_html_e( 'Pre-select a date (Y-m-d) on recurring / one-off classes.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
			<tr><td><code>preset_slot_rule_id</code></td><td><?php esc_html_e( 'Pre-select an appointment slot rule ID.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
		</tbody>
	</table>
</div>
		<?php
		$booking_row = self::help_doc_row(
			(string) ob_get_clean(),
			null,
			[
				[
					'file' => 'shortcode-class.png',
					'alt'  => __( 'Default booking form shortcode preview', 'class-bookings-with-stripe-pro' ),
				],
				[
					'file' => 'shortcode-class-dropdown.png',
					'alt'  => __( 'Recurring class with date dropdown preview', 'class-bookings-with-stripe-pro' ),
				],
				[
					'file' => 'shortcode-event.png',
					'alt'  => __( 'One-off event booking form preview', 'class-bookings-with-stripe-pro' ),
				],
				[
					'file' => 'shortcode-appointments.png',
					'alt'  => __( 'Appointment-style class booking form preview', 'class-bookings-with-stripe-pro' ),
				],
			]
		);

		ob_start();
		?>
<div class="clasbpro-doc">
	<h3 class="clasbpro-doc__h"><?php echo esc_html( '[' . $schedule_tag . ']' ); ?></h3>
	<p class="clasbpro-doc__lead"><?php esc_html_e( 'Renders the multi-class schedule calendar. Classes are chosen under Settings → Result pages unless overridden below.', 'class-bookings-with-stripe-pro' ); ?></p>
	<pre class="clasbpro-doc__pre"><code>[<?php echo esc_html( $schedule_tag ); ?>]</code></pre>
	<pre class="clasbpro-doc__pre"><code>[<?php echo esc_html( $schedule_tag ); ?> class_ids="12,34,56"]</code></pre>
	<table class="clasbpro-doc__table">
		<thead><tr><th><?php esc_html_e( 'Attribute', 'class-bookings-with-stripe-pro' ); ?></th><th><?php esc_html_e( 'Description', 'class-bookings-with-stripe-pro' ); ?></th></tr></thead>
		<tbody>
			<tr><td><code>class_ids</code></td><td><?php esc_html_e( 'Optional comma-separated Class post IDs. Overrides the schedule list in settings.', 'class-bookings-with-stripe-pro' ); ?></td></tr>
		</tbody>
	</table>
</div>
		<?php
		$schedule_row = self::help_doc_row(
			(string) ob_get_clean(),
			'shortcode-schedule.png',
			__( 'Default schedule calendar shortcode preview', 'class-bookings-with-stripe-pro' )
		);

		$status_rows = '';
		foreach (
			[
				'success'   => [
					'label' => __( 'Payment succeeded', 'class-bookings-with-stripe-pro' ),
					'file'  => 'shortcode-status-success.png',
					'alt'   => __( 'Booking status shortcode — success preview', 'class-bookings-with-stripe-pro' ),
				],
				'cancelled' => [
					'label' => __( 'Customer cancelled checkout', 'class-bookings-with-stripe-pro' ),
					'file'  => 'shortcode-status-cancelled.png',
					'alt'   => __( 'Booking status shortcode — cancelled preview', 'class-bookings-with-stripe-pro' ),
				],
				'error'     => [
					'label' => __( 'Payment failed or could not be verified', 'class-bookings-with-stripe-pro' ),
					'file'  => 'shortcode-status-error.png',
					'alt'   => __( 'Booking status shortcode — error preview', 'class-bookings-with-stripe-pro' ),
				],
			] as $type => $cfg
		) {
			ob_start();
			?>
<div class="clasbpro-doc">
	<h3 class="clasbpro-doc__h"><?php echo esc_html( '[' . $status_tag . ']' ); ?> — <?php echo esc_html( (string) $cfg['label'] ); ?></h3>
	<p class="clasbpro-doc__lead"><?php esc_html_e( 'Result page after Stripe Checkout. Assign pages under Settings → Result pages.', 'class-bookings-with-stripe-pro' ); ?></p>
	<pre class="clasbpro-doc__pre"><code>[<?php echo esc_html( $status_tag ); ?> type="<?php echo esc_attr( $type ); ?>"]</code></pre>
	<?php if ( 'success' === $type ) : ?>
	<p class="clasbpro-doc__muted"><?php esc_html_e( 'On success, the page reads booking and session details from the URL query string after redirect from Stripe.', 'class-bookings-with-stripe-pro' ); ?></p>
	<?php endif; ?>
</div>
			<?php
			$status_rows .= self::help_doc_row( (string) ob_get_clean(), (string) $cfg['file'], (string) $cfg['alt'] );
		}

		ob_start();
		?>
<div class="clasbpro-doc">
	<h3 class="clasbpro-doc__h"><?php echo esc_html( '[' . $packs_tag . ']' ); ?></h3>
	<p class="clasbpro-doc__lead"><?php esc_html_e( 'Lists purchasable coupons. Create coupons under Classes → Coupons, then place this shortcode on a page.', 'class-bookings-with-stripe-pro' ); ?></p>
	<pre class="clasbpro-doc__pre"><code>[<?php echo esc_html( $packs_tag ); ?> id="1"]</code></pre>
	<pre class="clasbpro-doc__pre"><code>[<?php echo esc_html( $packs_tag ); ?> id="1,2,3"]</code></pre>
	<p class="clasbpro-doc__muted"><?php esc_html_e( 'Omit id to list every active coupon. After purchase, Stripe creates a unique coupon code. Customers can redeem it on eligible class booking forms (1 seat per use).', 'class-bookings-with-stripe-pro' ); ?></p>
</div>
		<?php
		$packs_row = self::help_doc_row( (string) ob_get_clean(), null, [] );

		return $legacy_note . $booking_row . $schedule_row . $packs_row . $status_rows;
	}

	private static function help_plugin_meta_message(): string {
		$version = defined( 'CLASBOWPRO_VERSION' ) ? (string) CLASBOWPRO_VERSION : 'unknown';
		$developer = 'IORoot.com';
		$website = 'https://ioroot.com';
		return sprintf(
			'<div class="clasbowi-doc clasbowi-doc--compact"><p class="clasbowi-doc__meta"><strong>%s</strong> %s &nbsp;·&nbsp; <strong>%s</strong> %s &nbsp;·&nbsp; <strong>%s</strong> <a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p></div>',
			esc_html__( 'Version:', 'class-bookings-with-stripe-pro' ),
			esc_html( $version ),
			esc_html__( 'Developer:', 'class-bookings-with-stripe-pro' ),
			esc_html( $developer ),
			esc_html__( 'Website:', 'class-bookings-with-stripe-pro' ),
			esc_url( $website ),
			esc_html( $website )
		);
	}
}
