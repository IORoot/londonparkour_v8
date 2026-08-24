<?php
/**
 * Encrypt Stripe secrets at rest. Hashing cannot be used — Stripe needs the
 * real key on every API call. Ciphertext lives in wp_options; the encryption
 * key is derived from WordPress salts in wp-config.php and is never stored
 * in the database.
 *
 * Optional wp-config.php overrides (never written to the DB):
 *   CLASBPRO_STRIPE_SECRET_TEST
 *   CLASBPRO_STRIPE_SECRET_LIVE
 *   CLASBPRO_STRIPE_WEBHOOK_SECRET
 *   CLASBPRO_STRIPE_ENCRYPTION_KEY  — dedicated passphrase; survives AUTH_KEY rotation
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Secrets {

	private const PREFIX = 'clasbpro_enc:v1:';

	/**
	 * Character used to fill the password input — one per character of the
	 * real key. Stripe keys are pure ASCII alphanumeric + underscore, so this
	 * non-ASCII bullet (U+2022) can never appear in a real key and is safe to
	 * use as a sentinel on save.
	 */
	private const MASK_CHAR = "\xE2\x80\xA2";

	/** @var array<string, string> field name => wp-config constant */
	private const FIELD_CONSTANTS = [
		'stripe_secret_key_test' => 'CLASBPRO_STRIPE_SECRET_TEST',
		'stripe_secret_key_live' => 'CLASBPRO_STRIPE_SECRET_LIVE',
		'stripe_webhook_secret'  => 'CLASBPRO_STRIPE_WEBHOOK_SECRET',
	];

	public static function init(): void {
		foreach ( self::field_names() as $field ) {
			add_filter( 'acf/update_value/name=' . $field, [ self::class, 'filter_update_value' ], 5, 3 );
			add_filter( 'acf/load_value/name=' . $field, [ self::class, 'filter_load_value' ], 5, 3 );
			add_filter( 'acf/prepare_field/name=' . $field, [ self::class, 'filter_prepare_field' ], 20 );
			foreach ( self::option_keys_for( $field ) as $option ) {
				add_filter( 'pre_update_option_' . $option, [ self::class, 'filter_pre_update_option' ], 10, 3 );
			}
		}

		add_action( 'admin_notices', [ self::class, 'maybe_admin_notice' ] );
		self::scrub_constant_backed_options();
	}

	/**
	 * @return list<string>
	 */
	public static function field_names(): array {
		return array_keys( self::FIELD_CONSTANTS );
	}

	public static function is_secret_field( string $name ): bool {
		return isset( self::FIELD_CONSTANTS[ $name ] );
	}

	/**
	 * Decrypt a stored secret for Stripe. Never echo or log the return value.
	 */
	public static function get( string $field ): string {
		if ( ! self::is_secret_field( $field ) ) {
			return '';
		}

		$from_const = self::constant_value( $field );
		if ( '' !== $from_const ) {
			return $from_const;
		}

		$raw = self::raw( $field );
		if ( '' === $raw ) {
			return '';
		}

		if ( self::is_encrypted( $raw ) ) {
			return self::decrypt( $raw );
		}

		$encrypted = self::encrypt( $raw );
		if ( '' !== $encrypted ) {
			self::write_raw( $field, $encrypted );
		}

		return $raw;
	}

	/**
	 * Encrypt any leftover plaintext secrets in wp_options.
	 */
	public static function encrypt_stored_plaintext(): void {
		foreach ( self::field_names() as $field ) {
			if ( '' !== self::constant_value( $field ) ) {
				foreach ( self::option_keys_for( $field ) as $option ) {
					delete_option( $option );
				}
				continue;
			}

			foreach ( self::option_keys_for( $field ) as $option ) {
				$raw = get_option( $option, '' );
				if ( ! is_string( $raw ) || '' === $raw || self::is_encrypted( $raw ) ) {
					continue;
				}
				$encrypted = self::encrypt( $raw );
				if ( '' !== $encrypted ) {
					update_option( $option, $encrypted, false );
				}
			}
		}
	}

	/**
	 * @param mixed $value
	 * @param mixed $post_id
	 * @param mixed $field
	 * @return mixed
	 */
	public static function filter_update_value( $value, $post_id, $field ) {
		$name = is_array( $field ) ? (string) ( $field['name'] ?? '' ) : '';
		if ( ! self::is_secret_field( $name ) || ! self::is_settings_post_id( $post_id ) ) {
			return $value;
		}

		if ( '' !== self::constant_value( $name ) ) {
			return '';
		}

		$value = is_string( $value ) ? trim( $value ) : '';
		if ( self::is_display_mask( $value ) ) {
			return self::raw( $name );
		}
		if ( '' === $value ) {
			return '';
		}
		if ( self::is_encrypted( $value ) ) {
			return $value;
		}

		$encrypted = self::encrypt( $value );
		return '' !== $encrypted ? $encrypted : self::raw( $name );
	}

	/**
	 * Return the display mask when a key is saved so the password input shows
	 * as filled (dots). Return empty when no key is stored.
	 * Never return the ciphertext or plaintext.
	 *
	 * @param mixed $value
	 * @param mixed $post_id
	 * @param mixed $field
	 * @return string
	 */
	public static function filter_load_value( $value, $post_id, $field ) {
		$name = is_array( $field ) ? (string) ( $field['name'] ?? '' ) : '';
		if ( ! self::is_secret_field( $name ) ) {
			return $value;
		}
		unset( $value, $post_id );
		$plaintext = self::get( $name );
		if ( '' === $plaintext ) {
			return '';
		}
		return str_repeat( self::MASK_CHAR, strlen( $plaintext ) );
	}

	/**
	 * Append instructions and, for constant-backed keys, mark the field
	 * disabled. Value display is handled by filter_load_value.
	 *
	 * @param array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function filter_prepare_field( $field ) {
		if ( ! is_array( $field ) ) {
			return $field;
		}

		$name = (string) ( $field['name'] ?? '' );
		if ( ! self::is_secret_field( $name ) ) {
			return $field;
		}

		$has_key  = '' !== self::constant_value( $name ) || '' !== self::raw( $name );
		$existing = trim( (string) ( $field['instructions'] ?? '' ) );

		if ( '' !== self::constant_value( $name ) ) {
			$const = self::constant_name( $name );
			$note  = sprintf(
				/* translators: %s: wp-config.php constant name */
				__( 'This key is loaded from %s in wp-config.php and is not stored in the database.', 'class-bookings-with-stripe-pro' ),
				$const
			);
			$field['instructions'] = '' === $existing ? $note : $existing . ' ' . $note;
			$field['disabled']     = 1;
			return $field;
		}

		$note = $has_key
			? __( 'A key is saved (shown as dots). Paste a new key to replace it, or clear the field to remove it. Stored encrypted.', 'class-bookings-with-stripe-pro' )
			: __( 'Paste your Stripe secret. It is stored encrypted.', 'class-bookings-with-stripe-pro' );

		$field['instructions'] = '' === $existing ? $note : $existing . ' ' . $note;
		return $field;
	}

	/**
	 * @param mixed  $value
	 * @param mixed  $old_value
	 * @param string $option
	 * @return mixed
	 */
	public static function filter_pre_update_option( $value, $old_value, $option ) {
		$field = self::field_from_option( (string) $option );
		if ( '' === $field ) {
			return $value;
		}

		if ( '' !== self::constant_value( $field ) ) {
			return '';
		}

		$value = is_string( $value ) ? trim( $value ) : '';
		if ( self::is_display_mask( $value ) ) {
			return is_string( $old_value ) ? $old_value : '';
		}
		if ( '' === $value ) {
			return '';
		}
		if ( self::is_encrypted( $value ) ) {
			return $value;
		}

		$encrypted = self::encrypt( $value );
		if ( '' === $encrypted ) {
			return is_string( $old_value ) ? $old_value : '';
		}
		return $encrypted;
	}

	public static function maybe_admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, Constants::MENU_SETTINGS ) ) {
			return;
		}

		if ( ! self::crypto_available() ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Class Bookings cannot encrypt Stripe secrets. Enable the PHP sodium extension or OpenSSL AES-256-GCM.', 'class-bookings-with-stripe-pro' );
			echo '</p></div>';
			return;
		}

		$broken = [];
		foreach ( self::field_names() as $field ) {
			if ( '' !== self::constant_value( $field ) ) {
				continue;
			}
			$raw = self::raw( $field );
			if ( self::is_encrypted( $raw ) && '' === self::decrypt( $raw ) ) {
				$broken[] = $field;
			}
		}

		if ( empty( $broken ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Saved Stripe keys could not be decrypted. Re-enter them on this screen. If you recently rotated WordPress salts in wp-config.php, set CLASBPRO_STRIPE_ENCRYPTION_KEY to a dedicated passphrase before saving new keys, or paste the keys again.', 'class-bookings-with-stripe-pro' );
		echo '</p></div>';
	}

	public static function crypto_available(): bool {
		return ( function_exists( 'sodium_crypto_secretbox' ) && function_exists( 'sodium_crypto_secretbox_open' ) )
			|| ( function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ) );
	}

	public static function is_encrypted( string $value ): bool {
		return str_starts_with( $value, self::PREFIX );
	}

	private static function is_display_mask( string $value ): bool {
		if ( '' === $value ) {
			return false;
		}
		// A mask is a non-empty string consisting entirely of MASK_CHAR bullets.
		// Stripe keys are ASCII-only, so MASK_CHAR (U+2022) cannot appear in a real key.
		return preg_match( '/^(?:' . self::MASK_CHAR . ')+$/', $value ) === 1;
	}

	private static function encrypt( string $plaintext ): string {
		if ( '' === $plaintext || ! self::crypto_available() ) {
			return '';
		}

		$key = self::encryption_key();
		if ( '' === $key ) {
			return '';
		}

		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $key );
			return self::PREFIX . 'nacl:' . base64_encode( $nonce . $ciphertext );
		}

		$iv  = random_bytes( 12 );
		$tag = '';
		$ciphertext = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $ciphertext || '' === $tag ) {
			return '';
		}
		return self::PREFIX . 'gcm:' . base64_encode( $iv . $tag . $ciphertext );
	}

	private static function decrypt( string $stored ): string {
		if ( ! self::is_encrypted( $stored ) ) {
			return '';
		}

		$payload = substr( $stored, strlen( self::PREFIX ) );
		$algo    = 'nacl';
		$encoded = $payload;
		if ( str_starts_with( $payload, 'nacl:' ) || str_starts_with( $payload, 'gcm:' ) ) {
			$algo    = substr( $payload, 0, strpos( $payload, ':' ) );
			$encoded = substr( $payload, strlen( $algo ) + 1 );
		}

		$packed = base64_decode( $encoded, true );
		if ( false === $packed || '' === $packed ) {
			return '';
		}

		$key = self::encryption_key();
		if ( '' === $key ) {
			return '';
		}

		if ( 'gcm' === $algo ) {
			if ( strlen( $packed ) < 29 || ! function_exists( 'openssl_decrypt' ) ) {
				return '';
			}
			$iv         = substr( $packed, 0, 12 );
			$tag        = substr( $packed, 12, 16 );
			$ciphertext = substr( $packed, 28 );
			$plain      = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			return is_string( $plain ) ? $plain : '';
		}

		if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
			return '';
		}
		$nonce_size = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
		if ( strlen( $packed ) <= $nonce_size ) {
			return '';
		}
		$nonce      = substr( $packed, 0, $nonce_size );
		$ciphertext = substr( $packed, $nonce_size );
		$plain      = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
		return is_string( $plain ) ? $plain : '';
	}

	private static function encryption_key(): string {
		if ( defined( 'CLASBPRO_STRIPE_ENCRYPTION_KEY' ) && is_string( CLASBPRO_STRIPE_ENCRYPTION_KEY ) && '' !== CLASBPRO_STRIPE_ENCRYPTION_KEY ) {
			return hash( 'sha256', 'clasbpro-stripe-v1|' . CLASBPRO_STRIPE_ENCRYPTION_KEY, true );
		}

		$auth   = defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : '';
		$secure = defined( 'SECURE_AUTH_KEY' ) ? (string) SECURE_AUTH_KEY : '';
		if ( '' === $auth && '' === $secure ) {
			return '';
		}

		return hash( 'sha256', 'clasbpro-stripe-v1|' . $auth . '|' . $secure, true );
	}

	private static function constant_name( string $field ): string {
		return self::FIELD_CONSTANTS[ $field ] ?? '';
	}

	private static function constant_value( string $field ): string {
		$const = self::constant_name( $field );
		if ( '' === $const || ! defined( $const ) ) {
			return '';
		}
		$value = constant( $const );
		return is_string( $value ) ? trim( $value ) : '';
	}

	private static function raw( string $field ): string {
		foreach ( self::option_keys_for( $field ) as $option ) {
			$value = get_option( $option, '' );
			if ( is_string( $value ) && '' !== $value ) {
				return $value;
			}
		}
		return '';
	}

	private static function write_raw( string $field, string $value ): void {
		foreach ( self::option_keys_for( $field ) as $option ) {
			$existing = get_option( $option, '' );
			if ( is_string( $existing ) && '' !== $existing ) {
				update_option( $option, $value, false );
				return;
			}
		}
		update_option( self::option_keys_for( $field )[0], $value, false );
	}

	/**
	 * @return list<string>
	 */
	private static function option_keys_for( string $field ): array {
		return [
			Constants::OPTIONS_POST_ID . '_' . $field,
			'options_' . $field,
		];
	}

	private static function field_from_option( string $option ): string {
		foreach ( self::field_names() as $field ) {
			if ( in_array( $option, self::option_keys_for( $field ), true ) ) {
				return $field;
			}
		}
		return '';
	}

	/**
	 * @param mixed $post_id
	 */
	private static function is_settings_post_id( $post_id ): bool {
		return in_array( (string) $post_id, [ Constants::OPTIONS_POST_ID, 'options' ], true );
	}

	private static function scrub_constant_backed_options(): void {
		foreach ( self::field_names() as $field ) {
			if ( '' === self::constant_value( $field ) ) {
				continue;
			}
			foreach ( self::option_keys_for( $field ) as $option ) {
				if ( false !== get_option( $option, false ) ) {
					delete_option( $option );
				}
			}
		}
	}
}
