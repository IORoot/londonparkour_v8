<?php
/**
 * Testimonials — "07 — TESTIMONIALS / IN THEIR WORDS": page-ground quote stack.
 *
 * Ported from src/stories/Blocks/Testimonials/Testimonials.js, then wired to
 * lp_testimonial (5-star, quote filled). Slot numerals 01–03 stay put; the
 * quote board rotates when the pool is larger than three. SEE ALL sits in
 * the old (03) meta slot. LEAVE A GOOGLE REVIEW stays the ghost button.
 *
 * Index numerals use `text-accent` on the page ground (never `text-primary`).
 *
 * @param string $args['eyebrow']
 * @param string $args['quote_source']  latest|random|choose.
 * @param array  $args['source_items']  Chosen lp_testimonial IDs.
 * @param array  $args['see_all_action']
 * @param array  $args['review_action']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_quotes = array(
	array(
		'quote'       => '“A brilliant mind and playful spirit — the ability to lead a group and set the mood in a room is unparalleled.”',
		'attribution' => 'JAMES R. / TRAINING SINCE 2018',
	),
	array(
		'quote'       => '“An inspiring, exceptional place to train. The coaching applies the same work ethic to a first-timer as to an athlete.”',
		'attribution' => 'PRIYA S. / FUNDAMENTALS STUDENT',
	),
	array(
		'quote'       => '“London Parkour changed how I move through the city.”',
		'attribution' => 'TOM H. / ADVANCED',
	),
);

$lp_eyebrow = (string) ( $args['eyebrow'] ?? '07 — TESTIMONIALS / IN THEIR WORDS' );

$lp_quotes = array();
if ( function_exists( 'lp_resolve_testimonial_quotes' ) ) {
	$lp_quotes = lp_resolve_testimonial_quotes( $args );
}
if ( ! $lp_quotes ) {
	$lp_quotes = $lp_default_quotes;
}

$lp_visible     = array_slice( $lp_quotes, 0, 3 );
$lp_can_rotate  = count( $lp_quotes ) > 3;
$lp_quotes_json = wp_json_encode( $lp_quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( false === $lp_quotes_json ) {
	$lp_quotes_json = '[]';
	$lp_can_rotate  = false;
}

$lp_see_all = lp_action( $args['see_all_action'] ?? null );
if ( ! $lp_see_all ) {
	$lp_see_all = array(
		'label'  => 'SEE ALL',
		'href'   => 'https://g.page/r/CaEUXmf0e4IHEBM',
		'target' => '_blank',
	);
} elseif ( '' === $lp_see_all['target'] ) {
	$lp_see_all['target'] = '_blank';
}

$lp_review = lp_action( $args['review_action'] ?? null );
if ( ! $lp_review ) {
	$lp_review = array(
		'label'  => 'LEAVE A GOOGLE REVIEW',
		'href'   => 'https://g.page/r/CaEUXmf0e4IHEBM/review',
		'target' => '_blank',
	);
} elseif ( '' === $lp_review['target'] ) {
	$lp_review['target'] = '_blank';
}

$lp_spacing = lp_section_spacing( $args );
$lp_last    = count( $lp_visible ) - 1;
?>
<section
	class="<?php echo lp_classes( 'w-full bg-base-100 px-6 py-[96px] lg:px-[72px]', $lp_spacing ); ?>"
	data-component="testimonials"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<div class="flex flex-col gap-14">
		<header class="flex flex-col gap-[18px]">
			<div class="flex items-baseline justify-between gap-4">
				<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<?php if ( '' !== $lp_see_all['href'] && '' !== $lp_see_all['label'] ) : ?>
					<a
						class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"
						href="<?php echo esc_url( $lp_see_all['href'] ); ?>"
						target="<?php echo esc_attr( $lp_see_all['target'] ); ?>"
						rel="noopener noreferrer"
					><?php echo esc_html( $lp_see_all['label'] ); ?></a>
				<?php endif; ?>
			</div>
			<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
		</header>

		<div
			class="flex flex-col gap-12"
			data-quote-board-list
			<?php if ( $lp_can_rotate ) : ?>
				data-motion-quote-board
				data-motion-quote-board-dwell="10"
				data-quotes="<?php echo esc_attr( $lp_quotes_json ); ?>"
			<?php endif; ?>
		>
			<?php
			foreach ( $lp_visible as $lp_i => $lp_q ) :
				$lp_index       = str_pad( (string) ( $lp_i + 1 ), 2, '0', STR_PAD_LEFT );
				$lp_quote       = (string) ( $lp_q['quote'] ?? '' );
				$lp_attribution = (string) ( $lp_q['attribution'] ?? '' );
				?>
				<blockquote class="flex flex-col sm:flex-row gap-6 sm:gap-[48px] items-start" data-component="testimonial-quote" data-quote-row>
					<span class="font-label text-[14px] font-semibold tracking-[0.4px] text-accent shrink-0 pt-1" data-quote-index><?php echo esc_html( $lp_index ); ?></span>
					<div class="flex flex-col gap-6 min-w-0">
						<p class="font-heading text-[28px] sm:text-[32px] font-medium leading-[1.2] tracking-[-0.6px] text-base-content m-0" data-quote-text><?php echo esc_html( $lp_quote ); ?></p>
						<?php
						$lp_attr_parts = explode( ' / ', $lp_attribution, 2 );
						$lp_name       = trim( (string) ( $lp_attr_parts[0] ?? '' ) );
						$lp_note       = trim( (string) ( $lp_attr_parts[1] ?? '' ) );
						?>
						<footer class="flex flex-wrap items-center gap-3 font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65">
							<span data-quote-name><?php echo esc_html( $lp_name ); ?></span>
							<span class="w-px h-2.5 bg-base-300 shrink-0" aria-hidden="true"></span>
							<span class="flex items-center gap-0.5" aria-label="5 out of 5 stars">
								<?php
								for ( $lp_star = 1; $lp_star <= 5; $lp_star++ ) {
									lp_icon( 'icon-star', 'w-3 h-3 text-accent' );
								}
								?>
							</span>
							<span data-quote-note<?php echo '' === $lp_note ? ' hidden' : ''; ?>><?php echo '' === $lp_note ? '' : esc_html( '/ ' . $lp_note ); ?></span>
						</footer>
					</div>
				</blockquote>
				<?php if ( (int) $lp_i !== $lp_last ) : ?>
					<div class="h-px w-full bg-base-300" aria-hidden="true" data-quote-rule></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<template data-quote-row-template>
			<blockquote class="flex flex-col sm:flex-row gap-6 sm:gap-[48px] items-start" data-component="testimonial-quote" data-quote-row>
				<span class="font-label text-[14px] font-semibold tracking-[0.4px] text-accent shrink-0 pt-1" data-quote-index></span>
				<div class="flex flex-col gap-6 min-w-0">
					<p
						class="font-heading text-[28px] sm:text-[32px] font-medium leading-[1.2] tracking-[-0.6px] text-base-content m-0"
						data-quote-text
						data-motion-decode-charset="board"
						data-motion-decode-wrap="true"
					></p>
					<footer class="flex flex-wrap items-center gap-3 font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65">
						<span data-quote-name></span>
						<span class="w-px h-2.5 bg-base-300 shrink-0" aria-hidden="true"></span>
						<span class="flex items-center gap-0.5" aria-label="5 out of 5 stars">
							<?php
							for ( $lp_star = 1; $lp_star <= 5; $lp_star++ ) {
								lp_icon( 'icon-star', 'w-3 h-3 text-accent' );
							}
							?>
						</span>
						<span data-quote-note hidden></span>
					</footer>
				</div>
			</blockquote>
		</template>
		<template data-quote-rule-template>
			<div class="h-px w-full bg-base-300" aria-hidden="true" data-quote-rule></div>
		</template>
		<?php if ( '' !== $lp_review['href'] && '' !== $lp_review['label'] ) : ?>
			<?php
			lp_part(
				'elements/button',
				array(
					'variant'          => 'ghost',
					'label'            => $lp_review['label'],
					'href'             => $lp_review['href'],
					'target'           => $lp_review['target'],
					'trailing_icon_id' => 'icon-arrow-right',
				)
			);
			?>
		<?php endif; ?>
	</div>
</section>
