<?php
/**
 * Pricing — Concourse coupon fare board.
 *
 * Ported from src/stories/Blocks/Pricing/Pricing.js (node `k4hV1` /
 * homepage `Rf8Qz`): DROP-IN / 5-PACK / 10-PACK on a PRICE PER CLASS axis,
 * with SESSIONS / SAVING comparison rows. Each tier label leads with a
 * `#glyph-icons` mark (`glyph-step` / `glyph-flowing` / `glyph-chaining`).
 * The worked-out rate cell is a tight `gap-[7px]` pair, not `justify-between`.
 *
 * Repeater-only: two lists, `row_labels` (the left rail) and `tiers` (the
 * columns). Each tier carries a `values` repeater whose rows are keyed by
 * `row_key`, so a tier's cells stay attached to the right row when the rail is
 * reordered — the source's `values: { sessions: … }` map, expressed in ACF.
 * A row with no matching value renders the source's em dash.
 *
 * DELIBERATE DEPARTURE: the source gives each tier its own `cta.variant`, but
 * its data only ever pairs the highlighted column with the solid button and
 * the other two with ghost. The variant is derived from `highlight` here and
 * the CTA is a plain Link field — one control instead of two that must agree.
 *
 * Ground is `bg-base-200` (an elevated surface), and every foreground is the
 * themed `base-content` family, so this section is safe in all four themes.
 * The "— MOST POPULAR" annotations are `text-accent`, NOT `text-primary`:
 * on the page ground primary measures 1.54:1 / 1.27:1 in the light themes.
 *
 * Board layout uses one CSS grid with `grid-rows-subgrid` columns so the left
 * rail and every tier share the same row tracks — flex columns cannot keep
 * PRICE PER CLASS / SESSIONS / SAVING / CTA lined up when the work-out cell or
 * button is taller than the matching rail cell.
 *
 * Below `sm` the table scrolls inside its own `overflow-x-auto` wrapper rather
 * than making the page scroll sideways. The rail is hidden below `sm`.
 *
 * @param string $args['eyebrow']
 * @param string $args['heading']
 * @param string $args['note']
 * @param string $args['kicker']      Left-rail head (SB `kicker`).
 * @param string $args['subkicker']   Left-rail second line (SB `subkicker`).
 * @param string $args['axis']        First rail row under the head (SB `axis`).
 * @param array  $args['row_labels']  Rows of row_key/label.
 * @param array  $args['tiers']       The columns.
 * @param string $args['notice']
 * @param array  $args['guarantee']   kicker/copy.
 * @param string $args['sites_lead']
 * @param string $args['sites_list']
 * @param string $args['kit_note']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_row_value       = 'font-label text-[11px] font-normal tracking-[0.4px] text-base-content';
$lp_row_value_muted = 'font-label text-[11px] font-normal tracking-[0.4px] text-base-content/65';
$lp_row_label       = 'font-label text-[10px] font-normal tracking-[0.9px] uppercase text-base-content/65';

$lp_default_row_labels = array(
	array(
		'row_key' => 'sessions',
		'label'   => 'SESSIONS',
	),
	array(
		'row_key' => 'saving',
		'label'   => 'SAVING',
	),
);

$lp_default_tiers = array(
	array(
		'label'          => 'DROP-IN',
		'glyph_icon_id'  => 'glyph-step',
		'price'          => '£15',
		'unit'           => 'per session',
		'description'    => 'One class credit. Buy online, book any session at Vauxhall, Old Street or Kilburn Park.',
		'work_out_value' => '£15.00',
		'work_out_unit'  => 'a class',
		'cta'            => array(
			'title' => 'BUY DROP-IN',
			'url'   => '#',
		),
		'values'         => array(
			array(
				'row_key' => 'sessions',
				'value'   => 'One',
			),
			array(
				'row_key' => 'saving',
				'value'   => '—',
			),
		),
	),
	array(
		'label'          => '5-PACK',
		'badge'          => 'MOST POPULAR',
		'glyph_icon_id'  => 'glyph-flowing',
		'highlight'      => true,
		'price'          => '£65',
		'unit'           => 'for 5 classes',
		'description'    => 'Five classes, bought once. Use them when you want — no membership.',
		'work_out_value' => '£13.00',
		'work_out_unit'  => 'a class',
		'cta'            => array(
			'title' => 'BUY 5 CLASSES',
			'url'   => '#',
		),
		'values'         => array(
			array(
				'row_key' => 'sessions',
				'value'   => '5 classes',
			),
			array(
				'row_key' => 'saving',
				'value'   => '13% vs drop-in',
			),
		),
	),
	array(
		'label'          => '10-PACK',
		'badge'          => 'BEST VALUE',
		'glyph_icon_id'  => 'glyph-chaining',
		'price'          => '£120',
		'unit'           => 'for 10 classes',
		'description'    => 'Ten classes at the best rate we offer. Buy once, book when you want.',
		'work_out_value' => '£12.00',
		'work_out_unit'  => 'a class',
		'cta'            => array(
			'title' => 'BUY 10 CLASSES',
			'url'   => '#',
		),
		'values'         => array(
			array(
				'row_key' => 'sessions',
				'value'   => '10 classes',
			),
			array(
				'row_key' => 'saving',
				'value'   => '20% vs drop-in',
			),
		),
	),
);

$lp_eyebrow    = (string) ( $args['eyebrow'] ?? '03 — COUPON SALE' );
$lp_heading    = (string) ( $args['heading'] ?? 'No contract. Ever.' );
$lp_note       = (string) ( $args['note'] ?? 'Coupons work at Vauxhall, Old Street and Kilburn Park. Buy once, book when you want.' );
$lp_kicker     = (string) ( $args['kicker'] ?? 'COUPON SALE' );
$lp_subkicker  = (string) ( $args['subkicker'] ?? 'WHAT YOU GET' );
$lp_axis       = (string) ( $args['axis'] ?? 'PRICE PER CLASS' );
$lp_notice     = (string) ( $args['notice'] ?? 'PRICES HELD UNTIL 1 APRIL 2027' );
$lp_sites_lead = (string) ( $args['sites_lead'] ?? 'EVERY COUPON WORKS AT ALL THREE SITES' );
$lp_sites_list = (string) ( $args['sites_list'] ?? 'VAUXHALL · OLD STREET · KILBURN PARK' );
$lp_kit_note   = (string) ( $args['kit_note'] ?? 'JUST TRAINERS — NO SPECIALIST KIT NEEDED' );

$lp_guarantee   = is_array( $args['guarantee'] ?? null ) ? $args['guarantee'] : array();
$lp_guar_kicker = (string) ( $lp_guarantee['kicker'] ?? 'HOW IT WORKS' );
$lp_guar_copy   = (string) ( $lp_guarantee['copy'] ?? 'Buy a coupon, book any class at Vauxhall, Old Street or Kilburn Park. No membership required.' );

$lp_row_labels = array();

foreach ( is_array( $args['row_labels'] ?? null ) ? $args['row_labels'] : array() as $lp_row ) {
	if ( ! empty( $lp_row['label'] ) ) {
		$lp_row_labels[] = $lp_row;
	}
}

if ( ! $lp_row_labels ) {
	$lp_row_labels = $lp_default_row_labels;
}

$lp_tiers = array();

foreach ( is_array( $args['tiers'] ?? null ) ? $args['tiers'] : array() as $lp_tier ) {
	if ( ! empty( $lp_tier['label'] ) || ! empty( $lp_tier['price'] ) ) {
		$lp_tiers[] = $lp_tier;
	}
}

if ( ! $lp_tiers ) {
	$lp_tiers = $lp_default_tiers;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'bg-base-200 px-6 md:px-16 pt-[100px] pb-[104px]', $lp_spacing ); ?>" data-component="pricing"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col gap-[40px]">
		<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-[24px]">
			<div class="flex flex-col gap-[16px]">
				<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-base-content/65"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<h2 class="font-heading text-step-3 font-semibold tracking-[-1px] text-base-content max-w-[700px]"><?php echo esc_html( $lp_heading ); ?></h2>
			</div>
			<?php if ( '' !== $lp_note ) : ?>
				<p class="font-body text-step--2 text-base-content/65 max-w-[300px]"><?php echo esc_html( $lp_note ); ?></p>
			<?php endif; ?>
		</div>

		<?php
		$lp_tier_count = max( count( $lp_tiers ), 1 );
		// bar + header + workOut/axis + row labels + footer
		$lp_board_rows = 3 + count( $lp_row_labels ) + 1;
		$lp_board_style = sprintf(
			'--pricing-tiers: %d; grid-template-rows: 3px repeat(%d, auto)',
			$lp_tier_count,
			$lp_board_rows - 1
		);
		?>
		<div class="flex flex-col gap-[24px]">
			<div class="overflow-x-auto">
				<div
					class="grid min-w-[900px] sm:min-w-0 border border-base-300/60 grid-cols-[repeat(var(--pricing-tiers),minmax(280px,1fr))] sm:grid-cols-[196px_repeat(var(--pricing-tiers),minmax(0,1fr))]"
					style="<?php echo esc_attr( $lp_board_style ); ?>"
					data-slot="pricing-board"
				>
					<div class="hidden sm:grid sm:row-span-full sm:grid-rows-subgrid border-r border-base-300/60" data-slot="pricing-rail">
						<div class="bg-base-300" aria-hidden="true"></div>
						<div class="flex flex-col gap-[8px] pt-[24px] pb-[36px] px-[24px]">
							<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-base-content/65"><?php echo esc_html( $lp_kicker ); ?></span>
							<?php if ( '' !== $lp_subkicker ) : ?>
								<span class="font-label text-[10px] font-normal tracking-[0.9px] uppercase text-base-content/65"><?php echo esc_html( $lp_subkicker ); ?></span>
							<?php endif; ?>
						</div>
						<div class="py-[11px] px-[24px] border-t border-base-300/60 min-h-[36px] flex items-center">
							<span class="<?php echo esc_attr( $lp_row_label ); ?>"><?php echo esc_html( $lp_axis ); ?></span>
						</div>
						<?php foreach ( $lp_row_labels as $lp_row ) : ?>
							<div class="py-[11px] px-[24px] border-t border-base-300/60 min-h-[36px] flex items-center">
								<span class="<?php echo esc_attr( $lp_row_label ); ?>"><?php echo esc_html( (string) ( $lp_row['label'] ?? '' ) ); ?></span>
							</div>
						<?php endforeach; ?>
						<div class="pt-[16px] px-[24px] pb-[24px] flex items-end">
							<p class="font-label text-[10px] font-normal tracking-[0.7px] uppercase text-base-content/65"><?php echo esc_html( $lp_notice ); ?></p>
						</div>
					</div>
					<?php
					$lp_tier_last = count( $lp_tiers ) - 1;
					foreach ( $lp_tiers as $lp_index => $lp_tier ) :
						$lp_highlight = ! empty( $lp_tier['highlight'] );
						$lp_bar       = $lp_highlight ? 'bg-primary' : 'bg-base-300';
						$lp_wash      = $lp_highlight ? 'bg-primary/8' : '';
						$lp_edge      = $lp_index === $lp_tier_last ? '' : 'border-r border-base-300/60';
						$lp_value_cls = $lp_highlight ? $lp_row_value : $lp_row_value_muted;
						// The source hand-writes an id per tier; the label yields the
						// same slugs ('DROP-IN' → 'drop-in', '5-PACK' → '5-pack') without another field.
						$lp_tier_id   = sanitize_title( (string) ( $lp_tier['label'] ?? '' ) ) ?: 'tier-' . $lp_index;
						$lp_badge     = (string) ( $lp_tier['badge'] ?? '' );
						$lp_glyph_id  = (string) ( $lp_tier['glyph_icon_id'] ?? '' );
						$lp_unit      = (string) ( $lp_tier['unit'] ?? '' );
						$lp_desc      = (string) ( $lp_tier['description'] ?? '' );
						$lp_work_val  = (string) ( $lp_tier['work_out_value'] ?? '' );
						$lp_work_unit = (string) ( $lp_tier['work_out_unit'] ?? '' );
						$lp_cta       = lp_action( $lp_tier['cta'] ?? null );

						// The source keys each tier's cells by row; ACF stores them
						// as rows, so index them back by row_key.
						$lp_values = array();
						foreach ( is_array( $lp_tier['values'] ?? null ) ? $lp_tier['values'] : array() as $lp_cell ) {
							$lp_values[ (string) ( $lp_cell['row_key'] ?? '' ) ] = (string) ( $lp_cell['value'] ?? '' );
						}
						?>
						<div class="<?php echo lp_classes( 'row-span-full grid grid-rows-subgrid w-[280px] sm:w-auto', $lp_wash, $lp_edge ); ?>" data-component="pricing-tier" data-tier="<?php echo esc_attr( $lp_tier_id ); ?>">
							<div class="<?php echo esc_attr( $lp_bar ); ?>" aria-hidden="true"></div>
							<div class="flex flex-col gap-[20px] pt-[24px] pb-[36px] px-[28px]">
								<div class="flex items-center gap-[10px] flex-wrap">
									<?php if ( '' !== $lp_glyph_id ) : ?>
										<?php lp_icon( $lp_glyph_id, 'w-[18px] h-[18px] text-base-content/65', array( 'data-slot' => 'glyph' ) ); ?>
									<?php endif; ?>
									<span class="font-label text-[11px] font-semibold tracking-[0.6px] uppercase text-base-content"><?php echo esc_html( (string) ( $lp_tier['label'] ?? '' ) ); ?></span>
									<?php if ( '' !== $lp_badge ) : ?>
										<span class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-accent">— <?php echo esc_html( $lp_badge ); ?></span>
									<?php endif; ?>
								</div>
								<div class="flex items-end gap-[8px]">
									<span class="font-heading text-step-3 font-semibold tracking-[-1px] text-base-content"><?php echo esc_html( (string) ( $lp_tier['price'] ?? '' ) ); ?></span>
									<?php if ( '' !== $lp_unit ) : ?>
										<span class="font-label text-[11px] font-normal tracking-[0.4px] text-base-content/65 pb-[6px]"><?php echo esc_html( $lp_unit ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( '' !== $lp_desc ) : ?>
									<p class="font-body text-[11px] leading-[1.5] text-base-content/65 max-w-[280px]"><?php echo esc_html( $lp_desc ); ?></p>
								<?php endif; ?>
							</div>
							<div class="flex items-center gap-[7px] py-[11px] px-[28px] border-t border-base-300/60 min-h-[36px]" data-slot="work-out">
								<?php if ( '' !== $lp_work_val ) : ?>
									<span class="font-heading text-[17px] font-medium text-base-content"><?php echo esc_html( $lp_work_val ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $lp_work_unit ) : ?>
									<span class="font-label text-[11px] font-normal text-base-content/65"><?php echo esc_html( $lp_work_unit ); ?></span>
								<?php endif; ?>
							</div>
							<?php foreach ( $lp_row_labels as $lp_row ) : ?>
								<?php $lp_key = (string) ( $lp_row['row_key'] ?? '' ); ?>
								<div class="flex items-center py-[11px] px-[28px] border-t border-base-300/60 min-h-[36px]" data-row="<?php echo esc_attr( $lp_key ); ?>">
									<span class="<?php echo esc_attr( $lp_value_cls ); ?>"><?php echo esc_html( '' !== ( $lp_values[ $lp_key ] ?? '' ) ? $lp_values[ $lp_key ] : '—' ); ?></span>
								</div>
							<?php endforeach; ?>
							<div class="pt-[16px] px-[28px] pb-[24px] flex items-end">
								<?php
								$lp_pack_id = absint( $lp_tier['pack'] ?? 0 );
								$lp_cta_label = $lp_cta['label'] ?? '';
								if ( $lp_pack_id > 0 ) {
									$lp_buy = lp_pack_buy_button_args(
										$lp_pack_id,
										$lp_cta_label ?: 'BUY',
										$lp_highlight ? 'primary' : 'ghost'
									);
									lp_part(
										'elements/button',
										array_merge(
											$lp_buy,
											array(
												'class' => 'w-full',
											)
										)
									);
								} elseif ( $lp_cta ) {
									lp_part(
										'elements/button',
										array(
											'variant' => $lp_highlight ? 'primary' : 'ghost',
											'label'   => $lp_cta['label'],
											'href'    => $lp_cta['href'],
											'class'   => 'w-full',
										)
									);
								}
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="flex items-start gap-[20px] border-l-[3px] border-primary py-[8px] pl-[20px]">
				<div class="flex flex-col gap-[8px]">
					<?php if ( '' !== $lp_guar_kicker ) : ?>
						<span class="font-label text-[10px] font-normal tracking-[0.7px] uppercase text-base-content/65"><?php echo esc_html( $lp_guar_kicker ); ?></span>
					<?php endif; ?>
					<p class="font-heading text-step-0 text-base-content"><?php echo esc_html( $lp_guar_copy ); ?></p>
				</div>
			</div>

			<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-[10px] pt-[16px] border-t border-base-300/60">
				<p class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-base-content/65">
					<span><?php echo esc_html( $lp_sites_lead ); ?></span>
					<span class="text-base-content/65 mx-2" aria-hidden="true">·</span>
					<span><?php echo esc_html( $lp_sites_list ); ?></span>
				</p>
				<p class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-base-content/65"><?php echo esc_html( $lp_kit_note ); ?></p>
			</div>
		</div>
	</div>
</section>
