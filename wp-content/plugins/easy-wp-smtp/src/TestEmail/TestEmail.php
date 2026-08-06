<?php

namespace EasyWPSMTP\TestEmail;

use EasyWPSMTP\Admin\DomainChecker;
use EasyWPSMTP\ConnectionInterface;

/**
 * Class TestEmail.
 *
 * Sends a test email on behalf of any caller (admin test page, setup wizard,
 * future contexts) and exposes the result + diagnostics.
 *
 * @since 2.15.0
 */
class TestEmail {

	/**
	 * Test email sending failed.
	 *
	 * @since 2.15.0
	 *
	 * @const int
	 */
	const FAILED = 0;

	/**
	 * Test email sent successfully.
	 *
	 * @since 2.15.0
	 *
	 * @const int
	 */
	const SUCCESS = 1;

	/**
	 * Test email domain check failed.
	 *
	 * @since 2.15.0
	 *
	 * @const int
	 */
	const FAILED_DOMAIN_CHECK = 2;

	/**
	 * X-Mailer-Type header value for the admin Email Test page.
	 *
	 * @since 2.15.0
	 *
	 * @const string
	 */
	const CONTEXT_ADMIN_TEST = 'EasyWPSMTP/Admin/Test';

	/**
	 * X-Mailer-Type header value for the Setup Wizard configuration check.
	 *
	 * @since 2.15.0
	 *
	 * @const string
	 */
	const CONTEXT_SETUP_WIZARD = 'EasyWPSMTP/Admin/SetupWizard/Test';

	/**
	 * Caller context. Drives the X-Mailer-Type header read by MailCatcherTrait,
	 * Pro/SmartRouting and Pro/Emails/Logs/Email.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	private $context = self::CONTEXT_ADMIN_TEST;

	/**
	 * Test email connection. Defaults to the primary connection when not set.
	 *
	 * @since 2.15.0
	 *
	 * @var ConnectionInterface|null
	 */
	private $connection;

	/**
	 * Whether to send as HTML (true) or plain text (false).
	 *
	 * @since 2.15.0
	 *
	 * @var bool
	 */
	private $is_html = true;

	/**
	 * Whether to run the DomainChecker after a successful send.
	 *
	 * @since 2.15.0
	 *
	 * @var bool
	 */
	private $run_domain_check = false;

	/**
	 * Custom subject to send instead of the default test-email subject.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	private $custom_subject = '';

	/**
	 * Custom body to send instead of the default test-email message.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	private $custom_message = '';

	/**
	 * Test email result.
	 *
	 * @since 2.15.0
	 *
	 * @var int|null
	 */
	private $result = null;

	/**
	 * Domain Checker API object, populated when domain check is enabled and the send succeeded.
	 *
	 * @since 2.6.0
	 *
	 * @var DomainChecker|null
	 */
	private $domain_checker;

	/**
	 * Set the connection used to send the test email.
	 *
	 * @since 2.15.0
	 *
	 * @param ConnectionInterface $connection Connection object.
	 *
	 * @return self
	 */
	public function with_connection( ConnectionInterface $connection ) {

		$this->connection = $connection;

		return $this;
	}

	/**
	 * Set the caller context (drives the X-Mailer-Type header).
	 *
	 * @since 2.15.0
	 *
	 * @param string $context One of the CONTEXT_* constants.
	 *
	 * @return self
	 */
	public function with_context( $context ) {

		$this->context = (string) $context;

		return $this;
	}

	/**
	 * Toggle HTML vs plain text body.
	 *
	 * @since 2.15.0
	 *
	 * @param bool $is_html True for HTML, false for plain text.
	 *
	 * @return self
	 */
	public function as_html( $is_html ) {

		$this->is_html = (bool) $is_html;

		return $this;
	}

	/**
	 * Toggle DomainChecker invocation after a successful send.
	 *
	 * @since 2.15.0
	 *
	 * @param bool $run True to run the domain check, false to skip.
	 *
	 * @return self
	 */
	public function with_domain_check( $run ) {

		$this->run_domain_check = (bool) $run;

		return $this;
	}

	/**
	 * Set a custom subject, used instead of the default test-email subject.
	 *
	 * @since 2.15.0
	 *
	 * @param string $subject Custom subject. Empty string keeps the default.
	 *
	 * @return self
	 */
	public function with_custom_subject( $subject ) {

		$this->custom_subject = (string) $subject;

		return $this;
	}

	/**
	 * Set a custom body, used instead of the default test-email message.
	 *
	 * @since 2.15.0
	 *
	 * @param string $message Custom body. Empty string keeps the default.
	 *
	 * @return self
	 */
	public function with_custom_message( $message ) {

		$this->custom_message = (string) $message;

		return $this;
	}

	/**
	 * Whether the send succeeded (with or without domain-check issues).
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	public function is_successful() {

		return $this->result === self::SUCCESS || $this->result === self::FAILED_DOMAIN_CHECK;
	}

	/**
	 * Get the raw result constant.
	 *
	 * @since 2.15.0
	 *
	 * @return int|null
	 */
	public function get_result() {

		return $this->result;
	}

	/**
	 * Get the DomainChecker instance populated after a successful send (when enabled).
	 *
	 * @since 2.15.0
	 *
	 * @return DomainChecker|null
	 */
	public function get_domain_checker() {

		return $this->domain_checker;
	}

	/**
	 * Get the connection used for the send.
	 *
	 * @since 2.15.0
	 *
	 * @return ConnectionInterface|null
	 */
	public function get_connection() {

		return $this->connection;
	}

	/**
	 * Send the test email.
	 *
	 * Lifted from TestTab::process_post() with the form-data plumbing replaced by
	 * fluent setters; the wp_mail() / ob_start / DomainChecker block stays identical.
	 *
	 * @since 2.15.0
	 *
	 * @param string $recipient Recipient email address.
	 *
	 * @return int|null Result constant (SUCCESS, FAILED, FAILED_DOMAIN_CHECK), or null when validation failed.
	 */
	public function send( $recipient ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks, Generic.Metrics.CyclomaticComplexity.MaxExceeded -- Paired add_filter/remove_filter scope the HTML content-type to this single test send only; moving them to hooks() would change behavior.

		$recipient = filter_var( wp_unslash( $recipient ), FILTER_VALIDATE_EMAIL );

		if ( empty( $recipient ) ) {
			return null;
		}

		if ( ! $this->connection ) {
			$this->connection = easy_wp_smtp()->get_connections_manager()->get_primary_connection();
		}

		$phpmailer = easy_wp_smtp()->get_processor()->get_phpmailer();

		/* translators: %s - email address a test email will be sent to. */
		$subject = 'Easy WP SMTP: ' . sprintf( esc_html__( 'Test email to %s', 'easy-wp-smtp' ), $recipient );
		$headers = [ 'X-Mailer-Type:' . $this->context ];

		if ( $this->is_html ) {
			add_filter( 'wp_mail_content_type', [ __CLASS__, 'set_test_html_content_type' ] );

			/* translators: %s - email address a test email will be sent to. */
			$subject   = 'Easy WP SMTP: HTML ' . sprintf( esc_html__( 'Test email to %s', 'easy-wp-smtp' ), $recipient );
			$headers[] = 'Content-Type: text/html';
		}

		// A custom subject/body (admin Test Email "custom" option) overrides the defaults.
		if ( $this->custom_subject !== '' ) {
			$subject = $this->custom_subject;
		}

		$message = $this->custom_message !== '' ? $this->custom_message : $this->get_email_message( $this->is_html );

		// Send the test mail.
		$result = wp_mail(
			$recipient,
			$subject,
			$message,
			$headers
		);

		if ( $this->is_html ) {
			remove_filter( 'wp_mail_content_type', [ __CLASS__, 'set_test_html_content_type' ] );
		}

		/*
		 * Notify a user about the results.
		 */
		if ( $result ) {
			if ( $this->run_domain_check ) {
				$connection_options = $this->connection->get_options();
				$mailer             = $connection_options->get( 'mail', 'mailer' );
				$email              = $connection_options->get( 'mail', 'from_email' );
				$domain             = '';

				// Add the optional sending domain parameter.
				if ( in_array( $mailer, [ 'mailgun', 'sendinblue', 'sendgrid' ], true ) ) {
					$domain = $connection_options->get( $mailer, 'domain' );
				}

				$this->domain_checker = new DomainChecker( $mailer, $email, $domain );

				$this->result = $this->domain_checker->no_issues() ? self::SUCCESS : self::FAILED_DOMAIN_CHECK;
			} else {
				$this->result = self::SUCCESS;
			}
		} else {
			$this->result = self::FAILED;
		}

		return $this->result;
	}

	/**
	 * Get the email message that should be sent.
	 *
	 * @since 1.4.0
	 *
	 * @param bool $is_html Whether to send an HTML email or plain text.
	 *
	 * @return string
	 */
	private function get_email_message( $is_html = true ) {

		// Default plain text version of the email.
		$message = self::get_email_message_text();

		if ( $is_html ) {
			$message = $this->get_email_message_html();
		}

		return $message;
	}

	/**
	 * Get the HTML prepared message for test email.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	private function get_email_message_html() {

		ob_start();
		?>
		<!doctype html>
		<html lang="en">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width">
			<title>Easy WP SMTP Test Email</title>
			<style type="text/css">@media only screen and (max-width: 599px) {table.body .container {width: 95% !important;}.header {padding: 30px 15px 30px 15px !important;}.content, .education-main {padding: 40px 30px !important;} .education-footer {padding: 20px 30px !important;}.guaranty-badge {display: none !important;}.education-footer p {text-align: left !important;}}</style>
		</head>
		<body style="height: 100% !important; width: 100% !important; min-width: 100%; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; -webkit-font-smoothing: antialiased !important; -moz-osx-font-smoothing: grayscale !important; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #F2F2F4; text-align: center;">
		<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="body" style="border-collapse: collapse; border-spacing: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; height: 100% !important; width: 100% !important; min-width: 100%; -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; -webkit-font-smoothing: antialiased !important; -moz-osx-font-smoothing: grayscale !important; background-color: #F2F2F4; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%;">
			<tr style="padding: 0; vertical-align: top; text-align: left;">
				<td align="center" valign="top" class="body-inner easy-wp-smtp" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: center;">
					<!-- Container -->
					<table border="0" cellpadding="0" cellspacing="0" class="container" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 600px; margin: 0 auto 30px auto; Margin: 0 auto 30px auto; text-align: inherit;">
						<!-- Header -->
						<tr style="padding: 0; vertical-align: top; text-align: left;">
							<td align="center" valign="middle" class="header" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: center; padding: 30px 30px 30px 30px;">
								<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/easy-wp-smtp.png' ); ?>" width="308" alt="Easy WP SMTP Logo" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: inline-block !important; width: 308px;">
							</td>
						</tr>
						<!-- Content -->
						<tr style="padding: 0; vertical-align: top; text-align: left;">
							<td align="left" valign="top" class="content" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #ffffff; padding-top: 60px;padding-bottom: 60px;padding-left: 60px;padding-right: 60px;">
								<div class="success" style="text-align: center;">
									<p class="check" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; margin: 0 auto 40px auto; Margin: 0 auto 40px auto; text-align: center;">
										<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/icon-check.png' ); ?>" width="64" alt="Success" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; display: block; margin: 0 auto 0 auto; Margin: 0 auto 0 auto; width: 64px;">
									</p>
									<p class="text-extra-large text-center congrats" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #09092C; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; padding: 0; mso-line-height-rule: exactly; line-height: 140%; font-size: 20px; text-align: center; margin: 0 0 40px 0; Margin: 0 0 40px 0;">
										Congrats, test email was sent successfully!
									</p>
									<p class="text-large" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 40px 0; Margin: 0 0 40px 0; font-size: 16px;">
										Thank you for using Easy WP SMTP. We're on a mission to make sure your emails actually get delivered.
									</p>
									<p class="signature" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #3A3A56; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; text-align: left; margin: 0 0 10px 0; Margin: 0 0 10px 0;">
										<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/signature.png' ); ?>" width="180" alt="Signature" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 180px; display: block; margin: 0 0 0 0; Margin: 0 0 0 0;">
									</p>
									<p style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #6F6F84; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; margin: 0 0 0px 0; Margin: 0 0 0px 0;">
										<strong>Syed Balkhi</strong><br>
										CEO, SendLayer
									</p>
								</div>
							</td>
						</tr>

						<?php if ( ! easy_wp_smtp()->is_pro() ) : ?>
							<tr style="padding: 0; vertical-align: top; text-align: left;">
								<td align="left" valign="top" class="education-main" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #DBEDE6; text-align: left !important; padding-top: 60px;padding-bottom: 60px;padding-left: 60px;padding-right: 60px;">
									<h6 style="padding: 0; color: #02150D; word-wrap: normal; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: bold; mso-line-height-rule: exactly; line-height: 130%; font-size: 17px; text-align: left; margin: 0 0 20px 0; Margin: 0 0 20px 0;">
										Unlock Powerful Features with Easy WP SMTP Pro
									</h6>

									<table style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100% !important;">
										<tr style="padding: 0; vertical-align: top; text-align: left;">
											<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; padding: 0 0 0 0; line-height: 100%;width: 67%;">
												<table style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100% !important; margin-bottom: 5px;">
													<tr style="padding: 0; vertical-align: top; text-align: left;">
														<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; line-height: 140%; padding-bottom: 15px; padding-top: 0; padding-right: 10px;padding-left: 0; width: 16px;">
															<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/check.png' ); ?>" width="16" alt="Check" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 16px; vertical-align: middle;">
														</td>
														<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; line-height: 140%; padding-bottom: 15px; padding-top: 0; padding-right: 0;padding-left: 0;font-size: 15px;">
															Detailed Email Logs
														</td>
													</tr>
													<tr style="padding: 0; vertical-align: top; text-align: left;">
														<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; line-height: 140%; padding-bottom: 15px; padding-top: 0; padding-right: 10px;padding-left: 0;width: 16px;">
															<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/check.png' ); ?>" width="16" alt="Check" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 16px;vertical-align: middle;">
														</td>
														<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; line-height: 140%; padding-bottom: 15px; padding-top: 0; padding-right: 0;padding-left: 0;font-size: 15px;">
															Complete Email Reports
														</td>
													</tr>
													<tr style="padding: 0; vertical-align: top; text-align: left;">
														<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; line-height: 140%; padding-bottom: 15px; padding-top: 0; padding-right: 10px;padding-left: 0;width: 16px;">
															<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/check.png' ); ?>" width="16" alt="Check" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 16px;vertical-align: middle;">
														</td>
														<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: left; line-height: 140%; padding-bottom: 15px; padding-top: 0; padding-right: 0;padding-left: 0;font-size: 15px;">
															Enhanced Weekly Email Summary
														</td>
													</tr>
												</table>

												<table class="button" style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100%; max-width: 202px;">
													<tr style="padding: 0; vertical-align: top; text-align: left;">
														<td class="button-inner" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; text-align: left; font-size: 14px; mso-line-height-rule: exactly; line-height: 100%; padding: 0 0 0 0;">
															<table style="border-collapse: collapse; border-spacing: 0; padding: 0; vertical-align: top; text-align: left; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100% !important;">
																<tr style="padding: 0; vertical-align: top; text-align: left;">
																	<td style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; margin: 0; Margin: 0; font-size: 16px; text-align: center; color: #ffffff; background: #0F8A56; border-radius: 4px; mso-line-height-rule: exactly; line-height: 100%;">
																		<a href="<?php echo esc_url( easy_wp_smtp()->get_upgrade_link( 'email-test' ) ); ?>" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; margin: 0; Margin: 0; font-family: Helvetica, Arial, sans-serif; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block; border: 0 solid #0F8A56; mso-line-height-rule: exactly; line-height: 100%; padding: 12px 20px 12px 20px; font-size: 16px; text-align: center; width: 100%; padding-left: 0; padding-right: 0;">
																			Upgrade to Pro Today
																		</a>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>

											<td class="guaranty-badge" align="right" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; mso-line-height-rule: exactly; text-align: right; padding: 0 0 0 0; line-height: 140%; width: 33%;">
												<img src="<?php echo esc_url( easy_wp_smtp()->plugin_url . '/assets/images/email/14days-badge.png' ); ?>" width="155" alt="Check" style="outline: none; text-decoration: none; max-width: 100%; clear: both; -ms-interpolation-mode: bicubic; width: 155px;">
											</td>
										</tr>
									</table>
								</td>
							</tr>

							<tr style="padding: 0; vertical-align: top; text-align: left;">
								<td align="left" valign="top" class="education-footer" style="word-wrap: break-word; -webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; border-collapse: collapse !important; vertical-align: top; mso-table-lspace: 0pt; mso-table-rspace: 0pt; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #444; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; margin: 0; Margin: 0; font-size: 14px; mso-line-height-rule: exactly; line-height: 140%; background-color: #B7DCCC; text-align: left !important; padding-top: 20px;padding-bottom: 20px;padding-left: 55px;padding-right: 55px;">
									<p class="text-large last" style="-ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; color: #042315; font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; font-weight: normal; padding: 0; mso-line-height-rule: exactly; line-height: 140%; font-size: 14px; text-align: center; margin: 0 0 0 0; Margin: 0 0 0 0;">
										Upgrade to the Pro and <span style="font-weight:bold;color:#0B613C;text-transform: uppercase;">save 50% today</span>, automatically applied at checkout.
									</p>
								</td>
							</tr>
						<?php endif; ?>
					</table>
				</td>
			</tr>
		</table>
		</body>
		</html>

		<?php
		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Get the plain text prepared message for test email.
	 *
	 * @since 1.4.0
	 * @since 1.5.0 Display an upsell to Easy WP SMTP Pro if free version installed.
	 * @since 2.6.0 Change visibility, so it can be used elsewhere.
	 *
	 * @return string
	 */
	public static function get_email_message_text() {

		// phpcs:disable
		if ( easy_wp_smtp()->is_pro() ) {
			// Easy WP SMTP Pro paid installed.
			$message =
'Congrats, test email was sent successfully!

Thank you for using Easy WP SMTP. We\'re on a mission to make sure your emails actually get delivered.

- Syed Balkhi
CEO, SendLayer';
		} else {
			// Free Easy WP SMTP is installed.
			$message =
'Congrats, test email was sent successfully!

Thank you for trying out Easy WP SMTP. We are on a mission to make sure your emails actually get delivered.

If you find this free plugin useful, please consider giving Easy WP SMTP Pro a try!

https://easywpsmtp.com/lite-upgrade/

Unlock These Powerful Features with Easy WP SMTP Pro:

+ Log all emails and export your email logs in different formats
+ Send emails with Amazon SES / Microsoft 365/ Zoho Mail
+ Track opens and clicks to measure engagement
+ Resend failed emails from your email log
+ Create email reports and graphs
+ Get help from our world-class support team

- Syed Balkhi
CEO, SendLayer';
		}
		// phpcs:enable

		return $message;
	}

	/**
	 * Set the HTML content type for a test email.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public static function set_test_html_content_type() {

		return 'text/html';
	}
}
