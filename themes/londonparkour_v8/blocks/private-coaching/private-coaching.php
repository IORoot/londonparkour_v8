<?php
/**
 * PrivateCoaching — "04 — PRIVATE COACHING": the two-column offer strip.
 *
 * Ported from src/stories/Blocks/PrivateCoaching/PrivateCoaching.js.
 *
 * Repeater-only: the fact rail is this block's own copy, not entities.
 *
 * Ground is `bg-base-200` — a raised white panel, not the page's `base-100`.
 * The source's own fill token says raised panel; if this section is ever meant
 * to sit flush with the page ground it is a one-word swap.
 *
 * The fact rail is NOT components/fact-row.php. FactRow is a daisyUI
 * `stats` label/value pair at `font-body` 15px with a `/65` label at
 * `tracking-[0.9px]`; these keys are `font-label` semibold at
 * `tracking-[1.1px]` and `/60`, and the value is `font-heading` and truncates.
 * Same idea, different type — ported inline as the source has it.
 *
 * The source models each fact's top hairline as its own border; flush columns
 * render the three as one continuous line, so this is a single `border-t` on
 * the row — equivalent pixels, one rule instead of three.
 *
 * @param string $args['eyebrow']
 * @param string $args['headline']
 * @param string $args['standfirst']
 * @param array  $args['facts']            Rows of label/value.
 * @param string $args['fare_label']
 * @param string $args['amount']
 * @param string $args['unit']
 * @param array  $args['primary_action']
 * @param array  $args['secondary_action'] Rendered as the ghost button.
 * @param string $args['reassure']
 * @param int    $args['media']            Attachment id.
 * @param string $args['media_alt']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_facts = array(
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

$lp_eyebrow    = (string) ( $args['eyebrow'] ?? '04 — PRIVATE COACHING' );
$lp_headline   = (string) ( $args['headline'] ?? 'One coach. Just you.' );
$lp_body       = (string) ( $args['standfirst'] ?? "Private sessions move at your pace — whether that's a first wall you'd rather not meet in front of a group, a comeback after injury, or one specific line you've been stuck on for months. We come to any of our six sites, any day of the week." );
$lp_fare_label = (string) ( $args['fare_label'] ?? 'FROM' );
$lp_amount     = (string) ( $args['amount'] ?? '£75' );
$lp_unit       = (string) ( $args['unit'] ?? 'PER SESSION · BLOCKS AVAILABLE' );
$lp_reassure   = (string) ( $args['reassure'] ?? 'No commitment. We call back within one working day and match you to a coach.' );

$lp_primary = lp_action( $args['primary_action'] ?? null ) ?? array(
	'label' => 'REQUEST A CALLBACK',
	'href'  => '',
);

$lp_ghost = lp_action( $args['secondary_action'] ?? null ) ?? array(
	'label' => 'HOW IT WORKS',
	'href'  => '',
);

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

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full bg-base-200', $lp_spacing ); ?>" data-component="private-coaching"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
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
