<?php
/**
 * SitePanel — the expanded training-site record: kicker + name, meeting point,
 * transport, and a code/count foot.
 *
 * Ported from src/stories/Components/SitePanel/SitePanel.js.
 *
 * `kind` drives the leading glyph and its tone, mirroring LocationCard's table
 * (the same source nodes). An explicit `kicker_icon_id` overrides the glyph but
 * not the tone, exactly as the source does.
 *
 * @param string $args['kicker']
 * @param string $args['kicker_icon_id'] Overrides the glyph chosen by `kind`.
 * @param string $args['kind']           indoor|outdoor. Unset uses the accent tone.
 * @param string $args['name']
 * @param string $args['streetview_href']  Renders the streetview link when set.
 * @param string $args['streetview_label']
 * @param string $args['meeting_point']
 * @param string $args['transport_rail']
 * @param string $args['transport_bus']
 * @param string $args['code']
 * @param string $args['count']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_kinds = array(
	'indoor'  => array(
		'icon_id'     => 'icon-home',
		'glyph_class' => 'text-primary',
	),
	'outdoor' => array(
		'icon_id'     => 'icon-map-pin',
		'glyph_class' => 'text-base-content',
	),
);

$lp_kind = $lp_kinds[ $args['kind'] ?? '' ] ?? null;

$lp_glyph_icon  = (string) ( $args['kicker_icon_id'] ?? '' );
$lp_glyph_class = $lp_kind ? $lp_kind['glyph_class'] : 'text-accent';

if ( '' === $lp_glyph_icon ) {
	$lp_glyph_icon = $lp_kind ? $lp_kind['icon_id'] : 'icon-map-pin';
}

$lp_kicker            = (string) ( $args['kicker'] ?? 'INDOOR · FLAGSHIP' );
$lp_name              = (string) ( $args['name'] ?? 'Vauxhall.' );
$lp_streetview_href   = (string) ( $args['streetview_href'] ?? '' );
$lp_streetview_label  = (string) ( $args['streetview_label'] ?? 'STREETVIEW ↗' );
$lp_meeting_point     = (string) ( $args['meeting_point'] ?? 'Outside Tube station exit 2, next to metal pillars and open area.' );
$lp_transport_rail    = (string) ( $args['transport_rail'] ?? 'Victoria Underground Line · Vauxhall (South Western Railway)' );
$lp_transport_bus     = (string) ( $args['transport_bus'] ?? 'Note: Oval Northern Line is walking distance. Buses — 2 · 36 · 87 · 88 · 156 · 185 · 196 · 344 · 436' );
$lp_code              = (string) ( $args['code'] ?? 'SW8 1SR · 4 MIN FROM VAUXHALL' );
$lp_count             = (string) ( $args['count'] ?? '3 CLASSES' );
?>
<div class="flex flex-col gap-[22px] w-full bg-base-200 border-t border-base-300 pt-[22px] pb-[34px]" data-component="site-panel">
	<div class="flex flex-wrap items-end justify-between gap-[16px]">
		<div class="flex flex-col gap-[9px]">
			<div class="flex items-center gap-[8px]">
				<?php lp_icon( $lp_glyph_icon, lp_classes( 'w-[12px] h-[12px]', $lp_glyph_class ) ); ?>
				<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65"><?php echo esc_html( $lp_kicker ); ?></span>
			</div>
			<p class="font-heading text-[36px] font-bold leading-none tracking-[-1.4px] text-base-content m-0"><?php echo esc_html( $lp_name ); ?></p>
		</div>
		<?php if ( '' !== $lp_streetview_href ) : ?>
			<a href="<?php echo esc_url( $lp_streetview_href ); ?>" class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-accent whitespace-nowrap"><?php echo esc_html( $lp_streetview_label ); ?></a>
		<?php endif; ?>
	</div>
	<div class="flex flex-wrap gap-[40px]">
		<div class="flex-1 min-w-[200px] flex flex-col gap-[10px]">
			<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">MEETING POINT</span>
			<p class="font-body text-[12px] font-medium leading-[1.6] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp_meeting_point ); ?></p>
		</div>
		<div class="flex-1 min-w-[200px] flex flex-col gap-[10px]">
			<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65">TRANSPORT</span>
			<p class="font-body text-[12px] font-medium leading-[1.6] tracking-[0.1px] text-base-content m-0"><?php echo esc_html( $lp_transport_rail ); ?></p>
			<p class="font-body text-[11px] leading-[1.6] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp_transport_bus ); ?></p>
		</div>
	</div>
	<div class="flex items-center justify-between border-t border-base-300 pt-[13px]">
		<span class="font-label text-[10px] font-medium uppercase tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_code ); ?></span>
		<span class="font-label text-[10px] font-bold uppercase tracking-[0.9px] text-base-content"><?php echo esc_html( $lp_count ); ?></span>
	</div>
</div>
