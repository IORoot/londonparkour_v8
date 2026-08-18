<?php
/**
 * Dialog Image — tile + lightbox for a still.
 *
 * Same el-dialog shell as elements/dialog-video.php. The tile is a real
 * <button command="show-modal"> wrapping media-photo — button.php has no
 * image-tile variant, so the invoker lives here.
 *
 * @param string $args['dialog_id'] Required native <dialog> id.
 * @param int    $args['image_id']  Attachment id.
 * @param string $args['alt']       Optional. Falls back to the attachment alt.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_dialog_id = (string) ( $args['dialog_id'] ?? '' );
$lp_image_id  = (int) ( $args['image_id'] ?? 0 );
if ( '' === $lp_dialog_id || $lp_image_id <= 0 ) {
	return;
}

$lp_alt = array_key_exists( 'alt', $args )
	? (string) $args['alt']
	: (string) get_post_meta( $lp_image_id, '_wp_attachment_image_alt', true );
$lp_open_label = '' !== $lp_alt
	? sprintf( /* translators: %s: image alt text */ __( 'Open image: %s', 'londonparkour_v8' ), $lp_alt )
	: __( 'Open image', 'londonparkour_v8' );
?>
<button
	type="button"
	command="show-modal"
	commandfor="<?php echo esc_attr( $lp_dialog_id ); ?>"
	class="relative aspect-[4/3] bg-secondary overflow-hidden w-full p-0 border-0 cursor-pointer text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
	aria-label="<?php echo esc_attr( $lp_open_label ); ?>"
>
	<?php
	lp_part(
		'components/media-photo',
		array(
			'image_id' => $lp_image_id,
			'layout'   => 'fill',
			'size'     => 'lp_wide',
			'sizes'    => '(min-width: 1024px) 25vw, 50vw',
			'class'    => 'absolute inset-0 w-full h-full object-cover',
			'alt'      => $lp_alt,
		)
	);
	?>
</button>
<el-dialog data-element="dialog" data-template="image">
	<dialog
		id="<?php echo esc_attr( $lp_dialog_id ); ?>"
		aria-label="<?php echo esc_attr( $lp_alt ? $lp_alt : __( 'Gallery image', 'londonparkour_v8' ) ); ?>"
		class="fixed inset-0 z-50 m-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent p-4 open:flex open:items-center open:justify-center"
	>
		<button
			type="button"
			command="close"
			commandfor="<?php echo esc_attr( $lp_dialog_id ); ?>"
			class="absolute inset-0 z-0 cursor-default bg-neutral/70 backdrop-blur-[7px]"
			aria-label="<?php esc_attr_e( 'Close image', 'londonparkour_v8' ); ?>"
		></button>
		<el-dialog-panel class="relative z-10 block w-full max-w-5xl overflow-hidden bg-neutral border border-neutral-content/10 shadow-xl">
			<button
				type="button"
				command="close"
				commandfor="<?php echo esc_attr( $lp_dialog_id ); ?>"
				class="absolute right-2 top-2 z-20 btn btn-sm btn-circle btn-ghost text-neutral-content"
				aria-label="<?php esc_attr_e( 'Close image', 'londonparkour_v8' ); ?>"
			>✕</button>
			<?php
			lp_part(
				'components/media-photo',
				array(
					'image_id' => $lp_image_id,
					'layout'   => 'plain',
					'size'     => 'lp_wide',
					'sizes'    => '90vw',
					'alt'      => $lp_alt,
				)
			);
			?>
		</el-dialog-panel>
	</dialog>
</el-dialog>
