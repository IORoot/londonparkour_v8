<?php
/**
 * SectionHead — the eyebrow + H2 + optional note that opens a section.
 *
 * Ported from src/stories/Components/SectionHead/SectionHead.js.
 *
 * The eyebrow composes elements/glyph-label rather than re-declaring its type
 * values — that is the critical reuse constraint, and it deliberately
 * overrides what the raw source eyebrow text nodes measure in favour of the
 * systemised label voice every other kicker renders as.
 *
 * The left-label + right-label row is a DIFFERENT shape and is NOT built here
 * — it is components/meta-row.php, placed directly by the section that needs
 * it. Sibling, not a variant.
 *
 * Heading colour follows `surface`: neutral-content on the fixed dark band,
 * base-content on the themed page ground — never the reverse.
 *
 * @param string $args['eyebrow']         Omit to render no eyebrow.
 * @param string $args['eyebrow_icon_id'] Optional leading glyph on the eyebrow.
 * @param string $args['eyebrow_tone']    signal|ink|muted. Defaults per surface.
 * @param string $args['heading']         Default 'Section headline.'
 * @param string $args['note']            Optional bottom-anchored note.
 * @param string $args['surface']         page|board|accent|fill. Default 'page'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'page'   => array(
		'heading'         => 'text-base-content',
		'note'            => 'text-base-content/65',
		'eyebrow_surface' => 'page',
		'eyebrow_tone'    => 'muted',
	),
	'board'  => array(
		'heading'         => 'text-neutral-content',
		'note'            => 'text-neutral-content/50',
		'eyebrow_surface' => 'board',
		'eyebrow_tone'    => 'signal',
	),
	// 'muted', not 'signal': glyph-label maps signal and ink to the same
	// text-accent-content on this ground, so there is no highlight to reach for.
	'accent' => array(
		'heading'         => 'text-accent-content',
		'note'            => 'text-accent-content/70',
		'eyebrow_surface' => 'accent',
		'eyebrow_tone'    => 'muted',
	),
	'fill'   => array(
		'heading'         => 'text-primary-content',
		'note'            => 'text-primary-content/70',
		'eyebrow_surface' => 'fill',
		'eyebrow_tone'    => 'muted',
	),
);

$lp_on_surface = $lp_surfaces[ $args['surface'] ?? 'page' ] ?? $lp_surfaces['page'];

$lp_eyebrow = (string) ( $args['eyebrow'] ?? '' );
$lp_heading = (string) ( $args['heading'] ?? 'Section headline.' );
$lp_note    = (string) ( $args['note'] ?? '' );
$lp_tone    = (string) ( $args['eyebrow_tone'] ?? '' );

if ( '' === $lp_tone ) {
	$lp_tone = $lp_on_surface['eyebrow_tone'];
}
?>
<div class="w-full flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8" data-component="section-head">
	<div class="flex flex-col gap-5 lg:max-w-[700px]">
		<?php
		if ( '' !== $lp_eyebrow ) {
			lp_part(
				'elements/glyph-label',
				array(
					'label'   => $lp_eyebrow,
					'icon_id' => $args['eyebrow_icon_id'] ?? '',
					'surface' => $lp_on_surface['eyebrow_surface'],
					'tone'    => $lp_tone,
				)
			);
		}
		?>
		<h2 class="<?php echo lp_classes( 'font-heading text-step-3 font-semibold leading-none tracking-[-1.6px]', $lp_on_surface['heading'] ); ?>"><?php echo esc_html( $lp_heading ); ?></h2>
	</div>
	<?php if ( '' !== $lp_note ) : ?>
		<p class="<?php echo lp_classes( 'w-full lg:w-[330px] text-left lg:text-right font-label text-[11px] leading-[1.6] tracking-[0.2px]', $lp_on_surface['note'] ); ?>"><?php echo esc_html( $lp_note ); ?></p>
	<?php endif; ?>
</div>
