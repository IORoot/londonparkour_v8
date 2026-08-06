<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\Admin\PageAbstract;

/**
 * Class AdditionalConnectionsTab is a placeholder for Pro additional connections feature.
 * Displays product education.
 *
 * @since 2.5.0
 */
class AdditionalConnectionsTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $slug = 'connections';

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 *
	 * @param PageAbstract $parent_page Parent page object.
	 */
	public function __construct( $parent_page = null ) {

		parent::__construct( $parent_page );

		if ( easy_wp_smtp()->get_admin()->get_current_tab() === $this->slug && ! easy_wp_smtp()->is_pro() ) {
			$this->hooks();
		}
	}

	/**
	 * Link label of a tab.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Additional Connections', 'easy-wp-smtp' );
	}

	/**
	 * Register hooks.
	 *
	 * @since 2.5.0
	 */
	public function hooks() {

		add_action( 'easy_wp_smtp_admin_area_enqueue_assets', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue required JS and CSS.
	 *
	 * @since 2.5.0
	 */
	public function enqueue_assets() {

		wp_enqueue_style( 'easy-wp-smtp-admin-lity' );
		wp_enqueue_script( 'easy-wp-smtp-admin-lity' );
	}

	/**
	 * Output HTML of additional connections' education.
	 *
	 * @since 2.5.0
	 */
	public function display() {

		$button_upgrade_link = add_query_arg(
			[ 'discount' => 'LITEUPGRADE' ],
			easy_wp_smtp()->get_upgrade_link(
				[
					'medium'  => 'additional-connections',
					'content' => 'Upgrade to Pro Button',
				]
			)
		);
		$link_upgrade_link   = add_query_arg(
			[ 'discount' => 'LITEUPGRADE' ],
			easy_wp_smtp()->get_upgrade_link(
				[
					'medium'  => 'additional-connections',
					'content' => 'upgrade-to-easy-wp-smtp-pro-text-link',
				]
			)
		);
		?>

		<div class="easy-wp-smtp-meta-box">
			<div class="easy-wp-smtp-meta-box__header">
				<div class="easy-wp-smtp-meta-box__heading">
					<?php echo esc_html( $this->get_title() ); ?>
				</div>

				<a href="<?php echo esc_url( $button_upgrade_link ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--sm easy-wp-smtp-btn--green">
					<?php esc_html_e( 'Upgrade to Pro', 'easy-wp-smtp' ); ?>
				</a>
			</div>
			<div class="easy-wp-smtp-meta-box__content">
				<div class="easy-wp-smtp-row">
					<div class="easy-wp-smtp-row__desc">
						<?php
						echo wp_kses(
							sprintf( /* translators: %s - EasyWPSMTP.com page URL. */
								__( 'Set up additional connections to ensure a backup for your Primary Connection or to enable Smart Routing. <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade to Easy WP SMTP Pro</a> to start taking advantage of additional connections.', 'easy-wp-smtp' ),
								esc_url( $link_upgrade_link )
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

				<?php
				$this->display_education_screenshots();
				$this->display_education_features_list();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output HTML of additional connections' education screenshots.
	 *
	 * @since 2.5.0
	 */
	protected function display_education_screenshots() {

		$assets_url  = easy_wp_smtp()->assets_url . '/images/additional-connections/';
		$screenshots = [
			[
				'url'           => $assets_url . 'screenshot-01.png',
				'url_thumbnail' => $assets_url . 'thumbnail-01.png',
				'title'         => __( 'Backup Connection', 'easy-wp-smtp' ),
			],
			[
				'url'           => $assets_url . 'screenshot-02.png',
				'url_thumbnail' => $assets_url . 'thumbnail-02.png',
				'title'         => __( 'Smart Routing', 'easy-wp-smtp' ),
			],
		];
		?>
		<div class="easy-wp-smtp-row">
			<div class="easy-wp-smtp-product-education-screenshots easy-wp-smtp-product-education-screenshots--two">
				<?php foreach ( $screenshots as $screenshot ) : ?>
					<div>
						<a href="<?php echo esc_url( $screenshot['url'] ); ?>" data-lity data-lity-desc="<?php echo esc_attr( $screenshot['title'] ); ?>">
							<img src="<?php echo esc_url( $screenshot['url_thumbnail'] ); ?>" alt="<?php esc_attr( $screenshot['title'] ); ?>">
						</a>
						<span><?php echo esc_html( $screenshot['title'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output HTML of additional connections' education features list.
	 *
	 * @since 2.5.0
	 */
	protected function display_education_features_list() {

		?>
		<div class="easy-wp-smtp-row easy-wp-smtp-row--has-bg-color easy-wp-smtp-product-education-cta-row">
			<div class="easy-wp-smtp-row__heading easy-wp-smtp-settings-heading">
				<?php esc_html_e( 'Using additional connections, you are able to:', 'easy-wp-smtp' ); ?>
			</div>
			<div class="easy-wp-smtp-product-education-list">
				<ul>
					<li><?php esc_html_e( 'Configure a Backup Connection', 'easy-wp-smtp' ); ?></li>
				</ul>
				<ul>
					<li><?php esc_html_e( 'Utilize different mailers for specific tasks', 'easy-wp-smtp' ); ?></li>
				</ul>
				<ul>
					<li><?php esc_html_e( 'Implement advanced routing rules', 'easy-wp-smtp' ); ?></li>
				</ul>
			</div>

			<?php $this->display_action_button(); ?>
		</div>
		<?php
	}

	/**
	 * Output the action button.
	 *
	 * @since 2.6.0
	 */
	protected function display_action_button() {

		$button_upgrade_link = add_query_arg(
			[ 'discount' => 'LITEUPGRADE' ],
			easy_wp_smtp()->get_upgrade_link(
				[
					'medium'  => 'additional-connections',
					'content' => 'Upgrade to Pro Button',
				]
			)
		);

		?>
		<a href="<?php echo esc_url( $button_upgrade_link ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green">
			<?php esc_html_e( 'Upgrade to Easy WP SMTP Pro', 'easy-wp-smtp' ); ?>
		</a>
		<?php
	}
}
