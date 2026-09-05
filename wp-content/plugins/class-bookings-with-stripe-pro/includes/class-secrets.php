<?php
/**
 * Encrypt Stripe secrets at rest. Hashing cannot be used — Stripe needs the
 * real key on every API call. Ciphertext lives in wp_options; the encryption
 * key is derived from WordPress salts in wp-config.php and is never stored
 * in the database.
 *
 * Optional environment / wp-config.php defaults (not written to the DB).
 * getenv / $_ENV are read first; a matching define() is next. A key pasted
 * into the settings fields overrides both for that field.
 *   CLASBPRO_STRIPE_SECRET_TEST
 *   CLASBPRO_STRIPE_SECRET_LIVE
 *   CLASBPRO_STRIPE_WEBHOOK_SECRET
 *   CLASBPRO_STRIPE_PUB_TEST
 *   CLASBPRO_STRIPE_PUB_LIVE
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

	/** @var array<string, string> field name => env / wp-config name */
	private const ENV_NAMES = [
		'stripe_secret_key_test' => 'CLASBPRO_STRIPE_SECRET_TEST',
		'stripe_secret_key_live' => 'CLASBPRO_STRIPE_SECRET_LIVE',
		'stripe_webhook_secret'  => 'CLASBPRO_STRIPE_WEBHOOK_SECRET',
		'stripe_pub_key_test'    => 'CLASBPRO_STRIPE_PUB_TEST',
		'stripe_pub_key_live'    => 'CLASBPRO_STRIPE_PUB_LIVE',
	];

	/** @var array<string, string> encrypted secret fields only */
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

		foreach ( array_keys( self::ENV_NAMES ) as $field ) {
			add_action( 'acf/render_field/name=' . $field, [ self::class, 'render_source_indicator' ], 20 );
		}

		add_action( 'admin_notices', [ self::class, 'maybe_admin_notice' ] );
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
	 *
	 * A key saved in settings overrides environment / wp-config. If nothing
	 * is stored (or ciphertext will not decrypt), the env default is used.
	 */
	public static function get( string $field ): string {
		if ( ! self::is_secret_field( $field ) ) {
			return '';
		}

		$stored = self::stored_plaintext( $field );
		if ( '' !== $stored ) {
			return $stored;
		}

		return self::env_value( $field );
	}

	/**
	 * Environment variable first, then a wp-config.php define() of the same name.
	 */
	public static function env_value( string $field ): string {
		$name = self::env_name( $field );
		if ( '' === $name ) {
			return '';
		}

		$from_env = self::read_process_env( $name );
		if ( '' !== $from_env ) {
			return $from_env;
		}

		if ( defined( $name ) ) {
			$value = constant( $name );
			if ( is_string( $value ) ) {
				$value = trim( $value );
				if ( '' !== $value ) {
					return $value;
				}
			}
		}

		return '';
	}

	public static function env_name( string $field ): string {
		return self::ENV_NAMES[ $field ] ?? '';
	}

	/**
	 * ACF prepare_field rewrites `name` to the HTML input name. `_name` keeps
	 * the original field name used in ENV_NAMES / options.
	 *
	 * @param array<string, mixed> $field
	 */
	private static function field_storage_name( array $field ): string {
		$name = (string) ( $field['_name'] ?? '' );
		if ( '' !== $name ) {
			return $name;
		}
		return (string) ( $field['name'] ?? '' );
	}

	/**
	 * Which source supplies the active key: form, env, wp-config, or empty if none.
	 */
	public static function key_source( string $field ): string {
		if ( '' === self::env_name( $field ) ) {
			return '';
		}

		if ( self::has_form_value( $field ) ) {
			return 'form';
		}

		return self::config_source( $field );
	}

	/**
	 * Badge under each Stripe key input on the settings screen.
	 *
	 * @param array<string, mixed> $field
	 */
	public static function render_source_indicator( $field ): void {
		if ( ! is_array( $field ) ) {
			return;
		}

		$name = self::field_storage_name( $field );
		if ( '' === self::env_name( $name ) ) {
			return;
		}

		$source = self::key_source( $name );
		$mod    = '' === $source ? 'none' : $source;
		$labels = [
			'env'       => 'ENV',
			'wp-config' => 'wp-config',
			'form'      => 'form',
			'none'      => 'none',
		];

		printf(
			'<p class="clasbpro-stripe-key-source clasbpro-stripe-key-source--%1$s"><span class="clasbpro-stripe-key-source__label">%2$s</span> <span class="clasbpro-stripe-key-source__badge">%3$s</span></p>',
			esc_attr( $mod ),
			esc_html__( 'Using', 'class-bookings-with-stripe-pro' ),
			esc_html( $labels[ $mod ] )
		);
	}

	/**
	 * Encrypt any leftover plaintext secrets in wp_options.
	 */
	public static function encrypt_stored_plaintext(): void {
		foreach ( self::field_names() as $field ) {
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
		$name = is_array( $field ) ? self::field_storage_name( $field ) : '';
		if ( ! self::is_secret_field( $name ) || ! self::is_settings_post_id( $post_id ) ) {
			return $value;
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
		$name = is_array( $field ) ? self::field_storage_name( $field ) : '';
		if ( ! self::is_secret_field( $name ) ) {
			return $value;
		}
		unset( $value, $post_id );
		$plaintext = self::stored_plaintext( $name );
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

		$name = self::field_storage_name( $field );
		if ( ! self::is_secret_field( $name ) ) {
			return $field;
		}

		$has_key  = '' !== self::stored_plaintext( $name );
		$from_env = self::env_value( $name );
		$existing = trim( (string) ( $field['instructions'] ?? '' ) );

		if ( '' !== $from_env ) {
			$const = self::env_name( $name );
			$note  = $has_key
				? sprintf(
					/* translators: %s: wp-config.php / environment variable name */
					__( 'A database key is saved and overrides %s. Clear this field to use the environment / wp-config value instead.', 'class-bookings-with-stripe-pro' ),
					$const
				)
				: sprintf(
					/* translators: %s: wp-config.php / environment variable name */
					__( 'Using %s from the environment or wp-config.php. Leave empty to keep that, or paste a key here to override it.', 'class-bookings-with-stripe-pro' ),
					$const
				);
			$field['instructions'] = '' === $existing ? $note : $existing . ' ' . $note;
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
			$raw = self::raw( $field );
			if ( self::is_encrypted( $raw ) && '' === self::decrypt( $raw ) && '' === self::env_value( $field ) ) {
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

	private static function has_form_value( string $field ): bool {
		if ( self::is_secret_field( $field ) ) {
			return '' !== self::stored_plaintext( $field );
		}

		return '' !== self::stored_public_value( $field );
	}

	/**
	 * env wins over wp-config when both are set (same order as env_value()).
	 */
	private static function config_source( string $field ): string {
		$name = self::env_name( $field );
		if ( '' === $name ) {
			return '';
		}

		if ( '' !== self::read_process_env( $name ) ) {
			return 'env';
		}

		if ( defined( $name ) ) {
			$value = constant( $name );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return 'wp-config';
			}
		}

		return '';
	}

	private static function read_process_env( string $name ): string {
		$candidates = [
			getenv( $name ),
			$_ENV[ $name ] ?? null,
			$_SERVER[ $name ] ?? null,
		];

		foreach ( $candidates as $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}
			$value = trim( $value );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	private static function stored_public_value( string $field ): string {
		foreach ( self::option_keys_for( $field ) as $option ) {
			$value = get_option( $option, '' );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	private static function stored_plaintext( string $field ): string {
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
}
