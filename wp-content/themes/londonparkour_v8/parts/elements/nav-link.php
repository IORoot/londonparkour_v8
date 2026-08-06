<?php
/**
 * NavLink — Concourse design system (london_parkour_V4.pen).
 *
 * Ported from src/stories/Elements/NavLink/NavLink.js. Three design nodes
 * (rest / hover / active) collapse into one component keyed by the `active`
 * boolean, with real CSS `:hover` for the rest state.
 *
 * Accessibility: hover and active render the exact same colour
 * (text-primary) — colour alone cannot tell a visitor which page they're on,
 * so the active link always carries aria-current="page" in addition to the
 * colour change.
 *
 * The .pen node's "gap 9px" only means something once a leading glyph is
 * present — it is the gap between glyph and label. Pass icon_id to use it;
 * without it the link is text-only and the gap is inert. Sibling spacing in
 * a nav row remains the parent's job.
 *
 * @param string $args['label']   Default 'Classes'.
 * @param string $args['href']    Default '#'.
 * @param bool   $args['active']
 * @param string $args['icon_id'] Optional leading glyph, from the icon sprite.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per state — Tailwind v4 scans source text.
$lp_states = array(
	'active'   => 'text-primary',
	'inactive' => 'text-neutral-content hover:text-primary',
);

$lp_label  = (string) ( $args['label'] ?? 'Classes' );
$lp_href   = (string) ( $args['href'] ?? '#' );
$lp_active = ! empty( $args['active'] );
$lp_icon   = (string) ( $args['icon_id'] ?? '' );

$lp_state_class = $lp_active ? $lp_states['active'] : $lp_states['inactive'];
?>
<a href="<?php echo esc_url( $lp_href ); ?>" class="<?php echo lp_classes( 'inline-flex items-center gap-[9px] font-label uppercase text-[12px] font-semibold tracking-[1.1px] transition-colors duration-150', $lp_state_class ); ?>" data-component="nav-link"<?php echo $lp_active ? ' aria-current="page"' : ''; ?>><?php
if ( '' !== $lp_icon ) {
	// Decorative — the label already carries the meaning, and it inherits
	// currentColor so it tracks the hover/active colour for free.
	echo '<span class="shrink-0" aria-hidden="true">';
	lp_icon( $lp_icon, 'w-3.5 h-3.5' );
	echo '</span>';
}
echo esc_html( $lp_label );
?></a>
