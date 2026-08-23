<?php
/**
 * TranscriptLine — timestamp + spoken line.
 *
 * Ported from src/stories/Components/TranscriptLine/TranscriptLine.js
 * (C3PAZ5, Tutorials/Detail — Transcript). Board classes are byte-identical
 * to that source. `page` remaps the two ink roles onto the page ground
 * (muted floor `base-content/65`, body `base-content`) so the same row can
 * sit in the Resources accordion without using board tokens on a light rail.
 *
 * Seek buttons are omitted here: there is no player hook on this surface,
 * matching the Storybook default when `onSeek` is not passed.
 *
 * @param string $args['timestamp'] mm:ss clock. Empty hides the stamp.
 * @param string $args['text']
 * @param string $args['surface']   board|page. Default 'board'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'board' => array(
		'stamp' => 'shrink-0 font-mono text-[12px] font-semibold tracking-[0.6px] text-neutral-content/50',
		'text'  => 'm-0 font-body text-[14px] font-normal leading-[1.55] tracking-[0.1px] text-neutral-content/90',
	),
	'page'  => array(
		'stamp' => 'shrink-0 font-mono text-[12px] font-semibold tracking-[0.6px] text-base-content/65',
		'text'  => 'm-0 font-body text-[14px] font-normal leading-[1.55] tracking-[0.1px] text-base-content',
	),
);

$lp_tone      = $lp_surfaces[ $args['surface'] ?? 'board' ] ?? $lp_surfaces['board'];
$lp_timestamp = (string) ( $args['timestamp'] ?? '00:00' );
$lp_text      = (string) ( $args['text'] ?? 'The cat leap — or arm jump — is how you catch and hold onto a ledge.' );
?>
<div class="flex items-baseline gap-[18px]" data-component="transcript-line">
	<?php if ( '' !== $lp_timestamp ) : ?>
		<span class="<?php echo esc_attr( $lp_tone['stamp'] ); ?>"><?php echo esc_html( $lp_timestamp ); ?></span>
	<?php endif; ?>
	<p class="<?php echo esc_attr( $lp_tone['text'] ); ?>"><?php echo esc_html( $lp_text ); ?></p>
</div>
