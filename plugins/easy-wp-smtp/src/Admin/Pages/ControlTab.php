<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\Admin\PageAbstract;
use EasyWPSMTP\WP;

/**
 * Class ControlTab is a placeholder for Pro Email Control tab settings.
 * Displays an upsell.
 *
 * @since 2.6.0
 */
class ControlTab extends PageAbstract {

	/**
	 * Slug of a tab.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	protected $slug = 'control';

	/**
	 * Link label of a tab.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'Email Controls', 'easy-wp-smtp' );
	}

	/**
	 * Title of a tab.
	 *
	 * @since 2.6.0
	 *
	 * @return string
	 */
	public function get_title() {

		return $this->get_label();
	}

	/**
	 * Get the list of all available emails that we can manage.
	 *
	 * @see   https://github.com/johnbillion/wp_mail Apr 12th 2019.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	public static function get_controls() {

		return [
			'comments'         => [
				'title'  => esc_html__( 'Comments', 'easy-wp-smtp' ),
				'emails' => [
					'dis_comments_awaiting_moderation' => [
						'label' => esc_html__( 'Awaiting Moderation', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Comment is awaiting moderation. Sent to the site admin and post author if they can edit comments.', 'easy-wp-smtp' ),
					],
					'dis_comments_published'           => [
						'label' => esc_html__( 'Published', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Comment has been published. Sent to the post author.', 'easy-wp-smtp' ),
					],
				],
			],
			'admin_email'      => [
				'title'  => esc_html__( 'Change of Admin Email', 'easy-wp-smtp' ),
				'emails' => [
					'dis_admin_email_attempt'         => [
						'label' => esc_html__( 'Site Admin Email Change Attempt', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Change of site admin email address was attempted. Sent to the proposed new email address.', 'easy-wp-smtp' ),
					],
					'dis_admin_email_changed'         => [
						'label' => esc_html__( 'Site Admin Email Changed', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Site admin email address was changed. Sent to the old site admin email address.', 'easy-wp-smtp' ),
					],
					'dis_admin_email_network_attempt' => [
						'label' => esc_html__( 'Network Admin Email Change Attempt', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Change of network admin email address was attempted. Sent to the proposed new email address.', 'easy-wp-smtp' ),
					],
					'dis_admin_email_network_changed' => [
						'label' => esc_html__( 'Network Admin Email Changed', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Network admin email address was changed. Sent to the old network admin email address.', 'easy-wp-smtp' ),
					],
				],
			],
			'user_details'     => [
				'title'  => esc_html__( 'Change of User Email or Password', 'easy-wp-smtp' ),
				'emails' => [
					'dis_user_details_password_reset_request' => [
						'label' => esc_html__( 'Reset Password Request', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User requested a password reset via "Lost your password?". Sent to the user.', 'easy-wp-smtp' ),
					],
					'dis_user_details_password_reset'         => [
						'label' => esc_html__( 'Password Reset Successfully', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User reset their password from the password reset link. Sent to the site admin.', 'easy-wp-smtp' ),
					],
					'dis_user_details_password_changed'       => [
						'label' => esc_html__( 'Password Changed', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User changed their password. Sent to the user.', 'easy-wp-smtp' ),
					],
					'dis_user_details_email_change_attempt'   => [
						'label' => esc_html__( 'Email Change Attempt', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User attempted to change their email address. Sent to the proposed new email address.', 'easy-wp-smtp' ),
					],
					'dis_user_details_email_changed'          => [
						'label' => esc_html__( 'Email Changed', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User changed their email address. Sent to the user.', 'easy-wp-smtp' ),
					],
				],
			],
			'personal_data'    => [
				'title'  => esc_html__( 'Personal Data Requests', 'easy-wp-smtp' ),
				'emails' => [
					'dis_personal_data_user_confirmed'   => [
						'label' => esc_html__( 'User Confirmed Export / Erasure Request', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User clicked a confirmation link in personal data export or erasure request email. Sent to the site or network admin.', 'easy-wp-smtp' ),
					],
					'dis_personal_data_erased_data'      => [
						'label' => esc_html__( 'Admin Erased Data', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Site admin clicked "Erase Personal Data" button next to a confirmed data erasure request. Sent to the requester email address.', 'easy-wp-smtp' ),
					],
					'dis_personal_data_sent_export_link' => [
						'label' => esc_html__( 'Admin Sent Link to Export Data', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Site admin clicked "Email Data" button next to a confirmed data export request. Sent to the requester email address.', 'easy-wp-smtp' ) . '<br>' .
						           '<strong>' . esc_html__( 'Disabling this option will block users from being able to export their personal data, as they will not receive an email with a link.', 'easy-wp-smtp' ) . '</strong>',
					],
				],
			],
			'auto_updates'     => [
				'title'  => esc_html__( 'Automatic Updates', 'easy-wp-smtp' ),
				'emails' => [
					'dis_auto_updates_plugin_status' => [
						'label' => esc_html__( 'Plugin Status', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Completion or failure of a background automatic plugin update. Sent to the site or network admin.', 'easy-wp-smtp' ),
					],
					'dis_auto_updates_theme_status'  => [
						'label' => esc_html__( 'Theme Status', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Completion or failure of a background automatic theme update. Sent to the site or network admin.', 'easy-wp-smtp' ),
					],
					'dis_auto_updates_status'        => [
						'label' => esc_html__( 'WP Core Status', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Completion or failure of a background automatic core update. Sent to the site or network admin.', 'easy-wp-smtp' ),
					],
					'dis_auto_updates_full_log'      => [
						'label' => esc_html__( 'Full Log', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'Full log of background update results which includes information about WordPress core, plugins, themes, and translations updates. Only sent when you are using a development version of WordPress. Sent to the site or network admin.', 'easy-wp-smtp' ),
					],
				],
			],
			'new_user'         => [
				'title'  => esc_html__( 'New User', 'easy-wp-smtp' ),
				'emails' => [
					'dis_new_user_created_to_admin'        => [
						'label' => esc_html__( 'Created (Admin)', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'A new user was created. Sent to the site admin.', 'easy-wp-smtp' ),
					],
					'dis_new_user_created_to_user'         => [
						'label' => esc_html__( 'Created (User)', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'A new user was created. Sent to the new user.', 'easy-wp-smtp' ),
					],
					'dis_new_user_invited_to_site_network' => [
						'label' => esc_html__( 'Invited To Site', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'A new user was invited to a site from Users -> Add New -> Add New User. Sent to the invited user.', 'easy-wp-smtp' ),
					],
					'dis_new_user_created_network'         => [
						'label' => esc_html__( 'Created On Site', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'A new user account was created. Sent to Network Admin.', 'easy-wp-smtp' ),
					],
					'dis_new_user_added_activated_network' => [
						'label' => esc_html__( 'Added / Activated on Site', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'A user has been added, or their account activation has been successful. Sent to the user, that has been added/activated.', 'easy-wp-smtp' ),
					],
				],
			],
			'network_new_site' => [
				'title'  => esc_html__( 'New Site', 'easy-wp-smtp' ),
				'emails' => [
					'dis_new_site_user_registered_site_network'                  => [
						'label' => esc_html__( 'User Created Site', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User registered for a new site. Sent to the site admin.', 'easy-wp-smtp' ),
					],
					'dis_new_site_user_added_activated_site_in_network_to_admin' => [
						'label' => esc_html__( 'Network Admin: User Activated / Added Site', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User activated their new site, or site was added from Network Admin -> Sites -> Add New. Sent to Network Admin.', 'easy-wp-smtp' ),
					],
					'dis_new_site_user_added_activated_site_in_network_to_site'  => [
						'label' => esc_html__( 'Site Admin: Activated / Added Site', 'easy-wp-smtp' ),
						'desc'  => esc_html__( 'User activated their new site, or site was added from Network Admin -> Sites -> Add New. Sent to Site Admin.', 'easy-wp-smtp' ),
					],
				],
			],
		];
	}

	/**
	 * Output HTML of the email controls settings preview.
	 *
	 * @since 2.6.0
	 */
	public function display() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$top_upgrade_button_url    = add_query_arg(
			[ 'discount' => 'LITEUPGRADE' ],
			easy_wp_smtp()->get_upgrade_link(
				[
					'medium'  => 'Email Controls',
					'content' => 'Upgrade to Easy WP SMTP Pro Button Top',
				]
			)
		);
		$upgrade_link_url          = add_query_arg(
			[ 'discount' => 'LITEUPGRADE' ],
			easy_wp_smtp()->get_upgrade_link(
				[
					'medium'  => 'Email Controls',
					'content' => 'Upgrade to Easy WP SMTP Pro Link',
				]
			)
		);
		$bottom_upgrade_button_url = add_query_arg(
			[ 'discount' => 'LITEUPGRADE' ],
			easy_wp_smtp()->get_upgrade_link(
				[
					'medium'  => 'Email Controls',
					'content' => 'Upgrade to Easy WP SMTP Pro Button Bottom',
				]
			)
		);
		?>

		<div class="easy-wp-smtp-email-controls-product-education">
			<div class="easy-wp-smtp-meta-box">
				<div class="easy-wp-smtp-meta-box__header">
					<div class="easy-wp-smtp-meta-box__heading">
						<?php esc_html_e( 'Email Controls', 'easy-wp-smtp' ); ?>
					</div>
					<a href="<?php echo esc_url( $top_upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--sm easy-wp-smtp-btn--green">
						<?php esc_html_e( 'Upgrade to Pro', 'easy-wp-smtp' ); ?>
					</a>
				</div>
				<div class="easy-wp-smtp-meta-box__content">
					<div class="easy-wp-smtp-row">
						<div class="easy-wp-smtp-row__desc">
							<?php
							echo wp_kses(
								sprintf( /* translators: %s - EasyWPSMTP.com page URL. */
									__( 'With email controls, you can manage the automatic notifications sent by your WordPress site. A simple switch lets you reduce inbox clutter and focus on the alerts that truly matter. Easily turn off emails related to comments, account changes, updates, registrations, and data requests. <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade to Easy WP SMTP Pro</a>.', 'easy-wp-smtp' ),
									esc_url( $upgrade_link_url )
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
				</div>
			</div>

			<?php
			foreach ( static::get_controls() as $section_id => $section ) :
				if ( empty( $section['emails'] ) ) {
					continue;
				}

				if ( $this->is_it_for_multisite( sanitize_key( $section_id ) ) && ! WP::use_global_plugin_settings() ) {
					continue;
				}
				?>

				<div class="easy-wp-smtp-meta-box">
					<div class="easy-wp-smtp-meta-box__header">
						<div class="easy-wp-smtp-meta-box__heading">
							<?php echo esc_html( $section['title'] ); ?>
						</div>
					</div>
					<div class="easy-wp-smtp-meta-box__content">
						<?php
						foreach ( $section['emails'] as $email_id => $email ) :
							$email_id = sanitize_key( $email_id );

							if ( empty( $email_id ) || empty( $email['label'] ) ) {
								continue;
							}

							if ( $this->is_it_for_multisite( sanitize_key( $email_id ) ) && ! WP::use_global_plugin_settings() ) {
								continue;
							}
							?>
							<div class="easy-wp-smtp-row easy-wp-smtp-setting-row easy-wp-smtp-row--inactive">
								<div class="easy-wp-smtp-setting-row__label">
									<label>
										<?php echo esc_html( $email['label'] ); ?>
									</label>
								</div>
								<div class="easy-wp-smtp-setting-row__field">
									<label class="easy-wp-smtp-toggle">
										<input type="checkbox" checked/>
										<span class="easy-wp-smtp-toggle__switch"></span>
										<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--checked"><?php esc_html_e( 'ON', 'easy-wp-smtp' ); ?></span>
										<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--unchecked"><?php esc_html_e( 'OFF', 'easy-wp-smtp' ); ?></span>
									</label>
									<?php if ( ! empty( $email['desc'] ) ) : ?>
										<p class="desc">
											<?php echo $email['desc']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</p>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>

			<a href="<?php echo esc_url( $bottom_upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green">
				<?php esc_html_e( 'Upgrade to Easy WP SMTP Pro', 'easy-wp-smtp' ); ?>
			</a>
		</div>

		<?php
	}

	/**
	 * Whether this key dedicated to MultiSite environment.
	 *
	 * @since 2.6.0
	 *
	 * @param string $key Email unique key.
	 *
	 * @return bool
	 */
	protected function is_it_for_multisite( $key ) {

		return strpos( $key, 'network' ) !== false;
	}

	/**
	 * Not used as we display an upsell.
	 *
	 * @since 2.6.0
	 *
	 * @param array $data Post data specific for the plugin.
	 */
	public function process_post( $data ) {}
}
