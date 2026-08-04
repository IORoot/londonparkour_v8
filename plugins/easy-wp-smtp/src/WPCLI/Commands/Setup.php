<?php

namespace EasyWPSMTP\WPCLI\Commands;

use WP_CLI;
use EasyWPSMTP\Options;
use EasyWPSMTP\WPCLI\Options\Help;
use EasyWPSMTP\WPCLI\Options\Registry;
use EasyWPSMTP\WPCLI\Options\Writer;

/**
 * Configure Easy WP SMTP from the command line.
 *
 * @since 2.15.0
 */
class Setup {

	/**
	 * One-line summary passed to WP_CLI::add_command() as `shortdesc`.
	 *
	 * Required because passing an explicit `longdesc` makes WP-CLI ignore the class docblock.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public static function shortdesc() {

		return __( 'Configure Easy WP SMTP from the command line.', 'easy-wp-smtp' );
	}

	/**
	 * Build the longdesc passed to WP_CLI::add_command().
	 *
	 * @since 2.15.0
	 *
	 * @param Registry $registry Provides the configuration-flags enumeration.
	 *
	 * @return string
	 */
	public static function help( Registry $registry ) {

		$flags = Help::configuration_flags( $registry );

		$force_desc = __( 'Skip the refusal that fires when the plugin is already configured. Does NOT wipe existing settings — only flags you pass are written.', 'easy-wp-smtp' );

		return <<<HELP
## OPTIONS

[--force]
: {$force_desc}

## EXAMPLES

    wp easy-wp-smtp setup --mail.from_email=noreply@example.com --mail.from_name="Example" \\
        --mail.mailer=smtp --smtp.host=mail.example.com --smtp.port=587 \\
        --smtp.encryption=tls --smtp.auth=1 --smtp.user=foo \\
        --smtp.pass-file=/run/secret/smtp_pass

    wp easy-wp-smtp setup --mail.from_email=noreply@example.com --mail.from_name="Example" \\
        --mail.mailer=sendgrid --sendgrid.api_key=\$SG_KEY

{$flags}
HELP;
	}

	/**
	 * Execute the `wp easy-wp-smtp setup` command.
	 *
	 * @since 2.15.0
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 *
	 * @return void
	 */
	public function __invoke( $args, $assoc_args ) {

		$force = isset( $assoc_args['force'] );

		unset( $assoc_args['force'] );

		$writer = new Writer( new Registry() );

		$current_mailer = Options::init()->get( 'mail', 'mailer' );

		if ( ! $force && $current_mailer !== '' && $current_mailer !== 'mail' ) {
			WP_CLI::error(
				sprintf(
					/* translators: %s is the currently configured mailer slug (e.g. smtp, sendgrid). The slug is not translated. */
					__( "Easy WP SMTP is already configured (mailer: %s).\nUse `wp easy-wp-smtp option set <group>.<key> <value>` to change individual settings, or pass --force to override this check.", 'easy-wp-smtp' ),
					$current_mailer
				)
			);
		}

		$resolved = $writer->resolve( $assoc_args );

		if ( empty( $resolved ) ) {
			WP_CLI::error( __( 'No configuration flags provided. Pass at least --mail.from_email, --mail.from_name, and --mail.mailer (plus the credentials for that mailer).', 'easy-wp-smtp' ) );
		}

		$writer->validate( $resolved );
		$result = $writer->write( $resolved );

		// Nothing stored means every flag was shadowed by a wp-config constant.
		if ( empty( $result['written'] ) ) {
			WP_CLI::error( __( 'No settings were stored — every flag passed was shadowed by a wp-config constant. Remove the constants and re-run, or pass flags for non-shadowed settings.', 'easy-wp-smtp' ) );
		}

		// Skip the admin Setup Wizard redirect, mirroring wizard completion.
		update_option( 'easy_wp_smtp_activation_prevent_redirect', true );

		$mailer = $resolved['mail.mailer'] ?? Options::init()->get( 'mail', 'mailer' );

		WP_CLI::success(
			sprintf(
				/* translators: %s is the configured mailer slug (e.g. smtp, sendgrid). The slug is not translated. */
				__( 'Configured Easy WP SMTP (mailer: %s). Run `wp easy-wp-smtp test <recipient>` to verify.', 'easy-wp-smtp' ),
				$mailer
			)
		);
	}
}
