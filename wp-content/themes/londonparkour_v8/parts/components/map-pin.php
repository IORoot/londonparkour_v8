<?php
/**
 * MapPin — the inline map label: a marker plus a name and optional sub-line.
 *
 * Ported from src/stories/Components/MapPin/MapPin.js.
 *
 * Positioning contract, carried over verbatim: this renders an inline label
 * row only — no absolute positioning is baked in and it never receives or
 * interprets coordinates. To place it on a map the CALLER positions the
 * wrapper it puts around this part.
 *
 * `variant` picks the marker: `ring` is the hollow bordered dot, `icon` is a
 * sprite glyph. `flagship` recolours either one. `label` false keeps only the
 * marker chip (dense spot markers).
 *
 * @param string $args['name']
 * @param string $args['sub']      Optional second line.
 * @param string $args['variant']  ring|icon. Default 'ring'.
 * @param string $args['icon_id']  variant=icon only. Default 'icon-map-pin'.
 * @param bool   $args['flagship'] Switches the marker to the signal colour.
 * @param bool   $args['label']    When false, marker only (aria-label from name).
 * @param string $args['href']     Renders the pin as one focusable <a>.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_marker_tones = array(
	'default'  => 'text-neutral-content',
	'flagship' => 'text-primary',
);

$lp_root_base        = 'inline-flex items-center gap-[10px] bg-neutral/88 py-[7px] px-[11px]';
$lp_root_compact     = 'inline-flex items-center justify-center bg-neutral/88 p-[7px]';
$lp_root_interactive = 'no-underline cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';

$lp_name    = (string) ( $args['name'] ?? '' );
$lp_sub     = (string) ( $args['sub'] ?? '' );
$lp_variant = (string) ( $args['variant'] ?? 'ring' );
$lp_href    = (string) ( $args['href'] ?? '' );
$lp_label   = ! array_key_exists( 'label', $args ) || ! empty( $args['label'] );
$lp_is_link = '' !== $lp_href;

$lp_marker_tone = empty( $args['flagship'] ) ? $lp_marker_tones['default'] : $lp_marker_tones['flagship'];
$lp_root_shape  = $lp_label ? $lp_root_base : $lp_root_compact;
$lp_root        = $lp_is_link ? $lp_root_shape . ' ' . $lp_root_interactive : $lp_root_shape;
?>
<?php if ( $lp_is_link ) : ?>
<a class="<?php echo esc_attr( $lp_root ); ?>" data-component="map-pin" href="<?php echo esc_url( $lp_href ); ?>"<?php echo ! $lp_label && '' !== $lp_name ? ' aria-label="' . esc_attr( $lp_name ) . '"' : ''; ?>>
<?php else : ?>
<span class="<?php echo esc_attr( $lp_root ); ?>" data-component="map-pin"<?php echo ! $lp_label && '' !== $lp_name ? ' aria-label="' . esc_attr( $lp_name ) . '"' : ''; ?>>
<?php endif; ?>
	<?php if ( 'icon' === $lp_variant ) : ?>
		<?php lp_icon( (string) ( $args['icon_id'] ?? 'icon-map-pin' ), lp_classes( 'w-3 h-3 shrink-0', $lp_marker_tone ) ); ?>
	<?php else : ?>
		<span class="w-3 h-3 shrink-0 rounded-full border-[2.7px] border-neutral-content" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( $lp_label ) : ?>
		<span class="flex flex-col gap-[3px]">
			<span class="font-label text-[11px] font-semibold tracking-[0.9px] text-neutral-content"><?php echo esc_html( $lp_name ); ?></span>
			<?php if ( '' !== $lp_sub ) : ?>
				<span class="font-label text-[9px] font-normal uppercase tracking-[0.7px] text-neutral-content/50"><?php echo esc_html( $lp_sub ); ?></span>
			<?php endif; ?>
		</span>
	<?php endif; ?>
<?php echo $lp_is_link ? '</a>' : '</span>'; ?>
