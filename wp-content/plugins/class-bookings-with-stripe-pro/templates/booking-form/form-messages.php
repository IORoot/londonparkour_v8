<?php
defined( 'ABSPATH' ) || exit;
?>
		<p class="cbfs-form__error" role="alert" hidden></p>
		<?php if ( '' !== trim( (string) ( $view->labels['redirect_hint'] ?? '' ) ) ) : ?>
		<p class="cbfs-form__hint"><?php echo esc_html( $view->labels['redirect_hint'] ); ?></p>
		<?php endif; ?>
