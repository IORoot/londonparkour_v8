<?php
/**
 * Button — the only place button markup exists.
 *
 * Ported from src/stories/Elements/Button/Button.js. Built on daisyUI's own
 * `.btn` so it picks up the theme's form tokens (--radius-field: 0, --border,
 * depth/noise off) rather than hand-rolling sharp corners. Geometry and hover
 * live in assets/css/components/button.css, scoped under `.btn`.
 *
 * Renders <a href> — a real link, never role="button" — when given a href, and
 * <button type="button"> otherwise. A disabled anchor cannot take the disabled
 * attribute, so it gets btn-disabled + tabindex="-1" + aria-disabled instead.
 *
 * @param string $args['label']            Visible text.
 * @param string $args['href']             Renders an anchor when set.
 * @param string $args['variant']          primary|inverse|ghost|destructive|icon|band|band_text.
 * @param bool   $args['disabled']
 * @param string $args['type']             button|submit, for the button form.
 * @param string $args['aria_label']       Required for variant=icon.
 * @param string $args['icon_id']          Glyph for variant=icon.
 * @param string $args['trailing_icon_id'] Glyph after the label.
 * @param string $args['target']
 * @param string $args['command']          @tailwindplus/elements dialog trigger.
 * @param string $args['command_for']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/*
 * Whole literal strings per variant. Tailwind v4 scans source text, so a class
 * must never be assembled by interpolating a variant name — this lookup is the
 * pattern every part in this theme follows.
 */
$lp_variants = array(
	'primary'     => 'btn btn-primary',
	'inverse'     => 'btn btn-neutral',
	'ghost'       => 'btn btn-outline',
	'destructive' => 'btn btn-error',
	'icon'        => 'btn btn-primary btn-square',
	/*
	 * The flush, full-width label+icon band. Closes the gap docs/CONSOLIDATION.md
	 * §1b recorded: aside-panel's CTA and both site-nav CTAs each hand-rolled
	 * this because every variant above is label-only or icon-only at a fixed
	 * padding, and none fills its container with a justify-between split.
	 * Deliberately carries no `btn` — daisyUI's padding and height are exactly
	 * what this variant must not inherit.
	 */
	'band'        => 'flex items-center justify-between gap-[12px] w-full h-[60px] px-[22px] bg-primary text-primary-content hover:bg-neutral hover:text-primary transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary',
	/*
	 * The dark band's bare text control — SearchResults' query-bar `CLEAR ✕`
	 * (`A1PesB`), a real <button type="reset"> inside the search form. It is a
	 * button variant rather than a text-link variant because every text-link
	 * variant renders an <a href> and a form reset has no href. `shrink-0` is
	 * a call-site layout modifier, not part of the atom — pass it via
	 * $args['class'], the same treatment text-link gives `whitespace-nowrap`.
	 */
	'band_text'   => 'font-label text-[10px] font-semibold uppercase tracking-[0.9px] text-neutral-content/50 hover:text-neutral-content transition-colors duration-150',
);

$lp_variant  = $args['variant'] ?? 'primary';
$lp_label    = (string) ( $args['label'] ?? '' );
$lp_href     = (string) ( $args['href'] ?? '' );
$lp_disabled = ! empty( $args['disabled'] );
$lp_is_icon  = 'icon' === $lp_variant;
$lp_is_band  = 'band' === $lp_variant;
$lp_is_link  = '' !== $lp_href;

$lp_classes = $lp_variants[ $lp_variant ] ?? $lp_variants['primary'];

if ( $lp_is_link && $lp_disabled ) {
	$lp_classes .= ' btn-disabled';
}

if ( ! empty( $args['class'] ) ) {
	$lp_classes .= ' ' . $args['class'];
}

$lp_attrs = array( 'data-component' => 'button' );

if ( $lp_is_link ) {
	if ( $lp_disabled ) {
		$lp_attrs['tabindex']      = '-1';
		$lp_attrs['aria-disabled'] = 'true';
	}
	if ( ! empty( $args['target'] ) ) {
		$lp_attrs['target'] = $args['target'];
		$lp_attrs['rel']    = 'noopener';
	}
}

// An icon-only button has no text, so it needs an explicit accessible name.
$lp_name = (string) ( $args['aria_label'] ?? ( $lp_is_icon ? $lp_label : '' ) );
if ( '' !== $lp_name ) {
	$lp_attrs['aria-label'] = $lp_name;
}

foreach ( array( 'command', 'command_for' ) as $lp_pass ) {
	if ( ! empty( $args[ $lp_pass ] ) ) {
		$lp_attrs[ str_replace( '_', '', $lp_pass ) ] = $args[ $lp_pass ];
	}
}

if ( '' === $lp_label && ! $lp_is_icon ) {
	return;
}

/** The inner content, identical for the <a> and <button> forms. */
$lp_inner = static function () use ( $lp_is_icon, $lp_is_band, $lp_label, $args ) {
	if ( $lp_is_icon ) {
		lp_icon( $args['icon_id'] ?? 'icon-arrow-right', 'w-5 h-5' );
		return;
	}

	// The band splits its label and icon to opposite ends, so the label needs
	// its own element to be a flex child rather than a bare text node.
	if ( $lp_is_band ) {
		printf(
			'<span class="font-label text-[12px] font-semibold uppercase tracking-[1px]">%s</span>',
			esc_html( $lp_label )
		);
		lp_icon( $args['trailing_icon_id'] ?? 'icon-arrow-right', 'w-[14px] h-[14px]' );
		return;
	}

	echo esc_html( $lp_label );
	if ( ! empty( $args['trailing_icon_id'] ) ) {
		lp_icon( $args['trailing_icon_id'], 'w-3.5 h-3.5 shrink-0' );
	}
};
?>
<?php if ( $lp_is_link ) : ?>
	<a class="<?php echo esc_attr( $lp_classes ); ?>" href="<?php echo esc_url( $lp_href ); ?>"
		<?php foreach ( $lp_attrs as $lp_k => $lp_v ) : ?>
		<?php echo esc_attr( $lp_k ); ?>="<?php echo esc_attr( $lp_v ); ?>"
		<?php endforeach; ?>
	>
		<?php $lp_inner(); ?>
	</a>
<?php else : ?>
	<button class="<?php echo esc_attr( $lp_classes ); ?>" type="<?php echo esc_attr( $args['type'] ?? 'button' ); ?>"
		<?php echo $lp_disabled ? 'disabled' : ''; ?>
		<?php foreach ( $lp_attrs as $lp_k => $lp_v ) : ?>
		<?php echo esc_attr( $lp_k ); ?>="<?php echo esc_attr( $lp_v ); ?>"
		<?php endforeach; ?>
	>
		<?php $lp_inner(); ?>
	</button>
<?php endif; ?>
