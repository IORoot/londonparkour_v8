<?php
/**
 * PrivateCoaching — Concourse "05 Private Coaching" / homepage "05C".
 *
 * Ported from src/stories/Blocks/PrivateCoaching/PrivateCoaching.js.
 *
 * Layouts:
 *   - booking (default) — node TQ0ci: bg-primary band, media + caption,
 *     offer copy, inline week-slot booking panel. Homepage uses this.
 *   - offer — node jmalK: portrait + offer stack on bg-base-200.
 *
 * Repeater-only: the fact rail and booking grid are this block's own copy,
 * not entities.
 *
 * Offer fact rail: NOT components/fact-row.php. FactRow is a daisyUI stats
 * pair; these keys are font-label semibold at tracking-[1.1px]. Same idea,
 * different type — ported inline as the source has it.
 *
 * Offer fact hairline: the source models each fact's top border; flush
 * columns render as one continuous line, so offer uses a single border-t on
 * the row. Booking keeps per-fact border-t (source has them individually).
 *
 * Booking week-nav arrows are literal ‹ › characters in the Storybook — not
 * icons. Left as text.
 *
 * Report: the booking panel (data-component="private-coaching-booking-panel")
 * is PrivateCoaching-only furniture. Promote only if a second caller appears.
 *
 * @param string $args['layout']            booking|offer. Default booking.
 * @param string $args['eyebrow']
 * @param string $args['meta']              Booking only.
 * @param string $args['headline']
 * @param string $args['standfirst']        Maps to Storybook `body`.
 * @param array  $args['facts']             Rows of label/value.
 * @param string $args['fare_label']
 * @param string $args['amount']
 * @param string $args['unit']
 * @param string $args['reassure']
 * @param int    $args['media']             Attachment id.
 * @param string $args['media_alt']
 * @param string $args['caption_kicker']    Booking only.
 * @param string $args['caption']           Booking only.
 * @param string $args['media_position']    start|end. Booking only.
 * @param string $args['panel_kicker']
 * @param string $args['panel_title']
 * @param string $args['panel_lead']
 * @param string $args['week_range']
 * @param array  $args['days']              label/date rows.
 * @param array  $args['rows']              time + slots|mon…sun.
 * @param string $args['selected_label']
 * @param string $args['selected_value']
 * @param array  $args['primary_action']    Offer.
 * @param array  $args['secondary_action']  Offer ghost.
 * @param array  $args['book_action']       Booking CTA.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_facts_offer = array(
	array(
		'label' => 'DURATION',
		'value' => '60 or 90 min',
	),
	array(
		'label' => 'WHERE',
		'value' => 'Any of six sites',
	),
	array(
		'label' => 'WHO',
		'value' => 'Solo, pairs or family',
	),
);

$lp_default_facts_booking = array(
	array(
		'label' => 'DURATION',
		'value' => '60 min',
	),
	array(
		'label' => 'WHERE',
		'value' => 'Any site',
	),
	array(
		'label' => 'WHO',
		'value' => 'L2 coach',
	),
);

$lp_default_days = array(
	array(
		'label' => 'MON',
		'date'  => '14',
	),
	array(
		'label' => 'TUE',
		'date'  => '15',
	),
	array(
		'label' => 'WED',
		'date'  => '16',
	),
	array(
		'label' => 'THU',
		'date'  => '17',
	),
	array(
		'label' => 'FRI',
		'date'  => '18',
	),
	array(
		'label' => 'SAT',
		'date'  => '19',
	),
	array(
		'label' => 'SUN',
		'date'  => '20',
	),
);

$lp_default_rows = array(
	array(
		'time'  => '09:00',
		'slots' => array( 'available', 'taken', 'available', 'available', 'available', 'closed', 'closed' ),
	),
	array(
		'time'  => '11:00',
		'slots' => array( 'available', 'available', 'closed', 'available', 'available', 'available', 'closed' ),
	),
	array(
		'time'  => '14:00',
		'slots' => array( 'available', 'closed', 'available', 'selected', 'available', 'closed', 'closed' ),
	),
	array(
		'time'  => '16:00',
		'slots' => array( 'available', 'available', 'available', 'available', 'closed', 'available', 'available' ),
	),
	array(
		'time'  => '18:30',
		'slots' => array( 'closed', 'closed', 'closed', 'available', 'available', 'available', 'closed' ),
	),
);

/*
 * Whole literal class strings per slot state — Tailwind v4 scans source text.
 * Never assemble from fragments.
 */
$lp_slot_class = array(
	'selected'  => 'bg-primary text-neutral border border-primary flex items-center justify-center font-label text-[10px]',
	'available' => 'bg-secondary text-neutral-content/50 border border-neutral-content/15 flex items-center justify-center font-label text-[14px]',
	'taken'     => 'bg-neutral/80 text-neutral-content/40 border border-neutral-content/15 flex items-center justify-center font-label text-[12px]',
	'closed'    => 'bg-neutral border border-neutral-content/15 flex items-center justify-center font-label text-[14px]',
);

$lp_slot_mark = array(
	'selected'  => '●',
	'available' => '·',
	'taken'     => '×',
	'closed'    => '',
);

$lp_layout = (string) ( $args['layout'] ?? 'booking' );
if ( ! in_array( $lp_layout, array( 'booking', 'offer' ), true ) ) {
	$lp_layout = 'booking';
}
$lp_is_booking = 'booking' === $lp_layout;

$lp_eyebrow  = (string) ( $args['eyebrow'] ?? '04 — PRIVATE COACHING' );
$lp_headline = (string) ( $args['headline'] ?? 'One coach. Just you.' );

if ( $lp_is_booking ) {
	$lp_body       = (string) ( $args['standfirst'] ?? "Private sessions move at your pace — whether that's a first wall, a comeback from injury, or one line you've been stuck on for months. Any of our three sites, any day of the week." );
	$lp_fare_label = (string) ( $args['fare_label'] ?? 'FROM' );
	$lp_amount     = (string) ( $args['amount'] ?? '£65' );
	$lp_unit       = (string) ( $args['unit'] ?? 'PER SESSION' );
	$lp_default_facts = $lp_default_facts_booking;
} else {
	$lp_body       = (string) ( $args['standfirst'] ?? "Private sessions move at your pace — whether that's a first wall you'd rather not meet in front of a group, a comeback after injury, or one specific line you've been stuck on for months. We come to any of our six sites, any day of the week." );
	$lp_fare_label = (string) ( $args['fare_label'] ?? 'FROM' );
	$lp_amount     = (string) ( $args['amount'] ?? '£75' );
	$lp_unit       = (string) ( $args['unit'] ?? 'PER SESSION · BLOCKS AVAILABLE' );
	$lp_default_facts = $lp_default_facts_offer;
}

$lp_reassure = (string) ( $args['reassure'] ?? 'No commitment. We call back within one working day and match you to a coach.' );

$lp_facts = array();
foreach ( is_array( $args['facts'] ?? null ) ? $args['facts'] : array() as $lp_row ) {
	if ( ! empty( $lp_row['label'] ) || ! empty( $lp_row['value'] ) ) {
		$lp_facts[] = $lp_row;
	}
}
if ( ! $lp_facts ) {
	$lp_facts = $lp_default_facts;
}

$lp_media_id = ! empty( $args['media'] ) ? (int) $args['media'] : 0;
$lp_spacing  = lp_section_spacing( $args );

if ( $lp_is_booking ) :
	$lp_meta            = (string) ( $args['meta'] ?? 'BOOK 1:1 · ALL THREE SITES' );
	$lp_caption_kicker  = (string) ( $args['caption_kicker'] ?? '1:1 COACHING' );
	$lp_caption         = (string) ( $args['caption'] ?? 'A coach in your corner — from first wall to fluent flow.' );
	$lp_media_position  = (string) ( $args['media_position'] ?? 'start' );
	if ( 'end' !== $lp_media_position ) {
		$lp_media_position = 'start';
	}
	$lp_panel_kicker    = (string) ( $args['panel_kicker'] ?? 'PICK A TIME' );
	$lp_panel_title     = (string) ( $args['panel_title'] ?? 'Book your session' );
	$lp_panel_lead      = (string) ( $args['panel_lead'] ?? 'Choose a slot below. We confirm by email within one working day.' );
	$lp_week_range      = (string) ( $args['week_range'] ?? 'MON 14 — SUN 20 JUL' );
	$lp_selected_label  = (string) ( $args['selected_label'] ?? 'SELECTED' );
	$lp_selected_value  = (string) ( $args['selected_value'] ?? 'Thu 17 Jul · 14:00 · Vauxhall' );

	$lp_book = lp_action( $args['book_action'] ?? null ) ?? array(
		'label' => 'REQUEST 1:1',
		'href'  => '',
	);

	$lp_days = array();
	foreach ( is_array( $args['days'] ?? null ) ? $args['days'] : array() as $lp_day ) {
		if ( ! empty( $lp_day['label'] ) || ! empty( $lp_day['date'] ) ) {
			$lp_days[] = $lp_day;
		}
	}
	if ( ! $lp_days ) {
		$lp_days = $lp_default_days;
	}

	$lp_rows = array();
	foreach ( is_array( $args['rows'] ?? null ) ? $args['rows'] : array() as $lp_row ) {
		if ( empty( $lp_row['time'] ) && empty( $lp_row['slots'] ) && empty( $lp_row['mon'] ) ) {
			continue;
		}
		if ( ! empty( $lp_row['slots'] ) && is_array( $lp_row['slots'] ) ) {
			$lp_slots = array_values( $lp_row['slots'] );
		} else {
			$lp_slots = array(
				(string) ( $lp_row['mon'] ?? 'closed' ),
				(string) ( $lp_row['tue'] ?? 'closed' ),
				(string) ( $lp_row['wed'] ?? 'closed' ),
				(string) ( $lp_row['thu'] ?? 'closed' ),
				(string) ( $lp_row['fri'] ?? 'closed' ),
				(string) ( $lp_row['sat'] ?? 'closed' ),
				(string) ( $lp_row['sun'] ?? 'closed' ),
			);
		}
		$lp_rows[] = array(
			'time'  => (string) ( $lp_row['time'] ?? '' ),
			'slots' => $lp_slots,
		);
	}
	if ( ! $lp_rows ) {
		$lp_rows = $lp_default_rows;
	}

	ob_start();
	?>
	<div class="relative w-full lg:w-[568px] lg:shrink-0 min-h-[320px] overflow-hidden bg-neutral flex flex-col justify-end">
		<?php
		if ( $lp_media_id ) {
			$lp_photo = array(
				'image_id' => $lp_media_id,
				'size'     => 'lp_portrait',
				'sizes'    => '(min-width: 1024px) 568px, 100vw',
			);
			if ( '' !== (string) ( $args['media_alt'] ?? '' ) ) {
				$lp_photo['alt'] = (string) $args['media_alt'];
			}
			lp_part( 'components/media-photo', $lp_photo );
		}
		?>
		<div class="relative bg-neutral/60 px-5 pt-4 pb-5 flex flex-col gap-1.5">
			<span class="font-label text-[10px] font-semibold tracking-[1.2px] uppercase text-primary"><?php echo esc_html( $lp_caption_kicker ); ?></span>
			<p class="font-heading text-[16px] font-semibold tracking-[-0.6px] leading-[1.15] text-neutral-content m-0 max-w-[420px]"><?php echo esc_html( $lp_caption ); ?></p>
		</div>
	</div>
	<?php
	$lp_media_col = ob_get_clean();

	ob_start();
	?>
	<div class="flex-1 min-w-0 flex flex-col gap-8 p-8 lg:p-14">
		<div class="flex items-baseline justify-between gap-4 flex-wrap">
			<span class="font-label text-[12px] font-semibold tracking-[0.5px] uppercase text-primary-content"><?php echo esc_html( $lp_eyebrow ); ?></span>
			<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-primary-content/70"><?php echo esc_html( $lp_meta ); ?></span>
		</div>
		<div class="flex flex-col gap-[22px] max-w-[520px]">
			<h2 class="font-heading text-step-3 font-bold leading-[0.92] tracking-[-3px] text-primary-content m-0"><?php echo esc_html( $lp_headline ); ?></h2>
			<p class="font-body text-[15px] leading-[1.6] tracking-[0.2px] text-primary-content/70 m-0"><?php echo esc_html( $lp_body ); ?></p>
		</div>
		<div class="grid grid-cols-3 max-w-[420px]">
			<?php foreach ( $lp_facts as $lp_fact ) : ?>
				<div class="pt-[14px] pr-4 flex flex-col gap-[7px] min-w-0 border-t border-primary-content/25">
					<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-primary-content/70"><?php echo esc_html( (string) ( $lp_fact['label'] ?? '' ) ); ?></span>
					<span class="font-heading text-[20px] font-semibold tracking-[-0.4px] text-primary-content truncate"><?php echo esc_html( (string) ( $lp_fact['value'] ?? '' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="flex items-end gap-2.5 flex-wrap">
			<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-primary-content/70"><?php echo esc_html( $lp_fare_label ); ?></span>
			<span class="font-heading text-[56px] font-bold tracking-[-2px] leading-[0.9] text-primary-content"><?php echo esc_html( $lp_amount ); ?></span>
			<span class="font-label text-[11px] font-normal tracking-[0.8px] uppercase text-primary-content/70"><?php echo esc_html( $lp_unit ); ?></span>
		</div>

		<div class="bg-neutral px-8 pt-8 pb-[30px] flex flex-col" data-component="private-coaching-booking-panel">
			<span class="font-label text-[10px] font-semibold tracking-[1.2px] uppercase text-primary"><?php echo esc_html( $lp_panel_kicker ); ?></span>
			<p class="mt-3 font-heading text-[32px] font-bold tracking-[-1.2px] leading-none text-neutral-content m-0"><?php echo esc_html( $lp_panel_title ); ?></p>
			<p class="mt-2.5 font-body text-[11px] leading-[1.5] tracking-[0.2px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_panel_lead ); ?></p>

			<div class="mt-5 flex items-center justify-between gap-3">
				<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-neutral-content"><?php echo esc_html( $lp_week_range ); ?></span>
				<div class="flex gap-1.5" aria-hidden="true">
					<span class="w-[26px] h-[26px] border border-neutral-content/20 flex items-center justify-center text-neutral-content/50 text-[12px]">‹</span>
					<span class="w-[26px] h-[26px] border border-neutral-content/20 flex items-center justify-center text-neutral-content/50 text-[12px]">›</span>
				</div>
			</div>

			<div class="mt-3 flex flex-col gap-px bg-neutral-content/20">
				<div class="flex gap-px">
					<div class="w-12 shrink-0 h-[34px] bg-neutral"></div>
					<?php foreach ( $lp_days as $lp_day ) : ?>
						<div class="flex-1 min-w-0 h-[34px] bg-neutral flex flex-col items-center justify-center leading-none">
							<span class="font-label text-[9px] font-normal tracking-[0.5px] uppercase text-neutral-content/50"><?php echo esc_html( (string) ( $lp_day['label'] ?? '' ) ); ?></span>
							<span class="font-label text-[9px] font-normal text-neutral-content/50"><?php echo esc_html( (string) ( $lp_day['date'] ?? '' ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<?php foreach ( $lp_rows as $lp_row ) : ?>
					<div class="flex gap-px bg-neutral-content/20">
						<div class="w-12 shrink-0 h-8 bg-neutral flex items-center justify-center font-label text-[9px] text-neutral-content/50"><?php echo esc_html( (string) ( $lp_row['time'] ?? '' ) ); ?></div>
						<?php
						foreach ( (array) ( $lp_row['slots'] ?? array() ) as $lp_state ) :
							$lp_state = (string) $lp_state;
							$lp_cls   = $lp_slot_class[ $lp_state ] ?? $lp_slot_class['closed'];
							$lp_mark  = $lp_slot_mark[ $lp_state ] ?? '';
							?>
							<div class="flex-1 min-w-0 h-8 <?php echo esc_attr( $lp_cls ); ?>" aria-label="<?php echo esc_attr( $lp_state ); ?>"><?php echo esc_html( $lp_mark ); ?></div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="mt-[18px] flex flex-wrap gap-4">
				<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-primary border border-neutral-content/20" aria-hidden="true"></span><span class="font-label text-[9px] font-semibold tracking-[1px] uppercase text-neutral-content/50">SELECTED</span></div>
				<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-secondary border border-neutral-content/20" aria-hidden="true"></span><span class="font-label text-[9px] font-semibold tracking-[1px] uppercase text-neutral-content/50">AVAILABLE</span></div>
				<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-neutral/80 border border-neutral-content/20" aria-hidden="true"></span><span class="font-label text-[9px] font-semibold tracking-[1px] uppercase text-neutral-content/50">TAKEN</span></div>
				<div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-neutral border border-neutral-content/20" aria-hidden="true"></span><span class="font-label text-[9px] font-semibold tracking-[1px] uppercase text-neutral-content/50">CLOSED</span></div>
			</div>

			<div class="mt-5 h-px bg-neutral-content/20" aria-hidden="true"></div>

			<div class="mt-[18px] flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
				<div class="flex flex-col gap-1.5">
					<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_selected_label ); ?></span>
					<span class="font-heading text-[18px] font-semibold tracking-[-0.4px] text-neutral-content"><?php echo esc_html( $lp_selected_value ); ?></span>
				</div>
				<span>
					<?php
					lp_part(
						'elements/button',
						array(
							'variant'          => 'primary',
							'label'            => $lp_book['label'] ?: 'REQUEST 1:1',
							'href'             => $lp_book['href'],
							'trailing_icon_id' => 'icon-arrow-right',
						)
					);
					?>
				</span>
			</div>
		</div>

		<p class="font-label text-[11px] font-normal tracking-[0.2px] leading-[1.5] text-primary-content/70 m-0"><?php echo esc_html( $lp_reassure ); ?></p>
	</div>
	<?php
	$lp_offer_col = ob_get_clean();
	?>
<section class="<?php echo lp_classes( 'w-full bg-primary', $lp_spacing ); ?>" data-component="private-coaching" data-layout="booking"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col lg:flex-row lg:items-stretch">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with escaped content.
		echo 'end' === $lp_media_position ? $lp_offer_col . $lp_media_col : $lp_media_col . $lp_offer_col;
		?>
	</div>
</section>
	<?php
else :
	$lp_primary = lp_action( $args['primary_action'] ?? null ) ?? array(
		'label' => 'REQUEST A CALLBACK',
		'href'  => '',
	);

	$lp_ghost = lp_action( $args['secondary_action'] ?? null ) ?? array(
		'label' => 'HOW IT WORKS',
		'href'  => '',
	);
	?>
<section class="<?php echo lp_classes( 'w-full bg-base-200', $lp_spacing ); ?>" data-component="private-coaching" data-layout="offer"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col lg:flex-row lg:items-stretch">
		<div class="relative w-full aspect-[4/3] lg:aspect-auto lg:w-[568px] lg:shrink-0 overflow-hidden bg-base-300">
			<?php
			if ( $lp_media_id ) {
				$lp_photo = array(
					'image_id' => $lp_media_id,
					'size'     => 'lp_portrait',
					'sizes'    => '(min-width: 1024px) 568px, 100vw',
				);
				// Blank means inherit the attachment's own alt — an empty ACF
				// text field must not silently mark the photo decorative.
				if ( '' !== (string) ( $args['media_alt'] ?? '' ) ) {
					$lp_photo['alt'] = (string) $args['media_alt'];
				}
				lp_part( 'components/media-photo', $lp_photo );
			}
			?>
		</div>

		<div class="flex-1 min-w-0 flex flex-col px-6 py-[56px] lg:pt-[100px] lg:pr-[64px] lg:pb-[104px] lg:pl-[72px]">
			<div class="flex flex-col gap-[20px]">
				<span class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-base-content/60"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<h2 class="font-heading text-step-3 font-semibold leading-none tracking-[-1.6px] text-base-content"><?php echo esc_html( $lp_headline ); ?></h2>
			</div>

			<p class="mt-[26px] font-body text-[15px] font-normal tracking-[0.1px] leading-[1.65] text-base-content/75"><?php echo esc_html( $lp_body ); ?></p>

			<div class="mt-[38px] w-full border-t border-base-300 grid grid-cols-3">
				<?php foreach ( $lp_facts as $lp_fact ) : ?>
					<div class="pt-[14px] pr-[16px] flex flex-col gap-[7px] min-w-0">
						<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-base-content/60"><?php echo esc_html( (string) ( $lp_fact['label'] ?? '' ) ); ?></span>
						<span class="font-heading text-[15px] font-medium tracking-[-0.2px] text-base-content truncate"><?php echo esc_html( (string) ( $lp_fact['value'] ?? '' ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="mt-[40px] flex items-end gap-[12px] flex-wrap">
				<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-base-content/60"><?php echo esc_html( $lp_fare_label ); ?></span>
				<span class="font-heading text-[52px] font-semibold tracking-[-2px] leading-[0.9] text-base-content"><?php echo esc_html( $lp_amount ); ?></span>
				<span class="font-label text-[11px] font-normal tracking-[0.8px] uppercase text-base-content/60"><?php echo esc_html( $lp_unit ); ?></span>
			</div>

			<div class="mt-[30px] flex items-center gap-[14px] flex-wrap">
				<span>
					<?php
					lp_part(
						'elements/button',
						array(
							'variant'          => 'primary',
							'label'            => $lp_primary['label'],
							'href'             => $lp_primary['href'],
							'trailing_icon_id' => 'icon-arrow-right',
						)
					);
					?>
				</span>
				<span>
					<?php
					lp_part(
						'elements/button',
						array(
							'variant' => 'ghost',
							'label'   => $lp_ghost['label'],
							'href'    => $lp_ghost['href'],
						)
					);
					?>
				</span>
			</div>

			<p class="mt-[20px] font-label text-[11px] font-normal tracking-[0.2px] leading-[1.5] text-base-content/60"><?php echo esc_html( $lp_reassure ); ?></p>
		</div>
	</div>
</section>
	<?php
endif;
