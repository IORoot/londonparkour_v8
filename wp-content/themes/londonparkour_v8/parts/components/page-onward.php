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
	),
	'page' => array(
		'root'  => 'bg-base-100',
		'rule'  => 'border-base-300',
		'ink'   => 'text-base-content',
		'muted' => 'text-base-content/65',
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

/** One side of the pair; an empty <span> when there is nothing to link to. */
$lp_side = static function ( $lp_item, $lp_default_keyword, $lp_align, $lp_surf, $lp_aligns ) {
	$lp_item  = is_array( $lp_item ) ? $lp_item : array();
	$lp_label = (string) ( $lp_item['label'] ?? '' );

	if ( '' === $lp_label ) {
		echo '<span></span>';
		return;
	}

	$lp_keyword = (string) ( $lp_item['keyword'] ?? $lp_default_keyword );
	?>
	<a href="<?php echo esc_url( (string) ( $lp_item['href'] ?? '#' ) ); ?>" class="<?php echo lp_classes( 'group flex-1 min-w-0 flex flex-col gap-[10px]', $lp_aligns[ $lp_align ] ); ?>">
		<span class="<?php echo lp_classes( 'font-label text-[10px] font-semibold uppercase tracking-[1px]', $lp_surf['muted'] ); ?>"><?php echo esc_html( $lp_keyword ); ?></span>
		<span class="<?php echo lp_classes( 'font-heading text-[19px] font-medium tracking-[-0.3px]', $lp_surf['ink'], 'group-hover:underline' ); ?>"><?php echo esc_html( $lp_label ); ?></span>
	</a>
	<?php
};
?>
<nav aria-label="<?php echo esc_attr( $lp_aria_label ); ?>" class="<?php echo esc_attr( $lp_outer ); ?>" data-component="page-onward">
	<div class="<?php echo lp_classes( 'flex items-start gap-6 sm:gap-14 pt-[26px] border-t', $lp_surf['rule'] ); ?>">
		<?php $lp_side( $args['prev'] ?? null, '← Previous', 'left', $lp_surf, $lp_aligns ); ?>
		<?php $lp_side( $args['next'] ?? null, 'Next →', 'right', $lp_surf, $lp_aligns ); ?>
	</div>
</nav>
