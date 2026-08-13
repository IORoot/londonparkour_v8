<?php
/**
 * Testimonials — "07 — TESTIMONIALS / IN THEIR WORDS": page-ground quote stack.
 *
 * Ported from src/stories/Blocks/Testimonials/Testimonials.js.
 *
 * Repeater-only. Index numerals use `text-accent` on the page ground (never
 * `text-primary`) — surface-axis signal role on light grounds.
 *
 * Copy defaults transcribed from the Storybook source (pen leaves under
 * `g6OHme`), not from phase7 inventories.
 *
 * @param string $args['eyebrow']
 * @param string $args['meta']
 * @param array  $args['quotes']         Rows of index/quote/attribution.
 * @param array  $args['review_action']  ACF action group — Google Business write-review.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_quotes = array(
	array(
		'index'       => '01',
		'quote'       => '“A brilliant mind and playful spirit — the ability to lead a group and set the mood in a room is unparalleled.”',
		'attribution' => 'JAMES R. / TRAINING SINCE 2018',
	),
	array(
		'index'       => '02',
		'quote'       => '“An inspiring, exceptional place to train. The coaching applies the same work ethic to a first-timer as to an athlete.”',
		'attribution' => 'PRIYA S. / FUNDAMENTALS STUDENT',
	),
	array(
		'index'       => '03',
		'quote'       => '“London Parkour changed how I move through the city.”',
		'attribution' => 'TOM H. / ADVANCED',
	),
);

$lp_eyebrow = (string) ( $args['eyebrow'] ?? '07 — TESTIMONIALS / IN THEIR WORDS' );
$lp_meta    = (string) ( $args['meta'] ?? '(03)' );

$lp_quotes = array();
foreach ( is_array( $args['quotes'] ?? null ) ? $args['quotes'] : array() as $lp_row ) {
	if ( ! is_array( $lp_row ) ) {
		continue;
	}
	if ( '' !== (string) ( $lp_row['quote'] ?? '' ) || '' !== (string) ( $lp_row['attribution'] ?? '' ) ) {
		$lp_quotes[] = $lp_row;
	}
}
if ( ! $lp_quotes ) {
	$lp_quotes = $lp_default_quotes;
}

$lp_review = lp_action( $args['review_action'] ?? null );
if ( ! $lp_review ) {
	$lp_review = array(
		'label'  => 'LEAVE A GOOGLE REVIEW',
		'href'   => 'https://g.page/r/CY-t6mExHHvoEAI/review',
		'target' => '_blank',
	);
} elseif ( '' === $lp_review['target'] ) {
	$lp_review['target'] = '_blank';
}

$lp_spacing = lp_section_spacing( $args );
$lp_last    = count( $lp_quotes ) - 1;
?>
<section
	class="<?php echo lp_classes( 'w-full bg-base-100 px-6 py-16 lg:px-[72px] lg:py-[96px]', $lp_spacing ); ?>"
	data-component="testimonials"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<div class="flex flex-col gap-14">
		<header class="flex flex-col gap-[18px]">
			<div class="flex items-baseline justify-between gap-4">
				<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<?php if ( '' !== $lp_meta ) : ?>
					<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_meta ); ?></span>
				<?php endif; ?>
			</div>
			<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
		</header>

		<div class="flex flex-col gap-12">
			<?php
			foreach ( $lp_quotes as $lp_i => $lp_q ) :
				$lp_index       = (string) ( $lp_q['index'] ?? '' );
				$lp_quote       = (string) ( $lp_q['quote'] ?? '' );
				$lp_attribution = (string) ( $lp_q['attribution'] ?? '' );
				?>
				<blockquote class="flex flex-col sm:flex-row gap-6 sm:gap-[48px] items-start" data-component="testimonial-quote">
					<span class="font-label text-[14px] font-semibold tracking-[0.4px] text-accent shrink-0 pt-1"><?php echo esc_html( $lp_index ); ?></span>
					<div class="flex flex-col gap-6 min-w-0">
						<p class="font-heading text-[28px] sm:text-[32px] font-medium leading-[1.2] tracking-[-0.6px] text-base-content m-0"><?php echo esc_html( $lp_quote ); ?></p>
						<footer class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_attribution ); ?></footer>
					</div>
				</blockquote>
				<?php if ( (int) $lp_i !== $lp_last ) : ?>
					<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
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
