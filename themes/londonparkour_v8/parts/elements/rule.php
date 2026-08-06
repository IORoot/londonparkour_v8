<?php
/**
 * Rule — a 1px hairline with an optional caption.
 *
 * Ported from src/stories/Elements/Rule/Rule.js. Semantically a divider, so the
 * wrapper carries role="separator"; the line itself is decorative because the
 * caption, when present, already carries the meaning as text.
 *
 * Tones are the combinations the design actually contains, as whole literals:
 *   ink      full base-content on the page ground (default)
 *   hairline faint base-300 on the page ground
 *   board    neutral-content on the fixed dark band, where a base-content line
 *            is invisible in both light themes
 *   accent   accent-content on a bg-accent band
 *
 * @param string $args['tone']    ink|hairline|board|accent.
 * @param string $args['caption'] Optional trailing label.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_tones = array(
	'ink'      => array(
		'line'    => 'flex-1 h-px bg-base-content',
		'caption' => 'font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65 whitespace-nowrap',
	),
	'hairline' => array(
		'line'    => 'flex-1 h-px bg-base-300',
		'caption' => 'font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65 whitespace-nowrap',
	),
	'board'    => array(
		'line'    => 'flex-1 h-px bg-neutral-content/20',
		'caption' => 'font-label text-[10px] font-normal tracking-[0.8px] text-neutral-content/50 whitespace-nowrap',
	),
	'accent'   => array(
		'line'    => 'flex-1 h-px bg-accent-content/15',
		'caption' => 'font-label text-[10px] font-normal tracking-[0.8px] text-accent-content/70 whitespace-nowrap',
	),
);

$lp_tone    = $lp_tones[ $args['tone'] ?? 'ink' ] ?? $lp_tones['ink'];
$lp_caption = (string) ( $args['caption'] ?? '' );
?>
<div class="flex items-center gap-[10px]" data-component="rule" role="separator" aria-orientation="horizontal">
	<span class="<?php echo esc_attr( $lp_tone['line'] ); ?>" aria-hidden="true"></span>
	<?php if ( '' !== $lp_caption ) : ?>
		<span class="<?php echo esc_attr( $lp_tone['caption'] ); ?>"><?php echo esc_html( $lp_caption ); ?></span>
	<?php endif; ?>
</div>
