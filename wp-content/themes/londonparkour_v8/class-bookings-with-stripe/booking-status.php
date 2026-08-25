<?php
/**
 * Booking status layout — Concourse overlay of the clasbpro shortcode.
 *
 * Source: src/stories/Pages/BookingStatus/BookingStatus.js
 * Pencil: NAOqS (confirmed), i3S9z (cancelled), O10PG5 (error).
 *
 * Coupon purchases fall through to the plugin fragments. Class bookings get
 * the welcome pack (confirmed) or the compact board (cancelled / error).
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View layout; $view extracted by the plugin.

$loader = '\IOROOT_STRIPE_BOOKINGS_PRO\Theme_Loader';
$theme_class  = class_exists( $loader ) ? $loader::get_wrapper_class() : '';
$status_class = $view->get_status_class();
$root_classes = trim( 'cbfs-status cbfs-status--layout-modern cbfs-status--concourse ' . $theme_class . ' cbfs-status--' . $status_class );
$session_attr = class_exists( $loader ) ? $loader::status_session_attrs( $view->type, $view->session_id, $view->status_token, $view->kind, $view->purchase_id ) : '';

do_action( 'clasbpro_status_template_start', $type, $booking );
?>
<div class="<?php echo esc_attr( $root_classes ); ?>"<?php echo $session_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<?php
if ( $view->is_coupon() ) {
	if ( class_exists( $loader ) ) {
		include $loader::status_content_path();
	}
	echo '</div>';
	do_action( 'clasbpro_status_template_end', $type, $booking );
	return;
}

$lp          = lp_clasbpro_status_context( $view );
$lp_variant  = $view->get_variant();
$lp_compact  = in_array( $lp_variant, array( 'cancelled', 'error' ), true );
$lp_error    = 'error' === $lp_variant;
$lp_pending  = 'success-pending' === $lp_variant;
$lp_name     = $lp['class_name'] ? $lp['class_name'] : __( 'Class', 'londonparkour_v8' );
$lp_href     = $lp['class_href'] ? $lp['class_href'] : $lp['origin'];
$lp_crumb    = $lp_error ? 'ERROR' : ( $lp_compact ? 'CANCELLED' : 'CONFIRMED' );
$lp_title    = $lp_error
	? "We couldn't take the booking."
	: ( $lp_compact ? 'You left before paying.' : 'Booking confirmed.' );
$lp_note     = $lp['note'] ? $lp['note'] : $lp_name;

lp_part(
	'components/breadcrumb-rail',
	array(
		'crumbs' => array(
			array(
				'label' => 'HOME',
				'href'  => home_url( '/' ),
			),
			array(
				'label' => 'CLASSES',
				'href'  => function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' ),
			),
			array(
				'label' => strtoupper( $lp_name ),
				'href'  => $lp_href,
			),
			array( 'label' => $lp_crumb ),
		),
		'action' => array(
			'label' => 'CLASS PAGE ↗',
			'href'  => $lp_href,
		),
	)
);

lp_part(
	'components/page-masthead',
	array(
		'title' => $lp_title,
		'note'  => $lp_note,
	)
);

$lp_when = $lp['session'] ? $lp['session'] : '—';
$lp_site = $lp['location'] ? $lp['location'] : '—';
$lp_seat = '' !== $lp['seats'] ? $lp['seats'] : '—';
$lp_sum  = $lp['total'] ? $lp['total'] : '—';
$lp_ref  = $lp['ref'] ? $lp['ref'] : '—';
$lp_facts = $lp_compact
	? array(
		array( 'icon' => 'icon-clock', 'label' => 'WHEN', 'value' => $lp_when ),
		array( 'icon' => 'icon-map-pin', 'label' => 'SITE', 'value' => $lp_site ),
		array( 'icon' => 'icon-user', 'label' => 'SEATS', 'value' => $lp_seat ),
		array( 'icon' => 'icon-currency-pound', 'label' => 'TOTAL', 'value' => $lp_sum ),
		array(
			'icon'  => $lp_error ? 'icon-exclamation-triangle' : 'icon-x-circle',
			'label' => 'STATUS',
			'value' => $lp_error ? 'FAILED' : 'NOT TAKEN',
		),
	)
	: array(
		array( 'icon' => 'icon-clock', 'label' => 'WHEN', 'value' => $lp_when ),
		array( 'icon' => 'icon-map-pin', 'label' => 'SITE', 'value' => $lp_site ),
		array( 'icon' => 'icon-user', 'label' => 'SEATS', 'value' => $lp_seat ),
		array( 'icon' => 'icon-currency-pound', 'label' => 'TOTAL', 'value' => $lp_sum ),
		array( 'icon' => 'icon-hashtag', 'label' => 'REF', 'value' => $lp_ref ),
	);
?>
	<div class="w-full bg-neutral" data-component="booking-status-fact-rail">
		<div class="px-6 lg:px-16 py-scale-s grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-x-[40px] gap-y-6" data-mount="rail">
			<?php foreach ( $lp_facts as $lp_fact ) : ?>
				<?php lp_part( 'components/fact-row', array_merge( $lp_fact, array( 'surface' => 'board' ) ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
<?php if ( $lp_pending ) : ?>
	<div class="w-full bg-primary text-primary-content">
		<p class="px-6 lg:px-16 py-4 font-label text-[11px] font-semibold uppercase tracking-[1px] m-0">Confirming your booking… Stripe is still settling. This page will update.</p>
	</div>
<?php endif; ?>

<?php if ( $lp_compact ) : ?>
	<?php
	$lp_kicker      = $lp_error ? 'PAYMENT' : 'CHECKOUT';
	$lp_headline    = $lp_error ? 'The payment did not complete.' : 'You left before paying.';
	$lp_lede        = $lp_error
		? ( $view->get_reason_message() ? $view->get_reason_message() : 'Stripe returned an error before the seat was held. Nothing has been charged. Retry the same session, or contact us and we will take the booking by hand.' )
		: 'Stripe was never charged. Nobody has your seat. Book again when you are ready.';
	$lp_charge      = $lp_error ? ( $view->msg ? $view->msg : 'None · card declined' ) : 'None';
	$lp_aside_kick  = $lp_error ? 'HELP' : 'NEXT';
	$lp_aside_title = $lp_error ? 'Still want Saturday?' : 'The seat is still open.';
	$lp_aside_body  = $lp_error
		? 'If this keeps happening, do not retry from the receipt email. Write to us with the class and the time — we will book you in by hand.'
		: 'If you closed the tab by mistake, book the same session from the class page. Need a hand? Contact us and we will hold it.';
	$lp_cta         = $lp_error ? 'TRY AGAIN' : 'BOOK THIS CLASS';
	$lp_surface     = $lp_error ? 'board' : 'page';
	$lp_band        = $lp_error ? 'bg-neutral' : 'bg-base-100';
	$lp_kicker_c    = $lp_error ? 'font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content/70' : 'font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content';
	$lp_heading_c   = $lp_error ? 'font-heading text-[36px] font-bold leading-none tracking-[-1.6px] text-neutral-content m-0' : 'font-heading text-[36px] font-bold leading-none tracking-[-1.6px] text-base-content m-0';
	$lp_body_c      = $lp_error ? 'font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/80 m-0' : 'font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/65 m-0';
	$lp_ak_c        = $lp_error ? 'font-label text-[10px] font-semibold uppercase tracking-[1px] text-neutral-content/70' : 'font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65';
	$lp_at_c        = $lp_error ? 'font-heading text-[22px] font-medium tracking-[-0.3px] text-neutral-content m-0' : 'font-heading text-[22px] font-medium tracking-[-0.3px] text-base-content m-0';
	$lp_ab_c        = $lp_error ? 'font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/80 m-0' : 'font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/65 m-0';
	$lp_rule        = $lp_error ? 'border-neutral-content/20' : 'border-base-300';
	$lp_link_c      = $lp_error ? 'font-label text-[11px] font-semibold uppercase tracking-[1px] text-primary' : 'font-label text-[11px] font-semibold uppercase tracking-[1px] text-accent';
	?>
	<div class="w-full <?php echo esc_attr( $lp_band ); ?>" data-component="booking-status-compact">
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-x-16 gap-y-12 items-start">
			<div class="flex flex-col gap-6">
				<div class="flex flex-col gap-3">
					<span class="<?php echo esc_attr( $lp_kicker_c ); ?>"><?php echo esc_html( $lp_kicker ); ?></span>
					<h2 class="<?php echo esc_attr( $lp_heading_c ); ?>"><?php echo esc_html( $lp_headline ); ?></h2>
					<p class="<?php echo esc_attr( $lp_body_c ); ?>"><?php echo esc_html( $lp_lede ); ?></p>
				</div>
				<dl class="m-0 max-w-[520px]">
					<?php lp_clasbpro_status_ticket_row( 'CLASS', $lp_name, $lp_surface ); ?>
					<?php lp_clasbpro_status_ticket_row( 'SESSION', $lp_when, $lp_surface ); ?>
					<?php lp_clasbpro_status_ticket_row( 'AMOUNT', $lp_sum, $lp_surface ); ?>
					<?php lp_clasbpro_status_ticket_row( 'CHARGE', $lp_charge, $lp_surface ); ?>
				</dl>
				<?php
				lp_part(
					'elements/button',
					array(
						'variant'          => 'primary',
						'label'            => $lp_cta,
						'href'             => $lp_href,
						'trailing_icon_id' => 'icon-arrow-right',
					)
				);
				?>
			</div>
			<aside class="flex flex-col gap-3 border-t <?php echo esc_attr( $lp_rule ); ?> pt-[22px]">
				<span class="<?php echo esc_attr( $lp_ak_c ); ?>"><?php echo esc_html( $lp_aside_kick ); ?></span>
				<h3 class="<?php echo esc_attr( $lp_at_c ); ?>"><?php echo esc_html( $lp_aside_title ); ?></h3>
				<p class="<?php echo esc_attr( $lp_ab_c ); ?>"><?php echo esc_html( $lp_aside_body ); ?></p>
				<a href="<?php echo esc_url( $lp['contact_mail'] ); ?>" class="<?php echo esc_attr( $lp_link_c ); ?>">Write to hello@londonparkour.com ↗</a>
			</aside>
		</div>
	</div>
<?php else : ?>
	<div class="w-full bg-base-100" data-component="booking-status-ticket-place">
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
			<article class="bg-neutral-content border border-base-300 px-7 pt-7 pb-8 flex flex-col" data-mount="ticket">
				<span class="font-label text-[10px] font-normal tracking-[1.2px] uppercase text-base-content/65">YOUR BOOKING</span>
				<h2 class="font-display text-[32px] font-bold leading-none text-base-content mt-2 mb-0">Receipt</h2>
				<p class="font-label text-[11px] font-normal leading-none tracking-[0.1px] text-base-content/65 mt-2 mb-4">Paid in full · confirmation emailed.</p>
				<dl class="m-0">
					<?php lp_clasbpro_status_ticket_row( 'CLASS', $lp_name ); ?>
					<?php lp_clasbpro_status_ticket_row( 'WHEN', $lp_when ); ?>
					<?php lp_clasbpro_status_ticket_row( 'WHERE', $lp_site ); ?>
					<?php lp_clasbpro_status_ticket_row( 'SEATS', $lp_seat ); ?>
					<?php lp_clasbpro_status_ticket_row( 'NAME', $lp['customer_name'] ); ?>
					<?php lp_clasbpro_status_ticket_row( 'TOTAL', $lp_sum ); ?>
					<?php lp_clasbpro_status_ticket_row( 'REFERENCE', $lp_ref ); ?>
				</dl>
			</article>
			<div class="flex flex-col gap-4" data-mount="place">
				<div class="bg-base-200 p-[22px] flex flex-col gap-4">
					<?php if ( $lp['site_kicker'] ) : ?>
						<div class="flex items-center gap-2">
							<?php lp_icon( 'icon-map-pin', 'w-3 h-3 text-base-content shrink-0' ); ?>
							<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65"><?php echo esc_html( $lp['site_kicker'] ); ?></span>
						</div>
					<?php endif; ?>
					<div class="flex flex-col gap-[10px]">
						<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">MEETING POINT</span>
						<?php if ( $lp['meeting_point'] ) : ?>
							<p class="font-body text-[14px] font-normal leading-[1.7] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp['meeting_point'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( $lp['transport_rail'] || $lp['transport_bus'] || $lp['maps_href'] ) : ?>
						<div class="flex flex-col gap-[10px]">
							<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">TRANSPORT</span>
							<?php if ( $lp['transport_rail'] ) : ?>
								<p class="font-body text-[13px] font-medium leading-[1.6] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp['transport_rail'] ); ?></p>
							<?php endif; ?>
							<?php if ( $lp['transport_bus'] ) : ?>
								<p class="font-body text-[12px] font-normal leading-[1.6] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp['transport_bus'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="flex flex-wrap items-center justify-between gap-4 border-t border-base-300 pt-[14px]">
							<?php if ( $lp['foot'] ) : ?>
								<span class="font-label text-[10px] font-medium uppercase tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp['foot'] ); ?></span>
							<?php endif; ?>
							<?php if ( $lp['maps_href'] ) : ?>
								<a href="<?php echo esc_url( $lp['maps_href'] ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent">OPEN IN MAPS ↗</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( $lp['location_image_id'] ) : ?>
						<div class="relative w-full h-[220px] bg-base-300 overflow-hidden">
							<?php
							lp_part(
								'components/media-photo',
								array(
									'image_id' => (int) $lp['location_image_id'],
									'layout'   => 'fill',
									'size'     => 'lp_wide',
									'sizes'    => '(min-width: 1024px) 50vw, 100vw',
									'alt'      => '',
								)
							);
							?>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $lp['show_whatsapp'] ) ) : ?>
				<aside class="bg-base-200 p-[22px] flex flex-row gap-5 items-center" data-component="booking-status-whatsapp">
					<div class="flex flex-col gap-2 min-w-0 flex-1">
						<span class="font-label text-[10px] font-normal tracking-[1.2px] uppercase leading-[12px] text-base-content/65">CLASS GROUP</span>
						<h3 class="font-display text-[22px] font-bold leading-[26px] text-base-content m-0 [text-box:normal]">Join the WhatsApp.</h3>
						<p class="font-body text-[12px] font-normal leading-[15px] text-base-content/65 m-0">Scan to join the class group. Coaches post the pin the morning of.</p>
						<a href="<?php echo esc_url( $lp['whatsapp_href'] ); ?>" target="_blank" rel="noopener noreferrer" class="font-label text-[11px] font-semibold leading-[13px] text-accent">Open WhatsApp ↗</a>
					</div>
					<div class="w-[132px] h-[132px] shrink-0 bg-base-100 p-2 relative">
						<?php
						lp_part(
							'components/media-photo',
							array(
								'image_url' => $lp['qr_src'],
								'layout'    => 'contain',
								'alt'       => 'WhatsApp group QR code',
							)
						);
						?>
					</div>
				</aside>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="w-full bg-base-100" data-component="booking-status-before-you-come">
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-[380px_minmax(0,1fr)] gap-x-16 gap-y-16 items-start">
			<aside class="flex flex-col gap-4">
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-base-content/65">COMMON QUESTIONS</span>
				<h2 class="font-display text-[36px] font-bold leading-none text-base-content m-0">Before you come.</h2>
				<p class="font-body text-[12px] leading-[1.25] text-base-content/65 m-0">Anything else, email hello@londonparkour.com or ask the coach on the meeting point.</p>
				<div class="flex flex-col gap-2 mt-2">
					<div class="relative w-full aspect-[380/214] bg-neutral overflow-hidden">
						<?php if ( $lp['image_id'] ) : ?>
							<?php
							lp_part(
								'components/media-photo',
								array(
									'image_id' => (int) $lp['image_id'],
									'layout'   => 'fill',
									'size'     => 'lp_wide',
									'sizes'    => '(min-width: 1024px) 380px, 100vw',
									'alt'      => '',
								)
							);
							?>
						<?php endif; ?>
						<?php if ( $lp['video_id'] ) : ?>
							<button type="button" class="absolute inset-0 grid place-items-center" command="show-modal" commandfor="booking-status-class-film" data-video-type="youtube" data-video-id="<?php echo esc_attr( $lp['video_id'] ); ?>" data-autoplay="true" aria-label="Watch the class">
								<span class="w-14 h-14 rounded-full bg-base-100 grid place-items-center">
									<?php lp_icon( 'icon-play', 'w-4 h-4 text-base-content' ); ?>
								</span>
							</button>
						<?php endif; ?>
					</div>
					<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-base-content/65">CLASS FILM  ·  <?php echo esc_html( $lp['site_kicker'] ? str_replace( ' · ', ' — ', $lp['site_kicker'] ) : strtoupper( $lp_name ) ); ?></span>
				</div>
			</aside>
			<div class="flex flex-col divide-y divide-base-300 border-t border-t-base-content border-b border-b-base-300">
				<?php foreach ( $lp['faqs'] as $lp_faq ) : ?>
					<?php lp_part( 'components/faq-item', array_merge( $lp_faq, array( 'surface' => 'page', 'collapsible' => false ) ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
	if ( $lp['video_id'] ) {
		lp_part(
			'elements/dialog-video',
			array(
				'dialog_id'  => 'booking-status-class-film',
				'video_type' => 'youtube',
				'title'      => $lp_name,
			)
		);
	}
	?>

	<div class="w-full bg-neutral" data-component="booking-status-private">
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-[320px_minmax(0,1fr)] gap-12 items-center">
			<div class="relative w-full h-[200px] bg-secondary overflow-hidden">
				<?php if ( $lp['image_id'] ) : ?>
					<?php
					lp_part(
						'components/media-photo',
						array(
							'image_id' => (int) $lp['image_id'],
							'layout'   => 'fill',
							'size'     => 'lp_wide',
							'sizes'    => '320px',
							'alt'      => '',
						)
					);
					?>
				<?php endif; ?>
			</div>
			<div class="flex flex-col gap-3 items-start">
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50">PRIVATE 1:1</span>
				<h2 class="font-display text-[36px] font-bold leading-none text-neutral-content m-0">Train one-to-one.</h2>
				<p class="font-body text-[13px] leading-[1.25] text-neutral-content/50 m-0">Same coaches, your pace. First wall or a skill you want locked in — sixty minutes on the Southbank.</p>
				<div class="flex items-end gap-5">
					<span class="font-display font-bold text-[28px] leading-none text-neutral-content">£60</span>
					<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50 pb-1">/ SESSION · 60 MIN</span>
				</div>
				<?php
				lp_part(
					'elements/button',
					array(
						'variant'          => 'primary',
						'label'            => 'BOOK A SESSION',
						'href'             => $lp['private_href'],
						'trailing_icon_id' => 'icon-arrow-right',
					)
				);
				?>
			</div>
		</div>
	</div>

	<div class="bg-primary px-6 lg:px-16 py-16 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-20" data-component="gift-card-upsell">
		<div class="flex flex-col gap-[22px] flex-1 items-start max-w-[912px]">
			<span class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-primary-content/70">GIFT CARDS</span>
			<h2 class="font-heading text-[32px] font-semibold leading-none text-primary-content m-0">Give the gift of movement.</h2>
			<p class="font-body text-[13px] leading-[1.6] text-primary-content/75 m-0 max-w-[520px]">A LondonParkour gift card unlocks classes, private tuition and the full tutorial library — for anyone ready to move.</p>
			<?php
			lp_part(
				'elements/button',
				array(
					'variant'          => 'inverse',
					'label'            => 'BUY A GIFT CARD',
					'href'             => $lp['coupons_href'],
					'trailing_icon_id' => 'icon-arrow-right',
				)
			);
			?>
		</div>
		<div class="w-[320px] shrink-0 bg-secondary flex flex-col">
			<div class="flex items-center justify-between px-[18px] py-[14px]">
				<span class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-primary">LONDON PARKOUR</span>
				<span class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-neutral-content">VALID</span>
			</div>
			<div class="flex items-center gap-5 px-[18px] py-4">
				<div class="flex flex-col gap-1 flex-1">
					<span class="font-label text-[9px] font-semibold uppercase tracking-[0.9px] text-neutral-content/50">FROM</span>
					<span class="font-heading text-[15px] font-semibold text-neutral-content">ANY SITE</span>
				</div>
				<?php lp_icon( 'icon-arrow-right', 'w-4 h-4 text-primary shrink-0' ); ?>
				<div class="flex flex-col gap-1 flex-1">
					<span class="font-label text-[9px] font-semibold uppercase tracking-[0.9px] text-neutral-content/50">TO</span>
					<span class="font-heading text-[15px] font-semibold text-neutral-content">CLASSES</span>
				</div>
			</div>
			<div class="flex items-end justify-between px-[18px] pt-[22px] pb-[18px]">
				<div class="flex flex-col gap-1.5">
					<span class="font-label text-[9px] font-semibold uppercase tracking-[0.9px] text-neutral-content/50">FARE</span>
					<span class="font-heading text-[42px] font-bold leading-none text-primary">£50</span>
				</div>
				<div class="flex flex-col items-end gap-1">
					<span class="font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-neutral-content">GIFT CARD</span>
					<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50">NO EXPIRY</span>
				</div>
			</div>
			<div class="flex items-center justify-between px-[18px] py-3 bg-neutral">
				<span class="font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-primary">DEP · GIFT</span>
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50">REF LP-50-GFT</span>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php
lp_part(
	'components/page-onward',
	array(
		'prev' => array(
			'keyword' => '← CLASS PAGE',
			'label'   => $lp_name,
			'href'    => $lp_href,
		),
		'next' => array(
			'keyword' => 'CONTACT →',
			'label'   => $lp_compact ? 'Book by hand' : 'Questions before Saturday',
			'href'    => $lp['contact_href'],
		),
	)
);
?>
</div>
<?php
do_action( 'clasbpro_status_template_end', $type, $booking );
