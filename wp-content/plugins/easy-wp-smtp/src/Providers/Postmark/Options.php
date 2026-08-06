<?php

namespace EasyWPSMTP\Providers\Postmark;

use EasyWPSMTP\ConnectionInterface;
use EasyWPSMTP\Helpers\UI;
use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.3.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.3.0
	 */
	const SLUG = 'postmark';

	/**
	 * Options constructor.
	 *
	 * @since 2.3.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		$description = sprintf(
			wp_kses( /* translators: %1$s - URL to postmarkapp.com site, %2$s - URL to easywpsmtp.com doc. */
				__( '<p><a href="%1$s" target="_blank" rel="noopener noreferrer">Postmark</a> is a transactional email service with great deliverability and budget-friendly pricing. You can start out with their free trial option, which allows you to send up to 100 emails per month through their secure API.', 'easy-wp-smtp' ) .
				'</p><p>To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">Postmark documentation</a>.</p>',
				[
					'strong' => true,
					'p'      => true,
					'a'      => [
						'href'   => true,
						'rel'    => true,
						'target' => true,
					],
				]
			),
			'https://postmarkapp.com',
			esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-postmark-mailer/', 'Postmark documentation' ) )
		);

		parent::__construct(
			[
				'logo_url'    => easy_wp_smtp()->assets_url . '/images/providers/postmark.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'Postmark', 'easy-wp-smtp' ),
				'php'         => '5.6',
				'description' => $description,
				'supports'    => [
					'from_email'       => true,
					'from_name'        => true,
					'return_path'      => false,
					'from_email_force' => true,
					'from_name_force'  => true,
				],
				'recommended' => false,
			],
			$connection
		);
	}

	/**
	 * Output the mailer provider options.
	 *
	 * @since 2.3.0
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();

			return;
		}
		?>

		<!-- Server API Token -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-server_api_token" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-server_api_token"><?php esc_html_e( 'Server API Token', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<?php if ( $this->connection_options->is_const_defined( $this->get_slug(), 'server_api_token' ) ) : ?>
					<input type="text" disabled value="****************************************"
					       id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-server_api_token"/>
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_POSTMARK_SERVER_API_TOKEN' ); ?>
				<?php else : ?>
					<?php
					$slug  = $this->get_slug();
					$value = $this->connection_options->get( $slug, 'server_api_token' );

					UI::hidden_password_field(
						[
							'name'       => "easy-wp-smtp[{$slug}][server_api_token]",
							'id'         => "easy-wp-smtp-setting-{$slug}-server_api_token",
							'value'      => $value,
							'clear_text' => esc_html__( 'Remove Server API Token', 'easy-wp-smtp' ),
						]
					);
					?>
				<?php endif; ?>
				<p class="desc">
					<?php
					printf( /* translators: %s - Server API Token link. */
						esc_html__( 'Follow this link to get a Server API Token from Postmark: %s.', 'easy-wp-smtp' ),
						'<a href="https://account.postmarkapp.com/api_tokens" target="_blank" rel="noopener noreferrer">' .
							esc_html__( 'Get Server API Token', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Message Stream ID -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-message_stream" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-message_stream"><?php esc_html_e( 'Message Stream ID', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<input name="easy-wp-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][message_stream]" type="text"
					   value="<?php echo esc_attr( $this->connection_options->get( $this->get_slug(), 'message_stream' ) ); ?>"
					   <?php echo $this->connection_options->is_const_defined( $this->get_slug(), 'message_stream' ) ? 'disabled' : ''; ?>
					   id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-message_stream" spellcheck="false"/>
				<?php
				if ( $this->connection_options->is_const_defined( $this->get_slug(), 'message_stream' ) ) {
					$this->display_const_set_message( 'EASY_WP_SMTP_POSTMARK_MESSAGE_STREAM' );
				}
				?>
				<p class="desc">
					<?php
					printf(
						wp_kses(
						/* translators: %s - URL to Postmark documentation on easywpsmtp.com. */
							__( 'Message Stream ID is <strong>optional</strong>. By default <strong>outbound</strong> (Default Transactional Stream) will be used. More information can be found in our <a href="%s" target="_blank" rel="noopener noreferrer">Postmark documentation</a>.', 'easy-wp-smtp' ),
							[
								'strong' => [],
								'a'      => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-postmark-mailer/#message-stream', 'Postmark documentation - message stream' ) )
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
