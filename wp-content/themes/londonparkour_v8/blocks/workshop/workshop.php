<?php
/**
 * Workshop — homepage "06 Workshop" full-bleed promotional spotlight.
 *
 * Ported from Pencil node `YSrxs` ("06 Workshop — Concourse").
 * Class strings are copied byte-for-byte from
 * src/stories/Blocks/Workshop/Workshop.js.
 *
 * Queries the soonest upcoming one-off workshop via lp_class_workshops_split().
 * Renders nothing when no upcoming workshop exists.
 *
 * Background image: editor-supplied override from ACF `workshop_image`, falling
 * back to the workshop's featured image via lp_class_image_id(). If neither
 * is set, the gradient renders over a black background — acceptable but editors
 * should always supply an image for this section.
 *
 * CTA: inline `bg-neutral-content text-neutral` button — no existing variant
 * in parts/elements/button.php matches this paper/dark pairing. Noted as a
 * candidate for a future `paper` variant in that part.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// ── Workshop data ────────────────────────────────────────────────────────────

$split = lp_class_workshops_split();
$lead  = $split['lead'] ?? null;
if ( ! ( $lead instanceof WP_Post ) ) {
	return; // No upcoming workshop — render nothing.
}

$lead_id = (int) $lead->ID;

// Title and description.
$title       = get_the_title( $lead_id );
$description = (string) ( get_field( 'acf_logline', $lead_id ) ?? '' );

// Session data: date, time, urgency.
$sessions   = lp_class_upcoming_sessions( $lead_id, 1 );
$session    = $sessions[0] ?? null;
$date_label = $session ? (string) ( $session['date_label'] ?? '' ) : lp_class_workshop_date_label( $lead_id );
$time_label = $session ? (string) ( $session['time'] ?? '' ) : '';
$sold_out   = $session && ! empty( $session['sold_out'] );
$remaining  = $session ? (int) ( $session['remaining'] ?? 0 ) : 0;
$capacity   = $session ? (int) ( $session['capacity'] ?? 0 ) : 0;

// Urgency label — only shown when 10 or fewer places remain.
$spaces_label = '';
if ( $sold_out || $remaining <= 0 ) {
	// $sold_out flag handles the chip display, $spaces_label stays empty.
} elseif ( $remaining <= 10 ) {
	$places       = 1 === $remaining ? 'PLACE' : 'PLACES';
	$spaces_label = sprintf( '%d %s LEFT', $remaining, $places );
}

// Location.
$location_id   = lp_class_location_id( $lead_id );
$location_name = $location_id ? strtoupper( (string) get_the_title( $location_id ) ) : '';

// CTA href → workshop detail page.
$cta_href = (string) get_permalink( $lead_id );

// Background image: ACF override > class featured image.
$override_image_id = (int) ( ( $args['workshop_image']['ID'] ?? 0 ) ?: 0 );
$bg_image_id       = $override_image_id ?: lp_class_image_id( $lead_id );
$bg_image_src      = $bg_image_id ? (string) wp_get_attachment_image_url( $bg_image_id, 'full' ) : '';
$bg_image_alt      = $bg_image_id ? (string) get_post_meta( $bg_image_id, '_wp_attachment_image_alt', true ) : '';
if ( ! $bg_image_alt ) {
	$bg_image_alt = esc_attr( $title ) . ' — London Parkour Workshop';
}

// Eyebrow (editor override or default).
$eyebrow = lp_section_label( (string) ( $args['eyebrow'] ?? '' ) ?: '06 — WORKSHOPS', $args['_section_number'] ?? null );
?>

<section
	class="relative overflow-hidden w-full aspect-[16/9] min-h-[640px] flex flex-col justify-end"
	data-component="workshop"
>

	<?php if ( $bg_image_src ) : ?>
		<img
			src="<?php echo esc_url( $bg_image_src ); ?>"
			alt="<?php echo esc_attr( $bg_image_alt ); ?>"
			class="absolute inset-0 w-full h-full object-cover"
			loading="lazy"
		/>
	<?php endif; ?>

	<div
		class="absolute inset-0 bg-gradient-to-t from-neutral via-neutral/90 to-transparent"
		aria-hidden="true"
	></div>

	<div class="relative z-10 px-6 lg:px-16 pb-14 flex flex-col gap-5">

		<span class="font-label text-[11px] font-semibold tracking-[1.5px] uppercase text-primary">
			<?php echo esc_html( $eyebrow ); ?>
		</span>

		<h2 class="font-display text-[38px] lg:text-[57px] font-bold leading-none tracking-[-0.04em] text-neutral-content m-0">
			<?php echo esc_html( $title ); ?>
		</h2>

		<div class="flex items-center gap-3 flex-wrap">

			<?php if ( $date_label ) : ?>
				<span class="inline-flex items-center py-[5px] px-[9px] bg-primary font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-primary-content">
					<?php echo esc_html( $date_label ); ?><?php echo $time_label ? ' · ' . esc_html( $time_label ) : ''; ?>
				</span>
			<?php endif; ?>

			<?php if ( $location_name ) : ?>
				<span class="inline-flex items-center py-[5px] px-[9px] bg-primary font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-primary-content">
					<?php echo esc_html( $location_name ); ?>
				</span>
			<?php endif; ?>

			<?php if ( $sold_out ) : ?>
				<span class="inline-flex items-center font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50">
					SOLD OUT
				</span>
			<?php elseif ( $spaces_label ) : ?>
				<span class="inline-flex items-center gap-[9px] font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-primary">
					<span class="w-[6px] h-[6px] rounded-full bg-primary shrink-0" aria-hidden="true"></span>
					<?php echo esc_html( $spaces_label ); ?>
				</span>
			<?php endif; ?>

		</div>

		<?php if ( $description ) : ?>
			<p class="font-body text-[16px] leading-[1.55] text-neutral-content/75 max-w-[560px] m-0">
				<?php echo esc_html( $description ); ?>
			</p>
		<?php endif; ?>

		<a
			href="<?php echo esc_url( $cta_href ); ?>"
			class="inline-flex items-center gap-3 self-start py-[15px] px-6 bg-neutral-content text-neutral font-label text-[12px] font-semibold tracking-[1px] uppercase hover:bg-neutral hover:text-primary transition-colors duration-150 no-underline"
		>
			BOOK YOUR PLACE
			<?php lp_icon( 'icon-arrow-right', 'w-3.5 h-3.5 shrink-0' ); ?>
		</a>

	</div>
</section>
