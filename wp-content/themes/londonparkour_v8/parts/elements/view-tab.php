<?php
/**
 * ViewTab — Concourse design system (london_parkour_V4.pen).
 *
 * Ported from src/stories/Elements/ViewTab/ViewTab.js. Two design nodes
 * (active / inactive) collapse into one component keyed by `active`. This is
 * a real interactive control (switches page view modes — Grid/Map/Agenda/
 * Listings), so it renders as a native <button role="tab">: focusable,
 * clickable, operable with Enter/Space out of the box, plus an explicit
 * focus-visible ring. Composing several into a roving-tabindex tablist
 * (arrow-key navigation) is the job of a parent Tabs component — out of
 * scope here.
 *
 * Built on daisyUI's `tab` class for interactive/structural resets only. The
 * `tabs-border`/`tab-active` decorative modifiers are NOT used: daisyUI
 * hardcodes that underline at a fixed 3px / 80% width, which doesn't match
 * the spec's plain 2px, full-width bottom border. The border is built
 * explicitly with border-b-2 instead; every other property is an explicit
 * utility override on top of `tab`.
 *
 * Plain ViewTab still has no hover in the .pen — inactive tabs keep a
 * subtle opacity lift. The `rich` variant (tutorials/classes/docs view rail)
 * hovers onto `bg-primary` with `text-neutral` ink.
 *
 * Deliberate departure: the source's `onClick` is a JS-object callback bound
 * in Storybook's `init()`, not a data-* driven behaviour from the motion
 * layer — there is nothing in assets/js/ to hand it to. It does not come
 * across; wiring a click handler for the rendered tab is the consumer
 * template/JS's job, same as any other interactive element this theme emits.
 *
 * Given a `href` EITHER form renders an <a> instead, for tabs that are really
 * navigation — search.php's filter rail switches results by post type, and the
 * Classes view rail switches between three separate pages. Both are URLs, not
 * view-mode toggles. The link form carries aria-current="page" rather than
 * role="tab"/aria-selected: an <a href> is a link, and mislabelling it as a tab
 * promises keyboard behaviour a link does not have (Port Brief a11y rule).
 * Class strings are identical in both forms.
 *
 * @param string $args['label']   Default 'Grid'.
 * @param bool   $args['active']
 * @param string $args['href']    Renders as a link rather than a tab button.
 * @param string $args['variant'] Omit for the plain tab; 'rich' for view-rail's
 *                                dark two-line tab.
 * @param string $args['meta']    rich only — the second line.
 * @param string $args['icon_id'] rich only. Default 'icon-squares-2x2'.
 * @param int    $args['index']   rich only — emitted as data-tab-index.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per state — Tailwind v4 scans source text.
$lp_states = array(
	'active'   => 'border-b-2 border-base-content text-base-content font-semibold',
	'inactive' => 'border-b-2 border-transparent text-base-content/65 font-normal hover:text-base-content/70',
);

/*
 * The `rich` variant is view-rail's dark two-line tab (icon + label over a
 * meta line, with its own bottom bar) — a different tab shape, not a restyle
 * of the plain one. It lives here because this is where tab markup is defined;
 * view-rail composes it rather than hand-rolling a second <button>.
 */
$lp_rich_states = array(
	'active'   => array(
		'label' => 'text-primary group-hover:text-neutral',
		'meta'  => 'text-neutral-content/80 group-hover:text-neutral',
		'bar'   => 'h-[3px] bg-primary group-hover:bg-neutral',
	),
	'inactive' => array(
		'label' => 'text-neutral-content/65 group-hover:text-neutral',
		'meta'  => 'text-neutral-content/50 group-hover:text-neutral',
		'bar'   => 'h-px bg-neutral-content/15 group-hover:bg-neutral',
	),
);

$lp_label  = (string) ( $args['label'] ?? 'Grid' );
$lp_active = ! empty( $args['active'] );

$lp_state_class = $lp_active ? $lp_states['active'] : $lp_states['inactive'];
$lp_href        = (string) ( $args['href'] ?? '' );

if ( 'rich' === ( $args['variant'] ?? '' ) ) {
	$lp_rich       = $lp_rich_states[ $lp_active ? 'active' : 'inactive' ];
	$lp_meta       = (string) ( $args['meta'] ?? '' );
	$lp_icon_id    = (string) ( $args['icon_id'] ?? 'icon-squares-2x2' );
	$lp_rich_class = 'group relative flex-1 min-w-[160px] h-[66px] flex flex-col text-left transition-colors duration-150 border-r border-neutral-content/10 last:border-r-0 hover:bg-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';
	?>
	<?php if ( '' !== $lp_href ) : ?>
	<a
		href="<?php echo esc_url( $lp_href ); ?>"
		<?php echo $lp_active ? 'aria-current="page"' : ''; ?>
		data-tab-index="<?php echo esc_attr( (string) ( $args['index'] ?? 0 ) ); ?>"
		class="<?php echo esc_attr( $lp_rich_class ); ?>"
		data-component="view-tab"
		data-variant="rich"
	>
	<?php else : ?>
	<button
		type="button"
		role="tab"
		aria-selected="<?php echo $lp_active ? 'true' : 'false'; ?>"
		data-tab-index="<?php echo esc_attr( (string) ( $args['index'] ?? 0 ) ); ?>"
		class="<?php echo esc_attr( $lp_rich_class ); ?>"
		data-component="view-tab"
		data-variant="rich"
	>
	<?php endif; ?>
		<span class="flex-1 flex items-center justify-between gap-5 px-[26px]">
			<span class="<?php echo lp_classes( 'flex items-center gap-[11px]', $lp_rich['label'] ); ?>">
				<?php lp_icon( $lp_icon_id, 'w-[14px] h-[14px] flex-none text-current' ); ?>
				<span class="font-label text-[12px] font-semibold uppercase tracking-[1.2px]"><?php echo esc_html( $lp_label ); ?></span>
			</span>
			<span class="<?php echo lp_classes( 'font-label text-[10px] font-normal uppercase tracking-[0.9px]', $lp_rich['meta'] ); ?>"><?php echo esc_html( $lp_meta ); ?></span>
		</span>
		<span class="<?php echo lp_classes( 'absolute inset-x-0 bottom-0', $lp_rich['bar'] ); ?>" aria-hidden="true"></span>
	<?php echo '' !== $lp_href ? '</a>' : '</button>'; ?>
	<?php
	return;
}

$lp_tab_class = lp_classes( 'tab h-auto rounded-none pt-[17px] px-0 pb-[15px] text-[11px] uppercase tracking-[1px] transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-base-content', $lp_state_class );
?>
<?php if ( '' !== $lp_href ) : ?>
<a
	href="<?php echo esc_url( $lp_href ); ?>"
	<?php echo $lp_active ? 'aria-current="page"' : ''; ?>
	class="<?php echo $lp_tab_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lp_classes() escapes. ?>"
	data-component="view-tab"
><?php echo esc_html( $lp_label ); ?></a>
<?php else : ?>
<button
	type="button"
	role="tab"
	aria-selected="<?php echo $lp_active ? 'true' : 'false'; ?>"
	class="<?php echo $lp_tab_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lp_classes() escapes. ?>"
	data-component="view-tab"
><?php echo esc_html( $lp_label ); ?></button>
<?php endif; ?>
