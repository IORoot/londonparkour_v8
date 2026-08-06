<?php
defined( 'ABSPATH' ) || exit;
?>
	<a class="cbfs-form__button cbfs-form__button--link" href="<?php echo esc_url( $view->external_link_url ); ?>" target="_blank" rel="noopener noreferrer">
		<?php echo esc_html( $view->labels['external_button'] ); ?>
	</a>
	<p class="cbfs-form__hint"><?php echo esc_html( $view->labels['external_hint'] ); ?></p>
