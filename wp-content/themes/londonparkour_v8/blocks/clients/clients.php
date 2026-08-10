<?php
/**
 * Clients — "06 — CLIENTS / TRUSTED BY": accent logo grid of text wordmarks.
 *
 * Ported from src/stories/Blocks/Clients/Clients.js.
 *
 * Repeater-only. Cell labels are plain text in the source (not image logos).
 * Ground is `bg-accent`; ink / muted / hairline follow the accent column of
 * docs/phase7/surface-axis.md (`accent-content`). The 1px channel is
 * `gap-px` on an `accent-content/15` track — same as the Storybook source.
 *
 * @param string $args['eyebrow']
 * @param string $args['meta']
 * @param array  $args['logos'] Rows of array( 'label' => … ).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_logos = array(
	'NIKE',
	'UCL',
	'BBC',
	'RED BULL',
	'PARKOUR UK',
	'ADIDAS',
	'GOOGLE',
	'SKY',
	'O2',
	'ITV',
	'PUMA',
	'HSBC',
);

$lp_eyebrow = (string) ( $args['eyebrow'] ?? '06 — CLIENTS / TRUSTED BY' );
$lp_meta    = (string) ( $args['meta'] ?? '(12)' );

$lp_logos = array();
foreach ( is_array( $args['logos'] ?? null ) ? $args['logos'] : array() as $lp_row ) {
	$lp_label = is_array( $lp_row ) ? (string) ( $lp_row['label'] ?? '' ) : (string) $lp_row;
	if ( '' !== $lp_label ) {
		$lp_logos[] = $lp_label;
	}
}
if ( ! $lp_logos ) {
	$lp_logos = $lp_default_logos;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section
	class="<?php echo lp_classes( 'w-full bg-accent px-6 py-[72px] lg:px-16', $lp_spacing ); ?>"
	data-component="clients"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
>
	<div class="flex flex-col gap-[36px]">
		<header class="flex flex-col gap-[18px]">
			<div class="flex items-baseline justify-between gap-4">
				<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-accent-content/70"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<?php if ( '' !== $lp_meta ) : ?>
					<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-accent-content/70"><?php echo esc_html( $lp_meta ); ?></span>
				<?php endif; ?>
			</div>
			<div class="h-px w-full bg-accent-content/15" aria-hidden="true"></div>
		</header>

		<div
			class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-px bg-accent-content/15"
			role="list"
			aria-label="<?php echo esc_attr__( 'Trusted by', 'londonparkour_v8' ); ?>"
		>
			<?php foreach ( $lp_logos as $lp_logo ) : ?>
				<div role="listitem">
					<div
						class="flex items-center justify-center min-h-[112px] px-3 bg-accent"
						data-component="clients-logo"
					>
						<span class="font-label text-[14px] sm:text-[16px] font-semibold tracking-[1.2px] uppercase text-accent-content text-center leading-none"><?php echo esc_html( $lp_logo ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
