<?php
/**
 * AsidePanel — the booking panel: title + spots, fact rows, CTA band, note.
 *
 * Ported from src/stories/Components/AsidePanel/AsidePanel.js.
 *
 * DELIBERATE DEPARTURES:
 * 1. The CTA goes through elements/button.php variant `band`, closing the gap
 *    docs/CONSOLIDATION.md §1b recorded rather than hand-rolling it here.
 * 2. The source puts role="button" on the anchor form of that CTA. Port Brief
 *    rule 7 forbids it outright — an <a href> is a link — so it is dropped.
 * 3. The spots-left dot is NOT routed through elements/status.php, contrary to
 *    §2d. That atom's dot is daisyUI `status status-sm` (8px) with a fixed
 *    text-primary label; this one is a 6px dot with an `ink`-toned label. See
 *    docs/PORT-FINDINGS.md — routing it would silently change the design.
 *
 * @param string $args['title']       Default 'TAKE A SLOT'.
 * @param string $args['spots_left']  Optional — renders the dot + count.
 * @param array  $args['rows']        array of array( 'label' => …, 'value' => … ).
 * @param string $args['cta_label']
 * @param string $args['cta_icon_id'] Default 'icon-arrow-right'.
 * @param string $args['href']        Renders the CTA as an <a>.
 * @param string $args['command']     @tailwindplus/elements dialog trigger.
 * @param string $args['command_for']
 * @param array  $args['data_attrs']  Passed through to elements/button.php.
 * @param string $args['note']        Optional foot note.
 * @param string $args['surface']     board|page. Default 'board'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'board' => array(
		'root'          => 'bg-neutral border-neutral-content/10',
		'header_border' => 'border-neutral-content/[.18]',
		'row_border'    => 'border-neutral-content/[.06]',
		'signal'        => 'text-primary',
		'dot'           => 'bg-primary',
		'ink'           => 'text-neutral-content',
		'muted'         => 'text-neutral-content/50',
	),
	'page'  => array(
		'root'          => 'bg-base-200 border-base-300',
		'header_border' => 'border-base-content',
		'row_border'    => 'border-base-300',
		'signal'        => 'text-accent',
		'dot'           => 'bg-accent',
		'ink'           => 'text-base-content',
		'muted'         => 'text-base-content/65',
	),
);

$lp_default_rows = array(
	array(
		'label' => 'NEXT SESSION',
		'value' => 'Thu 30 Jul · 18:30',
	),
	array(
		'label' => 'SITE',
		'value' => 'Vauxhall — The Arches',
	),
	array(
		'label' => 'LEVEL',
		'value' => 'Level 1 · Beginner',
	),
	array(
		'label' => 'PRICE',
		'value' => '£15 drop-in',
	),
);

$lp_surf = $lp_surfaces[ $args['surface'] ?? 'board' ] ?? $lp_surfaces['board'];

$lp_title      = (string) ( $args['title'] ?? 'TAKE A SLOT' );
$lp_spots_left = (string) ( $args['spots_left'] ?? '' );
$lp_rows       = is_array( $args['rows'] ?? null ) ? $args['rows'] : $lp_default_rows;
$lp_cta_label  = (string) ( $args['cta_label'] ?? 'BOOK THIS SESSION' );
$lp_note       = (string) ( $args['note'] ?? 'Free to cancel up to 12 hours before. All kit provided.' );
?>
<div class="<?php echo lp_classes( 'flex flex-col w-full max-w-none lg:max-w-[380px]', $lp_surf['root'] ); ?>" data-component="aside-panel">
	<div class="<?php echo lp_classes( 'flex items-center justify-between border-b', $lp_surf['header_border'], 'px-[22px] py-[16px]' ); ?>">
		<span class="<?php echo lp_classes( 'font-label text-[12px] font-semibold uppercase tracking-[1px]', $lp_surf['signal'] ); ?>"><?php echo esc_html( $lp_title ); ?></span>
		<?php if ( '' !== $lp_spots_left ) : ?>
			<span class="inline-flex items-center gap-[8px]">
				<span class="<?php echo lp_classes( 'inline-block w-[6px] h-[6px] rounded-full', $lp_surf['dot'] ); ?>" aria-hidden="true"></span>
				<span class="<?php echo lp_classes( 'font-label text-[10px] font-semibold uppercase tracking-[0.8px]', $lp_surf['ink'] ); ?>"><?php echo esc_html( $lp_spots_left ); ?></span>
			</span>
		<?php endif; ?>
	</div>
	<?php foreach ( $lp_rows as $lp_row ) : ?>
		<div class="<?php echo lp_classes( 'flex items-center justify-between gap-[20px] border-b', $lp_surf['row_border'], 'px-[22px] py-[14px]' ); ?>" data-row>
			<span class="<?php echo lp_classes( 'font-label text-[10px] font-normal uppercase tracking-[0.9px]', $lp_surf['muted'] ); ?>"><?php echo esc_html( (string) ( $lp_row['label'] ?? '' ) ); ?></span>
			<span class="<?php echo lp_classes( 'font-heading text-[15px] font-medium tracking-[-0.2px]', $lp_surf['ink'] ); ?>"><?php echo esc_html( (string) ( $lp_row['value'] ?? '' ) ); ?></span>
		</div>
	<?php endforeach; ?>
	<?php
	lp_part(
		'elements/button',
		array(
			'variant'          => 'band',
			'label'            => $lp_cta_label,
			'href'             => $args['href'] ?? '',
			'trailing_icon_id' => $args['cta_icon_id'] ?? 'icon-arrow-right',
			'command'          => $args['command'] ?? '',
			'command_for'      => $args['command_for'] ?? '',
			'data_attrs'       => is_array( $args['data_attrs'] ?? null ) ? $args['data_attrs'] : array(),
		)
	);
	?>
	<?php if ( '' !== $lp_note ) : ?>
		<div class="px-[22px] py-[14px]">
			<p class="<?php echo lp_classes( 'font-body text-[10px] leading-[1.6] tracking-[0.3px]', $lp_surf['muted'], 'm-0' ); ?>"><?php echo esc_html( $lp_note ); ?></p>
		</div>
	<?php endif; ?>
</div>
