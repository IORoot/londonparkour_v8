<?php

namespace EasyWPSMTP\Providers\SparkPost;

use EasyWPSMTP\ConnectionInterface;
use EasyWPSMTP\Helpers\UI;
use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.4.0
 */
class Options extends OptionsAbstract {

	/**
	 * Mailer slug.
	 *
	 * @since 2.4.0
	 */
	const SLUG = 'sparkpost';

	/**
	 * Options constructor.
	 *
	 * @since 2.4.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 */
	public function __construct( $connection = null ) {

		$description = sprintf(
			wp_kses( /* translators: %1$s - URL to SparkPost website, %2$s - URL to easywpsmtp.com doc. */
				__( '<p><a href="%1$s" target="_blank" rel="noopener noreferrer">SparkPost</a> is a transactional email provider, designed to provide high-speed, reliable, and secure email delivery. You can get started with the free test account that lets you send up to 500 emails per month.</p><p>To get started, read our <a href="%2$s" target="_blank" rel="noopener noreferrer">SparkPost documentation</a>.</p>', 'easy-wp-smtp' ),
				[
					'p' => true,
					'a' => [
						'href'   => true,
						'rel'    => true,
						'target' => true,
					],
				]
			),
			'https://www.sparkpost.com/',
			esc_url( easy_wp_smtp()->get_utm_url( 'https://easywpsmtp.com/docs/setting-up-the-sparkpost-mailer/', 'SparkPost documentation' ) )
		);

		parent::__construct(
			[
				'logo_url'    => easy_wp_smtp()->assets_url . '/images/providers/sparkpost.svg',
				'slug'        => self::SLUG,
				'title'       => esc_html__( 'SparkPost', 'easy-wp-smtp' ),
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
	 * @since 2.4.0
	 */
	public function display_options() {

		// Do not display options if PHP version is not correct.
		if ( ! $this->is_php_correct() ) {
			$this->display_php_warning();
			return;
		}
		?>

		<!-- API Key -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-api_key" class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-setting-row--text">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"><?php esc_html_e( 'API Key', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">
				<?php if ( $this->connection_options->is_const_defined( $this->get_slug(), 'api_key' ) ) : ?>
					<input type="text" disabled value="****************************************"
								 id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-api_key"
					/>
					<?php $this->display_const_set_message( 'EASY_WP_SMTP_SPARKPOST_API_KEY' ); ?>
				<?php else : ?>
					<?php
					$slug  = $this->get_slug();
					$value = $this->connection_options->get( $slug, 'api_key' );

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
					$url = 'sparkpost.com';
					$url = $this->connection_options->get( $this->get_slug(), 'region' ) === 'EU' ? 'eu.' . $url : $url;
					$url = 'https://app.' . $url . '/account/api-keys';

					printf( /* translators: %s - API Key link. */
						esc_html__( 'Follow this link to get an API Key from SparkPost: %s.', 'easy-wp-smtp' ),
						'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' .
							esc_html__( 'Get API Key', 'easy-wp-smtp' ) .
						'</a>'
					);
					?>
				</p>
			</div>
		</div>

		<!-- Region -->
		<div id="easy-wp-smtp-setting-row-<?php echo esc_attr( $this->get_slug() ); ?>-region" class="easy-wp-smtp-row easy-wp-smtp-setting-row">
			<div class="easy-wp-smtp-setting-row__label">
				<label for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region"><?php esc_html_e( 'Region', 'easy-wp-smtp' ); ?></label>
			</div>
			<div class="easy-wp-smtp-setting-row__field">

				<div class="easy-wp-smtp-radio-group">
					<label class="easy-wp-smtp-radio" for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-us">
						<input type="radio" id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-us"
									 name="easy-wp-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][region]" value="US"
							<?php echo $this->connection_options->is_const_defined( $this->get_slug(), 'region' ) ? 'disabled' : ''; ?>
							<?php checked( 'US', $this->connection_options->get( $this->get_slug(), 'region' ) ); ?>
						/>
						<span class="easy-wp-smtp-radio__checkmark"></span>
						<span class="easy-wp-smtp-radio__label"><?php esc_html_e( 'US', 'easy-wp-smtp' ); ?></span>
					</label>

					<label class="easy-wp-smtp-radio"
								 for="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-eu">
						<input type="radio" id="easy-wp-smtp-setting-<?php echo esc_attr( $this->get_slug() ); ?>-region-eu"
									 name="easy-wp-smtp[<?php echo esc_attr( $this->get_slug() ); ?>][region]" value="EU"
							<?php echo $this->connection_options->is_const_defined( $this->get_slug(), 'region' ) ? 'disabled' : ''; ?>
							<?php checked( 'EU', $this->connection_options->get( $this->get_slug(), 'region' ) ); ?>
						/>
						<span class="easy-wp-smtp-radio__checkmark"></span>
						<span class="easy-wp-smtp-radio__label"><?php esc_html_e( 'EU', 'easy-wp-smtp' ); ?></span>
					</label>
				</div>

				<?php
				if ( $this->connection_options->is_const_defined( $this->get_slug(), 'region' ) ) {
					$this->display_const_set_message( 'EASY_WP_SMTP_SPARKPOST_REGION' );
				}
				?>
				<p class="desc">
					<?php esc_html_e( 'Select your SparkPost account region.', 'easy-wp-smtp' ); ?>
					<?php
					printf(
						wp_kses(
						/* translators: %s - URL to Mailgun.com page. */
							__( '<a href="%s" rel="" target="_blank">More information</a> on SparkPost.', 'easy-wp-smtp' ),
							[
								'a' => [
									'href'   => [],
									'rel'    => [],
									'target' => [],
								],
							]
						),
						'https://www.sparkpost.com/docs/getting-started/getting-started-sparkpost'
					);
					?>
				</p>
			</div>
		</div>

		<?php
	}
}
