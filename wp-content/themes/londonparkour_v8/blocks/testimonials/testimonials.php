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
 * @param string $args['quote_source']  latest|random|choose. Default random.
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

$lp_eyebrow = lp_section_label( (string) ( $args['eyebrow'] ?? '07 — TESTIMONIALS / IN THEIR WORDS' ), $args['_section_number'] ?? null );

$lp_quotes = array();
if ( function_exists( 'lp_resolve_testimonial_quotes' ) ) {
	$lp_quotes = lp_resolve_testimonial_quotes( $args );
}
$lp_allow_placeholders = ! array_key_exists( 'allow_placeholders', $args ) || ! empty( $args['allow_placeholders'] );
if ( ! $lp_quotes ) {
	if ( ! $lp_allow_placeholders ) {
		return;
	}
	$lp_quotes = $lp_default_quotes;
}

$lp_limit       = isset( $args['quote_limit'] ) ? max( 1, (int) $args['quote_limit'] ) : 3;
$lp_visible     = array_slice( $lp_quotes, 0, $lp_limit );
$lp_rotate      = ! array_key_exists( 'rotate', $args ) || ! empty( $args['rotate'] );
$lp_can_rotate  = $lp_rotate && count( $lp_quotes ) > 3;
$lp_quotes_json = wp_json_encode( $lp_quotes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( false === $lp_quotes_json ) {
	$lp_quotes_json = '[]';
	$lp_can_rotate  = false;
}

$lp_show_see_all = ! array_key_exists( 'show_see_all', $args ) || ! empty( $args['show_see_all'] );
$lp_see_all      = lp_action( $args['see_all_action'] ?? null );
if ( ! $lp_show_see_all ) {
	$lp_see_all = array(
		'label'  => '',
		'href'   => '',
		'target' => '',
	);
} elseif ( ! $lp_see_all ) {
	$lp_see_all = array(
		'label'  => 'SEE ALL',
		'href'   => 'https://g.page/r/CaEUXmf0e4IHEBM',
		'target' => '_blank',
	);
} elseif ( '' === $lp_see_all['target'] ) {
	$lp_see_all['target'] = '_blank';
}
$lp_meta = $lp_show_see_all ? '' : sprintf( '(%02d)', count( $lp_visible ) );

$lp_band = isset( $args['surface'] ) && 'band' === $args['surface'];
if ( $lp_band ) {
	$lp_section_class = 'w-full bg-neutral px-6 py-[120px] lg:px-[72px]';
	$lp_meta_class    = 'font-label text-[12px] font-normal tracking-[0.5px] uppercase text-neutral-content/65';
	$lp_rule_class    = 'h-px w-full bg-neutral-content/20';
	$lp_index_class   = 'font-label text-[14px] font-semibold tracking-[0.4px] text-primary shrink-0 pt-1';
	$lp_quote_class   = 'font-heading text-[28px] sm:text-[32px] font-medium leading-[1.2] tracking-[-0.6px] text-neutral-content m-0';
	$lp_footer_class  = 'flex flex-wrap items-center gap-3 font-label text-[12px] font-normal tracking-[0.5px] uppercase text-neutral-content/65';
	$lp_bar_class     = 'w-px h-2.5 bg-neutral-content/20 shrink-0';
	$lp_star_class    = 'w-3 h-3 text-primary';
	$lp_surface       = 'band';
} else {
	$lp_section_class = 'w-full bg-base-100 px-6 py-[120px] lg:px-[72px]';
	$lp_meta_class    = 'font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65';
	$lp_rule_class    = 'h-px w-full bg-base-300';
	$lp_index_class   = 'font-label text-[14px] font-semibold tracking-[0.4px] text-accent shrink-0 pt-1';
	$lp_quote_class   = 'font-heading text-[28px] sm:text-[32px] font-medium leading-[1.2] tracking-[-0.6px] text-base-content m-0';
	$lp_footer_class  = 'flex flex-wrap items-center gap-3 font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65';
	$lp_bar_class     = 'w-px h-2.5 bg-base-300 shrink-0';
	$lp_star_class    = 'w-3 h-3 text-accent';
	$lp_surface       = 'page';
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
	class="<?php echo lp_classes( $lp_section_class, $lp_spacing ); ?>"
	data-component="testimonials"
	data-surface="<?php echo esc_attr( $lp_surface ); ?>"
	<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<div class="flex flex-col gap-14">
		<header class="flex flex-col gap-[18px]">
			<div class="flex items-baseline justify-between gap-4">
				<span class="<?php echo esc_attr( $lp_meta_class ); ?>"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<?php if ( '' !== $lp_see_all['href'] && '' !== $lp_see_all['label'] ) : ?>
					<a
						class="<?php echo esc_attr( $lp_meta_class ); ?>"
						href="<?php echo esc_url( $lp_see_all['href'] ); ?>"
						target="<?php echo esc_attr( $lp_see_all['target'] ); ?>"
						rel="noopener noreferrer"
					><?php echo esc_html( $lp_see_all['label'] ); ?></a>
				<?php elseif ( '' !== $lp_meta ) : ?>
					<span class="<?php echo esc_attr( $lp_meta_class ); ?>"><?php echo esc_html( $lp_meta ); ?></span>
				<?php endif; ?>
			</div>
			<div class="<?php echo esc_attr( $lp_rule_class ); ?>" aria-hidden="true"></div>
		</header>

		<div class="flex flex-col gap-12">
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
					<span class="<?php echo esc_attr( $lp_index_class ); ?>" data-quote-index><?php echo esc_html( $lp_index ); ?></span>
					<div class="flex flex-col gap-6 min-w-0">
						<p class="<?php echo esc_attr( $lp_quote_class ); ?>" data-quote-text><?php echo esc_html( $lp_quote ); ?></p>
						<?php
						$lp_attr_parts = explode( ' / ', $lp_attribution, 2 );
						$lp_name       = trim( (string) ( $lp_attr_parts[0] ?? '' ) );
						$lp_note       = trim( (string) ( $lp_attr_parts[1] ?? '' ) );
						?>
						<footer class="<?php echo esc_attr( $lp_footer_class ); ?>">
							<span data-quote-name><?php echo esc_html( $lp_name ); ?></span>
							<span class="<?php echo esc_attr( $lp_bar_class ); ?>" aria-hidden="true"></span>
							<span class="flex items-center gap-0.5" role="img" aria-label="5 out of 5 stars">
								<?php
								for ( $lp_star = 1; $lp_star <= 5; $lp_star++ ) {
									lp_icon( 'icon-star', $lp_star_class );
								}
								?>
							</span>
							<span data-quote-note<?php echo '' === $lp_note ? ' hidden' : ''; ?>><?php echo '' === $lp_note ? '' : esc_html( '/ ' . $lp_note ); ?></span>
						</footer>
					</div>
				</blockquote>
				<?php if ( (int) $lp_i !== $lp_last ) : ?>
					<div class="<?php echo esc_attr( $lp_rule_class ); ?>" aria-hidden="true" data-quote-rule></div>
				<?php endif; ?>
			<?php endforeach; ?>
			</div>
			<?php if ( $lp_can_rotate ) : ?>
			<div
				class="relative h-0.5 w-full bg-base-300 overflow-hidden"
				data-quote-board-loader
				aria-hidden="true"
			>
				<div class="absolute inset-0 origin-left bg-accent" data-quote-board-loader-fill style="transform: scaleX(0)"></div>
			</div>
			<?php endif; ?>
		</div>
		<template data-quote-row-template>
			<blockquote class="flex flex-col sm:flex-row gap-6 sm:gap-[48px] items-start" data-component="testimonial-quote" data-quote-row>
				<span class="<?php echo esc_attr( $lp_index_class ); ?>" data-quote-index></span>
				<div class="flex flex-col gap-6 min-w-0">
					<p
						class="<?php echo esc_attr( $lp_quote_class ); ?>"
						data-quote-text
						data-motion-decode-charset="board"
						data-motion-decode-wrap="true"
					></p>
					<footer class="<?php echo esc_attr( $lp_footer_class ); ?>">
						<span data-quote-name></span>
						<span class="<?php echo esc_attr( $lp_bar_class ); ?>" aria-hidden="true"></span>
						<span class="flex items-center gap-0.5" role="img" aria-label="5 out of 5 stars">
							<?php
							for ( $lp_star = 1; $lp_star <= 5; $lp_star++ ) {
								lp_icon( 'icon-star', $lp_star_class );
							}
							?>
						</span>
						<span data-quote-note hidden></span>
					</footer>
				</div>
			</blockquote>
		</template>
		<template data-quote-rule-template>
			<div class="<?php echo esc_attr( $lp_rule_class ); ?>" aria-hidden="true" data-quote-rule></div>
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
