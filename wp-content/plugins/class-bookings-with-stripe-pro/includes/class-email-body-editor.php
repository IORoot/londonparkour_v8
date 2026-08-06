<?php
/**
 * Settings UI: Visual vs HTML email body editors (separate fields, no sync).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Email_Body_Editor {

	public const MODE_VISUAL = 'visual';
	public const MODE_HTML   = 'html';

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
				'body'         => '',
				'html_mode'    => false,
				'editor_mode'  => self::MODE_VISUAL,
			];
		}

		$editor_mode = sanitize_key( (string) Helpers::get_option( $prefix . '_body_editor_mode', self::MODE_VISUAL ) );
		if ( self::MODE_HTML !== $editor_mode ) {
			$editor_mode = self::MODE_VISUAL;
		}

		$html_mode = self::MODE_HTML === $editor_mode;
		$body      = $html_mode
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
