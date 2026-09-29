<?php
/**
 * PageOnward — the previous/next pair that closes a page.
 *
 * Ported from src/stories/Components/PageOnward/PageOnward.js.
 *
 * `rail` is the full band with its own ground and gutter; `bare` is the same
 * pair with no chrome, for a caller that already owns the band. Both source
 * rail paddings fall inside the Utopia `xl` step, so both resolve to
 * `scale-xl`, and the gutter is the layout contract's `px-6 lg:px-16`.
 *
 * A missing side still emits an empty <span> so the present side keeps its
 * alignment in the flex row.
 *
 * @param array  $args['prev']       array( 'label' => …, 'href' => …, 'keyword' => … ).
 * @param array  $args['next']       Same shape.
 * @param string $args['aria_label'] Default 'Page navigation'.
 * @param string $args['surface']    fill|page. Default 'fill'.
 * @param string $args['variant']    rail|bare. Default 'rail'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'fill' => array(
		'root'  => 'bg-primary',
		'rule'  => 'border-primary-content/15',
		'ink'   => 'text-primary-content',
		'muted' => 'text-primary-content/70',
		'ring'  => 'focus-visible:outline-primary-content',
	),
	'page' => array(
		'root'  => 'bg-base-100',
		'rule'  => 'border-base-300',
		'ink'   => 'text-base-content',
		'muted' => 'text-base-content/65',
		'ring'  => 'focus-visible:outline-base-content',
	),
);

$lp_chrome = array(
	'rail' => 'pt-scale-xl px-6 lg:px-16 pb-scale-xl',
	'bare' => '',
);

$lp_aligns = array(
	'left'  => 'items-start text-left',
	'right' => 'items-end text-right',
);

$lp_surf    = $lp_surfaces[ $args['surface'] ?? 'fill' ] ?? $lp_surfaces['fill'];
$lp_variant = (string) ( $args['variant'] ?? 'rail' );

// 'bare' is deliberately an empty string, so this must key on existence — not
// on truthiness, which would coerce it back to 'rail'.
$lp_chrome_class = array_key_exists( $lp_variant, $lp_chrome ) ? $lp_chrome[ $lp_variant ] : $lp_chrome['rail'];
$lp_outer        = 'bare' === $lp_variant ? $lp_chrome_class : lp_classes( $lp_surf['root'], $lp_chrome_class );

$lp_aria_label = (string) ( $args['aria_label'] ?? 'Page navigation' );

/** Arrows are decorative — keep them out of the accessible name. */
$lp_arrow = static function ( $lp_keyword ) {
	$lp_html = esc_html( $lp_keyword );
	$lp_html = preg_replace( '/^(←)/u', '<span aria-hidden="true">$1</span>', $lp_html );
	return preg_replace( '/(→)$/u', '<span aria-hidden="true">$1</span>', $lp_html );
};

/** One side of the pair. An empty spacer keeps the other side pinned; no href = static block, never a '#' link. */
$lp_side = static function ( $lp_item, $lp_default_keyword, $lp_align, $lp_surf, $lp_aligns ) use ( $lp_arrow ) {
	$lp_item  = is_array( $lp_item ) ? $lp_item : array();
	$lp_label = (string) ( $lp_item['label'] ?? '' );

	if ( '' === $lp_label ) {
		echo '<span class="flex-1" aria-hidden="true"></span>';
		return;
	}

	$lp_keyword = (string) ( $lp_item['keyword'] ?? $lp_default_keyword );
	$lp_href    = (string) ( $lp_item['href'] ?? '' );
	$lp_cls     = lp_classes( 'group flex-1 min-w-0 flex flex-col gap-[10px]', $lp_aligns[ $lp_align ] );
	$lp_inner   = static function () use ( $lp_keyword, $lp_label, $lp_surf, $lp_arrow ) {
		?>
		<span class="<?php echo lp_classes( 'font-label text-[10px] font-semibold uppercase tracking-[1px]', $lp_surf['muted'] ); ?>"><?php echo $lp_arrow( $lp_keyword ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html'd inside $lp_arrow. ?></span>
		<span class="<?php echo lp_classes( 'font-heading text-[19px] font-medium tracking-[-0.3px]', $lp_surf['ink'], 'group-hover:underline group-focus-visible:underline' ); ?>"><?php echo esc_html( $lp_label ); ?></span>
		<?php
	};
	if ( '' === $lp_href ) :
		?>
		<div class="<?php echo esc_attr( $lp_cls ); ?>"><?php $lp_inner(); ?></div>
	<?php else : ?>
		<a href="<?php echo esc_url( $lp_href ); ?>" class="<?php echo lp_classes( $lp_cls, 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4', $lp_surf['ring'] ); ?>"><?php $lp_inner(); ?></a>
	<?php
	endif;
};
?>
<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="<?php echo esc_attr( $lp_outer ); ?>" data-component="page-onward">
	<div class="<?php echo lp_classes( 'flex items-start gap-6 sm:gap-14 pt-[26px] border-t', $lp_surf['rule'] ); ?>">
		<?php $lp_side( $args['prev'] ?? null, '← Previous', 'left', $lp_surf, $lp_aligns ); ?>
		<?php $lp_side( $args['next'] ?? null, 'Next →', 'right', $lp_surf, $lp_aligns ); ?>
	</div>
</nav>
