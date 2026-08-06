<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\Admin\PageAbstract;

/**
 * Get Pro tab — a product-education page shown to Lite users only.
 *
 * @since 2.15.0
 */
class GetProTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	protected $slug = 'get-pro';

	/**
	 * Tab priority.
	 *
	 * @since 2.15.0
	 *
	 * @var int
	 */
	protected $priority = 100;

	/**
	 * Link label of a tab.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Get Pro', 'easy-wp-smtp' );
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
	 * Tab content.
	 *
	 * @since 2.15.0
	 */
	public function display() {

		?>
		<div class="esmtp-get-pro-tab esmtp:mt-[30px] esmtp:bg-white esmtp:rounded-[4px] esmtp:border esmtp:border-[#dadadf] esmtp:shadow-soft">

			<?php $this->display_hero(); ?>
			<?php $this->display_features(); ?>
			<?php $this->display_comparison(); ?>

		</div>
		<?php
	}

	/**
	 * Hero: headline, supporting copy, primary CTA and the comparison anchor link.
	 *
	 * @since 2.15.0
	 */
	private function display_hero() {

		?>
		<div class="esmtp:flex esmtp:flex-wrap esmtp:items-center esmtp:justify-between esmtp:gap-[40px] esmtp:px-[40px] esmtp:py-[40px]">

			<div class="esmtp:flex esmtp:flex-col esmtp:gap-[20px] esmtp:basis-[554px] esmtp:grow-0 esmtp:min-w-[300px]">
				<h1>
					<?php esc_html_e( 'Take complete control of your WordPress emails', 'easy-wp-smtp' ); ?>
				</h1>

				<p>
					<?php esc_html_e( 'Get email logs, delivery tracking, backup connections, and instant failure alerts so you catch issues before your customers do.', 'easy-wp-smtp' ); ?>
				</p>

				<div class="esmtp:flex esmtp:flex-col esmtp:gap-[20px]">
					<div class="esmtp:flex esmtp:flex-col esmtp:gap-[10px] esmtp:items-start">
						<?php $this->display_upgrade_button( 'Hero' ); ?>
						<?php $this->display_discount_badge(); ?>
					</div>

					<a href="#easy-wp-smtp-get-pro-comparison" class="esmtp:text-[13px] esmtp:text-link! esmtp:underline">
						<?php esc_html_e( 'Compare Lite vs Pro features', 'easy-wp-smtp' ); ?>
					</a>
				</div>
			</div>

			<div class="esmtp:shrink-0">
				<img
					src="<?php echo esc_url( easy_wp_smtp()->assets_url . '/images/education/get-pro-tab/hero.svg' ); ?>"
					alt=""
					width="400"
					height="214"
					class="esmtp:w-[400px] esmtp:max-w-full esmtp:h-auto"
				>
			</div>

		</div>
		<?php
	}

	/**
	 * Feature grid: six product highlights on a pale section, closed by a CTA.
	 *
	 * @since 2.15.0
	 */
	private function display_features() {

		?>
		<div class="esmtp:bg-[#fcf9e8] esmtp:rounded-[4px] esmtp:px-[40px] esmtp:py-[40px]">
			<div class="esmtp:flex esmtp:flex-col esmtp:gap-[30px]">

				<h2 class="esmtp:text-center">
					<?php esc_html_e( 'Features you\'ll love with Pro', 'easy-wp-smtp' ); ?>
				</h2>

				<div class="esmtp:grid esmtp:grid-cols-3 esmtp:gap-x-[20px] esmtp:gap-y-[30px] esmtp:max-tablet:grid-cols-1">
					<?php foreach ( $this->get_feature_cards() as $card ) : ?>
						<div class="esmtp:flex esmtp:flex-col esmtp:gap-[20px] esmtp:bg-white esmtp:rounded-[8px] esmtp:border esmtp:border-[#dcdcde] esmtp:shadow-subtle esmtp:overflow-hidden">
							<img
								src="<?php echo esc_url( easy_wp_smtp()->assets_url . '/images/education/get-pro-tab/' . $card['image'] . '.svg' ); ?>"
								alt=""
								class="esmtp:w-full esmtp:h-auto"
							>
							<div class="esmtp:flex esmtp:flex-col esmtp:gap-[8px] esmtp:px-[36px] esmtp:pb-[32px]">
								<h3>
									<?php echo esc_html( $card['title'] ); ?>
								</h3>
								<p class="esmtp:text-[14px]!">
									<?php echo esc_html( $card['description'] ); ?>
								</p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="esmtp:flex esmtp:flex-col esmtp:gap-[8px] esmtp:items-center">
					<?php $this->display_upgrade_button( 'Features Grid' ); ?>
					<?php $this->display_discount_badge(); ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Lite vs Pro comparison: heading, twelve-row table and the closing CTA.
	 *
	 * @since 2.15.0
	 */
	private function display_comparison() {

		?>
		<div class="esmtp:px-[40px] esmtp:py-[40px]">
			<div class="esmtp:flex esmtp:flex-col esmtp:gap-[30px] esmtp:items-center">

				<div class="esmtp:flex esmtp:flex-col esmtp:gap-[20px] esmtp:items-center esmtp:text-center">
					<h2>
						<?php esc_html_e( 'Lite vs Pro: What\'s the Difference?', 'easy-wp-smtp' ); ?>
					</h2>
					<p class="esmtp:max-w-[467px]">
						<?php esc_html_e( 'Get the most out of Easy WP SMTP by upgrading to Pro and unlocking all of the powerful features.', 'easy-wp-smtp' ); ?>
					</p>
				</div>

				<div id="easy-wp-smtp-get-pro-comparison" class="esmtp:w-full esmtp:overflow-x-auto">
					<div class="esmtp:min-w-[1010px] esmtp:rounded-[8px] esmtp:border esmtp:border-[#dcdcde] esmtp:overflow-hidden">
						<?php $this->display_comparison_header(); ?>
						<?php foreach ( $this->get_comparison_rows() as $row ) : ?>
							<?php $this->display_comparison_row( $row ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="esmtp:flex esmtp:flex-col esmtp:gap-[8px] esmtp:items-center">
					<?php $this->display_upgrade_button( 'Bottom' ); ?>
					<?php $this->display_discount_badge(); ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Comparison table header row.
	 *
	 * @since 2.15.0
	 */
	private function display_comparison_header() {

		$cell  = 'esmtp:flex esmtp:items-start esmtp:p-[20px] esmtp:bg-[#f3f4f6] esmtp:border-b esmtp:border-l esmtp:border-[#f3f4f6]';
		$label = 'esmtp:m-0! esmtp:text-[20px]! esmtp:font-bold! esmtp:leading-[28px]! esmtp:tracking-[0.25px] esmtp:text-primary!';
		?>
		<div class="esmtp:flex esmtp:items-stretch">
			<div class="<?php echo esc_attr( $cell ); ?> esmtp:w-[310px] esmtp:shrink-0">
				<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Feature', 'easy-wp-smtp' ); ?></p>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> esmtp:w-[400px] esmtp:shrink-0">
				<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Lite', 'easy-wp-smtp' ); ?></p>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> esmtp:border-r esmtp:flex-1 esmtp:min-w-[300px]">
				<p class="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Pro', 'easy-wp-smtp' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * A single comparison table row (feature label + Lite cell + Pro cell).
	 *
	 * @since 2.15.0
	 *
	 * @param array $row Row data: feature label plus `lite` and `pro` cell arrays.
	 */
	private function display_comparison_row( $row ) {

		$cell = 'esmtp:flex esmtp:items-start esmtp:p-[20px] esmtp:bg-white esmtp:border-b esmtp:border-l esmtp:border-[#f3f4f6]';
		?>
		<div class="esmtp:flex esmtp:items-stretch">
			<div class="<?php echo esc_attr( $cell ); ?> esmtp:w-[310px] esmtp:shrink-0">
				<p class="esmtp:m-0! esmtp:text-[16px]! esmtp:font-medium! esmtp:leading-[24px]! esmtp:tracking-[0.25px] esmtp:text-secondary">
					<?php echo esc_html( $row['feature'] ); ?>
				</p>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> esmtp:w-[400px] esmtp:shrink-0">
				<?php $this->display_comparison_cell( $row['lite'] ); ?>
			</div>
			<div class="<?php echo esc_attr( $cell ); ?> esmtp:border-r esmtp:flex-1 esmtp:min-w-[300px]">
				<?php $this->display_comparison_cell( $row['pro'] ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Inner content of a comparison cell: a status icon plus its copy. The
	 * lead sentence is emphasized; any follow-up detail renders below it.
	 *
	 * @since 2.15.0
	 *
	 * @param array $cell Cell data: `status`, `lead` and optional `rest`.
	 */
	private function display_comparison_cell( $cell ) {

		?>
		<div class="esmtp:flex esmtp:gap-[8px] esmtp:items-start">
			<?php $this->display_status_icon( $cell['status'] ); ?>
			<p class="esmtp:m-0! esmtp:text-[16px]! esmtp:leading-[24px]! esmtp:tracking-[0.25px] esmtp:text-secondary">
				<strong class="esmtp:font-bold!"><?php echo esc_html( $cell['lead'] ); ?></strong>
				<?php if ( ! empty( $cell['rest'] ) ) : ?>
					<br><?php echo esc_html( $cell['rest'] ); ?>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render a comparison status icon for the given availability state.
	 *
	 * @since 2.15.0
	 *
	 * @param string $status One of `full`, `partial`, `none`.
	 */
	private function display_status_icon( $status ) {

		$icons = [
			'full'    => [
				'file'  => 'status-full',
				'label' => esc_attr__( 'Available', 'easy-wp-smtp' ),
			],
			'partial' => [
				'file'  => 'status-partial',
				'label' => esc_attr__( 'Partially available', 'easy-wp-smtp' ),
			],
			'none'    => [
				'file'  => 'status-none',
				'label' => esc_attr__( 'Not available', 'easy-wp-smtp' ),
			],
		];

		if ( ! isset( $icons[ $status ] ) ) {
			return;
		}

		$icon = $icons[ $status ];
		?>
		<img
			src="<?php echo esc_url( easy_wp_smtp()->assets_url . '/images/education/get-pro-tab/' . $icon['file'] . '.svg' ); ?>"
			alt="<?php echo esc_attr( $icon['label'] ); ?>"
			width="24"
			height="24"
			class="esmtp:w-[24px] esmtp:h-[24px] esmtp:shrink-0"
		>
		<?php
	}

	/**
	 * Primary "Upgrade to Pro" button, carrying the Lite upgrade discount.
	 *
	 * @since 2.15.0
	 *
	 * @param string $content UTM content tag identifying the button placement.
	 */
	private function display_upgrade_button( $content ) {

		$url = add_query_arg(
			'discount',
			'SMTPLITEUPGRADE',
			// phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			easy_wp_smtp()->get_upgrade_link( [ 'medium' => 'get-pro-tab', 'content' => $content ] )
		);
		?>
		<a
			href="<?php echo esc_url( $url ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--primary esmtp:w-[300px] esmtp:rounded-[4px]"
		>
			<?php esc_html_e( 'Upgrade to Pro', 'easy-wp-smtp' ); ?>
		</a>
		<?php
	}

	/**
	 * Discount badge accompanying every upgrade button.
	 *
	 * @since 2.15.0
	 */
	private function display_discount_badge() {

		?>
		<div class="esmtp:flex esmtp:items-center esmtp:gap-[5px]">
			<span aria-hidden="true" class="esmtp:icon-[custom--badge-percent] esmtp:text-success esmtp:w-[16px] esmtp:h-[16px] esmtp:shrink-0"></span>
			<span class="esmtp:text-[14px]! esmtp:leading-[20px]!">
				<span class="esmtp:font-semibold esmtp:text-success"><?php esc_html_e( '$50 OFF', 'easy-wp-smtp' ); ?></span>
				<span class="esmtp:text-tertiary"><?php esc_html_e( 'for Easy WP SMTP Lite users', 'easy-wp-smtp' ); ?></span>
			</span>
		</div>
		<?php
	}

	/**
	 * The six feature cards shown above the comparison table.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	private function get_feature_cards() {

		return [
			[
				'image'       => 'card-email-logs',
				'title'       => esc_html__( 'Email Logs', 'easy-wp-smtp' ),
				'description' => esc_html__( 'Save details about every email sent from your WordPress site.', 'easy-wp-smtp' ),
			],
			[
				'image'       => 'card-email-failure-alerts',
				'title'       => esc_html__( 'Email Failure Alerts', 'easy-wp-smtp' ),
				'description' => esc_html__( 'Receive immediate alerts for email failures to quickly address issues.', 'easy-wp-smtp' ),
			],
			[
				'image'       => 'card-backup-connections',
				'title'       => esc_html__( 'Backup Connections', 'easy-wp-smtp' ),
				'description' => esc_html__( 'Set up a secondary email provider in case your primary provider fails.', 'easy-wp-smtp' ),
			],
			[
				'image'       => 'card-one-click-mailer-setups',
				'title'       => esc_html__( 'One-Click Mailer Setups', 'easy-wp-smtp' ),
				'description' => esc_html__( 'An easy and secure way to configure your Gmail and Outlook mailers.', 'easy-wp-smtp' ),
			],
			[
				'image'       => 'card-smart-routing',
				'title'       => esc_html__( 'Smart Routing', 'easy-wp-smtp' ),
				'description' => esc_html__( 'Use conditional logic to send emails through different providers.', 'easy-wp-smtp' ),
			],
			[
				'image'       => 'card-advanced-mailers',
				'title'       => esc_html__( 'Advanced mailers', 'easy-wp-smtp' ),
				'description' => esc_html__( 'Connect with top providers like Amazon SES, Microsoft 365 / Outlook, and Zoho Mail.', 'easy-wp-smtp' ),
			],
		];
	}

	/**
	 * Twelve-row Lite vs Pro feature comparison.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	private function get_comparison_rows() {

		$not_available = [
			'status' => 'none',
			'lead'   => esc_html__( 'Not available', 'easy-wp-smtp' ),
			'rest'   => '',
		];

		return [
			[
				'feature' => esc_html__( 'Improved Email Deliverability', 'easy-wp-smtp' ),
				'lite'    => [
					'status' => 'partial',
					'lead'   => esc_html__( 'Connect your WordPress site to any of the following SMTP providers:', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'SendLayer, SMTP.com, Brevo, Google Workspace / Gmail, Mailgun, Postmark, SendGrid, SMTP2GO, Sparkpost, and any other SMTP provider using your SMTP credentials.', 'easy-wp-smtp' ),
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Connect your WordPress site to any of our Lite SMTP providers, as well as:', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'Amazon SES, Microsoft 365 / Outlook, and Zoho Mail.', 'easy-wp-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Weekly Email Summaries', 'easy-wp-smtp' ),
				'lite'    => [
					'status' => 'partial',
					'lead'   => esc_html__( 'Receive a basic report of your total emails sent including a summary of the previous week\'s sent emails.', 'easy-wp-smtp' ),
					'rest'   => '',
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Receive a detailed report of your total and previous week\'s metrics for emails sent, failed, and delivered, plus statistics for your top 5 popular emails.', 'easy-wp-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Email Error Tracking', 'easy-wp-smtp' ),
				'lite'    => [
					'status' => 'full',
					'lead'   => esc_html__( 'View email sending errors in your WordPress dashboard.', 'easy-wp-smtp' ),
					'rest'   => '',
				],
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'View email sending errors in your WordPress dashboard.', 'easy-wp-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Google Workspace', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Access your Google Workspace immediately with Gmail One Click Setup', 'easy-wp-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Outlook', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Access Outlook immediately with Outlook One Click Setup', 'easy-wp-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Email Logging', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'View detailed email logs in your WordPress dashboard.', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'Includes delivery status, email content, attachments, source, and technical details, plus the option to resend the email.', 'easy-wp-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Instant Email Alerts', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Receive an alert via your preferred channel whenever an email fails to send.', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'Email, Slack, SMS via Twilio, Microsoft Teams, Discord, or custom webhooks.', 'easy-wp-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Backup Connections', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Connect to multiple SMTP providers.', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'Choose one primary connection, then select another connection as a backup. If an email fails to send via your primary connection, it will automatically be resent using your backup connection.', 'easy-wp-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Manage Email Notifications', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Choose which default WordPress email notifications your site sends.', 'easy-wp-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Open & Click Tracking', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Monitor email open and click-through rates.', 'easy-wp-smtp' ),
					'rest'   => '',
				],
			],
			[
				'feature' => esc_html__( 'Email Reports', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'View advanced email reports in your WordPress dashboard.', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'Includes total number of emails sent, emails delivered, failed emails, opened emails, links clicked.', 'easy-wp-smtp' ),
				],
			],
			[
				'feature' => esc_html__( 'Smart Routing', 'easy-wp-smtp' ),
				'lite'    => $not_available,
				'pro'     => [
					'status' => 'full',
					'lead'   => esc_html__( 'Send emails through more than one SMTP provider.', 'easy-wp-smtp' ),
					'rest'   => esc_html__( 'Connect Easy WP SMTP to multiple providers, then route emails through your preferred provider based on custom conditional logic rules.', 'easy-wp-smtp' ),
				],
			],
		];
	}
}
