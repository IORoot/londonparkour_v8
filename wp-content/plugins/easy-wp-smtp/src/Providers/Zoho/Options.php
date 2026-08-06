<?php

namespace EasyWPSMTP\Providers\Zoho;

use EasyWPSMTP\Providers\OptionsAbstract;

/**
 * Class Options.
 *
 * @since 2.7.0
 */
class Options extends OptionsAbstract {

	/**
	 * Zoho Options constructor.
	 *
	 * @since 2.7.0
	 */
	public function __construct() {

		parent::__construct(
			[
				'logo_url' => easy_wp_smtp()->assets_url . '/images/providers/zoho.svg',
				'slug'     => 'zoho',
				'title'    => esc_html__( 'Zoho Mail', 'easy-wp-smtp' ),
				'disabled' => true,
			]
		);
	}

	/**
	 * Output the mailer provider options.
	 *
	 * @since 2.7.0
	 */
	public function display_options() {

		?>
		<div class="easy-wp-smtp-setting-row easy-wp-smtp-setting-row-content easy-wp-smtp-clear section-heading">
			<p>
				<?php esc_html_e( 'We\'re sorry, the Zoho Mail mailer is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features.', 'easy-wp-smtp' ); ?>
			</p>
		</div>
		<?php
	}
}
