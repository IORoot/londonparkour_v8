<?php
/**
 * Statement — "02 — WHY WE DO IT": the manifesto section.
 *
 * Ported from src/stories/Blocks/Statement/Statement.js.
 *
 * Repeater-only: the three principles are the block's own copy, not entities.
 *
 * Composes components/meta-row.php for the eyebrow row and elements/rule.php
 * (tone hairline) for the divider, exactly as the source mounts them.
 *
 * The principle marks are BRAND GLYPHS (`glyph-*`), so lp_icon() serves them
 * from glyphs.svg rather than the icon sprite — it routes on the id prefix.
 *
 * @param string $args['eyebrow']    Left of the meta row.
 * @param string $args['since']      Right of the meta row.
 * @param string $args['statement']  The large headline.
 * @param string $args['quote']
 * @param string $args['signature']
 * @param array  $args['principles'] Rows of icon_id/label/body.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_principles = array(
	array(
		'icon_id' => 'glyph-understanding',
		'label'   => 'UNDERSTANDING FIRST',
		'body'    => 'We teach you to read an obstacle before you ever touch it. The movement is the easy part.',
	),
	array(
		'icon_id' => 'glyph-challenge',
		'label'   => 'CHALLENGE BY CHOICE',
		'body'    => 'Nothing here is compulsory. You set the height, the gap and the risk — every single session.',
	),
	array(
		'icon_id' => 'glyph-teamwork',
		'label'   => 'NOBODY TRAINS ALONE',
		'body'    => 'Every session is coached and capped at twelve, so the room learns at the pace of the room.',
	),
);

$lp_eyebrow   = (string) ( $args['eyebrow'] ?? '02 — WHY WE DO IT' );
$lp_since     = (string) ( $args['since'] ?? 'SINCE 2015' );
$lp_statement = (string) ( $args['statement'] ?? "We don't teach tricks. We teach people to trust what their body can already do." );
$lp_quote     = (string) ( $args['quote'] ?? "Most people arrive convinced they're not the athletic type. Six weeks later they're vaulting a rail they used to walk around. That shift — from avoiding obstacles to reading them — is the whole point." );
$lp_signature = (string) ( $args['signature'] ?? '— Andy Pearson, Head Coach' );

$lp_principles = array();

foreach ( is_array( $args['principles'] ?? null ) ? $args['principles'] : array() as $lp_row ) {
	if ( ! empty( $lp_row['label'] ) || ! empty( $lp_row['body'] ) ) {
		$lp_principles[] = $lp_row;
	}
}

if ( ! $lp_principles ) {
	$lp_principles = $lp_default_principles;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'bg-base-100 pt-[104px] px-16 pb-[96px]', $lp_spacing ); ?>" data-component="statement"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col">
		<?php
		lp_part(
			'components/meta-row',
			array(
				'left'  => $lp_eyebrow,
				'right' => $lp_since,
			)
		);
		lp_part( 'elements/rule', array( 'tone' => 'hairline' ) );
		?>

		<div class="mt-[56px] flex flex-col lg:flex-row lg:items-start gap-10 lg:gap-[80px]">
			<h2 class="flex-1 min-w-0 font-heading text-step-4 font-semibold leading-[0.95] tracking-[-2.4px] text-base-content"><?php echo esc_html( $lp_statement ); ?></h2>
			<div class="w-full lg:w-[380px] lg:shrink-0 flex flex-col gap-6">
				<p class="font-body text-[14px] leading-[1.65] tracking-[0.2px] text-base-content/65"><?php echo esc_html( $lp_quote ); ?></p>
				<cite class="not-italic font-body text-[12px] font-semibold tracking-[0.5px] text-base-content"><?php echo esc_html( $lp_signature ); ?></cite>
			</div>
		</div>

		<div class="mt-[88px] flex flex-col lg:flex-row gap-10 lg:gap-[72px]">
			<?php foreach ( $lp_principles as $lp_principle ) : ?>
				<div class="flex-1 flex flex-col gap-[16px] border-t border-base-content pt-[24px]">
					<div class="flex items-center gap-[10px] text-base-content">
						<?php lp_icon( (string) ( $lp_principle['icon_id'] ?? 'glyph-understanding' ), 'w-[24px] h-[24px] text-current' ); ?>
						<span class="font-label text-[11px] font-semibold uppercase tracking-[1px]"><?php echo esc_html( (string) ( $lp_principle['label'] ?? '' ) ); ?></span>
					</div>
					<p class="font-body text-[13px] leading-[1.65] tracking-[0.1px] text-base-content/65"><?php echo esc_html( (string) ( $lp_principle['body'] ?? '' ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
