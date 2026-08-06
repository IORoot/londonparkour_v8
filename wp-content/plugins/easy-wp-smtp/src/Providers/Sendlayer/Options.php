<?php

namespace EasyWPSMTP\Providers\Sendlayer;

use EasyWPSMTP\Admin\ConnectionSettings;
use EasyWPSMTP\ConnectionInterface;
use EasyWPSMTP\Helpers\UI;
use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.0.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	const SLUG = 'sendlayer';

	/**
	 * Options constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = easy_wp_smtp()->get_connections_manager()->get_primary_connection();
		}

		$description = sprintf(
			wp_kses(
			/* translators: %1$s - URL to sendlayer.com; %2$s - URL to SendLayer documentation on easywpsmtp.com. */
				__( '<p><strong><a href="%1$s" target="_blank" rel="noopener noreferrer">SendLayer</a> is our #1 recommended mailer.</strong> It offers affordable pricing and is easy to set up, which makes it an excellent option for WordPress sites. With SendLayer, your domain will be authenticated so all your outgoing emails reach your customers\' inboxes. Our detailed <a href="%2$s" target="_blank" rel="noopener noreferrer">documentation</a> will walk you through the entire process, start to finish. <span class="easy-wp-smtp-text-primary">When you sign up for a free trial, you can send your first emails at no charge.</span></p>', 'easy-wp-smtp' ), // phpcs:ignore WordPress.WP.I18n.NoHtmlWrappedStrings
				[
					'strong' => [],
					'p'      => [],
					'span'   => [
						'class' => [],
					],
					'a'      => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound, WordPress.Security.NonceVerification.Recommended
			esc_url( easy_wp_smtp()->get_utm_url( 'https://sendlayer.com/easy-wp-smtp/', [ 'source' => 'easywpsmtpplugin', 'medium' => 'WordPress', 'content' => isset( $_GET['page'] ) && $_GET['page'] === 'easy-wp-smtp-setup-wizard' ? 'Setup Wizard - Mailer Description' : 'Plugin Settings - Mailer Description' ] ) ),
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-sendlayer-mailer/', 'SendLayer Documentation' ) )
		);

		parent::__construct(
			[
				'logo_url'    => easy_wp_smtp()->assets_url . '/images/providers/sendlayer.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'SendLayer', 'easy-wp-smtp' ),
				'description' => $description,
				'recommended' => true,
				'supports'    => [
					'from_email'       => true,
					'from_name'        => true,
					'return_path'      => false,
					'from_email_force' => true,
					'from_name_force'  => true,
				],
			],
			$connection
		);
	}

	/**
	 * Output the mailer provider options.
	 *
	 * @since 2.0.0
	 */
	public function display_options() {

		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		$get_api_key_url  = easy_wp_smtp()->get_utm_url( 'https://app.sendlayer.com/settings/api/', ['source' => 'easywpsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Get API Key'] );
		$is_const         = $this->connection_options->is_const_defined( $this->get_slug(), 'api_key' );
		$has_api_key      = $is_const || ! empty( $this->connection_options->get( 'sendlayer', 'api_key' ) );
		$is_quick_connect = (bool) $this->connection_options->get( 'sendlayer', 'quick_connect' );
		$is_shared_domain = $is_quick_connect && (bool) $this->connection_options->get( 'sendlayer', 'is_shared_domain' );
		$sender_domain    = $is_shared_domain ? $this->connection_options->get( 'sendlayer', 'sender_domain' ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_new_additional = ! $this->connection->is_primary() && isset( $_GET['mode'] ) && $_GET['mode'] === 'new';
		?>
		<?php if ( ! $has_api_key ) : ?>
			<!-- Quick Connect -->
			<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-connect" class="easy-wp-smtp-row easy-wp-smtp-setting-row">
				<div class="easy-wp-smtp-setting-row__field">
					<?php if ( $is_new_additional ) : ?>
						<div class="easy-wp-smtp-notice easy-wp-smtp-notice--info" style="margin-bottom: 20px;">
							<?php esc_html_e( 'Please save this additional connection first to use Quick Connect or enter an API Key manually.', 'easy-wp-smtp' ); ?>
						</div>
					<?php endif; ?>
					<button type="button" id="easy-wp-smtp-sendlayer-connect-btn" class="easy-wp-smtp-btn easy-wp-smtp-btn--primary easy-wp-smtp-btn--lg easy-wp-smtp-sendlayer-connect-btn"
						<?php disabled( $is_new_additional ); ?>>
						<?php esc_html_e( 'Quick Connect', 'easy-wp-smtp' ); ?>
						<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.5898 0C13.8086 0 14 0.191406 14 0.410156L13.9727 4.86719C13.9727 5.08594 13.8086 5.25 13.5898 5.25H12.6875V5.27734C12.4688 5.27734 12.2773 5.08594 12.2773 4.86719L12.3594 2.84375L12.3047 2.78906L4.67578 10.418C4.62109 10.4727 4.53906 10.5273 4.45703 10.5273C4.34766 10.5273 4.26562 10.4727 4.21094 10.418L3.58203 9.78906C3.52734 9.73438 3.47266 9.65234 3.47266 9.54297C3.47266 9.46094 3.52734 9.37891 3.58203 9.32422L11.2109 1.69531L11.1562 1.64062L9.13281 1.72266C8.91406 1.72266 8.75 1.53125 8.75 1.3125V0.410156C8.75 0.191406 8.91406 0.0273438 9.13281 0.0273438L13.5898 0ZM11.8125 7.875C12.0312 7.875 12.25 8.09375 12.25 8.3125V12.6875C12.25 13.4258 11.6484 14 10.9375 14H1.3125C0.574219 14 0 13.4258 0 12.6875V3.0625C0 2.35156 0.574219 1.75 1.3125 1.75H5.6875C5.90625 1.75 6.125 1.96875 6.125 2.1875V2.625C6.125 2.87109 5.90625 3.0625 5.6875 3.0625H1.47656C1.36719 3.0625 1.3125 3.14453 1.3125 3.22656V12.5234C1.3125 12.6328 1.36719 12.6875 1.47656 12.6875H10.7734C10.8555 12.6875 10.9375 12.6328 10.9375 12.5234V8.3125C10.9375 8.09375 11.1289 7.875 11.375 7.875H11.8125Z" fill="white"/></svg>
					</button>
					<p class="desc">
						<span class="easy-wp-smtp-sendlayer-connect-badge">
							<svg width="9" height="11" viewBox="0 0 9 11" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.67969 0.175781C6.91406 0.351562 6.99219 0.644531 6.89453 0.917969L5.35156 4.74609H8.18359C8.45703 4.74609 8.69141 4.90234 8.76953 5.15625C8.86719 5.41016 8.78906 5.68359 8.59375 5.85938L2.96875 10.5469C2.73438 10.7227 2.42188 10.7422 2.1875 10.5664C1.95312 10.3906 1.875 10.0977 1.97266 9.82422L3.51562 5.99609H0.683594C0.429688 5.99609 0.195312 5.83984 0.0976562 5.58594C0 5.33203 0.078125 5.05859 0.292969 4.88281L5.91797 0.195312C6.13281 0.0195312 6.44531 0 6.67969 0.175781Z" fill="#6F6F84"/></svg>
							<?php esc_html_e( 'Takes about 2 mins', 'easy-wp-smtp' ); ?>
						</span>
					</p>
					<p class="desc easy-wp-smtp-sendlayer-connect-error"></p>
				</div>
			</div>

			<?php if ( ! $is_new_additional ) : ?>
				<!-- Manual API Key toggle -->
				<div class="easy-wp-smtp-row easy-wp-smtp-setting-row">
					<div class="easy-wp-smtp-setting-row__field">
						<a href="#" id="easy-wp-smtp-sendlayer-show-api-key">
							<?php esc_html_e( 'Connect manually with API Key', 'easy-wp-smtp' ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $is_shared_domain && ! empty( $sender_domain ) ) : ?>
			<!-- Domain (shown for quick connect shared domain accounts) -->
			<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-domain" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
				<div class="easy-wp-smtp-setting-row__label">
					<label><?php esc_html_e( 'Domain', 'easy-wp-smtp' ); ?></label>
				</div>
				<div class="easy-wp-smtp-setting-row__field">
					<p class="easy-wp-smtp-sendlayer-domain-value">
						<?php echo esc_html( $sender_domain ); ?>
						<a href="#" id="easy-wp-smtp-sendlayer-change-domain" class="easy-wp-smtp-sendlayer-change-domain">
							<?php esc_html_e( 'Change', 'easy-wp-smtp' ); ?>
						</a>
					</p>
					<p class="desc">
						<?php
						printf(
							wp_kses(
								/* translators: %1$s - URL to SendLayer app domains page; %2$s - URL to documentation about custom domains. */
								__( 'This is a shared domain created for you via SendLayer Connect. You will need to register your own domain first on the <a href="%1$s" target="_blank" rel="noopener noreferrer">SendLayer dashboard</a> to change it here. Check our <a href="%2$s" target="_blank" rel="noopener noreferrer">documentation</a> on how to add a custom domain.', 'easy-wp-smtp' ),
								[
									'a' => [
										'href'   => [],
										'rel'    => [],
										'target' => [],
									],
								]
							),
							// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
							esc_url( easy_wp_smtp()->get_utm_url( 'https://app.sendlayer.com/', [ 'source' => 'easywpsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Domain Link' ] ) ),
							// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
							esc_url( easy_wp_smtp()->get_utm_url( 'https://sendlayer.com/docs/authorizing-your-domain/', [ 'source' => 'easywpsmtpplugin', 'medium' => 'WordPress', 'content' => 'Plugin Settings - Custom Domain Documentation' ] ) )
						);
						?>
					</p>
					<p class="desc easy-wp-smtp-sendlayer-connect-error"></p>
				</div>
			</div>
		<?php endif; ?>

		<!-- API Key -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
			 class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text"
			 <?php if ( ! $has_api_key && ! $is_new_additional ) : ?>
				 style="display: none;"
			 <?php endif; ?>>
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'API Key', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<?php if ( $is_const ) : ?>
					<input type="text" disabled value="****************************************"
						id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_SENDLAYER_API_KEY' ); ?>
				<?php else : ?>
					<?php
					$slug  = $this->get_slug();
					$value = $this->connection_options->get( $slug, 'api_key' );

					$field_args = [
						'name'       => "easy-wp-smtp[{$slug}][api_key]",
						'id'         => "easy-wp-smtp-setting-{$slug}-api_key",
						'value'      => $value,
						'clear_text' => esc_html__( 'Remove API Key', 'easy-wp-smtp' ),
					];

					if ( $is_quick_connect ) {
						$field_args['clear_text'] = esc_html__( 'Disconnect', 'easy-wp-smtp' );
						$field_args['clear_url']  = wp_nonce_url( ( new ConnectionSettings( $this->connection ) )->get_admin_page_url(), 'sendlayer_quick_connect_disconnect', 'sendlayer_quick_connect_disconnect_nonce' );
					}

					UI::hidden_password_field( $field_args );
					?>
				<?php endif; ?>
				<p class="desc">
					<?php
					printf(
						/* translators: %s - API key link. */
						esc_html__( 'Follow this link to get an API Key from SendLayer: %s.', 'easy-wp-smtp' ),
						'<a href="' . esc_url( $get_api_key_url ) . '" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get API Key', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<input type="hidden" id="easy-wp-smtp-sendlayer-quick-connect-return-url"
					 value="<?php echo esc_url( ( new ConnectionSettings( $this->connection ) )->get_admin_page_url() ); ?>" />
		<input type="hidden" id="easy-wp-smtp-sendlayer-quick-connect-connection-id"
					 value="<?php echo esc_attr( $this->connection->get_id() ); ?>" />
		<?php
	}
}
