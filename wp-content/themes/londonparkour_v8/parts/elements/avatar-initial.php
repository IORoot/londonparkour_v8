<?php
/**
 * AvatarInitial — the square initial avatar: a coloured box with one letter.
 *
 * Extracted so byline and blog-card share one copy of this markup. Both build
 * the identical box (docs/CONSOLIDATION.md §4b measured them byte-identical),
 * but §4b's suggestion — that blog-card simply compose byline — does not work:
 * byline always renders the person's name alongside the avatar, and blog-card
 * renders that name itself in a deliberately different voice (font-body, not
 * font-heading). Composing byline printed the name twice. So the avatar, and
 * only the avatar, is promoted here; the surrounding text stays each caller's.
 *
 * A11y: `decorative` is the source's own split. In byline the avatar is
 * aria-hidden because the initial is a stand-in for a photo and the adjacent
 * name carries identity; blog-card's source does not hide it. Neither is
 * changed here.
 *
 * daisyUI: `avatar avatar-placeholder`. The system's --radius-* is 0, so the
 * placeholder box is square with no rounded-* class.
 *
 * @param string $args['name']       The initial is its first character.
 * @param string $args['size']       md|sm|lg. Default 'md'.
 * @param string $args['surface']    page|board|accent. Default 'page'.
 * @param bool   $args['decorative'] aria-hidden the wrapper. Default true.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'page'   => 'bg-neutral text-neutral-content',
	'board'  => 'bg-neutral-content text-neutral',
	'accent' => 'bg-accent-content text-accent',
);

$lp_sizes = array(
	'md' => array(
		'box'  => 'w-[34px] h-[34px]',
		'text' => 'text-[14px]',
	),
	'sm' => array(
		'box'  => 'w-[26px] h-[26px]',
		'text' => 'text-[11px]',
	),
	// "Your Coach" (Classes/Class Detail) — a 104×126 portrait, not a square.
	'lg' => array(
		'box'  => 'w-[104px] h-[126px]',
		'text' => 'text-[36px]',
	),
);

$lp_surf = $lp_surfaces[ $args['surface'] ?? 'page' ] ?? $lp_surfaces['page'];
$lp_size = $lp_sizes[ $args['size'] ?? 'md' ] ?? $lp_sizes['md'];

$lp_initial = mb_strtoupper( mb_substr( trim( (string) ( $args['name'] ?? '' ) ), 0, 1 ) );
if ( '' === $lp_initial ) {
	$lp_initial = '?';
}

$lp_decorative = ! array_key_exists( 'decorative', $args ) || ! empty( $args['decorative'] );
?>
<div class="avatar avatar-placeholder"<?php echo $lp_decorative ? ' aria-hidden="true"' : ''; ?>>
	<div class="<?php echo lp_classes( $lp_surf, $lp_size['box'], 'flex items-center justify-center' ); ?>">
		<span class="<?php echo lp_classes( $lp_size['text'], 'font-semibold' ); ?>"><?php echo esc_html( $lp_initial ); ?></span>
	</div>
</div>
