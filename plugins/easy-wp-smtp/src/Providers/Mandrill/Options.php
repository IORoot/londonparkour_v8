<?php

namespace EasyWPSMTP\Providers\Mandrill;

use EasyWPSMTP\ConnectionInterface;
use EasyWPSMTP\Helpers\UI;
use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.12.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.12.0
	 */
	const SLUG = 'mandrill';

	/**
	 * Options constructor.
	 *
	 * @since 2.12.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = easy_wp_smtp()->get_connections_manager()->get_primary_connection();
		}

		$description = sprintf(
			wp_kses( /* translators: %1$s - URL to Mandrill website. */
				__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">Mandrill</a> is MailChimp’s transactional email API, designed to deliver transactional emails with reliability, scalability, and security. To use the service for sending emails, a paid monthly subscription is required.', 'easy-wp-smtp' ) .
				'<br><br>' .
				/* translators: %2$s - URL to easywpsmtp.com doc. */
				__( 'To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">Mandrill documentation</a>.', 'easy-wp-smtp' ),
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
			'https://mandrillapp.com/',
			esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-mandrill-mailer/', 'Mandrill documentation' ) )
		);

		parent::__construct(
			[
				'logo_url'    => easy_wp_smtp()->assets_url . '/images/providers/mandrill.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'Mandrill', 'easy-wp-smtp' ),
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
	 * @since 2.12.0
	 */
	public function display_options() {

		// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound, WordPress.Security.NonceVerification.Recommended
		$get_api_key_url = 'https://mandrillapp.com/settings/index/';
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
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_MANDRILL_API_KEY' ); ?>
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
					printf(
					/* translators: %s - API key link. */
						esc_html__( 'Follow this link to get an API Key from Mandrill: %s.', 'easy-wp-smtp' ),
						'<a href="' . esc_url( $get_api_key_url ) . '" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get API Key', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
