<?php
defined( 'ABSPATH' ) || exit;
?>
		<div class="cbfs-form__pack" data-cbfs-pack-panel hidden>
			<div class="cbfs-form__pack-status" data-cbfs-pack-status hidden>
				<p class="cbfs-form__pack-summary" data-cbfs-pack-summary></p>
				<label class="cbfs-form__pack-choice">
					<input type="radio" name="cbfs_pack_choice" value="pack" data-cbfs-pack-choice-pack />
					<span class="cbfs-form__pack-choice-text">
						<span class="cbfs-form__pack-choice-line">
							<span
								data-cbfs-pack-choice-label
								data-label-available="<?php echo esc_attr__( 'Use coupon (1 seat)', 'class-bookings-with-stripe-pro' ); ?>"
								data-label-recognised="<?php echo esc_attr__( 'Use coupon', 'class-bookings-with-stripe-pro' ); ?>"
								data-label-unavailable="<?php echo esc_attr__( 'Use coupon (unavailable)', 'class-bookings-with-stripe-pro' ); ?>"
							><?php esc_html_e( 'Use coupon (1 seat)', 'class-bookings-with-stripe-pro' ); ?></span>
							<span class="cbfs-form__pack-code" data-cbfs-pack-choice-code hidden></span>
							<span class="cbfs-form__pack-left" data-cbfs-pack-choice-left data-left-template="<?php echo esc_attr__( '(%s left)', 'class-bookings-with-stripe-pro' ); ?>" hidden></span>
						</span>
						<span class="cbfs-form__pack-message" data-cbfs-pack-message hidden></span>
					</span>
				</label>
				<label class="cbfs-form__pack-choice">
					<input type="radio" name="cbfs_pack_choice" value="pay" checked data-cbfs-pack-choice-pay />
					<span><?php esc_html_e( 'Pay full price', 'class-bookings-with-stripe-pro' ); ?></span>
				</label>
				<button type="button" class="cbfs-form__pack-switch" data-cbfs-pack-switch>
					<?php esc_html_e( 'Enter a different coupon code', 'class-bookings-with-stripe-pro' ); ?>
				</button>
			</div>
			<div class="cbfs-form__pack-attach" data-cbfs-pack-attach>
				<p class="cbfs-form__pack-attach-intro"><?php esc_html_e( 'Have a coupon?', 'class-bookings-with-stripe-pro' ); ?></p>
				<div class="cbfs-form__pack-attach-row">
					<input class="cbfs-form__input" type="text" name="pack_code" data-cbfs-pack-code autocomplete="off" placeholder="<?php esc_attr_e( 'Coupon code', 'class-bookings-with-stripe-pro' ); ?>" />
					<button type="button" class="cbfs-form__pack-attach-btn" data-cbfs-pack-attach-btn>
						<?php esc_html_e( 'Apply', 'class-bookings-with-stripe-pro' ); ?>
					</button>
				</div>
				<p class="cbfs-form__pack-attach-error" data-cbfs-pack-attach-error hidden></p>
				<button type="button" class="cbfs-form__pack-cancel" data-cbfs-pack-cancel hidden>
					<?php esc_html_e( 'Back to coupon options', 'class-bookings-with-stripe-pro' ); ?>
				</button>
			</div>
		</div>
