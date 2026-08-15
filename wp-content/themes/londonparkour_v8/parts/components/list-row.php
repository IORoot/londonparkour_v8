<?php
/**
 * ListRow — index + title + meta row, with a trailing chevron or marker.
 *
 * Ported from src/stories/Components/ListRow/ListRow.js.
 *
 * The trailing chevron is parts/elements/chevron.php (variants
 * `list_row_board`/`list_row_page`, docs/CONSOLIDATION.md §2b) rather than a
 * second copy of that markup; `marker` replaces it with a text label.
 *
 * A11y: `href` renders the whole row as one focusable <a>; without it, a
 * static <div> for a read-only list.
 *
 * @param string $args['index']   Ordinal. Falsy renders none. Default '01'.
 * @param string $args['title']
 * @param string $args['meta']    Uppercase meta line. Falsy renders none.
 * @param string $args['href']    Renders the row as one focusable <a>.
 * @param string $args['icon']    Optional leading glyph; decorative.
 * @param string $args['marker']  Trailing text label, replacing the chevron.
 * @param string $args['surface'] board|page|panel. Default 'board'.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_surfaces = array(
	'board' => array(
		'ground'  => 'bg-secondary hover:bg-neutral',
		'border'  => 'border-neutral-content/10',
		'outline' => 'focus-visible:outline-primary',
		'title'   => 'text-neutral-content',
		'meta'    => 'text-neutral-content/50',
		'icon'    => 'text-neutral-content/50',
		'index'   => 'text-neutral-content/50 group-hover:text-neutral-content/70',
		'marker'  => 'text-neutral-content/50 group-hover:text-primary',
		'chevron' => 'list_row_board',
	),
	'page'  => array(
		'ground'  => 'bg-base-100 hover:bg-base-200',
		'border'  => 'border-base-300',
		'outline' => 'focus-visible:outline-accent',
		'title'   => 'text-base-content',
		'meta'    => 'text-base-content/65',
		'icon'    => 'text-base-content/65',
		'index'   => 'text-base-content/65 group-hover:text-base-content/80',
		'marker'  => 'text-base-content/65 group-hover:text-accent',
		'chevron' => 'list_row_page',
	),
	// $v2_surface white band. Same ink family as page. Docs Index on J8MoSB.
	'panel' => array(
		'ground'  => 'bg-base-200 hover:bg-base-100',
		'border'  => 'border-base-300',
		'outline' => 'focus-visible:outline-accent',
		'title'   => 'text-base-content',
		'meta'    => 'text-base-content/65',
		'icon'    => 'text-base-content/65',
		'index'   => 'text-base-content/65 group-hover:text-base-content/80',
		'marker'  => 'text-base-content/65 group-hover:text-accent',
		'chevron' => 'list_row_page',
	),
);

$lp_root_prefix      = 'group relative flex items-center gap-[14px] w-full py-[13px] px-[16px] sm:px-[22px]';
$lp_root_tail        = 'transition-colors duration-150 no-underline text-left';
$lp_root_interactive = 'cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px]';

$lp_tone = $lp_surfaces[ $args['surface'] ?? 'board' ] ?? $lp_surfaces['board'];

$lp_index   = (string) ( $args['index'] ?? '01' );
$lp_title   = (string) ( $args['title'] ?? 'Adult Beginner Class' );
$lp_meta    = (string) ( $args['meta'] ?? 'VAUXHALL · THURSDAYS 18:00 · WITH LEON' );
$lp_href    = (string) ( $args['href'] ?? '' );
$lp_icon_id = (string) ( $args['icon'] ?? '' );
$lp_marker  = (string) ( $args['marker'] ?? '' );
$lp_is_link = '' !== $lp_href;

$lp_root = lp_classes(
	$lp_root_prefix,
	$lp_tone['ground'],
	'border-b',
	$lp_tone['border'],
	$lp_root_tail,
	$lp_is_link ? $lp_root_interactive : '',
	$lp_is_link ? $lp_tone['outline'] : ''
);
?>
<?php if ( $lp_is_link ) : ?>
<a class="<?php echo $lp_root; ?>" data-component="list-row" href="<?php echo esc_url( $lp_href ); ?>">
<?php else : ?>
<div class="<?php echo $lp_root; ?>" data-component="list-row">
<?php endif; ?>
	<?php if ( '' !== $lp_icon_id ) : ?>
		<span class="<?php echo lp_classes( $lp_tone['icon'], 'shrink-0' ); ?>" aria-hidden="true"><?php lp_icon( $lp_icon_id, 'w-3.5 h-3.5' ); ?></span>
	<?php endif; ?>
	<?php if ( '' !== $lp_index ) : ?>
		<span class="<?php echo lp_classes( 'font-label text-[10px] font-normal tracking-[0.8px]', $lp_tone['index'], 'transition-colors duration-150 min-w-[20px] shrink-0 whitespace-nowrap' ); ?>"><?php echo esc_html( $lp_index ); ?></span>
	<?php endif; ?>
	<div class="flex-1 min-w-0 flex flex-col gap-[5px]">
		<p class="<?php echo lp_classes( 'font-heading text-[15px] font-medium tracking-[-0.2px]', $lp_tone['title'], 'truncate' ); ?>"><?php echo esc_html( $lp_title ); ?></p>
		<?php if ( '' !== $lp_meta ) : ?>
			<p class="<?php echo lp_classes( 'font-label text-[10px] font-normal tracking-[0.6px] uppercase', $lp_tone['meta'], 'truncate' ); ?>"><?php echo esc_html( $lp_meta ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( '' !== $lp_marker ) : ?>
		<span class="<?php echo lp_classes( 'font-label text-[10px] font-normal tracking-[0.6px] uppercase', $lp_tone['marker'], 'transition-colors duration-150 shrink-0' ); ?>"><?php echo esc_html( $lp_marker ); ?></span>
	<?php else : ?>
		<?php lp_part( 'elements/chevron', array( 'variant' => $lp_tone['chevron'] ) ); ?>
	<?php endif; ?>
<?php echo $lp_is_link ? '</a>' : '</div>'; ?>
