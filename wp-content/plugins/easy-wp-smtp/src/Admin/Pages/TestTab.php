<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\ConnectionInterface;
use EasyWPSMTP\Options;
use EasyWPSMTP\TestEmail\TestEmail;
use EasyWPSMTP\WP;
use EasyWPSMTP\Admin\PageAbstract;

/**
 * Class TestTab is part of Area, displays email testing page of the plugin.
 *
 * @since 2.0.0
 */
class TestTab extends PageAbstract {

	/**
	 * @var string Slug of a tab.
	 */
	protected $slug = 'test';

	/**
	 * Tab priority.
	 *
	 * @since 2.0.0
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Domain Checker API object.
	 *
	 * @since 2.1.0
	 *
	 * @var DomainChecker|null
	 */
	private $domain_checker;

	/**
	 * Option key where the test email form values are persisted between visits,
	 * so the next page load prefills with what the user last entered.
	 *
	 * @since 2.15.0
	 *
	 * @const string
	 */
	const TEST_EMAIL_OPTION_KEY = 'easy_wp_smtp_test_email';

	/**
	 * Test email sending failed.
	 *
	 * @since 2.1.0
	 *
	 * @const int
	 */
	const FAILED = 0;

	/**
	 * Test email sent successfully.
	 *
	 * @since 2.1.0
	 *
	 * @const int
	 */
	const SUCCESS = 1;

	/**
	 * Test email domain check failed.
	 *
	 * @since 2.1.0
	 *
	 * @const int
	 */
	const FAILED_DOMAIN_CHECK = 2;

	/**
	 * Test email result.
	 *
	 * @since 2.1.0
	 *
	 * @var int
	 */
	private $result = null;

	/**
	 * Test email POST data.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $post_data = [];

	/**
	 * Test email connection.
	 *
	 * @since 2.0.0
	 *
	 * @var ConnectionInterface
	 */
	private $connection;

	/**
	 * @inheritdoc
	 */
	public function get_label() {

		return esc_html__( 'Email Test', 'easy-wp-smtp' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Display the content of the tab.
	 *
	 * @since 2.0.0
	 */
	public function display() {

		if ( $this->result === self::SUCCESS ) {
			$this->display_success_banner();
		}

		$this->display_form();

		if ( $this->result === self::FAILED_DOMAIN_CHECK ) {
			echo '<div class="easy-wp-smtp-test-email-result">';
			$this->display_domain_check_details();
			echo '</div>';
			?>
			<!-- Scroll to the domain check container. -->
			<script>
				jQuery( function( $ ) {
					$( 'html, body' ).animate( {
						scrollTop: $( ".easy-wp-smtp-test-email-result" ).offset().top - 50
					}, 500 );
				} );
			</script>
			<?php
		}
	}

	/**
	 * Display test email form.
	 *
	 * @since 2.0.0
	 */
	private function display_form() {

		$test_email_options = array_merge(
			[
				'to'      => '',
				'html'    => true,
				'subject' => '',
				'message' => '',
			],
			get_option( self::TEST_EMAIL_OPTION_KEY, [] )
		);

		if ( empty( $test_email_options['to'] ) ) {
			$test_email_options['to'] = wp_get_current_user()->user_email;
		}

		?>
		<form id="easy-wp-smtp-email-test-form" method="POST" action="<?php echo esc_url( $this->get_link() ); ?>">
			<?php $this->wp_nonce_field(); ?>
			<div class="easy-wp-smtp-meta-box">
				<div class="easy-wp-smtp-meta-box__header">
					<div class="easy-wp-smtp-meta-box__heading">
						<?php esc_html_e( 'Send a Test', 'easy-wp-smtp' ); ?>
					</div>
				</div>
				<div class="easy-wp-smtp-meta-box__content">
					<!-- Test Email -->
					<div id="easy-wp-smtp-setting-row-test_email" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
						<div class="easy-wp-smtp-setting-row__label">
							<label for="easy-wp-smtp-setting-test_email"><?php esc_html_e( 'Send To', 'easy-wp-smtp' ); ?></label>
						</div>
						<div class="easy-wp-smtp-setting-row__field">
							<input name="easy-wp-smtp[test][email]" value="<?php echo esc_attr( $test_email_options['to'] ); ?>"
										 type="email" id="easy-wp-smtp-setting-test_email" spellcheck="false" placeholder="yourmail@example.com"
										 required
							/>
							<p class="desc">
								<?php esc_html_e( 'Enter the email address you want to send the test email to.', 'easy-wp-smtp' ); ?>
							</p>
						</div>
					</div>

					<?php
					/**
					 * Fires after "Send To" section on the test email page.
					 *
					 * @since 2.0.0
					 */
					do_action( 'easy_wp_smtp_admin_pages_test_tab_display_form_send_to_after' );
					?>

					<!-- HTML/Plain -->
					<div id="easy-wp-smtp-setting-row-test_email_html" class="easy-wp-smtp-row easy-wp-smtp-setting-row">
						<div class="easy-wp-smtp-setting-row__label">
							<label for="easy-wp-smtp-setting-test_email_html"><?php esc_html_e( 'HTML', 'easy-wp-smtp' ); ?></label>
						</div>
						<div class="easy-wp-smtp-setting-row__field">
							<label class="easy-wp-smtp-toggle" for="easy-wp-smtp-setting-test_email_html">
								<input type="checkbox" id="easy-wp-smtp-setting-test_email_html" name="easy-wp-smtp[test][html]" value="yes" <?php checked( (bool) $test_email_options['html'] ); ?>/>
								<span class="easy-wp-smtp-toggle__switch"></span>
								<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
								<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
							</label>
							<p class="desc">
								<?php esc_html_e( 'Enable to send this email in HTML format. Disable to send it in plain text format.', 'easy-wp-smtp' ); ?>
							</p>
						</div>
					</div>

					<!-- Custom Email -->
					<div id="easy-wp-smtp-setting-row-test_email_custom" class="easy-wp-smtp-row easy-wp-smtp-setting-row">
						<div class="easy-wp-smtp-setting-row__label">
							<label for="easy-wp-smtp-setting-test_email_custom"><?php esc_html_e( 'Custom Email', 'easy-wp-smtp' ); ?></label>
						</div>
						<div class="easy-wp-smtp-setting-row__field">
							<label class="easy-wp-smtp-toggle" for="easy-wp-smtp-setting-test_email_custom">
								<input type="checkbox" id="easy-wp-smtp-setting-test_email_custom" name="easy-wp-smtp[test][custom]" value="yes"/>
								<span class="easy-wp-smtp-toggle__switch"></span>
								<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'On', 'easy-wp-smtp' ); ?></span>
								<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'Off', 'easy-wp-smtp' ); ?></span>
							</label>
							<p class="desc">
								<?php esc_html_e( 'Replace the predefined email template with your own content.', 'easy-wp-smtp' ); ?>
							</p>
						</div>
					</div>

					<!-- Subject -->
					<div id="easy-wp-smtp-setting-row-test_email_subject" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text" style="display: none;">
						<div class="easy-wp-smtp-setting-row__label">
							<label for="easy-wp-smtp-setting-test_email_subject"><?php esc_html_e( 'Subject', 'easy-wp-smtp' ); ?></label>
						</div>
						<div class="easy-wp-smtp-setting-row__field">
							<input name="easy-wp-smtp[test][subject]" type="text" id="easy-wp-smtp-setting-test_email_subject" value="<?php echo esc_attr( $test_email_options['subject'] ); ?>" spellcheck="false">
							<p class="desc">
								<?php esc_html_e( 'Enter a custom subject for your message.', 'easy-wp-smtp' ); ?>
							</p>
						</div>
					</div>

					<!-- Message -->
					<div id="easy-wp-smtp-setting-row-test_email_message" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text" style="display: none;">
						<div class="easy-wp-smtp-setting-row__label">
							<label for="easy-wp-smtp-setting-test_email_message"><?php esc_html_e( 'Message', 'easy-wp-smtp' ); ?></label>
						</div>
						<div class="easy-wp-smtp-setting-row__field">
							<textarea name="easy-wp-smtp[test][message]" id="easy-wp-smtp-setting-test_email_message" spellcheck="false" rows="9"><?php echo esc_textarea( stripslashes( $test_email_options['message'] ) ); ?></textarea>
							<p class="desc">
								<?php esc_html_e( 'Write your custom email message.', 'easy-wp-smtp' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>

			<?php
			$btn       = 'easy-wp-smtp-btn--primary';
			$disabled  = '';
			$help_text = '';

			$mailer = easy_wp_smtp()->get_providers()->get_mailer(
				Options::init()->get( 'mail', 'mailer' ),
				easy_wp_smtp()->get_processor()->get_phpmailer()
			);

			if ( ! $mailer || ! $mailer->is_mailer_complete() ) {
				$btn      = 'easy-wp-smtp-btn--primary easy-wp-smtp-btn--primary--disabled';
				$disabled = 'disabled';

				$help_text = '<div class="easy-wp-smtp-test-email-submit__text">' . esc_html__( 'You cannot send an email. Mailer is not properly configured. Please check your settings.', 'easy-wp-smtp' ) . '</div>';
			}
			?>
			<div class="easy-wp-smtp-test-email-submit">
				<button type="submit" class="easy-wp-smtp-btn easy-wp-smtp-btn--lg <?php echo esc_attr( $btn ); ?>" <?php echo esc_attr( $disabled ); ?>>
					<?php esc_html_e( 'Send Test Email', 'easy-wp-smtp' ); ?>
				</button>
				<?php echo $help_text; ?>
			</div>

			<?php $this->post_form_hidden_field(); ?>
		</form>

		<?php if ( ! empty( $mailer ) && $mailer->is_mailer_complete() && isset( $_GET['auto-start'] ) ) : // phpcs:ignore ?>
			<script>
				(function( $ ) {
					var $button = $( '.easy-wp-smtp-tab-tools-test #easy-wp-smtp-email-test-form .easy-wp-smtp-btn' );

					$button.addClass( 'easy-wp-smtp-btn--loading' );

					$( '#easy-wp-smtp-email-test-form' ).submit();
				}( jQuery ));
			</script>
		<?php
		endif;
	}

	/**
	 * @inheritdoc
	 */
	public function process_post( $data ) {

		$this->post_data = $data;

		$connection = easy_wp_smtp()->get_connections_manager()->get_primary_connection();

		/**
		 * Filters test email connection object.
		 *
		 * @since 2.0.0
		 *
		 * @param ConnectionInterface $connection The Connection object.
		 * @param array               $data       Post data.
		 */
		$this->connection = apply_filters( 'easy_wp_smtp_admin_pages_test_tab_process_post_connection', $connection, $data );

		if ( ! empty( $data['test']['email'] ) ) {
			$data['test']['email'] = wp_unslash( $data['test']['email'] );
			$data['test']['email'] = filter_var( $data['test']['email'], FILTER_VALIDATE_EMAIL );
		}

		$is_html = ! empty( $data['test']['html'] );

		if ( empty( $data['test']['email'] ) ) {
			WP::add_admin_notice(
				esc_html__( 'Test failed. Please use a valid email address and try to resend the test email.', 'easy-wp-smtp' ),
				WP::ADMIN_NOTICE_WARNING
			);
			return;
		}

		$to = $data['test']['email'];

		// Delegate the send to the shared TestEmail builder, so the subject/body,
		// content-type handling, and domain check live in one place (no duplication).
		$test_email = ( new TestEmail() )
			->with_connection( $this->connection )
			->with_context( TestEmail::CONTEXT_ADMIN_TEST )
			->as_html( $is_html )
			->with_domain_check( true );

		// Preserve the Test Email page's custom subject/message option.
		$is_custom = ! empty( $data['test']['custom'] );

		if ( $is_custom && ! empty( $data['test']['subject'] ) ) {
			$test_email->with_custom_subject( $data['test']['subject'] );
		}

		if ( $is_custom && ! empty( $data['test']['message'] ) ) {
			$test_email->with_custom_message( $data['test']['message'] );
		}

		// Force processing for test email even if email sending is blocked.
		easy_wp_smtp()->get_processor()->set_force_processing( true );

		$test_email->send( $to );

		easy_wp_smtp()->get_processor()->set_force_processing( false );

		// Send failures are surfaced by the EmailSendingErrors banner, which the
		// MailCatcher populates at send-time, so no debug data is assembled here.
		$this->result         = $test_email->get_result();
		$this->domain_checker = $test_email->get_domain_checker();

		// Persist the form values so the next visit prefills with what the
		// user last entered.
		$test_email_options = get_option( self::TEST_EMAIL_OPTION_KEY, [] );

		$test_email_options['to']   = filter_var( $to, FILTER_SANITIZE_EMAIL );
		$test_email_options['html'] = $is_html;

		if ( $is_custom ) {
			$test_email_options['subject'] = ! empty( $data['test']['subject'] ) ? sanitize_text_field( $data['test']['subject'] ) : '';

			if ( ! empty( $data['test']['message'] ) ) {
				$test_email_options['message'] = sanitize_textarea_field( $data['test']['message'] );
			}
		}

		update_option( self::TEST_EMAIL_OPTION_KEY, $test_email_options, false );
	}

	/**
	 * Get the plain text prepared message for test email.
	 *
	 * Use {@see TestEmail::get_email_message_text()} instead.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 *
	 * @return string
	 */
	public static function get_email_message_text() {

		return TestEmail::get_email_message_text();
	}

	/**
	 * Set the HTML content type for a test email.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function set_test_html_content_type() {

		return 'text/html';
	}

	/**
	 * Render the success banner shown after a test email succeeds. This is the
	 * Lite implementation: success headline, four Pro feature bullets, an
	 * "Upgrade to Pro" CTA with a $50 OFF badge, the hero illustration, and
	 * the Pro Tip strip. The Pro subclass overrides this method to render its
	 * own variants.
	 *
	 * @since 2.15.0
	 */
	protected function display_success_banner() {

		$assets_url   = easy_wp_smtp()->assets_url;
		$illustration = $assets_url . '/images/test-success/illustration-lite.svg';

		$upgrade_url = easy_wp_smtp()->get_upgrade_link(
			[
				'medium'  => 'lite-test-email-success',
				'content' => 'Upgrade Button',
			]
		);

		$bullets = [
			esc_html__( 'Log, track, and resend any email your site sends', 'easy-wp-smtp' ),
			esc_html__( 'Get instant failure alerts via Email, Slack, SMS, Discord, etc', 'easy-wp-smtp' ),
			esc_html__( 'Never miss an email with an automatic backup mailer', 'easy-wp-smtp' ),
		];
		?>
		<div class="esmtp-test-email-success-banner easy-wp-smtp-test-success-banner easy-wp-smtp-test-success-banner--lite">
			<?php $this->display_success_banner_dismiss(); ?>

			<div class="esmtp:flex esmtp:items-center esmtp:gap-md esmtp:max-tablet:flex-col esmtp:max-tablet:items-stretch">
				<div class="esmtp:flex esmtp:flex-col esmtp:flex-1 esmtp:min-w-[0] esmtp:gap-md esmtp:p-md">
					<div class="esmtp:flex esmtp:flex-col esmtp:gap-sm">
						<div class="esmtp-test-email-success-banner__heading">
							<span aria-hidden="true" class="esmtp:icon-[fa6-solid--circle-check] esmtp:text-success esmtp:w-[16px] esmtp:h-[16px] esmtp:shrink-0"></span>
							<h2>
								<?php esc_html_e( 'Test email sent successfully! Check your inbox to confirm delivery.', 'easy-wp-smtp' ); ?>
							</h2>
						</div>
						<p class="esmtp:m-[0]! esmtp:text-sm! esmtp:leading-5! esmtp:text-tertiary">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s - "Pro" wrapped in a bold tag highlighting the Easy WP SMTP Pro tier. */
									__( 'Level Up Your Email Game! Unlock these %s features and get even more from Easy WP SMTP.', 'easy-wp-smtp' ),
									'<strong class="esmtp:font-medium! esmtp:text-primary">' . esc_html__( 'Pro', 'easy-wp-smtp' ) . '</strong>'
								),
								[ 'strong' => [ 'class' => [] ] ]
							);
							?>
						</p>
					</div>

					<ul class="esmtp:m-[0]! esmtp:p-[0]! esmtp:list-none esmtp:flex esmtp:flex-col esmtp:gap-sm">
						<?php foreach ( $bullets as $bullet ) : ?>
							<li class="esmtp:m-[0]! esmtp:p-[0]! esmtp:flex esmtp:items-center esmtp:gap-sm">
								<span aria-hidden="true" class="esmtp:icon-[fa6-solid--check] esmtp:text-success esmtp:w-[16px] esmtp:h-[16px] esmtp:shrink-0"></span>
								<span class="esmtp:text-sm! esmtp:leading-5! esmtp:font-medium! esmtp:text-primary"><?php echo esc_html( $bullet ); ?></span>
							</li>
						<?php endforeach; ?>
						<li class="esmtp:m-[0]! esmtp:p-[0]! esmtp:flex esmtp:items-center esmtp:gap-sm">
							<span aria-hidden="true" class="esmtp:icon-[fa6-solid--check] esmtp:text-success esmtp:w-[16px] esmtp:h-[16px] esmtp:shrink-0"></span>
							<span class="esmtp:text-sm! esmtp:leading-5! esmtp:font-medium! esmtp:text-primary">
								<?php esc_html_e( 'Priority support from our email deliverability experts', 'easy-wp-smtp' ); ?>
							</span>
							<span class="esmtp:text-sm! esmtp:leading-5! esmtp:text-tertiary">
								<?php esc_html_e( '...and much more!', 'easy-wp-smtp' ); ?>
							</span>
						</li>
					</ul>

					<div class="esmtp:flex esmtp:flex-col esmtp:gap-[8px] esmtp:items-start">
						<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green">
							<?php esc_html_e( 'Upgrade to Easy WP SMTP Pro', 'easy-wp-smtp' ); ?>
						</a>

						<p class="esmtp:m-[0]! esmtp:flex esmtp:items-center esmtp:gap-[7px]">
							<span aria-hidden="true" class="esmtp:icon-[custom--badge-percent] esmtp:text-success esmtp:w-[16px] esmtp:h-[16px] esmtp:shrink-0"></span>
							<span class="esmtp:text-sm! esmtp:leading-5! esmtp:text-tertiary">
								<strong class="esmtp:font-medium! esmtp:text-success"><?php esc_html_e( '50% OFF', 'easy-wp-smtp' ); ?></strong>
								<?php esc_html_e( 'for Easy WP SMTP users, applied at checkout.', 'easy-wp-smtp' ); ?>
							</span>
						</p>
					</div>
				</div>

				<div class="esmtp:w-[400px] esmtp:self-stretch esmtp:shrink-0 esmtp:max-tablet:w-full esmtp:max-tablet:h-[316px]">
					<img src="<?php echo esc_url( $illustration ); ?>" alt="<?php esc_attr_e( 'A person celebrating after sending an email.', 'easy-wp-smtp' ); ?>" class="esmtp:block esmtp:w-full esmtp:h-full esmtp:object-cover">
				</div>
			</div>

			<?php $this->display_success_pro_tip_strip(); ?>
		</div>
		<?php
	}

	/**
	 * Render the dismiss button shared by every success-banner variant.
	 * Uses the WP-native `.notice-dismiss` class so the visual treatment
	 * matches WP admin notices; JS handles the per-session hide.
	 *
	 * @since 2.15.0
	 */
	protected function display_success_banner_dismiss() {

		?>
		<button type="button" class="notice-dismiss esmtp-test-email-success-banner__dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'easy-wp-smtp' ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'easy-wp-smtp' ); ?></span>
		</button>
		<?php
	}

	/**
	 * Render the Pro Tip strip shown below the Lite and Pro-Setup-Pending
	 * banners. Picks the first not-installed cross-sell candidate as the
	 * featured plugin; if no candidates remain, renders nothing.
	 *
	 * @since 2.15.0
	 */
	protected function display_success_pro_tip_strip() {

		// Capability gating lives in get_cross_sell_recommendations() — it returns
		// an empty pool for users without install_plugins, which short-circuits below.
		$recommendations = $this->get_cross_sell_recommendations( 1 );

		if ( empty( $recommendations ) ) {
			return;
		}

		$product     = $recommendations[0];
		$install_url = $this->get_install_plugin_url( $product['install'] );
		?>
		<div class="esmtp-test-email-pro-tip-strip esmtp:flex esmtp:items-center esmtp:gap-sm esmtp:px-md esmtp:py-sm esmtp:bg-surface-background-white esmtp:border-t esmtp:border-surface-divider">
			<span aria-hidden="true" class="esmtp:icon-[fa6-solid--lightbulb] esmtp:text-utility-yellow-50 esmtp:w-[14px] esmtp:h-[14px] esmtp:shrink-0"></span>
			<span class="esmtp-test-email-pro-tip-strip__initial esmtp:text-sm esmtp:leading-5 esmtp:text-tertiary">
				<strong class="esmtp:font-medium! esmtp:text-primary"><?php esc_html_e( 'Pro Tip:', 'easy-wp-smtp' ); ?></strong>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %1$s - call to action, %2$s - product name wrapped in <strong>, e.g. WPConsent. */
						__( '%1$s with our sister plugin %2$s', 'easy-wp-smtp' ),
						esc_html( $product['pro_tip'] ),
						'<strong class="esmtp:font-medium! esmtp:text-primary">' . esc_html( $product['name'] ) . '</strong>'
					),
					[ 'strong' => [ 'class' => [] ] ]
				);
				?>
				-
				<a href="<?php echo esc_url( $install_url ); ?>"
					class="js-easy-wp-smtp-plugin-install-link status-download esmtp:font-medium! esmtp:text-link esmtp:underline esmtp:focus:outline-none! esmtp:focus:shadow-none!"
					data-plugin="<?php echo esc_attr( $product['install_url'] ); ?>"
					data-plugin-name="<?php echo esc_attr( $product['name'] ); ?>"
					>
					<?php
					/* translators: %s - product name (e.g. WPConsent). */
					printf( esc_html__( 'Install %s (Free)', 'easy-wp-smtp' ), esc_html( $product['name'] ) );
					?>
					</a>
			</span>
			<span class="esmtp-test-email-pro-tip-strip__success esmtp:text-sm esmtp:leading-5 esmtp:text-tertiary" role="status" hidden>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %1$s - plugin name (bold). %2$s - settings-page hyperlink. */
						__( '%1$s was installed and activated, please visit their %2$s to configure it.', 'easy-wp-smtp' ),
						'<strong class="esmtp:font-medium! esmtp:text-primary">' . esc_html( $product['name'] ) . '</strong>',
						'<a href="' . esc_url( $product['settings_page_url'] ) . '" class="esmtp:font-medium! esmtp:text-link esmtp:underline">' . esc_html__( 'settings page', 'easy-wp-smtp' ) . '</a>'
					),
					[
						'strong' => [ 'class' => [] ],
						'a'      => [
							'href'  => [],
							'class' => [],
						],
					]
				);
				?>
			</span>
		</div>
		<?php
	}

	/**
	 * Build the cross-sell pool filtered to plugins not already installed
	 * on this site. The catalog is inlined here — the success banner uses
	 * its own small, hand-curated set.
	 *
	 * @since 2.15.0
	 *
	 * @param int $limit Max number of products to return.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function get_cross_sell_recommendations( $limit = 3 ) { // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded -- Inner foreach over per-product competitor list is intentional; flattening would obscure the catalog structure.

		if ( ! current_user_can( 'install_plugins' ) ) {
			return [];
		}

		$assets_url = easy_wp_smtp()->assets_url;

		$catalog = [
			[
				'name'              => 'ActiveLayer',
				'title'             => esc_html__( 'Smarter Spam Protection for WordPress', 'easy-wp-smtp' ),
				'desc'              => esc_html__( 'Catch spam in milliseconds with AI, invisible to your real visitors.', 'easy-wp-smtp' ),
				'pro_tip'           => esc_html__( 'Stop spam at the door', 'easy-wp-smtp' ),
				'icon'              => $assets_url . '/images/about/icon-activelayer.svg',
				'plugin'            => 'activelayer-anti-spam-spam-protection-for-forms-comments/activelayer-anti-spam-spam-protection-for-forms-comments.php',
				'install'           => 'activelayer-anti-spam-spam-protection-for-forms-comments',
				'install_url'       => 'https://downloads.wordpress.org/plugin/activelayer-anti-spam-spam-protection-for-forms-comments.zip',
				'settings_page_url' => admin_url( 'admin.php?page=activelayer-settings' ),
				'framed_icon'       => true,
				'competitors'       => [
					'akismet/akismet.php',
					'antispam-bee/antispam_bee.php',
					'honeypot/wp-armour.php',
					'wp-armour-extended/wp-armour-extended.php',
					'cleantalk-spam-protect/cleantalk.php',
					'wp-cerber/wp-cerber.php',
					'anti-spam/anti-spam.php',
				],
			],
			[
				'name'              => 'WPConsent',
				'title'             => esc_html__( 'Stay GDPR & Privacy Compliant', 'easy-wp-smtp' ),
				'desc'              => esc_html__( 'Add a cookie consent banner to your site and meet privacy laws in minutes.', 'easy-wp-smtp' ),
				'pro_tip'           => esc_html__( 'Stay GDPR & Privacy compliant', 'easy-wp-smtp' ),
				'icon'              => $assets_url . '/images/about/icon-wpconsent.svg',
				'plugin'            => 'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
				'install'           => 'wpconsent-cookies-banner-privacy-suite',
				'install_url'       => 'https://downloads.wordpress.org/plugin/wpconsent-cookies-banner-privacy-suite.zip',
				'settings_page_url' => admin_url( 'admin.php?page=wpconsent-cookies' ),
				'framed_icon'       => true,
				'competitors'       => [
					'cookie-law-info/cookie-law-info.php',
					'complianz-gdpr/complianz-gpdr.php',
					'complianz-gdpr-premium/complianz-gpdr-premium.php',
					'cookie-notice/cookie-notice.php',
					'gdpr-cookie-compliance/moove-gdpr.php',
					'iubenda-cookie-law-solution/iubenda_cookie_solution.php',
					'real-cookie-banner/index.php',
					'cookiebot/cookiebot.php',
					'uk-cookie-consent/uk-cookie-consent.php',
					'borlabs-cookie/borlabs-cookie.php',
				],
			],
			[
				'name'              => 'Duplicator',
				'title'             => esc_html__( 'Add Secure WordPress Backups', 'easy-wp-smtp' ),
				'desc'              => esc_html__( 'Automated, encrypted backups with 1-click restore to keep your site safe.', 'easy-wp-smtp' ),
				'pro_tip'           => esc_html__( 'Protect your site with automated backups', 'easy-wp-smtp' ),
				'icon'              => $assets_url . '/images/about/icon-duplicator.svg',
				'plugin'            => 'duplicator/duplicator.php',
				'plugin_pro'        => 'duplicator-pro/duplicator-pro.php',
				'install'           => 'duplicator',
				'install_url'       => 'https://downloads.wordpress.org/plugin/duplicator.zip',
				'settings_page_url' => admin_url( 'admin.php?page=duplicator-settings' ),
				'framed_icon'       => false,
				'competitors'       => [
					'all-in-one-wp-migration/all-in-one-wp-migration.php',
					'all-in-one-wp-migration-unlimited-extension/all-in-one-wp-migration-unlimited-extension.php',
					'updraftplus/updraftplus.php',
					'wpvivid-backuprestore/wpvivid-backuprestore.php',
					'wpvivid-backup-pro/wpvivid-backup-pro.php',
					'backwpup/backwpup.php',
					'backwpup-pro/backwpup.php',
					'migrate-guru/migrateguru.php',
					'wp-migrate-db/wp-migrate-db.php',
					'wp-migrate-db-pro/wp-migrate-db-pro.php',
					'wp-staging/wp-staging.php',
					'wp-staging-pro/wp-staging-pro.php',
					'backupbuddy/backupbuddy.php',
				],
			],
		];

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();

		$candidates = array_filter(
			$catalog,
			function ( $product ) use ( $installed ) {
				if ( array_key_exists( $product['plugin'], $installed ) ) {
					return false;
				}

				// Pro variant present too — treat as "already provided" so we
				// don't push the Lite when the Pro version is installed.
				if ( ! empty( $product['plugin_pro'] ) && array_key_exists( $product['plugin_pro'], $installed ) ) {
					return false;
				}

				// Drop the entry when a competitor that covers the same need
				// is installed (e.g., recommending Duplicator to an
				// UpdraftPlus user is wasted screen space).
				if ( ! empty( $product['competitors'] ) ) {
					foreach ( $product['competitors'] as $competitor ) {
						if ( array_key_exists( $competitor, $installed ) ) {
							return false;
						}
					}
				}

				return true;
			}
		);

		return array_slice( array_values( $candidates ), 0, $limit );
	}

	/**
	 * Build a nonce-protected install-plugin URL for the WP-Admin updater.
	 * Users without the install_plugins capability get the public wp.org
	 * page so the link still makes sense.
	 *
	 * @since 2.15.0
	 *
	 * @param string $slug WP.org plugin slug.
	 *
	 * @return string
	 */
	private function get_install_plugin_url( $slug ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return 'https://wordpress.org/plugins/' . $slug . '/';
		}

		return wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ),
			'install-plugin_' . $slug
		);
	}

	/**
	 * Returns debug information for detection, processing, and display.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 *
	 * @return array
	 */
	protected function get_debug_details() {

		_deprecated_function( __METHOD__, '2.15.0', '\EasyWPSMTP\Admin\EmailSendingErrors\EmailSendingErrors::get_local_failure_info' );

		return [];
	}

	/**
	 * Displays all the various error and debug details.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 */
	protected function display_debug_details() {

		_deprecated_function( __METHOD__, '2.15.0', '\EasyWPSMTP\Admin\EmailSendingErrors\EmailSendingErrors::print_banner_body' );
	}

	/**
	 * Display the domain check details.
	 *
	 * @since 2.1.0
	 */
	protected function display_domain_check_details() {

		if ( empty( $this->domain_checker ) || $this->domain_checker->no_issues() ) {
			return;
		}
		?>
		<?php if ( $this->domain_checker->is_supported_mailer() ) : ?>
			<div class="notice-warning notice-inline easy-wp-smtp-notice">
				<p><?php esc_html_e( 'The test email might have sent, but its deliverability should be improved.', 'easy-wp-smtp' ); ?></p>
			</div>
		<?php endif; ?>

		<?php echo $this->domain_checker->get_results_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
	}
}
