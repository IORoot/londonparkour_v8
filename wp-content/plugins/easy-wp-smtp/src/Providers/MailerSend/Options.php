<?php

namespace EasyWPSMTP\Providers\MailerSend;

use EasyWPSMTP\ConnectionInterface;
use EasyWPSMTP\Helpers\UI;
use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.11.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.11.0
	 *
	 * @var string
	 */
	const SLUG = 'mailersend';

	/**
	 * Options constructor.
	 *
	 * @since 2.11.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		if ( is_null( $connection ) ) {
			$connection = easy_wp_smtp()->get_connections_manager()->get_primary_connection();
		}

		$description = sprintf(
			wp_kses(
			/* translators: %1$s - URL to mailersend.com; %2$s - URL to MailerSend documentation on easywpsmtp.com. */
				__( '<a href="%1$s" target="_blank" rel="noopener noreferrer">MailerSend</a> is a trusted provider of transactional email services, offering a solid set of features. Users get 12,000 free emails each month, with cost-effective options for larger volumes. Its modern API and high deliverability rates make it perfect for WordPress users.<br><br>To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">MailerSend documentation</a>.', 'easy-wp-smtp' ),
				[
					'strong' => [],
					'br'     => [],
					'a'      => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			esc_url( 'https://mailersend.com/' ),
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-mailersend-mailer/', 'MailerSend Documentation' ) )
		);

		parent::__construct(
			[
				'logo_url'    => easy_wp_smtp()->assets_url . '/images/providers/mailersend.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'MailerSend', 'easy-wp-smtp' ),
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
	 * @since 2.11.0
	 */
	public function display_options() {

		$get_api_key_url = 'https://app.mailersend.com/api-tokens';
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
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_MAILERSEND_API_KEY' ); ?>
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
						esc_html__( 'Follow this link to get an API Key from MailerSend: %s.', 'easy-wp-smtp' ),
						'<a href="' . esc_url( $get_api_key_url ) . '" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'Get API Key', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Professional Plan Features -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-has_pro_plan" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row-checkbox-toggle easy-wp-smtp-clear">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-has_pro_plan">
					<?php esc_html_e( 'Professional Plan', 'easy-wp-smtp' ); ?>
				</label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<?php
				UI::toggle(
					[
						'name'     => 'easy-wp-smtp[' . $this->get_slug() . '][has_pro_plan]',
						'id'       => 'easy-wp-smtp-setting-' . $this->get_slug() . '-has_pro_plan',
						'value'    => 'true',
						'checked'  => (bool) $this->connection_options->get( $this->get_slug(), 'has_pro_plan' ),
						'disabled' => $this->connection_options->is_const_defined( $this->get_slug(), 'has_pro_plan' ),
					]
				);
				?>
				<p class="desc">
					<?php
					printf(
					/* translators: %s - MailerSend pricing page URL. */
						esc_html__( 'Activate if you have a Professional or higher plan with MailerSend. This allows you to use custom headers. For more information about MailerSend plans, check their %s.', 'easy-wp-smtp' ),
						'<a href="https://www.mailersend.com/pricing" target="_blank" rel="noopener noreferrer">' .
						esc_html__( 'pricing page', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
