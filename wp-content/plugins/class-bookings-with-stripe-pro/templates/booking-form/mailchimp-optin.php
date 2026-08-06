<?php
defined( 'ABSPATH' ) || exit;
?>
		<div class="cbfs-form__row cbfs-form__row--check">
			<label class="cbfs-form__check">
				<input class="cbfs-form__check-input" type="checkbox" name="mailchimp_opt_in" value="1">
				<span class="cbfs-form__check-label"><?php echo wp_kses_post( $view->labels['mailchimp_optin_label'] ); ?></span>
			</label>
		</div>
