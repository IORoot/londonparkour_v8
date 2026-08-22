<?php
/**
 * Dispatch — PathFinder monthly Mailchimp capture.
 *
 * Ported from src/stories/Blocks/Dispatch/Dispatch.js (`giA1v` / homepage `bMeq5`).
 *
 * Page ground (`bg-base-100`) with a hairline top. Composes forms/field.php
 * (underline, page, name=EMAIL) and elements/button.php type=submit — never
 * role=button on an anchor. Privacy ↗ is text-accent on this ground.
 *
 * Mailchimp: POST the form_action URL with name=EMAIL and the honeypot field
 * named in Mailchimp's embed (b_{u}_{id}). Point Mailchimp's thank-you URL at
 * this page with ?dispatch=sent to show the QU13c success copy.
 *
 * @param string $args['kicker']
 * @param string $args['note']
 * @param string $args['headline']
 * @param string $args['standfirst']
 * @param string $args['consent']
 * @param string $args['privacy_label']
 * @param string $args['privacy_href']
 * @param string $args['submit_label']
 * @param string $args['form_action']
 * @param string $args['honeypot_name']
 * @param string $args['success_kicker']
 * @param string $args['success_headline']
 * @param string $args['success_body']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_kicker        = (string) ( $args['kicker'] ?? 'The PathFinder' );
$lp_note          = (string) ( $args['note'] ?? 'A MONTHLY NOTE · NEVER A WEEKLY BLAST' );
$lp_headline      = (string) ( $args['headline'] ?? 'Subscribe to the monthly dispatch.' );
$lp_standfirst    = (string) ( $args['standfirst'] ?? 'One email. Unsubscribe any time.' );
$lp_consent       = (string) ( $args['consent'] ?? 'By joining you agree we can email you.' );
$lp_privacy_label = (string) ( $args['privacy_label'] ?? 'Privacy ↗' );
$lp_privacy_href  = (string) ( $args['privacy_href'] ?? '/privacy' );
$lp_submit_label  = (string) ( $args['submit_label'] ?? 'JOIN THE LIST' );
$lp_form_action   = (string) ( $args['form_action'] ?? '' );
$lp_honeypot_name = (string) ( $args['honeypot_name'] ?? 'b_hp' );
$lp_success_kicker   = (string) ( $args['success_kicker'] ?? 'SENT' );
$lp_success_headline = (string) ( $args['success_headline'] ?? "You're on the list." );
$lp_success_body     = (string) ( $args['success_body'] ?? 'Check your inbox to confirm. We will not write again until you do.' );

$lp_status = isset( $_GET['dispatch'] ) ? sanitize_key( wp_unslash( $_GET['dispatch'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lp_is_success = 'sent' === $lp_status;

$lp_heading_id  = wp_unique_id( 'dispatch-heading-' );
$lp_honeypot_id = wp_unique_id( 'dispatch-hp-' );
$lp_note_class  = 'font-label text-[12px] font-normal tracking-[0.4px] text-base-content/65 m-0';

$lp_spacing = lp_section_spacing( $args );
?>
<section class="w-full bg-base-100 border-t border-base-300" data-component="dispatch"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="<?php echo lp_classes( 'px-6 lg:px-16 py-scale-xl flex flex-col gap-7 lg:gap-10', $lp_spacing ); ?>">
		<div class="flex items-center justify-between gap-4">
			<span class="font-label text-[12px] font-semibold tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_kicker ); ?></span>
			<span class="<?php echo lp_classes( 'hidden lg:block', $lp_note_class ); ?>"><?php echo esc_html( $lp_note ); ?></span>
		</div>
		<div class="flex flex-col lg:flex-row lg:items-start gap-7 lg:gap-[72px]">
			<div class="flex flex-col gap-7 lg:gap-4 flex-1 min-w-0">
				<h2 id="<?php echo esc_attr( $lp_heading_id ); ?>" class="font-heading text-[28px] font-semibold tracking-[-0.6px] text-base-content lg:text-[32px] lg:tracking-[-0.8px] m-0"><?php echo esc_html( $lp_headline ); ?></h2>
				<p class="font-body text-[14px] leading-[1.55] lg:leading-[1.6] text-base-content/65 m-0"><?php echo esc_html( $lp_standfirst ); ?></p>
				<p class="<?php echo lp_classes( 'lg:hidden', $lp_note_class ); ?>"><?php echo esc_html( $lp_note ); ?></p>
			</div>
			<?php if ( $lp_is_success ) : ?>
				<div class="flex flex-col gap-[14px] w-full lg:w-[480px]" role="status" aria-live="polite">
					<span class="font-label text-[12px] font-semibold tracking-[0.9px] text-success"><?php echo esc_html( $lp_success_kicker ); ?></span>
					<p class="font-heading text-[22px] font-semibold tracking-[-0.4px] text-base-content m-0"><?php echo esc_html( $lp_success_headline ); ?></p>
					<p class="font-body text-[14px] leading-[1.55] text-base-content/65 m-0"><?php echo esc_html( $lp_success_body ); ?></p>
				</div>
			<?php else : ?>
				<form
					class="flex flex-col gap-[22px] w-full lg:w-[480px]"
					method="post"
					action="<?php echo esc_url( $lp_form_action ); ?>"
					aria-labelledby="<?php echo esc_attr( $lp_heading_id ); ?>"
				>
					<div class="sr-only" aria-hidden="true">
						<input id="<?php echo esc_attr( $lp_honeypot_id ); ?>" type="text" name="<?php echo esc_attr( $lp_honeypot_name ); ?>" tabindex="-1" autocomplete="off" value="">
					</div>
					<?php
					lp_part(
						'forms/field',
						array(
							'label'         => 'EMAIL ADDRESS',
							'name'          => 'EMAIL',
							'type'          => 'email',
							'autocomplete'  => 'email',
							'placeholder'   => 'you@email.com',
							'required'      => true,
							'surface'       => 'page',
							'variant'       => 'underline',
						)
					);
					lp_part(
						'elements/button',
						array(
							'variant'          => 'primary',
							'type'             => 'submit',
							'label'            => $lp_submit_label,
							'trailing_icon_id' => 'icon-arrow-right',
						)
					);
					?>
					<div class="flex flex-col gap-1.5 lg:flex-row lg:items-center lg:gap-2">
						<p class="font-label text-[12px] font-normal tracking-[0.2px] text-base-content/65 m-0"><?php echo esc_html( $lp_consent ); ?></p>
						<a href="<?php echo esc_url( $lp_privacy_href ); ?>" class="font-label text-[12px] font-semibold tracking-[0.9px] text-accent"><?php echo esc_html( $lp_privacy_label ); ?></a>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</section>
