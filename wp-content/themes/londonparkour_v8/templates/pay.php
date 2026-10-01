<?php
/**
 * Template Name: Pay
 *
 * Pay — breadcrumb → masthead → form + THIS PAYMENT aside → onward.
 * Quiet in the site nav. Copy from `PRTrR` "Pay (Concourse)" under v3.
 *
 * Ported from src/stories/Pages/Pay/Pay.js. Chrome stays in the template
 * (Legal / Contact pattern). The form posts to admin-post as a no-JS
 * fallback; assets/js/elements/PayForm.js intercepts and POSTs JSON to
 * clasbpro `/custom-checkout` so errors stay on the page.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header( null, array( 'active_key' => '' ) );

$lp_classes_href = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' );
$lp_contact_href = home_url( '/contact/' );

$lp_amount = '';
if ( isset( $_GET['amount'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lp_amount = preg_replace( '/[^0-9.]/', '', (string) wp_unslash( $_GET['amount'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$lp_pay_status = isset( $_GET['pay'] ) ? sanitize_key( wp_unslash( $_GET['pay'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lp_pay_error  = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array( 'label' => 'PAY' ),
			),
			'action' => array(
				'label' => 'CLASSES ↗',
				'href'  => $lp_classes_href,
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'Pay an agreed amount.',
			'note'  => 'For a figure already discussed — a workshop rest, a private adjustment, or anything that is not a standard class fare.',
		)
	);
	?>

	<section class="w-full bg-secondary" data-component="pay-form" data-surface="board">
		<div class="px-6 lg:px-16 pt-scale-xl pb-scale-2xl">
			<div class="flex flex-col lg:flex-row gap-12 lg:gap-20 items-start">
				<div class="w-full lg:max-w-[852px] flex flex-col gap-10">
					<div class="flex flex-col gap-4">
						<h2 class="font-heading text-[32px] font-semibold tracking-[-0.6px] text-neutral-content m-0">Enter the amount</h2>
						<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/50 m-0">Name and email go on the receipt. You will pay on Stripe Checkout.</p>
					</div>

					<?php if ( 'error' === $lp_pay_status ) : ?>
						<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-error m-0" role="alert" data-pay-error>
							<?php echo esc_html( $lp_pay_error ? $lp_pay_error : 'Something went wrong starting payment. Please try again.' ); ?>
						</p>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-form="pay" aria-label="<?php echo esc_attr__( 'Pay an agreed amount', 'londonparkour_v8' ); ?>">
						<?php wp_nonce_field( 'lp_pay', 'lp_pay_nonce' ); ?>
						<input type="hidden" name="action" value="lp_pay" />
						<div class="sr-only">
							<label for="lp-pay-company"><?php esc_html_e( 'Company', 'londonparkour_v8' ); ?></label>
							<input type="text" name="lp_company" id="lp-pay-company" value="" tabindex="-1" autocomplete="off" />
						</div>

						<div class="flex flex-col gap-10">
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-11">
								<?php
								lp_part(
									'forms/field',
									array(
										'variant'      => 'boxed',
										'surface'      => 'board',
										'label'        => 'NAME',
										'name'         => 'name',
										'type'         => 'text',
										'placeholder'  => 'Your full name',
										'required'     => true,
										'autocomplete' => 'name',
									)
								);
								lp_part(
									'forms/field',
									array(
										'variant'      => 'boxed',
										'surface'      => 'board',
										'label'        => 'EMAIL',
										'name'         => 'email',
										'type'         => 'email',
										'placeholder'  => 'you@email.com',
										'required'     => true,
										'autocomplete' => 'email',
									)
								);
								?>
							</div>
							<?php
							lp_part(
								'forms/field',
								array(
									'variant'      => 'boxed',
									'surface'      => 'board',
									'label'        => 'AMOUNT',
									'name'         => 'amount',
									'type'         => 'text',
									'placeholder'  => '£0.00',
									'required'     => true,
									'inputmode'    => 'decimal',
									'autocomplete' => 'transaction-amount',
									'value'        => $lp_amount,
								)
							);
							?>
							<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-[18px]">
								<p class="inline-flex items-center gap-2.5 font-body text-[11px] tracking-[0.2px] text-neutral-content/50 m-0">
									<?php lp_icon( 'icon-clock', 'w-[13px] h-[13px] shrink-0' ); ?>
									You will be sent to Stripe to complete payment.
								</p>
								<?php
								lp_part(
									'elements/button',
									array(
										'variant'          => 'primary',
										'type'             => 'submit',
										'label'            => 'PAY',
										'trailing_icon_id' => 'icon-arrow-right',
									)
								);
								?>
							</div>
						</div>
					</form>
					<noscript>
						<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/50 m-0">JavaScript is not required — this form still sends you to Stripe Checkout.</p>
					</noscript>
				</div>
				<div class="w-full lg:w-[380px] lg:shrink-0">
					<?php
					lp_part(
						'components/aside-panel',
						array(
							'title'      => 'THIS PAYMENT',
							'rows'       => array(
								array(
									'label' => 'CURRENCY',
									'value' => 'GBP',
								),
								array(
									'label' => 'RECEIPT',
									'value' => 'Sent to the email you enter',
								),
								array(
									'label' => 'CHECKOUT',
									'value' => 'Stripe hosted card form',
								),
								array(
									'label' => 'AFTER',
									'value' => 'Confirmation page',
								),
							),
							'cta_label'  => 'FIND A CLASS',
							'href'       => $lp_classes_href,
							'note'       => 'This is not a class booking. Standard sessions are on the agenda.',
							'surface'    => 'board',
						)
					);
					?>
				</div>
			</div>
		</div>
	</section>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CONTACT',
				'label'   => 'Write to the school',
				'href'    => $lp_contact_href,
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Standard £15 sessions',
				'href'    => $lp_classes_href,
			),
		)
	);
	?>
</main>

<?php
get_footer();
