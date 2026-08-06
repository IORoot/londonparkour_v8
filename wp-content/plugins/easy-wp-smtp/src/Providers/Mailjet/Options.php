<?php

namespace EasyWPSMTP\Providers\Mailjet;

use EasyWPSMTP\Helpers\UI;
use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.8.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.8.0
	 */
	const SLUG = 'mailjet';

	/**
	 * Options constructor.
	 *
	 * @since 2.8.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = easy_wp_smtp()->get_connections_manager()->get_primary_connection();
		}

		$description = sprintf(
			wp_kses( /* translators: %1$s - URL to Mailjet.com site. */
				__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">Mailjet</a> is a cloud-based email solution that allows businesses to send marketing and transactional emails. With features like automated email campaigns, real-time reporting, and responsive templates, it’s an ideal platform for email communication. As a new user, you can send up to 200 emails per day without a credit card.', 'easy-wp-smtp' ) .
				'<br><br>' .
				/* translators: %2$s - URL to easywpsmtp.com doc. */
				__( 'To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">Mailjet documentation</a>.', 'easy-wp-smtp' ),
				[
					'strong' => true,
					'br'     => true,
					'a'      => [
						'href'   => true,
						'rel'    => true,
						'target' => true,
					],
				]
			),
			'https://www.mailjet.com/',
			esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-mailjet-mailer/', 'Mailjet documentation' ) )
		);

		parent::__construct(
			[
				'logo_url'    => easy_wp_smtp()->assets_url . '/images/providers/mailjet.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'Mailjet', 'easy-wp-smtp' ),
				'php'         => '5.6',
				'description' => $description,
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
	 * @since 2.8.0
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();

			return;
		}
		?>

		<!-- API Key -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-api_key" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row-text easy-wp-smtp-clear">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'API Key', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<?php if ( $this->connection_options->is_const_defined( $this->get_slug(), 'api_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
					       id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_MAILJET_API_KEY' ); ?>
				<?php else : ?>
					<?php
					$slug  = $this->get_slug();
					$value = $this->connection_options->get( $this->get_slug(), 'api_key' );

					UI::hidden_password_field(
						[
							'name'       => "easy-wp-smtp[{$slug}][api_key]",
							'id'         => "easy-wp-smtp-setting-{$slug}-api_key",
							'value'      => $value,
							'clear_text' => esc_html__( 'Remove API Key', 'easy-wp-smtp' ),
						]
					);
					?>
				<?php endif; ?>

				<p class="desc">
					<?php
					printf( /* translators: %s - link to get an API Key. */
						esc_html__( 'Follow this link to get the API key from Mailjet: %s.', 'easy-wp-smtp' ),
						'<a href="https://app.mailjet.com/account/apikeys" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'API Key Management', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Secret Key -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-secret_key" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row-text easy-wp-smtp-clear">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-secret_key"><?php esc_html_e( 'Secret Key', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<?php if ( $this->connection_options->is_const_defined( $this->get_slug(), 'secret_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
					       id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-secret_key"
					/>
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_MAILJET_SECRET_KEY' ); ?>
				<?php else : ?>
					<?php
					$slug  = $this->get_slug();
					$value = $this->connection_options->get( $this->get_slug(), 'secret_key' );

					UI::hidden_password_field(
						[
							'name'       => "easy-wp-smtp[{$slug}][secret_key]",
							'id'         => "easy-wp-smtp-setting-{$slug}-secret_key",
							'value'      => $value,
							'clear_text' => esc_html__( 'Remove Secret Key', 'easy-wp-smtp' ),
						]
					);
					?>
				<?php endif; ?>

				<p class="desc">
					<?php
					printf( /* translators: %s - link to get an API Key. */
						esc_html__( 'Follow this link to get the Secret key from Mailjet: %s.', 'easy-wp-smtp' ),
						'<a href="https://app.mailjet.com/account/apikeys" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'API Key Management', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
