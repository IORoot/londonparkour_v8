<?php

namespace EasyWPSMTP\Admin\Pages;

use EasyWPSMTP\Admin\PageAbstract;

/**
 * Class SmartRoutingTab is a placeholder for Pro smart routing feature.
 * Displays product education.
 *
 * @since 2.5.0
 */
class SmartRoutingTab extends PageAbstract {

	/**
	 * Part of the slug of a tab.
	 *
	 * @since 2.5.0
	 *
	 * @var string
	 */
	protected $slug = 'routing';

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

		return esc_html__( 'Smart Routing', 'easy-wp-smtp' );
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

		wp_enqueue_style(
			'easy-wp-smtp-smart-routing',
			easy_wp_smtp()->plugin_url . '/assets/css/smtp-smart-routing.min.css',
			[],
			EasyWPSMTP_PLUGIN_VERSION
		);
	}

	/**
	 * Output HTML of smart routing education.
	 *
	 * @since 2.5.0
	 */
	public function display() {

		$upgrade_button_url = easy_wp_smtp()->get_upgrade_link(
			[
				'medium'  => 'Smart Routing Settings',
				'content' => 'Upgrade to Easy WP SMTP Pro Button Top',
			]
		);
		?>
		<div class="easy-wp-smtp-meta-box">
			<div class="easy-wp-smtp-meta-box__header">
				<div class="easy-wp-smtp-meta-box__heading">
					<?php esc_html_e( 'Smart Routing', 'easy-wp-smtp' ); ?>
				</div>
				<a href="<?php echo esc_url( $upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--sm easy-wp-smtp-btn--green">
					<?php esc_html_e( 'Upgrade to Pro', 'easy-wp-smtp' ); ?>
				</a>
			</div>
			<div class="easy-wp-smtp-meta-box__content">
				<div class="easy-wp-smtp-row">
					<div class="easy-wp-smtp-row__desc">
						<?php
						echo wp_kses(
							sprintf( /* translators: %s - EasyWPSMTP.com page URL. */
								__( 'Route emails through different additional connections depending on your set conditions. Any emails that don\'t meet these conditions will be sent through your Primary Connection. <a href="%s" target="_blank" rel="noopener noreferrer">Upgrade to Easy WP SMTP Pro</a>.', 'easy-wp-smtp' ),
								esc_url( $upgrade_button_url )
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
				<div class="easy-wp-smtp-row">
					<label for="easy-wp-smtp-setting-from_name_force" class="easy-wp-smtp-toggle">
						<input type="checkbox" value="true" id="easy-wp-smtp-setting-from_name_force" disabled/>
						<span class="easy-wp-smtp-toggle__switch"></span>
						<span class="easy-wp-smtp-toggle__label easy-wp-smtp-toggle__label--static">
								<?php esc_html_e( 'Enable Smart Routing', 'easy-wp-smtp' ); ?>
							</span>
					</label>
				</div>
			</div>
		</div>
		<div class="easy-wp-smtp-meta-box">
			<div class="easy-wp-smtp-meta-box__header">
				<div class="easy-wp-smtp-meta-box__heading">
					<?php esc_html_e( 'Conditions', 'easy-wp-smtp' ); ?>
				</div>
			</div>
			<div class="easy-wp-smtp-meta-box__content">
				<div class="easy-wp-smtp-smart-routing-routes">
					<div class="easy-wp-smtp-setting-row easy-wp-smtp-row--inactive easy-wp-smtp-smart-routing-route">
						<div class="easy-wp-smtp-smart-routing-route__header">
							<span><?php esc_html_e( 'Send with', 'easy-wp-smtp' ); ?></span>
							<select class="easy-wp-smtp-smart-routing-route__connection">
								<option><?php esc_html_e( 'WooCommerce Emails (SendLayer)', 'easy-wp-smtp' ); ?></option>
							</select>
							<span><?php esc_html_e( 'if the following conditions are met...', 'easy-wp-smtp' ); ?></span>
							<div class="easy-wp-smtp-smart-routing-route__actions">
								<div class="easy-wp-smtp-smart-routing-route__order">
									<button class="easy-wp-smtp-smart-routing-route__order-btn easy-wp-smtp-smart-routing-route__order-btn--up"></button>
									<button class="easy-wp-smtp-smart-routing-route__order-btn easy-wp-smtp-smart-routing-route__order-btn--down"></button>
								</div>
								<button class="easy-wp-smtp-smart-routing-route__delete">
									<i class="dashicons dashicons-trash"></i>
								</button>
							</div>
						</div>
						<div class="easy-wp-smtp-smart-routing-route__main">
							<div class="easy-wp-smtp-conditional">
								<div class="easy-wp-smtp-conditional__group">
									<table>
										<tbody>
										<tr class="easy-wp-smtp-conditional__row">
											<td class="easy-wp-smtp-conditional__property-col">
												<select>
													<option><?php esc_html_e( 'Subject', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__operator-col">
												<select class="easy-wp-smtp-conditional__operator">
													<option><?php esc_html_e( 'Contains', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__value-col">
												<input type="text" value="<?php esc_html_e( 'Order', 'easy-wp-smtp' ); ?>" class="easy-wp-smt-conditional__value">
											</td>
											<td class="easy-wp-smtp-conditional__actions">
												<button class="easy-wp-smtp-conditional__add-rule easy-wp-smtp-btn easy-wp-smtp-btn easy-wp-smtp-btn--secondary">
													<?php esc_html_e( 'And', 'easy-wp-smtp' ); ?>
												</button>
												<button class="easy-wp-smtp-conditional__delete-rule">
													<i class="dashicons dashicons-trash" aria-hidden="true"></i>
												</button>
											</td>
										</tr>
										<tr class="easy-wp-smtp-conditional__row">
											<td class="easy-wp-smtp-conditional__property-col">
												<select class="easy-wp-smtp-conditional__property">
													<option><?php esc_html_e( 'From Email', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__operator-col">
												<select class="easy-wp-smtp-conditional__operator">
													<option><?php esc_html_e( 'Is', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__value-col">
												<input type="text" value="shop@easywpsmtp.com" class="easy-wp-smtp-conditional__value">
											</td>
											<td class="easy-wp-smtp-conditional__actions">
												<button class="easy-wp-smtp-conditional__add-rule easy-wp-smtp-btn easy-wp-smtp-btn easy-wp-smtp-btn--secondary">
													<?php esc_html_e( 'And', 'easy-wp-smtp' ); ?>
												</button>
												<button class="easy-wp-smtp-conditional__delete-rule">
													<i class="dashicons dashicons-trash" aria-hidden="true"></i>
												</button>
											</td>
										</tr>
										</tbody>
									</table>
									<div class="easy-wp-smtp-conditional__group-delimiter"><?php esc_html_e( 'or', 'easy-wp-smtp' ); ?></div>
								</div>
								<div class="easy-wp-smtp-conditional__group">
									<table>
										<tbody>
										<tr class="easy-wp-smtp-conditional__row">
											<td class="easy-wp-smtp-conditional__property-col">
												<select class="easy-wp-smtp-conditional__property">
													<option><?php esc_html_e( 'From Email', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__operator-col">
												<select class="easy-wp-smtp-conditional__operator">
													<option><?php esc_html_e( 'Is', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__value-col">
												<input type="text" value="returns@easywpsmtp.com" class="easy-wp-smtp-conditional__value">
											</td>
											<td class="easy-wp-smtp-conditional__actions">
												<button class="easy-wp-smtp-conditional__add-rule easy-wp-smtp-btn easy-wp-smtp-btn easy-wp-smtp-btn--secondary">
													<?php esc_html_e( 'And', 'easy-wp-smtp' ); ?>
												</button>
												<button class="easy-wp-smtp-conditional__delete-rule">
													<i class="dashicons dashicons-trash" aria-hidden="true"></i>
												</button>
											</td>
										</tr>
										</tbody>
									</table>
									<div class="easy-wp-smtp-conditional__group-delimiter"><?php esc_html_e( 'or', 'easy-wp-smtp' ); ?></div>
								</div>
								<button class="easy-wp-smtp-conditional__add-group easy-wp-smtp-btn easy-wp-smtp-btn easy-wp-smtp-btn--secondary">
									<?php esc_html_e( 'Add New Group', 'easy-wp-smtp' ); ?>
								</button>
							</div>
						</div>
					</div>
					<div class="easy-wp-smtp-setting-row easy-wp-smtp-row--inactive easy-wp-smtp-smart-routing-route">
						<div class="easy-wp-smtp-smart-routing-route__header">
							<span><?php esc_html_e( 'Send with', 'easy-wp-smtp' ); ?></span>
							<select class="easy-wp-smtp-smart-routing-route__connection">
								<option><?php esc_html_e( 'Contact Emails (SMTP.com)', 'easy-wp-smtp' ); ?></option>
							</select>
							<span><?php esc_html_e( 'if the following conditions are met...', 'easy-wp-smtp' ); ?></span>
							<div class="easy-wp-smtp-smart-routing-route__actions">
								<div class="easy-wp-smtp-smart-routing-route__order">
									<button class="easy-wp-smtp-smart-routing-route__order-btn easy-wp-smtp-smart-routing-route__order-btn--up"></button>
									<button class="easy-wp-smtp-smart-routing-route__order-btn easy-wp-smtp-smart-routing-route__order-btn--down"></button>
								</div>
								<button class="easy-wp-smtp-smart-routing-route__delete">
									<i class="dashicons dashicons-trash"></i>
								</button>
							</div>
						</div>
						<div class="easy-wp-smtp-smart-routing-route__main">
							<div class="easy-wp-smtp-conditional">
								<div class="easy-wp-smtp-conditional__group">
									<table>
										<tbody>
										<tr class="easy-wp-smtp-conditional__row">
											<td class="easy-wp-smtp-conditional__property-col">
												<select>
													<option><?php esc_html_e( 'Initiator', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__operator-col">
												<select class="easy-wp-smtp-conditional__operator">
													<option><?php esc_html_e( 'Is', 'easy-wp-smtp' ); ?></option>
												</select>
											</td>
											<td class="easy-wp-smtp-conditional__value-col">
												<input type="text" value="<?php esc_html_e( 'WPForms', 'easy-wp-smtp' ); ?>" class="easy-wp-smtp-conditional__value">
											</td>
											<td class="easy-wp-smtp-conditional__actions">
												<button class="easy-wp-smtp-conditional__add-rule easy-wp-smtp-btn easy-wp-smtp-btn easy-wp-smtp-btn--secondary">
													<?php esc_html_e( 'And', 'easy-wp-smtp' ); ?>
												</button>
												<button class="easy-wp-smtp-conditional__delete-rule">
													<i class="dashicons dashicons-trash" aria-hidden="true"></i>
												</button>
											</td>
										</tr>
										</tbody>
									</table>
									<div class="easy-wp-smtp-conditional__group-delimiter"><?php esc_html_e( 'or', 'easy-wp-smtp' ); ?></div>
								</div>
								<button class="easy-wp-smtp-conditional__add-group easy-wp-smtp-btn easy-wp-smtp-btn easy-wp-smtp-btn--secondary">
									<?php esc_html_e( 'Add New Group', 'easy-wp-smtp' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="easy-wp-smtp-row">
			<a href="<?php echo esc_url( $upgrade_button_url ); ?>" target="_blank" rel="noopener noreferrer" class="easy-wp-smtp-btn easy-wp-smtp-btn--lg easy-wp-smtp-btn--green">
				<?php esc_html_e( 'Upgrade to Easy WP SMTP Pro', 'easy-wp-smtp' ); ?>
			</a>
		</div>
		<?php
	}
}
