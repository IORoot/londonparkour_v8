<?php
/**
 * Dialog Video — YouTube / Vimeo / HTML5 overlay.
 *
 * Behaviour matches Storybook Elements/Dialog/Video (DialogVideo.js +
 * command="show-modal"), but the shell follows this theme's working
 * el-dialog pattern (see lp_clasbpro_booking_drawer) — daisyUI `modal` /
 * `modal-box` classes are not compiled into the theme CSS.
 *
 * Opened by a button with command="show-modal" commandfor="{dialog_id}"
 * plus data-video-type / data-video-id (or data-video-url for html5).
 * DialogVideo.js mounts the player on open and stops it on close.
 *
 * @param string $args['dialog_id']  Native <dialog> id the trigger targets.
 * @param string $args['video_type'] youtube|vimeo|html5. Default youtube.
 * @param string $args['title']      Optional heading under the player.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_dialog_id  = (string) ( $args['dialog_id'] ?? 'video-dialog' );
$lp_video_type = (string) ( $args['video_type'] ?? 'youtube' );
$lp_title      = (string) ( $args['title'] ?? '' );
?>
<el-dialog data-video-dialog="<?php echo esc_attr( $lp_video_type ); ?>" data-element="dialog" data-template="video">
	<dialog
		id="<?php echo esc_attr( $lp_dialog_id ); ?>"
		aria-label="<?php esc_attr_e( 'Class video', 'londonparkour_v8' ); ?>"
		class="fixed inset-0 z-50 m-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent p-4 open:flex open:items-center open:justify-center"
	>
		<button
			type="button"
			command="close"
			commandfor="<?php echo esc_attr( $lp_dialog_id ); ?>"
			class="absolute inset-0 z-0 cursor-default bg-neutral/70 backdrop-blur-[7px]"
			aria-label="<?php esc_attr_e( 'Close video', 'londonparkour_v8' ); ?>"
		></button>
		<el-dialog-panel class="relative z-10 block w-full max-w-5xl overflow-hidden bg-neutral border border-neutral-content/10 shadow-xl">
			<button
				type="button"
				command="close"
				commandfor="<?php echo esc_attr( $lp_dialog_id ); ?>"
				class="absolute right-2 top-2 z-20 btn btn-sm btn-circle btn-ghost text-neutral-content"
				aria-label="<?php esc_attr_e( 'Close video', 'londonparkour_v8' ); ?>"
			>✕</button>
			<div class="relative bg-secondary aspect-video">
				<div class="video-player w-full h-full"></div>
			</div>
			<?php if ( '' !== $lp_title ) : ?>
				<div class="px-[22px] py-[16px]">
					<h3 class="font-heading text-[18px] font-semibold tracking-[-0.3px] text-neutral-content m-0"><?php echo esc_html( $lp_title ); ?></h3>
				</div>
			<?php endif; ?>
		</el-dialog-panel>
	</dialog>
</el-dialog>
