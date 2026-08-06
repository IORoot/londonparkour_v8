<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\Admin\PageAbstract;
use EasyWPSMTP\WP;

/**
 * AI MCP tab: 1-click installs the Vibe AI MCP plugin and surfaces the read-only Abilities API.
 *
 * @since 2.15.0
 */
class AiMcpTab extends PageAbstract {

	/**
	 * WPVibe plugin basename on wp.org.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	const WPVIBE_BASENAME = 'vibe-ai/vibe-ai.php';

	/**
	 * WPVibe wp.org download URL.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	const WPVIBE_DOWNLOAD_URL = 'https://downloads.wordpress.org/plugin/vibe-ai.zip';

	/**
	 * WPVibe top-level admin page slug.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	const WPVIBE_PAGE_SLUG = 'vibe-ai';

	/**
	 * Tab slug.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	protected $slug = 'ai-mcp';

	/**
	 * Tab display priority.
	 *
	 * @since 2.15.0
	 *
	 * @var int
	 */
	protected $priority = 60;

	/**
	 * Link label of a tab.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'AI MCP', 'easy-wp-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Register tab hooks. Runs only when this tab is the current one.
	 *
	 * @since 2.15.0
	 */
	public function hooks() {

		add_action( 'easy_wp_smtp_admin_area_enqueue_assets', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue the tab's install/activate script and localized strings.
	 *
	 * @since 2.15.0
	 */
	public function enqueue_assets() {

		wp_enqueue_script(
			'easy-wp-smtp-ai-mcp',
			easy_wp_smtp()->assets_url . '/js/smtp-ai-mcp' . WP::asset_min() . '.js',
			[ 'jquery', 'easy-wp-smtp-admin' ],
			EasyWPSMTP_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'easy-wp-smtp-ai-mcp',
			'easy_wp_smtp_ai_mcp',
			[
				'error_text' => esc_html__( 'Something went wrong. Please try again.', 'easy-wp-smtp' ),
			]
		);
	}

	/**
	 * Resolve the WPVibe install state: not installed, installed but inactive, or active.
	 *
	 * @since 2.15.0
	 *
	 * @return string One of 'not_installed', 'installed_inactive', 'active'.
	 */
	private function get_wpvibe_state() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		if ( ! array_key_exists( self::WPVIBE_BASENAME, $plugins ) ) {
			return 'not_installed';
		}

		if ( ! is_plugin_active( self::WPVIBE_BASENAME ) ) {
			return 'installed_inactive';
		}

		return 'active';
	}

	/**
	 * Output the state-dependent WPVibe CTA button.
	 *
	 * @since 2.15.0
	 *
	 * @param string $state        WPVibe state.
	 * @param bool   $can_install  Whether the user can install plugins.
	 * @param bool   $can_activate Whether the user can activate plugins.
	 * @param string $setup_url    WPVibe admin page URL for the active state.
	 */
	private function render_cta_button( $state, $can_install, $can_activate, $setup_url ) {

		if ( $state === 'active' ) {
			?>
			<a
				class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--secondary easy-wp-smtp-ai-mcp-wpvibe-button"
				href="<?php echo esc_url( $setup_url ); ?>"
			><?php esc_html_e( 'Set Up WPVibe', 'easy-wp-smtp' ); ?></a>
			<?php

			return;
		}

		if ( $state === 'installed_inactive' && $can_activate ) {
			?>
			<button
				type="button"
				class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green easy-wp-smtp-ai-mcp-wpvibe-button"
				data-action="activate"
				data-plugin="<?php echo esc_attr( self::WPVIBE_BASENAME ); ?>"
			><?php esc_html_e( 'Activate WPVibe', 'easy-wp-smtp' ); ?></button>
			<?php

			return;
		}

		if ( $state === 'not_installed' && $can_install ) {
			?>
			<button
				type="button"
				class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green easy-wp-smtp-ai-mcp-wpvibe-button"
				data-action="install"
				data-plugin="<?php echo esc_attr( self::WPVIBE_DOWNLOAD_URL ); ?>"
			><?php esc_html_e( 'Install & Activate WPVibe', 'easy-wp-smtp' ); ?></button>
			<?php

			return;
		}

		if ( $state === 'not_installed' ) {
			?>
			<a
				href="https://wordpress.org/plugins/vibe-ai/"
				class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green easy-wp-smtp-ai-mcp-wpvibe-button"
				target="_blank"
				rel="noopener noreferrer"
			><?php esc_html_e( 'Install from WordPress.org', 'easy-wp-smtp' ); ?></a>
			<?php
		}
	}

	/**
	 * Output HTML of the tab.
	 *
	 * @since 2.15.0
	 */
	public function display() {

		$state         = $this->get_wpvibe_state();
		$is_pro        = easy_wp_smtp()->is_pro();
		$can_install   = current_user_can( 'install_plugins' );
		$can_activate  = current_user_can( 'activate_plugins' );
		$wpvibe_setup  = admin_url( 'admin.php?page=' . self::WPVIBE_PAGE_SLUG );
		$pro_badge_url = easy_wp_smtp()->assets_url . '/images/pro-badge-small.svg';

		$docs_url = easy_wp_smtp()->get_utm_url(
			'https://easywpsmtp.com/docs/using-easywpsmtp-with-ai-assistants/',
			[
				'medium'  => 'ai-mcp',
				'content' => 'View Abilities API Documentation',
			]
		);

		// Icon classes are literal so the Tailwind scanner can generate the Iconify utilities.
		$cards = [
			[
				'icon'    => 'esmtp:icon-[fa6-solid--rectangle-list]',
				'title'   => esc_html__( 'Email Logs', 'easy-wp-smtp' ),
				'bullets' => [
					esc_html__( 'Browse and filter logged emails by status, mailer, date, and recipient', 'easy-wp-smtp' ),
					esc_html__( "Get a single email's full details and content", 'easy-wp-smtp' ),
				],
				'pro'     => true,
			],
			[
				'icon'    => 'esmtp:icon-[fa6-solid--chart-column]',
				'title'   => esc_html__( 'Email Stats', 'easy-wp-smtp' ),
				'bullets' => [
					esc_html__( 'See aggregate sending stats for any period or date range', 'easy-wp-smtp' ),
					esc_html__( 'Scope stats to a single mailer and check the success rate', 'easy-wp-smtp' ),
				],
				'pro'     => true,
			],
			[
				'icon'    => 'esmtp:icon-[fa6-solid--bug]',
				'title'   => esc_html__( 'Debug Events', 'easy-wp-smtp' ),
				'bullets' => [
					esc_html__( 'List recorded send errors and debug entries', 'easy-wp-smtp' ),
				],
				'pro'     => false,
			],
		];

		?>
		<div class="easy-wp-smtp-ai-mcp">

			<section class="easy-wp-smtp-ai-mcp-hero">

				<div class="easy-wp-smtp-ai-mcp-hero-copy">

					<p class="easy-wp-smtp-ai-mcp-eyebrow"><?php esc_html_e( 'WordPress Abilities API + Easy WP SMTP', 'easy-wp-smtp' ); ?></p>
					<h2 class="easy-wp-smtp-ai-mcp-title"><?php esc_html_e( 'Use Easy WP SMTP With Your Favorite AI', 'easy-wp-smtp' ); ?></h2>
					<p class="easy-wp-smtp-ai-mcp-lede">
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s - WPVibe.ai inline link. */
								__( 'Connect your WordPress site and Easy WP SMTP to AI assistants like Claude, ChatGPT, Cursor, and more. Ask them to review your email logs, sending stats, and debug events in plain English. No copy-pasting, no exports. Connect them with the free %s plugin.', 'easy-wp-smtp' ),
								sprintf(
									'<a href="%s" target="_blank" rel="noopener noreferrer"><strong>WPVibe.ai</strong></a>',
									esc_url( 'https://wpvibe.ai/?utm_source=easywpsmtpplugin&utm_medium=link&utm_campaign=ai-mcp-page' )
								)
							),
							[
								'a'      => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
								'strong' => [],
							]
						);
						?>
					</p>

					<div class="easy-wp-smtp-ai-mcp-cta-row">
						<?php $this->render_cta_button( $state, $can_install, $can_activate, $wpvibe_setup ); ?>
					</div>

					<?php if ( $state === 'not_installed' && ! $can_install ) : ?>
						<p class="easy-wp-smtp-ai-mcp-install-note">
							<?php esc_html_e( 'Your site is configured to disallow plugin installation from the dashboard.', 'easy-wp-smtp' ); ?>
						</p>
					<?php endif; ?>

				</div>

				<img
					class="easy-wp-smtp-ai-mcp-hero-illustration"
					src="<?php echo esc_url( easy_wp_smtp()->assets_url . '/images/ai-mcp/hero-illustration.svg' ); ?>"
					alt=""
					role="presentation"
				>

			</section>

			<section class="easy-wp-smtp-ai-mcp-capabilities">

				<header class="easy-wp-smtp-ai-mcp-capabilities-head">
					<h3 class="easy-wp-smtp-ai-mcp-capabilities-title"><?php esc_html_e( 'Everything Easy WP SMTP Can Do With AI', 'easy-wp-smtp' ); ?></h3>
					<a
						class="easy-wp-smtp-ai-mcp-docs-link"
						href="<?php echo esc_url( $docs_url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<span class="easy-wp-smtp-ai-mcp-docs-text"><?php esc_html_e( 'View Abilities API Documentation', 'easy-wp-smtp' ); ?></span>
						<span class="easy-wp-smtp-ai-mcp-docs-arrow esmtp:icon-[fa6-solid--arrow-right]" aria-hidden="true"></span>
					</a>
				</header>

				<div class="easy-wp-smtp-ai-mcp-cards">
					<?php foreach ( $cards as $card ) : ?>
						<article class="easy-wp-smtp-ai-mcp-card">

							<header class="easy-wp-smtp-ai-mcp-card-head">
								<span class="easy-wp-smtp-ai-mcp-card-icon" aria-hidden="true">
									<span class="<?php echo esc_attr( $card['icon'] ); ?>"></span>
								</span>
								<h4 class="easy-wp-smtp-ai-mcp-card-title"><?php echo esc_html( $card['title'] ); ?></h4>
							</header>

							<ul class="easy-wp-smtp-ai-mcp-card-bullets">
								<?php foreach ( $card['bullets'] as $bullet ) : ?>
									<li>
										<span class="easy-wp-smtp-ai-mcp-card-dot" aria-hidden="true"></span>
										<span class="easy-wp-smtp-ai-mcp-card-bullet-text"><?php echo esc_html( $bullet ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>

							<?php if ( $card['pro'] && ! $is_pro ) : ?>
								<img
									class="easy-wp-smtp-ai-mcp-pro-badge"
									src="<?php echo esc_url( $pro_badge_url ); ?>"
									alt="<?php esc_attr_e( 'Pro feature', 'easy-wp-smtp' ); ?>"
								>
							<?php endif; ?>

						</article>
					<?php endforeach; ?>
				</div>

			</section>

		</div>
		<?php
	}
}
