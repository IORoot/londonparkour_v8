<?php

namespace EasyWPSMTP\WPCLI\Commands;

use WP_CLI\Dispatcher\CommandNamespace;

/**
 * Configure Easy WP SMTP from the command line.
 *
 * Use one of the subcommands listed below. See `wp help easy-wp-smtp <subcommand>`
 * for detailed help on each one.
 *
 * ## EXAMPLES
 *
 *     wp easy-wp-smtp setup --mail.from_email=noreply@example.com --mail.from_name="Example" \
 *         --mail.mailer=smtp --smtp.host=mail.example.com --smtp.port=587 \
 *         --smtp.encryption=tls --smtp.auth=1 --smtp.user=foo --smtp.pass-file=/run/secret/smtp_pass
 *
 *     wp easy-wp-smtp option get mail.from_email
 *
 *     wp easy-wp-smtp test you@example.com
 *
 * @since 2.15.0
 */
class Manage extends CommandNamespace {

}
