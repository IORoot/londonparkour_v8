<?php
/**
 * Enquiries — Contact page 01 Enquiries: form + reach aside.
 *
 * Ported from src/stories/Pages/Contact/Contact.js (`data-component="contact-enquiries"`).
 *
 * Form fields mirror Contact.js FIELD_DEFS + MESSAGE textarea — not editable
 * in ACF. Submits via admin-post.php → app/includes/contact.php.
 *
 * @param string $args['title']
 * @param string $args['lead']
 * @param string $args['note']
 * @param string $args['success_message']
 * @param string $args['error_message']
 * @param array  $args['reach'] Aside panel group.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_locations_inline = function_exists( 'lp_contact_locations_inline' ) ? lp_contact_locations_inline() : '';
$lp_default_reach_rows = array(
	array(
		'label' => 'EMAIL',
		'value' => 'hello@londonparkour.com',
	),
	array(
		'label' => 'LOCATIONS',
		'value' => $lp_locations_inline ? $lp_locations_inline : 'Vauxhall · Old Street · Kilburn Park',
	),
	array(
		'label' => 'HOURS',
		'value' => 'Wed, Sat, Sun · 09:00–20:00',
	),
);

$lp_title            = (string) ( $args['title'] ?? 'Send us a message' );
$lp_lead             = (string) ( $args['lead'] ?? "Tell us what you're training for and we'll point you in the right direction." );
$lp_note             = (string) ( $args['note'] ?? 'We reply within 1 working day.' );
$lp_success_message  = (string) ( $args['success_message'] ?? 'Thanks — your message is on its way. We usually reply within one working day.' );
$lp_error_message    = (string) ( $args['error_message'] ?? 'Something went wrong sending your message. Please try again or email us direct.' );

$lp_reach       = is_array( $args['reach'] ?? null ) ? $args['reach'] : array();
$lp_reach_title = (string) ( $lp_reach['title'] ?? 'REACH US NOW' );
$lp_reach_spots = (string) ( $lp_reach['spots_left'] ?? 'OPEN' );
$lp_reach_cta   = (string) ( $lp_reach['cta_label'] ?? 'EMAIL US' );
$lp_reach_href  = (string) ( $lp_reach['cta_href'] ?? 'mailto:hello@londonparkour.com' );
$lp_reach_note  = (string) ( $lp_reach['note'] ?? 'We reply within 1 working day. Email is the fastest way to reach us.' );
$lp_reach_rows  = $lp_default_reach_rows;

if ( false !== stripos( $lp_reach_cta, 'studio' ) || false !== stripos( $lp_reach_cta, 'call' ) || 0 === strpos( $lp_reach_href, 'tel:' ) ) {
	$lp_reach_cta  = 'EMAIL US';
	$lp_reach_href = 'mailto:hello@londonparkour.com';
}
if ( '' === $lp_reach_href ) {
	$lp_reach_href = 'mailto:hello@londonparkour.com';
}
if ( false !== stripos( $lp_reach_note, 'phone' ) ) {
	$lp_reach_note = 'We reply within 1 working day. Email is the fastest way to reach us.';
}

$lp_field_defs = array(
	array(
		'key'         => 'name',
		'label'       => 'NAME',
		'type'        => 'text',
		'placeholder' => 'Your full name',
		'required'    => true,
	),
	array(
		'key'         => 'email',
		'label'       => 'EMAIL',
		'type'        => 'email',
		'placeholder' => 'you@email.com',
		'required'    => true,
	),
	array(
		'key'         => 'subject',
		'label'       => 'SUBJECT',
		'type'        => 'text',
		'placeholder' => "What's this about?",
		'required'    => false,
	),
);

$lp_contact_status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full bg-secondary', $lp_spacing ); ?>" data-component="contact-enquiries" data-surface="board"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="px-6 lg:px-16 pt-scale-xl pb-scale-2xl">
		<div class="flex flex-col lg:flex-row gap-12 lg:gap-20 items-start">
			<div class="w-full lg:max-w-[852px] flex flex-col gap-10">
				<div class="flex flex-col gap-4">
					<h2 class="font-heading text-[32px] font-semibold tracking-[-0.6px] text-neutral-content m-0"><?php echo esc_html( $lp_title ); ?></h2>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_lead ); ?></p>
				</div>

				<?php if ( 'sent' === $lp_contact_status ) : ?>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-primary m-0" role="status"><?php echo esc_html( $lp_success_message ); ?></p>
				<?php elseif ( 'error' === $lp_contact_status ) : ?>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-error m-0" role="alert"><?php echo esc_html( $lp_error_message ); ?></p>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" aria-label="<?php echo esc_attr__( 'Contact enquiry form', 'londonparkour_v8' ); ?>">
					<?php wp_nonce_field( 'lp_contact', 'lp_contact_nonce' ); ?>
					<input type="hidden" name="action" value="lp_contact" />
					<label class="sr-only" for="lp-company"><?php esc_html_e( 'Company', 'londonparkour_v8' ); ?></label>
					<input type="text" name="lp_company" id="lp-company" value="" tabindex="-1" autocomplete="off" class="sr-only" />

					<div class="flex flex-col gap-10">
						<div class="grid grid-cols-1 sm:grid-cols-2 gap-11">
							<?php
							foreach ( array_slice( $lp_field_defs, 0, 2 ) as $lp_def ) {
								lp_part(
									'forms/field',
									array(
										'variant'     => 'boxed',
										'surface'     => 'board',
										'label'       => $lp_def['label'],
										'name'        => $lp_def['key'],
										'type'        => $lp_def['type'],
										'placeholder' => $lp_def['placeholder'],
										'required'    => $lp_def['required'],
									)
								);
							}
							?>
						</div>
						<?php
						lp_part(
							'forms/field',
							array(
								'variant'     => 'boxed',
								'surface'     => 'board',
								'label'       => $lp_field_defs[2]['label'],
								'name'        => $lp_field_defs[2]['key'],
								'type'        => $lp_field_defs[2]['type'],
								'placeholder' => $lp_field_defs[2]['placeholder'],
								'required'    => $lp_field_defs[2]['required'],
							)
						);
						lp_part(
							'forms/text-area',
							array(
								'variant'     => 'boxed',
								'surface'     => 'board',
								'label'       => 'MESSAGE',
								'name'        => 'message',
								'placeholder' => 'Tell us a little about your goals...',
								'required'    => true,
							)
						);
						?>
						<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-[18px]">
							<p class="inline-flex items-center gap-2.5 font-body text-[11px] tracking-[0.2px] text-neutral-content/50 m-0">
								<?php lp_icon( 'icon-clock', 'w-[13px] h-[13px] shrink-0' ); ?>
								<?php echo esc_html( $lp_note ); ?>
							</p>
							<?php
							lp_part(
								'elements/button',
								array(
									'variant'          => 'primary',
									'type'             => 'submit',
									'label'            => 'SEND MESSAGE',
									'trailing_icon_id' => 'icon-arrow-right',
								)
							);
							?>
						</div>
					</div>
				</form>
			</div>
			<div class="w-full lg:w-[380px] lg:shrink-0">
				<?php
				lp_part(
					'components/aside-panel',
					array(
						'title'      => $lp_reach_title,
						'spots_left' => $lp_reach_spots,
						'rows'       => $lp_reach_rows,
						'cta_label'  => $lp_reach_cta,
						'href'       => $lp_reach_href,
						'note'       => $lp_reach_note,
						'surface'    => 'board',
					)
				);
				?>
			</div>
		</div>
	</div>
</section>
