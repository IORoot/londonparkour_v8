<?php
defined( 'ABSPATH' ) || exit;

$meta_bits = $view->get_meta_bits();
?>
		<header class="cbfs-form__hero">
			<div class="cbfs-form__hero-main">
				<h3 class="cbfs-form__title"><?php echo esc_html( $view->get_title() ); ?></h3>
				<?php if ( ! empty( $meta_bits ) ) : ?>
					<p class="cbfs-form__meta"><?php echo esc_html( $view->get_meta_text() ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $view->show_price_badge() ) : ?>
				<div class="cbfs-form__price-badge">
					<span class="cbfs-form__price-badge-label"><?php esc_html_e( 'From', CLASBOWPRO_TEXT_DOMAIN ); ?></span>
					<span class="cbfs-form__price-badge-value"><?php echo esc_html( \IOROOT_STRIPE_BOOKINGS_PRO\Helpers::format_price( (float) $view->class_data['price'] ) ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $view->class_data['description'] ) ) : ?>
				<div class="cbfs-form__description"><?php echo wp_kses_post( $view->get_description() ); ?></div>
			<?php endif; ?>
		</header>
