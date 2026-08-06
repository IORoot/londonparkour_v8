<?php
/**
 * Status — Concourse design system (london_parkour_V4.pen).
 *
 * Ported from src/stories/Elements/Status/Status.js. Three design nodes
 * (live / signal / spaces) collapse into one component keyed by `variant`.
 * No background — a dot plus a label.
 *
 * daisyUI's `status` component supplies the dot (`bg-primary` via
 * `status-primary`). The spec wants a 6px dot; daisyUI's size steps are
 * xs 2px / sm 4px / md 8px / lg 12px / xl 16px, so 6px sits exactly between
 * sm and md. `status-sm` (4px) is used: at 10px label text, `status-md` (8px)
 * reads larger than the copy and starts competing with it as the primary
 * signal — the opposite of what the accessibility note below asks for.
 *
 * Accessibility: the dot is decorative (aria-hidden) — the label is the only
 * carrier of meaning and always renders. There is no dot-only variant.
 *
 * `surface` exists because of surface-axis.md rule 2: `base-content` and
 * `neutral` are the same value in both light themes, so the `live` variant's
 * muted label is invisible on a `bg-neutral` band there while looking correct
 * in the dark themes. Pass `surface = 'board'` on the dark band. The
 * signal/spaces variants are `text-primary`, which is ground-independent, so
 * they are identical in the page/board maps below — kept spelled out rather
 * than merged because Tailwind v4 scans source text and these must stay
 * complete literals.
 *
 * `fill` and `accent` (Phase 7, docs/phase7/surface-axis.md) were added for
 * Docs "Passenger Enquiries", which sits on a `bg-primary` band — the dot
 * hardcoded `status-primary` (`bg-primary`), which is invisible against its
 * own fill ground. daisyUI's `status` component has no `-content` colour
 * modifier (`status-primary`/`status-accent`/etc. only), so `fill`/`accent`
 * drop the modifier class entirely and set the dot's background with the
 * plain literal utility instead: `bg-primary-content` on `fill`,
 * `bg-accent-content` on `accent`. The dot stays decorative — the label still
 * carries the meaning — but now stays visible on all four grounds.
 * `signal`/`spaces` follow the matrix's "signal" role for these two surfaces:
 * `text-primary-content` / `text-accent-content`, same value as `ink` on both
 * (neither ground has a distinct highlight colour to reach for, matching
 * GlyphLabel's `fill`/`accent` tone maps).
 *
 * @param string $args['variant'] live|signal|spaces.
 * @param string $args['label']
 * @param string $args['surface'] page|board|fill|accent.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per surface x variant — Tailwind v4 scans source text.
$lp_surfaces = array(
	'page'   => array(
		'live'   => 'text-[10px] font-normal tracking-[0.9px] text-base-content/65',
		'signal' => 'text-[10px] font-semibold tracking-[1.1px] text-primary',
		'spaces' => 'text-[10px] font-semibold tracking-[0.8px] text-primary',
	),
	'board'  => array(
		'live'   => 'text-[10px] font-normal tracking-[0.9px] text-neutral-content/50',
		'signal' => 'text-[10px] font-semibold tracking-[1.1px] text-primary',
		'spaces' => 'text-[10px] font-semibold tracking-[0.8px] text-primary',
	),
	'fill'   => array(
		'live'   => 'text-[10px] font-normal tracking-[0.9px] text-primary-content/70',
		'signal' => 'text-[10px] font-semibold tracking-[1.1px] text-primary-content',
		'spaces' => 'text-[10px] font-semibold tracking-[0.8px] text-primary-content',
	),
	'accent' => array(
		'live'   => 'text-[10px] font-normal tracking-[0.9px] text-accent-content/70',
		'signal' => 'text-[10px] font-semibold tracking-[1.1px] text-accent-content',
		'spaces' => 'text-[10px] font-semibold tracking-[0.8px] text-accent-content',
	),
);

// Dot colour per surface — full literal class strings. `page`/`board` use
// daisyUI's `status-primary` colour modifier; `fill`/`accent` have no
// matching `-content` modifier, so the dot is coloured with the plain
// Tailwind utility instead (see docblock above).
$lp_dots = array(
	'page'   => 'status status-sm status-primary',
	'board'  => 'status status-sm status-primary',
	'fill'   => 'status status-sm bg-primary-content',
	'accent' => 'status status-sm bg-accent-content',
);

$lp_surface = $args['surface'] ?? 'page';
$lp_variant = $args['variant'] ?? 'live';
$lp_label   = (string) ( $args['label'] ?? 'On time' );

$lp_variants    = $lp_surfaces[ $lp_surface ] ?? $lp_surfaces['page'];
$lp_label_class = $lp_variants[ $lp_variant ] ?? $lp_variants['live'];
$lp_dot_class   = $lp_dots[ $lp_surface ] ?? $lp_dots['page'];
?>
<span class="inline-flex items-center gap-2" data-component="status" data-variant="<?php echo esc_attr( $lp_variant ); ?>">
	<span class="<?php echo esc_attr( $lp_dot_class ); ?>" aria-hidden="true"></span>
	<span class="<?php echo esc_attr( lp_classes( 'font-label uppercase', $lp_label_class ) ); ?>"><?php echo esc_html( $lp_label ); ?></span>
</span>
