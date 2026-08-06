<?php
/**
 * Intro / welcome panel for Class Bookings with Stripe → Settings (admin).
 *
 * Edit this file to change the HTML shown above the settings tabs. You can
 * also override it in your theme as:
 *   class-bookings-with-stripe/admin-settings-intro.php
 *
 * This file is included in an admin context; output is not escaped by the
 * plugin — use only trusted markup.
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

defined( 'ABSPATH' ) || exit;

$clasbpro_settings_url = admin_url( 'edit.php?post_type=' . \IOROOT_STRIPE_BOOKINGS_PRO\CPT::CLASS_PT . '&page=clasbowi-settings' );
$clasbpro_reports_url  = admin_url( 'edit.php?post_type=' . \IOROOT_STRIPE_BOOKINGS_PRO\CPT::CLASS_PT . '&page=clasbpro-reports' );
$clasbpro_new_class_url = admin_url( 'post-new.php?post_type=' . \IOROOT_STRIPE_BOOKINGS_PRO\CPT::CLASS_PT );
?>
<div class="clasbpro-welcome" id="clasbpro-welcome-panel" role="region" aria-labelledby="clasbpro-welcome-heading">
	<div class="clasbpro-welcome__toolbar">
		<p class="clasbpro-welcome__toolbar-summary">
			<?php esc_html_e( 'Class Bookings with Stripe overview is hidden. Expand to see getting started steps and shortcuts.', 'class-bookings-with-stripe' ); ?>
		</p>
		<button type="button" class="button clasbpro-welcome__panel-toggle" id="clasbpro-welcome-toggle" aria-expanded="true" aria-controls="clasbpro-welcome-expandable" aria-label="<?php esc_attr_e( 'Hide overview panel', 'class-bookings-with-stripe' ); ?>" data-clasbpro-aria-expanded="<?php esc_attr_e( 'Hide overview panel', 'class-bookings-with-stripe' ); ?>" data-clasbpro-aria-collapsed="<?php esc_attr_e( 'Show overview panel', 'class-bookings-with-stripe' ); ?>">
			<span class="clasbpro-welcome__panel-toggle-label clasbpro-welcome__panel-toggle-label--expanded"><?php esc_html_e( 'Hide panel', 'class-bookings-with-stripe' ); ?></span>
			<span class="clasbpro-welcome__panel-toggle-label clasbpro-welcome__panel-toggle-label--collapsed"><?php esc_html_e( 'Show panel', 'class-bookings-with-stripe' ); ?></span>
			<span class="clasbpro-welcome__panel-toggle-chevron dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
		</button>
	</div>
	<div id="clasbpro-welcome-expandable" class="clasbpro-welcome__expandable" aria-hidden="false">
	<div class="clasbpro-welcome__bg" aria-hidden="true">
		<span class="clasbpro-welcome__blob clasbpro-welcome__blob--1"></span>
		<span class="clasbpro-welcome__blob clasbpro-welcome__blob--2"></span>
		<span class="clasbpro-welcome__blob clasbpro-welcome__blob--3"></span>
		<span class="clasbpro-welcome__grid-dots"></span>
	</div>

	<div class="clasbpro-welcome__shell">
		<div class="clasbpro-welcome__layout">
			<div class="clasbpro-welcome__hero">
				<div class="clasbpro-welcome__badges">
					<span class="clasbpro-welcome__pill clasbpro-welcome__pill--brand">
						<span class="clasbpro-welcome__pill-dot" aria-hidden="true"></span>
						<?php esc_html_e( 'IORoot', 'class-bookings-with-stripe' ); ?>
					</span>
					<span class="clasbpro-welcome__pill">
						<?php esc_html_e( 'Getting started', 'class-bookings-with-stripe' ); ?>
					</span>
				</div>

				<div class="clasbpro-welcome__title-row">
					<div class="clasbpro-welcome__logo-wrap">
						<img
							class="clasbpro-welcome__logo"
							src="<?php echo esc_url( CLASBOWPRO_URL . 'assets/logo_plugin.svg' ); ?>"
							width="88"
							height="74"
							alt=""
							decoding="async"
							loading="lazy"
						/>
					</div>
					<h2 id="clasbpro-welcome-heading" class="clasbpro-welcome__title">
						<span class="clasbpro-welcome__title-line"><?php esc_html_e( 'Welcome to', 'class-bookings-with-stripe' ); ?></span>
						<span class="clasbpro-welcome__title-accent"><?php esc_html_e( 'Class Bookings with Stripe', 'class-bookings-with-stripe' ); ?></span>
					</h2>
				</div>

				<p class="clasbpro-welcome__lede">
					<?php esc_html_e( 'Work through the steps below once. When you are ready for day-to-day tasks, use the shortcuts on the right.', 'class-bookings-with-stripe' ); ?>
				</p>

				<ol class="clasbpro-welcome__timeline">
					<li class="clasbpro-welcome__timeline-item">
						<span class="clasbpro-welcome__timeline-marker" aria-hidden="true">1</span>
						<div class="clasbpro-welcome__timeline-body">
							<strong><?php esc_html_e( 'Connect Stripe keys and webhook', 'class-bookings-with-stripe' ); ?></strong>
							<span><?php esc_html_e( 'Open the Stripe tab, add your publishable and secret keys, set test or live mode, and register the webhook signing secret from your Stripe dashboard.', 'class-bookings-with-stripe' ); ?></span>
						</div>
					</li>
					<li class="clasbpro-welcome__timeline-item">
						<span class="clasbpro-welcome__timeline-marker" aria-hidden="true">2</span>
						<div class="clasbpro-welcome__timeline-body">
							<strong><?php esc_html_e( 'Add custom fields and embed the booking form', 'class-bookings-with-stripe' ); ?></strong>
							<span><?php esc_html_e( 'Optional extras live under Form extras (ACF). Then place the Elementor block or shortcode on the page where customers should book.', 'class-bookings-with-stripe' ); ?></span>
						</div>
					</li>
					<li class="clasbpro-welcome__timeline-item">
						<span class="clasbpro-welcome__timeline-marker" aria-hidden="true">3</span>
						<div class="clasbpro-welcome__timeline-body">
							<strong><?php esc_html_e( 'Set up emails', 'class-bookings-with-stripe' ); ?></strong>
							<span><?php esc_html_e( 'Use the Emails tab for subjects and bodies, merge tags, and admin notifications (emails always use WordPress wp_mail()).', 'class-bookings-with-stripe' ); ?></span>
						</div>
					</li>
				</ol>
			</div>

			<aside class="clasbpro-welcome__aside" aria-label="<?php esc_attr_e( 'Quick reference', 'class-bookings-with-stripe' ); ?>">
				<div class="clasbpro-welcome__bento clasbpro-welcome__bento--stack">
					<div class="clasbpro-welcome__tile clasbpro-welcome__tile--wide clasbpro-welcome__tile--cta-row">
						<div class="clasbpro-welcome__tile-icon-wrap clasbpro-welcome__tile-icon-wrap--violet">
							<span class="clasbpro-welcome__tile-icon dashicons dashicons-email" aria-hidden="true"></span>
						</div>
						<div class="clasbpro-welcome__tile-copy">
							<strong><?php esc_html_e( 'Email Templates', 'class-bookings-with-stripe' ); ?></strong>
							<p><?php esc_html_e( 'Edit customer and admin messages, merge tags, and admin notification address. Mail is sent with WordPress wp_mail().', 'class-bookings-with-stripe' ); ?></p>
						</div>
						<div class="clasbpro-welcome__tile-aside">
							<a class="button clasbpro-welcome__tile-action clasbpro-welcome__tile-action--violet" href="<?php echo esc_url( $clasbpro_settings_url . '#clasbpro-tab-field_clasbpro_tab_emails' ); ?>">
								<?php esc_html_e( 'Open Emails tab', 'class-bookings-with-stripe' ); ?>
							</a>
						</div>
					</div>
					<div class="clasbpro-welcome__tile clasbpro-welcome__tile--wide clasbpro-welcome__tile--cta-row">
						<div class="clasbpro-welcome__tile-icon-wrap">
							<span class="clasbpro-welcome__tile-icon dashicons dashicons-chart-area" aria-hidden="true"></span>
						</div>
						<div class="clasbpro-welcome__tile-copy">
							<strong><?php esc_html_e( 'Reports', 'class-bookings-with-stripe' ); ?></strong>
							<p><?php esc_html_e( 'Historic trends, upcoming attendance, and guest lists by class.', 'class-bookings-with-stripe' ); ?></p>
						</div>
						<div class="clasbpro-welcome__tile-aside">
							<a class="button clasbpro-welcome__tile-action clasbpro-welcome__tile-action--teal" href="<?php echo esc_url( $clasbpro_reports_url ); ?>">
								<?php esc_html_e( 'Open reports', 'class-bookings-with-stripe' ); ?>
							</a>
						</div>
					</div>
					<div class="clasbpro-welcome__tile clasbpro-welcome__tile--wide clasbpro-welcome__tile--cta-row">
						<div class="clasbpro-welcome__tile-icon-wrap clasbpro-welcome__tile-icon-wrap--amber">
							<span class="clasbpro-welcome__tile-icon dashicons dashicons-calendar-alt" aria-hidden="true"></span>
						</div>
						<div class="clasbpro-welcome__tile-copy">
							<strong><?php esc_html_e( 'Weekly Classes / One-off Events / External Links', 'class-bookings-with-stripe' ); ?></strong>
							<p><?php esc_html_e( 'Add a Class, set schedule or single dates, price, capacity, or an external booking URL.', 'class-bookings-with-stripe' ); ?></p>
						</div>
						<div class="clasbpro-welcome__tile-aside">
							<a class="button clasbpro-welcome__tile-action clasbpro-welcome__tile-action--amber" href="<?php echo esc_url( $clasbpro_new_class_url ); ?>">
								<?php esc_html_e( 'Add new class', 'class-bookings-with-stripe' ); ?>
							</a>
						</div>
					</div>
					<div class="clasbpro-welcome__tile clasbpro-welcome__tile--wide clasbpro-welcome__tile--cta-row">
						<div class="clasbpro-welcome__tile-icon-wrap clasbpro-welcome__tile-icon-wrap--emerald">
							<span class="clasbpro-welcome__tile-icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
						</div>
						<div class="clasbpro-welcome__tile-copy">
							<strong><?php esc_html_e( 'Extend with ACF', 'class-bookings-with-stripe' ); ?></strong>
							<p><?php esc_html_e( 'Form Extras: waiver, Mailchimp opt-in, and custom ACF fields on the booking form.', 'class-bookings-with-stripe' ); ?></p>
						</div>
						<div class="clasbpro-welcome__tile-aside">
							<a class="button clasbpro-welcome__tile-action clasbpro-welcome__tile-action--emerald" href="<?php echo esc_url( $clasbpro_settings_url . '#clasbpro-tab-field_clasbpro_tab_pages' ); ?>">
								<?php esc_html_e( 'Open Form extras tab', 'class-bookings-with-stripe' ); ?>
							</a>
						</div>
					</div>
				</div>
			</aside>
		</div>

	</div>
	</div>
</div>
