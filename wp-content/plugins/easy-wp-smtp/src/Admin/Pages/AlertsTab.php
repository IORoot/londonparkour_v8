<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\Admin\PageAbstract;

/**
 * Class AlertsTab is a placeholder for Pro alerts feature.
 * Displays product education.
 *
 * @since 2.4.0
 */
class AlertsTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	protected $slug = 'alerts';

	/**
	 * Tab priority.
	 *
	 * @since 2.4.0
	 *
	 * @var int
	 */
	protected $priority = 20;

	/**
	 * Link label of a tab.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Alerts', 'easy-wp-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Output HTML of the alerts settings preview.
	 *
	 * @since 2.4.0
	 */
	public function display() {

		$upgrade_link_url = easy_wp_smtp()->get_upgrade_link(
			[
				'medium'  => 'Alerts Settings',
				'content' => 'Upgrade to Easy WP SMTP Pro Link',
			]
		);

		$upgrade_button_url = easy_wp_smtp()->get_upgrade_link(
			[
				'medium'  => 'Alerts Settings',
				'content' => 'Upgrade to Easy WP SMTP Pro Button',
			]
		);

		?>
		<div class="easy-wp-smtp-meta-box">
			<div class="easy-wp-smtp-meta-box__header">
				<div class="easy-wp-smtp-meta-box__heading">
					<?php esc_html_e( 'Alerts', 'easy-wp-smtp' ); ?>
				</div>
				<a href="<?php echo esc_url( $upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--sm easy-wp-smtp-btn--green">
					<?php esc_html_e( 'Upgrade to Pro', 'easy-wp-smtp' ); ?>
				</a>
			</div>
			<div class="easy-wp-smtp-meta-box__content">
				<div class="easy-wp-smtp-row">
					<div class="easy-wp-smtp-row__desc">
						<?php
						echo wp_kses(
							sprintf( /* translators: %s - EasyWPSMTP.com Upgrade page URL. */
								__( 'Configure these alert options to receive notifications when email fails to send from your site. Alert notifications will contain the following important data: email subject, email Send To address, the error message, and helpful links to help you fix the issue. <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade to Easy WP SMTP Pro!</a>', 'easy-wp-smtp' ),
								esc_url( $upgrade_link_url )
							),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						);
						?>
					</div>
				</div>

				<div id="easy-wp-smtp-setting-row-alert_event_types" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-row--has-divider">
					<div class="easy-wp-smtp-setting-row__label">
						<label for="easy-wp-smtp-setting-alert_event_types">
							<?php esc_html_e( 'Notify when', 'easy-wp-smtp' ); ?>
						</label>
					</div>
					<div class="easy-wp-smtp-setting-row__field">
						<div class="easy-wp-smtp-setting-row__sub-row">
							<label class="easy-wp-smtp-toggle" for="easy-wp-smtp-setting-alert_events_email_fails">
								<input name="easy-wp-smtp[alert_events][email_fails]" type="checkbox"
								       value="true" checked disabled id="easy-wp-smtp-setting-alert_events_email_fails"
								/>
								<span class="easy-wp-smtp-toggle__switch"></span>
								<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--static"><?php esc_html_e( 'The initial email sending request fails', 'easy-wp-smtp' ); ?></span>
							</label>
							<p class="desc">
								<?php esc_html_e( 'This option is always enabled and will notify you about instant email sending failures.', 'easy-wp-smtp' ); ?>
							</p>
						</div>

						<div class="easy-wp-smtp-setting-row__sub-row">
							<label class="easy-wp-smtp-toggle" for="easy-wp-smtp-setting-alerts_hard_bounced">
								<input name="easy-wp-smtp[alert_events][email_hard_bounced]" type="checkbox"
								       value="true" disabled id="easy-wp-smtp-setting-alert_events_email_hard_bounced"
								/>
								<span class="easy-wp-smtp-toggle__switch"></span>
								<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--static"><?php esc_html_e( 'The deliverability verification process detects a hard bounce', 'easy-wp-smtp' ); ?></span>
							</label>
							<p class="desc">
								<?php esc_html_e( 'Get notified about emails that were successfully sent, but have hard bounced on delivery attempt. A hard bounce is an email that has failed to deliver for permanent reasons, such as the recipient\'s email address being invalid.', 'easy-wp-smtp' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'Email', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'Enter the email addresses (3 max) you’d like to use to receive alerts when email sending fails. Read our documentation on setting up email alerts.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'Email Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
								<div class="easy-wp-smtp-setting-row__label">
									<label><?php esc_html_e( 'Send To', 'easy-wp-smtp' ); ?></label>
								</div>
								<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'Slack', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'Paste in the Slack webhook URL you’d like to use to receive alerts when email sending fails. Read our documentation on setting up Slack alerts.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'Slack Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
								<div class="easy-wp-smtp-setting-row__label">
									<label><?php esc_html_e( 'Webhook URL', 'easy-wp-smtp' ); ?></label>
								</div>
								<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'Discord', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'Paste in the Discord webhook URL you’d like to use to receive alerts when email sending fails. Read our documentation on setting up Discord alerts.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'Discord', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
								<div class="easy-wp-smtp-setting-row__label">
									<label><?php esc_html_e( 'Webhook URL', 'easy-wp-smtp' ); ?></label>
								</div>
								<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'Microsoft Teams', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'Paste in the Microsoft Teams webhook URL you’d like to use to receive alerts when email sending fails. Read our documentation on setting up Microsoft Teams alerts.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'Microsoft Teams Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
								<div class="easy-wp-smtp-setting-row__label">
									<label><?php esc_html_e( 'Webhook URL', 'easy-wp-smtp' ); ?></label>
								</div>
								<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'SMS via Twilio', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'To receive SMS alerts, you’ll need a Twilio account. Read our documentation to learn how to set up Twilio SMS, then enter your connection details below.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'SMS via Twilio Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'Twilio Account ID', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'Twilio Auth Token', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'From Phone Number', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'To Phone Number', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'Webhook', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'Paste in the webhook URL you’d like to use to receive alerts when email sending fails. Read our documentation on setting up webhook alerts.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'Webhook Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'Webhook URL', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'Push Notifications', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'To receive push notifications on this device, you\'ll need to allow our plugin to send notifications via this browser. Read our documentation on setting up Push Notification alerts.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'Push Notification Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'Connection Name', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="easy-wp-smtp-row easy-wp-smtp-row--has-divider easy-wp-smtp-row--inactive easy-wp-smtp-alert-setting-row">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__heading">
							<?php esc_html_e( 'WhatsApp', 'easy-wp-smtp' ); ?>
						</div>
						<div class="easy-wp-smtp-row__desc">
							<?php esc_html_e( 'Enter your WhatsApp Cloud API credentials to receive alerts when email sending fails. You\'ll need to create a Meta developer account and set up WhatsApp Business Platform.', 'easy-wp-smtp' ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-setting-row">
							<div class="easy-wp-smtp-setting-row__label">
								<label><?php esc_html_e( 'WhatsApp Alerts', 'easy-wp-smtp' ); ?></label>
							</div>
							<div class="easy-wp-smtp-setting-row__field">
								<label class="easy-wp-smtp-toggle">
									<input type="checkbox"/>
									<span class="easy-wp-smtp-toggle__switch"></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
									<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
								</label>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-options">
						<div class="easy-wp-smtp-row easy-wp-smtp-alert-setting-row-connection-options">
							<div class="easy-wp-smtp-row">
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'Access Token', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'WhatsApp Business Account ID', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'Phone Number ID', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
								<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
									<div class="easy-wp-smtp-setting-row__label">
										<label><?php esc_html_e( 'To Phone Number', 'easy-wp-smtp' ); ?></label>
									</div>
									<div class="easy-wp-smtp-setting-row__field"><input type="text"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<a href="<?php echo esc_url( $upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green">
			<?php esc_html_e( 'Upgrade to Easy WP SMTP Pro', 'easy-wp-smtp' ); ?>
		</a>
		<?php
	}
}
