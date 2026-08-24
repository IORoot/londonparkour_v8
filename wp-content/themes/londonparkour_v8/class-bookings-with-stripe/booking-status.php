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
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-2 gap-x-16 gap-y-16 items-start">
			<article class="flex flex-col gap-8" data-mount="ticket">
				<div class="flex flex-col gap-2">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">YOUR BOOKING</span>
					<h2 class="font-heading text-[36px] font-bold leading-none tracking-[-1.6px] text-base-content m-0">Receipt</h2>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/65 m-0">Paid in full · confirmation emailed.</p>
				</div>
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
			<div class="flex flex-col gap-8" data-mount="place">
				<div class="flex flex-col gap-3">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">MEETING POINT</span>
					<h2 class="font-heading text-[36px] font-bold leading-none tracking-[-1.6px] text-base-content m-0"><?php echo esc_html( $lp_site && '—' !== $lp_site ? $lp_site . '.' : 'Meeting point.' ); ?></h2>
					<?php if ( $lp['meeting_point'] ) : ?>
						<p class="font-label text-[14px] font-normal leading-[1.7] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp['meeting_point'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $lp['transport_rail'] || $lp['transport_bus'] || $lp['maps_href'] ) : ?>
					<div class="flex flex-col gap-[10px] border-t border-base-content pt-[22px]">
						<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">TRANSPORT</span>
						<?php if ( $lp['transport_rail'] ) : ?>
							<p class="font-body text-[13px] font-medium leading-[1.6] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp['transport_rail'] ); ?></p>
						<?php endif; ?>
						<?php if ( $lp['transport_bus'] ) : ?>
							<p class="font-body text-[12px] font-normal leading-[1.6] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp['transport_bus'] ); ?></p>
						<?php endif; ?>
						<div class="flex flex-wrap items-center justify-between gap-[16px] border-t border-base-300 pt-[14px]">
							<?php if ( $lp['lat'] && $lp['lon'] ) : ?>
								<span class="font-label text-[10px] font-medium uppercase tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp['lat'] . ' / ' . $lp['lon'] ); ?></span>
							<?php endif; ?>
							<?php if ( $lp['maps_href'] ) : ?>
								<a href="<?php echo esc_url( $lp['maps_href'] ); ?>" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent">OPEN IN MAPS ↗</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
				<?php if ( $lp['image_id'] ) : ?>
					<div class="relative w-full aspect-[16/10] bg-base-300 overflow-hidden">
						<?php
						lp_part(
							'components/media-photo',
							array(
								'image_id' => (int) $lp['image_id'],
								'layout'   => 'fill',
								'size'     => 'lp_wide',
								'sizes'    => '(min-width: 1024px) 50vw, 100vw',
								'alt'      => '',
							)
						);
						?>
					</div>
				<?php endif; ?>
				<?php if ( $lp['whatsapp_href'] ) : ?>
					<aside class="bg-neutral text-neutral-content p-6 flex flex-col sm:flex-row gap-6 items-start" data-component="booking-status-whatsapp">
						<div class="flex flex-col gap-3 min-w-0">
							<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content/70">CLASS GROUP</span>
							<h3 class="font-heading text-[22px] font-medium tracking-[-0.3px] text-neutral-content m-0">Join the WhatsApp.</h3>
							<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/80 m-0">Coaches post the pin the morning of. Open the group on this phone.</p>
							<?php
							lp_part(
								'elements/button',
								array(
									'variant'          => 'ghost',
									'label'            => 'Open WhatsApp',
									'href'             => $lp['whatsapp_href'],
									'trailing_icon_id' => 'icon-arrow-right',
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
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-x-16 gap-y-16 items-start">
			<div class="flex flex-col gap-8">
				<div class="flex flex-col gap-2">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content">BEFORE YOU COME</span>
					<h2 class="font-heading text-[36px] font-bold leading-none tracking-[-1.6px] text-base-content m-0">Before you come.</h2>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/65 m-0">Anything else, email hello@londonparkour.com or ask the coach on the meeting point.</p>
				</div>
				<div class="flex flex-col">
					<?php foreach ( $lp['faqs'] as $lp_faq ) : ?>
						<?php lp_part( 'components/faq-item', array_merge( $lp_faq, array( 'surface' => 'page' ) ) ); ?>
					<?php endforeach; ?>
				</div>
			</div>
			<aside class="flex flex-col gap-4">
				<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">CLASS FILM  ·  <?php echo esc_html( strtoupper( $lp_name ) ); ?></span>
				<div class="relative w-full aspect-[4/5] bg-base-300 overflow-hidden">
					<?php if ( $lp['image_id'] ) : ?>
						<?php
						lp_part(
							'components/media-photo',
							array(
								'image_id' => (int) $lp['image_id'],
								'layout'   => 'fill',
								'size'     => 'lp_portrait',
								'sizes'    => '(min-width: 1024px) 380px, 100vw',
								'alt'      => '',
							)
						);
						?>
					<?php endif; ?>
					<span class="absolute top-[16px] left-[16px]">
						<?php
						lp_part(
							'elements/button',
							array(
								'variant'          => 'primary',
								'label'            => 'WATCH THE CLASS',
								'href'             => $lp_href,
								'trailing_icon_id' => 'icon-play',
							)
						);
						?>
					</span>
				</div>
			</aside>
		</div>
	</div>

	<div class="w-full bg-neutral" data-component="booking-status-private">
		<div class="px-6 lg:px-16 py-scale-2xl grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-10 items-end">
			<div class="flex flex-col gap-4 max-w-[640px]">
				<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content/70">PRIVATE 1:1</span>
				<h2 class="font-heading text-[36px] font-bold leading-none tracking-[-1.6px] text-neutral-content m-0">Train one-to-one.</h2>
				<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-neutral-content/80 m-0">Same coaches, your pace. First wall or a skill you want locked in — sixty minutes on the Southbank.</p>
			</div>
			<div class="flex flex-col gap-4 items-start lg:items-end">
				<div class="flex items-baseline gap-3">
					<span class="font-display font-bold text-[48px] leading-none tracking-[-2px] text-neutral-content">£60</span>
					<span class="font-label text-[11px] font-semibold uppercase tracking-[1px] text-neutral-content/70">/ SESSION · 60 MIN</span>
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

	<div class="bg-neutral text-neutral-content p-scale-xl flex flex-col lg:flex-row items-center justify-between gap-scale-l" data-component="gift-card-upsell">
		<div class="flex flex-col gap-scale-xs flex-1">
			<span class="font-mono uppercase tracking-widest text-step--1 text-neutral-content/60">GIFT CARDS</span>
			<h2 class="font-display font-medium text-step-4 leading-tight">Give the gift of movement.</h2>
			<p class="font-mono text-step-0 text-neutral-content/60 max-w-md">A London Parkour gift card is redeemable against classes, coaching and workshops — the perfect present for anyone ready to move.</p>
			<?php
			lp_part(
				'elements/button',
				array(
					'variant'          => 'primary',
					'label'            => 'Buy a gift card',
					'href'             => $lp['coupons_href'],
					'trailing_icon_id' => 'icon-arrow-right',
				)
			);
			?>
		</div>
		<div class="bg-primary text-primary-content w-[300px] h-[186px] p-scale-s rounded-lg flex flex-col justify-between">
			<div class="flex justify-between items-start">
				<span class="font-display font-bold">LONDONPARKOUR</span>
			</div>
			<div class="flex justify-between items-end">
				<span class="font-display font-medium text-step-3">£65</span>
				<span class="font-mono text-step--2 tracking-widest">GIFT CARD</span>
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
