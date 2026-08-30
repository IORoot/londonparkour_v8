<?php
/**
 * VideoStage — the player stage: status bar, scrimmed frame with a play
 * control, progress, and a time/up-next bar.
 *
 * Ported from src/stories/Components/VideoStage/VideoStage.js.
 *
 * The play control is elements/icon-circle.php variant '78' (the interactive
 * one, docs/CONSOLIDATION.md §2a); the two overlay chips are elements/chip.php
 * variant `dark`; the photo and its wash are media-photo's `video_stage` scrim.
 *
 * DELIBERATE DEPARTURE: the now-playing dot is NOT routed through
 * elements/status.php, contrary to §2d. That atom's dot is daisyUI
 * `status status-sm` (8px) at gap-2 with a 10px label; this one is a 6px dot
 * at gap-[9px] with a 12px/600 label. See docs/PORT-FINDINGS.md — routing it
 * would silently change three separate designs.
 *
 * @param int    $args['image_id']       Attachment id — enables srcset.
 * @param string $args['image_url']      Raw URL fallback.
 * @param string $args['image_alt']      Omit to inherit the attachment's alt.
 * @param string $args['status_label']
 * @param string $args['quality_label']
 * @param string $args['badge_label']    Top-left overlay chip.
 * @param string $args['duration_label'] Top-right overlay chip.
 * @param string $args['title']
 * @param string $args['stage_meta']
 * @param int    $args['progress']       0–100, clamped.
 * @param string $args['time_label']
 * @param string $args['up_next_label']
 * @param string $args['play_aria_label'] Defaults to "Play: {title}".
 * @param string $args['command']         Play control dialog trigger.
 * @param string $args['command_for']
 * @param array  $args['data_attrs']      Extra data-* on the play control.
 *
 * Mobile: the stage is `aspect-video` (16:9) with `object-contain`, matching
 * Storybook VideoStage.js. A min-height on a different ratio either overflows
 * the viewport or crops the still.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_image_id  = ! empty( $args['image_id'] ) ? (int) $args['image_id'] : 0;
$lp_image_url = (string) ( $args['image_url'] ?? '' );
$lp_has_image = $lp_image_id || '' !== $lp_image_url;

$lp_status_label   = (string) ( $args['status_label'] ?? 'NOW PLAYING · INTRO' );
$lp_quality_label  = (string) ( $args['quality_label'] ?? 'EN · HD · 03:12' );
$lp_badge_label    = (string) ( $args['badge_label'] ?? 'CAT LEAP · LESSON 01 OF 04' );
$lp_duration_label = (string) ( $args['duration_label'] ?? '03:12' );
$lp_title          = (string) ( $args['title'] ?? 'Introduction' );
$lp_stage_meta     = (string) ( $args['stage_meta'] ?? 'CLIMBING · ARM-JUMP · BEGINNER' );
$lp_time_label     = (string) ( $args['time_label'] ?? '00:47 / 03:12' );
$lp_up_next_label  = (string) ( $args['up_next_label'] ?? 'UP NEXT — 02 THE LANDING' );

$lp_progress  = min( 100, max( 0, (int) ( $args['progress'] ?? 23 ) ) );
$lp_play_aria = (string) ( $args['play_aria_label'] ?? '' );

if ( '' === $lp_play_aria ) {
	$lp_play_aria = 'Play: ' . $lp_title;
}

$lp_photo = array(
	'image_id'  => $lp_image_id,
	'image_url' => $lp_image_url,
	'scrim'     => 'video_stage',
	'layout'    => 'contain',
	'size'      => 'lp_wide_lg',
	'sizes'     => '100vw',
);

if ( array_key_exists( 'image_alt', $args ) ) {
	$lp_photo['alt'] = (string) $args['image_alt'];
}
?>
<div class="relative w-full" data-component="video-stage">
	<div class="flex flex-wrap items-center justify-between gap-2 py-[15px] px-[22px]">
		<div class="flex items-center gap-[9px]">
			<span class="inline-block w-[6px] h-[6px] rounded-full bg-primary" aria-hidden="true"></span>
			<span class="font-label text-[12px] font-semibold tracking-[1px] uppercase text-base-content"><?php echo esc_html( $lp_status_label ); ?></span>
		</div>
		<span class="font-label text-[10px] font-normal tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_quality_label ); ?></span>
	</div>

	<div class="relative w-full min-w-0 max-w-full aspect-video overflow-hidden bg-neutral cursor-pointer">
		<?php
		if ( $lp_has_image ) {
			lp_part( 'components/media-photo', $lp_photo );
		} else {
			// The wash belongs to the stage, not the photo, so it stays either way.
			?>
			<div class="absolute inset-0 bg-secondary/45" aria-hidden="true"></div>
			<?php
		}
		?>
		<div class="relative z-10 flex h-full flex-col justify-between p-4 lg:p-[26px]">
			<div class="flex items-center justify-between gap-2">
				<span>
					<?php
					lp_part(
						'elements/chip',
						array(
							'variant' => 'dark',
							'label'   => $lp_badge_label,
						)
					);
					?>
				</span>
				<span>
					<?php
					lp_part(
						'elements/chip',
						array(
							'variant' => 'dark',
							'label'   => $lp_duration_label,
						)
					);
					?>
				</span>
			</div>
			<div class="flex flex-1 items-center justify-center">
				<?php
				lp_part(
					'elements/icon-circle',
					array(
						'variant'     => '78',
						'icon_id'     => 'icon-play',
						'aria_label'  => $lp_play_aria,
						'command'     => $args['command'] ?? '',
						'command_for' => $args['command_for'] ?? '',
						'data_attrs'  => is_array( $args['data_attrs'] ?? null ) ? $args['data_attrs'] : array(),
					)
				);
				?>
			</div>
			<div class="flex flex-col gap-[7px]">
				<p class="font-heading text-[26px] font-semibold tracking-[-0.6px] text-neutral-content"><?php echo esc_html( $lp_title ); ?></p>
				<p class="font-label text-[10px] font-normal tracking-[0.9px] text-neutral-content/70"><?php echo esc_html( $lp_stage_meta ); ?></p>
			</div>
		</div>
	</div>

	<progress class="progress progress-primary h-[3px] w-full block rounded-none" value="<?php echo esc_attr( (string) $lp_progress ); ?>" max="100" aria-hidden="true"></progress>

	<div class="flex flex-wrap items-center justify-between gap-2 py-[15px] px-[22px]">
		<span class="font-label text-[11px] font-semibold tracking-[0.8px] text-base-content"><?php echo esc_html( $lp_time_label ); ?></span>
		<span class="font-label text-[10px] font-normal tracking-[0.9px] text-base-content/65"><?php echo esc_html( $lp_up_next_label ); ?></span>
	</div>
</div>
