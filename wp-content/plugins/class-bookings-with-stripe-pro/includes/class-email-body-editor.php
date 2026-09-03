<?php
/**
 * Settings UI: Visual vs HTML vs Raw HTML email body editors (separate fields, no sync).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Email_Body_Editor {

	public const MODE_VISUAL = 'visual';
	public const MODE_HTML   = 'html';
	public const MODE_RAW    = 'raw';

	/** Queue flag: visual wrap + wpautop. */
	public const QUEUE_VISUAL = 0;

	/** Queue flag: HTML fragment, wrapped, no wpautop. */
	public const QUEUE_HTML = 1;

	/** Queue flag: full document, send as-is after merge tags. */
	public const QUEUE_RAW = 2;

	/** @var list<string> */
	public const HTML_BODY_FIELD_KEYS = [
		'field_clasbpro_admin_email_body_html',
		'field_clasbpro_customer_email_body_html',
		'field_clasbpro_admin_coupon_email_body_html',
		'field_clasbpro_customer_coupon_email_body_html',
		'field_clasbpro_reminder_email_body_html',
		'field_clasbpro_post_class_email_body_html',
	];

	/** @var array<string, string> */
	private static array $raw_html_stash = [];

	public static function init(): void {
		add_filter( 'acf/pre_save_post', [ self::class, 'stash_raw_html_values' ], 1 );
		foreach ( self::HTML_BODY_FIELD_KEYS as $field_key ) {
			add_filter( "acf/update_value/key={$field_key}", [ self::class, 'restore_raw_html_value' ], 5, 3 );
		}
	}

	/**
	 * @return array<string, string>
	 */
	public static function mode_choices(): array {
		return [
			self::MODE_VISUAL => __( 'Visual', 'class-bookings-with-stripe-pro' ),
			self::MODE_HTML   => __( 'HTML', 'class-bookings-with-stripe-pro' ),
			self::MODE_RAW    => __( 'Raw HTML', 'class-bookings-with-stripe-pro' ),
		];
	}

	public static function html_field_instructions(): string {
		return __( 'HTML mode: body fragment — the plugin wraps it in the standard email layout. Raw HTML mode: full document — merge tags are replaced, then the HTML is sent as-is with no wrapping.', 'class-bookings-with-stripe-pro' );
	}

	public static function sanitize_mode( string $mode ): string {
		$mode = sanitize_key( $mode );
		if ( in_array( $mode, [ self::MODE_HTML, self::MODE_RAW ], true ) ) {
			return $mode;
		}

		return self::MODE_VISUAL;
	}

	/**
	 * Accept a stored mode, a legacy bool (html vs visual), or a queue flag 0/1/2.
	 *
	 * @param mixed $mode
	 */
	public static function normalize_mode( $mode ): string {
		if ( is_bool( $mode ) ) {
			return $mode ? self::MODE_HTML : self::MODE_VISUAL;
		}
		if ( is_int( $mode ) || ( is_string( $mode ) && ctype_digit( $mode ) ) ) {
			return self::mode_from_queue_flag( (int) $mode );
		}

		return self::sanitize_mode( (string) $mode );
	}

	public static function uses_html_field( string $mode ): bool {
		return in_array( $mode, [ self::MODE_HTML, self::MODE_RAW ], true );
	}

	public static function wraps_layout( string $mode ): bool {
		return self::MODE_RAW !== self::sanitize_mode( $mode );
	}

	public static function queue_flag( string $mode ): int {
		switch ( self::sanitize_mode( $mode ) ) {
			case self::MODE_RAW:
				return self::QUEUE_RAW;
			case self::MODE_HTML:
				return self::QUEUE_HTML;
			default:
				return self::QUEUE_VISUAL;
		}
	}

	public static function mode_from_queue_flag( int $flag ): string {
		if ( self::QUEUE_RAW === $flag ) {
			return self::MODE_RAW;
		}
		if ( self::QUEUE_HTML === $flag ) {
			return self::MODE_HTML;
		}

		return self::MODE_VISUAL;
	}

	/**
	 * @param int|string $post_id
	 * @return int|string
	 */
	public static function stash_raw_html_values( $post_id ) {
		if ( ! self::is_options_save( $post_id ) ) {
			return $post_id;
		}

		self::$raw_html_stash = [];
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $post_id;
		}

		foreach ( self::HTML_BODY_FIELD_KEYS as $field_key ) {
			if ( isset( $_POST['acf'][ $field_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				self::$raw_html_stash[ $field_key ] = wp_unslash( (string) $_POST['acf'][ $field_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
		}

		return $post_id;
	}

	/**
	 * @param mixed               $value
	 * @param int|string          $post_id
	 * @param array<string,mixed> $field
	 * @return mixed
	 */
	public static function restore_raw_html_value( $value, $post_id, array $field ) {
		if ( ! self::is_options_save( $post_id ) ) {
			return $value;
		}

		$key = (string) ( $field['key'] ?? '' );
		if ( isset( self::$raw_html_stash[ $key ] ) ) {
			return self::$raw_html_stash[ $key ];
		}

		return $value;
	}

	/**
	 * @return array{body: string, html_mode: bool, editor_mode: string}
	 */
	public static function get_body_settings( string $template_key ): array {
		$prefix = self::template_option_prefix( $template_key );
		if ( '' === $prefix ) {
			return [
				'body'        => '',
				'html_mode'   => false,
				'editor_mode' => self::MODE_VISUAL,
			];
		}

		$editor_mode = self::sanitize_mode( (string) Helpers::get_option( $prefix . '_body_editor_mode', self::MODE_VISUAL ) );
		$html_mode   = self::uses_html_field( $editor_mode );
		$body        = $html_mode
			? (string) Helpers::get_option( $prefix . '_body_html', '' )
			: (string) Helpers::get_option( $prefix . '_body', '' );

		return [
			'body'        => $body,
			'html_mode'   => $html_mode,
			'editor_mode' => $editor_mode,
		];
	}

	public static function template_option_prefix( string $template_key ): string {
		$map = [
			'admin'            => 'admin_email',
			'customer'         => 'customer_email',
			'admin_coupon'     => 'admin_coupon_email',
			'customer_coupon'  => 'customer_coupon_email',
			'reminder'         => 'reminder_email',
			'post_class'       => 'post_class_email',
		];

		return $map[ $template_key ] ?? '';
	}

	/**
	 * @param int|string $post_id
	 */
	private static function is_options_save( $post_id ): bool {
		$post_id = (string) $post_id;
		return in_array( $post_id, [ 'clasbpro_options', 'options' ], true );
	}
}
